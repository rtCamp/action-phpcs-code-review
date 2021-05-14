<?php

/*
 * Log information to the console.
 * Include timestamp, and any debug-data
 * our caller might pass us.
 */

function vipgoci_log(
	$str,
	$debug_data = array(),
	$debug_level = 0,
	$irc = false
) {
	global $vipgoci_debug_level;

	/*
	 * Determine if to log the message; if
	 * debug-level of the message is not high
	 * enough compared to the debug-level specified
	 * to be the threshold, do not print it, but
	 * otherwise, do print it,
	 */

	if ( $debug_level > $vipgoci_debug_level ) {
		return;
	}

	echo '[ ' . date( 'c' ) . ' -- ' . (int) $debug_level . ' ]  ' .
		$str .
		'; ' .
		print_r(
			json_encode(
				$debug_data,
				JSON_PRETTY_PRINT
			),
			true
		) .
		PHP_EOL;

	/*
	 * Send to IRC API as well if asked
	 * to do so. Include debugging information as well.
	 */
	if ( true === $irc ) {
		vipgoci_irc_api_alert_queue(
			$str .
				'; ' .
				print_r(
					json_encode(
						$debug_data
					),
					true
				)
		);
	}
}

/**
 * Exit program, using vipgoci_log() to print a
 * message before doing so.
 *
 * @codeCoverageIgnore
 */

function vipgoci_sysexit(
	$str,
	$debug_data = array(),
	$exit_status = VIPGOCI_EXIT_USAGE_ERROR
) {
	if ( $exit_status === VIPGOCI_EXIT_USAGE_ERROR ) {
		$str = 'Usage: ' . $str;
	}

	vipgoci_log(
		$str,
		$debug_data,
		0
	);

	exit( $exit_status );
}

/*
 * Check if a particular set of fields exist
 * in a target array and if their values match a set
 * given. Will return an array describing
 * which items of the array contain all the fields
 * and the matching values.
 *
 * Example:
 *	$fields_arr = array(
 *		'a'	=> 920,
 *		'b'	=> 700,
 *	);
 *
 *	$data_arr = array(
 *		array(
 *			'a'	=> 920,
 *			'b'	=> 500,
 *			'c'	=> 0,
 *			'd'	=> 1,
 *			...
 *		),
 *		array(
 *			'a'	=> 920,
 *			'b'	=> 700,
 *			'c'	=> 0,
 *			'd'	=> 2,
 *			...
 *		),
 *	);
 *
 *	$res = vipgoci_find_fields_in_array(
 *		$fields_arr, $data_arr
 *	);
 *
 *	$res will be:
 *	array(
 *		0 => false,
 *		1 => true,
 *	);
 */
function vipgoci_find_fields_in_array( $fields_arr, $data_arr ) {
	$res_arr = array();

	for(
		$data_item_cnt = 0;
		$data_item_cnt < count( $data_arr );
		$data_item_cnt++
	) {
		$res_arr[ $data_item_cnt ] = 0;

		foreach( $fields_arr as $field_name => $field_values ) {
			if ( ! array_key_exists( $field_name, $data_arr[ $data_item_cnt ] ) ) {
				continue;
			}

			foreach( $field_values as $field_value_item ) {
				if ( $data_arr[ $data_item_cnt ][ $field_name ] === $field_value_item ) {
					$res_arr[ $data_item_cnt ]++;

					/*
					 * Once we find a match, stop searching.
					 * This is to safeguard against any kind of
					 * multiple matches (which though are nearly
					 * impossible).
					 */
					break;
				}
			}
		}

		$res_arr[
			$data_item_cnt
		] = (
			$res_arr[ $data_item_cnt ]
			===
			count( array_keys( $fields_arr ) )
		);
	}

	return $res_arr;
}

/*
 * Convert a string that contains "true", "false" or
 * "null" to a variable of that type.
 */
function vipgoci_convert_string_to_type( $str ) {
	switch( $str ) {
		case 'true':
			$ret = true;
			break;

		case 'false':
			$ret = false;
			break;

		case 'null':
			$ret = null;
			break;

		default:
			$ret = $str;
			break;
	}

	return $ret;
}

/*
 * Given a patch-file, the function will return an
 * associative array, mapping the patch-file
 * to the raw committed file.
 *
 * In the resulting array, the keys represent every
 * line in the patch (except for the "@@" lines),
 * while the values represent line-number in the
 * raw committed line. Some keys might point
 * to empty values, in which case there is no
 * relation between the two.
 */

function vipgoci_patch_changed_lines(
	string $local_git_repo,
	string $repo_owner,
	string $repo_name,
	string $github_token,
	string $pr_base_sha,
	string $commit_id,
	string $file_name
): ?array {
	/*
	 * Fetch patch for all files of the Pull-Request
	 */
	$patch_arr = vipgoci_git_diffs_fetch(
		$local_git_repo,
		$repo_owner,
		$repo_name,
		$github_token,
		$pr_base_sha,
		$commit_id,
		false,
		false,
		false
	);

	/*
	 * No such file found, return with error
	 */
	if ( ! isset(
		$patch_arr['files'][ $file_name ]
	) ) {
		return null;
	}

	/*
	 * Get patch for the relevant file
	 * our caller is interested in
	 */

	$lines_arr = explode(
		"\n",
		$patch_arr['files'][ $file_name ]
	);

	$lines_changed = array();

	$i = 1;

	foreach ( $lines_arr as $line ) {
		preg_match_all(
			"/^@@\s+[-\+]([0-9]+,[0-9]+)\s+[-\+]([0-9]+,[0-9]+)\s+@@/",
			$line,
			$matches
		);

		if ( ! empty( $matches[0] ) ) {
			$start_end = explode(
				',',
				$matches[2][0]
			);


			$i = $start_end[0];


			$lines_changed[] = null;
		}

		else if ( empty( $matches[0] ) ) {
			if ( empty( $line[0] ) ) {
				// Do nothing
			}

			else if (
				( $line[0] == '-' ) ||
				( $line[0] == '\\' )
			) {
				$lines_changed[] = null;
			}

			else if (
				( $line[0] == '+' ) ||
				( $line[0] == " " ) ||
				( $line[0] == "\t" )
			) {
				$lines_changed[] = $i++;
			}
		}
	}

	/*
	 * In certain edge-cases, line 1 in the patch
	 * will refer to line 0 in the code, which
	 * is not what we want. In these cases, we
	 * simply hard-code line 1 in the patch to match
	 * with line 1 in the code.
	 */
	if (
		( isset( $lines_changed[1] ) ) &&
		(
			( $lines_changed[1] === null ) ||
			( $lines_changed[1] === 0 )
		)
		||
		( ! isset( $lines_changed[1] ) )
	) {
		$lines_changed[1] = 1;
	}

	return $lines_changed;
}


/*
 * Get a specific item from in-memory cache based on
 * $cache_id_arr if $data is null, or if $data is not null,
 * add a specific item to cache.
 *
 * The data is stored in an associative array, with
 * key being an array (or anything else) -- $cache_id_arr --,
 * and used to identify the data up on retrieval.
 *
 * If the data being cached is an object, we make a copy of it,
 * and then store it. When the cached data is being retrieved,
 * we return a copy of the cached data.
 */

function vipgoci_cache( $cache_id_arr, $data = null ) {
	global $vipgoci_cache_buffer;

	/*
	 * Special invocation: Allow for
	 * the cache to be cleared.
	 */
	if (
		( is_string(
			$cache_id_arr
		) )
		&&
		(
			VIPGOCI_CACHE_CLEAR ===
			$cache_id_arr
		)
	) {
		$vipgoci_cache_buffer = array();

		return true;
	}

	$cache_id = json_encode(
		$cache_id_arr
	);


	if ( null === $data ) {
		if ( isset( $vipgoci_cache_buffer[ $cache_id ] ) ) {
			$ret = $vipgoci_cache_buffer[ $cache_id ];

			// If an object, copy and return the copy
			if ( is_object( $ret ) ) {
				$ret = clone $ret;
			}

			return $ret;
		}

		else {
			return false;
		}
	}

	// If an object, copy, save it, and return the copy
	if ( is_object( $data ) ) {
		$data = clone $data;
	}

	$vipgoci_cache_buffer[ $cache_id ] = $data;

	return $data;
}


/**
 * Support function for other functions
 * that use the internal cache and need to indicate
 * that information from the cache was used.
 *
 * @codeCoverageIgnore
 */
function vipgoci_cached_indication_str( $cache_used ) {
	return $cache_used ? ' (cached)' : '';
}


/*
 * Create a temporary file, and return the
 * full-path to the file.
 */

function vipgoci_save_temp_file(
	$file_name_prefix,
	$file_name_extension = null,
	$file_contents = ''
) {
	// Determine name for temporary-file
	$temp_file_name = $temp_file_save_status = tempnam(
		sys_get_temp_dir(),
		$file_name_prefix
	);

	/*
	 * If temporary file should have an extension,
	 * make that happen by renaming the currently existing
	 * file.
	 */

	if (
		( null !== $file_name_extension ) &&
		( false !== $temp_file_name )
	) {
		$temp_file_name_old = $temp_file_name;
		$temp_file_name .= '.' . $file_name_extension;

		if ( true !== rename(
			$temp_file_name_old,
			$temp_file_name
		) ) {
			vipgoci_sysexit(
				'Unable to rename temporary file',
				array(
					'temp_file_name_old' => $temp_file_name_old,
					'temp_file_name_new' => $temp_file_name,
				),
				VIPGOCI_EXIT_SYSTEM_PROBLEM
			);
		}

		unset( $temp_file_name_old );
	}

	if ( false !== $temp_file_name ) {
		vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'save_temp_file' );

		$temp_file_save_status = file_put_contents(
			$temp_file_name,
			$file_contents
		);

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'save_temp_file' );
	}

	// Detect possible errors when saving the temporary file
	if ( false === $temp_file_save_status ) {
		vipgoci_sysexit(
			'Could not save file to disk, got ' .
			'an error. Exiting...',

			array(
				'temp_file_name' => $temp_file_name,
			),
			VIPGOCI_EXIT_SYSTEM_PROBLEM
		);
	}

	return $temp_file_name;
}

/*
 * Determine file-extension of a particular file,
 * and return it in lowercase. If it can not be
 * determined, return null.
 */
function vipgoci_file_extension_get( $file_name ) {
	$file_extension = pathinfo(
		$file_name,
		PATHINFO_EXTENSION
	);

	if ( empty( $file_extension ) ) {
		return null;
	}

	$file_extension = strtolower(
		$file_extension
	);

	return $file_extension;
}

/*
 * Return ASCII-art for GitHub, which will then
 * be turned into something more fancy. This is
 * intended to be called when preparing messages/comments
 * to be submitted to GitHub.
 */
function vipgoci_github_transform_to_emojis( $text_string ) {
	switch( strtolower( $text_string ) ) {
		case 'warning':
			return ':warning:';

		case 'error':
			return ':no_entry_sign:';

		case 'info':
			return ':information_source:';
	}

	return '';
}

/*
 * Remove any draft Pull-Requests from the array
 * provided.
 */
function vipgoci_github_pr_remove_drafts( $prs_array ) {
	$prs_array = array_filter(
		$prs_array,
		function( $pr_item ) {
			if ( (bool) $pr_item->draft === true ) {
				return false;
			}

			return true;
		}
	);

	return $prs_array;
}

/*
 * Determine if the presented file has an
 * allowable file-ending, and if the file presented
 * is in a directory that is can be scanned.
 *
 * Note: $filename is expected to be a relative
 * path to the git-repository root.
 */
function vipgoci_filter_file_path(
	$filename,
	$filter
) {
	$file_info_extension = vipgoci_file_extension_get(
		$filename
	);

	$file_dirs = pathinfo(
		$filename,
		PATHINFO_DIRNAME
	);

	/*
	 * If the file does not have an acceptable
	 * file-extension, flag it.
	 */

	$file_ext_match =
		( null !== $filter ) &&
		( isset( $filter['file_extensions'] ) ) &&
		( ! in_array(
			$file_info_extension,
			$filter['file_extensions'],
			true
		) );

	/*
	 * If path to the file contains any non-acceptable
	 * directory-names, skip it.
	 */

	$file_folders_match = false;

	if (
		( null !== $filter ) &&
		( isset( $filter['skip_folders'] ) )
	) {
		/*
		 * Loop through all skip-folders.
		 */
		foreach(
			$filter['skip_folders'] as $tmp_skip_folder_item
		) {
			/*
			 * Note: All 'skip_folder' options should lack '/' at the
			 * end and beginning.
			 *
			 * $filename we expect to be a relative path.
			 */

			$file_folders_match = strpos(
				$filename,
				$tmp_skip_folder_item . '/'
			);

			/*
			 * Check if the file matches any of the folders
			 * that are to be skipped -- note that we enforce
			 * that the folder has to be at the root of the
			 * path to be a match.
			 */
			if (
				( false !== $file_folders_match ) &&
				( is_numeric( $file_folders_match ) ) &&
				( 0 === $file_folders_match )
			) {
				$file_folders_match = true;
				break;
			}
		}
	}

	/*
	 * Do the actual skipping of file,
	 * if either of the conditions are fulfiled.
	 */

	if (
		( true === $file_ext_match ) ||
		( true === $file_folders_match )
	) {
		vipgoci_log(
			'Skipping file that does not seem ' .
				'to be a file matching ' .
				'filter-criteria',

			array(
				'filename' =>
					$filename,

				'filter' =>
					$filter,

				'matches' => array(
					'file_ext_match' => $file_ext_match,
					'file_folders_match' => $file_folders_match,
				),
			),
			2
		);

		return false;
	}

	return true;
}


/*
 * Recursively scan the git repository,
 * returning list of files that exist in
 * it, making sure to filter the result
 *
 * Note: Do not call with $base_path parameter,
 * that is reserved for internal use only.
 */
function vipgoci_scandir_git_repo( $path, $filter, $base_path = null ) {
	$result = array();

	vipgoci_log(
		'Fetching git-tree using scandir()',

		array(
			'path' => $path,
			'filter' => $filter,
			'base_path' => $base_path,
		),
		2
	);

	/*
	 * If no base path is given,
	 * use $path. This will be used
	 * when making sure we do not
	 * accidentally filter by the filesystem
	 * outside of the git-repository (see below).
 	 */
	if ( null === $base_path ) {
		$base_path = $path;
	}

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'git_repo_scandir' );

	$cdir = scandir( $path );

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'git_repo_scandir' );


	foreach ( $cdir as $key => $value ) {
		if ( in_array(
			$value,
			array( '.', '..', '.git' )
		) ) {
			// Skip '.' and '..'
			continue;
		}


		if ( is_dir(
			$path . DIRECTORY_SEPARATOR . $value
		) ) {
			/*
			 * A directory, traverse into, get files,
			 * amend the results
			 */
			$tmp_result = vipgoci_scandir_git_repo(
				$path . DIRECTORY_SEPARATOR . $value,
				$filter,
				$base_path
			);

			foreach ( $tmp_result as $tmp_result_item ) {
				$result[] = $value .
					DIRECTORY_SEPARATOR .
					$tmp_result_item;
			}

			continue;
		}

		/*
		 * Filter out files not with desired line-ending
		 * or are located in directories that should be
		 * ignored.
		 */
		if ( null !== $filter ) {
			/*
			 * Remove the portion of the path
			 * that leads to the git repository,
			 * as we only want to filter by files in the
			 * git repository it self here. This is to
			 * make sure "skip_folders" filtering works
			 * correctly and does not accidentally take into
			 * consideration the path leading to the git repository.
			 */
			$file_path_without_git_repo = substr(
				$path . DIRECTORY_SEPARATOR . $value,
				strlen( $base_path ) + 1 // Send in what looks like a relative path
			);

			if ( false === vipgoci_filter_file_path(
				$file_path_without_git_repo,
				$filter
			) ) {
				continue;
			}
		}

		// Not a directory, passed filter, save in array
		$result[] = $value;
	}

	return $result;
}


/*
 * Go through the given blame-log, and
 * return only the items from the log that
 * are found in $relevant_commit_ids.
 */

function vipgoci_blame_filter_commits(
	$blame_log,
	$relevant_commit_ids
) {

	/*
	 * Loop through each file, get a
	 * 'git blame' log for the file, so
	 * so we can filter out issues not
	 * stemming from commits that are a
	 * part of the current Pull-Request.
	 */

	$blame_log_filtered = array();

	foreach ( $blame_log as $blame_log_item ) {
		if ( ! in_array(
			$blame_log_item['commit_id'],
			$relevant_commit_ids,
			true
		) ) {
			continue;
		}

		$blame_log_filtered[] =
			$blame_log_item;
	}

	return $blame_log_filtered;
}


/*
 * Check if the specified comment exists
 * within an array of other comments --
 * this is used to understand if the specific
 * comment has already been submitted earlier.
 */
function vipgoci_github_comment_match(
	$file_issue_path,
	$file_issue_line,
	$file_issue_comment,
	$comments_made
) {

	/*
	 * Construct an index-key made of file:line.
	 */
	$comment_index_key =
		$file_issue_path .
		':' .
		$file_issue_line;


	if ( ! isset(
		$comments_made[
			$comment_index_key
		]
	)) {
		/*
		 * No match on index-key within the
		 * associative array -- the comment has
		 * not been made, so return false.
		 */
		return false;
	}


	/*
	 * Some comment matching the file and line-number
	 * was found -- figure out if it is definately the
	 * same comment.
	 */

	foreach (
		$comments_made[ $comment_index_key ] as
		$comment_made
	) {
		/*
		 * The comment might contain formatting, such
		 * as "Warning: ..." -- remove all of that.
		 */
		$comment_made_body = str_replace(
			array("**", "Warning", "Error", "Info", ":no_entry_sign:", ":warning:", ":information_source:"),
			array("", "", "", "", ""),
			$comment_made->body
		);

		/*
		 * The comment might include severity level
		 * -- remove that.
		 */
		$comment_made_body = preg_replace(
			'/\( severity \d{1,2} \)/',
			'',
			$comment_made_body
		);

		/*
		 * The comment might be prefixed with ': ',
		 * remove that as well.
		 */
		$comment_made_body = ltrim(
			$comment_made_body,
			': '
		);

		/*
		 * The comment might include PHPCS source
		 * of the error at the end (e.g.
		 * "... (*WordPress.WP.AlternativeFunctions.json_encode_json_encode*)."
		 * -- remove the source, the brackets and the ending dot.
		 */
		$comment_made_body = preg_replace(
			'/ \([\*_\.a-zA-Z0-9]+\)\.$/',
			'',
			$comment_made_body
		);

		/*
		 * Transform string to lowercase,
		 * remove ending '.' just in case if
		 * not removed earlier.
		 */
		$comment_made_body = strtolower(
			$comment_made_body
		);

		$comment_made_body = rtrim(
			$comment_made_body,
			'.'
		);

		/*
		 * Transform the string to lowercase,
		 * and remove potential '.' at the end
		 * of it.
		 */
		$file_issue_comment = strtolower(
			$file_issue_comment
		);

		$file_issue_comment = rtrim(
			$file_issue_comment,
			'.'
		);

		/*
		 * Check if comments match, including
		 * if we need to HTML-encode our new comment
		 * (GitHub encodes their comments when
		 * returning them.
		 */
		if (
			(
				$comment_made_body ==
				$file_issue_comment
			)
			||
			(
				$comment_made_body ==
				htmlentities( $file_issue_comment )
			)
		) {
			/* Comment found, return true. */
			return true;
		}
	}

	return false;
}


/*
 * Filter out any issues in the code that were not
 * touched up on by the changed lines -- i.e., any issues
 * that existed prior to the change.
 */
function vipgoci_issues_filter_irrellevant(
	$file_name,
	$file_issues_arr,
	$file_blame_log,
	$pr_item_commits,
	$file_relative_lines
) {
	/*
	 * Filter out any issues
	 * that are due to commits outside
	 * of the Pull-Request
	 */

	$file_blame_log_filtered =
		vipgoci_blame_filter_commits(
			$file_blame_log,
			$pr_item_commits
		);


	$file_issues_ret = array();

	/*
	 * Loop through all the issues affecting
	 * this particular file
	 */
	foreach (
		$file_issues_arr[ $file_name ] as
			$file_issue_key =>
			$file_issue_val
	) {
		$keep_issue = false;

		/*
		 * Filter out issues outside of the blame log
		 */

		foreach ( $file_blame_log_filtered as $blame_log_item ) {
			if (
				$blame_log_item['line_no'] ===
					$file_issue_val['line']
			) {
				$keep_issue = true;
			}
		}

		if ( false === $keep_issue ) {
			continue;
		}

		unset( $keep_issue );

		/*
		 * Filter out any issues that are outside
		 * of the current patch
		 */

		if ( ! isset(
			$file_relative_lines[ $file_issue_val['line'] ]
		) ) {
			continue;
		}

		// Passed all tests, keep this issue
		$file_issues_ret[] = $file_issue_val;
	}

	return $file_issues_ret;
}

/*
 * In case of some issues being reported in duplicate
 * by PHPCS, remove those. Only issues reported
 * twice in the same file on the same line are considered
 * a duplicate.
 */
function vipgoci_issues_filter_duplicate( $file_issues_arr ) {
	$issues_hashes = array();
	$file_issues_arr_new = array();

	foreach(
		$file_issues_arr as
			$issue_item_key => $issue_item_value
	) {
		$issue_item_hash = md5(
			$issue_item_value['message']
		)
		. ':' .
		$issue_item_value['line'];

		if ( in_array( $issue_item_hash, $issues_hashes, true ) ) {
			continue;
		}

		$issues_hashes[] = $issue_item_hash;

		$file_issues_arr_new[] = $issue_item_value;
	}

	return $file_issues_arr_new;
}

/*
 * Sort results to be submitted to GitHub according to
 * severity of issues -- if configured to do so:
 */
function vipgoci_results_sort_by_severity(
	$options,
	&$results
) {

	if ( true !== $options['results-comments-sort'] ) {
		return;
	}

	vipgoci_log(
		'Sorting issues in results according to severity before submission',
		array(
		)
	);


	foreach(
		array_keys(
			$results['issues']
		) as $pr_number
	) {
		$current_pr_results = &$results['issues'][ $pr_number ];

		/*
		 * Temporarily add severity
		 * column so we can sort using that.
		 */
		foreach(
			array_keys( $current_pr_results ) as
				$current_pr_result_item_key
		) {
			$current_pr_results[ $current_pr_result_item_key ][ 'severity'] =
				$current_pr_results[ $current_pr_result_item_key ]['issue']['severity'];
		}

		/*
		 * Do the actual sorting.
		 */
		$severity_column  = array_column(
			$current_pr_results,
			'severity'
		);

		array_multisort(
		        $severity_column,
		        SORT_DESC,
		        $current_pr_results
		);

		/*
		 * Remove severity column
		 * afterwards.
		 */
		foreach(
			array_keys( $current_pr_results ) as
				$current_pr_result_item_key
		) {
			unset(
				$current_pr_results[ $current_pr_result_item_key ][ 'severity']
			);
		}
	}
}


/*
 * Add pagebreak to a Markdown-style comment
 * string -- but only if a pagebreak is not
 * already the latest addition to the comment.
 * If whitespacing is present just after the
 * pagebreak, ignore it and act as if it does
 * not exist.
 */
function vipgoci_markdown_comment_add_pagebreak(
	&$comment,
	$pagebreak_style = '***'
) {
	/*
	 * Get rid of any \n\r strings, and other
	 * whitespaces from $comment.
	 */
	$comment_copy = rtrim( $comment );
	$comment_copy = rtrim( $comment_copy, " \n\r" );

	/*
	 * Find the last pagebreak in the comment.
	 */
	$pagebreak_location = strrpos(
		$comment_copy,
		$pagebreak_style
	);


	/*
	 * If pagebreak is found, and is
	 * at the end of the comment, bail
	 * out and do nothing to the comment.
	 */

	if (
		( false !== $pagebreak_location ) &&
		(
			$pagebreak_location +
			strlen( $pagebreak_style )
		)
		===
		strlen( $comment_copy )
	) {
		return;
	}

	$comment .= $pagebreak_style . "\n\r";
}


/*
 * Sanitize a string, removing any whitespace-characters
 * from the beginning and end, and transform to lowercase.
 */
function vipgoci_sanitize_string( $str ) {
	return strtolower( ltrim( rtrim(
		$str
	) ) );
}

/*
 * Sanitize path, remove any of the specified prefixes
 * if exist.
 */
function vipgoci_sanitize_path_prefix( string $path, array $prefixes ): string {
	foreach( $prefixes as $prefix ) {
		if ( 0 === strpos( $path, $prefix ) ) {
			$path = substr(
				$path,
				strlen( $prefix )
			);

			break;
		}
	}

	return $path;
}

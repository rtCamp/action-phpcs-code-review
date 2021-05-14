<?php

/*
 * Run PHPCS for the file specified, using the
 * appropriate standards. Return the results.
 */

function vipgoci_phpcs_do_scan(
	$filename_tmp,
	$phpcs_path,
	$phpcs_standard,
	$phpcs_sniffs_exclude,
	$phpcs_severity,
	$phpcs_runtime_set
) {
	/*
	 * Run PHPCS from the shell, making sure we escape everything.
	 *
	 * Feed PHPCS the temporary file specified by our caller.
	 */
	$cmd = sprintf(
		'%s %s --severity=%s --report=%s',
		escapeshellcmd( 'php' ),
		escapeshellcmd( $phpcs_path ),
		escapeshellarg( $phpcs_severity ),
		escapeshellarg( 'json' )
	);

	/*
	 * Add standard to the command-line string.
	 */

	if ( is_array(
		$phpcs_standard
	) ) {
		$phpcs_standard = join(
			',',
			$phpcs_standard
		);
	}

	if ( ! empty( $phpcs_standard ) ) {
		$cmd .= sprintf(
			' --standard=%s',
			escapeshellarg( $phpcs_standard )
		);
	}

	/*
	 * If we have sniffs to exclude, add them
	 * to the command-line string.
	 */

	if ( is_array(
		$phpcs_sniffs_exclude
	) ) {
		$phpcs_sniffs_exclude = join(
			',',
			$phpcs_sniffs_exclude
		);
	}

	if ( ! empty( $phpcs_sniffs_exclude ) ) {
		$cmd .= sprintf(
			' --exclude=%s',
			escapeshellarg( $phpcs_sniffs_exclude )
		);
	}


	/*
	 * If we have specific runtime-set values,
	 * put them in them now.
	 */
	if ( ! empty( $phpcs_runtime_set ) ) {
		foreach(
			$phpcs_runtime_set as
				$phpcs_runtime_set_value
		) {
			$cmd .= sprintf(
				' --runtime-set %s %s',
				escapeshellarg( $phpcs_runtime_set_value[0] ),
				escapeshellarg( $phpcs_runtime_set_value[1] )
			);
		}
	}

	/*
	 * Lastly, append the target filename
	 * to the command-line string.
	 */
	$cmd .= sprintf(
		' %s',
		escapeshellarg( $filename_tmp )
	);

	$cmd .= ' 2>&1';

	vipgoci_log(
		'Running PHPCS now',
		array(
			'cmd' => $cmd,
		),
		0
	);

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'phpcs_cli' );

	$result = shell_exec( $cmd );

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'phpcs_cli' );

	/* Remove linebreak PHPCS possibly adds */
	$result = rtrim( $result, "\n" );

	return $result;
}

/*
 * Write a custom PHPCS XML coding standard
 * file to a file specified. Will write out
 * all the PHPCS standards specified along
 * with PHPCS sniffs specified.
 */
function vipgoci_phpcs_write_xml_standard_file(
	$file_name,
	$phpcs_standard,
	$phpcs_sniffs_include
) {

	$xml_doc = xmlwriter_open_memory();

	xmlwriter_set_indent( $xml_doc, 1 );
	xmlwriter_set_indent_string( $xml_doc, "\t" );

	xmlwriter_start_document( $xml_doc, '1.0', 'UTF-8' );

	xmlwriter_start_element( $xml_doc, 'ruleset' );

		xmlwriter_start_attribute( $xml_doc, 'xmlns:xsi' );
			xmlwriter_text( $xml_doc, 'http://www.w3.org/2001/XMLSchema-instance' );
		xmlwriter_end_attribute( $xml_doc );

		xmlwriter_start_attribute( $xml_doc, 'name' );
			xmlwriter_text( $xml_doc, 'PHP_CodeSniffer' );
		xmlwriter_end_attribute( $xml_doc );

		xmlwriter_start_attribute( $xml_doc, 'xsi:noNamespaceSchemaLocation' );
			xmlwriter_text( $xml_doc, 'phpcs.xsd' );
		xmlwriter_end_attribute( $xml_doc );


		xmlwriter_start_element( $xml_doc, 'description' );
			xmlwriter_text( $xml_doc, 'Custom coding standard' );
		xmlwriter_end_element( $xml_doc );

		/*
		 * Merge an array of rulesets and
		 * write them into the XML document.
		 */
		$rulesets_arr = array_merge(
			$phpcs_standard,
			$phpcs_sniffs_include
		);

		foreach( $rulesets_arr as $ruleset_item ) {
			xmlwriter_start_element( $xml_doc, 'rule' );
				xmlwriter_start_attribute( $xml_doc, 'ref' );
					xmlwriter_text( $xml_doc, $ruleset_item );
				xmlwriter_end_attribute( $xml_doc );
			xmlwriter_end_element( $xml_doc );
		}

	xmlwriter_end_element( $xml_doc );

	xmlwriter_end_document( $xml_doc );

	file_put_contents(
		$file_name,
		xmlwriter_output_memory( $xml_doc )
	);

	unset( $xml_doc );

	return $file_name;
}

function vipgoci_phpcs_scan_single_file(
	$options,
	$file_name
) {
	$file_contents = vipgoci_gitrepo_fetch_committed_file(
		$options['repo-owner'],
		$options['repo-name'],
		$options['token'],
		$options['commit'],
		$file_name,
		$options['local-git-repo']
	);

	$file_extension = vipgoci_file_extension_get(
		$file_name
	);

	if ( empty( $file_extension ) ) {
		$file_extension = null;
	}

	$temp_file_name = vipgoci_save_temp_file(
		'vipgoci-phpcs-scan-',
		$file_extension,
		$file_contents
	);

	vipgoci_log(
		'About to PHPCS-scan file',
		array(
			'repo_owner' => $options['repo-owner'],
			'repo_name' => $options['repo-name'],
			'commit_id' => $options['commit'],
			'filename' => $file_name,
			'file_extension' => $file_extension,
			'temp_file_name' => $temp_file_name,
		)
	);


	$file_issues_str = vipgoci_phpcs_do_scan(
		$temp_file_name,
		$options['phpcs-path'],
		$options['phpcs-standard'],
		$options['phpcs-sniffs-exclude'],
		$options['phpcs-severity'],
		$options['phpcs-runtime-set']
	);

	/* Get rid of temporary file */
	unlink( $temp_file_name );

	$file_issues_arr_master = json_decode(
		$file_issues_str,
		true
	);

	/*
	 * Detect errors and report
	 */
	if ( null === $file_issues_arr_master ) {
		vipgoci_log(
			'Error when running PHPCS',
			array(
				'file_issues_str' => $file_issues_str,
			)
		);
	}

	return array(
		'file_issues_arr_master'	=> $file_issues_arr_master,
		'file_issues_str'		=> $file_issues_str,
		'temp_file_name'		=> $temp_file_name,
	);
}


/**
 * Dump output of scan-analysis to a file,
 * if possible.
 *
 * @codeCoverageIgnore
 */

function vipgoci_phpcs_scan_output_dump( $output_file, $data ) {
	if (
		( is_file( $output_file ) ) &&
		( ! is_writeable( $output_file ) )
	) {
		vipgoci_log(
			'File ' .
				$output_file .
				' is not writeable',
			array()
		);
	} else {
		file_put_contents(
			$output_file,
			json_encode(
				$data,
				JSON_PRETTY_PRINT
			),
			FILE_APPEND
		);
	}
}

/*
 * Scan a particular commit which should live within
 * a particular repository on GitHub, and use the specified
 * access-token to gain access.
 */
function vipgoci_phpcs_scan_commit(
	$options,
	&$commit_issues_submit,
	&$commit_issues_stats
) {
	$repo_owner = $options['repo-owner'];
	$repo_name  = $options['repo-name'];
	$commit_id  = $options['commit'];
	$github_token = $options['token'];

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'phpcs_scan_commit' );

	vipgoci_log(
		'About to PHPCS-scan repository',

		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id' => $commit_id,
		)
	);


	/*
	 * First, figure out if a .gitmodules
	 * file was added or modified; if so,
	 * we need to scan the relevant sub-module(s)
	 * specifically.
	 */

	$commit_info = vipgoci_github_fetch_commit_info(
		$repo_owner,
		$repo_name,
		$commit_id,
		$github_token,
		array(
			'file_extensions'
				=> array( 'gitmodules' ),

			'status'
				=> array( 'added', 'modified' ),
		)
	);


	if ( ! empty( $commit_info->files ) ) {
		// FIXME: Do something about the .gitmodule file
	}



	// Fetch list of all Pull-Requests which the commit is a part of
	$prs_implicated = vipgoci_github_prs_implicated(
		$repo_owner,
		$repo_name,
		$commit_id,
		$github_token,
		$options['branches-ignore'],
		$options['skip-draft-prs']
	);


	/*
	 * Get list of all files affected by
	 * each Pull-Request implicated by the commit.
	 */

	vipgoci_log(
		'Fetching list of all files affected by each Pull-Request ' .
			'implicated by the commit',

		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id' => $commit_id,
		)
	);

	$pr_item_files_changed = array();
	$pr_item_files_changed['all'] = array();

	foreach ( $prs_implicated as $pr_item ) {
		/*
		 * Make sure that the PR is defined in the array
		 */
		if ( ! isset( $pr_item_files_changed[ $pr_item->number ] ) ) {
			$pr_item_files_changed[ $pr_item->number ] = [];
		}

		/*
		 * Get list of all files changed
		 * in this Pull-Request.
		 */

		$pr_item_files_tmp = vipgoci_git_diffs_fetch(
			$options['local-git-repo'],
			$options['repo-owner'],
			$options['repo-name'],
			$options['token'],
			$pr_item->base->sha,
			$commit_id,
			false, // exclude renamed files
			false, // exclude removed files
			false, // exclude permission changes
			array(
				'file_extensions' =>
					/*
					 * If SVG-checks are enabled,
					 * include it in the file-extensions
					 */
					array_merge(
						array( 'php', 'js', 'twig' ),
						( $options['svg-checks'] ?
							array( 'svg' ) :
							array()
						)
					),
				'skip_folders' =>
					$options['phpcs-skip-folders'],
			)
		);


		foreach ( $pr_item_files_tmp['files'] as $pr_item_file_name => $_tmp ) {
			if ( in_array(
				$pr_item_file_name,
				$pr_item_files_changed['all'],
				true
			) === false ) {
				$pr_item_files_changed['all'][] =
					$pr_item_file_name;
			}

			if ( in_array(
				$pr_item_file_name,
				$pr_item_files_changed[ $pr_item->number ],
				true
			) === false ) {
				$pr_item_files_changed[
					$pr_item->number
				][] = $pr_item_file_name;
			}
		}
	}


	$files_issues_arr = array();

	/*
	 * Loop through each altered file in all the Pull-Requests,
	 * use PHPCS to scan for issues, save the issues; they will
	 * be processed in the next step.
	 */

	vipgoci_log(
		'About to PHPCS-scan all files affected by any of the ' .
			'Pull-Requests',

		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id' => $commit_id,
			'all_files_changed_by_prs' =>
				$pr_item_files_changed['all'],
		)
	);

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'phpcs_scan_single_file' );

	foreach ( $pr_item_files_changed['all'] as $file_name ) {
		/*
		 * Loop through each file affected by
		 * the commit.
		 */

		$file_extension = vipgoci_file_extension_get(
			$file_name
		);

		/*
		 * If a SVG file, scan using a
		 * custom internal function, otherwise
		 * use PHPCS.
		 *
		 * However, only do this if SVG-checks
		 * is enabled.
		 */
		$scanning_func =
			(
				( 'svg' === $file_extension ) &&
				( $options['svg-checks'] )
			) ?
				'vipgoci_svg_scan_single_file' :
				'vipgoci_phpcs_scan_single_file';

		$tmp_scanning_results = $scanning_func(
			$options,
			$file_name
		);

		$file_issues_arr_master =
			$tmp_scanning_results['file_issues_arr_master'];

		$file_issues_str =
			$tmp_scanning_results['file_issues_str'];

		$temp_file_name =
			$tmp_scanning_results['temp_file_name'];

		/*
		 * Keep statistics on number of lines
		 * and files we scan.
		 */
		vipgoci_stats_per_file(
			$options,
			$file_name,
			'scanned'
		);

		/*
		 * Do sanity-checking
		 */

		if (
			( null === $file_issues_arr_master ) ||
			( ! isset( $file_issues_arr_master['totals'] ) ) ||
			( ! isset( $file_issues_arr_master['files'] ) )
		) {
			vipgoci_log(
				'Failed parsing output from PHPCS',
				array(
					'repo_owner' => $repo_owner,
					'repo_name' => $repo_name,
					'commit_id' => $commit_id,
					'file_issues_arr_master' =>
						$file_issues_arr_master,
					'file_issues_str' =>
						$file_issues_str,
				),
				0,
				true // log to IRC
			);

			/*
			 * No further processing in case of an error.
			 *
			 * Set an empty array just in case to avoid warnings.
			 */
			$files_issues_arr[ $file_name ] = array();

			continue;
		}

		unset( $file_issues_str );

		/*
		 * In some cases filename in PHPCS output
		 * is without leading "/", but usually it is
		 * with leading "/". Make sure we cover both
		 * cases here.
		 */
	
		/* First attempt the default, with leading "/" */
		if ( isset(
			$file_issues_arr_master
				['files']
				[ $temp_file_name ]
		) ) {
			$file_issues_arr_index =
				$temp_file_name;
		}

		/* If this fails, try without leading "/" */
		else if ( isset(
			$file_issues_arr_master
				['files']
				[ ltrim( $temp_file_name, '/' ) ]
		) ) {
			$file_issues_arr_index =
				ltrim( $temp_file_name, '/' );
		}

		/* Everything failed, print error and continue */
		else {
			/* Empty placeholder, to avoid warnings. */
			$files_issues_arr[ $file_name ] = array();

			vipgoci_log(
				'Unable to read results of PHPCS scanning, missing index',
				array(
					'temp_file_name'	=> $temp_file_name,
				)
			);

			continue;
		}

		/*
		 * Make sure items in $file_issues_arr_master have
		 * 'level' key and value.
		 */
		$file_issues_arr_master = array_map(
			function( $item ) {
				$item['level'] = $item['type'];

				return $item;
			},
			$file_issues_arr_master
				['files']
				[ $file_issues_arr_index ]
				['messages']
		);

		/*
		 * Remove any duplicate issues.
		 */
		$file_issues_arr_master = vipgoci_issues_filter_duplicate(
			$file_issues_arr_master
		);

		$files_issues_arr[ $file_name ] = $file_issues_arr_master;

		/*
		 * Output scanning-results if requested
		 */

		if ( ! empty( $options['output'] ) ) {
			vipgoci_phpcs_scan_output_dump(
				$options['output'],
				array(
					'repo_owner'	=> $repo_owner,
					'repo_name'	=> $repo_name,
					'commit_id'	=> $commit_id,
					'filename'	=> $file_name,
					'issues'	=> $file_issues_arr_master,
				)
			);
		}

		/*
		 * Get rid of data, and
		 * attempt to garbage-collect.
		 */
		vipgoci_log(
			'Cleaning up after scanning of file...',
			array()
		);

		unset( $file_contents );
		unset( $file_extension );
		unset( $temp_file_name );
		unset( $file_issues_arr_master );
		unset( $file_issues_str );

		gc_collect_cycles();
	}

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'phpcs_scan_single_file' );

	/*
	 * Loop through each Pull-Request implicated,
	 * get comments made on GitHub already,
	 * then filter out any PHPCS-issues irrelevant
	 * as they are not due to any commit that is part
	 * of the Pull-Request, and skip any PHPCS-issue
	 * already reported. Report the rest, if any.
	 */

	vipgoci_log(
		'Figuring out which comment(s) to submit to GitHub, if any',
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id' => $commit_id,
		)
	);


	foreach ( $prs_implicated as $pr_item ) {
		vipgoci_log(
			'Preparing to process PHPCS scanned files in ' .
				'Pull-Request, to construct results ' .
				'to be submitted',
			array(
				'repo_owner'    => $repo_owner,
				'repo_name'     => $repo_name,
				'commit_id'     => $commit_id,
				'pr_number'     => $pr_item->number,
				'files_changed' =>
					$pr_item_files_changed[ $pr_item->number ]
			)
		);


		/*
		 * Check if user requested to turn off PHPCS
		 * scanning for the Pull-Request by adding a label
		 * to the Pull-Request, and if so, skip scanning.
		 * Make sure to indicate so in the statistics.
		 *
		 * This is only done if allowed via option.
		 */

		if (
			true ===
			$options['phpcs-skip-scanning-via-labels-allowed']
		) {
			$pr_label_skip_phpcs = vipgoci_github_pr_labels_get(
				$repo_owner,
				$repo_name,
				$github_token,
				$pr_item->number,
				'skip-phpcs-scan'
			);

			if ( ! empty( $pr_label_skip_phpcs ) ) {
				vipgoci_log(
					'Label on Pull-Request indicated to ' .
						'skip PHPCS-scanning; ' .
						'scanning will be skipped',
					array(
						'repo_owner'
							=> $repo_owner,

						'repo_name'
							=> $repo_name,

						'commit_id'
							=> $commit_id,

						'pr_number'
							=> $pr_item->number,

						'pr_label_skip_phpcs'
							=> $pr_label_skip_phpcs,
					)
				);

				unset(
					$commit_issues_stats
						[ $pr_item->number ]
				);

				continue;
			}
		}


		/*
		 * Get all commits related to the current
		 * Pull-Request.
		 */
		$pr_item_commits = vipgoci_github_prs_commits_list(
			$repo_owner,
			$repo_name,
			$pr_item->number,
			$github_token
		);


		/*
		 * Loop through each file, get a
		 * 'git blame' log for the file, then
		 * filter out issues stemming
		 * from commits that are not a
		 * part of the current Pull-Request.
		 */

		foreach (
			$pr_item_files_changed[ $pr_item->number ] as
				$_tmp => $file_name
			) {

			/*
			 * Get blame log for file
			 */
			$file_blame_log = vipgoci_gitrepo_blame_for_file(
				$commit_id,
				$file_name,
				$options['local-git-repo']
			);

			/*
			 * Get patch for the file
			 */
			$file_changed_lines = vipgoci_patch_changed_lines(
				$options['local-git-repo'],
				$options['repo-owner'],
				$options['repo-name'],
				$options['token'],
				$pr_item->base->sha,
				$commit_id,
				$file_name
			);

			/*
			 * If no changed lines were available, log
			 * and continue.
			 */
			if ( null === $file_changed_lines ) {
				vipgoci_log(
					'Unable to fetch changed lines for file, ' .
						'skipping scanning',
					array(
						'local-git-repo'	=> $options['local-git-repo'],
						'repo-owner'		=> $options['repo-owner'],
						'repo-name'		=> $options['repo-name'],
						'base_sha'		=> $pr_item->base->sha,
						'commit_id'		=> $commit_id,
						'file_name'		=> $file_name,
					)
				);

				continue;
			}

			$file_relative_lines = @array_flip(
				$file_changed_lines
			);


			/*
			 * Filter the issues we found
			 * previously in this file; remove
			 * the ones that the are not found
			 * in the blame-log (meaning that
			 * they are due to commits outside of
			 * the Pull-Request).
			 */

			$file_issues_arr_filtered = vipgoci_issues_filter_irrellevant(
				$file_name,
				$files_issues_arr,
				$file_blame_log,
				$pr_item_commits,
				$file_relative_lines
			);

			/*
			 * Collect all the issues that
			 * we need to submit about
			 */

			foreach( $file_issues_arr_filtered as
				$file_issue_val_key =>
				$file_issue_val_item
			) {
				$commit_issues_submit[
					$pr_item->number
				][] = array(
					'type'		=> VIPGOCI_STATS_PHPCS,

					'file_name'	=>
						$file_name,

					'file_line'	=>
						$file_relative_lines[
							$file_issue_val_item[
								'line'
						]
					],

					'issue'		=>
						$file_issue_val_item,
				);

				/*
				 * Collect statistics on
				 * number of warnings/errors
				 */

				$commit_issues_stats[
					$pr_item->number
				][
					strtolower(
						$file_issue_val_item[
							'level'
						]
					)
				]++;
			}
		}

		unset( $pr_item_commits );
		unset( $file_blame_log );
		unset( $file_changed_lines );
		unset( $file_relative_lines );
		unset( $file_issues_arr_filtered );

		gc_collect_cycles();
	}

	/*
	 * Clean up a bit
	 */
	vipgoci_log(
		'Cleaning up after PHPCS-scanning...',
		array()
	);

	unset( $prs_implicated );
	unset( $pr_item_files_changed );

	gc_collect_cycles();

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'phpcs_scan_commit' );
}

/*
 * Ask PHPCS for a list of all standards installed.
 * Returns with an array of those standards.
 */

function vipgoci_phpcs_get_all_standards(
	$phpcs_path
) {
	vipgoci_log(
		'Getting active PHPCS standards',
		array(
			'phpcs-path'		=> $phpcs_path,
		)
	);

	$phpcs_standards_arr = array();

	/*
	 * Run PHPCS from the shell, making sure we escape everything.
	 */
	$cmd = sprintf(
		'%s %s -i',
		escapeshellcmd( 'php' ),
		escapeshellcmd( $phpcs_path ),
	);

	vipgoci_log(
		'Running PHPCS now to get standards',
		array(
			'cmd' => $cmd,
		),
		0
	);

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'phpcs_cli' );

	$result = shell_exec( $cmd );

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'phpcs_cli' );

	$result = str_replace(
		array( "The installed coding standards are", " and ", " " ),
		array( "", ",", "" ),
		$result
	);

	$result = trim( $result );

	$phpcs_standards_arr = explode(
		',',
		$result
	);

	unset( $result );

	return $phpcs_standards_arr;
}

/*
 * Ask PHPCS for a list of all sniffs that are active
 * in the specified standard. Returns with an array
 * of active sniffs.
 */
function vipgoci_phpcs_get_sniffs_for_standard(
	$phpcs_path,
	$phpcs_standard
) {
	vipgoci_log(
		'Getting sniffs active in PHPCS standard',
		array(
			'phpcs-path'		=> $phpcs_path,
			'phpcs-standard'	=> $phpcs_standard,
		)
	);

	/*
	 * If array. convert to string.
	 */
	if ( is_array(
		$phpcs_standard
	) ) {
		$phpcs_standard = join(
			',',
			$phpcs_standard
		);
	}

	/*
	 * Run PHPCS from the shell, making sure we escape everything.
	 */
	$cmd = sprintf(
		'%s %s --standard=%s -e -s',
		escapeshellcmd( 'php' ),
		escapeshellcmd( $phpcs_path ),
		escapeshellarg( $phpcs_standard )
	);

	vipgoci_log(
		'Running PHPCS now to get sniffs',
		array(
			'cmd' => $cmd,
		),
		0
	);

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'phpcs_cli' );

	$result = shell_exec( $cmd );

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'phpcs_cli' );

	$sniffs_arr = explode(
		"\n",
		$result
	);

	/*
	 * Filter output: Remove everything
	 * that does not look like a sniff name.
	 */
	$sniffs_arr = array_filter(
		$sniffs_arr,
		function( $line_item ) {
			if ( false === strpos( $line_item, '.' ) ) {
				return false;
			}

			if ( false !== strpos( $line_item, '-' ) ) {
				return false;
			}

			/* Sniff names start with spaces, let them through. */
			if ( 0 === strpos( $line_item, ' ' ) ) {
				return true;
			}

			return false;
		}
	);

	/*
	 * Remove any whitespacing, etc.
	 * from list of sniffs.
	 */
	$sniffs_arr = array_map(
		function( $sniff_item ) {
			return trim( $sniff_item );
		},
		$sniffs_arr
	);

	/*
	 * Remove any potential duplicates.
	 */
	$sniffs_arr = array_unique(
		$sniffs_arr
	);

	/*
	 * Recreate array with fresh
	 * keys.
	 */
	$sniffs_arr = array_values(
		$sniffs_arr
	);

	return $sniffs_arr;
}

/*
 * Check if the sniffs specified in options
 * -- either to remove or set -- are valid.
 *
 * Do this by getting a list of valid sniffs
 * and check if each and every one is in the list.
 */
function vipgoci_phpcs_validate_sniffs_in_options_and_report(
	&$options
) {
	vipgoci_log(
		'Validating sniffs provided in options',
		array(
			'phpcs-path'		=> $options['phpcs-path'],
			'phpcs-standard'	=> $options['phpcs-standard'],
			'phpcs-sniffs-exclude'	=> $options['phpcs-sniffs-exclude'],
			'phpcs-sniffs-include'	=> $options['phpcs-sniffs-include'],
		)
	);

	/*
	 * Get sniffs that are part of
	 * the PHPCS standard specified.
	 */
	$phpcs_sniffs_valid_for_selected_standards = vipgoci_phpcs_get_sniffs_for_standard(
		$options['phpcs-path'],
		$options['phpcs-standard']
	);

	/*
	 * Get all valid sniffs for all standards.
	 */
	$all_standards_arr = vipgoci_phpcs_get_all_standards(
		$options['phpcs-path']
	);

	$phpcs_sniffs_valid_for_all_standards = vipgoci_phpcs_get_sniffs_for_standard(
		$options['phpcs-path'],
		$all_standards_arr
	);

	/*
	 * Create array of invalid sniffs --
	 * sniffs that are specified in options
	 * but are not part of the currently specified
	 * standard.
	 *
	 * Normalise and sort the results.
 	 */
	$phpcs_sniffs_exclude_invalid = array_diff(
		$options['phpcs-sniffs-exclude'],
		$phpcs_sniffs_valid_for_selected_standards
	);

	asort(
		$phpcs_sniffs_exclude_invalid
	);

	$phpcs_sniffs_exclude_invalid = array_values(
		$phpcs_sniffs_exclude_invalid
	);

	/*
	 * Get an array of invalid sniffs -- sniffs
	 * that are not part of any of the standards
	 * available.
	 */
	$phpcs_sniffs_include_invalid = array_diff(
		$options['phpcs-sniffs-include'],
		$phpcs_sniffs_valid_for_all_standards
	);

	asort(
		$phpcs_sniffs_include_invalid
	);

	$phpcs_sniffs_include_invalid = array_values(
		$phpcs_sniffs_include_invalid
	);


	vipgoci_log(
		'Got valid PHPCS sniffs, calculated invalid PHPCS sniffs',
		array(
			'phpcs-sniffs-valid-for-selected-standards' 	=> $phpcs_sniffs_valid_for_selected_standards,
			'phpcs-sniffs-valid-for-all-standards'		=> $phpcs_sniffs_valid_for_all_standards,
			'phpcs-sniffs-exclude-invalid'			=> $phpcs_sniffs_exclude_invalid,
			'phpcs-sniffs-include-invalid'			=> $phpcs_sniffs_include_invalid,
		),
		2
	);

	/*
	 * Prepare to post a message reporting
	 * problems with the options defined, if needed.
	 */
	$tmp_invalid_options_and_sniffs = '';

	if (
		( ! empty( $phpcs_sniffs_include_invalid ) ) ||
		( ! empty( $phpcs_sniffs_exclude_invalid ) )
	) {
		$tmp_invalid_options_and_sniffs .=
			VIPGOCI_PHPCS_INVALID_SNIFFS;

		if ( ! empty( $phpcs_sniffs_include_invalid ) ) {
			$tmp_invalid_options_and_sniffs .=
				sprintf(
					VIPGOCI_PHPCS_INVALID_SNIFFS_CONT,
					'--phpcs-sniffs-include',
					implode(
						', ',
						$phpcs_sniffs_include_invalid
					),
				);
		}

		if ( ! empty( $phpcs_sniffs_exclude_invalid ) ) {
			$tmp_invalid_options_and_sniffs .=
				sprintf(
					VIPGOCI_PHPCS_INVALID_SNIFFS_CONT,
					'--phpcs-sniffs-exclude',
						implode(
						', ',
						$phpcs_sniffs_exclude_invalid
						),
				);
		}

		/*
	 	 * Dynamically remove invalid sniffs from options
		 */
		vipgoci_log(
			'Dynamically removing invalid PHPCS sniffs from options',
			array(
				'phpcs-sniffs-include'		=> $options['phpcs-sniffs-include'],
				'phpcs-sniffs-include-invalid'	=> $phpcs_sniffs_include_invalid,
				'phpcs-sniffs-exclude'		=> $options['phpcs-sniffs-exclude'],
				'phpcs-sniffs-exclude-invalid'	=> $phpcs_sniffs_exclude_invalid,
			)
		);

		if ( ! empty( $phpcs_sniffs_include_invalid ) ) {
			$options['phpcs-sniffs-include'] = array_intersect(
				$options['phpcs-sniffs-include'],
				$phpcs_sniffs_valid_for_all_standards
			);

			$options['phpcs-sniffs-include'] = array_values(
				$options['phpcs-sniffs-include']
			);
		}

		if ( ! empty( $phpcs_sniffs_exclude_invalid ) ) {
			$options['phpcs-sniffs-exclude'] = array_intersect(
				$options['phpcs-sniffs-exclude'],
				$phpcs_sniffs_valid_for_selected_standards
			);

			$options['phpcs-sniffs-exclude'] = array_values(
				$options['phpcs-sniffs-exclude']
			);
		}
	}

	/*
	 * Check if any of the --phpcs-sniffs-exclude items
	 * are also defined in --phpcs-sniffs-include. If so,
	 * skip those in --phpcs-sniffs-include, and report the problem.
	 */

	$phpcs_sniffs_excluded_and_included = array_intersect(
		$options['phpcs-sniffs-exclude'],
		$options['phpcs-sniffs-include']
	);

	if ( ! empty( $phpcs_sniffs_excluded_and_included ) ) {
		if ( ! empty( $tmp_invalid_options_and_sniffs ) ) {
			$tmp_invalid_options_and_sniffs .=
				PHP_EOL . '<hr />' . PHP_EOL;
		}

		$tmp_invalid_options_and_sniffs .=
			VIPGOCI_PHPCS_DUPLICATE_SNIFFS;

		$tmp_invalid_options_and_sniffs .= sprintf(
			VIPGOCI_PHPCS_DUPLICATE_SNIFFS_CONT,
			'--phpcs-sniffs-exclude',
			'--phpcs-sniffs-include',
			implode(
				', ',
				$phpcs_sniffs_excluded_and_included
			)
		);
	}

	if (
		( ! empty( $phpcs_sniffs_include_invalid ) ) ||
		( ! empty( $phpcs_sniffs_exclude_invalid ) ) ||
		( ! empty( $phpcs_sniffs_excluded_and_included ) )
	) {
		/*
		 * Post generic message with error for each Pull-Request
		 * implicated.
		 */

		$prs_implicated = vipgoci_github_prs_implicated(
			$options['repo-owner'],
			$options['repo-name'],
			$options['commit'],
			$options['token'],
			$options['branches-ignore'],
			$options['skip-draft-prs']
		);

		foreach ( $prs_implicated as $pr_item ) {
			vipgoci_github_pr_comments_generic_submit(
				$options['repo-owner'],
				$options['repo-name'],
				$options['token'],
				$pr_item->number,
				/*
				 * Send invalid sniffs message,
				 * but append the sniffs that are invalid.
				 */
				$tmp_invalid_options_and_sniffs,
				$options['commit']
			);
		}

		unset(
			$tmp_invalid_options_and_sniffs
		);
	}

	vipgoci_log(
		'Validated sniffs provided in options',
		array(
			'phpcs-path'				=> $options['phpcs-path'],
			'phpcs-standard'			=> $options['phpcs-standard'],
			'phpcs-sniffs-include-after'		=> $options['phpcs-sniffs-include'],
			'phpcs-sniffs-include-invalid'		=> $phpcs_sniffs_include_invalid,
			'phpcs-sniffs-exclude-after'		=> $options['phpcs-sniffs-exclude'],
			'phpcs-sniffs-exclude-invalid'		=> $phpcs_sniffs_exclude_invalid,
			'phpcs-sniffs-excluded-and-included'	=> $phpcs_sniffs_excluded_and_included,
		)
	);
}

/*
 * Possibly switch to a new PHPCS standard on the fly.
 * This depends on if new PHPCS sniffs are to be included.
 */
function vipgoci_phpcs_possibly_use_new_standard_file(
	&$options
) {
	/*
	 * Switch to new standard: Write new standard
	 * to a temporary file, then switch to using that.
	 */

	if ( ! empty( $options['phpcs-sniffs-include'] ) ) {
		$new_standard_file = vipgoci_save_temp_file(
			'vipgoci-phpcs-standard-',
			'xml',
			''
		);

		vipgoci_phpcs_write_xml_standard_file(
			$new_standard_file,
			$options['phpcs-standard'],
			$options['phpcs-sniffs-include']
		);

		$old_phpcs_standard = $options['phpcs-standard'];
		$options['phpcs-standard'] = array( $new_standard_file );
		$options['phpcs-standard-file'] = true;

		vipgoci_log(
			'As PHPCS sniffs are being included that are outside of the PHPCS standard specified, we switched to a new PHPCS standard',
			array(
				'old-phpcs-standard'	=> $old_phpcs_standard,
				'phpcs-standard'	=> $new_standard_file,
			)
		);
	}
}


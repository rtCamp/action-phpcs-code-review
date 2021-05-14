<?php

/*
 * Use SVG scanner to scan for any issues
 * in the file specified, and return the
 * results.
 */
function vipgoci_svg_do_scan_with_scanner(
	$svg_scanner_path,
	$temp_file_name
) {
	/*
	 * Run SVG scanner from the shell, making sure we escape everything.
	 */
	$cmd = sprintf(
		'%s %s %s',
		escapeshellcmd( 'php' ),
		escapeshellcmd( $svg_scanner_path ),
		escapeshellarg( $temp_file_name )
	);

	$cmd .= ' 2>&1';

	vipgoci_log(
		'Running SVG scanner now',
		array(
			'cmd' => $cmd,
		),
		2
	);


	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'svg_scanner_cli' );

	$result = shell_exec( $cmd );

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'svg_scanner_cli' );

	return $result;
}

function vipgoci_svg_look_for_specific_tokens(
	$disallowed_tokens,
	$temp_file_name,
	&$results
) {

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'svg_scanner_specific' );

	$file_contents = file_get_contents(
		$temp_file_name
	);

	if ( false === $file_contents ) {
		vipgoci_log(
			'Unable to open file for SVG specific tag scanning',
			array(
				'temp_file_name'	=> $temp_file_name,
				'disallowed_tokens'	=> $disallowed_tokens,
			)
		);

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'svg_scanner_specific' );

		return;
	}

	/*
	 * Explode each line into
	 * each item in an array.
	 */
	$file_lines_arr = explode(
		PHP_EOL,
		$file_contents
	);

	$line_no = 1; // Line numbers begin at 1

	/*
	 * Loop through each line of the
	 * file, look for disallowed tokens,
	 * record any found and keep statistics.
	 */
	foreach ( $file_lines_arr as $file_line_item ) {

		/*
		 * Scan for each disallowed token
		 */
		foreach( $disallowed_tokens as $disallowed_token ) {
			/*
			 * Do a case insensitive search
			 */
			$token_pos = stripos(
				$file_line_item,
				$disallowed_token
			);

			if ( false === $token_pos ) {
				continue;
			}

			/*
			 * Found a problem, adding to results.
			 */
			$results['totals']['errors']++;

			if ( ! isset(
				$results['files'][ $temp_file_name ]
			) ) {
				$results['files'][ $temp_file_name ] = array(
					'errors' => 0,
					'messages' => array()
				);
			}

			$results['files'][ $temp_file_name ]['errors']++;

			$results['files'][ $temp_file_name ]['messages'][] =
				array(
					'message'	=>
						'Found forbidden tag in SVG ' .
							'file: \'' .
							$disallowed_token .
							'\'',

					'line'		=> $line_no,
					'level'		=> 'ERROR',
				);
		}

		$line_no++;
	}


	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'svg_scanner_specific' );

	return $results;
}


/*
 * Scan a SVG-file for disallowed
 * tokens. Will return results in the
 * same format as PHPCS does.
 *
 * Note that this function is designed as
 * a substitute for PHPCS in case of
 * scanning SVG files.
 */
function vipgoci_svg_scan_single_file(
	$options,
	$file_name
) {
	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'svg_scan_single_file' );

	vipgoci_log(
		'Scanning single SVG file',
		array(
			'repo_owner'	=> $options['repo-owner'],
			'repo_name'	=> $options['repo-name'],
			'commit_id'	=> $options['commit'],
			'svg_checks'	=> $options['svg-checks'],
			'file_name'	=> $file_name,
		)
	);

	/*
	 * These tokens are not allowed
	 * in SVG files. Note that we do
	 * a case insensitive search for these.
	 */

	$disallowed_tokens = array(
		'<?php',
		'<?=',
	);

	/*
	 * Read in file contents from Git repo.
	 */

	$file_contents = vipgoci_gitrepo_fetch_committed_file(
		$options['repo-owner'],
		$options['repo-name'],
		$options['token'],
		$options['commit'],
		$file_name,
		$options['local-git-repo']
	);

	/*
	 * Determine file-ending of the file,
	 * then save it into temporary file
	 * before scanning.
	 */

	$file_extension = vipgoci_file_extension_get(
		$file_name
	);

	/*
	 * Could not determine? Return null.
	 * We only process SVG files.
	 */
	if ( 'svg' !== $file_extension ) {

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'svg_scan_single_file' );

		vipgoci_log(
			'Could not scan file, does not seem to be a SVG file',
			array(
				'repo_owner'	=> $options['repo-owner'],
				'repo_name'	=> $options['repo-name'],
				'commit_id'	=> $options['commit'],
				'svg_checks'	=> $options['svg-checks'],
				'file_name'	=> $file_name,
			)
		);


		return null;
	}

	$temp_file_name = vipgoci_save_temp_file(
		'svg-scan-',
		$file_extension,
		$file_contents
	);


	/*
	 * Use the svg-sanitizer's library scanner
	 * to scan the file.
	 */

	$results = vipgoci_svg_do_scan_with_scanner(
		$options['svg-scanner-path'],
		$temp_file_name
	);


	$results = json_decode(
		$results,
		true
	);

	if ( null === $results ) {
		vipgoci_log(
			'SVG scanning of a single file failed',
			array(
				'results'		=> $results,
				'file_name'		=> $file_name,
				'temp_file_name'	=> $temp_file_name,
			)
		);

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'svg_scan_single_file' );

		return array(
			'file_issues_arr_master'	=> $results,
			'file_issues_str'		=> null,
			'temp_file_name'		=> $temp_file_name,
		);
	}

	/*
	 * Use custom scanning to look for
	 * forbidden tags.
	 */

	vipgoci_svg_look_for_specific_tokens(
		$disallowed_tokens,
		$temp_file_name,
		$results
	);

	/*
	 * Add in missing information to
	 * the results -- this will emulate
	 * PHPCS results as possible.
	 */
	$results['files'][ $temp_file_name ]['messages'] = array_map(
		function( $issue_item ) {
			$issue_item['severity'] = 5;
			$issue_item['type'] = 'ERROR';
			$issue_item['source'] = 'WordPressVIPMinimum.Security.SVG.DisallowedTags';
			$issue_item['level'] = $issue_item['type'];
			$issue_item['fixable'] = false;
			$issue_item['column'] = 0;

			/*
			 * FIXME: In some cases $issue_item['line'] (line number),
			 * can be null indicating a problem with scanning the file
			 * generally. This should be reported to the end-user.
			 */

			return $issue_item;
		},
		$results['files'][ $temp_file_name ]['messages']
	);


	unlink( $temp_file_name );

	/*
	 * Emulate results returned
	 * by vipgoci_phpcs_scan_single_file().
	 */

	foreach( array( 'errors', 'warnings', 'fixable' ) as $stats_key ) {
		if ( ! isset( $results['totals']['errors'] ) ) {
			$results['totals'][ $stats_key ] = 0;
		}
	}

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'svg_scan_single_file' );

	vipgoci_log(
		'SVG scanning of a single file finished',
		array(
			'file_issues_arr_master' => $results,
		)
	);

	return array(
		'file_issues_arr_master'	=> $results,
		'file_issues_str'		=> json_encode( $results ),
		'temp_file_name'		=> $temp_file_name,
	);
}


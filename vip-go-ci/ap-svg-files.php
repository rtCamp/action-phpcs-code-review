<?php

/*
 * Process any SVG files that may be part of the PRs.
 *
 * Should there be any SVG files in the PRs, these
 * files can be auto-approved as long as no PHPCS
 * issues are found in them. The logic is that if there
 * are any such issues, these have to be looked into
 * manually, but if not, theses kind of files should be
 * safe to be deployed.
 */

function vipgoci_ap_svg_files(
		$options,
		&$auto_approved_files_arr
	) {

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'ap_svg_files' );

	vipgoci_log(
		'Doing auto-approval scanning for SVG files',
		array(
			'repo_owner'	=> $options['repo-owner'],
			'repo_name'	=> $options['repo-name'],
			'commit_id'	=> $options['commit'],
		)
	);


	$prs_implicated = vipgoci_github_prs_implicated(
		$options['repo-owner'],
		$options['repo-name'],
		$options['commit'],
		$options['token'],
		$options['branches-ignore'],
		$options['skip-draft-prs']
	);


	foreach ( $prs_implicated as $pr_item ) {
		$pr_diff = vipgoci_git_diffs_fetch(
			$options['local-git-repo'],
			$options['repo-owner'],
			$options['repo-name'],
			$options['token'],
			$pr_item->base->sha,
			$options['commit'],
			true, // include renamed files
			true, // include removed files
			true // include permission changes
		);


		foreach ( $pr_diff['files'] as
			$pr_diff_file_name => $pr_diff_contents
		) {
			$pr_diff_file_extension = vipgoci_file_extension_get(
				$pr_diff_file_name
			);

			/*
			 * If not a SVG file, do not do anything.
			 */

			if (
				'svg' !==
				$pr_diff_file_extension
			) {
				continue;
			}

			/*
			 * If the file is already in the array
			 * of approved files, do not do anything.
			 */
			if ( isset(
				$auto_approved_files_arr[
					$pr_diff_file_name
				]
			) ) {
				continue;
			}

			/*
			 * No patch found for file, so likely
			 * there were only changes in file-name,
			 * permissions, removal or other -- we
			 * can auto-approve SVG files in such cases.
			 */
			if ( null === $pr_diff_contents ) {
				vipgoci_log(
					'Adding SVG file to list of approved ' .
						'files, as no material changes ' .
						'were being done, only renaming, ' .
						'permission changes, or removal. ',
					array(
						'file_name' =>
							$pr_diff_file_name,
					)
				);

				$auto_approved_files_arr[
					$pr_diff_file_name
				] = 'ap-svg-files';
			}

			/*
			 * Scan the SVG file, get the results.
			 */

			$tmp_scan_results = vipgoci_svg_scan_single_file(
				$options,
				$pr_diff_file_name
			);

			$file_issues_arr_master =
				$tmp_scan_results['file_issues_arr_master'];

			/*
			 * Check for failure
			 */
			if (
				( ! isset(
					$file_issues_arr_master['totals']
				) )
				||
				( ! isset(
					$file_issues_arr_master['totals']['errors']
				) )
				||
				( ! isset(
					$file_issues_arr_master['totals']['warnings']
				) )
			) {
				vipgoci_log(
					'Not adding SVG file to list of ' .
						'approved files as a failure occurred',

					array(
						'file_name' =>
							$pr_diff_file_name,
						'file_issues_arr_master' =>
							$file_issues_arr_master,
					),
					0,
					true // log to IRC
				);
			}


			/*
			 * If no issues were found, we
			 * can approve this file.
			 */
			else if (
				( 0 ===
					$file_issues_arr_master['totals']['errors']
				)
				&&
				( 0 ===
					$file_issues_arr_master['totals']['warnings']
				)
			) {
				vipgoci_log(
					'Adding SVG file to list of approved ' .
						'files, as no PHPCS-issues ' .
						'were found',
					array(
						'file_name' =>
							$pr_diff_file_name,
					)
				);

				$auto_approved_files_arr[
					$pr_diff_file_name
				] = 'ap-svg-files';
			}

			else {
				vipgoci_log(
					'Not adding SVG file to list of ' .
						'approved files as issues ' .
						'were found',
					array(
						'file_name' =>
							$pr_diff_file_name,
						'file_issues_arr_master' =>
							$file_issues_arr_master,
					)
				);
			}
		}
	}

	/*
	 * Reduce memory-usage as possible
	 */
	unset( $tmp_scan_results );
	unset( $prs_implicated );
	unset( $pr_diff );
	unset( $pr_item );
	unset( $pr_diff_file_extension );
	unset( $pr_diff_file_name );
	unset( $file_issues_arr_master );

	gc_collect_cycles();

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'ap_svg_files' );
}


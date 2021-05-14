<?php

/*
 * Process all files in the PRs
 * involved with the commit specified.
 *
 * This function will add to an array
 * of auto-approvable files any PHP files that
 * contain no material, functional changes -- only
 * changes in whitespacing, comments, etc.
 */

function vipgoci_ap_nonfunctional_changes(
		$options,
		&$auto_approved_files_arr
	) {

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'ap_nonfunctional_changes' );

	vipgoci_log(
		'Doing auto-approval of PHP files with non-functional changes',
		array(
			'repo_owner'	=> $options['repo-owner'],
			'repo_name'	=> $options['repo-name'],
			'commit_id'	=> $options['commit'],
			'autoapprove'	=> $options['autoapprove'],
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
			true, // renamed files included
			true, // removed files included
			true, // permission changes included
			null
		);

		/*
		 * Note: We will here loop through files
		 * that have been renamed, removed, had their
		 * permission changed, or had their contents
		 * modified.
		 */

		foreach ( $pr_diff['files'] as
			$pr_diff_file_name => $pr_diff_contents
		) {
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

			$pr_diff_file_extension = vipgoci_file_extension_get(
				$pr_diff_file_name
			);

			/*
			 * Check if the extension of the file
			 * is "php".
			 */
			if ( in_array(
				$pr_diff_file_extension,
				array( 'php' ),
				true
			) === false ) {
				continue;
			}

			/*
			 * Save contents of version of
			 * file at the base of the Pull-Request
			 * to a temporary file ("old version").
			 */

			$pr_diff_file_old_contents = vipgoci_gitrepo_get_file_at_commit(
				$pr_item->base->sha,
				$pr_diff_file_name,
				$options['local-git-repo'],
				$options['commit']
			);

			if ( null === $pr_diff_file_old_contents ) {
				/*
				 * If we could not find the file
				 * in this commit, skip and continue.
				 * We need an older version to make
				 * comparisons.
				 */

				vipgoci_log(
					'Skipping PHP file ("old version"), as it could not be fetched from git-repository',
					array(
						'pr_base_sha'		=> $pr_item->base->sha,
						'pr_diff_file_name'	=> $pr_diff_file_name,
						'local_git_repo'	=> $options['local-git-repo'],
					)
				);

				continue;
			}

			$tmp_file_old = vipgoci_save_temp_file(
				$pr_diff_file_name,
				null,
				$pr_diff_file_old_contents
			);

			unset( $pr_diff_file_old_contents );

			/*
			 * Save contents of version of
			 * file at the head of the Pull-Request
			 * to a temporary file ("new version").
			 */

			$pr_diff_file_new_contents = vipgoci_gitrepo_fetch_committed_file(
				$options['repo-name'],
				$options['repo-owner'],
				$options['token'],
				$options['commit'],
				$pr_diff_file_name,
				$options['local-git-repo']
			);

			if ( null === $pr_diff_file_new_contents ) {
				/*
				 * If we could not find the file
				 * in this commit, skip and continue.
				 */

				vipgoci_log(
					'Skipping PHP file ("new version"), as it could not be fetched from git-repository',
					array(
						'commit'		=> $options['commit'],
						'pr_diff_file_name'	=> $pr_diff_file_name,
						'local_git_repo'	=> $options['local-git-repo'],
					)
				);

				continue;
			}


			$tmp_file_new = vipgoci_save_temp_file(
				$pr_diff_file_name,
				null,
				$pr_diff_file_new_contents
			);

			unset( $pr_diff_file_new_contents );

			/*
			 * Check if the version at the base of
			 * of the Pull-Request ("old version")
			 * is the same as the latest version at
			 * the head of the Pull-Request ("new version")
			 * are exactly the same, given that we remove
			 * all whitespacing changes.
			 */
			if (
				sha1( php_strip_whitespace(
					$tmp_file_old
				) )
				===
				sha1( php_strip_whitespace(
					$tmp_file_new
				) )
			) {
				$log_msg = 'File is indeed functionally the same, autoapproving';

				$auto_approved_files_arr[
					$pr_diff_file_name
				] = 'autoapprove-nonfunctional-changes';
			}

			else {
				$log_msg = 'File is not functionally the same, not autoapproving';
			}

			vipgoci_log(
				$log_msg,
				array(
					'repo_owner'	=> $options['repo-owner'],
					'repo_name'	=> $options['repo-name'],
					'autoapprove'	=> $options['autoapprove'],
					'commit_id'	=> $options['commit'],
					'file_name'	=> $pr_diff_file_name,
				)
			);

			/*
			 * Remove temporary files.
			 */
			unlink(
				$tmp_file_old
			);

			unlink(
				$tmp_file_new
			);
		}
	}

	/*
	 * Reduce memory-usage as possible
	 */
	unset( $prs_implicated );
	unset( $pr_diff );
	unset( $pr_item );
	unset( $pr_diff_file_extension );
	unset( $pr_diff_file_name );
	unset( $tmp_file_old );
	unset( $tmp_file_new );

	gc_collect_cycles();

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'ap_nonfunctional_changes' );
}


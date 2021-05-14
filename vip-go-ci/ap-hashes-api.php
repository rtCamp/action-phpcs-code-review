<?php

/*
 * Ask the hashes-to-hashes database API if the
 * specified file is approved.
 */

function vipgoci_ap_hashes_api_file_approved(
	$options,
	$file_path
) {
	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'hashes_api_scan_file' );

	/*
	 * Make sure to process only *.php and
	 * *.js files -- others are ignored.
	 *
	 * Cross-reference: These file types are not
	 * auto-approved by the auto-approval mechanism --
	 * see vip-go-ci.php.
	 */

	$file_extensions_approvable = array(
		'php',
		'js',
	);


	$file_info_extension = vipgoci_file_extension_get(
		$file_path
	);


	if ( in_array(
		$file_info_extension,
		$file_extensions_approvable
	) === false ) {
		vipgoci_log(
			'Not checking file for approval in hashes-to-hashes ' .
				'API, as it is not a file-type that is ' .
				'to be checked using it',

			array(
				'file_path'
					=> $file_path,

				'file_extension'
					=> $file_info_extension,

				'file_extensions_approvable'
					=> $file_extensions_approvable,
			)
		);

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'hashes_api_scan_file' );

		return null;
	}


	vipgoci_log(
		'Checking if file is already approved in ' .
			'hashes-to-hashes API',
		array(
			'repo_owner'	=> $options['repo-owner'],
			'repo_name'	=> $options['repo-name'],
			'commit'	=> $options['commit'],
			'file_path'	=> $file_path,
		)
	);

	/*
	 * Try to read file from disk, then
	 * get rid of whitespaces in the file
	 * and calculate SHA1 hash from the whole.
	 */

	$file_contents = vipgoci_gitrepo_fetch_committed_file(
		$options['repo-owner'],
		$options['repo-name'],
		$options['token'],
		$options['commit'],
		$file_path,
		$options['local-git-repo']
	);

	if ( false === $file_contents ) {
		vipgoci_log(
			'Unable to read file',
			array(
				'file_path' => $file_path,
			)
		);

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'hashes_api_scan_file' );

		return null;
	}

	vipgoci_log(
		'Saving file from git-repository into temporary file ' .
			'in order to strip any whitespacing from it',
		array(
			'file_path' => $file_path,
		),
		2
	);


	$file_temp_path = vipgoci_save_temp_file(
		$file_path,
		null,
		$file_contents
	);

	$file_contents_stripped = php_strip_whitespace(
		$file_temp_path
	);


	$file_sha1 = sha1( $file_contents_stripped );

	unlink( $file_temp_path );
	unset( $file_contents );
	unset( $file_contents_stripped );


	/*
	 * Ask the API for information about
	 * the specific hash we calculated.
	 */

	vipgoci_log(
		'Asking hashes-to-hashes HTTP API if hash of file is ' .
			'known',
		array(
			'file_path'	=> $file_path,
			'file_sha1'	=> $file_sha1,
		)
	);

	$hashes_to_hashes_url =
		$options['hashes-api-url'] .
		'/v1/hashes/id/' .
		rawurlencode( $file_sha1 );

	/*
	 * Not really asking GitHub here,
	 * but we can re-use the function
	 * for this purpose.
	 */

	$file_hashes_info =
		vipgoci_github_fetch_url(
			$hashes_to_hashes_url,
			array(
				'oauth_consumer_key' =>
					$options['hashes-oauth-consumer-key'],

				'oauth_consumer_secret' =>
					$options['hashes-oauth-consumer-secret'],

				'oauth_token' =>
					$options['hashes-oauth-token'],

				'oauth_token_secret' =>
					$options['hashes-oauth-token-secret'],
			)
		);


	/*
	 * Try to parse, and check for errors.
	 */

	if ( false !== $file_hashes_info ) {
		$file_hashes_info = json_decode(
			$file_hashes_info,
			true
		);
	}


	if (
		( false === $file_hashes_info ) ||
		( null === $file_hashes_info ) ||
		( isset( $file_hashes_info['data']['status'] ) )
	) {
		vipgoci_log(
			'Unable to get information from ' .
				'hashes-to-hashes HTTP API',
			array(
				'hashes_to_hashes_url'	=> $hashes_to_hashes_url,
				'file_path'		=> $file_path,
				'http_reply'		=> $file_hashes_info,
			),
			0,
			true // log to IRC
		);

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'hashes_api_scan_file' );

		return null;
	}

	$file_approved = null;

	/*
	 * Only approve file if all info-items show
	 * the file to be approved.
	 */

	foreach( $file_hashes_info as $file_hash_info ) {
		if ( ! isset( $file_hash_info[ 'status' ] ) ) {
			$file_approved = false;
		}

		if (
			( 'false' === $file_hash_info[ 'status' ] ) ||
			( false === $file_hash_info[ 'status' ] )
		) {
			$file_approved = false;
		}

		else if (
			( 'true' === $file_hash_info[ 'status' ] ) ||
			( true === $file_hash_info[ 'status' ] )
		) {
			/*
			 * Only update approval-flag if we have not
			 * seen any other approvals, and if we have
			 * not seen any rejections.
			 */
			if ( null === $file_approved ) {
				$file_approved = true;
			}
		}
	}


	/*
	 * If no approval is seen, assume it is not
	 * approved at all.
	 */

	if ( null === $file_approved ) {
		$file_approved = false;
	}

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'hashes_api_scan_file' );

	return $file_approved;
}


/*
 * Scan a particular commit, look for altered
 * files in the Pull-Request we are associated with
 * and for each of these files, check if they
 * are approved in the hashes-to-hashes API.
 */
function vipgoci_ap_hashes_api_scan_commit(
	$options,
	&$commit_issues_submit,
	&$commit_issues_stats,
	&$auto_approved_files_arr
) {
	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'hashes_api_scan' );

	vipgoci_log(
		'Scanning altered or new files affected by Pull-Request(s) ' .
			'using hashes-to-hashes API',
		array(
			'repo_owner'		=> $options['repo-owner'],
			'repo_name'		=> $options['repo-name'],
			'commit_id'		=> $options['commit'],
			'hashes-api'		=> $options['hashes-api'],
			'hashes-api-url'	=> $options['hashes-api-url'],
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
		/*
		 * Do not auto-approve renamed,
		 * removed or permission-changed files,
		 * even if they might be auto-approved: the
		 * reason is that renaming might be harmful to
		 * stability, there could be removal of vital
		 * files, and permission changes might be dangerous.
		 */
		$pr_diff = vipgoci_git_diffs_fetch(
			$options['local-git-repo'],
			$options['repo-owner'],
			$options['repo-name'],
			$options['token'],
			$pr_item->base->sha,
			$options['commit'],
			false, // exclude renamed files
			false, // exclude removed files
			false // exclude permission changes
		);


		foreach( $pr_diff['files'] as
			$pr_diff_file_name => $pr_diff_contents
		) {
			/*
			 * If it is already approved,
			 * do not do anything.
			 */

			if ( isset(
				$auto_approved_files_arr[
					$pr_diff_file_name
				]
			) ) {
				continue;
			}

			/*
			 * Check if the hashes-to-hashes database
			 * recognises this file, and check its
			 * status.
			 */

			$approval_status = vipgoci_ap_hashes_api_file_approved(
				$options,
				$pr_diff_file_name
			);


			/*
			 * Add the file to a list of approved files
			 * of these affected by the Pull-Request.
			 */
			if ( true === $approval_status ) {
				vipgoci_log(
					'File is approved in ' .
						'hashes-to-hashes API',
					array(
						'file_name' => $pr_diff_file_name,
					)
				);

				$auto_approved_files_arr[
					$pr_diff_file_name
				] = 'autoapprove-hashes-to-hashes';
			}

			else if ( false === $approval_status ) {
				vipgoci_log(
					'File is not approved in ' .
						'hashes-to-hashes API',
					array(
						'file_name' => $pr_diff_file_name,
					)
				);
			}

			else if ( null === $approval_status ) {
				vipgoci_log(
					'Could not determine if file is approved ' .
						'in hashes-to-hashes API',
					array(
						'file_name' => $pr_diff_file_name,
					)
				);
			}
		}
	}


	/*
	 * Reduce memory-usage as possible
	 */

	unset( $prs_implicated );
	unset( $pr_item );
	unset( $pr_diff );
	unset( $pr_diff_contents );
	unset( $approval_status );

	gc_collect_cycles();

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'hashes_api_scan' );
}


<?php

/*
 * Fetch meta-data for repository from
 * repo-meta API, cache the results in memory.
 */
function vipgoci_repo_meta_api_data_fetch(
	$repo_meta_api_base_url,
	$repo_meta_api_user_id,
	$repo_meta_api_access_token,
	$repo_owner,
	$repo_name
) {
	$cached_id = array(
		__FUNCTION__, $repo_meta_api_base_url, $repo_meta_api_user_id,
		$repo_meta_api_access_token, $repo_owner, $repo_name
	);

	$cached_data = vipgoci_cache( $cached_id );

	vipgoci_log(
		'Fetching repository meta-data from repo-meta API' .
			vipgoci_cached_indication_str( $cached_data ),

		array(
			'repo_meta_api_base_url'	=> $repo_meta_api_base_url,
			'repo_meta_api_user_id'		=> $repo_meta_api_user_id,
			'repo_owner'			=> $repo_owner,
			'repo_name'			=> $repo_name,
		)
	);

	if ( false !== $cached_data ) {
		return $cached_data;
	}

	$curl_retries = 0;

	do {
		$resp_data = false;
		$resp_data_parsed = null;

		$endpoint_url =
			$repo_meta_api_base_url .
			'/v1' .
			'/sites?' .
			'active=1&' .
			'page=1&' .
			'pagesize=20&' .
			'source_repo=' . rawurlencode( $repo_owner . '/' . $repo_name );

		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_URL,			$endpoint_url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER,	1 );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT,	20 );

		curl_setopt(
			$ch,
			CURLOPT_USERAGENT,
			VIPGOCI_CLIENT_ID
		);

		$endpoint_send_headers = array(
		);

		if ( ! empty( $repo_meta_api_user_id ) ) {
			$endpoint_send_headers[] =
				'API-User-ID: ' . $repo_meta_api_user_id;
		}

		if ( ! empty( $repo_meta_api_access_token ) ) {
			$endpoint_send_headers[] =
				'Access-Token: ' . $repo_meta_api_access_token;
		}

		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			$endpoint_send_headers
		);

		curl_setopt(
			$ch,
			CURLOPT_HEADERFUNCTION,
			'vipgoci_curl_headers'
		);

		vipgoci_curl_set_security_options(
			$ch
		);

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'repo_meta_data_endpoint_api_request' );

		vipgoci_counter_report(
			VIPGOCI_COUNTERS_DO,
			'repo_meta_data_endpoint_api'
		);

		$resp_data = curl_exec( $ch );

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'repo_meta_data_endpoint_api_request' );

		if ( false !== $resp_data ) {
			$resp_data_parsed = json_decode(
				$resp_data,
				true
			);
		}

		$resp_headers = vipgoci_curl_headers(
			null,
			null
		);

		if (
			( false === $resp_data ) ||
			( null === $resp_data_parsed ) ||
			(
				( isset($resp_data_parsed['status'] ) ) &&
				( 'error' === $resp_data_parsed['status'] )
			)
		) {
			vipgoci_log(
				'Failed fetching or parsing data...',
				array(
					'resp_data'		=> $resp_data,
					'resp_data_parsed'	=> $resp_data_parsed,
					'curl_error'		=> curl_error( $ch ),
					'http_status'		=> (
						isset($resp_headers['status']) ?
						$resp_headers['status'] :
						null
					)
				)
			);

			/*
			 * For the while() below
			 */
			if ( ! isset( $resp_data_parsed['status'] ) ) {
				$resp_data = false;
			}
		}

		curl_close( $ch );

	} while (
		( false === $resp_data ) &&
		( $curl_retries++ < 2 )
	);

	vipgoci_cache(
		$cached_id,
		$resp_data_parsed
	);

	return $resp_data_parsed;
}

/*
 * Fetch data from repo-meta API, then try
 * to match fields and their values with
 * the data. The fields and values are those
 * found in a particular $option parameter
 * specified as an argument here ($option_name).
 *
 * If there is a match, return true. Otherwise,
 * return false.
 */
function vipgoci_repo_meta_api_data_match(
	$options,
	$option_name,
	&$option_no_match
) {
	if (
		( empty( $option_name ) ) ||
		( empty( $options['repo-meta-api-base-url'] ) ) ||
		( empty( $options[ $option_name ] ) )
	) {
		vipgoci_log(
			'Not attempting to match repo-meta API field-value ' .
				'to a criteria due to invalid configuration',
			array(
				'option_name'
					=> $option_name,

				'repo_meta_api_base_url'
					=> isset( $options['repo-meta-api-base-url'] ) ?
						$options['repo-meta-api-base-url'] : '',

				'repo_meta_match'
					=> ( ( ! empty( $option_name ) ) && ( isset( $options[ $option_name ] ) ) ) ?
						$options[ $option_name ] : '',
			)
		);

		return false;
	}

	else {
		vipgoci_log(
			'Attempting to match repo-meta API field-value to a criteria',
			array(
				'option_name'			=> $option_name,
				'repo_meta_match'		=> $options[ $option_name ],
				'repo_meta_api_base_url'	=> $options['repo-meta-api-base-url'],
			)
		);
	}


	$repo_meta_data = vipgoci_repo_meta_api_data_fetch(
		$options['repo-meta-api-base-url'],
		$options['repo-meta-api-user-id'],
		$options['repo-meta-api-access-token'],
		$options['repo-owner'],
		$options['repo-name']
	);

	if (
		( empty(
			$repo_meta_data['data']
		) )
		||
		( 'error' === $repo_meta_data['status'] )
	) {
		return false;
	}

	/*
	 * Loop through possible match in the
	 * option array -- bail out once we
	 * find a match.
	 */
	foreach(
		array_keys( $options[ $option_name ] ) as
			$option_name_key_no
	) {
		$found_fields = vipgoci_find_fields_in_array(
			$options[ $option_name ][ $option_name_key_no ],
			$repo_meta_data['data']
		);

		/*
		 * If we find one data-item that had
		 * all fields matching the criteria given,
		 * we return true.
		 */
		$ret_val = false;

		foreach(
			$found_fields as
				$found_field_item_key => $found_field_item_value
		) {
			if ( $found_field_item_value === true ) {
				$ret_val = true;
			}
		}

		if ( true === $ret_val ) {
			$option_no_match = $option_name_key_no;
			break;
		}
	}

	vipgoci_log(
		'Repo-meta API matching returning',
		array(
			'found_fields_in_repo_meta_data'	=> $found_fields,
			'repo_meta_data_item_cnt'		=> count( $repo_meta_data['data'] ),
			'ret_val'				=> $ret_val,
			'option_no_match'			=> $option_no_match,
		)
	);

	return $ret_val;
}

/*
 * Attach support level label to
 * Pull-Requests, if configured to
 * do so. Will fetch information
 * about support-level from an API.
 */
function vipgoci_support_level_label_set(
	$options
) {

	if ( true !== $options['set-support-level-label'] ) {
		vipgoci_log(
			'Not attaching support label to Pull-Requests ' .
				'implicated by commit, as not configured ' .
				'to do so',
			array(
				'set_support_level_label'
					=> $options['set-support-level-label']
			)
		);

		return false;
	}

	vipgoci_log(
		'Attaching support-level label to Pull-Requests implicated by commit',
		array(
			'repo_owner'			=> $options['repo-owner'],
			'repo_name'			=> $options['repo-name'],
			'commit'			=> $options['commit'],
			'repo_meta_api_base_url'	=>
				( ! empty( $options['repo-meta-api-base-url'] ) ?
				$options['repo-meta-api-base-url'] : '' ),
		),
		0,
		true
	);

	if (
		( empty( $options['repo-meta-api-base-url'] ) )
	) {
		vipgoci_log(
			'Missing URL for repo-meta API, skipping'
		);

		return false;
	}

	if (
		( empty( $options['set-support-level-field'] ) )
	) {
		vipgoci_log(
			'Missing field for support level in repo-meta API, skipping'
		);

		return false;
	}

	/*
	 * Get information from API about the
	 * repository, including support level.
	 */

	$repo_meta_data = vipgoci_repo_meta_api_data_fetch(
		$options['repo-meta-api-base-url'],
		$options['repo-meta-api-user-id'],
		$options['repo-meta-api-access-token'],
		$options['repo-owner'],
		$options['repo-name']
	);

	/*
	 * Construct support-level label
	 * from information found in API,
	 * if available.
	 */

	if ( ! empty( $options['set-support-level-label-prefix'] ) ) {
		$support_label_prefix = $options['set-support-level-label-prefix'];
	}

	else {
		$support_label_prefix = '[Support Level]';
	}

	$support_label_from_api = '';

	if (
		( ! empty(
			$repo_meta_data['data']
		) )
		&&
		( ! empty(
			$repo_meta_data['data'][0][
				$options['set-support-level-field']
			]
		) )
	) {
		/*
		 * Construct the label itself
		 * from prefix and support level
		 * found in API.
		 */
		$support_label_from_api =
			$support_label_prefix .
			' ' .
			ucfirst( strtolower(
				$repo_meta_data['data'][0][
					$options['set-support-level-field']
				]
			) );
	}

	$support_level_response = (
		isset( $repo_meta_data['data'][0][ $options['set-support-level-field'] ] ) ?
		$repo_meta_data['data'][0][ $options['set-support-level-field'] ] :
		''
	);

	/*
	 * No support label found in API, so
	 * do not do anything.
	 */
	if ( empty( $support_label_from_api ) ) {
		vipgoci_log(
			'Found no valid support level in repo-meta API, so not ' .
				'attaching any label (nor removing)',
			array(
				'repo_owner'			=> $options['repo-owner'],
				'repo_name'			=> $options['repo-name'],
				'repo_meta_api_base_url'	=> $options['repo-meta-api-base-url'],
				'support_level_response'	=> $support_level_response,
			)
		);

		return false;
	}

	else {
		vipgoci_log(
			'Found valid support level in API, making alterations as needed',
			array(
				'repo_owner'			=> $options['repo-owner'],
				'repo_name'			=> $options['repo-name'],
				'repo_meta_api_base_url'	=> $options['repo-meta-api-base-url'],
				'support_level_response'	=> $support_level_response,
				'support_label_from_api'	=> $support_label_from_api,
			)
		);
	}

	/*
	 * Get Pull-Requests associated with the
	 * commit and repository.
	 */
	$prs_implicated = vipgoci_github_prs_implicated(
		$options['repo-owner'],
		$options['repo-name'],
		$options['commit'],
		$options['token'],
		$options['branches-ignore'],
		$options['skip-draft-prs']
	);

	/*
	 * Loop through each Pull-Request,
	 * remove any invalid support levels
	 * and add a correct one.
	 *
	 * If everything is correct, will not
	 * make any alterations.
	 */
	foreach ( $prs_implicated as $pr_item ) {
		$pr_correct_support_label_found = false;

		/*
		 * Get labels for PR.
		 */
		$pr_item_labels =
			vipgoci_github_pr_labels_get(
				$options['repo-owner'],
				$options['repo-name'],
				$options['token'],
				$pr_item->number
			);


		/*
		 * If no found, substitute boolean for empty array.
		 */
		if ( false === $pr_item_labels ) {
			$pr_item_labels = array();
		}

		/*
		 * Loop through each label found for
		 * Pull-Request, figure out if is support
		 * label, remove if not the same as is supposed
		 * to be set.
		 */
		foreach(
			$pr_item_labels as $pr_item_label
		) {
			if ( strpos(
				$pr_item_label->name,
				$support_label_prefix . ' '
			) !== 0 ) {
				/*
				 * Not support level
				 * label, skip.
				 */
				continue;
			}

			if ( $pr_item_label->name === $support_label_from_api ) {
				$pr_correct_support_label_found = true;

				/*
				 * We found correct support label
				 * associated, note that for later
				 * use.
				 */
				continue;
			}

			/*
			 * All conditions met; is support level
			 * label, but incorrect one, so remove label.
			 */
			vipgoci_github_pr_label_remove(
				$options['repo-owner'],
				$options['repo-name'],
				$options['token'],
				$pr_item->number,
				$pr_item_label->name,
				false
			);
		}

		/*
		 * Add support label if found in API but
		 * not if correct one is associated on
		 * GitHub already.
		 */
		if ( $pr_correct_support_label_found === true ) {
			vipgoci_log(
				'Correct support label already attached to Pull-Request, skipping',
				array(
					'repo_owner'			=> $options['repo-owner'],
					'repo_name'			=> $options['repo-name'],
					'support_label_from_api'	=> $support_label_from_api,
				)
			);
		}

		/*
		 * A support label was found in API and
		 * a correct one was not associated on
		 * GitHub already, so add one.
		 */
		else if ( $pr_correct_support_label_found === false ) {
			vipgoci_github_label_add_to_pr(
				$options['repo-owner'],
				$options['repo-name'],
				$options['token'],
				$pr_item->number,
				$support_label_from_api
			);
		}
	}

	return true;
}



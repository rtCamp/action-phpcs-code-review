<?php

/*
 * This function works both to collect headers
 + when called as a callback function, and to return
 * the headers collected when called standalone.
 *
 * The difference is that the '$ch' argument is non-null
 * when called as a callback.
 */
function vipgoci_curl_headers( $ch, $header ) {
	static $resp_headers = array();

	if ( null === $ch ) {
		/*
		 * If $ch is null, we are being called to
		 * return whatever headers we have collected.
		 *
		 * Make sure to empty the headers collected.
		 */
		$ret = $resp_headers;
		$resp_headers = array();

		/*
		 * 'Fix' the status header before returning;
		 * we want the value to be an array such as:
		 * array(
		 *	0 => 201, // Status-code
		 *	1 => 'Created' // Status-string
		 * )
		 */
		if ( isset( $ret['status'] ) ) {
			$ret['status'] = explode(
				' ',
				$ret['status'][0]
			);
		}

		return $ret;
	}


	/*
	 * Get header length
	 */
	$header_len = strlen( $header );

	/*
	 * Construct 'status' HTTP header based on the
	 * HTTP status code. This used to be provided
	 * by GitHub, but is not anymore.
	 */

	if ( strpos( $header, 'HTTP/' ) === 0 ) {
		$header = explode(
			' ',
			$header
		);

		$header = 'Status: ' . $header[1] . "\n\r";
	}

	/*
	 * Turn the header into an array
	 */
	$header = explode( ':', $header, 2 );

	if ( count( $header ) < 2 ) {
		/*
		 * Should there be less than two values
		 * in the array, simply return, as the header is
		 * invalid.
		 */
		return $header_len;
	}


	/*
	 * Save the header as a key => value
	 * in our associative array.
	 */
	$key = strtolower( trim( $header[0] ) );

	if ( ! array_key_exists( $key, $resp_headers ) ) {
		$resp_headers[ $key ] = array();
	}

	$resp_headers[ $key ][] = trim(
		$header[1]
	);

	return $header_len;
}

/*
 * Set a few options for cURL that enhance security.
 *
 * @codeCoverageIgnore
 */
function vipgoci_curl_set_security_options( $ch ) {
	/*
	 * Maximum number of redirects to zero.
	 */
	curl_setopt(
		$ch,
		CURLOPT_MAXREDIRS,
		0
	);	

	/*
	 * Do not follow any "Location:" headers.
	 */
	curl_setopt(
		$ch,
		CURLOPT_FOLLOWLOCATION,
		false
	);
}

/**
 * Detect if we exceeded the GitHub rate-limits,
 * and if so, exit with error.
 *
 * @codeCoverageIgnore
 */

function vipgoci_github_rate_limits_check(
	$github_url,
	$resp_headers
) {
	if (
		( isset( $resp_headers['x-ratelimit-remaining'][0] ) ) &&
		( $resp_headers['x-ratelimit-remaining'][0] <= 1 )
	) {
		vipgoci_sysexit(
			'Ran out of request limits for GitHub, ' .
				'cannot continue without making ' .
				'making further requests.',
			array(
				'github_url' => $github_url,

				'x-ratelimit-remaining' =>
					$resp_headers['x-ratelimit-remaining'][0],

				'x-ratelimit-limit' =>
					$resp_headers['x-ratelimit-limit'][0],
			),
			VIPGOCI_EXIT_GITHUB_PROBLEM
		);
	}
}


/*
 * Ask GitHub for API rate-limit information and
 * report that back to the user.
 *
 * The results are not cached, as we want fresh data
 * every time.
 */

function vipgoci_github_rate_limit_usage(
	$github_token
) {
	$rate_limit = vipgoci_github_fetch_url(
		VIPGOCI_GITHUB_BASE_URL . '/rate_limit',
		$github_token
	);

	return json_decode(
		$rate_limit
	);
}

/*
 * Make sure to wait in between requests to
 * GitHub. Only waits if it is really needed.
 *
 * This function should only be called just before
 * sending a request to GitHub -- that is the most
 * effective usage.
 *
 * See here for background:
 * https://developer.github.com/v3/guides/best-practices-for-integrators/#dealing-with-abuse-rate-limits
 *
 * @codeCoverageIgnore
 */

function vipgoci_github_wait() {
	static $last_request_time = null;

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'github_forced_wait' );

	if ( null !== $last_request_time ) {
		/*
		 * Only sleep if less than one second
		 * has elapsed from last request.
		 */
		if ( ( time() - $last_request_time ) < 1 ) {
			sleep( 1 );
		}
	}

	$last_request_time = time();

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'github_forced_wait' );
}

/*
 * Calculate HMAC-SHA1 signature for OAuth 1.0 HTTP
 * request. Follows the standard on this but to a
 * limited extent only. For instance, this function
 * does not support having two parameters with the
 * same name.
 *
 * See here for background:
 * https://oauth.net/core/1.0a/#signing_process
 */
function vipgoci_oauth1_signature_get_hmac_sha1(
	$http_method,
	$request_url,
	$parameters_arr
) {
	/*
	 * Start constructing the 'base string' --
	 * a crucial part of the signature.
	 */
	$base_string = strtoupper( $http_method ) . '&';
	$base_string .= rawurlencode( $request_url ) . '&';


	/*
	 * New array for parameters, temporary
	 * so we can alter them freely.
	 */
	$parameters_arr_new = array();

	/*
	 * In case this parameter is present, it
	 * should not be part of the signature according
	 * to the standard.
	 */
	if ( isset( $parameters_arr['realm'] ) ) {
		unset( $parameters_arr['realm'] );
	}

	/*
	 * Add parameters to the new array, these
	 * need to be encoded in a certain way.
	 */
	foreach( $parameters_arr as $key => $value ) {
		$parameters_arr_new[ rawurlencode( $key ) ] =
			rawurlencode( $value );
	}

	/*
	 * Also these two should not be part of the
	 * signature.
	 */
	unset( $parameters_arr_new['oauth_token_secret'] );
	unset( $parameters_arr_new['oauth_consumer_secret'] );

	/*
	 * Sort the parameters alphabetically.
	 */
	ksort( $parameters_arr_new );


	/*
	 * Loop through the parameters, and add them
	 * to a temporary 'base string' according to the standard.
	 */

	$delimiter = '';
	$base_string_tmp = '';

	foreach( $parameters_arr_new as $key => $value ) {
		$base_string_tmp .=
			$delimiter .
			$key .
			'=' .
			$value;

		$delimiter = '&';
	}

	/*
	 * Then add the temporary 'base string' to the
	 * permanent 'base string'.
	 */
	$base_string .= rawurlencode(
		$base_string_tmp
	);

	/*
	 * Now calculate hash, using the
	 * 'base string' as input, and
	 * secrets as key.
	 */
	$hash_raw = hash_hmac(
		'sha1',
		$base_string,
		$parameters_arr['oauth_consumer_secret'] . '&' .
			$parameters_arr['oauth_token_secret'],
		true
	);

	/*
	 * Return it base64 encoded.
	 */
	return base64_encode( $hash_raw );
}


/*
 * Create and set HTTP header for OAuth 1.0a requests,
 * including timestamp, nonce, signature method
 * (all part of the header) and then actually sign
 * the request. Returns with a full HTTP header for
 * a OAuth 1.0a HTTP request.
 */
function vipgoci_oauth1_headers_get(
	$http_method,
	$github_url,
	$github_token
) {

	/*
	 * Set signature-method header, static.
	 */
	$github_token['oauth_signature_method'] =
		'HMAC-SHA1';

	/*
	 * Set timestamp and nonce.
	 */
	$github_token['oauth_timestamp'] = (string) ( time() - 1);

	$github_token['oauth_nonce'] = (string) md5(
		openssl_random_pseudo_bytes( 100 )
	);

	/*
	 * Get the signature for the header.
	 */
	$github_token['oauth_signature'] =
		vipgoci_oauth1_signature_get_hmac_sha1(
			$http_method,
			$github_url,
			$github_token
		);

	/*
	 * Those are not needed after this point,
	 * so we remove them to limit any risk
	 * of information leakage.
	 */
	unset( $github_token['oauth_token_secret' ] );
	unset( $github_token['oauth_consumer_secret' ] );

	/*
	 * Actually create the full HTTP header
	 */

	$res_header = 'OAuth ';
	$sep = '';

	foreach(
		$github_token as
			$github_token_key =>
			$github_token_value
	) {
		if ( strpos(
			$github_token_key,
			'oauth_'
		) !== 0 ) {
			/*
			 * If the token_key does not
			 * start with 'oauth_' we skip to
			 * avoid information-leakage.
			 */
			continue;
		}

		$res_header .=
			$sep .
			$github_token_key . '="' .
			rawurlencode( $github_token_value ) .
			'"';
		$sep = ', ';
	}

	/*
	 * Return the header.
	 */
	return $res_header;
}


/**
 * Send a POST/DELETE request to GitHub -- attempt
 * to retry if errors were encountered.
 *
 * Note that the '$http_delete' parameter will determine
 * if a POST or DELETE request will be sent.
 *
 * @codeCoverageIgnore
 */

function vipgoci_github_post_url(
	$github_url,
	$github_postfields,
	$github_token,
	$http_delete = false
) {
	/*
	 * Actually send a request to GitHub -- make sure
	 * to retry if something fails.
	 */
	do {
		/*
		 * By default, assume request went through okay.
		 */

		$ret_val = 0;

		/*
		 * By default, do not retry the request,
		 * just assume everything goes well
		 */

		$retry_req = false;

		/*
		 * Initialize and send request.
		 */

		$ch = curl_init();

		curl_setopt(
			$ch, CURLOPT_URL, $github_url
		);

		curl_setopt(
			$ch, CURLOPT_RETURNTRANSFER, 1
		);

		curl_setopt(
			$ch, CURLOPT_CONNECTTIMEOUT, 20
		);

		curl_setopt(
			$ch, CURLOPT_USERAGENT,	VIPGOCI_CLIENT_ID
		);

		if ( false === $http_delete ) {
			curl_setopt(
				$ch, CURLOPT_POST, 1
			);
		}

		else {
			curl_setopt(
				$ch, CURLOPT_CUSTOMREQUEST, 'DELETE'
			);
		}

		curl_setopt(
			$ch,
			CURLOPT_POSTFIELDS,
			json_encode( $github_postfields )
		);

		curl_setopt(
			$ch,
			CURLOPT_HEADERFUNCTION,
			'vipgoci_curl_headers'
		);

		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array( 'Authorization: token ' . $github_token )
		);

		vipgoci_curl_set_security_options(
			$ch
		);

		// Make sure to pause between GitHub-requests
		vipgoci_github_wait();

		/*
		 * Execute query to GitHub, keep
		 * record of how long time it took,
		 * and keep count of how many requests we do.
		 */

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'github_api_post' );

		vipgoci_counter_report(
			VIPGOCI_COUNTERS_DO,
			'github_api_request_post',
			1
		);

		$resp_data = curl_exec( $ch );

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'github_api_post' );


		$resp_headers = vipgoci_curl_headers(
			null,
			null
		);


		/*
		 * Allow certain statuses, depending on type of request
		 */
		if (
			(
				( false === $http_delete ) &&
				( intval( $resp_headers['status'][0] ) !== 200 ) &&
				( intval( $resp_headers['status'][0] ) !== 201 )
			)

			||

			(
				( true === $http_delete ) &&
				( intval( $resp_headers['status'][0] ) !== 204 ) &&
				( intval( $resp_headers['status'][0] ) !== 200 )
			)
		) {
			/*
			 * Set default wait period between requests
			 */
			$retry_sleep = 10;

			/*
			 * Set error-return value
			 */
			$ret_val = -1;

			/*
			 * Figure out if to retry...
			 */

			// Decode JSON
			$resp_data = json_decode( $resp_data );

			if (
				( isset(
					$resp_headers['retry-after']
				) ) &&
				( intval(
					$resp_headers['retry-after']
				) > 0 )
			) {
				$retry_req = true;
				$retry_sleep = intval(
					$resp_headers['retry-after']
				);
			}

			else if (
				( $resp_data->message ==
					'Validation Failed' ) &&

				( $resp_data->errors[0] ==
					'was submitted too quickly ' .
					'after a previous comment' )
			) {
				/*
				 * These messages are due to the
				 * submission being categorized
				 * as a spam by GitHub -- no good
				 * reason to retry, really.
				 */
				$retry_req = false;
				$retry_sleep = 20;
			}

			else if (
				( $resp_data->message ==
					'Validation Failed' )
			) {
				$retry_req = false;
			}

			else if (
				( $resp_data->message ==
					'Server Error' )
			) {
				$retry_req = false;
			}

			vipgoci_log(
				'GitHub reported an error' .
					( $retry_req === true ?
					' will retry request in ' .
					$retry_sleep . ' seconds' :
					'' ),
				array(
					'http_url'
						=> $github_url,

					'http_response_headers'
						=> $resp_headers,

					'http_reponse_body'
						=> $resp_data,
				)
			);

			sleep( $retry_sleep + 1 );
		}

		vipgoci_github_rate_limits_check(
			$github_url,
			$resp_headers
		);


		curl_close( $ch );

	} while ( $retry_req == true );

	return $ret_val;
}

/**
 * Make a GET request to GitHub, for the URL
 * provided, using the access-token specified.
 *
 * Will return the raw-data returned by GitHub,
 * or halt execution on repeated errors.
 */
function vipgoci_github_fetch_url(
	$github_url,
	$github_token
) {

	$curl_retries = 0;

	/*
	 * Attempt to send request -- retry if
	 * it fails.
	 */
	do {
		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_URL,			$github_url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER,	1 );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT,	20 );

		curl_setopt(
			$ch,
			CURLOPT_USERAGENT,
			VIPGOCI_CLIENT_ID
		);

		curl_setopt(
			$ch,
			CURLOPT_HEADERFUNCTION,
			'vipgoci_curl_headers'
		);

		if (
			( is_string( $github_token ) ) &&
			( strlen( $github_token ) > 0 )
		) {
			curl_setopt(
				$ch,
				CURLOPT_HTTPHEADER,
				array( 'Authorization: token ' . $github_token )
			);
		}

		else if ( is_array( $github_token ) ) {
			if (
				( isset( $github_token[ 'oauth_consumer_key' ] ) ) &&
				( isset( $github_token[ 'oauth_consumer_secret' ] ) ) &&
				( isset( $github_token[ 'oauth_token' ] ) ) &&
				( isset( $github_token[ 'oauth_token_secret' ] ) )
			) {
				$github_auth_header = vipgoci_oauth1_headers_get(
					'GET',
					$github_url,
					$github_token
				);

				curl_setopt(
					$ch,
					CURLOPT_HTTPHEADER,
					array(
						'Authorization: ' .
						$github_auth_header
					)
				);
			}
		}

		vipgoci_curl_set_security_options(
			$ch
		);


		// Make sure to pause between GitHub-requests
		vipgoci_github_wait();


		/*
		 * Execute query to GitHub, keep
		 * record of how long time it took,
		 + and also keep count of how many we do.
		 */
		vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'github_api_get' );

		vipgoci_counter_report(
			VIPGOCI_COUNTERS_DO,
			'github_api_request_get',
			1
		);

		$resp_data = curl_exec( $ch );

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'github_api_get' );


		$resp_headers = vipgoci_curl_headers(
			null,
			null
		);


		/*
		 * Detect and process possible errors
		 */
		if (
			( false === $resp_data ) ||
			( curl_errno( $ch ) )
		) {
			vipgoci_log(
				'Sending request to GitHub failed, will ' .
					'retry in a bit... ',

				array(
					'github_url' => $github_url,
					'curl_retries' => $curl_retries,

					'curl_errno' => curl_errno(
						$ch
					),

					'curl_errormsg' => curl_strerror(
						curl_errno( $ch )
					),
				)
			);

			sleep( 10 );
		}


		vipgoci_github_rate_limits_check(
			$github_url,
			$resp_headers
		);

		curl_close( $ch );

	} while (
		( false === $resp_data ) &&
		( $curl_retries++ < 2 )
	);


	if ( false === $resp_data ) {
		vipgoci_sysexit(
			'Gave up retrying request to GitHub, cannot continue',
			array(),
			VIPGOCI_EXIT_GITHUB_PROBLEM
		);
	}

	return $resp_data;
}

/**
 * Submit PUT request to the GitHub API.
 *
 * @codeCoverageIgnore
 */
function vipgoci_github_put_url(
	$github_url,
	$github_postfields,
	$github_token
) {
	/*
	 * Actually send a request to GitHub -- make sure
	 * to retry if something fails.
	 */
	do {
		/*
		 * By default, assume request went through okay.
		 */

		$ret_val = 0;

		/*
		 * By default, do not retry the request,
		 * just assume everything goes well
		 */

		$retry_req = false;

		/*
		 * Initialize and send request.
		 */

		$ch = curl_init();

		curl_setopt(
			$ch, CURLOPT_URL, $github_url
		);

		curl_setopt(
			$ch, CURLOPT_RETURNTRANSFER, 1
		);

		curl_setopt(
			$ch, CURLOPT_CONNECTTIMEOUT, 20
		);

		curl_setopt(
			$ch, CURLOPT_USERAGENT,	VIPGOCI_CLIENT_ID
		);

		curl_setopt(
			$ch, CURLOPT_CUSTOMREQUEST, 'PUT'
		);

		curl_setopt(
			$ch,
			CURLOPT_POSTFIELDS,
			json_encode( $github_postfields )
		);

		curl_setopt(
			$ch,
			CURLOPT_HEADERFUNCTION,
			'vipgoci_curl_headers'
		);

		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array( 'Authorization: token ' . $github_token )
		);

		vipgoci_curl_set_security_options(
			$ch
		);

		// Make sure to pause between GitHub-requests
		vipgoci_github_wait();

		/*
		 * Execute query to GitHub, keep
		 * record of how long time it took,
		 * and keep count of how many requests we do.
		 */

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'github_api_put' );

		vipgoci_counter_report(
			VIPGOCI_COUNTERS_DO,
			'github_api_request_put',
			1
		);

		$resp_data = curl_exec( $ch );

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'github_api_put' );


		$resp_headers = vipgoci_curl_headers(
			null,
			null
		);


		/*
		 * Assume 200 for success, everything else for failure.
		 */
		if ( intval( $resp_headers['status'][0] ) !== 200 ) {
			/*
			 * Set default wait period between requests
			 */
			$retry_sleep = 10;

			/*
			 * Set error-return value
			 */
			$ret_val = -1;

			/*
			 * Figure out if to retry...
			 */

			// Decode JSON
			$resp_data = json_decode( $resp_data );

			if (
				( isset(
					$resp_headers['retry-after']
				) ) &&
				( intval(
					$resp_headers['retry-after']
				) > 0 )
			) {
				$retry_req = true;
				$retry_sleep = intval(
					$resp_headers['retry-after']
				);
			}

			else if (
				( $resp_data->message ==
					'Validation Failed' ) &&

				( $resp_data->errors[0] ==
					'was submitted too quickly ' .
					'after a previous comment' )
			) {
				/*
				 * These messages are due to the
				 * submission being categorized
				 * as a spam by GitHub -- no good
				 * reason to retry, really.
				 */
				$retry_req = false;
				$retry_sleep = 20;
			}

			else if (
				( $resp_data->message ==
					'Validation Failed' )
			) {
				$retry_req = false;
			}

			else if (
				( $resp_data->message ==
					'Server Error' )
			) {
				$retry_req = false;
			}

			vipgoci_log(
				'GitHub reported an error' .
					( $retry_req === true ?
					' will retry request in ' .
					$retry_sleep . ' seconds' :
					'' ),
				array(
					'http_url'
						=> $github_url,

					'http_response_headers'
						=> $resp_headers,

					'http_reponse_body'
						=> $resp_data,
				)
			);

			sleep( $retry_sleep + 1 );
		}

		vipgoci_github_rate_limits_check(
			$github_url,
			$resp_headers
		);


		curl_close( $ch );

	} while ( $retry_req == true );

	return $ret_val;
}

/*
 * Fetch diffs between two commits from GitHub API,
 * cache results.
 */
function vipgoci_github_diffs_fetch_unfiltered(
	string $repo_owner,
	string $repo_name,
	string $github_token,
	string $commit_id_a,
	string $commit_id_b
): ?array {

	/*
	 * Check for a cached copy of the diffs
	 */
	$cached_id = array(
		__FUNCTION__, $repo_owner, $repo_name,
		$commit_id_a, $commit_id_b
	);

	$cached_data = vipgoci_cache( $cached_id );

	vipgoci_log(
		'Fetching diffs between two commits ' .
			'from GitHub' .
			vipgoci_cached_indication_str( $cached_data ),

		array(
			'repo_owner'	=> $repo_owner,
			'repo_name'	=> $repo_name,
			'commit_id_a'	=> $commit_id_a,
			'commit_id_b'	=> $commit_id_b,
		)
	);

	if ( false !== $cached_data ) {
		return $cached_data;
	}

	/*
	 * Nothing cached; ask GitHub.
	 */

	$github_url =
		VIPGOCI_GITHUB_BASE_URL . '/' .
		'repos/' .
		rawurlencode( $repo_owner ) . '/' .
		rawurlencode( $repo_name ) . '/' .
		'compare/' .
		rawurlencode( $commit_id_a ) .
		'...' .
		rawurlencode( $commit_id_b );

	// FIXME: Error-handling
	$resp_raw = json_decode(
		vipgoci_github_fetch_url(
			$github_url,
			$github_token
		),
		true
	);

	/*
	 * If no "files" in array, return with error.
	 */
	if ( ! isset( $resp_raw['files'] ) ) {
		return null;
	}

	/*
	 * Prepare results array.
	 */
	$diff_results = array(
		'files'         => array(),
		'statistics'    => array(
			VIPGOCI_GIT_DIFF_CALC_CHANGES['+']      => 0,
			VIPGOCI_GIT_DIFF_CALC_CHANGES['-']      => 0,
			'changes'                               => 0,
		),
	);

	foreach( array_values( $resp_raw['files'] ) as $file_item ) {
		$diff_results['files'][
			$file_item['filename']
		] = array(
			'filename'	=> $file_item['filename'],
			'patch'		=> (
				isset( $file_item['patch'] ) ?
				$file_item['patch'] :
				''
			),
			'status'	=> $file_item['status'],
			'additions'	=> $file_item['additions'],
			'deletions'	=> $file_item['deletions'],
			'changes'	=> $file_item['changes'],
		);

		if ( isset( $file_item['previous_filename'] ) ) {
			$diff_results['files'][
				$file_item['filename']
			]['previous_filename'] =
				$file_item['previous_filename'];
		}

		$diff_results['statistics']
			[ VIPGOCI_GIT_DIFF_CALC_CHANGES['+'] ] +=
				$file_item[ VIPGOCI_GIT_DIFF_CALC_CHANGES['+'] ];

		$diff_results['statistics']
			[ VIPGOCI_GIT_DIFF_CALC_CHANGES['-'] ] +=
				$file_item[ VIPGOCI_GIT_DIFF_CALC_CHANGES['-'] ];

		$diff_results['statistics']['changes'] +=
			$file_item['changes'];
	}

	/*
	 * Save a copy in cache.
	 */
	vipgoci_cache( $cached_id, $diff_results );

	vipgoci_log(
		'Fetched git diff from GitHub API',
		array(
			'statistics'            => $diff_results['statistics'],
			'files_partial_20_max'  => array_slice(
				array_keys(
					$diff_results['files']
				),
				0,
				20
			)
		)
	);

	return $diff_results;
}

/*
 * Fetch information from GitHub on a particular
 * commit within a particular repository, using
 * the access-token given.
 *
 * Will return the JSON-decoded data provided
 * by GitHub on success.
 */
function vipgoci_github_fetch_commit_info(
	$repo_owner,
	$repo_name,
	$commit_id,
	$github_token,
	$filter = null
) {
	/* Check for cached version */
	$cached_id = array(
		__FUNCTION__, $repo_owner, $repo_name,
		$commit_id, $github_token
	);

	$cached_data = vipgoci_cache( $cached_id );


	vipgoci_log(
		'Fetching commit info from GitHub' .
			vipgoci_cached_indication_str( $cached_data ),
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id' => $commit_id,
			'filter' => $filter,
		)
	);


	if ( false === $cached_data ) {

		/*
		 * Nothing cached, attempt to
		 * fetch from GitHub.
		 */

		$github_url =
			VIPGOCI_GITHUB_BASE_URL . '/' .
			'repos/' .
			rawurlencode( $repo_owner ) . '/' .
			rawurlencode( $repo_name ) . '/' .
			'commits/' .
			rawurlencode( $commit_id );

		$data = json_decode(
			vipgoci_github_fetch_url(
				$github_url,
				$github_token
			)
		);


		if (
			( isset( $data->message ) ) &&
			( 'Not Found' === $data->message )
		) {
			vipgoci_sysexit(
				'Unable to fetch commit-info from GitHub, ' .
					'the commit does not exist.',
				array(
					'error_data' => $data
				),
				VIPGOCI_EXIT_GITHUB_PROBLEM
			);
		}

		// Cache the results
		vipgoci_cache(
			$cached_id,
			$data
		);
	}

	else {
		$data = $cached_data;
	}

	/*
	 * Filter array of files based on
	 * parameter -- i.e., files
	 * that the commit implicates, and
	 * GitHub hands over to us.
	 */

	if ( null !== $filter ) {
		$files_new = array();

		foreach( $data->files as $file_info ) {
			/*
			 * If the file does not have an acceptable
			 * file-extension, skip
			 */

			if ( false === vipgoci_filter_file_path(
				$file_info->filename,
				$filter
			) ) {
				continue;
			}


			/*
			 * Process status based on filter.
			 */

			if (
				! in_array(
					$file_info->status,
					$filter['status']
				)
			) {

				vipgoci_log(
					'Skipping file that does not have a  ' .
						'matching modification status',

					array(
						'filename'	=>
							$file_info->filename,

						'status'	=>
							$file_info->status,

						'filter_status' =>
							$filter['status'],
					),
					1
				);

				continue;
			}

			$files_new[] = $file_info;
		}

		$data->files = $files_new;
	}

	return $data;
}


/*
 * Fetch all comments made on GitHub for the
 * repository and commit specified -- but are
 * still associated with a Pull Request.
 *
 * Will return an associative array of comments,
 * with file-name and file-line number as keys. Will
 * return false on an error.
 */
function vipgoci_github_pr_reviews_comments_get(
	$options,
	$commit_id,
	$commit_made_at,
	&$prs_comments
) {
	$repo_owner = $options['repo-owner'];
	$repo_name = $options['repo-name'];
	$github_token = $options['token'];

	/*
	 * Try to get comments from cache
	 */
	$cached_id = array(
		__FUNCTION__, $repo_owner, $repo_name,
		$commit_made_at, $github_token
	);

	$cached_data = vipgoci_cache( $cached_id );

	vipgoci_log(
		'Fetching Pull-Requests comments info from GitHub' .
			vipgoci_cached_indication_str( $cached_data ),

		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id' => $commit_id,
			'commit_made_at' => $commit_made_at,
		)
	);


	if ( false !== $cached_data ) {
		$prs_comments_cache = $cached_data;
	}

	else {
		/*
		 * Nothing in cache, ask GitHub.
		 */

		$page = 1;
		$per_page = 100;
		$prs_comments_cache = array();

		do {
			$github_url =
				VIPGOCI_GITHUB_BASE_URL . '/' .
				'repos/' .
				rawurlencode( $repo_owner ) . '/' .
				rawurlencode( $repo_name ) . '/' .
				'pulls/' .
				'comments?' .
				'sort=created&' .
				'direction=asc&' .
				'since=' . rawurlencode( $commit_made_at ) . '&' .
				'page=' . rawurlencode( $page ) . '&' .
				'per_page=' . rawurlencode( $per_page );

			// FIXME: Detect when GitHub returned with an error
			$prs_comments_tmp = json_decode(
				vipgoci_github_fetch_url(
					$github_url,
					$github_token
				)
			);

			foreach ( $prs_comments_tmp as $pr_comment ) {
				$prs_comments_cache[] = $pr_comment;
			}

			$page++;
		} while ( count( $prs_comments_tmp ) >= $per_page );

		vipgoci_cache( $cached_id, $prs_comments_cache );
	}


	foreach ( $prs_comments_cache as $pr_comment ) {
		if ( null === $pr_comment->position ) {
			/*
			 * If no line-number was provided,
			 * ignore the comment.
			 */
			continue;
		}

		if ( $commit_id !== $pr_comment->original_commit_id ) {
			/*
			 * If commit_id on comment does not match
			 * current one, skip the comment.
			 */
			continue;
		}

		/*
		 * Look through each comment, create an associative array
		 * of file:position out of all the comments, so any comment
		 * can easily be found.
		 */

		$prs_comments[
			$pr_comment->path . ':' .
			$pr_comment->position
		][] = $pr_comment;
	}
}


/*
 * Get all review-comments submitted to a
 * particular Pull-Request.
 * Supports filtering by:
 * - User submitted (parameter: login)
 * - Comment state (parameter: comments_active, true/false)
 *
 * Note that parameter login can be assigned a magic
 * value, 'myself', in which case the actual username
 * will be assumed to be that of the token-holder.

 */
function vipgoci_github_pr_reviews_comments_get_by_pr(
	$options,
	$pr_number,
	$filter = array()
) {

	/*
	 * Calculate caching ID.
	 *
	 * Note that $filter should be used here and not its
	 * individual components, to enable new data to be fetched
	 * (i.e. avoiding of caching by callers).
	 */
	$cache_id = array(
		__FUNCTION__, $options['repo-owner'], $options['repo-name'],
		$pr_number, $filter
	);

	/*
	 * Try to get cached data
	 */
	$cached_data = vipgoci_cache( $cache_id );

	vipgoci_log(
		'Fetching all review comments submitted to a Pull-Request' .
			vipgoci_cached_indication_str( $cached_data ),
		array(
			'repo_owner'	=> $options['repo-owner'],
			'repo_name'	=> $options['repo-name'],
			'pr_number'	=> $pr_number,
			'filter'	=> $filter,
		)
	);

	/*
	 * If we have the information cached,
	 * return that.
	 */
	if ( false !== $cached_data ) {
		return $cached_data;
	}

	if (
		( isset( $filter['login'] ) ) &&
		( 'myself' === $filter['login'] )
	) {
		/* Get info about token-holder */
		$current_user_info = vipgoci_github_authenticated_user_get(
			$options['token']
		);

		$filter['login'] = $current_user_info->login;
	}

	$page = 1;
	$per_page = 100;

	$all_comments = array();

	do {
		$github_url =
			VIPGOCI_GITHUB_BASE_URL . '/' .
			'repos/' .
			rawurlencode( $options['repo-owner'] ) . '/' .
			rawurlencode( $options['repo-name'] ) . '/' .
			'pulls/' .
			rawurlencode( $pr_number ) . '/' .
			'comments?' .
			'page=' . rawurlencode( $page ) . '&' .
			'per_page=' . rawurlencode( $per_page );

		$comments = json_decode(
			vipgoci_github_fetch_url(
				$github_url,
				$options['token']
			)
		);

		foreach( $comments as $comment ) {
			if (
				( isset( $filter['login'] ) ) &&
				( $comment->user->login !== $filter['login'] )
			) {
				continue;
			}

			if ( isset( $filter['comments_active'] ) ) {
				if (
					( ( $comment->position !== null ) &&
					( $filter['comments_active'] === false ) )
					||
					( ( $comment->position === null ) &&
					( $filter['comments_active'] === true ) )
				) {
					continue;
				}
			}

			$all_comments[] = $comment;
		}

		$page++;
	} while( count( $comments ) >= $per_page );

	/*
	 * Cache the results and return
	 */
	vipgoci_cache( $cache_id, $all_comments );

	return $all_comments;
}


/*
 * Remove a particular PR review comment.
 */

function vipgoci_github_pr_reviews_comments_delete(
	$options,
	$comment_id
) {
	vipgoci_log(
		'Deleting an inline comment from a Pull-Request ' .
			'Review',
		array(
			'repo_owner'	=> $options['repo-owner'],
			'repo_name'	=> $options['repo-name'],
			'comment_id'	=> $comment_id,
		)
	);

	$github_url =
		VIPGOCI_GITHUB_BASE_URL . '/' .
		'repos/' .
		rawurlencode( $options['repo-owner'] ) . '/' .
		rawurlencode( $options['repo-name'] ) . '/' .
		'pulls/' .
		'comments/' .
		rawurlencode( $comment_id );

	vipgoci_github_post_url(
		$github_url,
		array(),
		$options['token'],
		true // Indicates a 'DELETE' request
	);
}

/*
 * Get all generic comments made to a Pull-Request from Github.
 */

function vipgoci_github_pr_generic_comments_get_all(
	$repo_owner,
	$repo_name,
	$pr_number,
	$github_token
) {
	/*
	 * Try to get comments from cache
	 */
	$cached_id = array(
		__FUNCTION__, $repo_owner, $repo_name,
		$pr_number, $github_token
	);

	$cached_data = vipgoci_cache( $cached_id );

	vipgoci_log(
		'Fetching Pull-Requests generic comments from GitHub' .
			vipgoci_cached_indication_str( $cached_data ),

		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'pr_number' => $pr_number,
		)
	);

	if ( false !== $cached_data ) {
		return $cached_data;
	}


	/*
	 * Nothing in cache, ask GitHub.
	 */

	$pr_comments_ret = array();

	$page = 1;
	$per_page = 100;

	do {
		$github_url =
			VIPGOCI_GITHUB_BASE_URL . '/' .
			'repos/' .
			rawurlencode( $repo_owner ) . '/' .
			rawurlencode( $repo_name ) . '/' .
			'issues/' .
			rawurlencode( $pr_number ) . '/' .
			'comments' .
			'?page=' . rawurlencode( $page ) . '&' .
			'per_page=' . rawurlencode( $per_page );


		$pr_comments_raw = json_decode(
			vipgoci_github_fetch_url(
				$github_url,
				$github_token
			)
		);

		foreach ( $pr_comments_raw as $pr_comment ) {
			$pr_comments_ret[] = $pr_comment;
		}

		$page++;
	} while ( count( $pr_comments_raw ) >= $per_page );


	vipgoci_cache(
		$cached_id,
		$pr_comments_ret
	);

	return $pr_comments_ret;
}

/*
 * Submit generic PR comment to GitHub, reporting any
 * issues found within $results. Selectively report
 * issues that we are supposed to report on, ignore
 * others. Attempts to format the comment to GitHub.
 */

function vipgoci_github_pr_generic_comment_submit_results(
	$repo_owner,
	$repo_name,
	$github_token,
	$commit_id,
	$results,
	$informational_url
) {
	$stats_types_to_process = array(
		VIPGOCI_STATS_LINT,
	);


	vipgoci_log(
		'About to ' .
		'submit generic PR comment to GitHub about issues',
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id' => $commit_id,
			'results' => $results,
		)
	);


	foreach (
		// The $results['issues'] array is keyed by Pull-Request number
		array_keys(
			$results['issues']
		) as $pr_number
	) {
		$github_url =
			VIPGOCI_GITHUB_BASE_URL . '/' .
			'repos/' .
			rawurlencode( $repo_owner ) . '/' .
			rawurlencode( $repo_name ) . '/' .
			'issues/' .
			rawurlencode( $pr_number ) . '/' .
			'comments';


		$github_postfields = array(
			'body' => ''
		);


		$tmp_linebreak = false;

		foreach (
			$results['issues'][ $pr_number ]
				as $commit_issue
		) {
			if ( ! in_array(
				strtolower(
					$commit_issue['type']
				),
				$stats_types_to_process,
				true
			) ) {
				// Not an issue we process, ignore
				continue;
			}


			/*
			 * Put in linebreaks
			 */

			if ( false === $tmp_linebreak ) {
				$tmp_linebreak = true;
			}

			else {
				$github_postfields['body'] .= "\n\r";

				vipgoci_markdown_comment_add_pagebreak(
					$github_postfields['body']
				);
			}


			/*
			 * Construct comment -- (start or continue)
			 */
			$github_postfields['body'] .=
				'**' .

				// First in: level (error, warning)
				ucfirst( strtolower(
					$commit_issue['issue']['level']
				) ) .

				'**' .

				': ' .

				// Then the message
				str_replace(
					'\'',
					'`',
					$commit_issue['issue']['message']
				) .

				"\n\r\n\r" .

				// And finally a URL to the issue is
				'https://github.com/' .
					$repo_owner . '/' .
					$repo_name . '/' .
					'blob/' .
					$commit_id . '/' .
					$commit_issue['file_name'] .
					'#L' . $commit_issue['file_line'] .

				"\n\r";
		}


		if ( $github_postfields['body'] === '' ) {
			/*
			 * No issues? Nothing to report to GitHub.
			 */

			continue;
		}


		/*
		 * There are issues, report them.
		 *
		 * Put togather a comment to be posted to GitHub
		 * -- splice a header to the message we currently have.
		 */

		$tmp_postfields_body =
			'**' . VIPGOCI_SYNTAX_ERROR_STR . '**' .
			"\n\r\n\r" .

			"Scan performed on the code at commit " . $commit_id .
				" ([view code](https://github.com/" .
				rawurlencode( $repo_owner ) . "/" .
				rawurlencode( $repo_name ) . "/" .
				"tree/" .
				rawurlencode( $commit_id ) .
				"))." .
				"\n\r";

		vipgoci_markdown_comment_add_pagebreak(
			$tmp_postfields_body
		);

		/*
		 * If we have informational URL, append that
		 * and a generic message.
		 */
		if ( null !== $informational_url ) {
			$tmp_postfields_body .=
				sprintf(
					VIPGOCI_INFORMATIONAL_MESSAGE,
					$informational_url
				) .
				"\n\r";

			vipgoci_markdown_comment_add_pagebreak(
				$tmp_postfields_body
			);
		}

		/*
		 * Splice the two messages together,
		 * remove temporary variable.
		 */
		$github_postfields['body'] =
			$tmp_postfields_body .
			$github_postfields['body'];

		unset( $tmp_postfields_body );

		vipgoci_github_post_url(
			$github_url,
			$github_postfields,
			$github_token
		);
	}
}

/*
 * Post a generic PR comment to GitHub. Will
 * include a commit_id in the comment if provided.
 */
function vipgoci_github_pr_comments_generic_submit(
	$repo_owner,
	$repo_name,
	$github_token,
	$pr_number,
	$message,
	$commit_id = null
) {
	vipgoci_log(
		'Posting a comment to a Pull-Request',
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'pr_number' => $pr_number,
			'commit_id' => $commit_id,
			'message' => $message,
		),
		0,
		true // Log to IRC as well
	);

	$github_url =
		VIPGOCI_GITHUB_BASE_URL . '/' .
		'repos/' .
		rawurlencode( $repo_owner ) . '/' .
		rawurlencode( $repo_name ) . '/' .
		'issues/' .
		rawurlencode( $pr_number ) . '/' .
		'comments';


	$github_postfields = array();
	$github_postfields['body'] =
		$message;

	if ( ! empty( $commit_id ) ) {
		$github_postfields['body'] .=
			' (commit-ID: ' . $commit_id . ').';
	}

	$github_postfields['body'] .=
		"\n\r";

	vipgoci_github_post_url(
		$github_url,
		$github_postfields,
		$github_token
	);
}

/*
 * Remove any comments made by us earlier.
 */

function vipgoci_github_pr_comments_cleanup(
	$repo_owner,
	$repo_name,
	$commit_id,
	$github_token,
	$branches_ignore,
	$skip_draft_prs,
	$comments_remove
) {
	vipgoci_log(
		'About to clean up generic PR comments on Github',
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id' => $commit_id,
			'branches_ignore' => $branches_ignore,
			'comments_remove' => $comments_remove,
			'skip_draft_prs' => $skip_draft_prs,
		)
	);

	/* Get info about token-holder */
	$current_user_info = vipgoci_github_authenticated_user_get(
		$github_token
	);


	$prs_implicated = vipgoci_github_prs_implicated(
		$repo_owner,
		$repo_name,
		$commit_id,
		$github_token,
		$branches_ignore,
		$skip_draft_prs
	);

	foreach ( $prs_implicated as $pr_item ) {
		$pr_comments = vipgoci_github_pr_generic_comments_get_all(
			$repo_owner,
			$repo_name,
			$pr_item->number,
			$github_token
		);

		foreach ( $pr_comments as $pr_comment ) {

			if ( $pr_comment->user->login !== $current_user_info->login ) {
				// Do not delete other person's comment
				continue;
			}


			/*
			 * Check if the comment is actually
			 * a feedback generated by vip-go-ci -- we might
			 * be run as on a shared account, with comments
			 * being generated by other programs, and we do
			 * not want to remove those. Avoid that.
			 */

			foreach( $comments_remove as $comments_remove_item ) {
				if ( strpos(
					$pr_comment->body,
					$comments_remove_item
				) !== false ) {
					// Actually delete the comment
					vipgoci_github_pr_generic_comment_delete(
						$repo_owner,
						$repo_name,
						$github_token,
						$pr_comment->id
					);
				}
			}
		}
	}
}


/*
 * Delete generic comment made to Pull-Request.
 */

function vipgoci_github_pr_generic_comment_delete(
	$repo_owner,
	$repo_name,
	$github_token,
	$comment_id
) {
	vipgoci_log(
		'About to remove generic PR comment on Github',
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'comment_id' => $comment_id,
		),
		1
	);


	$github_url =
		VIPGOCI_GITHUB_BASE_URL . '/' .
		'repos/' .
		rawurlencode( $repo_owner ) . '/' .
		rawurlencode( $repo_name ) . '/' .
		'issues/' .
		'comments/' .
		rawurlencode( $comment_id );

	/*
	 * Send DELETE request to GitHub.
	 */
	vipgoci_github_post_url(
		$github_url,
		array(),
		$github_token,
		true
	);
}

/*
 * Post generic comment to each Pull-Request
 * that has target branch that matches the
 * options given, but only if the same generic
 * comment has not been posted before. Uses a
 * comment given by one of the options.
 */
function vipgoci_github_pr_generic_support_comment_submit(
	$options,
	$prs_implicated
) {

	$log_debugmsg =
		array(
			'post-generic-pr-support-comments' =>
				$options['post-generic-pr-support-comments'],

			'post-generic-pr-support-comments-on-drafts' =>
				$options['post-generic-pr-support-comments-on-drafts'],

			'post-generic-pr-support-comments-string' =>
				$options['post-generic-pr-support-comments-string'],

			'post-generic-pr-support-comments-branches' =>
				$options['post-generic-pr-support-comments-branches'],

			'post-generic-pr-support-comments-repo-meta-match' =>
				$options['post-generic-pr-support-comments-repo-meta-match'],
		);

	/*
	 * Detect if to run, or invalid configuration.
	 */
	if (
		( true !== $options['post-generic-pr-support-comments'] ) ||
		( empty( $options['post-generic-pr-support-comments-string'] ) ) ||
		( empty( $options['post-generic-pr-support-comments-branches'] ) )
	) {
		vipgoci_log(
			'Not posting support-comments on Pull-Requests, as ' .
				'either not configured to do so, or ' .
				'incorrectly configured',
			$log_debugmsg
		);

		return;
	}

	else {
		vipgoci_log(
			'Posting support-comments on Pull-Requests',
			$log_debugmsg
		);
	}

	/*
	 * Check if a field value in response
	 * from repo-meta API service
	 * matches the field value given here.
	 */
	if ( ! empty( $options['post-generic-pr-support-comments-repo-meta-match'] ) ) {
		$option_key_no_match = null;

		$repo_meta_api_data_match = vipgoci_repo_meta_api_data_match(
			$options,
			'post-generic-pr-support-comments-repo-meta-match',
			$option_key_no_match
		);

		if ( true !== $repo_meta_api_data_match ) {
			vipgoci_log(
				'Not posting generic support comment, as repo-meta API field-value did not match given criteria',
				array(
				)
			);

			return;
		}
	}

	else {
		/*
		 * If matching is not configured, we post
		 * first message we can find.
		 */

		$tmp_generic_support_msgs_keys = array_keys(
			$options['post-generic-pr-support-comments-string']
		);

		$option_key_no_match = $tmp_generic_support_msgs_keys[0];
	}


	foreach(
		$prs_implicated as $pr_item
	) {
		/*
		 * If not one of the target-branches,
		 * skip this PR.
		 */
		if (
			( in_array(
				'any',
				$options['post-generic-pr-support-comments-branches'][ $option_key_no_match ],
				true
			) === false )
			&&
			( ( in_array(
				$pr_item->base->ref,
				$options['post-generic-pr-support-comments-branches'][ $option_key_no_match ],
				true
			) === false ) )
		) {
			vipgoci_log(
				'Not posting support-comment to PR, not in list of target branches',
				array(
					'repo-owner'	=> $options['repo-owner'],
					'repo-name'	=> $options['repo-name'],
					'pr_number'	=> $pr_item->number,
					'pr_base_ref'	=> $pr_item->base->ref,
					'post-generic-pr-support-comments-branches' =>
						$options['post-generic-pr-support-comments-branches'][ $option_key_no_match ],
				)
			);

			continue;
		}

		/*
		 * Do not post support comments on drafts when
		 * not configured to do so.
		 */
		if (
			( false === $options['post-generic-pr-support-comments-on-drafts'][ $option_key_no_match ] ) &&
			( true === $pr_item->draft )
		) {
			vipgoci_log(
				'Not posting support-comment to PR, is draft',
				array(
					'repo-owner'	=> $options['repo-owner'],
					'repo-name'	=> $options['repo-name'],
					'pr_number'	=> $pr_item->number,
					'pr_base_ref'	=> $pr_item->base->ref,
					'post-generic-pr-support-comments-on-drafts' =>
						$options['post-generic-pr-support-comments-on-drafts'][ $option_key_no_match ],
				)
		);

			continue;
		}

		/*
		 * When configured to do so, do not post support comments when a special label
		 * has been added to the Pull-Request.
		 */

		if ( ! empty( $options[ 'post-generic-pr-support-comments-skip-if-label-exists' ][ $option_key_no_match ] ) ) {			
			$pr_label_support_comment_skip = vipgoci_github_pr_labels_get(
				$options['repo-owner'],
				$options['repo-name'],
				$options['token'],
				$pr_item->number,
				$options[ 'post-generic-pr-support-comments-skip-if-label-exists' ][ $option_key_no_match ]
			);

			if ( false !== $pr_label_support_comment_skip ) {
				vipgoci_log(
					'Not posting support comment to PR, label exists',
					array(
						'repo-owner'	=> $options['repo-owner'],
						'repo-name'	=> $options['repo-name'],
						'pr_number'	=> $pr_item->number,
						'pr_base_ref'	=> $pr_item->base->ref,
						'post-generic-pr-support-comments-skip-if-label-exists' =>
							$options[ 'post-generic-pr-support-comments-skip-if-label-exists' ][ $option_key_no_match ]
					)
				);

				continue;
			}
		}

		/*
		 * Check if the comment we are set to
		 * post already exists, and if so, do
		 * not post anything.
		 */

		$existing_comments = vipgoci_github_pr_generic_comments_get_all(
			$options['repo-owner'],
			$options['repo-name'],
			$pr_item->number,
			$options['token']
		);

		$comment_exists_already = false;

		foreach(
			$existing_comments as
				$existing_comment_item
		) {

			if ( strpos(
				$existing_comment_item->body,
				$options['post-generic-pr-support-comments-string'][ $option_key_no_match ]
			) !== false ) {
				$comment_exists_already = true;
			}
		}

		if ( true === $comment_exists_already ) {
			vipgoci_log(
				'Not submitting support-comment to Pull-Request as it already exists',
				array(
					'pr_number'	=> $pr_item->number,
				)
			);

			continue;
		}

		/*
		 * All checks successful, post comment.
		 */
		vipgoci_github_pr_comments_generic_submit(
			$options['repo-owner'],
			$options['repo-name'],
			$options['token'],
			$pr_item->number,
			$options['post-generic-pr-support-comments-string'][ $option_key_no_match ]
		);
	}
}

/*
 * Get all reviews for a particular Pull-Request,
 * and allow filtering by:
 * - User submitted (parameter: login)
 * - State of review (parameter: state,
 *	values are an array of: CHANGES_REQUESTED,
 *	COMMENTED, APPROVED)
 *
 * Note that parameter login can be assigned a magic
 * value, 'myself', in which case the actual username
 * will be assumed to be that of the token-holder.
 */
function vipgoci_github_pr_reviews_get(
	$repo_owner,
	$repo_name,
	$pr_number,
	$github_token,
	$filter = array(),
	$skip_cache = false
) {

	$cache_id = array(
		__FUNCTION__, $repo_owner, $repo_name, $pr_number,
		$github_token,
	);

	$cached_data = vipgoci_cache( $cache_id );

	if ( true === $skip_cache ) {
		$cached_data = false;
	}

	vipgoci_log(
		'Fetching reviews for Pull-Request ' .
			vipgoci_cached_indication_str( $cached_data ),
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'pr_number' => $pr_number,
			'filter' => $filter,
			'skip_cache' => $skip_cache,
		)
	);


	if ( false === $cached_data ) {
		/*
		 * Fetch reviews, paged, from GitHub.
		 */

		$ret_reviews = array();

		$page = 1;
		$per_page = 100;

		do {
			$github_url =
				VIPGOCI_GITHUB_BASE_URL . '/' .
				'repos/' .
				rawurlencode( $repo_owner ) . '/' .
				rawurlencode( $repo_name ) . '/' .
				'pulls/' .
				rawurlencode( $pr_number ) . '/' .
				'reviews' .
				'?per_page=' . rawurlencode( $per_page ) . '&' .
				'page=' . rawurlencode( $page );


			/*
			 * Fetch reviews, decode result.
			 */
			$pr_reviews = json_decode(
				vipgoci_github_fetch_url(
					$github_url,
					$github_token
				)
			);

			foreach( $pr_reviews as $pr_review ) {
				$ret_reviews[] = $pr_review;
			}

			unset( $pr_review );

			$page++;
		} while( count( $pr_reviews ) >= $per_page );


		vipgoci_cache(
			$cache_id,
			$ret_reviews
		);
	}

	else {
		$ret_reviews = $cached_data;
	}


	/*
	 * Figure out login name.
	 */
	if (
		( ! empty( $filter['login'] ) ) &&
		( $filter['login'] === 'myself' )
	) {
		$current_user_info = vipgoci_github_authenticated_user_get(
			$github_token
		);

		$filter['login'] = $current_user_info->login;
	}

	/*
	 * Loop through each review-item,
	 * do filtering and save the ones
	 * we want to keep.
	 */

	$ret_reviews_filtered = array();

	foreach( $ret_reviews as $pr_review ) {
		if ( ! empty( $filter['login'] ) ) {
			if (
				$pr_review->user->login !==
				$filter['login']
			) {
				continue;
			}
		}

		if ( ! empty( $filter['state'] ) ) {
			$match = false;

			foreach(
				$filter['state'] as
					$allowed_state
			) {
				if (
					$pr_review->state ===
					$allowed_state
				) {
					$match = true;
				}
			}

			if ( false === $match ) {
				continue;
			}
		}

		$ret_reviews_filtered[] = $pr_review;
	}


	return $ret_reviews_filtered;
}

/*
 * Submit a review on GitHub for a particular commit,
 * and pull-request using the access-token provided.
 */
function vipgoci_github_pr_review_submit(
	$repo_owner,
	$repo_name,
	$github_token,
	$commit_id,
	$results,
	$informational_url,
	$github_review_comments_max,
	$github_review_comments_include_severity
) {

	$stats_types_to_process = array(
		VIPGOCI_STATS_PHPCS,
		VIPGOCI_STATS_HASHES_API,
	);

	vipgoci_log(
		'About to submit comment(s) to GitHub about issue(s)',
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id' => $commit_id,
			'results' => $results,
		)
	);


	/*
	 * Reverse results before starting processing,
	 * so that results are shown in correct order
	 * after posting.
	 */

	foreach (
		array_keys(
			$results['issues']
		) as $pr_number
	) {
		$results['issues'][ $pr_number ] = array_reverse(
			$results['issues'][ $pr_number ]
		);
	}

	foreach (
		// The $results array is keyed by Pull-Request number
		array_keys(
			$results['issues']
		) as $pr_number
	) {

		$github_url =
			VIPGOCI_GITHUB_BASE_URL . '/' .
			'repos/' .
			rawurlencode( $repo_owner ) . '/' .
			rawurlencode( $repo_name ) . '/' .
			'pulls/' .
			rawurlencode( $pr_number ) . '/' .
			'reviews';


		$github_postfields = array(
			'commit_id'	=> $commit_id,
			'body'		=> '',
			'event'		=> '',
			'comments'	=> array(),
		);


		/*
		 * For each issue reported, format
		 * and prepare to be published on
		 * GitHub -- ignore those issues
		 * that we should not process.
		 */
		foreach (
			$results['issues'][ $pr_number ]
				as $commit_issue
		) {
			if ( ! in_array(
				strtolower(
					$commit_issue['type']
				),
				$stats_types_to_process,
				true
			) ) {
				// Not an issue we process, ignore
				continue;
			}

			/*
			 * Construct comment, append to array of comments.
			 */

			$github_postfields['comments'][] = array(
				'body'		=>

					// Add nice label
					vipgoci_github_transform_to_emojis(
						$commit_issue['issue']['level']
					) . ' ' .


					'**' .

					// Level -- error, warning
					ucfirst( strtolower(
						$commit_issue['issue']['level']
						)) .

					(
						true === $github_review_comments_include_severity ?
							(
								'( severity ' .
								$commit_issue['issue']['severity'] .
								' )'
							)
							:
							( '' )
					) .

					'**: ' .

					// Then the message it self
					htmlentities(
						rtrim(
							$commit_issue['issue']['message'],
							'.'
						)
					)


					. ' (*' .
					htmlentities(
						$commit_issue['issue']['source']
					)
					. '*).',

				'position'	=> $commit_issue['file_line'],
				'path'		=> $commit_issue['file_name']
			);
		}


		/*
		 * Figure out what to report to GitHub.
		 *
		 * If there are any 'error'-level issues, make sure the submission
		 * asks for changes to be made, otherwise only comment.
		 *
		 * If there are no issues at all -- warning, error, info -- do not
		 * submit anything.
		 */

		$github_postfields['event'] = 'COMMENT';

		$github_errors = false;
		$github_warnings = false;
		$github_info = false;

		foreach (
			$stats_types_to_process as
				$stats_type
		) {
			if ( ! empty(
				$results['stats']
					[ $stats_type ][ $pr_number ]['error']
			) ) {
				$github_postfields['event'] = 'REQUEST_CHANGES';
				$github_errors = true;
			}

			if ( ! empty(
				$results['stats']
					[ $stats_type ][ $pr_number ]['warning']
			) ) {
				$github_warnings = true;
			}

			if ( ! empty(
				$results['stats']
					[ $stats_type ][ $pr_number ]['info']
			) ) {
				$github_info = true;
			}
		}


		/*
		 * If there are no issues to report to GitHub,
		 * do not continue processing the Pull-Request.
		 * Our exit signal will indicate if anything is wrong.
		 */
		if (
			( false === $github_errors ) &&
			( false === $github_warnings ) &&
			( false === $github_info )
		) {
			continue;
		}

		unset( $github_errors );
		unset( $github_warnings );


		/*
		 * Compose the number of warnings/errors for the
		 * review-submission to GitHub.
		 */

		foreach (
			$stats_types_to_process as
				$stats_type
		) {
			/*
			 * Add page-breaking, if needed.
			 */
			if ( ! empty( $github_postfields['body'] ) ) {
				vipgoci_markdown_comment_add_pagebreak(
					$github_postfields['body']
				);
			}

			/*
			 * Check if this type of scanning
			 * was skipped, and if so, note it.
			 */

			if ( empty(
				$results
					['stats']
					[ strtolower( $stats_type ) ]
					[ $pr_number ]
			) ) {
				$github_postfields['body'] .=
					'**' . $stats_type . '**' .
						"-scanning skipped\n\r";

				// Skipped
				continue;
			}


			/*
			 * If the current stat-type has no items
			 * to report, do not print out anything for
			 * it saying we found something to report on.
			 */

			$found_stats_to_ignore = true;

			foreach(
				$results
					['stats']
					[ strtolower( $stats_type ) ]
					[ $pr_number ] as

					$commit_issue_stat_key =>
						$commit_issue_stat_value
			) {
				if ( $commit_issue_stat_value > 0 ) {
					$found_stats_to_ignore = false;
				}
			}

			if ( true === $found_stats_to_ignore ) {
				// Skipped
				continue;
			}

			unset( $found_stats_to_ignore );


			$github_postfields['body'] .=
				'**' . $stats_type . '**' .
				" scanning turned up:\n\r";

			foreach (
				$results
					['stats']
					[ strtolower( $stats_type ) ]
					[ $pr_number ] as

					$commit_issue_stat_key =>
						$commit_issue_stat_value
			) {
				/*
				 * Do not include statistic in the
				 * the report if nothing is found.
				 *
				 * Note that if nothing is found at
				 * all, we will not get to this point,
				 * so there is no need to report if
				 * nothing is found at all.
				 */
				if ( 0 === $commit_issue_stat_value ) {
					continue;
				}

				$github_postfields['body'] .=
					vipgoci_github_transform_to_emojis(
						$commit_issue_stat_key
					) . ' ' .

					$commit_issue_stat_value . ' ' .
					$commit_issue_stat_key .
					( ( $commit_issue_stat_value > 1 ) ? 's' : '' ) .
					' ' .
					"\n\r";
			}
		}

		/*
		 * If we have a informational-URL about
		 * the bot, append it along with a generic
		 * message.
		 */
		if ( null !== $informational_url ) {
			$github_postfields['body'] .=
				"\n\r";

			vipgoci_markdown_comment_add_pagebreak(
				$github_postfields['body']
			);


			$github_postfields['body'] .=
				sprintf(
					VIPGOCI_INFORMATIONAL_MESSAGE,
					$informational_url
				);
		}


		/*
		 * Only submit a specific number of comments in one go.
		 *
		 * This hopefully will reduce the likelihood of problems
		 * with the GitHub API. Also, it will avoid excessive number
		 * of comments being posted at once.
		 *
		 * Do this by picking out a few comments at a time,
		 * submit, and repeat.
		 */

		if (
			count( $github_postfields['comments'] ) >
				$github_review_comments_max
		) {
			// Append a comment that there will be more reviews
			$github_postfields['body'] .=
				"\n\r" .
				'Posting will continue in further review(s)';
		}


		do {
			/*
			 * Set temporary variable we use for posting
			 * and remove all comments from it.
			 */
			$github_postfields_tmp = $github_postfields;

			unset( $github_postfields_tmp['comments'] );

			/*
			 * Add in comments.
			 */

			for ( $i = 0; $i < $github_review_comments_max; $i++ ) {
				$y = count( $github_postfields['comments'] );

				if ( 0 === $y ) {
					/* No more items, break out */
					break;
				}

				$y--;

				$github_postfields_tmp['comments'][] =
					$github_postfields['comments'][ $y ];

				unset(
					$github_postfields['comments'][ $y ]
				);
			}

			// Actually send a request to GitHub
			$github_post_res_tmp = vipgoci_github_post_url(
				$github_url,
				$github_postfields_tmp,
				$github_token
			);

			/*
			 * If something goes wrong with any submission,
			 * keep a note on that.
			 */
			if (
				( ! isset( $github_post_res ) ||
				( -1 !== $github_post_res ) )
			) {
				$github_post_res = $github_post_res_tmp;
			}

			// Set a new post-body for future posting.
			$github_postfields['body'] = 'Previous scan continued.';
		} while ( count( $github_postfields['comments'] ) > 0 );

		unset( $github_post_res_tmp );
		unset( $y );
		unset( $i );

		/*
		 * If one or more submissions went wrong,
		 * let humans know that there was a problem.
		 */
		if ( -1 === $github_post_res ) {
			vipgoci_github_pr_comments_generic_submit(
				$repo_owner,
				$repo_name,
				$github_token,
				$pr_number,
				VIPGOCI_GITHUB_ERROR_STR,
				$commit_id
			);
		}
	}

	return;
}

/*
 * Dismiss a particular review
 * previously submitted to a Pull-Request.
 */

function vipgoci_github_pr_review_dismiss(
	$repo_owner,
	$repo_name,
	$pr_number,
	$review_id,
	$dismiss_message,
	$github_token
) {

	vipgoci_log(
		'Dismissing a Pull-Request Review',
		array(
			'repo_owner'		=> $repo_owner,
			'repo_name'		=> $repo_name,
			'pr_number'		=> $pr_number,
			'review_id'		=> $review_id,
			'dismiss_message'	=> $dismiss_message,
		)
	);

	$github_url =
		VIPGOCI_GITHUB_BASE_URL . '/' .
		'repos/' .
		rawurlencode( $repo_owner ) . '/' .
		rawurlencode( $repo_name ) . '/' .
		'pulls/' .
		rawurlencode( $pr_number ) . '/' .
		'reviews/' .
		rawurlencode( $review_id ) . '/' .
		'dismissals';

	vipgoci_github_put_url(
		$github_url,
		array(
			'message' => $dismiss_message
		),
		$github_token
	);
}


/*
 * Dismiss all Pull-Request Reviews that have no
 * active comments attached to them.
 */
function vipgoci_github_pr_reviews_dismiss_with_non_active_comments(
	$options,
	$pr_number
) {
	vipgoci_log(
		'Dismissing any Pull-Request reviews submitted by ' .
			'us and contain no active inline comments any more',
		array(
			'repo_owner'		=> $options['repo-owner'],
			'repo_name'		=> $options['repo-name'],
			'pr_number'		=> $pr_number,
		)
	);

	/*
	 * Get any Pull-Request reviews with changes
 	 * required status, and submitted by us.
	 */
	$pr_reviews = vipgoci_github_pr_reviews_get(
		$options['repo-owner'],
		$options['repo-name'],
		$pr_number,
		$options['token'],
		array(
			'login' => 'myself',
			'state' => array( 'CHANGES_REQUESTED' )
		)
	);

	/*
	 * Get all comments to a the current Pull-Request.
	 *
	 * Note that we must bypass cache here,
	 */
	$all_comments = vipgoci_github_pr_reviews_comments_get_by_pr(
		$options,
		$pr_number,
		array(
			'login' => 'myself',
			'timestamp' => time() // To bypass caching
		)
	);

	if ( count( $all_comments ) === 0 ) {
		/*
		 * In case we receive no comments at all
		 * from GitHub, do not do anything, as a precaution.
		 * Receiving no comments might indicate a
		 * failure (communication error or something else),
		 * and if we dismiss reviews that seem not to
		 * contain any comments, we might risk dismissing
		 * all reviews when there is a failure. By
		 * doing this, we take much less risk.
		 */
		vipgoci_log(
			'Not dismissing any reviews, as no inactive ' .
				'comments submitted to the Pull-Request ' .
				'were found',
			array(
				'repo_owner'	=> $options['repo-owner'],
				'repo_name'	=> $options['repo-name'],
				'pr_number'	=> $pr_number,
			)
		);

		return;
	}

	$reviews_status = array();

	foreach( $all_comments as $comment_item ) {
		/*
		 * Not associated with a review? Ignore then.
		 */
		if ( ! isset( $comment_item->pull_request_review_id ) ) {
			continue;
		}

		/*
		 * If the review ID is not found in
		 * the array of reviews, put in 'null'.
		 */
		if ( ! isset( $reviews_status[
			$comment_item->pull_request_review_id
		] ) ) {
			$reviews_status[
				$comment_item->pull_request_review_id
			] = null;
		}

		/*
		 * In case position (relative line number)
		 * is at null, this means that the comment
		 * is no longer 'active': It has become obsolete
		 * as the code has changed. If we have not so far
		 * found any instance of the review associated
		 * with the comment having other active comments,
		 * mark it as 'safe to dismiss'.
		 */
		if ( null === $comment_item->position ) {
			if (
				$reviews_status[
					$comment_item->pull_request_review_id
				] !== false
			) {
				$reviews_status[
					$comment_item->pull_request_review_id
				] = true;
			}
		}

		else {
			$reviews_status[
				$comment_item->pull_request_review_id
			] = false;
		}
	}

	/*
	 * Loop through each review we
	 * found matching the specific criteria.
	 *
	 * Note that implicit in this logic is that
	 * there must be some comments attached to a
	 * review so it becomes dismissable at all.
	 */
	foreach( $pr_reviews as $pr_review ) {
		/*
		 * If no active comments were found,
		 * it should be safe to dismiss the review.
		 */
		if (
			( isset( $reviews_status[ $pr_review->id ] ) ) &&
			( true === $reviews_status[ $pr_review->id ] )
		) {
			vipgoci_github_pr_review_dismiss(
				$options['repo-owner'],
				$options['repo-name'],
				$pr_number,
				$pr_review->id,
				'Dismissing review as all inline comments ' .
					'are obsolete by now',
				$options['token']
			);
		}
	}
}

/*
 * Approve a Pull-Request, and afterwards
 * make sure to verify that the latest commit
 * added to the Pull-Request is commit with
 * commit-ID $latest_commit_id -- this is to avoid
 * race-conditions.
 *
 * The race-conditions can occur when a Pull-Request
 * is approved, but it is approved after a new commit
 * was added which has not been scanned.
 */

function vipgoci_github_approve_pr(
	$repo_owner,
	$repo_name,
	$github_token,
	$pr_number,
	$latest_commit_id,
	$message
) {
	$github_url =
		VIPGOCI_GITHUB_BASE_URL . '/' .
		'repos/' .
		rawurlencode( $repo_owner ) . '/' .
		rawurlencode( $repo_name ) . '/' .
		'pulls/' .
		rawurlencode( $pr_number ) . '/' .
		'reviews';

	$github_postfields = array(
		'commit_id' => $latest_commit_id,
		'body' => null,
		'event' => 'APPROVE',
		'comments' => array()
	);

	$github_postfields['body'] = $message;

	vipgoci_log(
		'Sending request to GitHub to approve Pull-Request',
		array(
			'repo_owner'		=> $repo_owner,
			'repo_name'		=> $repo_name,
			'pr_number'		=> $pr_number,
			'latest_commit_id'	=> $latest_commit_id,
			'github_url'		=> $github_url,
			'github_postfields'	=> $github_postfields,
		),
		2
	);

	// Actually approve
	vipgoci_github_post_url(
		$github_url,
		$github_postfields,
		$github_token
	);

	// FIXME: Approve PR, then make sure
	// the latest commit in the PR is actually
	// the one provided in $latest_commit_id
}


/*
 * Get Pull Requests which are open currently
 * and the commit is a part of. Make sure to ignore
 * certain branches specified in a parameter.
 */

function vipgoci_github_prs_implicated(
	$repo_owner,
	$repo_name,
	$commit_id,
	$github_token,
	$branches_ignore,
	$skip_draft_prs = false
) {

	/*
	 * Check for cached copy
	 */

	$cached_id = array(
		__FUNCTION__, $repo_owner, $repo_name,
		$commit_id, $github_token, $branches_ignore
	);

	$cached_data = vipgoci_cache( $cached_id );

	vipgoci_log(
		'Fetching all open Pull-Requests from GitHub' .
			vipgoci_cached_indication_str( $cached_data ),
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'commit_id' => $commit_id,
			'branches_ignore' => $branches_ignore,
			'skip_draft_prs' => $skip_draft_prs,
		)
	);

	if ( false !== $cached_data ) {
		/*
		 * Filter away draft Pull-Requests if requested.
		 */
		if ( true === $skip_draft_prs ) {
			$cached_data = vipgoci_github_pr_remove_drafts(
				$cached_data
			);
		}

		return $cached_data;
	}


	/*
	 * Nothing cached; ask GitHub.
	 */

	$prs_implicated = array();


	$page = 1;
	$per_page = 100;

	/*
	 * Fetch all open Pull-Requests, store
	 * PR IDs that have a commit-head that matches
	 * the one we are working on.
	 */
	do {
		$github_url =
			VIPGOCI_GITHUB_BASE_URL . '/' .
			'repos/' .
			rawurlencode( $repo_owner ) . '/' .
			rawurlencode( $repo_name ) . '/' .
			'pulls' .
			'?state=open&' .
			'page=' . rawurlencode( $page ) . '&' .
			'per_page=' . rawurlencode( $per_page );


		// FIXME: Detect when GitHub sent back an error
		$prs_implicated_unfiltered = json_decode(
			vipgoci_github_fetch_url(
				$github_url,
				$github_token
			)
		);


		foreach ( $prs_implicated_unfiltered as $pr_item ) {
			if ( ! isset( $pr_item->head->ref ) ) {
				continue;
			}

			/*
			 * If the branch this Pull-Request is associated
			 * with is one of those we are supposed to ignore,
			 * then ignore it.
			 */
			if ( in_array(
				$pr_item->head->ref,
				$branches_ignore
			) ) {
				continue;
			}


			/*
			 * If the commit we are processing currently
			 * matches the head-commit of the Pull-Request,
			 * then the Pull-Request should be considered to
			 * be relevant.
			 */
			if ( $commit_id === $pr_item->head->sha ) {
				$prs_implicated[ $pr_item->number ] = $pr_item;
			}
		}

		sleep ( 2 );

		$page++;
	} while ( count( $prs_implicated_unfiltered ) >= $per_page );


	/*
	 * Convert number parameter of each object
	 * saved to an integer.
	 */

	foreach(
		array_keys( $prs_implicated ) as
			$pr_implicated
	) {
		if ( isset( $pr_implicated->number ) ) {
			$prs_implicated[ $pr_implicated->number ]->number =
				(int) $pr_implicated->number;
		}
	}

	vipgoci_cache( $cached_id, $prs_implicated );

	/*
	 * Filter away draft Pull-Requests if requested.
	 */
	if ( true === $skip_draft_prs ) {
		$prs_implicated = vipgoci_github_pr_remove_drafts(
			$prs_implicated
		);
	}

	return $prs_implicated;
}


/*
 * Get all commits that are a part of a Pull-Request.
 */

function vipgoci_github_prs_commits_list(
	$repo_owner,
	$repo_name,
	$pr_number,
	$github_token
) {

	/*
	 * Check for cached copy
	 */
	$cached_id = array(
		__FUNCTION__, $repo_owner, $repo_name,
		$pr_number, $github_token
	);

	$cached_data = vipgoci_cache( $cached_id );


	vipgoci_log(
		'Fetching information about all commits made' .
			' to Pull-Request #' .
			(int) $pr_number . ' from GitHub' .
			vipgoci_cached_indication_str( $cached_data ),

		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'pr_number' => $pr_number,
		)
	);

	if ( false !== $cached_data ) {
		return $cached_data;
	}

	/*
	 * Nothing in cache; ask GitHub.
	 */

	$pr_commits = array();


	$page = 1;
	$per_page = 100;

	do {
		$github_url =
			VIPGOCI_GITHUB_BASE_URL . '/' .
			'repos/' .
			rawurlencode( $repo_owner ) . '/' .
			rawurlencode( $repo_name ) . '/' .
			'pulls/' .
			rawurlencode( $pr_number ) . '/' .
			'commits?' .
			'page=' . rawurlencode( $page ) . '&' .
			'per_page=' . rawurlencode( $per_page );


		// FIXME: Detect when GitHub sent back an error
		$pr_commits_raw = json_decode(
			vipgoci_github_fetch_url(
				$github_url,
				$github_token
			)
		);

		foreach ( $pr_commits_raw as $pr_commit ) {
			$pr_commits[] = $pr_commit->sha;
		}

		$page++;
	} while ( count( $pr_commits_raw ) >= $per_page );

	vipgoci_cache( $cached_id, $pr_commits );

	return $pr_commits;
}

/**
 * Get information from GitHub on the user
 * authenticated.
 *
 * @codeCoverageIgnore
 */
function vipgoci_github_authenticated_user_get( $github_token ) {
	$cached_id = array(
		__FUNCTION__, $github_token
	);

	$cached_data = vipgoci_cache( $cached_id );

	vipgoci_log(
		'Trying to get information about the user the GitHub-token belongs to' .
			vipgoci_cached_indication_str( $cached_data ),
		array(
		)
	);

	if ( false !== $cached_data ) {
		return $cached_data;
	}


	$github_url =
		VIPGOCI_GITHUB_BASE_URL . '/' .
		'user';

	$current_user_info_json = vipgoci_github_fetch_url(
		$github_url,
		$github_token
	);

	$current_user_info = null;

	if ( false !== $current_user_info_json ) {
		$current_user_info = json_decode(
			$current_user_info_json
		);
	}

	if (
		( false === $current_user_info_json ) ||
		( null === $current_user_info )
	) {
		vipgoci_log(
			'Unable to get information about token-holder from' .
				'GitHub due to error',
			array(
				'current_user_info_json' => $current_user_info_json,
				'current_user_info' => $current_user_info,
			)
		);

		return false;
	}


	vipgoci_cache( $cached_id, $current_user_info );

	return $current_user_info;
}


/*
 * Add a particular label to a specific
 * Pull-Request (or issue).
 */
function vipgoci_github_label_add_to_pr(
	$repo_owner,
	$repo_name,
	$github_token,
	$pr_number,
	$label_name
) {
	vipgoci_log(
		'Adding label to GitHub issue',
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'pr_number' => $pr_number,
			'label_name' => $label_name,
		)
	);

	$github_url =
		VIPGOCI_GITHUB_BASE_URL . '/' .
		'repos/' .
		rawurlencode( $repo_owner ) . '/' .
		rawurlencode( $repo_name ) . '/' .
		'issues/' .
		rawurlencode( $pr_number ) . '/' .
		'labels';

	$github_postfields = array(
		$label_name
	);

	vipgoci_github_post_url(
		$github_url,
		$github_postfields,
		$github_token
	);
}

/*
 * Fetch labels associated with a
 * particular issue/Pull-Request.
 */
function vipgoci_github_pr_labels_get(
	$repo_owner,
	$repo_name,
	$github_token,
	$pr_number,
	$label_to_look_for = null,
	$skip_cache = false
) {
	/*
	 * Check first if we have
	 * got the information cached
	 */
	$cache_id = array(
		__FUNCTION__, $repo_owner, $repo_name,
		$github_token, $pr_number
	);

	$cached_data = vipgoci_cache( $cache_id );

	/*
	 * If asked to skip cache, imitate no cached
	 * data available.
	 */
	if (
		( false !== $cached_data ) &&
		( true === $skip_cache )
	) {
		$cached_data = false;
	}

	vipgoci_log(
		'Getting labels associated with GitHub issue' .
			vipgoci_cached_indication_str( $cached_data ),
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'pr_number' => $pr_number,
			'label_to_look_for' => $label_to_look_for,
			'skip_cache' => $skip_cache,
		)
	);

	/*
	 * If there is nothing cached, fetch it
	 * from GitHub.
	 */
	if ( false === $cached_data ) {
		$github_url =
			VIPGOCI_GITHUB_BASE_URL . '/' .
			'repos/' .
			rawurlencode( $repo_owner ) . '/' .
			rawurlencode( $repo_name ) . '/' .
			'issues/' .
			rawurlencode( $pr_number ) . '/' .
			'labels';

		$data = vipgoci_github_fetch_url(
			$github_url,
			$github_token
		);

		$data = json_decode( $data );

		vipgoci_cache( $cache_id, $data );
	}

	else {
		$data = $cached_data;
	}

	/*
	 * We got something -- validate it.
	 */

	if ( empty( $data ) ) {
		return false;
	}

	else if ( ( ! empty( $data ) ) && ( null !== $label_to_look_for ) ) {
		/*
		 * Decoding of data succeeded,
		 * look for any labels and return
		 * them specifically
		 */
		foreach( $data as $data_item ) {
			if ( $data_item->name === $label_to_look_for ) {
				return $data_item;
			}
		}

		return false;
	}

	return $data;
}


/*
 * Remove a particular label from a specific
 * Pull-Request (or issue).
 */
function vipgoci_github_pr_label_remove(
	$repo_owner,
	$repo_name,
	$github_token,
	$pr_number,
	$label_name
) {
	vipgoci_log(
		'Removing label from GitHub issue',
		array(
			'repo_owner' => $repo_owner,
			'repo_name' => $repo_name,
			'pr_number' => $pr_number,
			'label_name' => $label_name,
		)
	);

	$github_url =
		VIPGOCI_GITHUB_BASE_URL . '/' .
		'repos/' .
		rawurlencode( $repo_owner ) . '/' .
		rawurlencode( $repo_name ) . '/' .
		'issues/' .
		rawurlencode( $pr_number ) . '/' .
		'labels/' .
		rawurlencode( $label_name );

	vipgoci_github_post_url(
		$github_url,
		array(),
		$github_token,
		true // DELETE request will be sent
	);
}


/*
 * Get all events issues related to a Pull-Request
 * from the GitHub API, and filter away any items that
 * do not match a given criteria (if applicable).
 *
 * Note: Using $review_ids_only = true will imply
 * selecting only certain types of events (i.e. dismissed_review).
 */
function vipgoci_github_pr_review_events_get(
	$options,
	$pr_number,
	$filter = null,
	$review_ids_only = false
) {
	$cached_id = array(
		__FUNCTION__, $options['repo-owner'], $options['repo-name'],
		$options['token'], $pr_number
	);

	$cached_data = vipgoci_cache( $cached_id );

	vipgoci_log(
		'Getting issue events for Pull-Request from GitHub API' .
		vipgoci_cached_indication_str( $cached_data ),
		array(
			'repo_owner' => $options['repo-owner'],
			'repo_name' => $options['repo-name'],
			'pr_number' => $pr_number,
			'filter' => $filter,
			'review_ids_only' => $review_ids_only,
		)
	);

	if ( false === $cached_data ) {
		$page = 1;
		$per_page = 100;

		$all_issue_events = array();

		do {
			$github_url =
				VIPGOCI_GITHUB_BASE_URL . '/' .
				'repos/' .
				rawurlencode( $options['repo-owner'] ) . '/' .
				rawurlencode( $options['repo-name'] ) . '/' .
				'issues/' .
				rawurlencode( $pr_number ) . '/' .
				'events?' .
				'page=' . rawurlencode( $page ) . '&' .
				'per_page=' . rawurlencode( $per_page );


			$issue_events = vipgoci_github_fetch_url(
				$github_url,
				$options['token']
			);

			$issue_events = json_decode(
				$issue_events
			);

			foreach( $issue_events as $issue_event ) {
				$all_issue_events[] = $issue_event;
			}

			unset( $issue_event );

			$page++;
		} while ( count( $issue_events ) >= $per_page );

		$issue_events = $all_issue_events;
		unset( $all_issue_events );

		vipgoci_cache(
			$cached_id,
			$issue_events
		);
	}

	else {
		$issue_events = $cached_data;
	}

	/*
	 * Filter results if requested. We can filter
	 * by type of event and/or by actors that initiated
	 * the event.
	 */
	if ( null !== $filter ) {
		$filtered_issue_events = array();

		foreach( $issue_events as $issue_event ) {
			if (
				( ! empty( $filter['event_type'] ) ) &&
				( is_string( $filter['event_type'] ) ) &&
				(
					$issue_event->event !==
					$filter['event_type']
				)
			) {
				continue;
			}

			if (
				( ! empty( $filter['actors_logins'] ) ) &&
				( is_array( $filter['actors_logins'] ) ) &&
				( false === in_array(
					$issue_event->actor->login,
					$filter['actors_logins']
				) )
			) {
				continue;
			}

			if (
				( ! empty( $filter['actors_ids'] ) ) &&
				( is_array( $filter['actors_ids'] ) ) &&
				( false === in_array(
					$issue_event->actor->id,
					$filter['actors_ids']
				) )
			) {
				continue;
			}


			$filtered_issue_events[] = $issue_event;
		}

		$issue_events = $filtered_issue_events;
	}

	if ( true === $review_ids_only ) {
		$issue_events_ret = array();

		foreach( $issue_events as $issue_event ) {
			if ( ! isset(
				$issue_event->dismissed_review->review_id
			) ) {
				continue;
			}

			$issue_events_ret[] =
				$issue_event->dismissed_review->review_id;
		}

		$issue_events = $issue_events_ret;
	}

	return $issue_events;
}


/*
 * Get members for a team.
 */
function vipgoci_github_team_members_get(
	$github_token,
	$team_id,
	$return_values_only = null
) {
	$cached_id = array(
		__FUNCTION__, $github_token, $team_id
	);

	$cached_data = vipgoci_cache( $cached_id );

	vipgoci_log(
		'Getting members for organization team' .
		vipgoci_cached_indication_str( $cached_data ),
		array(
			'team_id' => $team_id,
			'return_values_only' => $return_values_only,
		)
	);

	if ( false === $cached_data ) {
		$page = 1;
		$per_page = 100;

		$team_members_all = array();

		do {
			$github_url =
				VIPGOCI_GITHUB_BASE_URL . '/' .
				'teams/' .
				rawurlencode( $team_id ) . '/' .
				'members?' .
				'page=' . rawurlencode( $page ) . '&' .
				'per_page=' . rawurlencode( $per_page );


			$team_members = vipgoci_github_fetch_url(
				$github_url,
				$github_token
			);

			$team_members = json_decode(
				$team_members
			);

			foreach( $team_members as $team_member ) {
				$team_members_all[] = $team_member;
			}

			$page++;
		} while ( count( $team_members ) >= $per_page );

		$team_members = $team_members_all;
		unset( $team_members_all );
		unset( $team_member );

		vipgoci_cache(
			$cached_id,
			$team_members
		);
	}

	else {
		$team_members = $cached_data;
	}

	/*
	 * If caller specified only certain value from
	 * each item to be return, honor that.
	 */
	if ( null !== $return_values_only ) {
		$team_members = array_column(
			(array) $team_members,
			$return_values_only
		);
	}

	return $team_members;
}


/*
 * Get team members for one or more teams,
 * return members as a merged array.
 *
 * @codeCoverageIgnore
 */
function vipgoci_github_team_members_many_get(
	$github_token,
	$team_ids_arr = array()
) {
	vipgoci_log(
		'Getting members of teams specified by caller',
		array(
			'teams_ids' => $team_ids_arr,
		)
	);

	$team_members_ids_arr = array();

	foreach( $team_ids_arr as $team_id_item ) {
		$team_id_members = vipgoci_github_team_members_get(
			$github_token,
			$team_id_item,
			'id'
		);

		$team_members_ids_arr = array_merge(
			$team_members_ids_arr,
			$team_id_members
		);
	}

	$team_members_ids_arr = array_unique(
		$team_members_ids_arr
	);

	return $team_members_ids_arr;
}


/*
 * Get organization teams available to the calling
 * user from the GitHub API.
 */
function vipgoci_github_org_teams_get(
	$github_token,
	$org_id,
	$filter = null,
	$keyed_by = null
) {
	$cached_id = array(
		__FUNCTION__, $github_token, $org_id
	);

	$cached_data = vipgoci_cache( $cached_id );

	vipgoci_log(
		'Getting organization teams from GitHub API' .
		vipgoci_cached_indication_str( $cached_data ),
		array(
			'org_id' => $org_id,
			'filter' => $filter,
			'keyed_by' => $keyed_by,
		)
	);

	if ( false === $cached_data ) {
		$page = 1;
		$per_page = 100;

		$org_teams_all = array();

		do {
			$github_url =
				VIPGOCI_GITHUB_BASE_URL . '/' .
				'orgs/' .
				rawurlencode( $org_id ) . '/' .
				'teams?' .
				'page=' . rawurlencode( $page ) . '&' .
				'per_page=' . rawurlencode( $per_page );


			$org_teams = vipgoci_github_fetch_url(
				$github_url,
				$github_token
			);

			$org_teams = json_decode(
				$org_teams
			);

			foreach( $org_teams as $org_team ) {
				$org_teams_all[] = $org_team;
			}

			$page++;
		} while ( count( (array) $org_teams ) >= $per_page );

		$org_teams = $org_teams_all;
		unset( $org_teams_all );

		vipgoci_cache(
			$cached_id,
			$org_teams
		);
	}

	else {
		$org_teams = $cached_data;
	}


	/*
	 * Filter the results according to criteria.
	 */
	if (
		( null !== $filter ) &&
		( ! empty( $filter['slug'] ) ) &&
		( is_string( $filter['slug'] ) )
	) {
		$org_teams_filtered = array();

		foreach( $org_teams as $org_team ) {
			if ( $filter['slug'] === $org_team->slug ) {
				$org_teams_filtered[] = $org_team;
			}
		}

		$org_teams = $org_teams_filtered;
	}


	/*
	 * If asked for, let the resulting
	 * array be keyed with a certain field.
	 */
	if ( null !== $keyed_by ) {
		$org_teams_keyed = array();

		foreach( $org_teams as $org_team ) {
			$org_team_arr = (array) $org_team;

			/*
			 * In case of invalid response,
			 * ignore item.
			 */
			if ( ! isset( $org_team_arr[ $keyed_by ] ) ) {
				continue;
			}

			$org_teams_keyed[
				$org_team_arr[
					$keyed_by
				]
			][] = $org_team;
		}

		$org_teams = $org_teams_keyed;
	}

	return $org_teams;
}

/*
 * Get repository collaborators.
 *
 * $affiliation can be:
 *  * outside, direct and all
 *
 * $filter works for permissions property, and removes
 * any items that do not match.
 */
function vipgoci_github_repo_collaborators_get(
	$repo_owner,
	$repo_name,
	$github_token,
	$affiliation = 'all',
	$filter = array()
) {
	$cached_id = array(
		__FUNCTION__, $repo_owner, $repo_name, $affiliation
	);

	$cached_data = vipgoci_cache( $cached_id );

	vipgoci_log(
		'Getting collaborators for repository from GitHub API' .
		vipgoci_cached_indication_str( $cached_data ),
		array(
			'repo_owner'	=> $repo_owner,
			'repo_name'	=> $repo_name,
			'affiliation'	=> $affiliation,
			'filter'	=> $filter,
		)
	);

	if ( false === $cached_data ) {
		$page = 1;
		$per_page = 100;

		$repo_users_all = array();

		do {
			$github_url =
				VIPGOCI_GITHUB_BASE_URL . '/' .
				'repos/' .
				rawurlencode( $repo_owner ) . '/' .
				rawurlencode( $repo_name ) . '/' .
				'collaborators?' .
				'page=' . rawurlencode( $page ) . '&' .
				'per_page=' . rawurlencode( $per_page );

			if ( null !== $affiliation ) {
				$github_url .= '&affiliation=' . rawurlencode( $affiliation );
			}

			$repo_users = vipgoci_github_fetch_url(
				$github_url,
				$github_token
			);

			$repo_users = json_decode(
				$repo_users
			);

			foreach( $repo_users as $repo_user_item ) {
				$repo_users_all[] = $repo_user_item;
			}

			$page++;
		} while ( count( (array) $repo_users ) >= $per_page );

		unset( $repo_users );

		vipgoci_cache(
			$cached_id,
			$repo_users_all
		);
	}

	else {
		$repo_users_all = $cached_data;
	}

	/*
	 * Filter results.
	 */

	$repo_users_all_new = array();

	foreach (
		$repo_users_all as $repo_user_item
	) {
		foreach( array( 'admin', 'push', 'pull' ) as $_prop ) {
			$repo_user_item_tmp = (array) $repo_user_item;

			if ( isset( $repo_user_item_tmp['permissions'] ) ) {
				$repo_user_item_tmp['permissions'] = (array) $repo_user_item_tmp['permissions'];
			}

			if (
				( isset( $filter[ $_prop ] ) ) &&
				( isset( $repo_user_item_tmp['permissions'][ $_prop ] ) ) &&
				( (bool) $filter[ $_prop ] !== $repo_user_item_tmp['permissions'][ $_prop ] )
			) {
				continue 2;
			}
		}

		$repo_users_all_new[] = $repo_user_item;
	}

	$repo_users_all = $repo_users_all_new;
	unset( $repo_users_all_new );

	return $repo_users_all;
}

<?php

/*
 * Queue up a message for IRC API,
 * or alternatively empty the queue and
 * return its contents.
 */
function vipgoci_irc_api_alert_queue(
	$message = null,
	$dump = false
) {
	static $msg_queue = array();

	if ( true === $dump ) {
		$msg_queue_tmp = $msg_queue;

		$msg_queue = array();

		return $msg_queue_tmp;
	}

	$msg_queue[] = $message;
}

/**
 * Empty IRC message queue and send off
 * to the IRC API.
 *
 * @codeCoverageIgnore
 */
function vipgoci_irc_api_alerts_send(
	$irc_api_url,
	$irc_api_token,
	$botname,
	$channel
) {
	$msg_queue = vipgoci_irc_api_alert_queue(
		null, true
	);

	vipgoci_log(
		'Sending messages to IRC API',
		array(
			'msg_queue' => $msg_queue,
		)
	);

	foreach( $msg_queue as $message ) {
		$irc_api_postfields = array(
			'message' => $message,
			'botname' => $botname,
			'channel' => $channel,
		);

		$ch = curl_init();

		curl_setopt(
			$ch, CURLOPT_URL, $irc_api_url
		);

		curl_setopt(
			$ch, CURLOPT_RETURNTRANSFER, 1
		);

		curl_setopt(
			$ch, CURLOPT_CONNECTTIMEOUT, 5
		);

		curl_setopt(
			$ch, CURLOPT_USERAGENT, VIPGOCI_CLIENT_ID
		);

		curl_setopt(
			$ch, CURLOPT_POST, 1
		);

		curl_setopt(
			$ch,
			CURLOPT_POSTFIELDS,
			json_encode( $irc_api_postfields )
		);

		curl_setopt(
			$ch,
			CURLOPT_HEADERFUNCTION,
			'vipgoci_curl_headers'
		);

		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array( 'Authorization: Bearer ' . $irc_api_token )
		);

		vipgoci_curl_set_security_options(
			$ch
		);

		/*
		 * Execute query, keep record of how long time it
		 * took, and keep count of how many requests we do.
		 */

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, 'irc_api_post' );

		vipgoci_counter_report(
			VIPGOCI_COUNTERS_DO,
			'irc_api_request_post',
			1
		);

		$resp_data = curl_exec( $ch );

		vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, 'irc_api_post' );

		$resp_headers = vipgoci_curl_headers(
			null,
			null
		);

		curl_close( $ch );

		/*
		 * Enforce a small wait between requests.
		 */

		time_nanosleep( 0, 500000000 );
	}
}

/**
 * Send statistics to pixel API so
 * we can keep track of actions we
 * take during runtime.
 *
 * @codeCoverageIgnore
 */
function vipgoci_send_stats_to_pixel_api(
	$pixel_api_url,
	$stat_names_to_report,
	$statistics
) {
	vipgoci_log(
		'Sending statistics to pixel API service',
		array(
			'stat_names_to_report' =>
				$stat_names_to_report
		)
	);

	$stat_names_to_groups = array(
	);

	foreach(
		array_keys( $stat_names_to_report ) as
			$statistic_group
	) {
		foreach(
			$stat_names_to_report[
				$statistic_group
			] as $stat_name
		) {
			$stat_names_to_groups[
				$stat_name
			] = $statistic_group;
		}
	}

	foreach(
		$statistics as
			$stat_name => $stat_value
	) {

		/*
		 * We are to report only certain
		 * values, so skip those who we should
		 * not report on.
		 */
		if ( false === array_key_exists(
			$stat_name,
			$stat_names_to_groups
		) ) {
			/*
			 * Not found, so nothing to report, skip.
			 */
			continue;
		}

		/*
		 * Compose URL.
		 */
		$url =
			$pixel_api_url .
			'?' .
			'v=wpcom-no-pv' .
			'&' .
			'x_' . rawurlencode(
				$stat_names_to_groups[
					$stat_name
				]
			) .
			'/' .
			rawurlencode(
				$stat_name
			) . '=' .
			rawurlencode(
				$stat_value
			);

		/*
		 * Call service, do nothing with output.
		 * Specify a short timeout.
		 */
		$ctx = stream_context_create(
			array(
				'http' => array(
					'timeout' => 5
				)
			)
		);

		file_get_contents( $url, 0, $ctx );

		/*
		 * Sleep a short while between
		 * requests.
		 */
		time_nanosleep(
			0,
			500000000
		);
	}
}


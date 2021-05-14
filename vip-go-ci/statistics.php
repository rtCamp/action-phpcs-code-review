<?php

/*
 * Initialize statistics array
 */
function vipgoci_stats_init( $options, $prs_implicated, &$results ) {
	/*
	 * Init stats
	 */

	foreach ( $prs_implicated as $pr_item ) {
		/*
		 * Initialize array for stats and
		 * results of scanning, if needed.
		 */

		if ( empty( $results['issues'][ $pr_item->number ] ) ) {
			$results['issues'][ $pr_item->number ] = array(
			);
		}

		foreach (
			array(
				VIPGOCI_STATS_PHPCS,
				VIPGOCI_STATS_LINT,
				VIPGOCI_STATS_HASHES_API
			)
			as $stats_type
		) {
			/*
			 * Initialize stats for the stats-types only when
			 * supposed to run them
			 */
			if (
				( true !== $options[ $stats_type ] ) ||
				( ! empty( $results['stats'][ $stats_type ][ $pr_item->number ] ) )
			) {
				continue;
			}

			$results['stats'][ $stats_type ]
				[ $pr_item->number ] = array(
					'error'		=> 0,
					'warning'	=> 0,
					'info'		=> 0,
				);
		}
	}
}


/*
 * A simple function to keep record of how
 * much a time a particular action takes to execute.
 * Allows multiple records to be kept at the same time.
 *
 * Allows specifying 'start' acton, which indicates that
 * keeping record of measurement should start, 'stop'
 * which indicates that recording should be stopped,
 * and 'dump' which will return with an associative
 * array of all measurements collected henceforth.
 *
 */
function vipgoci_runtime_measure( $action = null, $type = null ) {
	static $runtime = array();
	static $timers = array();

	/*
	 * Check usage.
	 */
	if (
		( VIPGOCI_RUNTIME_START !== $action ) &&
		( VIPGOCI_RUNTIME_STOP !== $action ) &&
		( VIPGOCI_RUNTIME_DUMP !== $action )
	) {
		return false;
	}

	// Dump all runtimes we have
	if ( VIPGOCI_RUNTIME_DUMP === $action ) {
		return $runtime;
	}


	/*
	 * Being asked to either start
	 * or stop collecting, act on that.
	 */

	if ( ! isset( $runtime[ $type ] ) ) {
		$runtime[ $type ] = 0;
	}


	if ( VIPGOCI_RUNTIME_START === $action ) {
		$timers[ $type ] = microtime( true );

		return true;
	}

	else if ( VIPGOCI_RUNTIME_STOP === $action ) {
		if ( ! isset( $timers[ $type ] ) ) {
			return false;
		}

		$tmp_time = microtime( true ) - $timers[ $type ];

		$runtime[ $type ] += $tmp_time;

		unset( $timers[ $type ] );

		return $tmp_time;
	}
}

/*
 * A simple function to keep record of how
 * much time executing a particular command takes.
 */

function vipgoci_runtime_measure_shell_exec(
	string $cmd,
	string $runtime_measure_type = null
): ?string {
	vipgoci_runtime_measure( VIPGOCI_RUNTIME_START, $runtime_measure_type );

	$shell_exec_output = shell_exec( $cmd );

	vipgoci_runtime_measure( VIPGOCI_RUNTIME_STOP, $runtime_measure_type );

	return $shell_exec_output;
}

/*
 * Keep a counter for stuff we do. For instance,
 * number of GitHub API requests.
 */

function vipgoci_counter_report( $action = null, $type = null, $amount = 1 ) {
	static $counters = array();

	/*
	 * Check usage.
	 */
	if (
		( VIPGOCI_COUNTERS_DO !== $action ) &&
		( VIPGOCI_COUNTERS_DUMP !== $action )
	) {
		return false;
	}

	// Dump all runtimes we have
	if ( VIPGOCI_COUNTERS_DUMP === $action ) {
		return $counters;
	}


	/*
	 * Being asked to start
	 * collecting, act on that.
	 */

	if ( VIPGOCI_COUNTERS_DO === $action ) {
		if ( ! isset( $counters[ $type ] ) ) {
			$counters[ $type ] = 0;
		}

		$counters[ $type ] += $amount;

		return true;
	}
}


/*
 * Record statistics on number of linting and PHPCS
 * issues found in results.
 */
function vipgoci_counter_update_with_issues_found(
	$results
) {
	$stats_types = array_keys(
		$results['stats']
	);

	foreach( $stats_types as $stat_type ) {
		/*
		 * Skip statistics for stat-types skipped
		 */
		if ( null === $results['stats'][ $stat_type ] ) {
			continue;
		}

		$pr_keys = array_keys(
			$results['stats'][ $stat_type ]
		);

		$max_issues_found = 0;

		foreach( $pr_keys as $pr_key ) {
			$issue_types = array_keys(
				$results['stats'][
					$stat_type
				][
					$pr_key
				]
			);

			$issues_found = 0;

			foreach( $issue_types as $issue_type ) {
				$issues_found +=
					$results['stats'][
						$stat_type
					][
						$pr_key
					][
						$issue_type
					];
			}

			$max_issues_found = max(
				$issues_found,
				$max_issues_found
			);
		}

		$stat_type = str_replace(
			'-',
			'_',
			$stat_type
		);

		vipgoci_counter_report(
			VIPGOCI_COUNTERS_DO,
			'github_pr_' . $stat_type . '_issues',
			$max_issues_found
		);
	}
}

/*
 * Keep statistics on number of files and lines
 * either scanned or linted.
 */

function vipgoci_stats_per_file(
	$options,
	$file_name,
	$stat_type
) {
	$file_contents = vipgoci_gitrepo_fetch_committed_file(
		$options['repo-owner'],
		$options['repo-name'],
		$options['token'],
		$options['commit'],
		$file_name,
		$options['local-git-repo']
	);


	if ( false === $file_contents ) {
		return;
	}

	$file_lines_cnt = count(
		explode(
			"\n",
			$file_contents
		)
	);

	vipgoci_counter_report(
		VIPGOCI_COUNTERS_DO,
		'github_pr_files_' . $stat_type,
		1
	);

	vipgoci_counter_report(
		VIPGOCI_COUNTERS_DO,
		'github_pr_lines_' . $stat_type,
		$file_lines_cnt
	);
}


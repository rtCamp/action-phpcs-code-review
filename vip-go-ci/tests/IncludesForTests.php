<?php

declare( strict_types=1 );

if ( ! defined( 'VIPGOCI_UNIT_TESTING' ) ) {
	define( 'VIPGOCI_UNIT_TESTING', true );
}

if ( ! defined( 'VIPGOCI_UNIT_TESTS_INI_DIR_PATH' ) ) {
	define( 'VIPGOCI_UNIT_TESTS_INI_DIR_PATH', dirname( __DIR__ ) );
}

function vipgoci_unittests_get_config_value(
	$section,
	$key,
	$secret_file = false
) {
	if ( false === $secret_file ) {
		$ini_array = parse_ini_file(
			VIPGOCI_UNIT_TESTS_INI_DIR_PATH . '/unittests.ini',
			true
		);
	}

	else {
		$ini_array = parse_ini_file(
			VIPGOCI_UNIT_TESTS_INI_DIR_PATH . '/unittests-secrets.ini',
			true
		);
	}


	if ( false === $ini_array ) {
		return null;
	}	

	if ( isset(
		$ini_array
			[ $section ]
			[ $key ]
	) ) {
		if ( empty(
			$ini_array
				[ $section ]
				[ $key ]
		) ) {
			return null;
		}
			
		return $ini_array
			[ $section ]
			[ $key ];
	}

	return null;
}

function vipgoci_unittests_get_config_values( $section, &$config_arr, $secret_file = false ) {
	foreach (
		array_keys( $config_arr ) as $config_key
	) {
		$config_arr[ $config_key ] =
			vipgoci_unittests_get_config_value(
				$section,
				$config_key,
				$secret_file
			);

		if ( empty( $config_arr[ $config_key ] ) ) {
			$config_arr[ $config_key ] = null;
		}
	}
}


/*
 * Clone a git-repository and check out a
 * particular revision.
 */
function vipgoci_unittests_setup_git_repo(
	$options
) {
	$temp_dir = tempnam(
		sys_get_temp_dir(),
		'git-repo-dir-'
	);

	if ( false === $temp_dir ) {
		return false;
	}


	$res = unlink( $temp_dir );

	if ( false === $res ) {
		return false;
	}


	$res = mkdir( $temp_dir );

	if ( false === $res ) {
		return false;
	}


	$cmd = sprintf(
		'%s clone %s %s 2>&1',
		escapeshellcmd( $options['git-path'] ),
		escapeshellarg( $options['github-repo-url'] ),
		escapeshellarg( $temp_dir )
	);

	$cmd_output = '';
	$cmd_status = 0;

	$res = exec( $cmd, $cmd_output, $cmd_status );

	$cmd_output = implode( PHP_EOL, $cmd_output);

	if (
		( null === $cmd_output ) ||
		( false !== strpos( $cmd_output, 'fatal' ) ) ||
		( 0 !== $cmd_status )
	) {
		return false;
	}

	unset( $cmd );
	unset( $cmd_output );
	unset( $cmd_status );


	$cmd = sprintf(
		'%s -C %s checkout %s 2>&1',
		escapeshellcmd( $options['git-path'] ),
		escapeshellarg( $temp_dir ),
		escapeshellarg( $options['commit'] )
	);

	$cmd_output = '';
	$cmd_status = 0;

	$res = exec( $cmd, $cmd_output, $cmd_status );

	$cmd_output = implode( PHP_EOL, $cmd_output);

	if (
		( null === $cmd_output ) ||
		( false !== strpos( $cmd_output, 'fatal:' ) ) ||
		( 0 !== $cmd_status )
	) {
		return false;
	}

	unset( $cmd );
	unset( $cmd_output );
	unset( $cmd_status );


	return $temp_dir;
}

/*
 * Remove temporary git-repository folder
 * created by vipgoci_unittests_setup_git_repo()
 */

function vipgoci_unittests_remove_git_repo( $repo_path ) {
	$temp_dir = sys_get_temp_dir();

	/*
	 * If not a string, do not do anything.
	 */
	if ( ! is_string( $repo_path ) ) {
		return false;
	}

	/*
	 * If this does not look like
	 * a path to a temporary directory,
	 * do not do anything.
	 */
	if ( false === strstr(
		$repo_path,
		$temp_dir
	) ) {
		return false;
	}

	/*
	 * If not a directory, do not do anything.
	 */

	if ( ! is_dir( $repo_path ) ) {
		return false;
	}

	/*
	 * Check if it is really a git-repository.
	 */
	if ( ! is_dir( $repo_path . '/.git' ) ) {
		return false;
	}

	/*
	 * Prepare to run the rm -rf command.
	 */
	
	$cmd = sprintf(
		'%s -rf %s',
		escapeshellcmd( 'rm' ),
		escapeshellarg( $repo_path )
	);

	$cmd_output = '';
	$cmd_status = 0;

	/* 
	 * Run it and check results.
	 */
	$res = exec( $cmd, $cmd_output, $cmd_status );

	if ( $cmd_status === 0 ) {
		return true;
	}

	else {
		printf(
			"Warning: Not able to remove temporary directory successfully; %i, %s",
			$cmd_status,
			$cmd_output
		);

		return false;
	}
}

function vipgoci_unittests_options_test(
	$options,
	$options_not_required,
	&$test_instance
) {
	$missing_options_str = '';

	$options_keys = array_keys(
		$options
	);

	foreach(
		$options_keys as $option_key
	) {
		if ( in_array(
			$option_key,
			$options_not_required
		) ) {
			continue;
		}

		if (
			( '' === $options[ $option_key ] ) ||
			( null === $options[ $option_key ] )
		) {
			if ( '' !== $missing_options_str ) {
				$missing_options_str .= ', ';
			}

			$missing_options_str .= $option_key;
		}
	}

	if ( '' !== $missing_options_str ) {
		$test_instance->markTestSkipped(
			'Skipping test, not configured correctly, as some options are missing (' . $missing_options_str . ')'
		);

		return -1;
	}

	return 0;
}

function vipgoci_unittests_debug_mode_on() {
	/*
	 * Detect if phpunit was started with
	 * debug-mode on.
	 */

	if (
		( in_array( '-v', $_SERVER['argv'] ) ) ||
		( in_array( '-vv', $_SERVER['argv'] ) ) ||
		( in_array( '-vvv', $_SERVER['argv'] ) ) ||
		( in_array( '--debug', $_SERVER['argv'] ) )
	) {
		return true;
	}

	return false;
}

function vipgoci_unittests_output_suppress() {
	if ( false === vipgoci_unittests_debug_mode_on() ) {
		ob_start();
	}
}

function vipgoci_unittests_output_get() {
	if ( false === vipgoci_unittests_debug_mode_on() ) {
		return ob_get_flush();
	}

	return '';
}

function vipgoci_unittests_output_unsuppress() {
	if ( false === vipgoci_unittests_debug_mode_on() ) {
		ob_end_clean();
	}
}

/*
 * Some versions of PHP reverse the ',' and ';'
 * in output from PHP linting; deal with that here.
 *
 * Some versions use ' and some use ", deal with
 * that too.
 */
function vipgoci_unittests_php_syntax_error_compat( $str ) {
	$str = str_replace(
		"syntax error, unexpected end of file, expecting ';' or ','",
		"syntax error, unexpected end of file, expecting ',' or ';'",
		$str
	);

	$str = str_replace(
		'syntax error, unexpected end of file, expecting "," or ";"',
		"syntax error, unexpected end of file, expecting ',' or ';'",
		$str
	);

	return $str;
}

require_once( __DIR__ . '/../requires.php' );


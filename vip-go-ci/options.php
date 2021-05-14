<?php

/*
 * Read settings from a options file in the
 * repository, but only allow certain options
 * to be configured.
 */
function vipgoci_options_read_repo_file(
	&$options,
	$repo_options_file_name,
	$options_overwritable
) {

	if ( false === $options[ 'repo-options' ] ) {
		vipgoci_log(
			'Skipping possibly overwriting options ' .
				'using data from repository settings file ' .
				'as this is disabled via command-line options',
			array(
				'repo-options'
					=> $options[ 'repo-options' ],
			)
		);

		return true;
	}

	vipgoci_log(
		'Reading options from repository, overwriting ' .
			'already set option values if applicable',
		array(
			'repo_owner'		=> $options['repo-owner'],
			'repo_name'		=> $options['repo-name'],
			'commit'		=> $options['commit'],
			'filename'		=> $repo_options_file_name,
			'options_overwritable'	=> $options_overwritable,
			'repo_options_allowed'	=> $options['repo-options-allowed'],
		)
	);


	/*
	 * Try to read options-file from
	 * repository, bail out of that fails.
	 */

	$repo_options_file_contents = vipgoci_gitrepo_fetch_committed_file(
		$options['repo-owner'],
		$options['repo-name'],
		$options['token'],
		$options['commit'],
		$repo_options_file_name,
		$options['local-git-repo']
	);

	if ( false === $repo_options_file_contents ) {
		vipgoci_log(
			'No options found in repository settings-file, nothing further to do',
			array(
				'filename' => $repo_options_file_name,
			)
		);

		return false;
	}

	$repo_options_arr = json_decode(
		$repo_options_file_contents,
		true
	);

	if ( null === $repo_options_arr ) {
		vipgoci_log(
			'Options not parsable, nothing further to do',
			array(
				'filename'
					=> $repo_options_file_name,

				'repo_options_arr'
					=> $repo_options_arr,

				'repo_options_file_contents'
					=> $repo_options_file_contents,

				'repo_options_allowed'
					=> $options['repo-options-allowed'],
			)
		);

		return false;
	}


	/*
	 * Actually set/overwrite values. Keep account of what we set
	 * and to what value, log it at the end.
	 */
	$options_read = array();

	foreach(
		$options_overwritable as
			$option_overwritable_name =>
			$option_overwritable_conf
	) {
		/*
		 * Detect possible issues with
		 * the arguments given, or value not defined
		 * in the options-file.
		 */
		if (
			( ! isset(
				$repo_options_arr[
					$option_overwritable_name
				]
			) )
			||
			( ! isset(
				$option_overwritable_conf['type']
			) )
		) {
			continue;
		}

		/*
		 * We require the 'valid_values' parameter to
		 * be in place for all options, except ones
		 * dealing with arrays.
		 */
		if (
			( 'array' !== $option_overwritable_conf['type'] ) &&
			( ! isset(
				$option_overwritable_conf['valid_values']
			) )
		) {
			continue;
		}

		/*
		 * If not specified, we do not append
		 * by default.
		 */
		if ( ! isset(
			$option_overwritable_conf['append']
		) ) {
			$option_overwritable_conf['append'] = false;
		}

		/*
		 * Limit which options are configurable via repository
		 * options file. Skip the current option if not found
		 * the list of allowed options.
		 */
		if ( ! in_array(
			$option_overwritable_name,
			$options['repo-options-allowed'],
			true
		) ) {
			vipgoci_log(
				'Found option to be configured that cannot ' .
					'be configured via repository ' .
					'options file, skipping',
				array(
					'option_overwritable_name'
						=> $option_overwritable_name,

					'option_overwritable_conf'
						=> $option_overwritable_conf,

					'repo_options_arr[' . $option_overwritable_name .' ]'
						=> $repo_options_arr[ $option_overwritable_name ],

					'repo_options_allowed'
						=> $options['repo-options-allowed'],
				)
			);

			continue;
		}

		$do_skip = false;

		if ( 'integer' === $option_overwritable_conf['type'] ) {
			if ( ! is_numeric( $repo_options_arr[
					$option_overwritable_name
				]
			) ) {
				$do_skip = true;
			}

			else if ( ! in_array(
				$repo_options_arr[
					$option_overwritable_name
				],
				$option_overwritable_conf['valid_values'],
				true
			) ) {
				$do_skip = true;
			}
		}

		else if ( 'boolean' === $option_overwritable_conf['type'] ) {
			if ( ! is_bool(
				$repo_options_arr[
					$option_overwritable_name
				]
			) ) {
				$do_skip = true;
			}

			else if ( ! in_array(
				$repo_options_arr[
					$option_overwritable_name
				],
				$option_overwritable_conf['valid_values'],
				true
			) ) {
				$do_skip = true;
			}
		}

		else if ( 'array' === $option_overwritable_conf['type'] ) {
			if ( ! is_array(
				$repo_options_arr[
					$option_overwritable_name
				]
			) ) {
				$do_skip = true;
			}
		}

		else {
			$do_skip = true;
		}


		if ( true === $do_skip ) {
			vipgoci_log(
				'Found invalid value for option in option-file, or invalid arguments passed internally',
				array(
					'option_overwritable_name'
						=> $option_overwritable_name,

					'option_overwritable_conf'
						=> $option_overwritable_conf,

					'repo_options_arr[' . $option_overwritable_name .' ]'
						=> $repo_options_arr[ $option_overwritable_name ],
				)
			);

			continue;
		}

		if (
			( 'array' === $option_overwritable_conf['type'] ) &&
			( true === $option_overwritable_conf['append'] )
		) {
			$options[
				$option_overwritable_name
			] = array_merge(
				$options[
					$option_overwritable_name
				],
				$repo_options_arr[
					$option_overwritable_name
				]
			);
		}

		else {
			$options[
				$option_overwritable_name
			]
			=
			$repo_options_arr[
				$option_overwritable_name
			];
		}

		$options_read[
			$option_overwritable_name
		]
		=
		$repo_options_arr[
			$option_overwritable_name
		];
	}

	vipgoci_log(
		'Set, overwrote or appended the following options',
		$options_read
	);

	return true;
}

/*
 * Read from repository files which folders are to
 * be skipped from PHPCS scanning and PHP Linting,
 * if configured to do so, and join with any folders
 * specified on the command line.
 */
function vipgoci_options_read_repo_skip_files(
	&$options
) {
	foreach(
		array( 'phpcs', 'lint' ) as $scan_type
	) {
		/*
		 * If not configured to read
		 * from options files, skip.
		 */
		if ( true !== $options[ $scan_type . '-skip-folders-in-repo-options-file' ] ) {
			vipgoci_log(
				'Not reading from repository files which ' .
				$scan_type . ' folders can be skipped, as not ' .
				'configured to do so',
				array(
					$scan_type . '_skip_folders_in_repo_options_file'	=>
						$options[ $scan_type . '-skip-folders-in-repo-options-file' ]
				)
			);

			continue;
		}

		vipgoci_log(
			'Reading from repository files which folders can ' .
				'be skipped from ' . $scan_type . ' scanning',
			array(
				$scan_type . '_skip_folders_in_repo_options_file'	=>
					$options[ $scan_type . '-skip-folders-in-repo-options-file' ]
			)
		);

		$type_options_file_name = '.vipgoci_' . $scan_type . '_skip_folders';

		$type_options_file_contents =
			vipgoci_gitrepo_fetch_committed_file(
				$options['repo-owner'],
				$options['repo-name'],
				$options['token'],
				$options['commit'],
				$type_options_file_name,
				$options['local-git-repo']
			);

		if ( empty(
			$type_options_file_contents
		) ) {
			vipgoci_log(
				'No folders skippable found in repository for ' . $scan_type . ', so skipping',
				array(
					'type_options_file_name'	=> $type_options_file_name,
				)
			);

			continue;
		}

		/*
		 * Options files can use
		 * new-lines as separators
		 * between items. Here we
		 * emulate the behaviour
	 	 * found on the command-line,
		 * which is to use commas.
		 */
		$type_options_file_contents =
			str_replace(
				"\n",
				',',
			$type_options_file_contents
		);

		/*
		 * Create temporary options
		 * to manage folders to be
		 * skipped, set with value
		 * read from repository options
		 * file.
		 */

		$tmp_options = array(
			'tmp-skip-folders' =>
				$type_options_file_contents
		);

		/*
		 * Use standard function to
		 * handle what we read.
		 */
		vipgoci_option_skip_folder_handle(
			$tmp_options,
			'tmp-skip-folders'
		);

		/*
		 * Remove any possible empty
		 * lines, etc.
		 */
		$tmp_options['tmp-skip-folders'] = array_filter(
			$tmp_options['tmp-skip-folders'],
			'strlen'
		);

		/*
		 * If the parsed result is
		 * an array, and it is not empty,
		 * join it with any existing
		 * folders.
		 */
		if (
			( is_array(
				$tmp_options[
					'tmp-skip-folders'
				]
			) )
			&&
			( count(
				$tmp_options[
					'tmp-skip-folders'
				]
			) > 0 )
		) {

			$log_msg = 'Merging folders found in configuration ' .
					'file with possible other folders';

			$options[ $scan_type . '-skip-folders' ] = array_merge(
				$options[ $scan_type . '-skip-folders'],
				$tmp_options['tmp-skip-folders']
			);
		}

		else {
			$log_msg = 'No folders found to merge with options, skipping';
		}

		vipgoci_log(
			$log_msg,
			array(
				'scan_type'
					=> $scan_type,

				'file_options_' . $scan_type . '_skip_folders' =>
					$tmp_options['tmp-skip-folders'],

				'current_' . $scan_type . '_skip_folders' =>
					$options[ $scan_type . '-skip-folders'],
			)
		);

		unset( $tmp_options );
	}
}

/*
 * Read options from environmental variables
 * as specified on the command-line. Does not overwrite
 * options already specified on the command-line even if
 * the environment specifies them.
 */
function vipgoci_options_read_env(
	&$options,
	$options_recognized
) {

	if ( ! isset( $options['env-options'] ) ) {
		return;
	}

	vipgoci_log(
		'Attempting to read configuration from environmental variables',
		array(
			'env-options' => $options['env-options'],
			'options-recognized' => $options_recognized,
		)
	);

	$options_configured = array();

	foreach(
		$options['env-options'] as $option_unparsed
	) {
		/*
		 * Try to parse option from the command-line
		 * to figure out which environmental variable to use
		 * for which option.
		 */
		$option_parsed = explode(
			'=',
			$option_unparsed,
			2 // Max one '='; any extra will be preserved in the option-env-var
		);

		if ( count( $option_parsed ) !== 2 ) {
			vipgoci_log(
				'Invalid option provided via environment, skipping',
				array(
					'option_parsed'	=> $option_parsed,
				)
			);

			continue;
		}

		$option_name = $option_parsed[0];
		$option_env_var = $option_parsed[1];
		unset( $option_parsed );

		/*
		 * If option-name or env-var is too short, skip.
		 */
		if (
			( strlen(
				$option_name
			) < 1 )
			||
			( strlen(
				$option_env_var
			) < 1 )
		) {
			vipgoci_log(
				'Skipping option name/var-name from environment as it is too short',
				array(
					'option_name' =>
						$option_name,

					'option_env_var' =>
						$option_env_var,
				)
			);

			continue;
		}

		/*
		 * If not a recognized option, skip.
		 */
		if ( ! in_array(
			$option_name . ':',
			$options_recognized,
			true
		) ) {
			vipgoci_log(
				'Skipping option from environment as it is not recognized',
				array(
					'option_name' =>
						$option_name,

					'option_env_var' =>
						$option_env_var,

					'options_recognized' =>
						$options_recognized,
				)
			);

			continue;
		}

		/*
		 * Check for invalid options.
		 */
		if ( in_array(
			$option_name,
			array( 'env-options' )
		) ) {
			vipgoci_log(
				'Skipping option from environment as it is not allowed',
				array(
					'option_name' =>
						$option_name,

					'option_env_var' =>
						$option_env_var,
				)
			);

			continue;
		}

		/*
		 * Already configured and not
		 * configured by us in an earlier
		 * round? Then skip.
		 */
		if (
			( isset(
				$options[
					$option_name
				]
			) )
			&&
			( ! isset(
				$options_configured[
					$option_name
				]
			) )
		) {
			vipgoci_log(
				'Skipping option from environment as it is already configured',
				array(
					'option_name' =>
						$option_name,

					'option_env_var'
						=> $option_env_var,
				)
			);

			continue;
		}

		$option_env_var_value = getenv(
			$option_env_var,
			true
		);

		if ( false === $option_env_var_value ) {
			vipgoci_log(
				'Skipping option from environment as the variable is not defined',
				array(
					'option_name' =>
						$option_name,

					'option_env_var' =>
						$option_env_var,

					'option_env_var_value' =>
						$option_env_var_value,
				)
			);

			continue;
		}

		/*
		 * All checks passed.
		 * Actually set value.
		 */
		$options[
			$option_name
		] = $option_env_var_value;

		$options_configured[
			$option_name
		] = $option_env_var_value;
	}

	vipgoci_log(
		'Read and set options from environment',
		array(
			/*
			 * Note: Do not print out the actual
			 * values, so not to expose them in logs.
			 */
			'options_configured_keys' => array_keys(
				$options_configured
			)
		)
	);
}

/*
 * Replace any sensitive option value from
 * a given option array with something
 * that can be printed safely and return
 * a new array.
 */
function vipgoci_options_sensitive_clean(
	$options,
	$options_add_to_sensitive = array()
) {
	static $sensitive_options = array(
	);

	if ( ! empty ( $options_add_to_sensitive ) ) {
		$sensitive_options = array_merge(
			$sensitive_options,
			$options_add_to_sensitive
		);

		return;
	}

	$options_clean = $options;

	foreach(
		$options_clean as
			$option_key => $option_value
	) {
		if ( ! in_array(
			$option_key,
			$sensitive_options,
			true
		) ) {
			continue;
		}

		if ( ! isset( $options_clean[ $option_key ] ) ) {
			continue;
		}

		$options_clean[ $option_key ] = '***';
	}

	return $options_clean;
}

/*
 * Handle boolean parameters given on the command-line.
 *
 * Will set a default value for the given parameter name,
 * if no value is set. Will then proceed to check if the
 * value given is a boolean and will then convert the value
 * to a boolean-type, and finally set it in $options.
 */

function vipgoci_option_bool_handle(
	&$options,
	$parameter_name,
	$default_value
) {

	/* If no default is given, set it */
	if ( ! isset( $options[ $parameter_name ] ) ) {
		$options[ $parameter_name ] = $default_value;
	}

	/* Check if the gien value is a false or true */
	if (
		( $options[ $parameter_name ] !== 'false' ) &&
		( $options[ $parameter_name ] !== 'true' )
	) {
		vipgoci_sysexit(
			'Parameter --' . $parameter_name .
				' has to be either false or true',
			array(),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}

	/* Convert the given value to a boolean type value */
	if ( $options[ $parameter_name ] === 'false' ) {
		$options[ $parameter_name ] = false;
	}

	else {
		$options[ $parameter_name ] = true;
	}
}

/*
 * Handle integer parameters given on the command-line.
 *
 * Will set a default value for the given parameter name,
 * if no value is set. Will then proceed to check if the
 * value given is an integer-value, then forcibly convert
 * it to integer-value to make sure it is of that type,
 * then check if it is in a list of allowable values.
 * If any of these fail, it will exit the program with an error.
 */

function vipgoci_option_integer_handle(
	&$options,
	$parameter_name,
	$default_value,
	$allowed_values = null
) {
	/* If no value is set, set the default value */
	if ( ! isset( $options[ $parameter_name ] ) ) {
		$options[ $parameter_name ] = $default_value;
	}

	/* Make sure it is a numeric */
	if ( ! is_numeric( $options[ $parameter_name ] ) ) {
		vipgoci_sysexit(
			'Usage: Parameter --' . $parameter_name . ' is not ' .
				'an integer-value.',
			array(),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}

	/* Forcibly convert to integer-value */
	$options[ $parameter_name ] =
		(int) $options[ $parameter_name ];

	/*
	 * Check if value is in range
	 */

	if (
		( null !== $allowed_values )
		&&
		( ! in_array(
			$options[ $parameter_name ],
			$allowed_values,
			true
		) )
	) {
		vipgoci_sysexit(
			'Parameter --' . $parameter_name . ' is out ' .
				'of allowable range.',
			array(),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}

}

/*
 * Handle array-like option parameters given on the command line
 *
 * Parses the parameter, turns it into a real array,
 * makes sure forbidden values are not contained in it.
 * Does not return the result, but rather alters
 * $options directly.
 *
 * Allows for array-item separator to be specified.
 */
function vipgoci_option_array_handle(
	&$options,
	$option_name,
	$default_value = array(),
	$forbidden_value = null,
	$array_separator = ',',
	$strlower_option_value = true
) {
	if ( ! isset( $options[ $option_name ] ) ) {
		$options[ $option_name ] = $default_value;
	}

	/*
	 * Detect when option is already
	 * array, which can happen when
	 * an option is specified twice.
	 */
	else if ( is_array(
		$options[ $option_name ]
	) ) {
		vipgoci_sysexit(
			'Parameter --' .
			$option_name . ' ' .
			'is an array -- ' .
			'should be a string. ' .
			'Is it specified twice?',
			array(),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}

	else {
		if ( true === $strlower_option_value ) {
			$options[ $option_name ] = strtolower(
				$options[ $option_name ]
			);
		}

		$options[ $option_name ] = explode(
			$array_separator,
			$options[ $option_name ]
		);

		if ( ! empty( $forbidden_value ) ) {
			if ( in_array(
				$forbidden_value,
				$options[ $option_name ],
				true
			) ) {
				vipgoci_sysexit(
					'Parameter --' .
						$option_name . ' ' .
						'can not contain \'' .
						$forbidden_value .
						'\' as one of ' .
						'the values',
					array(),
					VIPGOCI_EXIT_USAGE_ERROR
				);
			}
		}
	}
}


/*
 * Handle parameter that expects the value
 * of it to be a file. Allow a default value
 * to be set if none is set.
 */

function vipgoci_option_file_handle(
	&$options,
	$option_name,
	$default_value = null
) {

	if (
		( ! isset( $options[ $option_name ] ) ) &&
		( null !== $default_value )
	) {
		$options[ $option_name ] = $default_value;
	}

	else if (
		( ! isset( $options[ $option_name ] ) ) ||
		( ! is_file( $options[ $option_name ] ) )
	) {
		vipgoci_sysexit(
			'Parameter --' . $option_name .
				' has to be a valid path',
			array(),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}
}

/**
 * Handle parameter that we expect to be a URL.
 *
 * If the parameter is not empty, and is not really
 * a URL (not starting with http:// or https://),
 * exit with error. If empty, sets a default.
 *
 * @codeCoverageIgnore
 */

function vipgoci_option_url_handle(
	&$options,
	$option_name,
	$default_value
) {
	/*
	 * If not set, assume default value.
	 */
	if (
		( ! isset( $options[ $option_name ] ) ) ||
		( empty( $options[ $option_name ] ) )
	) {
		$options[ $option_name ] = $default_value;
	}

	/*
	 * If not default value, check if it looks like an URL,
	 * and if so, use it, but if not, exit with error.
	 */
	if ( $default_value !== $options[ $option_name ] ) {
		$options[ $option_name ] = trim(
			$options[ $option_name ]
		);

		if (
			( 0 !== strpos(
				$options[ $option_name ],
				'http://'
			) )
			&&
			( 0 !== strpos(
				$options[ $option_name ],
				'https://'
			) )
		) {
			vipgoci_sysexit(
				'Option --' . $option_name . ' should ' .
					'be an URL',
				array(
				),
				VIPGOCI_EXIT_USAGE_ERROR
			);
		}
	}

	if (
		( true === $options['enforce-https-urls'] ) &&
		( 0 !== strpos( $options[ $option_name ], 'https://' ) )
	) {
		vipgoci_sysexit(
			'Option --' . $option_name . ' should ' .
				'be an URL starting with https://',
			array(
			),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}
}

/*
 * Handle parameter that we expect to contain teams,
 * either as an ID (numeric) or a string (slug).
 *
 * Will check if the teams are valid, removing invalid ones,
 * transforming strings into IDs, and reconstruct the option
 * afterwards.
 */

function vipgoci_option_teams_handle(
	&$options,
	$option_name
) {
	if (
		( ! isset( $options[ $option_name ] ) ) ||
		( ! is_array( $options[ $option_name ] ) )
	) {
		$options[ $option_name ] = array();
	}

	if ( empty( $options[ $option_name ] ) ) {
		return;
	}

	$options[ $option_name ] = array_map(
		'vipgoci_sanitize_string',
		$options[ $option_name ]
	);


	$teams_info = vipgoci_github_org_teams_get(
		$options['token'],
		$options['repo-owner'],
		null,
		'slug'
	);

	foreach(
		$options[ $option_name ] as
			$team_id_key =>	$team_id_value
	) {
		$team_id_value_original = $team_id_value;

		/*
		 * If a string, transform team_id_value into integer ID
		 * for team.
		 */
		if (
			( ! is_numeric( $team_id_value ) ) &&
			( ! empty( $teams_info[ $team_id_value ] ) )
		) {
			$team_id_value = $teams_info[ $team_id_value ][0]->id;
		}

		/*
		 * If $team_id_value is a numeric,
		 * the team exists, so put in
		 * the integer-value in the options.
		 */
		if ( is_numeric( $team_id_value ) ) {
			$options
				[ $option_name ]
				[ $team_id_key ] = (int) $team_id_value;
		}

		/*
		 * Something failed; we might have
		 * failed to transform $team_id_value into
		 * a numeric representation (ID) and/or
		 * it may have been invalid, so remove
		 * it from the options array.
		 */

		else {
			vipgoci_log(
				'Invalid team ID found in ' .
				'--' . $option_name .
				' parameter; ignoring it.',
				array(
					'team_id' => $team_id_value,
					'team_id_original' => $team_id_value_original,
				)
			);

			unset(
				$options
					[ $option_name ]
					[ $team_id_key ]
			);
		}
	}

	/* Reconstruct array from the previous one */
	$options[ $option_name ] =
		array_values( array_unique(
			$options[ $option_name ]
		) );

	unset( $teams_info );
	unset( $team_id_key );
	unset( $team_id_value );
	unset( $team_id_value_original );
}


/*
 * Handles --skip-folder like parameters;
 * they are mostly handled as arrays, but
 * in addition we remove certain strings
 * from the beginning and end of each array
 * element.
*/
function vipgoci_option_skip_folder_handle(
	&$options,
	$option_name
) {
	vipgoci_option_array_handle(
		$options,
		$option_name,
		array(),
		null,
		',',
		false // no strtolower
	);

	/*
	 * Remove "/" from the beginning
	 * and end of each element, as they
	 * should be treated as relative paths.
	 */
	$options[
		$option_name
	] = array_map(
		function( $skip_folder_item ) {
			return trim(
				$skip_folder_item,
				'/'
			);
		},
		$options[
			$option_name
		]
	);
}

/*
 * Process options for generic support options parameters.
 */
function vipgoci_option_generic_support_comments_process(
	&$options,
	$option_name,
	$type = 'string',
	$strlower_option_value = true
) {
	if ( ! isset(
		$options[
			$option_name
		]
	) ) {

		if ( 'string' === $type ) {
			$default_value = null;
		}

		else if ( 'array' === $type ) {
			$default_value = array();
		}

		else if ( 'boolean' === $type ) {
			$default_value = false;
		}

		$options[
			$option_name
		] = $default_value;

		return;
	}


	if ( is_array(
			$options[
				$option_name
			]
	) ) {
		vipgoci_sysexit(
			'Option --' . $option_name . ' is an array, but should not be. Maybe specified twice?',
			array(
				'option_name'	=> $option_name,
				'option_value'	=> $options[ $option_name ],
			)
		);
	}


	$options[ $option_name ] =
		trim(
			$options[ $option_name ]
		);

	/*
	 * Boolean is always converted to lower case
	 */
	if ( 'boolean' === $type ) {
		$strlower_option_value = true;
	}

	vipgoci_option_array_handle(
		$options,
		$option_name,
		array(),
		null,
		'|||',
		$strlower_option_value
	);

	$original_option_value = $options[ $option_name ];
	$options[ $option_name ] = array();

	foreach(
		array_values(
			$original_option_value
		) as $tmp_string_option
	) {

		$tmp_string_option_arr =
			explode(
				':',
				$tmp_string_option,
				2 // Max two items in array, ID and value
			);

		$tmp_key = $tmp_string_option_arr[0];
		$tmp_value = $tmp_string_option_arr[1];

		if ( 'boolean' === $type ) {
			if ( ( 'true' === $tmp_value ) || ( 'false' === $tmp_value ) ) {
				$tmp_value = vipgoci_convert_string_to_type(
					$tmp_value
				);
			}

			else {
				vipgoci_sysexit(
					'Unsupported option value provided to options parameter',
					array(
						'option_name'		=> $option_name,
						'option_value'		=> $original_option_value,
						'option_value_problem'	=> $tmp_value,
					)
				);
			}

			$options[ $option_name ][ $tmp_key ] =
				$tmp_value;
		}

		else if ('string' === $type ) {
			$options[ $option_name ][ $tmp_key ] =
				$tmp_value;
		}

		else if ( 'array' === $type ) {
			if ( empty( $tmp_value ) ) {
				$options[ $option_name ][ $tmp_key ] = array();
			}

			else {
				$options[ $option_name ][ $tmp_key ] = explode(
					',',
					$tmp_value
				);
			}
		}
	}
}

/*
 * Process options for generic support comments matching.
 *
 * Syntax of this option is:
 * --post-generic-pr-support-comments-repo-meta-match="0:mykey=myvalue,foo=bar|||1:mykey2=myvalue3,aaa=bbb"
 */
function vipgoci_option_generic_support_comments_match(
	&$options,
	$option_name
) {
	vipgoci_option_array_handle(
		$options,
		$option_name,
		array(),
		null,
		'|||'
	);

	$raw_option_value = $options[ $option_name ];

	$processed_option_value = array();

	/*
	 * Loop through possible matches, separated
	 * by "|||"
	 */
	for(
		$i = 0;
		$i < count(
			$raw_option_value
		);
		$i++
	) {
		/*
		 * Split each possible match by ":" --
		 * should originally a string be something like: "0:key=value",
		 * where the number is ID of the match.
		 */

		$match_with_id_arr = explode(
			':',
			$raw_option_value[ $i ],
			2 // Max one ':'; any extra will be preserve
		);

		/* Should be only two items in the array */
		if ( count( $match_with_id_arr ) != 2 ) {
			continue;
		}

		$processed_option_value[
			$match_with_id_arr[0]
		] = array();

		/*
		 * Within each match, split by
		 * "," -- which is an AND.
		 */
		$match_key_values_arr = explode(
			',',
			$match_with_id_arr[1]
		);

		foreach( $match_key_values_arr as $match_key_value_item ) {
			$match_key_value_item_arr = explode(
				'=',
				$match_key_value_item,
				2
			);

			if ( ! isset(
				$processed_option_value[
					$match_with_id_arr[0]
				][
					$match_key_value_item_arr[0]
				]
			) ) {
				$processed_option_value[
					$match_with_id_arr[0]
				][
					$match_key_value_item_arr[0]
				] = array();
			}

			if ( ! isset(
				$match_key_value_item_arr[1]
			) ) {
				vipgoci_sysexit(
					'Parameter ' .
						'--' . $option_name . ' ' .
						'is illegally constructed, ' .
						'it is missing a value',
					array(
						'match_with_id_arr'
							=> $match_with_id_arr[0],

						'match_key_value_item_arr'
							=> $match_key_value_item_arr[0],
					)
				);
			}


			$processed_option_value[
				$match_with_id_arr[0]
			][
				$match_key_value_item_arr[0]
			][] = vipgoci_convert_string_to_type(
				$match_key_value_item_arr[1]
			);
		}
	}

	$options[
		$option_name
	] = $processed_option_value;
}

/*
 * Process --phpcs-runtime-set like parameters -- 
 * expected to be an array of values.
 */

function vipgoci_option_phpcs_runtime_set(
	&$options,
	$option_name
) {
	if ( empty( $options[ $option_name ] ) ) {
		$options[ $option_name ] = array();

		return;
	}

	vipgoci_option_array_handle(
		$options,
		$option_name,
		array(),
		array(),
		',',
		false
	);

	foreach(
		$options[ $option_name ] as
			$tmp_runtime_key =>
				$tmp_runtime_set
	) {
		$options
			[ $option_name ]
			[ $tmp_runtime_key ] =
			explode( ' ', $tmp_runtime_set, 2 );

		/*
		 * Catch any abnormalities with
	 	 * the --phpcs-runtime-set like parameter, such
		 * as key/value being missing, or set to empty.
		 */

		if (
			( count(
				$options
				[ $option_name ]
				[ $tmp_runtime_key ]
			) < 2 )
			||
			( empty( $options
				[ $option_name ]
				[ $tmp_runtime_key ]
				[0]
			) )
			||
			( empty( $options
				[ $option_name ]
				[ $tmp_runtime_key ]
				[1]
			) )
		) {
			vipgoci_sysexit(
				'--' . $option_name . ' is incorrectly formed; it should ' . PHP_EOL .
				'be a comma separated string of keys and values.' . PHP_EOL .
				'For instance: --' . $option_name . '="foo1 bar1,foo2 bar2"',
				array(
					$options[ $option_name ]
				),
				VIPGOCI_EXIT_USAGE_ERROR
			);
		}
	}
}


<?php

/**
 * Determine exit status.
 *
 * If any 'error'-type issues were submitted to
 * GitHub we announce a failure to our parent-process
 * by returning with a non-zero exit-code.
 *
 * If we only submitted warnings, we do not announce failure.
 */

function vipgoci_exit_status( $results ) {
	foreach (
		array_keys(
			$results['stats']
		)
		as $stats_type
	) {
		if (
			! isset( $results['stats'][ $stats_type ] ) ||
			null === $results['stats'][ $stats_type ]
		) {
			/* In case the type of scan was not performed, skip */
			continue;
		}

		foreach (
			array_keys(
				$results['stats'][ $stats_type ]
			)
			as $pr_number
		) {
			if (
				0 !== $results['stats']
					[ $stats_type ]
					[ $pr_number ]
					['error']
			) {
				// Some errors were found, return non-zero
				return VIPGOCI_EXIT_CODE_ISSUES;
			}
		}

	}

	return 0;
}


/**
 * Main invocation function.
 *
 * @codeCoverageIgnore
 */
function vipgoci_run() {
	global $argv;
	global $vipgoci_debug_level;

	/*
	 * Clear the internal
	 * cache before doing anything.
	 */
	vipgoci_cache(
		VIPGOCI_CACHE_CLEAR
	);

	$hashes_oauth_arguments =
		array(
			'hashes-oauth-token',
			'hashes-oauth-token-secret',
			'hashes-oauth-consumer-key',
			'hashes-oauth-consumer-secret'
		);


	vipgoci_log(
		'Initializing...',
		array(
			'debug_info' => array(
				'vipgoci_version'	=> VIPGOCI_VERSION,
				'php_version'		=> phpversion(),
				'hostname'		=> gethostname(),
				'php_uname'		=> php_uname(),
				'git_version'		=> vipgoci_git_version(),
			)
		)
	);

	/*
	 * Refuse to run as root.
	 */
	if ( 0 === posix_getuid() ) {
		vipgoci_sysexit(
			'Will not run as root. Please run as non-privileged user.',
			array(),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}

	/*
	 * Require PHP version 7.3 or later.
	 */
	if ( version_compare(
		phpversion(),
		'7.3.0'
	) < 0 ) {
		vipgoci_sysexit(
			'Error: PHP 7.3 is required as a minimum.',
			array(),
			VIPGOCI_EXIT_SYSTEM_PROBLEM
		);
	}

	/*
	 * Require minimum git version
	 */
	if ( version_compare(
		vipgoci_git_version(),
		VIPGOCI_GIT_VERSION_MINIMUM
	) < 0 ) {
		vipgoci_sysexit(
			'Error: git version ' . VIPGOCI_GIT_VERSION_MINIMUM . ' is required as a minimum.',
			array(),
			VIPGOCI_EXIT_SYSTEM_PROBLEM
		);
	}

	/*
	 * Set how to deal with errors:
	 * Report all errors, and display them.
	 */
	ini_set( 'error_log', '' );

	error_reporting( E_ALL );
	ini_set( 'display_errors', 'on' );


	// Set with a temp value for now, user value set later
	$vipgoci_debug_level = 0;

	$startup_time = time();

	$options_recognized =
		array(
			'env-options:',
			'repo-owner:',
			'repo-name:',
			'commit:',
			'token:',
			'enforce-https-urls:',
			'skip-draft-prs:',
			'results-comments-sort:',
			'review-comments-max:',
			'review-comments-total-max:',
			'review-comments-ignore:',
			'review-comments-include-severity:',
			'dismiss-stale-reviews:',
			'dismissed-reviews-repost-comments:',
			'dismissed-reviews-exclude-reviews-from-team:',
			'branches-ignore:',
			'output:',
			'informational-url:',
			'post-generic-pr-support-comments:',
			'post-generic-pr-support-comments-on-drafts:',
			'post-generic-pr-support-comments-string:',
			'post-generic-pr-support-comments-skip-if-label-exists:',
			'post-generic-pr-support-comments-branches:',
			'post-generic-pr-support-comments-repo-meta-match:',
			'set-support-level-label:',
			'set-support-level-label-prefix:',
			'set-support-level-field:',
			'repo-meta-api-base-url:',
			'repo-meta-api-user-id:',
			'repo-meta-api-access-token:',
			'phpcs:',
			'phpcs-path:',
			'phpcs-standard:',
			'phpcs-severity:',
			'phpcs-sniffs-include:',
			'phpcs-sniffs-exclude:',
			'phpcs-runtime-set:',
			'phpcs-skip-scanning-via-labels-allowed:',
			'phpcs-skip-folders:',
			'phpcs-skip-folders-in-repo-options-file:',
			'repo-options:',
			'repo-options-allowed:',
			'hashes-api:',
			'hashes-api-url:',
			'hashes-oauth-token:',
			'hashes-oauth-token-secret:',
			'hashes-oauth-consumer-key:',
			'hashes-oauth-consumer-secret:',
			'irc-api-url:',
			'irc-api-token:',
			'irc-api-bot:',
			'irc-api-room:',
			'pixel-api-url:',
			'pixel-api-groupprefix:',
			'php-path:',
			'local-git-repo:',
			'lint:',
			'lint-skip-folders:',
			'lint-skip-folders-in-repo-options-file:',
			'svg-checks:',
			'svg-scanner-path:',
			'autoapprove:',
			'autoapprove-filetypes:',
			'autoapprove-label:',
			'autoapprove-php-nonfunctional-changes:',
			'help',
			'debug-level:',
		);

	/*
	 * Try to read options from command-line parameters.
	 */

	$options = getopt(
		null,
		$options_recognized
	);

	/*
	 * Handle --enforce-https-urls absolutely first,
	 * as that is used in processing parameters expecting
	 * URLs.
	 */
	vipgoci_option_bool_handle( $options, 'enforce-https-urls', 'true' );


	/*
	 * Try to read configuration from
	 * environmental variables.
	 */

	vipgoci_option_array_handle(
		$options,
		'env-options',
		array(),
		null,
		',',
		false
	);

	vipgoci_options_read_env(
		$options,
		$options_recognized
	);

	// Validate args
	if (
		! isset( $options['repo-owner'] ) ||
		! isset( $options['repo-name'] ) ||
		! isset( $options['commit'] ) ||
		! isset( $options['token'] ) ||
		! isset( $options['local-git-repo']) ||
		isset( $options['help'] )
	) {
		print 'Usage: ' . $argv[0] . PHP_EOL .
			"\t" . 'Options --repo-owner, --repo-name, --commit, --token, --local-git-repo, --phpcs-path are ' . PHP_EOL .
			"\t" . 'mandatory, while others are optional.' . PHP_EOL .
			PHP_EOL .
			"\t" . 'Note that if option --autoapprove is specified, --autoapprove-label needs to' . PHP_EOL .
			"\t" . 'be specified as well.' . PHP_EOL .
			PHP_EOL .
			"\t" . '--repo-owner=STRING            Specify repository owner, can be an organization' . PHP_EOL .
			"\t" . '--repo-name=STRING             Specify name of the repository' . PHP_EOL .
			"\t" . '--commit=STRING                Specify the exact commit to scan (SHA)' . PHP_EOL .
			"\t" . '--token=STRING                 The access-token to use to communicate with GitHub' . PHP_EOL .
			PHP_EOL .
			"\t" . '--enforce-https-urls=BOOL      Check and enforce that all URLs provided to parameters ' .PHP_EOL .
			"\t" . '                               that expect a URL are HTTPS and not HTTP. Default is true.' . PHP_EOL .
			PHP_EOL .
			"\t" . '--skip-draft-prs=BOOL          If true, skip scanning of all Pull-Requests that are in draft mode.' . PHP_EOL .
			"\t" . '                               Default is false.' . PHP_EOL .
			PHP_EOL .
			"\t" . '--results-comments-sort=BOOL     Sort issues found according to severity, from high ' . PHP_EOL .
			"\t" . '                               to low, before submitting to GitHub. Not sorted by default.' . PHP_EOL .
			"\t" . '--review-comments-max=NUMBER   Maximum number of inline comments to submit' . PHP_EOL .
			"\t" . '                               to GitHub in one review. If the number of ' . PHP_EOL .
			"\t" . '                               comments exceed this number, additional reviews ' . PHP_EOL .
			"\t" . '                               will be submitted.' . PHP_EOL .
			"\t" . '--review-comments-total-max=NUMBER  Maximum number of inline comments submitted to'   . PHP_EOL .
			"\t" . '                                    a single Pull-Request by the program -- includes' . PHP_EOL .
			"\t" . '                                    comments from previous executions. A value of ' . PHP_EOL .
			"\t" . '                                    \'0\' indicates no limit.' . PHP_EOL .
			"\t" . '--review-comments-ignore=STRING     Specify which result comments to ignore' . PHP_EOL .
			"\t" . '                                    -- e.g. useful if one type of message is to be ignored' . PHP_EOL .
			"\t" . '                                    rather than a whole PHPCS sniff. Should be a ' . PHP_EOL .
			"\t" . '                                    whole string with items separated by \"|||\".' . PHP_EOL .
			"\t" . '--review-comments-include-severity=BOOL  Whether to include severity level with' . PHP_EOL .
			"\t" . '                                         each review comment. Default is false.' . PHP_EOL .
			PHP_EOL .
			"\t" . '--dismiss-stale-reviews=BOOL   Dismiss any reviews associated with Pull-Requests ' . PHP_EOL .
			"\t" . '                               that we process which have no active comments. ' . PHP_EOL .
			"\t" . '                               The Pull-Requests we process are those associated ' . PHP_EOL .
			"\t" . '                               with the commit specified.' . PHP_EOL .
			"\t" . '--dismissed-reviews-repost-comments=BOOL  When avoiding double-posting comments,' . PHP_EOL .
			"\t" . '                                          do not take into consideration comments ' . PHP_EOL .
			"\t" . '                                          posted against reviews that have now been ' . PHP_EOL .
			"\t" . '                                          dismissed. Setting this to true entails ' . PHP_EOL .
			"\t" . '                                          that comments from dismissed reviews will ' . PHP_EOL .
			"\t" . '                                          be posted again, should the underlying issue ' . PHP_EOL .
			"\t" . '                                          be detected during the run.' . PHP_EOL .
			"\t" . '--dismissed-reviews-exclude-reviews-from-team=STRING  With this parameter set, ' . PHP_EOL .
			"\t" . '                                                      comments that are part of reviews ' . PHP_EOL .
			"\t" . '                                                      dismissed by members of the teams specified,  ' . PHP_EOL .
			"\t" . '                                                      would be taken into consideration when ' . PHP_EOL .
			"\t" . '                                                      avoiding double-posting; they would be ' . PHP_EOL .
			"\t" . '                                                      excluded. Note that this parameter ' . PHP_EOL .
			"\t" . '                                                      only works in conjunction with ' . PHP_EOL .
			PHP_EOL .
			"\t" . '                                                      --dismissed-reviews-repost-comments' . PHP_EOL .
			"\t" . '--informational-url=STRING     URL to documentation on what this bot does. Should ' . PHP_EOL .
			"\t" . '                               start with https:// or https:// ' . PHP_EOL .
			PHP_EOL .
			"\t" . '--post-generic-pr-support-comments=BOOL            Whether to post generic comment to Pull-Requests ' . PHP_EOL .
			"\t" . '                                                   with support-related information for users. Will ' . PHP_EOL .
			"\t" . '                                                   be posted only once per Pull-Request. ' . PHP_EOL .
			"\t" . '--post-generic-pr-support-comments-string=STRING   String to use when posting support-comment. ' . PHP_EOL .
			"\t" . '--post-generic-pr-support-comments-skip-if-label-exists=STRING  If the specified label exists on ' . PHP_EOL .
			"\t" . '                                                                the Pull-Request, do not post support ' . PHP_EOL .
			"\t" . '                                                                comment' . PHP_EOL .
			"\t" . '--post-generic-pr-support-comments-branches=ARRAY  Only post support-comments to Pull-Requests ' . PHP_EOL .
			"\t" . '                                                   with the target branches specified. The ' . PHP_EOL .
			"\t" . '                                                   parameter can be a string with one value, or ' . PHP_EOL .
			"\t" . '                                                   comma separated. A single "any" value will ' . PHP_EOL .
			"\t" . '                                                   cause the message to be posted to any ' . PHP_EOL .
			"\t" . '                                                   branch.' . PHP_EOL .
			"\t" . '--post-generic-pr-support-comments-repo-meta-match=ARRAY   Only post generic support ' . PHP_EOL .
			"\t" . '                                                           messages when data from repo-meta API' . PHP_EOL .
			"\t" . '                                                           matches the criteria specified here. ' . PHP_EOL .
			"\t" . '                                                           See README.md for usage. ' . PHP_EOL .
			PHP_EOL .
			"\t" . '--set-support-level-label=BOOL       Whether to attach support level labels to Pull-Requests. ' . PHP_EOL .
			"\t" . '                                     Will fetch information on support levels from repo-meta API. ' . PHP_EOL .
			"\t" . '--set-support-level-label-prefix=STRING    Prefix to use for support level labels. Should be longer than five letters.' . PHP_EOL .
			"\t" . '--set-support-level-field=STRING     Name for field in responses from repo-meta API which we use to extract support level. ' . PHP_EOL .
			"\t" . '--repo-meta-api-base-url=STRING      Base URL to repo-meta API, containing support level and other ' . PHP_EOL .
			"\t" . '                                     information. ' . PHP_EOL .
			"\t" . '--repo-meta-api-user-id=STRING       Authentication detail for the repo-meta API. ' . PHP_EOL .
			"\t" . '--repo-meta-api-access-token=STRING  Access token for the repo-meta API. ' . PHP_EOL .
			PHP_EOL .
			"\t" . '--phpcs=BOOL                   Whether to run PHPCS (true/false)' . PHP_EOL .
			"\t" . '--phpcs-path=FILE              Full path to PHPCS script' . PHP_EOL .
			"\t" . '--phpcs-standard=STRING        Specify which PHPCS standard to use' . PHP_EOL .
			"\t" . '--phpcs-severity=NUMBER        Specify severity for PHPCS' . PHP_EOL .
			"\t" . '--phpcs-sniffs-include=ARRAY   Specify which sniffs to include when PHPCS scanning, ' . PHP_EOL .
			"\t" . '                               should be an array with items separated by commas. ' . PHP_EOL .
			"\t" . '--phpcs-sniffs-exclude=ARRAY   Specify which sniffs to exclude from PHPCS scanning, ' . PHP_EOL .
			"\t" . '                               should be an array with items separated by commas. ' . PHP_EOL .
			"\t" . '--phpcs-runtime-set=STRING     Specify --runtime-set values passed on to PHPCS' . PHP_EOL .
			"\t" . '                               -- expected to be a comma-separated value string of ' . PHP_EOL .
			"\t" . '                               key-value pairs.' . PHP_EOL .
			"\t" . '                               For example: --phpcs-runtime-set="key1 value1,key2 value2"' . PHP_EOL .
			"\t" . '--phpcs-skip-scanning-via-labels-allowed=BOOL    Whether to allow users to skip ' . PHP_EOL .
			"\t" . '                                                 PHPCS scanning of Pull-Requests ' . PHP_EOL .
			"\t" . '                                                 via labels attached to them. ' . PHP_EOL .
			"\t" . '                                                 The labels should be named "skip-phpcs-scan".' . PHP_EOL .
			"\t" . '--phpcs-skip-folders=STRING    Specify folders relative to root of the git repository in which ' . PHP_EOL .
			"\t" . '                               files are not to be scanned using PHPCS. Values are comma' . PHP_EOL .
			"\t" . '                               separated' . PHP_EOL .
			"\t" . '--phpcs-skip-folders-in-repo-options-file=BOOL   Allows folders that are not to be PHPCS ' . PHP_EOL .
			"\t" . '                                                 scanned to be specified in file in root of ' . PHP_EOL .
			"\t" . '                                                 repository (.vipgoci_phpcs_skip_folders).' . PHP_EOL .
			"\t" . '                                                 Folders should be separated by newlines.' . PHP_EOL .
			PHP_EOL .
			"\t" . '--autoapprove=BOOL             Whether to auto-approve Pull-Requests' . PHP_EOL .
			"\t" . '                               altering only files of certain types or ' . PHP_EOL .
			"\t" . '                               already approved files. ' . PHP_EOL .
			"\t" . '--autoapprove-filetypes=STRING Specify what file-types can be auto-' . PHP_EOL .
			"\t" . '                               approved. PHP files cannot be specified.' . PHP_EOL .
			"\t" . '--autoapprove-php-nonfunctional-changes=BOOL    For autoapprovals, also consider ' . PHP_EOL .
			"\t" . '                                                PHP files approved that contain ' . PHP_EOL .
			"\t" . '                                                non-functional changes, such as  ' . PHP_EOL .
			"\t" . '                                                whitespacing and comments alterations. ' . PHP_EOL .
			"\t" . '--autoapprove-label=STRING     String to use for labels when auto-approving' . PHP_EOL .
			"\t" . '--php-path=FILE                Full path to PHP, if not specified the' . PHP_EOL .
			"\t" . '                               default in $PATH will be used instead' . PHP_EOL .
			"\t" . '--svg-checks=BOOL              Enable or disable SVG checks, both' . PHP_EOL .
			"\t" . '                               auto-approval of SVG files and problem' . PHP_EOL .
			"\t" . '                               checking of these files. Note that if' . PHP_EOL .
			"\t" . '                               auto-approvals are turned off globally, no' . PHP_EOL .
			"\t" . '                               auto-approval is performed for SVG files.' . PHP_EOL .
			"\t" . '--svg-scanner-path=FILE        Path to SVG scanning tool. Should return' . PHP_EOL .
			"\t" . '                               similar output as PHPCS. ' . PHP_EOL .
			"\t" . '--hashes-api=BOOL              Whether to do hashes-to-hashes API verfication ' . PHP_EOL .
			"\t" . '                               with individual PHP files found to be altered ' . PHP_EOL .
			"\t" . '                               in the branch specified' . PHP_EOL .
			"\t" . '--hashes-api-url=STRING        URL to hashes-to-hashes HTTP API root' . PHP_EOL .
			"\t" . '                               -- note that it should not include any specific' . PHP_EOL .
			"\t" . '                               paths to individual parts of the API.' . PHP_EOL .
			PHP_EOL .
			"\t" . '--hashes-oauth-token=STRING,        --hashes-oauth-token-secret=STRING, ' . PHP_EOL .
			"\t" . '--hashes-oauth-consumer-key=STRING, --hashes-oauth-consumer-secret=STRING ' . PHP_EOL .
			"\t" . '                               OAuth 1.0 token, token secret, consumer key and ' . PHP_EOL .
			"\t" . '                               consumer secret needed for hashes-to-hashes HTTP requests' . PHP_EOL .
			"\t" . '                               All required for hashes-to-hashes requests.' . PHP_EOL .
			PHP_EOL .
			"\t" . '--irc-api-url=STRING           URL to IRC API to send alerts' . PHP_EOL .
			"\t" . '--irc-api-token=STRING         Access-token to use to communicate with the IRC ' . PHP_EOL .
			"\t" . '                               API' . PHP_EOL .
			"\t" . '--irc-api-bot=STRING           Name for the bot which is supposed to send the IRC ' .PHP_EOL .
			"\t" . '                               messages.' . PHP_EOL .
			"\t" . '--irc-api-room=STRING          Name for the chatroom to which the IRC messages should ' . PHP_EOL .
			"\t" . '                               be sent. ' . PHP_EOL .
			"\t" . '--branches-ignore=STRING,...   What branches to ignore -- useful to make sure' . PHP_EOL .
			"\t" . '                               some branches never get scanned. Separate branches' . PHP_EOL .
			"\t" . '                               with commas' . PHP_EOL .
			"\t" . '--local-git-repo=FILE          The local git repository to use for direct access to code' . PHP_EOL .
			"\t" . '--output=FILE                  Where to save output made from running PHPCS' . PHP_EOL .
			PHP_EOL .
			"\t" . '--lint=BOOL                    Whether to do PHP linting (true/false)' . PHP_EOL .
			"\t" . '--lint-skip-folders=STRING     Specify folders relative to root of the git repository in which ' . PHP_EOL .
			"\t" . '                               files should not be PHP linted. Values are comma separated.' . PHP_EOL .
			"\t" . '--lint-skip-folders-in-repo-options-file=BOOL   Allows folders that are not to be PHP Linted ' . PHP_EOL .
			"\t" . '                                                to be specified in file in root of repository ' . PHP_EOL .
			"\t" . '                                                (.vipgoci_lint_skip_folders). Folders should be ' . PHP_EOL .
			"\t" . '                                                separated by newlines.' . PHP_EOL .
			PHP_EOL .
			"\t" . '--help                         Displays this message' . PHP_EOL .
			"\t" . '--env-options=STRING           Specifies configuration options to be read from environmental ' . PHP_EOL .
			"\t" . '                               variables -- any variable can be specified. For instance, with ' . PHP_EOL .
			"\t" . '                               --env-options="repo-owner=U_ROWNER,output=U_FOUTPUT" specified ' . PHP_EOL .
			"\t" . '                               vip-go-ci will attempt to read the --repo-owner and --output ' . PHP_EOL .
			"\t" . '                               from the $U_ROWNER and $U_FOUTPUT environmental variables, ' . PHP_EOL .
			"\t" . '                               respectively. This is useful for environments, such as ' . PHP_EOL .
			"\t" . '                               TeamCity or GitHub Actions, where vital configuration. ' . PHP_EOL .
			"\t" . '                               are specified via environmental variables. ' . PHP_EOL .
			"\t" . '--repo-options=BOOL            Whether to allow configuring of --phpcs-severity ' . PHP_EOL .
			"\t" . '                               and --post-generic-pr-support-comments via options file' . PHP_EOL .
			"\t" . '                               ("' . VIPGOCI_OPTIONS_FILE_NAME . '") placed in root of the repository.' . PHP_EOL .
			"\t" . '--repo-options-allowed=STRING  Limits the options that can be set via repository options ' . PHP_EOL .
			"\t" . '                               configuration file. Values are separated by commas. ' . PHP_EOL .
			PHP_EOL .
			"\t" . '--debug-level=NUMBER           Specify minimum debug-level of messages to print' . PHP_EOL .
			"\t" . '                                -- higher number indicates more detailed debugging-messages.' . PHP_EOL .
			"\t" . '                               Default is zero' . PHP_EOL;

		exit( VIPGOCI_EXIT_USAGE_ERROR );
	}


	/*
	 * Process the --branches-ignore parameter,
	 * -- expected to be an array
	 */

	vipgoci_option_array_handle(
		$options,
		'branches-ignore',
		array()
	);


	/*
	 * Process --phpcs-path -- expected to
	 * be a file
	 */

	vipgoci_option_file_handle(
		$options,
		'phpcs-path',
		null
	);

	/*
	 * Process --phpcs-standard -- expected to be
	 * a string
	 */

	if ( empty( $options['phpcs-standard'] ) ) {
		$options['phpcs-standard'] = array(
			'WordPress-VIP-Go'
		);
	}

	else {
		vipgoci_option_array_handle(
			$options,
			'phpcs-standard',
			array(),
			array(),
			',',
			false
		);
	}


	/*
	 * Process --phpcs-sniffs-include and --phpcs-sniffs-exclude
	 * -- both expected to be an array.
	 */
	if ( empty( $options['phpcs-sniffs-include'] ) ) {
		$options['phpcs-sniffs-include'] = array();
	}

	else {
		vipgoci_option_array_handle(
			$options,
			'phpcs-sniffs-include',
			array(),
			array(),
			',',
			false
		);
	}

	if ( empty( $options['phpcs-sniffs-exclude'] ) ) {
		$options['phpcs-sniffs-exclude'] = array();
	}

	else {
		vipgoci_option_array_handle(
			$options,
			'phpcs-sniffs-exclude',
			array(),
			array(),
			',',
			false
		);
	}
	/*
	 * Process --phpcs-runtime-set -- expected to be an
	 * array of values.
	 */

	vipgoci_option_phpcs_runtime_set(
		$options,
		'phpcs-runtime-set'
	);

	vipgoci_option_skip_folder_handle(
		$options,
		'phpcs-skip-folders'
	);

	/*
	 * Process --review-comments-ignore -- expected
	 * to be an array (items separated by "|||").
	 * Then transform all of the messages to lower-case.
	 */

	vipgoci_option_array_handle(
		$options,
		'review-comments-ignore',
		array(),
		array(),
		'|||'
	);

	if ( ! empty( $options[ 'review-comments-ignore' ] ) ) {
		$options['review-comments-ignore'] = array_map(
			'strtolower',
			$options['review-comments-ignore']
		);
	}

	/*
	 * Process --dismissed-reviews-exclude-reviews-from-team,
	 * expected to be a string.
	 */

	vipgoci_option_array_handle(
		$options,
		'dismissed-reviews-exclude-reviews-from-team',
		array(),
		array(),
		','
	);


	/*
	 * Process --phpcs-severity -- expected to be
	 * an integer-value.
	 */

	vipgoci_option_integer_handle(
		$options,
		'phpcs-severity',
		1,
		array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 )
	);

	/*
	 * Process --php-path -- expected to be a file,
	 * default value is 'php' (then relies on $PATH)
	 */

	vipgoci_option_file_handle(
		$options,
		'php-path',
		'php'
	);


	/*
	 * Process --hashes-api -- expected to be a boolean.
	*/

	vipgoci_option_bool_handle( $options, 'hashes-api', 'false' );

	/*
	 * Process --svg-checks and --svg-scanner-path -- former expected
	 * to be a boolean, the latter a file-path.
	 */
	vipgoci_option_bool_handle( $options, 'svg-checks', 'false' );

	vipgoci_option_file_handle(
		$options,
		'svg-scanner-path',
		'invalid'
	);


	/*
	 * Process --hashes-api-url -- expected to
	 * be an URL to a webservice.
	 */

	if ( isset( $options['hashes-api-url'] ) ) {
		$options['hashes-api-url'] = trim(
			$options['hashes-api-url']
		);

		$options['hashes-api-url'] = rtrim(
			$options['hashes-api-url'],
			'/'
		);
	}

	/*
	 * Process hashes-oauth arguments
	 */

	foreach( $hashes_oauth_arguments as $tmp_key ) {
		if ( ! isset( $options[ $tmp_key ] ) ) {
			continue;
		}

		$options[ $tmp_key ] = rtrim( trim(
			$options[ $tmp_key ]
		) );
	}

	/*
	 * Ask for the hashes-oauth-* arguments
	 * to be considered as sensitive options
	 * when cleaning options for printing.
	 */
	vipgoci_options_sensitive_clean(
		null,
		$hashes_oauth_arguments
	);


	/*
	 * Handle --local-git-repo parameter
	 */

	$options['local-git-repo'] = rtrim(
		$options['local-git-repo'],
		'/'
	);


	vipgoci_gitrepo_ok(
		$options['commit'],
		$options['local-git-repo']
	);


	/*
	 * Handle optional --debug-level parameter
	 */

	vipgoci_option_integer_handle(
		$options,
		'debug-level',
		0,
		array( 0, 1, 2 )
	);

	// Set the value to global
	$vipgoci_debug_level = $options['debug-level'];

	/*
	 * Maximum number of inline comments posted to
	 * Github with one review -- from 5 to 100.
	 */

	vipgoci_option_integer_handle(
		$options,
		'review-comments-max',
		10,
		range( 5, 100, 1 )
	);

	/*
	 * Overall maximum number of inline comments
	 * posted to GitHub Pull-Request Reviews -- from
	 * 0 to 500. 0 means unlimited.
	 */

	vipgoci_option_integer_handle(
		$options,
		'review-comments-total-max',
		200,
		range( 0, 500, 1 )
	);

	/*
	 * Handle optional --informational-url --
	 * URL to information on what this bot does.
	 */

	vipgoci_option_url_handle(
		$options,
		'informational-url',
		null
	);


	/*
	 * Handle boolean parameters
	 */

	vipgoci_option_bool_handle( $options, 'skip-draft-prs', 'false' );

	vipgoci_option_bool_handle( $options, 'phpcs', 'true' );

	vipgoci_option_bool_handle( $options, 'phpcs-skip-folders-in-repo-options-file', 'false' );

	vipgoci_option_bool_handle( $options, 'phpcs-skip-scanning-via-labels-allowed', 'false' );

	vipgoci_option_bool_handle( $options, 'repo-options', 'false' );

	vipgoci_option_bool_handle( $options, 'lint', 'true' );

	vipgoci_option_bool_handle( $options, 'lint-skip-folders-in-repo-options-file', 'false' );

	vipgoci_option_bool_handle( $options, 'dismiss-stale-reviews', 'false' );

	vipgoci_option_bool_handle( $options, 'dismissed-reviews-repost-comments', 'true' );

	vipgoci_option_bool_handle( $options, 'results-comments-sort', 'false' );

	vipgoci_option_bool_handle( $options, 'review-comments-include-severity', 'false' );

	if (
		( false === $options['lint'] ) &&
		( false === $options['phpcs'] )
	) {
		vipgoci_sysexit(
			'Both --lint and --phpcs set to false, nothing to do!',
			array(),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}

	/* This variable is not configurable, is internal only */
	$options['phpcs-standard-file'] = false;

	/*
	 * This variable is not to be configurable on the command-line,
	 * only via options-file.
	 */
	$options['skip-execution'] = false;

	/*
	 * Should we auto-approve Pull-Requests when
	 * only altering certain file-types?
	 */

	vipgoci_option_bool_handle( $options, 'autoapprove', 'false' );

	vipgoci_option_bool_handle( $options, 'autoapprove-php-nonfunctional-changes', 'false' );

	vipgoci_option_array_handle(
		$options,
		'autoapprove-filetypes',
		array(),
		'php'
	);

	/*
	 * Handle parameters that enable posting of support-comments
	 * to Pull-Requests.
	 */

	vipgoci_option_bool_handle( $options, 'post-generic-pr-support-comments', 'false' );

	/*
	 * Submitting comments on draft PRs
	 */
	vipgoci_option_generic_support_comments_process(
		$options,
		'post-generic-pr-support-comments-on-drafts',
		'boolean'
	);

	vipgoci_option_generic_support_comments_process(
		$options,
		'post-generic-pr-support-comments-string',
		'string',
		false
	);

	vipgoci_option_generic_support_comments_process(
		$options,
		'post-generic-pr-support-comments-skip-if-label-exists',
		'string',
		false
	);

	vipgoci_option_generic_support_comments_process(
		$options,
		'post-generic-pr-support-comments-branches',
		'array',
		true
	);

	vipgoci_option_generic_support_comments_match(
		$options,
		'post-generic-pr-support-comments-repo-meta-match'
	);


	/*
	 * Handle option for setting support
	 * labels. Handle prefix and field too.
	 */

	vipgoci_option_bool_handle( $options, 'set-support-level-label', 'false' );

	if (
		( isset( $options['set-support-level-label-prefix'] ) ) &&
		( strlen( $options['set-support-level-label-prefix'] ) > 5 )
	) {
		$options['set-support-level-label-prefix'] = trim(
			$options['set-support-level-label-prefix']
		);
	}

	else {
		$options['set-support-level-label-prefix'] = null;
	}

	if (
		( isset( $options['set-support-level-field'] ) ) &&
		( strlen( $options['set-support-level-field'] ) > 1 )
	) {
		$options['set-support-level-field'] = trim(
			$options['set-support-level-field']
		);
	}

	else {
		$options['set-support-level-field'] = null;
	}

	/*
	 * Handle options for repo-meta API.
	 */
	if ( isset( $options['repo-meta-api-base-url' ] ) ) {
		vipgoci_option_url_handle( $options, 'repo-meta-api-base-url', null );
	}

	if ( isset( $options['repo-meta-api-user-id'] ) ) {
		vipgoci_option_integer_handle( $options, 'repo-meta-api-user-id', 0 );
	}

	else {
		$options['repo-meta-api-user-id'] = null;
	}

	if ( isset(
		$options['repo-meta-api-access-token']
	) ) {
		$options['repo-meta-api-access-token'] = trim(
			$options['repo-meta-api-access-token']
		);
	}

	else {
		$options['repo-meta-api-access-token'] = null;
	}

	vipgoci_options_sensitive_clean(
		null,
		array(
			'repo-meta-api-access-token'
		)
	);

	/*
	 * Handle IRC API parameters
	 */

	$irc_params_defined = 0;

	foreach( array(
			'irc-api-url',
			'irc-api-token',
			'irc-api-bot',
			'irc-api-room'
		) as $irc_api_param ) {

		if ( isset( $options[ $irc_api_param ] ) ) {
			$options[ $irc_api_param ] = trim(
				$options[ $irc_api_param ]
			);

			$options[ $irc_api_param ] = rtrim(
				$options[ $irc_api_param ]
			);

			$irc_params_defined++;
		}
	}

	if ( isset( $options['irc-api-url'] ) ) {
		vipgoci_option_url_handle(
			$options,
			'irc-api-url',
			null
		);
	}

	if (
		( $irc_params_defined > 0 ) &&
		( $irc_params_defined !== 4 )
	) {
		vipgoci_sysexit(
			'Some IRC API parameters defined but not all; all must be defined to be useful',
			array(
			),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}

	unset( $irc_params_defined );

	/*
	 * Make sure the IRC API token
	 * will be removed from output
	 * of options.
	 */
	vipgoci_options_sensitive_clean(
		null,
		array(
			'irc-api-token',
		)
	);

	/*
	 * Handle settings for the pixel API.
	 */
	if ( isset( $options['pixel-api-url'] ) ) {
		vipgoci_option_url_handle(
			$options,
			'pixel-api-url',
			null
		);
	}

	if ( isset( $options['pixel-api-groupprefix'] ) ) {
		$options['pixel-api-groupprefix'] = trim(
			$options['pixel-api-groupprefix']
		);
	}


	/*
	 * Handle --lint-skip-folders
	 */
	vipgoci_option_skip_folder_handle(
		$options,
		'lint-skip-folders'
	);

	/*
	 * Handle --repo-options-allowed parameter
	 */

	vipgoci_option_array_handle(
		$options,
		'repo-options-allowed',
		array(
			'skip-execution',
			'skip-draft-prs',
			'results-comments-sort',
			'review-comments-include-severity',
			'phpcs',
			'phpcs-severity',
			'post-generic-pr-support-comments',
			'phpcs-sniffs-include',
			'phpcs-sniffs-exclude',
			'hashes-api',
			'svg-checks',
			'autoapprove',
			'autoapprove-php-nonfunctional-changes',
		)
	);

	/*
	 * Do some sanity-checking on the parameters
	 *
	 * Note: Parameters should not be set after
	 * this point.
	 */

	/*
	 * Check if options relating to Generic Support Messages
	 * (--post-generic-pr-support-comments*) are consistent.
	 */

	if ( true === $options['post-generic-pr-support-comments'] ) {
		foreach(
			array(
				'post-generic-pr-support-comments-on-drafts',
				'post-generic-pr-support-comments-string',
				'post-generic-pr-support-comments-branches'
			)
			as
			$tmp_option_name
		) {
			if (
				( ! isset(
					$options[ $tmp_option_name ]
				) )
				||
				(
					empty(
						$options[ $tmp_option_name ]
					)
				)
			) {
				vipgoci_sysexit(
					'Option --' . $tmp_option_name . ' is not specified or invalid ' .
						'but --post-generic-pr-support-comments is set to true. Cannot continue',
					array(
						$tmp_option_name
							=> ( isset( $options[ $tmp_option_name ] ) ? $options[ $tmp_option_name ] : null ),
					)
				);
			}
		}

		if (
			(
				( empty( $options['post-generic-pr-support-comments-repo-meta-match'] ) ) &&
				(
					( count( $options['post-generic-pr-support-comments-on-drafts'] ) > 1 ) ||
					( count( $options['post-generic-pr-support-comments-string'] ) > 1 ) ||
					( count( $options['post-generic-pr-support-comments-skip-if-label-exists'] ) > 1 ) ||
					( count( $options['post-generic-pr-support-comments-branches'] ) > 1 )
				)
			)
			||
			(
				(
					( ! empty( $options['post-generic-pr-support-comments-repo-meta-match'] ) ) &&
					(
						count( $options['post-generic-pr-support-comments-repo-meta-match'] ) !==
						count( $options['post-generic-pr-support-comments-on-drafts'] )
					)
				)
				||
				(
					( ! empty( $options['post-generic-pr-support-comments-skip-if-label-exists'] ) ) &&
					(
						count( $options['post-generic-pr-support-comments-skip-if-label-exists'] ) !==
						count( $options['post-generic-pr-support-comments-on-drafts'] )
					)
				)
				||
				(
					count( $options['post-generic-pr-support-comments-on-drafts'] ) !==
					count( $options['post-generic-pr-support-comments-string'] )
				)
				||
				(
					count( $options['post-generic-pr-support-comments-string'] ) !==
					count( $options['post-generic-pr-support-comments-branches'] )
				)
			)
		) {
			vipgoci_sysexit(
				'Unable to process post-generic-pr-support-comments related options, ' .
					'as one or more than one string, branch or draft is specified, but ' .
					'not enough repo-meta-match options are specified to determine which ' .
					'string to post, or option values are not consistently equal in number',
				array(
					'post-generic-pr-support-comments-on-drafts' =>
						$options['post-generic-pr-support-comments-on-drafts'],

					'post-generic-pr-support-comments-string' =>
						$options['post-generic-pr-support-comments-string'],

					'post-generic-pr-support-comments-skip-if-label-exists' =>
						$options['post-generic-pr-support-comments-skip-if-label-exists'],

					'post-generic-pr-support-comments-branches' =>
						$options['post-generic-pr-support-comments-branches'],

					'post-generic-pr-support-comments-repo-meta-match' =>
						$options['post-generic-pr-support-comments-repo-meta-match'],
				)
			);
		}

		/*
		 * Check if all keys are consistent in
		 * the --post-generic-pr-support-comments-* parameters.
		 */
		$tmp_option_keys = null;

		foreach(
			array(
				'post-generic-pr-support-comments-string',
				'post-generic-pr-support-comments-skip-if-label-exists',
				'post-generic-pr-support-comments-on-drafts',
				'post-generic-pr-support-comments-branches',
				'post-generic-pr-support-comments-repo-meta-match',
			)
			as $tmp_option_name
		) {
			/*
			 * Parameter --post-generic-pr-support-comments-repo-meta-match is optional,
			 * but if it is specified, its keys should match keys of the other options.
			 */
			if (
				( 'post-generic-pr-support-comments-repo-meta-match'
					=== $tmp_option_name
				)
				&&
					( empty(
						$options[ $tmp_option_name ]
					) )
				) {
				continue;
			}

			/*
			 * Parameter --post-generic-pr-support-comments-skip-if-label-exists is
			 * optional as well.
			 */
			if (
				( 'post-generic-pr-support-comments-skip-if-label-exists'
					=== $tmp_option_name
				)
				&&
					( empty(
						$options[ $tmp_option_name ]
					) )
				) {
				continue;
			}


			if ( null === $tmp_option_keys ) {
				$tmp_option_keys = array_keys(
					$options[ $tmp_option_name ]
				);

				continue;
			}

			foreach(
				$tmp_option_keys as
					$tmp_option_key
			) {
				if ( ! isset(
					$options[
						$tmp_option_name
					][
						$tmp_option_key
					]
				) ) {
					vipgoci_sysexit(
						'Inconsistent keys in or more ' .
							'options parameters relating ' .
							'to --post-generic-pr-support-comments*',
						array(
							'post-generic-pr-support-comments-on-drafts' =>
								array_keys( $options['post-generic-pr-support-comments-on-drafts'] ),

							'post-generic-pr-support-comments-string' =>
								array_keys( $options['post-generic-pr-support-comments-string'] ),
	
							'post-generic-pr-support-comments-skip-if-label-exists' =>
								array_keys( $options['post-generic-pr-support-comments-skip-if-label-exists'] ),

							'post-generic-pr-support-comments-branches' =>
								array_keys( $options['post-generic-pr-support-comments-branches'] ),

							'post-generic-pr-support-comments-repo-meta-match' =>
								array_keys( $options['post-generic-pr-support-comments-repo-meta-match'] ),
						)
					);
				}
			}
		}
	}

	/*
	 * Check if the --output parameter looks
	 * good, if defined.
	 */
	if ( ! empty( $options['output'] ) ) {
		if ( ! is_string( $options['output'] ) ) {
			vipgoci_sysexit(
				'The --output argument should be a single string,' .
				'but it looks like it is something else. Please check ' .
				'if it is specified twice',
				array(
					'output' => print_r( $options['output'], true ),
				),
				VIPGOCI_EXIT_USAGE_ERROR
			);
		}

		if ( is_dir( $options['output'] ) ) {
			vipgoci_sysexit(
				'The file specified in --output argument is invalid, ' .
				'should not be a directory',
				array(
					'output' => print_r( $options['output'], true ),
				),
				VIPGOCI_EXIT_USAGE_ERROR
			);
		}

		/*
		 * Try writing empty string to it
		 */
		@file_put_contents(
			$options['output'],
			'',
			FILE_APPEND
		);

		/*
		 * Check if writing succeeded.
		 */
		if ( ! is_file( $options['output'] ) ) {
			vipgoci_sysexit(
				'The file specified in --output argument is invalid.',
				array(
					'output' => print_r( $options['output'], true ),
				),
				VIPGOCI_EXIT_USAGE_ERROR
			);
		}
	}

	$options['autoapprove-filetypes'] = array_map(
		'strtolower',
		$options['autoapprove-filetypes']
	);

	if ( empty( $options['autoapprove-label'] ) ) {
		$options['autoapprove-label'] = false;
	}

	else {
		$options['autoapprove-label'] = trim(
			$options['autoapprove-label']
		);
	}


	if (
		( true === $options['autoapprove'] ) &&
		( false === $options['autoapprove-label'] )
	) {
		vipgoci_sysexit(
			'To be able to auto-approve, file-types to approve ' .
			'must be specified, as well as a label; see --help ' .
			'for information',
			array(),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}

	/*
	 * Check if --svg-checks is set to true,
	 * and if a sensible scanning-tool is specified.
	 */
	if (
		( true === $options['svg-checks'] ) &&
		( 'invalid' === $options['svg-scanner-path'] )
	) {
		vipgoci_sysexit(
			'--svg-checks is set to true, but no scanner is ' .
				'configured. Please provide a valid path.',
			array(),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}

	/*
	 * Do sanity-checking with hashes-api-url
	 * and --hashes-oauth-* parameters
	 */
	if ( isset( $options['hashes-api-url'] ) ) {
		foreach ( $hashes_oauth_arguments as $tmp_key ) {
			if ( ! isset( $options[ $tmp_key ] ) ) {
				vipgoci_sysexit(
					'Asking to use --hashes-api-url without --hashes-oauth-* parameters, but that is not possible, as authorization is needed for hashes-to-hashes API',
					array(),
					VIPGOCI_EXIT_USAGE_ERROR
				);
			}
		}

		if ( false === $options['autoapprove'] ) {
			vipgoci_sysexit(
				'Asking to use --hashes-api-url without --autoapproval set to true, but for hashes-to-hashes functionality to be useful, --autoapprove must be enabled. Otherwise the functionality will not really be used',
				array(),
				VIPGOCI_EXIT_USAGE_ERROR
			);
		}
	}

	if (
		( true === $options['autoapprove'] ) &&

		/*
		 * Cross-reference: We disallow autoapproving
		 * PHP and JS files here, because they chould contain
		 * contain dangerous code.
		 */
		(
			( in_array(
				'php',
				$options['autoapprove-filetypes'],
				true
			) )
		||
			( in_array(
				'js',
				$options['autoapprove-filetypes'],
				true
			) )
		)
	) {
		vipgoci_sysexit(
			'PHP and JS files cannot be auto-approved on file-type basis, as they ' .
				'can cause serious problems for execution',
			array(
			),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}

	/*
	 * Also, we disallow autoapproving SVG files here, as
	 * we have a dedicated part of vip-go-ci to scan them
	 * and autoapprove.
	 */

	if (
		( true === $options['autoapprove'] ) &&
		(
			( in_array(
				'svg',
				$options['autoapprove-filetypes'],
				true
			) )
		)
	) {
		vipgoci_sysexit(
			'SVG files cannot be auto-approved on file-type basis, as they ' .
				'can contain problematic code. Use --svg-checks=true to ' .
				'allow auto-approval of SVG files',
			array(
			),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}


	/*
	 * Ask GitHub about information about
	 * the user the token belongs to
	 */
	$current_user_info = vipgoci_github_authenticated_user_get(
		$options['token']
	);

	if (
		( false === $current_user_info ) ||
		( ! isset( $current_user_info->login ) ) ||
		( empty( $current_user_info->login ) )
	) {
		vipgoci_sysexit(
			'Unable to get information about token-holder user from GitHub',
			array(
			),
			VIPGOCI_EXIT_GITHUB_PROBLEM
		);
	}

	else {
		vipgoci_log(
			'Got information about token-holder user from GitHub',
			array(
				'login' => $current_user_info->login,
				'html_url' => $current_user_info->html_url,
			)
		);
	}

	/*
	 * Hide GitHub token from printed options output.
	 */
	vipgoci_options_sensitive_clean(
		null,
		array(
			'token',
		)
	);


	/*
	 * Check if the teams specified in the
	 * --dismissed-reviews-exclude-reviews-from-team parameter are
	 * really valid, etc.
	 */
	vipgoci_option_teams_handle(
		$options,
		'dismissed-reviews-exclude-reviews-from-team'
	);

	/*
	 * Certain options are configurable via
	 * options-file in the repository. Set
	 * these options here.
	 *
	 * Note that any new option added here should
	 * be added to the --repo-options-allowed option
	 * found above.
	 */
	vipgoci_options_read_repo_file(
		$options,
		VIPGOCI_OPTIONS_FILE_NAME,
		array(
			'skip-execution'	=> array(
				'type'		=> 'boolean',
				'valid_values'	=> array( true, false ),
			),

			'skip-draft-prs'	=> array(
				'type'		=> 'boolean',
				'valid_values'	=> array( true, false ),
			),

			'results-comments-sort' => array(
				'type'		=> 'boolean',
				'valid_values'	=> array( true, false ),
			),

			'review-comments-include-severity' => array(
				'type'		=> 'boolean',
				'valid_values'	=> array( true, false ),
			),

			'phpcs' => array(
				'type'		=> 'boolean',
				'valid_values'	=> array( true, false ),
			),

			'phpcs-severity' => array(
				'type'		=> 'integer',
				'valid_values'	=> array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ),
			),

			'post-generic-pr-support-comments' => array(
				'type'		=> 'boolean',
				'valid_values'	=> array( true, false ),
			),

			'phpcs-sniffs-include' => array(
				'type'		=> 'array',
				'append'	=> true,
				'valid_values'	=> null,
			),

			'phpcs-sniffs-exclude' => array(
				'type'		=> 'array',
				'append'	=> true,
				'valid_values'	=> null,
			),

			'hashes-api' => array(
				'type'		=> 'boolean',
				'valid_values'	=> array( true, false ),
			),

			'svg-checks' => array(
				'type'		=> 'boolean',
				'valid_values'	=> array( true, false ),	
			),

			'autoapprove' => array(
				'type'		=> 'boolean',
				'valid_values'	=> array( true, false ),
			),

			'autoapprove-php-nonfunctional-changes' => array(
				'type'		=> 'boolean',
				'valid_values'	=> array( true, false ),
			),
		)
	);

	/*
	 * Folders to skip from PHPCS or PHP Linting
	 * can be read from a config-file in the
	 * repository. Read this here and set in
	 * options.
	 */

	vipgoci_options_read_repo_skip_files(
		$options
	);

	/*
	 * In case of exiting before we
	 * empty the IRC queue, do it on shutdown.
	 */
	if (
		( ! empty( $options['irc-api-url'] ) ) &&
		( ! empty( $options['irc-api-token'] ) ) &&
		( ! empty( $options['irc-api-bot'] ) ) &&
		( ! empty( $options['irc-api-room'] ) )
	) {
		register_shutdown_function(
			'vipgoci_irc_api_alerts_send',
			$options['irc-api-url'],
			$options['irc-api-token'],
			$options['irc-api-bot'],
			$options['irc-api-room']
		);
	}

	/*
	 * Log that we started working,
	 * and the arguments provided as well.
	 *
	 * Make sure not to print out any secrets.
	 */

	vipgoci_log(
		'Starting up...',
		array(
			'options' => vipgoci_options_sensitive_clean(
				$options
			)
		)
	);

	$results = array(
		'issues'	=> array(),

		'stats'		=> array(
			VIPGOCI_STATS_PHPCS	=> null,
			VIPGOCI_STATS_LINT	=> null,
			VIPGOCI_STATS_HASHES_API => null,
		),
	);

	/*
	 * If asked not to scan, don't scan then.
	 */
	if ( true === $options['skip-execution'] ) {
		vipgoci_sysexit(
			'Skipping scanning entirely, as determined ' .
				'by configuration',
			array(
			),
			VIPGOCI_EXIT_NORMAL
		);
	}

	/*
	 * If no Pull-Requests are implicated by this commit,
	 * bail now, as there is no point in continuing running.
	 */

	$prs_implicated = vipgoci_github_prs_implicated(
		$options['repo-owner'],
		$options['repo-name'],
		$options['commit'],
		$options['token'],
		$options['branches-ignore'],
		$options['skip-draft-prs']
	);

	if ( empty( $prs_implicated ) ) {
		vipgoci_sysexit(
			'Skipping scanning entirely, as the commit ' .
				'is not a part of any Pull-Request',
			array(),
			VIPGOCI_EXIT_NORMAL
		);
	}

	vipgoci_log(
		'Found implicated Pull-Requests',
		array(
			'prs_implicated' => array_keys( $prs_implicated ),
		)
	);

	/*
	 * Make sure we are working with the latest
	 * commit to each implicated PR.
	 *
	 * If we detect that we are doing linting,
	 * and the commit is not the latest, skip linting
	 * as it becomes useless if this is not the
	 * latest commit: There is no use in linting
	 * an obsolete commit.
	 */
	foreach ( $prs_implicated as $pr_item ) {
		$commits_list = vipgoci_github_prs_commits_list(
			$options['repo-owner'],
			$options['repo-name'],
			$pr_item->number,
			$options['token']
		);

		// If no commits, skip checks
		if ( empty( $commits_list ) ) {
			continue;
		}

		// Reverse array, so we get the last commit first
		$commits_list = array_reverse( $commits_list );


		// If latest commit to the PR, we do not care at all
		if ( $commits_list[0] === $options['commit'] ) {
			continue;
		}

		/*
		 * At this point, we have found an inconsistency;
		 * the commit we are working with is not the latest
		 * to the Pull-Request, and we have to deal with that.
		 */

		if (
			( true === $options['lint'] ) &&
			( false === $options['phpcs'] )
		) {
			vipgoci_sysexit(
				'The current commit is not the latest one ' .
					'to the Pull-Request, skipping ' .
					'linting, and not doing PHPCS ' .
					'-- nothing to do',
				array(
					'repo_owner' => $options['repo-owner'],
					'repo_name' => $options['repo-name'],
					'pr_number' => $pr_item->number,
				),
				VIPGOCI_EXIT_NORMAL
			);
		}

		else if (
			( true === $options['lint'] ) &&
			( true === $options['phpcs'] )
		) {
			// Skip linting, useless if not latest commit
			$options['lint'] = false;

			vipgoci_log(
				'The current commit is not the latest ' .
					'one to the Pull-Request, ' .
					'skipping linting',
				array(
					'repo_owner' => $options['repo-owner'],
					'repo_name' => $options['repo-name'],
					'pr_number' => $pr_item->number,
				)
			);
		}

		/*
		 * As for lint === false && true === phpcs,
		 * we do not care, as then we will not be linting.
		 */

		unset( $commits_list );
	}


	/*
	 * Init stats
	 */
	vipgoci_stats_init(
		$options,
		$prs_implicated,
		$results
	);

	/*
	 * Clean up old comments made by us previously
	 */
	vipgoci_github_pr_comments_cleanup(
		$options['repo-owner'],
		$options['repo-name'],
		$options['commit'],
		$options['token'],
		$options['branches-ignore'],
		$options['skip-draft-prs'],
		array(
			VIPGOCI_SYNTAX_ERROR_STR,
			VIPGOCI_GITHUB_ERROR_STR,
			VIPGOCI_REVIEW_COMMENTS_TOTAL_MAX,
			VIPGOCI_PHPCS_INVALID_SNIFFS,
			VIPGOCI_PHPCS_DUPLICATE_SNIFFS,
		)
	);

	/*
	 * If configured to do so, post a generic comment
	 * on the Pull-Request(s) with some helpful information.
	 * Comment is set via option.
	 */
	vipgoci_github_pr_generic_support_comment_submit(
		$options,
		$prs_implicated
	);

	/*
	 * Add support level label, if:
	 * - configured to do so
	 * - data is available in repo-meta API
	 */
	vipgoci_support_level_label_set(
		$options
	);

	/*
	 * Verify that sniffs specified on command line
	 * or via options file are valid. Will remove any
	 * invalid sniffs from the options on the fly and
	 * post a message to users about the invalid sniffs.
	 */

	vipgoci_phpcs_validate_sniffs_in_options_and_report(
		$options
	);

	/*
	 * Set to use new PHPCS standard if needed.
	 */

	vipgoci_phpcs_possibly_use_new_standard_file(
		$options
	);

	/*
	 * Run all checks requested and store the
	 * results in an array
	 */

	if ( true === $options['lint'] ) {
		vipgoci_lint_scan_commit(
			$options,
			$results['issues'],
			$results['stats'][ VIPGOCI_STATS_LINT ]
		);
	}

	/*
	 * Note: We run this, even if linting fails, to make sure
	 * to catch all errors incrementally.
	 */

	if ( true === $options['phpcs'] ) {
		vipgoci_phpcs_scan_commit(
			$options,
			$results['issues'],
			$results['stats'][ VIPGOCI_STATS_PHPCS ]
		);
	}

	/*
	 * If to do auto-approvals, then do so now.
	 * First ask all 'auto-approval modules'
	 * to do their scanning, collecting all files that
	 * can be auto-approved, and then actually do the
	 * auto-approval if possible.
	 */

	$auto_approved_files_arr = array();

	if ( true === $options['autoapprove'] ) {
		/*
		 * FIXME: Move the function-calls below
		 * to auto-approval.php -- place them
		 * in a wrapper, and not vipgoci_auto_approval_scan_commit()
		 */

		/*
		 * If to auto-approve based on file-types,
		 * scan through the files in the PR, and
		 * register which can be auto-approved.
		 */

		if ( ! empty( $options[ 'autoapprove-filetypes' ] ) ) {
			vipgoci_ap_file_types(
				$options,
				$auto_approved_files_arr
			);
		}

		/*
		 * Check if any of the files changed
		 * contain any non-functional changes --
		 * i.e., only whitespacing changes and
		 * commenting changes -- and if so,
		 * approve those files.
		 */
		if ( true === $options['autoapprove-php-nonfunctional-changes'] ) {
			vipgoci_ap_nonfunctional_changes(
				$options,
				$auto_approved_files_arr
			);
		}

		/*
		 * Do scanning of all altered files, using
		 * the hashes-to-hashes database API, collecting
		 * which files can be auto-approved.
		 */

		if ( true === $options['hashes-api'] ) {
			vipgoci_ap_hashes_api_scan_commit(
				$options,
				$results['issues'],
				$results['stats'][ VIPGOCI_STATS_HASHES_API ],
				$auto_approved_files_arr
			);
		}

		if ( true === $options['svg-checks'] ) {
			vipgoci_ap_svg_files(
				$options,
				$auto_approved_files_arr
			);
		}

		vipgoci_auto_approval_scan_commit(
			$options,
			$auto_approved_files_arr,
			$results
		);
	}


	/*
	 * Remove issues from $results for files
	 * that are approved in hashes-to-hashes API.
	 */

	vipgoci_results_approved_files_comments_remove(
		$options,
		$results,
		$auto_approved_files_arr
	);


	/*
	 * Get all events on dismissed reviews
	 * from members of the specified team(s),
	 * by Pull-Request.
	 */

	$team_members_ids_arr = vipgoci_github_team_members_many_get(
		$options['token'],
		$options['dismissed-reviews-exclude-reviews-from-team']
	);


	/*
	 * If we have any team member's logins,
	 * get any Pull-Request review dismissal events
	 * by members of that team.
	 */
	$prs_events_dismissed_by_team = array();

	if (
		( ! empty(
			$options['dismissed-reviews-exclude-reviews-from-team']
		) )
		&&
		( ! empty(
			$team_members_ids_arr
		) )
	) {
		foreach ( $prs_implicated as $pr_item ) {
			$prs_events_dismissed_by_team[ $pr_item->number ] =
				vipgoci_github_pr_review_events_get(
					$options,
					$pr_item->number,
					array(
						'event_type' => 'review_dismissed',
						'actors_ids' => $team_members_ids_arr,
					),
					true
				);
		}

		vipgoci_log(
			'Fetched list of Pull-Request reviews dismissed by members of a team',
			array(
				'team_members' =>
					$team_members_ids_arr,
				'reviews_dismissed_by_team' =>
					$prs_events_dismissed_by_team,
			)
		);
	}

	unset( $team_members_ids_arr );


	/*
	 * Remove comments from $results that have
	 * already been submitted.
	 */

	vipgoci_results_remove_existing_github_comments(
		$options,
		$prs_implicated,
		$results,
		$options['dismissed-reviews-repost-comments'],
		$prs_events_dismissed_by_team
	);

	/*
	 * Sort issues by severity level, so that
	 * highest severity is first.
	 */

	vipgoci_results_sort_by_severity(
		$options,
		$results
	);

	/*
	 * Remove ignorable comments from $results.
	 */

	if ( ! empty( $options['review-comments-ignore'] ) ) {
		$file_issues_arr_master =
			vipgoci_results_filter_ignorable(
				$options,
				$results
			);
	}

	/*
	 * Keep records of how many issues we found.
	 */
	vipgoci_counter_update_with_issues_found(
		$results
	);

	/*
	 * Limit number of issues in $results.
	 *
	 * If set to zero, skip this part.
	 */

	if ( $options['review-comments-total-max'] > 0 ) {
		$prs_comments_maxed = array();

		vipgoci_results_filter_comments_to_max(
			$options,
			$results,
			$prs_comments_maxed
		);
	}

	/*
	 * Submit any remaining issues to GitHub
	 */

	vipgoci_github_pr_generic_comment_submit_results(
		$options['repo-owner'],
		$options['repo-name'],
		$options['token'],
		$options['commit'],
		$results,
		$options['informational-url']
	);


	vipgoci_github_pr_review_submit(
		$options['repo-owner'],
		$options['repo-name'],
		$options['token'],
		$options['commit'],
		$results,
		$options['informational-url'],
		$options['review-comments-max'],
		$options['review-comments-include-severity']
	);

	if ( true === $options['dismiss-stale-reviews'] ) {
		/*
		 * Dismiss any reviews that contain *only*
		 * inactive comments -- i.e. comments that
		 * are obsolete as the code has been changed.
		 *
		 * Note that we do this again here because we might
		 * just have deleted comments from a Pull-Request which
		 * would then remain without comments.
		 */

		foreach ( $prs_implicated as $pr_item ) {
			vipgoci_github_pr_reviews_dismiss_with_non_active_comments(
				$options,
				$pr_item->number
			);
		}
	}

	/*
	 * If we reached maximum number of
	 * comments earlier, put a message out
	 * so people actually know it.
	 */

	if ( $options['review-comments-total-max'] > 0 ) {
		foreach( array_keys(
			$prs_comments_maxed
		) as $pr_number ) {
			vipgoci_github_pr_comments_generic_submit(
				$options['repo-owner'],
				$options['repo-name'],
				$options['token'],
				$pr_number,
				VIPGOCI_REVIEW_COMMENTS_TOTAL_MAX,
				$options['commit']
			);
		}
	}

	/*
	 * At this point, we have started to prepare
	 * for shutdown and exit -- no review-critical
	 * actions should be performed after this point.
	 */

	/*
	 * Send out to IRC API any alerts
	 * that are queued up.
	 */

	if (
		( ! empty( $options['irc-api-url'] ) ) &&
		( ! empty( $options['irc-api-token'] ) ) &&
		( ! empty( $options['irc-api-bot'] ) ) &&
		( ! empty( $options['irc-api-room'] ) )
	) {
		vipgoci_irc_api_alerts_send(
			$options['irc-api-url'],
			$options['irc-api-token'],
			$options['irc-api-bot'],
			$options['irc-api-room']
		);
	}

	$github_api_rate_limit_usage =
		vipgoci_github_rate_limit_usage(
			$options['token']
		);

	/*
	 * Prepare to send statistics to external service,
	 * also keep for exit-message.
	 */
	$counter_report = vipgoci_counter_report(
		VIPGOCI_COUNTERS_DUMP,
		null,
		null
	);

	/*
	 * Actually send statistics if configured
	 * to do so.
	 */

	if (
		( ! empty( $options['pixel-api-url'] ) ) &&
		( ! empty( $options['pixel-api-groupprefix' ] ) )
	) {
		vipgoci_send_stats_to_pixel_api(
			$options['pixel-api-url'],

			/*
			 * Which statistics to send.
			 */
			array(
				/*
				 * Generic statistics pertaining
				 * to all repositories.
				 */
				$options['pixel-api-groupprefix'] .
					'-actions' =>
				array(
					'github_pr_approval',
					'github_pr_non_approval',
					'github_api_request_get',
					'github_api_request_post',
					'github_api_request_put',
					'github_api_request_fetch',
					'github_api_request_delete'
				),

				/*
				 * Repository-specific statistics.
				 */
				$options['pixel-api-groupprefix'] .
					'-' .
					$options['repo-name']
				=> array(
					'github_pr_approval',
					'github_pr_non_approval',
					'github_pr_files_scanned',
					'github_pr_lines_scanned',
					'github_pr_files_linted',
					'github_pr_lines_linted',
					'github_pr_phpcs_issues',
					'github_pr_lint_issues'
				)
			),
			$counter_report
		);
	}

	/*
	 * Remove temporary PHPCS XML standard
	 * file if used.
	 */

	if (
		( true === $options['phpcs-standard-file'] ) &&
		( file_exists(
			$options['phpcs-standard'][0]
		) )
	) {
		unlink(
			$options['phpcs-standard'][0]
		);
	}


	/*
	 * Final logging before quitting.
	 */
	vipgoci_log(
		'Shutting down',
		array(
			'run_time_seconds'	=> time() - $startup_time,
			'run_time_measurements'	=>
				vipgoci_runtime_measure(
					VIPGOCI_RUNTIME_DUMP,
					null
				),
			'counters_report'	=> $counter_report,

			'github_api_rate_limit' =>
				$github_api_rate_limit_usage->resources->core,

			'results'		=> $results,
		)
	);


	/*
	 * Determine exit code.
	 */
	return vipgoci_exit_status(
		$results
	);
}

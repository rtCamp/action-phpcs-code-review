<?php
/**
 * Main functions for the program.
 *
 * @package Automattic/vip-go-ci
 * @author Automattic inc.
 * @copyright 2017-2022 Automattic inc.
 * @file main.php
 * @description vip-go-ci main functions.
 */

declare(strict_types=1);

/**
 * Print help message.
 *
 * @return void
 */
function vipgoci_help_print() :void {
	global $argv;

	print 'Usage: ' . $argv[0] . ' [OPTION]...' . PHP_EOL .
		PHP_EOL .
		"\t" . 'Options --repo-owner, --repo-name, --commit, --token, --local-git-repo are' . PHP_EOL .
		"\t" . 'mandatory, while others are optional.' . PHP_EOL .
		PHP_EOL .
		"\t" . 'Note that if option --autoapprove is specified, --autoapprove-label needs to' . PHP_EOL .
		"\t" . 'be specified as well.' . PHP_EOL .
		PHP_EOL .
		'General configuration:' . PHP_EOL .
		"\t" . '--help                         Displays this message' . PHP_EOL .
		"\t" . '--version                      Displays version number and exits.' . PHP_EOL .
		"\t" . '--debug-level=NUMBER           Specify minimum debug-level of messages to print' . PHP_EOL .
		"\t" . '                                -- higher number indicates more detailed debugging-messages.' . PHP_EOL .
		"\t" . '                               Default is zero' . PHP_EOL .
		"\t" . '--max-exec-time=NUMBER         Maximum execution time for vip-go-ci, in seconds. Will exit if exceeded.' . PHP_EOL .
		"\t" . '                               Only lime spent after options are initialized and during scanning is' . PHP_EOL .
		"\t" . '                               considered as execution time. Time initializing is excluded.' . PHP_EOL .
		"\t" . '--enforce-https-urls=BOOL      Check and enforce that all URLs provided to parameters' . PHP_EOL .
		"\t" . '                               that expect a URL are HTTPS and not HTTP. Default is true.' . PHP_EOL .
		"\t" . '--skip-draft-prs=BOOL          If true, skip scanning of all pull requests that are in draft mode.' . PHP_EOL .
		"\t" . '                               Default is false.' . PHP_EOL .
		"\t" . '--skip-large-files=true=BOOL          If true, skip scanning files that have number of lines higher than the skip-large-files-limit value.' . PHP_EOL .
		"\t" . '                                      Default is true.' . PHP_EOL .
		"\t" . '--skip-large-files-limit=INTEGER      Defines the maximum number of lines limit per file.' . PHP_EOL .
		"\t" . '                                      Default is ' . VIPGOCI_VALIDATION_MAXIMUM_LINES_LIMIT . ' lines.' . PHP_EOL .
		"\t" . '--branches-ignore=STRING,...   What branches to ignore -- useful to make sure' . PHP_EOL .
		"\t" . '                               some branches never get scanned. Separate branches' . PHP_EOL .
		"\t" . '                               with commas.' . PHP_EOL .
		"\t" . '--local-git-repo=FILE          The local git repository to use for direct access to code.' . PHP_EOL .
		PHP_EOL .
		'Environmental & repo configuration:' . PHP_EOL .
		"\t" . '--env-options=STRING           Specifies configuration options to be read from environmental' . PHP_EOL .
		"\t" . '                               variables -- any variable can be specified. For instance, with' . PHP_EOL .
		"\t" . '                               --env-options="repo-owner=U_ROWNER,output=U_FOUTPUT" specified' . PHP_EOL .
		"\t" . '                               vip-go-ci will attempt to read the --repo-owner and --output' . PHP_EOL .
		"\t" . '                               from the $U_ROWNER and $U_FOUTPUT environmental variables,' . PHP_EOL .
		"\t" . '                               respectively. This is useful for environments, such as' . PHP_EOL .
		"\t" . '                               TeamCity or GitHub Actions, where vital configuration.' . PHP_EOL .
		"\t" . '                               are specified via environmental variables.' . PHP_EOL .
		"\t" . '                               --enforce-https-urls parameter is not configurable via environment.' . PHP_EOL .
		"\t" . '--repo-options=BOOL            Whether to allow configuring of certain configuration parameters' . PHP_EOL .
		"\t" . '                               via options file ("' . VIPGOCI_OPTIONS_FILE_NAME . '") placed in' . PHP_EOL .
		"\t" . '                               root of the repository.' . PHP_EOL .
		"\t" . '--repo-options-allowed=STRING  Limits the options that can be set via repository options' . PHP_EOL .
		"\t" . '                               configuration file. Values are separated by commas. Default' . PHP_EOL .
		"\t" . '                               are all options supported (see README.md).' . PHP_EOL .
		PHP_EOL .
		'GitHub configuration:' . PHP_EOL .
		"\t" . '--repo-owner=STRING            Specify repository owner, can be an organization.' . PHP_EOL .
		"\t" . '--repo-name=STRING             Specify name of the repository.' . PHP_EOL .
		"\t" . '--commit=STRING                Specify the exact commit to scan (SHA).' . PHP_EOL .
		"\t" . '--token=STRING                 The access-token to use to communicate with GitHub.' . PHP_EOL .
		PHP_EOL .
		'PHP Linting configuration:' . PHP_EOL .
		"\t" . '--lint=BOOL                    Whether to do PHP linting. Default is true.' . PHP_EOL .
		"\t" . '--lint-php-version-paths=ARRAY Array of paths to different PHP interpreter versions, comma' . PHP_EOL .
		"\t" . '                               separated. Version and path separated by colon. Used for linting.' . PHP_EOL .
		"\t" . '                               E.g.: --lint-php-version-paths=7.4:/usr/bin/php7.4,8.1:/usr/bin/php8.1' . PHP_EOL .
		"\t" . '--lint-php-versions=ARRAY      Array of PHP versions to lint with during run. Comma separated values.' . PHP_EOL .
		"\t" . '--lint-modified-files-only=BOOL   Whether to limit lint scan to run against only modified or new' . PHP_EOL .
		"\t" . '                               files in the PR to be scanned. Default is true. It can be ' . PHP_EOL .
		"\t" . '                               modified via options file ("' . VIPGOCI_OPTIONS_FILE_NAME . '") placed in' . PHP_EOL .
		"\t" . '                               root of the repository.' . PHP_EOL .
		"\t" . '--lint-skip-folders=STRING     Specify folders relative to root of the git repository in which' . PHP_EOL .
		"\t" . '                               files should not be PHP linted. Values are comma separated.' . PHP_EOL .
		"\t" . '--lint-skip-folders-in-repo-options-file=BOOL   Whether to allow specifying folders that are not' . PHP_EOL .
		"\t" . '                                                to be PHP Linted in a file in root of repository' . PHP_EOL .
		"\t" . '                                                (.vipgoci_lint_skip_folders). Folders should be' . PHP_EOL .
		"\t" . '                                                separated by newlines.' . PHP_EOL .
		PHP_EOL .
		'PHPCS configuration:' . PHP_EOL .
		"\t" . '--phpcs=BOOL                   Whether to run PHPCS. Default is true.' . PHP_EOL .
		"\t" . '--phpcs-php-path=FILE          Full path to PHP used to run PHPCS. If not specified the default in' . PHP_EOL .
		"\t" . '                               $PATH will be used instead.' . PHP_EOL .
		"\t" . '--phpcs-path=FILE              Full path to PHPCS script.' . PHP_EOL .
		"\t" . '--phpcs-standard=STRING        Specify which PHPCS standard(s) to use. Separate by commas.' . PHP_EOL .
		"\t" . '                               If nothing is specified, the \'WordPress\' standard is used.' . PHP_EOL .
		"\t" . '--phpcs-severity=NUMBER        Specify severity for PHPCS.' . PHP_EOL .
		"\t" . '--phpcs-sniffs-include=ARRAY   Specify which sniffs to include when PHPCS scanning,' . PHP_EOL .
		"\t" . '                               should be an array with items separated by commas.' . PHP_EOL .
		"\t" . '--phpcs-sniffs-exclude=ARRAY   Specify which sniffs to exclude from PHPCS scanning,' . PHP_EOL .
		"\t" . '                               should be an array with items separated by commas.' . PHP_EOL .
		"\t" . '--phpcs-runtime-set=STRING     Specify --runtime-set values passed on to PHPCS' . PHP_EOL .
		"\t" . '                               -- expected to be a comma-separated value string of' . PHP_EOL .
		"\t" . '                               key-value pairs.' . PHP_EOL .
		"\t" . '                               For example: --phpcs-runtime-set="key1 value1,key2 value2"' . PHP_EOL .
		"\t" . '--phpcs-skip-scanning-via-labels-allowed=BOOL    Whether to allow users to skip PHPCS' . PHP_EOL .
		"\t" . '                                                 scanning of pull requests via labels' . PHP_EOL .
		"\t" . '                                                 attached to them. The label should be' . PHP_EOL .
		"\t" . '                                                 named "skip-phpcs-scan".' . PHP_EOL .
		"\t" . '--phpcs-skip-folders=STRING    Specify folders relative to root of the git repository in which' . PHP_EOL .
		"\t" . '                               files are not to be scanned using PHPCS. Values are comma' . PHP_EOL .
		"\t" . '                               separated.' . PHP_EOL .
		"\t" . '--phpcs-skip-folders-in-repo-options-file=BOOL   Whether to allow specifying folders that are not' . PHP_EOL .
		"\t" . '                                                 to be PHPCS scanned to be specified in file in root' . PHP_EOL .
		"\t" . '                                                 of repository (.vipgoci_phpcs_skip_folders).' . PHP_EOL .
		"\t" . '                                                 Folders should be separated by newlines.' . PHP_EOL .
		"\t" . '--output=FILE                  Where to save PHPCS output.' . PHP_EOL .
		PHP_EOL .
		'SVG scanning configuration:' . PHP_EOL .
		"\t" . '--svg-checks=BOOL              Enable or disable SVG checks, both auto-approval of SVG' . PHP_EOL .
		"\t" . '                               files and problem checking of these files. Note that if' . PHP_EOL .
		"\t" . '                               auto-approvals are turned off globally, no auto-approval' . PHP_EOL .
		"\t" . '                               is performed for SVG files.' . PHP_EOL .
		"\t" . '--svg-php-path=FILE            Full path to PHP used to run SVG scanner. If not specified the default in' . PHP_EOL .
		"\t" . '                               $PATH will be used instead.' . PHP_EOL .
		"\t" . '--svg-scanner-path=FILE        Path to SVG scanning tool. Should return similar output' . PHP_EOL .
		"\t" . '                               as PHPCS.' . PHP_EOL .
		PHP_EOL .
		'Auto approve configuration:' . PHP_EOL .
		"\t" . '--autoapprove=BOOL             Whether to auto-approve pull requests that fulfil' . PHP_EOL .
		"\t" . '                               certain conditions -- see README.md for details.' . PHP_EOL .
		"\t" . '--autoapprove-filetypes=STRING Specify what file-types can be auto-' . PHP_EOL .
		"\t" . '                               approved. PHP files cannot be specified.' . PHP_EOL .
		"\t" . '--autoapprove-php-nonfunctional-changes=BOOL    For autoapprovals, also consider' . PHP_EOL .
		"\t" . '                                                PHP files approved that contain' . PHP_EOL .
		"\t" . '                                                only non-functional changes, such as' . PHP_EOL .
		"\t" . '                                                whitespacing and comment changes.' . PHP_EOL .
		"\t" . '--autoapprove-label=STRING     String to use for labels when auto-approving.' . PHP_EOL .
		PHP_EOL .
		'Hashes API configuration:' . PHP_EOL .
		"\t" . '--hashes-api=BOOL              Whether to do hashes-to-hashes API verfication with' . PHP_EOL .
		"\t" . '                               individual PHP files found to be altered in' . PHP_EOL .
		"\t" . '                               scanned pull requests.' . PHP_EOL .
		"\t" . '--hashes-api-url=STRING        URL to hashes-to-hashes HTTP API root' . PHP_EOL .
		"\t" . '                               -- note that it should not include any specific' . PHP_EOL .
		"\t" . '                               paths to individual parts of the API.' . PHP_EOL .
		"\t" . '--hashes-oauth-token=STRING,' . PHP_EOL .
		"\t" . '--hashes-oauth-token-secret=STRING,' . PHP_EOL .
		"\t" . '--hashes-oauth-consumer-key=STRING,' . PHP_EOL .
		"\t" . '--hashes-oauth-consumer-secret=STRING' . PHP_EOL .
		"\t" . '                               OAuth 1.0 token, token secret, consumer key and' . PHP_EOL .
		"\t" . '                               consumer secret needed for hashes-to-hashes HTTP requests.' . PHP_EOL .
		"\t" . '                               All required for hashes-to-hashes requests.' . PHP_EOL .
		PHP_EOL .
		'GitHub reviews & generic comments configuration:' . PHP_EOL .
		"\t" . '--report-no-issues-found=BOOL  Post message indicating no issues were found during scanning.' . PHP_EOL .
		"\t" . '                               Enabled by default.' . PHP_EOL .
		"\t" . '--review-comments-sort=BOOL    Sort issues found according to severity, from high' . PHP_EOL .
		"\t" . '                               to low, before submitting to GitHub. Not sorted by default.' . PHP_EOL .
		"\t" . '--review-comments-max=NUMBER   Maximum number of inline comments to submit' . PHP_EOL .
		"\t" . '                               to GitHub in one review. If the number of' . PHP_EOL .
		"\t" . '                               comments exceed this number, additional reviews' . PHP_EOL .
		"\t" . '                               will be submitted.' . PHP_EOL .
		"\t" . '--review-comments-total-max=NUMBER  Maximum number of inline comments submitted to' . PHP_EOL .
		"\t" . '                                    a single pull request by the program -- includes' . PHP_EOL .
		"\t" . '                                    comments from previous executions. A value of' . PHP_EOL .
		"\t" . '                                    \'0\' indicates no limit.' . PHP_EOL .
		"\t" . '--review-comments-ignore=STRING     Specify which result comments to ignore' . PHP_EOL .
		"\t" . '                                    -- e.g. useful if one type of message is to be ignored' . PHP_EOL .
		"\t" . '                                    rather than a whole PHPCS sniff. Should be a' . PHP_EOL .
		"\t" . '                                    whole string with items separated by \"|||\".' . PHP_EOL .
		"\t" . '--review-comments-include-severity=BOOL  Whether to include severity level with' . PHP_EOL .
		"\t" . '                                         each review comment. Default is false.' . PHP_EOL .
		PHP_EOL .
		"\t" . '--dismiss-stale-reviews=BOOL   Dismiss any reviews associated with pull requests' . PHP_EOL .
		"\t" . '                               that we process which have no active comments.' . PHP_EOL .
		"\t" . '--dismissed-reviews-repost-comments=BOOL  When avoiding double-posting comments,' . PHP_EOL .
		"\t" . '                                          do not take into consideration comments' . PHP_EOL .
		"\t" . '                                          posted against reviews that have now been' . PHP_EOL .
		"\t" . '                                          dismissed. Setting this to true entails' . PHP_EOL .
		"\t" . '                                          that comments from dismissed reviews will' . PHP_EOL .
		"\t" . '                                          be posted again, should the underlying issue' . PHP_EOL .
		"\t" . '                                          be detected during the run.' . PHP_EOL .
		"\t" . '--dismissed-reviews-exclude-reviews-from-team=STRING  With this parameter set,' . PHP_EOL .
		"\t" . '                                                      comments that are part of reviews' . PHP_EOL .
		"\t" . '                                                      dismissed by members of the teams specified,' . PHP_EOL .
		"\t" . '                                                      would be taken into consideration when' . PHP_EOL .
		"\t" . '                                                      avoiding double-posting; they would be' . PHP_EOL .
		"\t" . '                                                      excluded. Note that this parameter' . PHP_EOL .
		"\t" . '                                                      only works in conjunction with' . PHP_EOL .
		"\t" . '                                                      --dismissed-reviews-repost-comments .' . PHP_EOL .
		"\t" . '                                                      The parameter expects a team slug, not ID.' . PHP_EOL .
		"\t" . '--informational-msg=STRING     Message to append to GitHub reviews and generic comments. Useful to' . PHP_EOL .
		"\t" . '                               explain what the bot does. Can contain HTML or Markdown.' . PHP_EOL .
		"\t" . '--scan-details-msg-include=BOOL If to include additional detail about the scan, versions of' . PHP_EOL .
		"\t" . '                                software used, options altered and so forth. Enabled by default.' . PHP_EOL .
		PHP_EOL .
		'Generic support comments configuration:' . PHP_EOL .
		"\t" . '--post-generic-pr-support-comments=BOOL            Whether to post generic comment to pull requests' . PHP_EOL .
		"\t" . '                                                   with support-related information for users. Will' . PHP_EOL .
		"\t" . '                                                   be posted only once per pull request.' . PHP_EOL .
		"\t" . '--post-generic-pr-support-comments-on-drafts=BOOL  Determine if to post generic comment to draft' . PHP_EOL .
		"\t" . '                                                   pull requests also. Default is true.' . PHP_EOL .
		"\t" . '--post-generic-pr-support-comments-string=STRING   String to use when posting support-comment.' . PHP_EOL .
		"\t" . '--post-generic-pr-support-comments-skip-if-label-exists=STRING  If the specified label exists on' . PHP_EOL .
		"\t" . '                                                                the pull request, do not post support' . PHP_EOL .
		"\t" . '                                                                comment.' . PHP_EOL .
		"\t" . '--post-generic-pr-support-comments-branches=ARRAY  Only post support-comments to pull requests' . PHP_EOL .
		"\t" . '                                                   with the target branches specified. The' . PHP_EOL .
		"\t" . '                                                   parameter can be a string with one value, or' . PHP_EOL .
		"\t" . '                                                   comma separated. A single "any" value will' . PHP_EOL .
		"\t" . '                                                   cause the message to be posted to any' . PHP_EOL .
		"\t" . '                                                   branch.' . PHP_EOL .
		"\t" . '--post-generic-pr-support-comments-repo-meta-match=ARRAY   Only post generic support' . PHP_EOL .
		"\t" . '                                                           messages when data from repo-meta API' . PHP_EOL .
		"\t" . '                                                           matches the criteria specified here.' . PHP_EOL .
		"\t" . '                                                           See README.md for usage.' . PHP_EOL .
		PHP_EOL .
		'Support level configuration:' . PHP_EOL .
		"\t" . '--set-support-level-label=BOOL       Whether to attach support level labels to pull requests.' . PHP_EOL .
		"\t" . '                                     Will fetch information on support levels from repo-meta API.' . PHP_EOL .
		"\t" . '--set-support-level-label-prefix=STRING    Prefix to use for support level labels. Should be longer than five letters.' . PHP_EOL .
		"\t" . '--set-support-level-field=STRING     Field in responses from repo-meta API which we use to extract support level.' . PHP_EOL .
		PHP_EOL .
		'Repo meta API configuration:' . PHP_EOL .
		"\t" . '--repo-meta-api-base-url=STRING      Base URL to repo-meta API, containing support level and other' . PHP_EOL .
		"\t" . '                                     information.' . PHP_EOL .
		"\t" . '--repo-meta-api-user-id=STRING       Authentication detail for the repo-meta API.' . PHP_EOL .
		"\t" . '--repo-meta-api-access-token=STRING  Access token for the repo-meta API.' . PHP_EOL .
		PHP_EOL .
		'IRC API configuration:' . PHP_EOL .
		"\t" . '--irc-api-url=STRING           URL to IRC API to send messages.' . PHP_EOL .
		"\t" . '--irc-api-token=STRING         Access-token to use to communicate with the IRC' . PHP_EOL .
		"\t" . '                               API.' . PHP_EOL .
		"\t" . '--irc-api-bot=STRING           Name for the bot which is supposed to send the IRC' . PHP_EOL .
		"\t" . '                               messages.' . PHP_EOL .
		"\t" . '--irc-api-room=STRING          Name for the chatroom to which the IRC messages should' . PHP_EOL .
		"\t" . '                               be sent.' . PHP_EOL .
		PHP_EOL .
		'Pixel API configuration:' . PHP_EOL .
		"\t" . '--pixel-api-url=STRING             URL to Pixel API.' . PHP_EOL .
		"\t" . '--pixel-api-groupprefix=STRING     Group to use when sending statistics to Pixel API.' . PHP_EOL;
}

/**
 * Returns options supported.
 *
 * @return array Recognized options.
 */
function vipgoci_options_recognized() :array {
	return array(

		/*
		 * General configuration.
		 */
		'help',
		'version',
		'debug-level:',
		'max-exec-time:',
		'enforce-https-urls:',
		'skip-draft-prs:',
		'branches-ignore:',
		'local-git-repo:',
		'skip-large-files:',
		'skip-large-files-limit:',

		/*
		 * Environmental & repo configuration.
		 */
		'env-options:',
		'repo-options:',
		'repo-options-allowed:',

		/*
		 * GitHub configuration.
		 */
		'repo-owner:',
		'repo-name:',
		'commit:',
		'token:',

		/*
		 * PHP Linting configuration.
		 */
		'lint:',
		'lint-skip-folders:',
		'lint-skip-folders-in-repo-options-file:',
		'lint-modified-files-only:',
		'lint-php-version-paths:',
		'lint-php-versions:',

		/*
		 * PHPCS configuration
		 */
		'phpcs:',
		'phpcs-php-path:',
		'phpcs-path:',
		'phpcs-standard:',
		'phpcs-severity:',
		'phpcs-sniffs-include:',
		'phpcs-sniffs-exclude:',
		'phpcs-runtime-set:',
		'phpcs-skip-scanning-via-labels-allowed:',
		'phpcs-skip-folders:',
		'phpcs-skip-folders-in-repo-options-file:',
		'output:',

		/*
		 * SVG scanning configuration
		 */
		'svg-checks:',
		'svg-php-path:',
		'svg-scanner-path:',

		/*
		 * Auto approve configuration
		 */
		'autoapprove:',
		'autoapprove-filetypes:',
		'autoapprove-php-nonfunctional-changes:',
		'autoapprove-label:',

		/*
		 * Hashes API configuration
		 */
		'hashes-api:',
		'hashes-api-url:',
		'hashes-oauth-token:',
		'hashes-oauth-token-secret:',
		'hashes-oauth-consumer-key:',
		'hashes-oauth-consumer-secret:',

		/*
		 * GitHub reviews & generic comments configuration
		 */
		'report-no-issues-found:',
		'review-comments-sort:',
		'review-comments-max:',
		'review-comments-total-max:',
		'review-comments-ignore:',
		'review-comments-include-severity:',
		'dismiss-stale-reviews:',
		'dismissed-reviews-repost-comments:',
		'dismissed-reviews-exclude-reviews-from-team:',
		'informational-msg:',
		'scan-details-msg-include:',

		/*
		 * Generic support comments configuration
		 */
		'post-generic-pr-support-comments:',
		'post-generic-pr-support-comments-on-drafts:',
		'post-generic-pr-support-comments-string:',
		'post-generic-pr-support-comments-skip-if-label-exists:',
		'post-generic-pr-support-comments-branches:',
		'post-generic-pr-support-comments-repo-meta-match:',

		/*
		 * Support level configuration
		 */
		'set-support-level-label:',
		'set-support-level-label-prefix:',
		'set-support-level-field:',

		/*
		 * Repo meta API configuration.
		 */
		'repo-meta-api-base-url:',
		'repo-meta-api-user-id:',
		'repo-meta-api-access-token:',

		/*
		 * IRC API configuration.
		 */
		'irc-api-url:',
		'irc-api-token:',
		'irc-api-bot:',
		'irc-api-room:',

		/*
		 * Pixel API configuration.
		 */
		'pixel-api-url:',
		'pixel-api-groupprefix:',
	);
}

/**
 * Determine exit status.
 *
 * If any 'error'-type issues were submitted to
 * GitHub return with a non-zero exit-code. Same
 * if any files were skipped.
 *
 * If we submitted nothing or only warnings, and
 * no files were skipped, return with zero.
 *
 * @param array $results Array with results from scanning, etc.
 *
 * @return int Exit status as determined from $results.
 */
function vipgoci_exit_status( array $results ) :int {
	foreach (
		array_keys(
			$results['stats']
		)
		as $stats_type
	) {
		if (
			( ! isset( $results['stats'][ $stats_type ] ) ) ||
			( null === $results['stats'][ $stats_type ] )
		) {
			// In case the type of scan was not performed, skip.
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
				// Some errors were found, return non-zero.
				return VIPGOCI_EXIT_CODE_ISSUES;
			}
		}
	}

	if ( ! empty( $results['skipped-files'] ) ) {
		foreach ( $results['skipped-files'] as $pr_number ) {
			if ( 0 < $pr_number['total'] ) {
				// Results contains skipped files due issues, return non-zero.
				return VIPGOCI_EXIT_CODE_ISSUES;
			}
		}
	}

	return 0;
}

/**
 * Process the --env-options option,
 * and read options from environment
 * as determined by the option.
 *
 * @param array $options            Array of options.
 * @param array $options_recognized Array of recognized options by the program.
 *
 * @return void
 */
function vipgoci_run_env_options_handle(
	array &$options,
	array $options_recognized
) :void {
	vipgoci_option_array_handle(
		$options,
		'env-options',
		array(),
		null,
		',',
		false
	);

	/*
	 * Try to read options from
	 * environmental variables.
	 */
	vipgoci_options_read_env(
		$options,
		$options_recognized
	);
}

/**
 * Process --max-exec-time option.
 *
 * @param array $options Array of options.
 *
 * @return void
 */
function vipgoci_run_init_options_max_exec_time( array &$options ) :void {
	vipgoci_option_integer_handle(
		$options,
		'max-exec-time',
		0,
		null
	);

	if ( 0 > $options['max-exec-time'] ) {
		vipgoci_sysexit(
			'Invalid value for --max-exec-time; must be positive',
			array(
				'max-exec-time' => $options['max-exec-time'],
			),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}
}

/**
 * Process PHPCS related options, such as --phpcs-path.
 *
 * @param array $options Array of options.
 *
 * @return void
 */
function vipgoci_run_init_options_phpcs( array &$options ) :void {
	/*
	 * Handle boolean options related to PHPCS
	 */
	vipgoci_option_bool_handle( $options, 'phpcs', 'false' );

	vipgoci_option_bool_handle( $options, 'phpcs-skip-folders-in-repo-options-file', 'false' );

	vipgoci_option_bool_handle( $options, 'phpcs-skip-scanning-via-labels-allowed', 'false' );

	/*
	 * This variable is not configurable, is internal only.
	 */
	$options['phpcs-standard-file'] = false;

	/*
	 * Process --phpcs-php-path if to do PHPCS scan --
	 * expected to be a file, default value is 'php'
	 * (then relies on $PATH).
	 */
	if ( true === $options['phpcs'] ) {
		vipgoci_option_file_handle(
			$options,
			'phpcs-php-path',
			'php'
		);
	} else {
		$options['phpcs-php-path'] = null;
	}

	/*
	 * Check --phpcs-path if to do PHPCS
	 * scanning, otherwise set to null.
	 */
	if ( true === $options['phpcs'] ) {
		/*
		 * Process --phpcs-path -- expected to
		 * be a file.
		 */
		vipgoci_option_file_handle(
			$options,
			'phpcs-path',
			null
		);
	} else {
		$options['phpcs-path'] = null;
	}

	/*
	 * Process --phpcs-standard -- expected to be
	 * a string
	 */
	if ( empty( $options['phpcs-standard'] ) ) {
		$options['phpcs-standard'] = array(
			'WordPress',
		);
	} else {
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
	} else {
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
	} else {
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

	/*
	 * Process --phpcs-skip-folders -- expected to be an
	 * array of values.
	 */
	vipgoci_option_skip_folder_handle(
		$options,
		'phpcs-skip-folders'
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
}

/**
 * Clean up from PHPCS customizations.
 *
 * @param array $options Array of options.
 *
 * @return void
 */
function vipgoci_run_cleanup_phpcs( array &$options ) :void {
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
}

/**
 * Process SVG options.
 *
 * @param array $options Array of options.
 *
 * @return void
 */
function vipgoci_run_init_options_svg( array &$options ) :void {
	/*
	 * Process --svg-checks and --svg-scanner-path -- former expected
	 * to be a boolean, the latter a file-path.
	 */
	vipgoci_option_bool_handle( $options, 'svg-checks', 'false' );

	/*
	 * Process --svg-php-path if to do SVG scan --
	 * expected to be a file, default value is 'php'
	 * (then relies on $PATH).
	 */
	if ( true === $options['svg-checks'] ) {
		vipgoci_option_file_handle(
			$options,
			'svg-php-path',
			'php'
		);
	} else {
		$options['svg-php-path'] = null;
	}

	/*
	 * If --svg-checks is set to true,
	 * check if a sensible scanning-tool is specified.
	 *
	 * If not set to true, set a null value.
	 */
	if ( true === $options['svg-checks'] ) {
		vipgoci_option_file_handle(
			$options,
			'svg-scanner-path',
			null
		);
	} else {
		$options['svg-scanner-path'] = null;
	}
}

/**
 * Process auto-approve options.
 *
 * @param array $options Array of options.
 *
 * @return void
 */
function vipgoci_run_init_options_autoapprove( array &$options ) :void {
	/*
	 * Process --autoapprove and --autoapprove-php-nonfunctional-changes
	 * boolean options.
	 */
	vipgoci_option_bool_handle( $options, 'autoapprove', 'false' );

	vipgoci_option_bool_handle( $options, 'autoapprove-php-nonfunctional-changes', 'false' );

	/*
	 * Process --autoapprove-filetypes, array option.
	 *
	 * Values will be converted to lowercase.
	 */
	vipgoci_option_array_handle(
		$options,
		'autoapprove-filetypes',
		array(),
		'php'
	);

	/*
	 * Process --autoapprove-label. Set to boolean
	 * false if not specified, otherwise string containing
	 * label.
	 */

	if ( empty( $options['autoapprove-label'] ) ) {
		$options['autoapprove-label'] = false;
	} else {
		$options['autoapprove-label'] = trim(
			$options['autoapprove-label']
		);
	}

	/*
	 * Sanity check, ensure that if we auto-approve,
	 * filetypes and a label are specified.
	 */
	if (
		( true === $options['autoapprove'] ) &&
		(
			( empty( $options['autoapprove-filetypes'] ) ) ||
			( false === $options['autoapprove-label'] )
		)
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
	 * More sanity checking; ensure PHP and JS files cannot
	 * be specified for auto-approval.
	 */
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
			array(),
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
		( in_array(
			'svg',
			$options['autoapprove-filetypes'],
			true
		) )
	) {
		vipgoci_sysexit(
			'SVG files cannot be auto-approved on file-type basis, as they ' .
				'can contain problematic code. Use --svg-checks=true to ' .
				'allow auto-approval of SVG files',
			array(),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}
}

/**
 * Process hashes-to-hashes options.
 *
 * @param array $options                Array of options.
 * @param array $hashes_oauth_arguments OAuth 1.0a options for hashes-to-hashes API.
 *
 * @return void
 *
 * @codeCoverageIgnore
 */
function vipgoci_run_init_options_hashes_options(
	array &$options,
	array $hashes_oauth_arguments
) :void {
	/*
	 * Process --hashes-api -- expected to be a boolean.
	 */
	vipgoci_option_bool_handle( $options, 'hashes-api', 'false' );

	if (! $options['hashes-api']) {
		return;
	}

	/*
	 * Process --hashes-api-url -- expected to
	 * be an URL to a webservice.
	 */
	vipgoci_option_url_handle( $options, 'hashes-api-url', null );

	$options['hashes-api-url'] = trim(
		$options['hashes-api-url'] ?? '',
		'/'
	);

	/*
	 * Sanity check: Can only use --hashes-api=true with a URL
	 * configured.
	 */
	if (
		( true === $options['hashes-api'] ) &&
		( empty( $options['hashes-api-url'] ) )
	) {
		vipgoci_sysexit(
			'Cannot run with --hashes-api set to true and without --hashes-api-url set',
			array(),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}

	/*
	 * Process hashes-oauth arguments
	 */
	foreach ( $hashes_oauth_arguments as $tmp_key ) {
		if ( ! isset( $options[ $tmp_key ] ) ) {
			vipgoci_sysexit(
				'Asking to use --hashes-api-url without --hashes-oauth-* parameters, but that is not possible, as authorization is needed for hashes-to-hashes API',
				array(),
				VIPGOCI_EXIT_USAGE_ERROR
			);
		}

		$options[ $tmp_key ] = trim(
			$options[ $tmp_key ]
		);

		unset( $tmp_key );
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
}

/**
 * Sanity-checks for hashes-to-hashes options.
 *
 * @param array $options Array of options.
 *
 * @return void
 */
function vipgoci_run_init_options_autoapprove_hashes_overlap(
	array &$options
) :void {
	/*
	 * Do sanity-checking with --autoapprove parameter
	 * and --hashes-api-url parameter.
	 */
	if (
		( isset( $options['hashes-api-url'] ) ) &&
		( false === $options['autoapprove'] )
	) {
		vipgoci_sysexit(
			'Asking to use --hashes-api-url without --autoapproval set to true, but for hashes-to-hashes functionality to be useful, --autoapprove must be enabled. Otherwise the functionality will not really be used',
			array(),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}
}

/**
 * Set options relating to GitHub reviews
 *
 * @param array $options Array of options.
 *
 * @return void
 */
function vipgoci_run_init_options_reviews( array &$options ) :void {
	/*
	 * Process --report-no-issues-found
	 */
	vipgoci_option_bool_handle(
		$options,
		'report-no-issues-found',
		'true'
	);

	/*
	 * Process --review-comments-sort -- determines if to sort review comments by severity.
	 * Also process --review-comments-include-severity -- will include severity in comments.
	 */
	vipgoci_option_bool_handle(
		$options,
		'review-comments-sort',
		'false'
	);

	vipgoci_option_bool_handle(
		$options,
		'review-comments-include-severity',
		'false'
	);

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
	 * posted to GitHub pull request Reviews -- from
	 * 0 to 500. 0 means unlimited.
	 */
	vipgoci_option_integer_handle(
		$options,
		'review-comments-total-max',
		200,
		range( 0, 500, 1 )
	);

	/*
	 * Process --review-comments-ignore -- expected
	 * to be an array (items separated by "|||").
	 */

	vipgoci_option_array_handle(
		$options,
		'review-comments-ignore',
		array(),
		array(),
		'|||'
	);

	// Transform to lower case, remove leading and ending whitespacing, and "." or "," at the end.
	$options['review-comments-ignore'] = array_map(
		'vipgoci_results_standardize_ignorable_message',
		$options['review-comments-ignore']
	);

	/*
	 * Handle --dismiss-stale-reviews and --dismissed-reviews-repost-comments --
	 * both boolean parameters.
	 */
	vipgoci_option_bool_handle( $options, 'dismiss-stale-reviews', 'false' );

	vipgoci_option_bool_handle( $options, 'dismissed-reviews-repost-comments', 'true' );

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
	 * Check if the teams specified in the
	 * --dismissed-reviews-exclude-reviews-from-team parameter are
	 * really valid, etc.
	 */
	vipgoci_option_teams_handle(
		$options,
		'dismissed-reviews-exclude-reviews-from-team'
	);

	/*
	 * Process --scan-details-msg-include
	 */
	vipgoci_option_bool_handle(
		$options,
		'scan-details-msg-include',
		'true'
	);
}


/**
 * Set options relating to skipping large files.
 *
 * @param array $options Array of options.
 *
 * @return void
 */
function vipgoci_run_init_options_skip_large_files( array &$options ) :void {
	vipgoci_option_bool_handle(
		$options,
		'skip-large-files',
		'true'
	);

	vipgoci_option_integer_handle(
		$options,
		'skip-large-files-limit',
		VIPGOCI_VALIDATION_MAXIMUM_LINES_LIMIT
	);

}

/**
 * Set options relating to PHP linting.
 *
 * @param array $options Array of options.
 *
 * @return void
 */
function vipgoci_run_init_options_lint( array &$options ) :void {
	vipgoci_option_bool_handle(
		$options,
		'lint',
		'true'
	);

	vipgoci_option_bool_handle(
		$options,
		'lint-modified-files-only',
		'true'
	);

	vipgoci_option_bool_handle(
		$options,
		'lint-skip-folders-in-repo-options-file',
		'false'
	);

	/*
	 * Handle --lint-skip-folders
	 */
	vipgoci_option_skip_folder_handle(
		$options,
		'lint-skip-folders'
	);

	/*
	 * Process --lint-php-versions and --lint-php-version-paths
	 * if to do PHP linting.
	 */
	if ( false === $options['lint'] ) {
		$options['lint-php-versions']      = null;
		$options['lint-php-version-paths'] = null;
	} else {
		vipgoci_option_array_handle(
			$options,
			'lint-php-versions',
			array(),
			array(),
			',',
			false
		);

		if ( empty( $options['lint-php-versions'] ) ) {
			vipgoci_sysexit(
				'--lint-php-versions is empty and --lint option is set to true. Must define at least one PHP version for linting',
				array(),
				VIPGOCI_EXIT_USAGE_ERROR
			);
		}

		vipgoci_option_array_handle(
			$options,
			'lint-php-version-paths',
			array(),
			array(),
			',',
			false
		);

		if ( empty( $options['lint-php-version-paths'] ) ) {
			vipgoci_sysexit(
				'--lint-php-version-paths is empty and --lint option is set to true. Must define at least one path to PHP interpreter',
				array(),
				VIPGOCI_EXIT_USAGE_ERROR
			);
		}

		/*
		 * Verify --lint-php-version-paths option.
		 */
		$tmp_new_lint_php_version_paths = array();

		$tmp_php_paths_versions_seen = array();

		foreach (
			$options['lint-php-version-paths'] as
				$tmp_php_version_path
		) {
			$tmp_version_to_path_arr = explode(
				':',
				$tmp_php_version_path
			);

			if ( 2 !== count( $tmp_version_to_path_arr ) ) {
				vipgoci_sysexit(
					'Invalid formatting of option --lint-php-version-paths',
					array(
						'lint-php-version-path-invalid' => $tmp_php_version_path,
					),
					VIPGOCI_EXIT_USAGE_ERROR
				);
			}

			if ( false === is_numeric( $tmp_version_to_path_arr[0] ) ) {
				vipgoci_sysexit(
					'Invalid formatting of option --lint-php-version-paths; version must be numeric',
					array(
						'lint-php-version-path-invalid' => $tmp_version_to_path_arr[0],
					),
					VIPGOCI_EXIT_USAGE_ERROR
				);
			}

			if ( false === is_file( $tmp_version_to_path_arr[1] ) ) {
				vipgoci_sysexit(
					'Option --lint-php-version-paths points to a non-existing file',
					array(
						'php-version-key'   => $tmp_version_to_path_arr[0],
						'path-not-existing' => $tmp_version_to_path_arr[1],
					),
					VIPGOCI_EXIT_USAGE_ERROR
				);
			}

			if ( true === in_array(
				$tmp_version_to_path_arr[0],
				$tmp_php_paths_versions_seen,
				true
			) ) {
				vipgoci_sysexit(
					'Option --lint-php-version-paths contains duplicate PHP version key',
					array(
						'lint-php-version-duplicate' => $tmp_version_to_path_arr[0],
					),
					VIPGOCI_EXIT_USAGE_ERROR
				);
			} else {
				$tmp_php_paths_versions_seen[] = $tmp_version_to_path_arr[0];
			}

			/*
			 * Check if the PHP interpreter specified is actually
			 * of correct version (version X.Y only, not X.Y.Z.).
			 */
			$tmp_lint_php_interpreter_version = vipgoci_util_php_interpreter_get_version(
				$tmp_version_to_path_arr[1]
			);

			if ( null === $tmp_lint_php_interpreter_version ) {
				vipgoci_sysexit(
					'Unable to get PHP interpreter when parsing option --lint-php-version-paths',
					array(
						'lint-php-interpreter-path' => $tmp_version_to_path_arr[1],
					),
					VIPGOCI_EXIT_USAGE_ERROR
				);
			}

			if ( 0 !== strpos(
				$tmp_lint_php_interpreter_version,
				$tmp_version_to_path_arr[0]
			) ) {
				vipgoci_sysexit(
					'Option --lint-php-version-paths refers to PHP interpreter that is not of the version specified',
					array(
						'version-defined'            => $tmp_version_to_path_arr[0],
						'actual-interpreter-version' => $tmp_lint_php_interpreter_version,
					),
					VIPGOCI_EXIT_USAGE_ERROR
				);
			}

			$tmp_new_lint_php_version_paths[ $tmp_version_to_path_arr[0] ] =
				$tmp_version_to_path_arr[1];
		}

		$options['lint-php-version-paths'] = $tmp_new_lint_php_version_paths;
		unset( $tmp_new_lint_php_version_paths );
		unset( $tmp_version_to_path_arr );

		/*
		 * Verify --lint-php-versions option.
		 */
		foreach (
			$options['lint-php-versions'] as
				$tmp_lint_php_version
		) {
			if ( ! isset(
				$options['lint-php-version-paths'][ $tmp_lint_php_version ]
			) ) {
				vipgoci_sysexit(
					'Option --lint-php-versions refers to PHP version not defined in --lint-php-version-paths',
					array(
						'version-not-defined' => $tmp_lint_php_version,
						'versions-defined'    => array_keys( $options['lint-php-version-paths'] ),
					),
					VIPGOCI_EXIT_USAGE_ERROR
				);
			}
		}

		unset( $tmp_lint_php_version );
		unset( $tmp_lint_php_interpreter_version );
	}
}

/**
 * Set options relating to generic PR support comments.
 *
 * @param array $options Array of options.
 *
 * @return void
 */
function vipgoci_run_init_options_post_generic_pr_support_comments( array &$options ) :void {
	/*
	 * Handle parameters that enable posting of support-comments
	 * to pull requests.
	 */
	vipgoci_option_bool_handle(
		$options,
		'post-generic-pr-support-comments',
		'false'
	);

	/*
	 * Process options relating to submitting support comments to PRs.
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
	 * If not to post generic support comments, return as
	 * no more processing is required.
	 */
	if ( false === $options['post-generic-pr-support-comments'] ) {
		return;
	}

	/*
	 * Check if options relating to Generic Support Messages
	 * (--post-generic-pr-support-comments*) are consistent.
	 */
	foreach (
		array(
			'post-generic-pr-support-comments-on-drafts',
			'post-generic-pr-support-comments-string',
			'post-generic-pr-support-comments-branches',
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
				),
				VIPGOCI_EXIT_USAGE_ERROR
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
			),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}

	/*
	 * Check if all keys are consistent in
	 * the --post-generic-pr-support-comments-* parameters.
	 */
	$tmp_option_keys = null;

	foreach (
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

		foreach (
			$tmp_option_keys as
				$tmp_option_key
		) {
			if ( ! isset(
				$options[ $tmp_option_name ][ $tmp_option_key ]
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
					),
					VIPGOCI_EXIT_USAGE_ERROR
				);
			}
		}
	}
}


/**
 * Do sanity checks relating to git repository.
 *
 * @param array $options Array of options.
 *
 * @return void
 *
 * @codeCoverageIgnore
 */
function vipgoci_run_init_options_git_repo( array &$options ): void {
	/*
	 * Handle --local-git-repo parameter
	 */

	$options['local-git-repo'] = rtrim(
		$options['local-git-repo'],
		'/'
	);

	/*
	 * Check if the repository seems to
	 * be in good condition.
	 */
	vipgoci_gitrepo_ok(
		$options['commit'],
		$options['local-git-repo']
	);
}

/**
 * Check if the GitHub token provided is valid by
 * getting info relating to the current user.
 *
 * @param array $options Array of options.
 *
 * @return void
 *
 * @codeCoverageIgnore
 */
function vipgoci_run_init_github_token_option( array &$options ) :void {
	/*
	 * Ask GitHub about information about
	 * the user the token belongs to
	 */
	$current_user_info = vipgoci_github_authenticated_user_get(
		$options['token']
	);

	/*
	 * Check if the returned information
	 * looks good.
	 */
	if (
		( false === $current_user_info ) ||
		( ! isset( $current_user_info->login ) ) ||
		( empty( $current_user_info->login ) )
	) {
		vipgoci_sysexit(
			'Unable to get information about token-holder user from GitHub',
			array(),
			VIPGOCI_EXIT_GITHUB_PROBLEM
		);
	} else {
		vipgoci_log(
			'Got information about token-holder user from GitHub',
			array(
				'login'    => $current_user_info->login,
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
}

/**
 * Do sanity checks with --output parameter.
 *
 * @param array $options Array of options.
 *
 * @return void
 *
 * @codeCoverageIgnore
 */
function vipgoci_run_init_options_output( array &$options ) :void {
	/*
	 * If --output is not used, return
	 * as no further processing is required.
	 */
	if ( empty( $options['output'] ) ) {
		return;
	}

	/*
	 * Check if the --output parameter looks
	 * good.
	 */
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
	 * Try writing empty string to it.
	 */
	$res = @file_put_contents( // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$options['output'],
		'',
		FILE_APPEND
	);

	if ( false === $res ) {
		vipgoci_sysexit(
			'Unable to write to file specified in --output.',
			array(
				'output' => print_r( $options['output'], true ),
			),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}

	/*
	 * File should exist by now.
	 */
	if ( ! is_file( $options['output'] ) ) {
		vipgoci_sysexit(
			'The file specified in --output argument is invalid.',
			array(
				'output' => $options['output'],
			),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}
}

/**
 * Sanitize and do sanity checks with IRC parameters.
 *
 * @param array $options Array of options.
 *
 * @return void
 *
 * @codeCoverageIgnore
 */
function vipgoci_run_init_options_irc( array &$options ) :void {
	/*
	 * Handle IRC API parameters
	 */

	$irc_params_defined = 0;

	foreach ( array(
		'irc-api-url',
		'irc-api-token',
		'irc-api-bot',
		'irc-api-room',
	) as $irc_api_param ) {
		if ( isset( $options[ $irc_api_param ] ) ) {
			$options[ $irc_api_param ] = trim(
				$options[ $irc_api_param ]
			);

			$irc_params_defined++;
		}
	}

	vipgoci_option_url_handle(
		$options,
		'irc-api-url',
		null
	);

	if (
		( $irc_params_defined > 0 ) &&
		( 4 !== $irc_params_defined )
	) {
		vipgoci_sysexit(
			'Some IRC API parameters defined but not all; all must be defined to be useful',
			array(),
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

}

/**
 * Send messages in IRC queue.
 *
 * @param array $options Array of options.
 *
 * @return void
 *
 * @codeCoverageIgnore
 */
function vipgoci_run_cleanup_irc( array &$options ) :void {
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
	} else {
		vipgoci_log(
			'Did not send alerts to IRC, due to missing configuration parameter'
		);
	}

	/*
	 * Note: vipgoci_irc_api_alerts_send() is called
	 * from shutdown function.
	 */
}

/**
 * Set support level label options.
 *
 * @param array $options Array of options.
 *
 * @return void
 */
function vipgoci_run_init_options_set_support_level_label(
	array &$options
) :void {
	/*
	 * Handle option for setting support
	 * labels. Handle prefix and field too.
	 */

	vipgoci_option_bool_handle(
		$options,
		'set-support-level-label',
		'false'
	);

	if (
		( isset( $options['set-support-level-label-prefix'] ) ) &&
		( strlen( $options['set-support-level-label-prefix'] ) > 5 )
	) {
		$options['set-support-level-label-prefix'] = trim(
			$options['set-support-level-label-prefix']
		);
	} else {
		$options['set-support-level-label-prefix'] = null;
	}

	if (
		( isset( $options['set-support-level-field'] ) ) &&
		( strlen( $options['set-support-level-field'] ) > 1 )
	) {
		$options['set-support-level-field'] = trim(
			$options['set-support-level-field']
		);
	} else {
		$options['set-support-level-field'] = null;
	}
}

/**
 * Set repo-meta API options.
 *
 * @param array $options Array of options.
 *
 * @return void
 */
function vipgoci_run_init_options_repo_meta_api( array &$options ) :void {
	/*
	 * Handle options for repo-meta API.
	 */
	vipgoci_option_url_handle(
		$options,
		'repo-meta-api-base-url',
		null
	);

	if ( isset( $options['repo-meta-api-user-id'] ) ) {
		vipgoci_option_integer_handle(
			$options,
			'repo-meta-api-user-id',
			0
		);
	} else {
		$options['repo-meta-api-user-id'] = null;
	}

	if ( isset(
		$options['repo-meta-api-access-token']
	) ) {
		$options['repo-meta-api-access-token'] = trim(
			$options['repo-meta-api-access-token']
		);
	} else {
		$options['repo-meta-api-access-token'] = null;
	}

	vipgoci_options_sensitive_clean(
		null,
		array(
			'repo-meta-api-access-token',
		)
	);
}

/**
 * Set options relating to Pixel API.
 *
 * @param array $options Array of options.
 *
 * @return void
 */
function vipgoci_run_init_options_pixel_api( array &$options ) :void {
	/*
	 * Handle settings for the Pixel API.
	 */
	vipgoci_option_url_handle(
		$options,
		'pixel-api-url',
		null
	);

	if ( isset( $options['pixel-api-groupprefix'] ) ) {
		$options['pixel-api-groupprefix'] = trim(
			$options['pixel-api-groupprefix']
		);
	}
}

/**
 * Send information to Pixel API.
 *
 * @param array $options        Array of options.
 * @param array $counter_report Array with counter statistics.
 *
 * @return void
 *
 * @codeCoverageIgnore
 */
function vipgoci_run_cleanup_send_pixel_api(
	array &$options,
	array $counter_report
) :void {
	/*
	 * Actually send statistics if configured
	 * to do so.
	 */
	if (
		( ! empty( $options['pixel-api-url'] ) ) &&
		( ! empty( $options['pixel-api-groupprefix'] ) )
	) {
		vipgoci_send_stats_to_pixel_api(
			$options['pixel-api-url'],
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
					'github_api_request_delete',
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
					'github_pr_lint_issues',
				),
			),
			$counter_report
		);
	} else {
		vipgoci_log(
			'Not sending data to pixel API due to missing configuration options'
		);
	}
}

/**
 * Process --debug-level option.
 *
 * @param array $options Array of options.
 *
 * @return void
 */
function vipgoci_run_init_options_debug( array &$options ) :void {
	global $vipgoci_debug_level;

	vipgoci_option_integer_handle(
		$options,
		'debug-level',
		0,
		array( 0, 1, 2 )
	);

	// Set the value to global.
	$vipgoci_debug_level = $options['debug-level'];
}

/**
 * Handle --repo-options option and related options.
 *
 * @param array $options Array of options.
 *
 * @return void
 */
function vipgoci_run_init_options_repo_options( array &$options ):void {
	vipgoci_option_bool_handle( $options, 'repo-options', 'false' );

	/*
	 * Certain options are configurable via
	 * options-file in the repository. Specify those
	 * options here.
	 */
	$repo_options_read_repo_file_arr = array(
		'autoapprove'                           => array(
			'type'         => 'boolean',
			'valid_values' => array( true, false ),
		),

		'autoapprove-php-nonfunctional-changes' => array(
			'type'         => 'boolean',
			'valid_values' => array( true, false ),
		),

		'hashes-api'                            => array(
			'type'         => 'boolean',
			'valid_values' => array( true, false ),
		),

		'lint-modified-files-only'              => array(
			'type'         => 'boolean',
			'valid_values' => array( true, false ),
		),

		'phpcs'                                 => array(
			'type'         => 'boolean',
			'valid_values' => array( true, false ),
		),

		'phpcs-severity'                        => array(
			'type'         => 'integer',
			'valid_values' => array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ),
		),

		'phpcs-sniffs-include'                  => array(
			'type'         => 'array',
			'append'       => true,
			'valid_values' => null,
		),

		'phpcs-sniffs-exclude'                  => array(
			'type'         => 'array',
			'append'       => true,
			'valid_values' => null,
		),

		'post-generic-pr-support-comments'      => array(
			'type'         => 'boolean',
			'valid_values' => array( true, false ),
		),

		'report-no-issues-found'                => array(
			'type'         => 'boolean',
			'valid_values' => array( true, false ),
		),

		'review-comments-include-severity'      => array(
			'type'         => 'boolean',
			'valid_values' => array( true, false ),
		),

		'review-comments-sort'                  => array(
			'type'         => 'boolean',
			'valid_values' => array( true, false ),
		),

		'scan-details-msg-include'              => array(
			'type'         => 'boolean',
			'valid_values' => array( true, false ),
		),

		'skip-draft-prs'                        => array(
			'type'         => 'boolean',
			'valid_values' => array( true, false ),
		),

		'skip-execution'                        => array(
			'type'         => 'boolean',
			'valid_values' => array( true, false ),
		),

		'svg-checks'                            => array(
			'type'         => 'boolean',
			'valid_values' => array( true, false ),
		),
	);

	/*
	 * Handle --repo-options-allowed parameter
	 */
	$repo_options_allowed_arr = array_keys(
		$repo_options_read_repo_file_arr
	);

	vipgoci_option_array_handle(
		$options,
		'repo-options-allowed',
		$repo_options_allowed_arr
	);

	/*
	 * Check if any values specified for --repo-options-allowed are invalid.
	 */
	if ( ! empty(
		array_diff(
			$options['repo-options-allowed'],
			$repo_options_allowed_arr
		)
	) ) {
		vipgoci_sysexit(
			'Invalid value specified for --repo-options-allowed',
			array(
				'allowed_values'   => $repo_options_allowed_arr,
				'specified_values' => $options['repo-options-allowed'],
			),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}

	vipgoci_options_read_repo_file(
		$options,
		VIPGOCI_OPTIONS_FILE_NAME,
		$repo_options_read_repo_file_arr
	);
}

/**
 * Process options by calling more specialized functions
 * that deal with each group of options.
 *
 * @param array $options            Array of options.
 * @param array $options_recognized Array of recognized options by the program.
 *
 * @return void
 *
 * @codeCoverageIgnore
 */
function vipgoci_run_init_options(
	array &$options,
	array $options_recognized
):void {
	$hashes_oauth_arguments =
		array(
			'hashes-oauth-token',
			'hashes-oauth-token-secret',
			'hashes-oauth-consumer-key',
			'hashes-oauth-consumer-secret',
		);

	/*
	 * Handle --enforce-https-urls absolutely first,
	 * as that is used in processing parameters expecting
	 * URLs.
	 */
	vipgoci_option_bool_handle( $options, 'enforce-https-urls', 'true' );

	/*
	 * This variable is not to be configurable on the command-line,
	 * only via options-file.
	 */
	$options['skip-execution'] = false;

	/*
	 * Read options from environment, if configured to do so.
	 */
	vipgoci_run_env_options_handle( $options, $options_recognized );

	/*
	 * Handle boolean parameters not handled by specialized functions.
	 */
	vipgoci_option_bool_handle( $options, 'skip-draft-prs', 'false' );

	/*
	 * Process the --branches-ignore parameter,
	 * -- expected to be an array
	 */
	vipgoci_option_array_handle(
		$options,
		'branches-ignore',
		array()
	);

	// Validate args.
	if (
		( ! isset( $options['repo-owner'] ) ) ||
		( empty( $options['repo-owner'] ) ) ||
		( ! isset( $options['repo-name'] ) ) ||
		( empty( $options['repo-name'] ) ) ||
		( ! isset( $options['commit'] ) ) ||
		( empty( $options['commit'] ) ) ||
		( ! isset( $options['token'] ) ) ||
		( empty( $options['token'] ) ) ||
		( ! isset( $options['local-git-repo'] ) ) ||
		( empty( $options['local-git-repo'] ) )
	) {
		vipgoci_help_print();
		exit( VIPGOCI_EXIT_USAGE_ERROR );
	}

	// Set debug option.
	vipgoci_run_init_options_debug( $options );

	// Set options relating to maximum execution time.
	vipgoci_run_init_options_max_exec_time( $options );

	// Ensure that the GitHub token is valid.
	vipgoci_run_init_github_token_option( $options );

	// Set options relating to PHP linting.
	vipgoci_run_init_options_lint( $options );

	// Set options relating to PHCPS.
	vipgoci_run_init_options_phpcs( $options );

	// Process autoapprove options.
	vipgoci_run_init_options_autoapprove( $options );

	// Process hashes-to-hashes options.
	vipgoci_run_init_options_hashes_options(
		$options,
		$hashes_oauth_arguments
	);

	// Set SVG options.
	vipgoci_run_init_options_svg( $options );

	// Set git repository options.
	vipgoci_run_init_options_git_repo( $options );

	// Set options relating to skipping large files.
	vipgoci_run_init_options_skip_large_files( $options );

	// Set options relating to GitHub reviews.
	vipgoci_run_init_options_reviews( $options );

	// Set options relating to generic PR support comments.
	vipgoci_run_init_options_post_generic_pr_support_comments( $options );

	// Set options relating to support level labels.
	vipgoci_run_init_options_set_support_level_label( $options );

	// Set options relating to the repo-meta API.
	vipgoci_run_init_options_repo_meta_api( $options );

	// Set IRC options.
	vipgoci_run_init_options_irc( $options );

	// Set Pixel API options.
	vipgoci_run_init_options_pixel_api( $options );

	// Check --output option.
	vipgoci_run_init_options_output( $options );

	/*
	 * Process autoapprove and hashes-to-hashes
	 * options that overlap.
	 */
	vipgoci_run_init_options_autoapprove_hashes_overlap( $options );

	/*
	 * Handle --repo-options and related parameters.
	 */
	vipgoci_run_init_options_repo_options( $options );

	if (
		( false === $options['lint'] ) &&
		( false === $options['phpcs'] )
	) {
		vipgoci_sysexit(
			'Both --lint and --phpcs set to false, cannot continue.',
			array(),
			VIPGOCI_EXIT_USAGE_ERROR
		);
	}

	/*
	 * Folders to skip from PHPCS or PHP Linting
	 * can be read from a config-file in the
	 * repository. Read this here and set in
	 * options.
	 */
	vipgoci_options_read_repo_skip_files( $options );

	/*
	 * Register shutdown function.
	 */
	register_shutdown_function( 'vipgoci_shutdown_function', $options );
}

/**
 * Set maximum execution time based on configuration.
 *
 * @param array $options      Array of options.
 * @param int   $startup_time Startup time in UNIX seconds.
 *
 * @return void
 *
 * @codeCoverageIgnore
 */
function vipgoci_run_scan_max_exec_time(
	array &$options,
	int $startup_time
) :void {
	/*
	 * Set maximum execution time if
	 * configured to do so.
	 */
	if ( 0 < $options['max-exec-time'] ) {
		/*
		 * Set max execution time, minus the
		 * time already spent.
		 */
		$tmp_runtime = time() - $startup_time;

		vipgoci_set_maximum_exec_time(
			$options['max-exec-time'] - $tmp_runtime,
			VIPGOCI_GITHUB_WEB_BASE_URL . '/' .
				rawurlencode( $options['repo-owner'] ) . '/' .
				rawurlencode( $options['repo-name'] ) . '/' .
				'commit/' .
				rawurlencode( $options['commit'] )
		);

		unset( $tmp_runtime );
	}
}

/**
 * Skip execution if configured to do so.
 *
 * @param array $options Array of options.
 *
 * @return void
 */
function vipgoci_run_scan_skip_execution( array &$options ) :void {
	/*
	 * If asked not to scan, don't scan then.
	 */
	if ( true === $options['skip-execution'] ) {
		vipgoci_sysexit(
			'Skipping scanning entirely, as determined ' .
				'by configuration',
			array(),
			VIPGOCI_EXIT_NORMAL
		);
	}
}

/**
 * Find pull requests implicated by the commit specified in $options.
 *
 * @param array $options Array of options.
 *
 * @return array Pull requests found.
 *
 * @codeCoverageIgnore
 */
function vipgoci_run_scan_find_prs( array &$options ) :array {
	/*
	 * Try to get PRs. Will retry if fails at first attempt. We do this as
	 * sometimes GitHub API does not invalidate its cache
	 * within the first seconds even though PR has been opened.
	 */
	$prs_implicated = vipgoci_github_prs_implicated_with_retries(
		$options['repo-owner'],
		$options['repo-name'],
		$options['commit'],
		$options['token'],
		$options['branches-ignore'],
		$options['skip-draft-prs'],
		2, // Attempt to get PRs maximum twice.
		30 // Sleep time between attempts.
	);

	/*
	 * If no pull requests are implicated by this commit,
	 * bail now, as there is no point in continuing running.
	 */
	if ( empty( $prs_implicated ) ) {
		vipgoci_sysexit(
			'Skipping scanning entirely, as the commit ' .
				'is not a part of any pull request',
			array(
				'repo_owner' => $options['repo-owner'],
				'repo_name'  => $options['repo-name'],
				'commit'     => $options['commit'],
			),
			VIPGOCI_EXIT_COMMIT_NOT_PART_OF_PR,
			true
		);
	}

	vipgoci_log(
		'Found implicated pull requests',
		array(
			'prs_implicated' => array_keys( $prs_implicated ),
		)
	);

	return $prs_implicated;
}

/**
 * Make sure we are working with the latest
 * commit with each implicated PR.
 *
 * If we detect that we are only performing linting,
 * and the commit is not the latest, skip linting
 * as it becomes useless if this is not the
 * latest commit: There is no use in linting
 * an obsolete commit.
 *
 * @param array $options        Array of options.
 * @param array $prs_implicated Pull requests implicated.
 *
 * @return void
 *
 * @codeCoverageIgnore
 */
function vipgoci_run_scan_check_latest_commit(
	array &$options,
	array $prs_implicated
) :void {
	/*
	 * Loop through each pull request.
	 */
	foreach ( $prs_implicated as $pr_item ) {
		$commits_list = vipgoci_github_prs_commits_list(
			$options['repo-owner'],
			$options['repo-name'],
			$pr_item->number,
			$options['token']
		);

		// Found commits, do verification.
		if ( ! empty( $commits_list ) ) {
			// Reverse array, so we get the last commit first.
			$commits_list = array_reverse( $commits_list );

			/*
			 * If latest commit to the PR things look good,
			 * can continue.
			 */
			if ( $commits_list[0] === $options['commit'] ) {
				continue;
			}
		}

		/*
		 * At this point, we have found an inconsistency;
		 * the commit we are working with is not the latest
		 * to the pull request, and we have to deal with that.
		 */

		if (
			( true === $options['lint'] ) &&
			( false === $options['phpcs'] )
		) {
			vipgoci_sysexit(
				'The current commit is not the latest one ' .
					'to the pull request, skipping ' .
					'linting, and not doing PHPCS ' .
					'-- nothing to do',
				array(
					'repo_owner' => $options['repo-owner'],
					'repo_name'  => $options['repo-name'],
					'pr_number'  => $pr_item->number,
				),
				VIPGOCI_EXIT_NORMAL
			);
		} elseif (
			( true === $options['lint'] ) &&
			( true === $options['phpcs'] )
		) {
			// Skip linting, useless if not latest commit.
			$options['lint'] = false;

			vipgoci_log(
				'The current commit is not the latest ' .
					'one to the pull request, ' .
					'skipping linting',
				array(
					'repo_owner' => $options['repo-owner'],
					'repo_name'  => $options['repo-name'],
					'pr_number'  => $pr_item->number,
				)
			);
		}

		/*
		 * As for lint === false && true === phpcs,
		 * we do not care, as then we will not be linting.
		 */
	}
}


/**
 * Get all events on dismissed reviews
 * from members of the specified team(s),
 * by pull request.
 *
 * @param array $options        Array of options.
 * @param array $prs_implicated Pull requests implicated.
 *
 * @return array Events related to dismissed reviews.
 *
 * @codeCoverageIgnore
 */
function vipgoci_run_scan_fetch_prs_reviews(
	array &$options,
	array $prs_implicated
) :array {
	/*
	 * Start with getting team members.
	 */
	$team_members_ids_arr = vipgoci_github_team_members_many_get(
		$options['token'],
		$options['repo-owner'],
		$options['dismissed-reviews-exclude-reviews-from-team']
	);

	/*
	 * If we have any team member's logins,
	 * and we are to dismiss reviews excluding
	 * those submitted by a particular team, get
	 * the pull request events generated by the team.
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
			'Fetched list of pull request reviews dismissed by members of a team',
			array(
				'team_members'              => $team_members_ids_arr,
				'reviews_dismissed_by_team' => $prs_events_dismissed_by_team,
			)
		);
	}

	unset( $team_members_ids_arr );

	return $prs_events_dismissed_by_team;
}

/**
 * Log if we skipped any large files.
 *
 * @param array $options        Array of options.
 * @param array $results        Results array.
 * @param array $prs_implicated Pull requests implicated.
 *
 * @return void
 */
function vipgoci_run_scan_log_skipped_files(
	array &$options,
	array &$results,
	array $prs_implicated
) :void {
	/*
	 * Loop through each pull request.
	 */
	foreach ( $prs_implicated as $pr_item ) {
		if ( ! empty(
			$results[ VIPGOCI_SKIPPED_FILES ][ $pr_item->number ]['issues']
		) ) {
			/*
			 * Log if any files were skipped.
			 */
			$log_url = VIPGOCI_GITHUB_WEB_BASE_URL . '/' .
				$options['repo-owner'] . '/' .
				$options['repo-name'] . '/' .
				'pull/' .
				$pr_item->number;

			vipgoci_log(
				'Too large file(s) was/were detected during analysis: ' .
					$log_url,
				array(
					'repo_owner' => $options['repo-owner'],
					'repo_name'  => $options['repo-name'],
					'pr_number'  => $pr_item->number,
				),
				0,
				true
			);
		}
	}
}

/**
 * Dismiss any reviews with only non-active comments.
 *
 * @param array $options        Array of options.
 * @param array $prs_implicated Pull requests implicated.
 *
 * @return void
 *
 * @codeCoverageIgnore
 */
function vipgoci_run_scan_dismiss_stale_reviews(
	array &$options,
	array $prs_implicated
) :void {

	if ( true !== $options['dismiss-stale-reviews'] ) {
		// Not configured to dismiss stale reviews, return early.
		return;
	}

	/*
	 * Dismiss any reviews that contain *only*
	 * inactive comments -- i.e. comments that
	 * are obsolete as the code has been changed.
	 *
	 */

	foreach ( $prs_implicated as $pr_item ) {
		vipgoci_github_pr_reviews_dismiss_with_non_active_comments(
			$options,
			$pr_item->number
		);
	}
}

/**
 * Limit number of issues in $results.
 * If set to zero, this is skipped.
 *
 * @param array $options Array of options.
 * @param array $results Results array.
 *
 * @return array PRs with maximum number of
 *               comments reached.
 *
 * @codeCoverageIgnore
 */
function vipgoci_run_scan_reviews_comments_enforce_maximum(
	array &$options,
	array &$results
) :array {
	$prs_comments_maxed = array();

	if ( 0 < $options['review-comments-total-max'] ) {
		vipgoci_results_filter_comments_to_max(
			$options,
			$results,
			$prs_comments_maxed
		);
	}

	return $prs_comments_maxed;
}

/**
 * If we reached maximum number of
 * comments earlier, put a message out
 * so people actually know about it.
 *
 * @param array $options            Array of options.
 * @param array $prs_comments_maxed Array of PRs with maximum number of
 *                                  comments reached.
 * @return void
 */
function vipgoci_run_scan_total_comments_max_warning_post(
	array &$options,
	array $prs_comments_maxed
) :void {
	if ( 0 < $options['review-comments-total-max'] ) {
		foreach ( array_keys(
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
}

/**
 * Run scan, call more specialized functions
 * that do the actual work.
 *
 * @param array      $options        Array of options.
 * @param array      $results        Results array.
 * @param array|null $prs_implicated Pull requests implicated.
 * @param int        $startup_time   Start up time, in UNIX seconds.
 *
 * @return void
 *
 * @codeCoverageIgnore
 */
function vipgoci_run_scan(
	array &$options,
	array &$results,
	?array &$prs_implicated,
	int $startup_time
) :void {
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
			),
		),
	);

	// Quit here if to skip execution (configured via config file).
	vipgoci_run_scan_skip_execution( $options );

	// Enforce maximum execution time from now on.
	vipgoci_run_scan_max_exec_time( $options, $startup_time );

	// Find PRs relating to the commit we are processing.
	$prs_implicated = vipgoci_run_scan_find_prs( $options );

	// Log to IRC URLs to PRs implicated.
	$prs_urls = vipgoci_github_prs_urls_get(
		$prs_implicated,
		' -- '
	);

	vipgoci_log(
		'Starting scanning PRs; ' . $prs_urls,
		array(
			'repo-owner' => $options['repo-owner'],
			'repo-name'  => $options['repo-name'],
		),
		0,
		true // Log to IRC.
	);

	// Check that each PR has the commit specified as the latest one.
	vipgoci_run_scan_check_latest_commit(
		$options,
		$prs_implicated
	);

	/*
	 * Init statistics.
	 */
	vipgoci_stats_init(
		$options,
		$prs_implicated,
		$results
	);

	/*
	 * Clean up old comments made by us previously
	 * if applicable.
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
			VIPGOCI_NO_ISSUES_FOUND_MSG_AND_NO_REVIEWS,
			VIPGOCI_NO_ISSUES_FOUND_MSG_AND_EXISTING_REVIEWS,
			VIPGOCI_LINT_FAILED_MSG_START,
			VIPGOCI_PHPCS_SCAN_FAILED_MSG_START,
			VIPGOCI_OUT_OF_MEMORY_ERROR,
		)
	);

	/*
	 * If configured to do so, post a generic comment
	 * on the pull request(s) with some helpful information.
	 * Comment is set via option.
	 */
	vipgoci_report_submit_pr_generic_support_comment(
		$options,
		$prs_implicated
	);

	/*
	 * Add support level label, if:
	 * - configured to do so
	 * - data is available in repo-meta API
	 */
	vipgoci_support_level_label_set( $options );

	if ( true === $options['phpcs'] ) {
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
		vipgoci_phpcs_possibly_use_new_standard_file( $options );
	}

	/*
	 * Now run all checks requested and store the
	 * results in an array.
	 */

	// Start with linting if configured to do so.
	if ( true === $options['lint'] ) {
		vipgoci_lint_scan_commit(
			$options,
			$results['issues'],
			$results['stats'][ VIPGOCI_STATS_LINT ],
			$results[ VIPGOCI_SKIPPED_FILES ]
		);

		// Reduce memory usage as possible.
		gc_collect_cycles();
	}

	// Next PHPCS scan if configured to do so.
	if ( true === $options['phpcs'] ) {
		vipgoci_phpcs_scan_commit(
			$options,
			$results['issues'],
			$results['stats'][ VIPGOCI_STATS_PHPCS ],
			$results[ VIPGOCI_SKIPPED_FILES ]
		);

		gc_collect_cycles();
	}

	/*
	 * If to do auto-approvals, then do so now.
	 * Will start by collecting what files are auto-
	 * approvable.
	 *
	 * Will also remove from $results any files
	 * auto-approved in hashes-to-hashes API.
	 */
	vipgoci_auto_approval_process(
		$options,
		$results
	);

	gc_collect_cycles();

	/*
	 * Remove comments from $results that have
	 * already been submitted.
	 */
	$prs_events_dismissed_by_team = vipgoci_run_scan_fetch_prs_reviews(
		$options,
		$prs_implicated
	);

	vipgoci_results_remove_existing_github_comments(
		$options,
		$prs_implicated,
		$results,
		$options['dismissed-reviews-repost-comments'],
		$prs_events_dismissed_by_team
	);

	/*
	 * Sort issues by severity level, so that
	 * highest severity is first (if configured in this way).
	 */
	vipgoci_results_sort_by_severity(
		$options,
		$results
	);

	/*
	 * Remove ignorable comments from $results.
	 */
	if ( ! empty( $options['review-comments-ignore'] ) ) {
		vipgoci_results_filter_ignorable(
			$options,
			$results
		);
	}

	// Keep record of how many issues we found.
	vipgoci_counter_update_with_issues_found( $results );

	/*
	 * Limit number of issues in $results.
	 */
	$prs_comments_maxed = vipgoci_run_scan_reviews_comments_enforce_maximum(
		$options,
		$results
	);

	if ( true === $options['scan-details-msg-include'] ) {
		// Construct scan details message.
		$scan_details_msg = vipgoci_report_create_scan_details(
			vipgoci_options_sensitive_clean( $options )
		);
	} else {
		$scan_details_msg = '';
	}

	gc_collect_cycles();

	/*
	 * Submit any remaining issues to GitHub
	 */
	vipgoci_report_submit_pr_generic_comment_from_results(
		$options['repo-owner'],
		$options['repo-name'],
		$options['token'],
		$options['commit'],
		$results,
		$options['informational-msg'],
		$scan_details_msg
	);

	vipgoci_report_submit_pr_review_from_results(
		$options['repo-owner'],
		$options['repo-name'],
		$options['token'],
		$options['commit'],
		$results,
		$options['informational-msg'],
		$scan_details_msg,
		$options['review-comments-max'],
		$options['review-comments-include-severity'],
		$options['skip-large-files-limit']
	);

	/*
	 * Dismiss stale reviews, i.e. reviews
	 * submitted by us which have no active
	 * comments.
	 */
	vipgoci_run_scan_dismiss_stale_reviews(
		$options,
		$prs_implicated
	);

	/*
	 * If we reached maximum number of
	 * comments earlier, put a message out
	 * so people actually know it.
	 */
	vipgoci_run_scan_total_comments_max_warning_post(
		$options,
		$prs_comments_maxed
	);

	/*
	 * If no issues found and configured to do so,
	 * report this to the pull requests implicated.
	 */
	if ( true === $options['report-no-issues-found'] ) {
		vipgoci_report_maybe_no_issues_found(
			$options['repo-owner'],
			$options['repo-name'],
			$options['token'],
			$options['commit'],
			$prs_implicated,
			$options['informational-msg'],
			$scan_details_msg
		);
	}

	/*
	 * Log to IRC when files are skipped.
	 */
	vipgoci_run_scan_log_skipped_files(
		$options,
		$results,
		$prs_implicated
	);
}

/**
 * Do initial checks at startup.
 *
 * @return void
 *
 * @codeCoverageIgnore
 */
function vipgoci_run_checks() :void {
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
	 * Require minimum PHP version or later.
	 */
	if ( 0 > version_compare(
		phpversion(),
		VIPGOCI_PHP_VERSION_MINIMUM
	) ) {
		vipgoci_sysexit(
			'Error: PHP ' . VIPGOCI_PHP_VERSION_MINIMUM . ' is required as a minimum.',
			array(),
			VIPGOCI_EXIT_SYSTEM_PROBLEM
		);
	}

	/*
	 * Require minimum git version
	 */
	if ( 0 > version_compare(
		vipgoci_git_version(),
		VIPGOCI_GIT_VERSION_MINIMUM
	) ) {
		vipgoci_sysexit(
			'Error: git version ' . VIPGOCI_GIT_VERSION_MINIMUM . ' is required as a minimum.',
			array(),
			VIPGOCI_EXIT_SYSTEM_PROBLEM
		);
	}

	vipgoci_log(
		'Minimum system and enviromental requirements passed',
		array(
			'uid'         => posix_getuid(),
			'php_version' => phpversion(),
			'git_version' => vipgoci_git_version(),
		)
	);
}

/**
 * Set some variables and return as array.
 *
 * @return array Initial values of some critical variables.
 */
function vipgoci_run_init_vars() :array {
	global $vipgoci_debug_level;

	// Set with a temp value for now, user value set later.
	$vipgoci_debug_level = 0;

	$startup_time = time();

	$results = array(
		'issues' => array(),

		'stats'  => array(
			VIPGOCI_STATS_PHPCS      => null,
			VIPGOCI_STATS_LINT       => null,
			VIPGOCI_STATS_HASHES_API => null,
		),
	);

	$options_recognized = vipgoci_options_recognized();

	/*
	 * Try to read options from command-line parameters.
	 */
	$options = getopt(
		'',
		$options_recognized
	);

	$prs_implicated = null;

	return array(
		$startup_time,
		$results,
		$options,
		$options_recognized,
		$prs_implicated,
	);
}

/**
 * Main invocation function.
 *
 * @return int To-be exit status of program.
 *
 * @codeCoverageIgnore
 */
function vipgoci_run() :int {
	// Set memory limit to 400MB.
	ini_set( 'memory_limit', '400M' ); // phpcs:ignore WordPress.PHP.IniSet.memory_limit_Blacklisted

	/*
	 * Assign a few variables.
	 */
	list(
		$startup_time,
		$results,
		$options,
		$options_recognized,
		$prs_implicated
	) = vipgoci_run_init_vars();

	/*
	 * Check if these option parameters are
	 * present before continuing; if so, perform
	 * the actions appropriate and then exit.
	 */
	if ( isset( $options['version'] ) ) {
		// Version number requested; print and exit.
		echo VIPGOCI_VERSION . PHP_EOL;

		exit( 0 );
	} elseif ( isset( $options['help'] ) ) {
		// Print help message and exit.
		vipgoci_help_print();

		exit( 0 );
	}

	// Do basic system check before continuing.
	vipgoci_run_checks();

	// Clear the internal cache before doing anything.
	vipgoci_cache( VIPGOCI_CACHE_CLEAR );

	vipgoci_log(
		'Initializing...',
		array(
			'debug_info' => array(
				'vipgoci_version' => VIPGOCI_VERSION,
				'php_version'     => phpversion(),
				'hostname'        => gethostname(),
				'php_uname'       => php_uname(),
				'git_version'     => vipgoci_git_version(),
			),
		)
	);

	// Configure PHP error reporting.
	vipgoci_set_php_error_reporting();

	// Process options parameters.
	vipgoci_run_init_options( $options, $options_recognized );

	// Reduce memory usage as possible.
	gc_collect_cycles();

	// Run scans.
	vipgoci_run_scan( $options, $results, $prs_implicated, $startup_time );

	/*
	 * At this point, we have started to prepare
	 * for shutdown and exit -- no review-critical
	 * actions should be performed after this point.
	 */

	// Cleanup after PHPCS.
	vipgoci_run_cleanup_phpcs( $options );

	// Clear IRC queue.
	vipgoci_run_cleanup_irc( $options );

	// Collect GitHub rate-limit usage info.
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

	// Send statistics to external service.
	vipgoci_run_cleanup_send_pixel_api(
		$options,
		$counter_report
	);

	/*
	 * Final logging before quitting.
	 */
	vipgoci_log(
		'Shutting down',
		array(
			'vipgoci_version'       => VIPGOCI_VERSION,
			'repo_owner'            => $options['repo-owner'],
			'repo_name'             => $options['repo-name'],
			'pr_numbers'            => array_column( $prs_implicated, 'number' ),
			'run_time_seconds'      => time() - $startup_time,
			'run_time_measurements' =>
				vipgoci_round_array_items(
					vipgoci_runtime_measure(
						VIPGOCI_RUNTIME_DUMP,
						null
					),
					4,
					PHP_ROUND_HALF_UP
				),
			'counters_report'       => $counter_report,

			'github_api_rate_limit' =>
				$github_api_rate_limit_usage->resources->core,

			'results'               => $results,
		),
		0,
		true // Log to IRC.
	);

	/*
	 * Determine exit code.
	 */
	return vipgoci_exit_status(
		$results
	);
}

/**
 * Shutdown function. Handle out of memory
 * situations, clear IRC queue.
 *
 * @param array $options Options array for the program.
 *
 * @return void
 */
function vipgoci_shutdown_function(
	array $options
) :void {
	/*
	 * Get last PHP error, if any.
	 */
	$error_last = error_get_last();

	if (
		( null !== $error_last ) &&
		( E_ERROR === $error_last['type'] ) &&
		( str_contains( $error_last['message'], 'Allowed memory size' ) )
	) {
		vipgoci_log(
			'Ran out of memory during execution, exiting',
			array(
				'repo-owner' => $options['repo-owner'],
				'repo-name'  => $options['repo-name'],
				'commit-id'  => $options['commit'],
			),
			0,
			true // Log to IRC.
		);

		/*
		 * Post generic message indicating
		 * resource constraints issue to each
		 * pull request implicated.
		 */
		$prs_implicated = vipgoci_github_prs_implicated(
			$options['repo-owner'],
			$options['repo-name'],
			$options['commit'],
			$options['token'],
			$options['branches-ignore'],
			$options['skip-draft-prs'],
			false
		);

		foreach ( $prs_implicated as $pr_item ) {
			vipgoci_github_pr_comments_generic_submit(
				$options['repo-owner'],
				$options['repo-name'],
				$options['token'],
				$pr_item->number,
				VIPGOCI_OUT_OF_MEMORY_ERROR,
				$options['commit']
			);
		}
	}

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
		vipgoci_irc_api_alerts_send(
			$options['irc-api-url'],
			$options['irc-api-token'],
			$options['irc-api-bot'],
			$options['irc-api-room']
		);
	}
}

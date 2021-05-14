<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class PhpcsScanValidateSniffsInOptionAndReportTest extends TestCase {
	var $options_phpcs = array(
		'phpcs-path'
			=> null,

		'phpcs-standard'
			=> null,

		'phpcs-validate-sniffs-and-report-include-commit'
			=> null,

		'phpcs-validate-sniffs-and-report-include-invalid'
			=> null,

		'phpcs-validate-sniffs-and-report-include-valid'
			=> null,

		'phpcs-validate-sniffs-and-report-exclude-commit'
			=> null,

		'phpcs-validate-sniffs-and-report-exclude-invalid'
			=> null,

		'phpcs-validate-sniffs-and-report-exclude-valid'
			=> null,
	);

	var $options_git = array(
		'repo-owner'	=> null,
		'repo-name'	=> null,
	);

	var $current_user_info = null;

	protected function setUp(): void {
		vipgoci_unittests_get_config_values(
			'phpcs-scan',
			$this->options_phpcs
		);

		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		$this->options = array_merge(
			$this->options_phpcs,
			$this->options_git
		);

		$this->options[ 'github-token' ] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);

		$this->options['token'] =
			$this->options['github-token'];

		$this->options['branches-ignore'] = array();

		$this->options['phpcs-sniffs-include'] = array();
		$this->options['phpcs-sniffs-exclude'] = array();

		foreach(
			array(
				'phpcs-validate-sniffs-and-report-include-valid',
				'phpcs-validate-sniffs-and-report-include-invalid',
				'phpcs-validate-sniffs-and-report-exclude-valid',
				'phpcs-validate-sniffs-and-report-exclude-invalid',
			) as $array_key
		) {
			$this->options[ $array_key ] = explode(
				',',
				$this->options[ $array_key ]
			);
		}

		$this->options['phpcs-validate-sniffs-and-report-include-valid-and-invalid'] = array_merge(
			$this->options['phpcs-validate-sniffs-and-report-include-valid'],
			$this->options['phpcs-validate-sniffs-and-report-include-invalid']
		);
	
		$this->options['phpcs-validate-sniffs-and-report-exclude-valid-and-invalid'] = array_merge(
			$this->options['phpcs-validate-sniffs-and-report-exclude-valid'],
			$this->options['phpcs-validate-sniffs-and-report-exclude-invalid']
		);

		/*
		 * Get info about token holder.
		 */
		if (
			( empty( $this->current_user_info ) ) &&
			( ! empty( $this->options['github-token'] ) )
		) {
			vipgoci_unittests_output_suppress();

			$this->current_user_info = vipgoci_github_authenticated_user_get(
				$this->options['github-token']
			);

			vipgoci_unittests_output_unsuppress();
		}

		$this->options['skip-draft-prs'] = false;
	}

	protected function tearDown(): void {
		$this->options_phpcs = null;
		$this->options_git = null;
		$this->options = null;
	}

	/**
	 * @covers ::vipgoci_phpcs_validate_sniffs_in_options_and_report
	 */
	public function testPhpcsValidateIncludeSniffsWithErrors() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['phpcs-validate-sniffs-and-report-include-commit'];

		$this->options['phpcs-sniffs-include'] =
			$this->options['phpcs-validate-sniffs-and-report-include-valid-and-invalid'];

		/*
		 * Begin with getting PRs implicated,
		 * check if we have at least one.
		 */
		vipgoci_unittests_output_suppress();

		$prs_implicated = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['github-token'],
			$this->options['branches-ignore']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertGreaterThanOrEqual(
			1,
			count( $prs_implicated )
		);

		/*
		 * For each Pull-Request, check
		 * that it has no comments.
		 */
		foreach( $prs_implicated as $pr_item ) {
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			$this->assertSame(
				0,
				count( array_keys( $pr_comments ) )
			);
		}

		/*
		 * Run sniff validation and report --
		 * it should post a generic comment.
		 */

		vipgoci_unittests_output_suppress();

		vipgoci_phpcs_validate_sniffs_in_options_and_report(
			$this->options
		);

		vipgoci_unittests_output_unsuppress();

		/*
		 * For each Pull-Request implicated,
		 * there should be at least one generic
		 * comment about invalid sniffs. Check
		 * those are in place and remove.
		 */

		$this->assertGreaterThanOrEqual(
			1,
			count( $prs_implicated )
		);

		foreach( $prs_implicated as $pr_item ) {
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			$this->assertGreaterThanOrEqual(
				1,
				count( array_keys( $pr_comments ) )
			);

			$removed_comments = 0;

			foreach ( $pr_comments as $pr_comment ) {
				// Make sure it is from the current token holder
				if ( $pr_comment->user->login !== $this->current_user_info->login ) {
					continue;
				}

				// Check if the comment is about invalid sniffs
				if ( strpos(
					$pr_comment->body,
					VIPGOCI_PHPCS_INVALID_SNIFFS
				) !== 0 ) {
					continue;
				}

				// Check if at least one invalid sniff-name is found in the comment
				if ( strpos(
					$pr_comment->body,
					$this->options['phpcs-validate-sniffs-and-report-include-invalid'][0]
				) === false ) {
					continue;
				}


				vipgoci_unittests_output_suppress();

				// Remove comment, submitted by us, is comment about invalid sniffs.
				vipgoci_github_pr_generic_comment_delete(
					$this->options['repo-owner'],
					$this->options['repo-name'],
					$this->options['github-token'],
					$pr_comment->id
				);

				vipgoci_unittests_output_unsuppress();

				$removed_comments++;
			}

			// Make sure we removed one comment
			$this->assertSame(
				1,
				$removed_comments
			);
		}

		$this->assertSame(
			$this->options['phpcs-validate-sniffs-and-report-include-valid'],
			$this->options['phpcs-sniffs-include']
		);
	}

	/**
	 * @covers ::vipgoci_phpcs_validate_sniffs_in_options_and_report
	 */
	public function testPhpcsValidateIncludeSniffsNoErrors() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['phpcs-validate-sniffs-and-report-include-commit'];

		$this->options['phpcs-sniffs-include'] =
			$this->options['phpcs-validate-sniffs-and-report-include-valid'];

		/*
		 * Begin with getting PRs implicated,
		 * check if we have at least one.
		 */
		vipgoci_unittests_output_suppress();

		$prs_implicated = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['github-token'],
			$this->options['branches-ignore']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertGreaterThanOrEqual(
			1,
			count( $prs_implicated )
		);

		/*
		 * For each Pull-Request, check
		 * that it has no comments.
		 */
		foreach( $prs_implicated as $pr_item ) {
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			$this->assertSame(
				0,
				count( array_keys( $pr_comments ) )
			);
		}

		/*
		 * Run sniff validation and report --
		 * it should post a generic comment.
		 */

		vipgoci_unittests_output_suppress();

		vipgoci_phpcs_validate_sniffs_in_options_and_report(
			$this->options
		);

		vipgoci_unittests_output_unsuppress();

		/*
		 * For each Pull-Request implicated,
		 * there should be no generic comments
		 * comment about invalid sniffs.
		 */

		$this->assertGreaterThanOrEqual(
			1,
			count( $prs_implicated )
		);

		foreach( $prs_implicated as $pr_item ) {
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			$this->assertSame(
				0,
				count( array_keys( $pr_comments ) )
			);
		}

		$this->assertSame(
			$this->options['phpcs-validate-sniffs-and-report-include-valid'],
			$this->options['phpcs-sniffs-include']
		);
	}

	/**
	 * @covers ::vipgoci_phpcs_validate_sniffs_in_options_and_report
	 */
	public function testPhpcsValidateExcludeSniffsWithErrors() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['phpcs-validate-sniffs-and-report-exclude-commit'];

		$this->options['phpcs-sniffs-exclude'] =
			$this->options['phpcs-validate-sniffs-and-report-exclude-valid-and-invalid'];

		/*
		 * Begin with getting PRs implicated,
		 * check if we have at least one.
		 */
		vipgoci_unittests_output_suppress();

		$prs_implicated = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['github-token'],
			$this->options['branches-ignore']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertGreaterThanOrEqual(
			1,
			count( $prs_implicated )
		);

		/*
		 * For each Pull-Request, check
		 * that it has no comments.
		 */
		foreach( $prs_implicated as $pr_item ) {
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			$this->assertSame(
				0,
				count( array_keys( $pr_comments ) )
			);
		}

		/*
		 * Run sniff validation and report --
		 * it should post a generic comment.
		 */

		vipgoci_unittests_output_suppress();

		vipgoci_phpcs_validate_sniffs_in_options_and_report(
			$this->options
		);

		vipgoci_unittests_output_unsuppress();

		/*
		 * For each Pull-Request implicated,
		 * there should be at least one generic
		 * comment about invalid sniffs. Check
		 * those are in place and remove.
		 */

		$this->assertGreaterThanOrEqual(
			1,
			count( $prs_implicated )
		);

		foreach( $prs_implicated as $pr_item ) {
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			$this->assertGreaterThanOrEqual(
				1,
				count( array_keys( $pr_comments ) )
			);

			$removed_comments = 0;

			foreach ( $pr_comments as $pr_comment ) {
				// Make sure it is from the current token holder
				if ( $pr_comment->user->login !== $this->current_user_info->login ) {
					continue;
				}

				// Check if the comment is about invalid sniffs
				if ( strpos(
					$pr_comment->body,
					VIPGOCI_PHPCS_INVALID_SNIFFS
				) !== 0 ) {
					continue;
				}

				// Check if at least one invalid sniff-name is found in the comment
				if ( strpos(
					$pr_comment->body,
					$this->options['phpcs-validate-sniffs-and-report-exclude-invalid'][0]
				) === false ) {
					continue;
				}


				vipgoci_unittests_output_suppress();

				// Remove comment, submitted by us, is comment about invalid sniffs.
				vipgoci_github_pr_generic_comment_delete(
					$this->options['repo-owner'],
					$this->options['repo-name'],
					$this->options['github-token'],
					$pr_comment->id
				);

				vipgoci_unittests_output_unsuppress();

				$removed_comments++;
			}

			// Make sure we removed one comment
			$this->assertSame(
				1,
				$removed_comments
			);
		}

		$this->assertSame(
			$this->options['phpcs-validate-sniffs-and-report-exclude-valid'],
			$this->options['phpcs-sniffs-exclude']
		);
	}

	/**
	 * @covers ::vipgoci_phpcs_validate_sniffs_in_options_and_report
	 */
	public function testPhpcsValidateExcludeSniffsNoErrors() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] =
			$this->options['phpcs-validate-sniffs-and-report-exclude-commit'];

		$this->options['phpcs-sniffs-exclude'] =
			$this->options['phpcs-validate-sniffs-and-report-exclude-valid'];

		/*
		 * Begin with getting PRs implicated,
		 * check if we have at least one.
		 */
		vipgoci_unittests_output_suppress();

		$prs_implicated = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['github-token'],
			$this->options['branches-ignore']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertGreaterThanOrEqual(
			1,
			count( $prs_implicated )
		);

		/*
		 * For each Pull-Request, check
		 * that it has no comments.
		 */
		foreach( $prs_implicated as $pr_item ) {
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			$this->assertSame(
				0,
				count( array_keys( $pr_comments ) )
			);
		}

		/*
		 * Run sniff validation and report --
		 * it should post a generic comment.
		 */

		vipgoci_unittests_output_suppress();

		vipgoci_phpcs_validate_sniffs_in_options_and_report(
			$this->options
		);

		vipgoci_unittests_output_unsuppress();

		/*
		 * For each Pull-Request implicated,
		 * there should be no generic comments
		 * comment about invalid sniffs.
		 */

		$this->assertGreaterThanOrEqual(
			1,
			count( $prs_implicated )
		);

		foreach( $prs_implicated as $pr_item ) {
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			$this->assertSame(
				0,
				count( array_keys( $pr_comments ) )
			);
		}

		$this->assertSame(
			$this->options['phpcs-validate-sniffs-and-report-exclude-valid'],
			$this->options['phpcs-sniffs-exclude']
		);
	}

	/*
	 * Get generic comments made to a Pull-Request
	 * from GitHub, uncached.
	 */
	protected function _getPrGenericComments(
		$pr_number
	) {
		$pr_comments_ret = array();

		$page = 1;
		$per_page = 100;

		do {
			$github_url =
				VIPGOCI_GITHUB_BASE_URL . '/' .
				'repos/' .
				rawurlencode( $this->options['repo-owner'] ) . '/' .
				rawurlencode( $this->options['repo-name'] ) . '/' .
				'issues/' .
				rawurlencode( $pr_number ) . '/' .
				'comments' .
				'?page=' . rawurlencode( $page ) . '&' .
				'per_page=' . rawurlencode( $per_page );


	                $pr_comments_raw = json_decode(
	                        vipgoci_github_fetch_url(
        	                        $github_url,
                	                $this->options['github-token']
                        	)
	                );

	                foreach ( $pr_comments_raw as $pr_comment ) {
	                        $pr_comments_ret[] = $pr_comment;
        	        }

	                $page++;
		} while ( count( $pr_comments_raw ) >= $per_page );

		return $pr_comments_ret;
	}
}

<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitHubPrReviewsCommentsGetByPrTest extends TestCase {
	var $options_git_repo_tests = array(
		'pr-test-github-pr-reviews-get-1'	=> null
	);

	var $options_git = array(
		'repo-owner'				=> null,
		'repo-name'				=> null,
	);

	protected function setUp(): void {
		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		vipgoci_unittests_get_config_values(
			'git-repo-tests',
			$this->options_git_repo_tests
		);

		$this->options = array_merge(
			$this->options_git,
			$this->options_git_repo_tests
		);

		$this->options[ 'github-token' ] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);

		$this->options['token'] =
			$this->options['github-token'];
	}

	protected function tearDown(): void {
		$this->options_git_repo_tests = null;
		$this->options_git = null;
		$this->options = null;
	}

	/**
	 * @covers ::vipgoci_github_pr_reviews_comments_get_by_pr
	 */
	public function testGitHubPrReviewsCommentsGetByPr1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$comments_actual = vipgoci_github_pr_reviews_comments_get_by_pr(
			$this->options,
			$this->options['pr-test-github-pr-reviews-get-1'],
			array()
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			1,
			count( $comments_actual )
		);

		$this->assertSame(
			264037556,
			$comments_actual[0]->id
		);


		$this->assertSame(
			'file1.php',
			$comments_actual[0]->path
		);

		$this->assertSame(
			3,
			$comments_actual[0]->position
		);

		$this->assertSame(
			'All output should be escaped.',
			$comments_actual[0]->body
		);
	}

	/**
	 * @covers ::vipgoci_github_pr_reviews_comments_get_by_pr
	 */
	public function testGitHubPrReviewsCommentsGetByPr2() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$comments_actual = vipgoci_github_pr_reviews_comments_get_by_pr(
			$this->options,
			$this->options['pr-test-github-pr-reviews-get-1'],
			array(
				'login'	=> 'gudmdharalds',
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			1,
			count( $comments_actual )
		);

		$this->assertSame(
			264037556,
			$comments_actual[0]->id
		);


		$this->assertSame(
			'file1.php',
			$comments_actual[0]->path
		);

		$this->assertSame(
			3,
			$comments_actual[0]->position
		);

		$this->assertSame(
			'All output should be escaped.',
			$comments_actual[0]->body
		);
	}

	/**
	 * @covers ::vipgoci_github_pr_reviews_comments_get_by_pr
	 */
	public function testGitHubPrReviewsCommentsGetByPr3() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$comments_actual = vipgoci_github_pr_reviews_comments_get_by_pr(
			$this->options,
			$this->options['pr-test-github-pr-reviews-get-1'],
			array(
				'login'	=> 'random_invalid_user___0x0',
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			0,
			count( $comments_actual )
		);
	}
}

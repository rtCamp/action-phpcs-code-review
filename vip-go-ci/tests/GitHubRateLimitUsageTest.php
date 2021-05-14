<?php

namespace Vipgoci\tests;

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

final class GitHubRateLimitUsageTest extends TestCase {
	protected function setUp(): void {
		$this->options = array();

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
		$this->options = null;
	}

	/**
	 * @covers ::vipgoci_github_rate_limit_usage
	 */
	public function testGitHubRateLimitUsage1 () {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$gh_result = vipgoci_github_rate_limit_usage(
			$this->options['github-token']
		);

		$this->assertTrue(
			isset(
				$gh_result->resources->core->used
			)
			&&
			(
				is_numeric( $gh_result->resources->core->used )
			)
		);
	
		$this->assertTrue(
			isset(
				$gh_result->resources->core->limit
			)
			&&
			(
				is_numeric( $gh_result->resources->core->limit )
			)
		);

		$this->assertTrue(
			isset(
				$gh_result->resources->core->remaining
			)
			&&
			(
				is_numeric( $gh_result->resources->core->remaining )
			)
		);
	}
}


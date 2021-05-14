<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class SupportLevelLabelMetaApiDataFetchTest extends TestCase {
	var $options_meta_api_secrets = array(
		'repo-meta-api-base-url'	=> null,
		'repo-meta-api-user-id'		=> null,
		'repo-meta-api-access-token'	=> null,

		'repo-owner'			=> null,
		'repo-name'			=> null,

		'support-level'			=> null,
		'support-level-field-name'	=> null,
	);

	protected function setUp(): void {
		vipgoci_unittests_get_config_values(
			'repo-meta-api-secrets',
			$this->options_meta_api_secrets,
			true
		);

		$this->options = $this->options_meta_api_secrets;
	}

	protected function tearDown(): void {
		$this->options_meta_api_secrets = null;
		$this->options = null;
	}

	/**
	 * @covers ::vipgoci_repo_meta_api_data_fetch
	 */
	public function testMetaApiDataFetch() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'repo-meta-api-user-id', 'repo-meta-api-access-token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$repo_meta_data = vipgoci_repo_meta_api_data_fetch(
			$this->options['repo-meta-api-base-url'],
			$this->options['repo-meta-api-user-id'],
			$this->options['repo-meta-api-access-token'],
			$this->options['repo-owner'],
			$this->options['repo-name']
		);

		$this->assertTrue(
			count(
				$repo_meta_data['data']
			) > 0
		);

		$this->assertTrue(
			( ! empty(
				$repo_meta_data['data'][0][
					$this->options['support-level-field-name']
				]
			) )
		);

		$this->assertSame(
			$this->options['support-level'],
			$repo_meta_data['data'][0][
				$this->options['support-level-field-name']
			]
		);

		/*
		 * Re-test due to caching.
		 */
		$repo_meta_data_2 = vipgoci_repo_meta_api_data_fetch(
			$this->options['repo-meta-api-base-url'],
			$this->options['repo-meta-api-user-id'],
			$this->options['repo-meta-api-access-token'],
			$this->options['repo-owner'],
			$this->options['repo-name']
		);

		$this->assertSame(
			$repo_meta_data,
			$repo_meta_data_2
		);
	}
}

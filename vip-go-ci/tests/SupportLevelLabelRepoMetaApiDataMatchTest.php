<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class SupportLevelLabelRepoMetaApiDataMatchTest extends TestCase {
	var $options_meta_api_secrets = array(
		'repo-meta-api-base-url'	=> null,
		'repo-meta-api-user-id'		=> null,
		'repo-meta-api-access-token'	=> null,

		'repo-name'			=> null,
		'repo-owner'			=> null,

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

		$this->options['data_match0'] = array(
		);

		$this->options['data_match1'] = array(
			2 => array(
				'__invalid_field' => array(
					'__somethinginvalid'
				),
			),

			3 => array(
				'invalid_field_761a' => array(
					'invalid_value',
				)
			)
		);

		$this->options['data_match2'] = array(
			2 => array(
				$this->options['support-level-field-name'] => array(
					$this->options['support-level']
				),
			),

			3 => array(
				'invalid_field_761a' => array(
					'invalid_value',
				)
			)
		);

		$this->options['branches-ignore'] = array();
	}

	protected function tearDown(): void {
		$this->options = null;
		$this->options_meta_api_secrets = null;
	}

	/**
	 * @covers ::vipgoci_repo_meta_api_data_match
	 */
	public function test_repo_meta_api_data_match1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'repo-meta-api-user-id', 'repo-meta-api-access-token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$option_key_no = null;

		$this->assertSame(
			false,

			vipgoci_repo_meta_api_data_match(
				$this->options,
				'',
				$option_key_no
			)
		);

		$this->assertSame(
			null,
			$option_key_no
		);
	}

	/**
	 * @covers ::vipgoci_repo_meta_api_data_match
	 */
	public function test_repo_meta_api_data_match2() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'repo-meta-api-user-id', 'repo-meta-api-access-token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$option_key_no = null;

		$this->assertSame(
			false,

			vipgoci_repo_meta_api_data_match(
				$this->options,
				'data_match0',
				$option_key_no
			)
		);

		$this->assertSame(
			null,
			$option_key_no
		);
	}

	/**
	 * @covers ::vipgoci_repo_meta_api_data_match
	 */
	public function test_repo_meta_api_data_match3() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'repo-meta-api-user-id', 'repo-meta-api-access-token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$option_key_no = null;

		$this->assertSame(
			false,

			vipgoci_repo_meta_api_data_match(
				$this->options,
				'data_match1',
				$option_key_no
			)
		);
	
		$this->assertSame(
			null,
			$option_key_no
		);
	}

	/**
	 * @covers ::vipgoci_repo_meta_api_data_match
	 */
	public function test_repo_meta_api_data_match4() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'repo-meta-api-user-id', 'repo-meta-api-access-token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$option_key_no = null;

		$this->assertSame(
			true,

			vipgoci_repo_meta_api_data_match(
				$this->options,
				'data_match2',
				$option_key_no
			)
		);

		$this->assertSame(
			2,
			$option_key_no
		);
	}
}

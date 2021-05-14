<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class VipgociOptionsTeamsTest extends TestCase {
	var $options = array(
		'github-token'	=> null,
		'team-id'	=> null,
		'team-slug'	=> null,
		'org-name'	=> null,
	);

	public function setUp(): void {
		foreach( $this->options as $option_key => $option_value ) {
			$this->options[ $option_key ] =
				vipgoci_unittests_get_config_value(
					'git-secrets',
					$option_key,
					true
				);
		}

		$this->options['repo-owner'] =
			$this->options['org-name'];

		$this->options['token'] =
			$this->options['github-token'];
	}

	public function tearDown(): void {
		$this->options = null;
	}

	/**
	 * @covers ::vipgoci_option_teams_handle
	 */
	public function testVipgociOptionTeams1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		if ( empty( $this->options ) ) {
			$this->markTestSkipped(
				'Must set up ' . __FUNCTION__ . '() test'
			);

			return;
		}


		$this->options['my-team-option'] = array(
			$this->options['team-id'],
			$this->options['team-slug']
		);

		vipgoci_unittests_output_suppress();

		vipgoci_option_teams_handle(
			$this->options,
			'my-team-option'
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertNotEmpty(
			$this->options['my-team-option'],
			'Got empty result from vipgoci_option_teams_handle()'
		);

		$this->assertTrue(
			count(
				$this->options['my-team-option']
			) > 0
		);

	}


	/**
	 * @covers ::vipgoci_option_teams_handle
	 */
	public function testVipgociOptionTeams2() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		if ( empty( $this->options ) ) {
			$this->markTestSkipped(
				'Must set up ' . __FUNCTION__ . '() test'
			);

			return;
		}

		$this->options['my-team-option'] = array(
			'IsInvalidteamId5000000XYZ',
		);

		vipgoci_unittests_output_suppress();

		vipgoci_option_teams_handle(
			$this->options,
			'my-team-option'
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertEmpty(
			$this->options['my-team-option'],
			'Got non-empty result from vipgoci_option_teams_handle()'
		);
	}
}

<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitHubTeamMembersManyTest extends TestCase {
	var $options = array(
		'github-token'	=> null,
		'team-id'	=> null,
		'team-slug'	=> null,
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
	}

	public function tearDown(): void {
		$this->options = null;
	}

	/**
	 * @covers ::vipgoci_github_team_members_many_get
	 */
	public function testTeamMembersMany1() {
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


		vipgoci_unittests_output_suppress();

		$team_members_res1_actual = vipgoci_github_team_members_many_get(
			$this->options['github-token'],
			array(
				$this->options['team-id'],
				$this->options['team-id'],
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertNotEmpty(
			$team_members_res1_actual,
			'Got no team members from vipgoci_github_team_members_many_get()'
		);

		$this->assertTrue(
			is_numeric(
				$team_members_res1_actual[0]
			)
		);

		unset( $team_members_res1_actual );
	}
}

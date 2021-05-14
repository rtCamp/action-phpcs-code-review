<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitHubTeamMembersTest extends TestCase {
	var $options = array(
		'github-token'	=> null,
		'team-id' => null,
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
	 * @covers ::vipgoci_github_team_members_get
	 */
	public function testTeamMembers_ids_only_false() {
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


		/*
		 * Test with ids_only = false
		 */

		vipgoci_unittests_output_suppress();

		$team_members_res1_actual = vipgoci_github_team_members_get(
			$this->options['github-token'],
			$this->options['team-id'],
			null
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertNotEmpty(
			$team_members_res1_actual,
			'Got no team members from vipgoci_github_team_members_get()'
		);

		$this->assertTrue(
			isset(
				$team_members_res1_actual[0]->login
			)
		);

		$this->assertTrue(
			strlen(
				$team_members_res1_actual[0]->login
			) > 0
		);


		/*
		 * Test again to make sure the cache behaves correctly.
		 */

		vipgoci_unittests_output_suppress();

		$team_members_res1_actual_cached = vipgoci_github_team_members_get(
			$this->options['github-token'],
			$this->options['team-id'],
			null
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			$team_members_res1_actual,
			$team_members_res1_actual_cached
		);

		unset( $team_members_res1_actual );
		unset( $team_members_res1_actual_cached );
	}

	/**
	 * @covers ::vipgoci_github_team_members_get
	 */
	public function testTeamMembers_ids_only_true() {	
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

		/*
		 * Second test, with $ids_only = true
		 */

		vipgoci_unittests_output_suppress();

		$team_members_res2_actual = vipgoci_github_team_members_get(
			$this->options['github-token'],
			$this->options['team-id'],
			'id'
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertNotEmpty(
			$team_members_res2_actual,
			'Got empty results when calling vipgoci_github_team_members_get()'
		);

		$this->assertTrue(
			isset(
				$team_members_res2_actual[0]
			)
		);

		$this->assertTrue(
			is_numeric(
				$team_members_res2_actual[0]
			)
		);

		// Again, for caching.

		vipgoci_unittests_output_suppress();

		$team_members_res2_actual_cached = vipgoci_github_team_members_get(
			$this->options['github-token'],
			$this->options['team-id'],
			'id'
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			$team_members_res2_actual,
			$team_members_res2_actual_cached
		);

		unset( $team_members_res2_actual );
		unset( $team_members_res2_actual_cached );
	}
}

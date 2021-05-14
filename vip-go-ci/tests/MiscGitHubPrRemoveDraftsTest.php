<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class MiscGitHubPrRemoveDraftsTest extends TestCase {
	/**
	 * @covers ::vipgoci_github_pr_remove_drafts
	 */
	public function testRemoveDraftPrs() {
		$prs_array = array(
			(object) array(
				'url'		=> 'https://myapi.mydomain.is',
				'id'		=> 123,
				'node_id'	=> 'testing',
				'state'		=> 'open',
				'draft'		=> true
			),

			(object) array(
 				'url'		=> 'https://myapi2.mydomain.is',
				'id'		=> 999,
				'node_id'	=> 'testing2',
				'state'		=> 'open',
				'draft'		=> false
			)
		);

		$prs_array = vipgoci_github_pr_remove_drafts(
			$prs_array
		);

		if ( isset( $prs_array[ 1 ] ) ) {
			$prs_array[ 1 ] = (array) $prs_array[ 1 ];
		}

		$this->assertSame(
			array(
				1 => array(
	 				'url'		=> 'https://myapi2.mydomain.is',
					'id'		=> 999,
					'node_id'	=> 'testing2',
					'state'		=> 'open',
					'draft'		=> false
				)
			),
			$prs_array
		);
	}
}

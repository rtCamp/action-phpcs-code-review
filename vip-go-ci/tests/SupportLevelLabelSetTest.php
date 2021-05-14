<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class SupportLevelLabelSetTest extends TestCase {
	var $options_meta_api_secrets = array(
		'repo-meta-api-base-url'	=> null,
		'repo-meta-api-user-id'		=> null,
		'repo-meta-api-access-token'	=> null,

		'support-level'			=> null,
		'support-level-field-name'	=> null,
	);

	var $options_git = array(
		'git-path'		=> null,
		'github-repo-url'	=> null,
		'repo-name'		=> null,
		'repo-owner'		=> null,
	);

	protected function setUp(): void {
		vipgoci_unittests_get_config_values(
			'repo-meta-api-secrets',
			$this->options_meta_api_secrets,
			true
		);

		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		$this->options = array_merge(
			$this->options_meta_api_secrets,
			$this->options_git
		);

		$this->options['branches-ignore'] = array();

		$this->options['token'] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);

		$this->options['commit'] =
			vipgoci_unittests_get_config_value(
				'repo-meta-api',
				'commit-support-level-set-test'
			);

		$this->options['set-support-level-label-prefix'] =
			'[MySupport Level]';

		$this->options['skip-draft-prs'] = false;
	}

	protected function tearDown(): void {
		$this->options_meta_api_secrets = null;
		$this->options_git = null;
		$this->options = null;
	}

	protected function _findPrsImplicated() {
		return vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['token'],
			$this->options['branches-ignore']
		);
	}

	/*
	 * Loop through each PR, count 
	 * number of support-level labels
	 * found and return.
	 */

	protected function _findSupportLabelstoPrs() {
		$support_labels_cnt = 0;

		$prs_implicated = $this->_findPrsImplicated();

		foreach ( $prs_implicated as $pr_item ) {
			$pr_item_labels = vipgoci_github_pr_labels_get(
				$this->options['repo-owner'],
				$this->options['repo-name'],
				$this->options['token'],
				$pr_item->number,
				null,
				true // Avoid cache
			);

			/*
			 * False can indicate none was found.
			 */

			if ( false === $pr_item_labels ) {
				continue;
			}

			foreach( $pr_item_labels as $label_item ) {
				if ( 
					$this->options['set-support-level-label-prefix'] . ' ' . ucfirst( strtolower( $this->options['support-level'] ) )
					===
					$label_item->name
				) {
					$support_labels_cnt++;
				}
			}
		}

		return $support_labels_cnt;
	}

	/**
	 * @covers ::vipgoci_support_level_label_set
	 */
	public function testSupportLevelSet1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'repo-meta-api-user-id', 'repo-meta-api-access-token' ),
			$this
		);

		$this->options['set-support-level-label'] = false;

		$this->options['set-support-level-field'] =
			$this->options['support-level-field-name'];

		$level_label = vipgoci_support_level_label_set(
			$this->options
		);

		$this->assertFalse(
			$level_label
		);

		$support_labels_cnt = $this->_findSupportLabelstoPrs();

		$this->assertSame(
			0,
			$support_labels_cnt
		);
	}

	/**
	 * @covers ::vipgoci_support_level_label_set
	 */
	public function testSupportLevelSet2() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'repo-meta-api-user-id', 'repo-meta-api-access-token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['set-support-level-label'] = true;

		$this->options['set-support-level-field'] =
			$this->options['support-level-field-name'];

		/*
		 * Get any PRs attached, verify we got at least one.
		 */
		$prs_implicated = $this->_findPrsImplicated();

		$this->assertNotEmpty(
			$prs_implicated
		);

		/*
		 * Look for support labels, should be none.
		 */
		$support_labels_cnt = $this->_findSupportLabelstoPrs();

		$this->assertSame(
			0,
			$support_labels_cnt
		);
	
		/*
		 * Attempt to set support level label.
		 */
		$level_label_ret = vipgoci_support_level_label_set(
			$this->options
		);

		$this->assertTrue(
			$level_label_ret
		);

		/*
		 * Loop through each PR, looking for
		 * support level label. Then delete any
		 * we find to clean up.
		 */
	
		$this->assertNotEmpty(
			$prs_implicated
		);

		foreach ( $prs_implicated as $pr_item ) {
			$pr_item_labels = vipgoci_github_pr_labels_get(
				$this->options['repo-owner'],
				$this->options['repo-name'],
				$this->options['token'],
				$pr_item->number,
				null,
				true // Avoid cache
			);

			$this->assertNotEmpty(
				$pr_item_labels
			);

			$found_support_level_label = false;

			foreach( $pr_item_labels as $label_item ) {
				if (
					$this->options['set-support-level-label-prefix'] . ' ' . ucfirst( strtolower( $this->options['support-level'] ) ) ===
					$label_item->name
				) {
					/*
					 * Clean up label and indicate we found it.
					 */	
					vipgoci_github_pr_label_remove(
						$this->options['repo-owner'],
						$this->options['repo-name'],
						$this->options['token'],
						$pr_item->number,
						$label_item->name
					);

					$found_support_level_label = true;
				}
			}

			$this->assertTrue(
				$found_support_level_label
			);
		}
	}
}

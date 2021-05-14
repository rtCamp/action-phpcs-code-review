<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class A09GitHubLabelsTest extends TestCase {
	const label_name = 'Label for testing';

	var $options_git = array(
		'github-repo-url'	=> null,
		'repo-name'		=> null,
		'repo-owner'		=> null,
	);

	var $options_labels = array(
		'labels-pr-to-modify'	=> null,
	);

	var $options_secrets = array(
	);

	protected function setUp(): void {
		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		vipgoci_unittests_get_config_values(
			'labels',
			$this->options_labels
		);

		$this->options_secrets[ 'github-token' ] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);

		$this->options = array_merge(
			$this->options_secrets,
			$this->options_git,
			$this->options_labels
		);
	}

	protected function tearDown(): void {
		$this->options_git = null;
		$this->options_secrets = null;
		$this->options_labels = null;
		$this->options = null;
	}

	/**
	 * @covers ::vipgoci_github_label_add_to_pr
	 */
	public function testGitHubAddLabel1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$labels_before = $this->labels_get();

		vipgoci_github_label_add_to_pr(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['github-token'],
			$this->options['labels-pr-to-modify'],
			$this::label_name
		);

		vipgoci_unittests_output_unsuppress();

		$labels_after = $this->labels_get();

		$this->assertSame(
			-1,
			count( $labels_before ) - count( $labels_after )
		);

		$this->assertSame(
			'Label for testing',
			$labels_after[0]->name
		);
	}

	/**
	 * @covers ::vipgoci_github_pr_label_remove
	 */
	public function testGitHubRemoveLabel1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$labels_before = $this->labels_get();

		vipgoci_github_pr_label_remove(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['github-token'],
			$this->options['labels-pr-to-modify'],
			$this::label_name
		);

		vipgoci_unittests_output_unsuppress();

		$labels_after = $this->labels_get();

		$this->assertSame(
			1,
			count( $labels_before ) - count( $labels_after )
		);
	}

	private function labels_get() {
		/*
		 * Sometimes it can take GitHub
		 * a while to update its cache.
		 * Avoid stale cache by waiting
		 * a short while.
		 */
		sleep( 10 );

		$github_url =
			VIPGOCI_GITHUB_BASE_URL . '/' .
			'repos/' .
			rawurlencode( $this->options['repo-owner'] ) . '/' .
			rawurlencode( $this->options['repo-name'] ) . '/' .
			'issues/' .
			rawurlencode( $this->options['labels-pr-to-modify'] ) . '/' .
			'labels';

		$data = vipgoci_github_fetch_url(
			$github_url,
			$this->options['github-token']
		);

		$data = json_decode( $data );

		return $data;
	}
}

<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitHubPrGenericSupportCommentTest extends TestCase {
	var $options_git = array(
		'repo-owner'	=> null,
		'repo-name'	=> null,
	);

	var $options_git_repo_tests = array(
		'test-github-pr-generic-support-comment-1'	=> null,
	);

	protected function setUp(): void {
		/*
		 * Many of the functions called
		 * make use of caching, clear the cache
		 * so the testing will not rely on
		 * old data by accident.
		 */
		vipgoci_cache(
			VIPGOCI_CACHE_CLEAR
		);

		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		vipgoci_unittests_get_config_values(
			'git-repo-tests',
			$this->options_git_repo_tests
		);

		$this->options = array();

		$this->options['token'] =
		$this->options['github-token'] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);

		$this->options['post-generic-pr-support-comments'] = true;

		$this->options['post-generic-pr-support-comments-on-drafts'] =
			array(
				2 => false,
			);

		$this->options['post-generic-pr-support-comments-string'] =
			array(
				2 => 'This is a generic support message from `vip-go-ci`. We hope this is useful.',
			);
				

		$this->options['post-generic-pr-support-comments-branches'] =
			array(
				2 => array( 'any' ),
			);

		$this->options['post-generic-pr-support-comments-repo-meta-match'] =
			array(
			);

		$this->options = array_merge(
			$this->options_git,
			$this->options_git_repo_tests,
			$this->options
		);

		$this->options['commit'] =
			$this->options['test-github-pr-generic-support-comment-1'];

		if ( empty( $this->current_user_info ) ) {
			$this->current_user_info = vipgoci_github_authenticated_user_get(
				$this->options['github-token']
			);
		}

		/*
		 * Don't attempt cleanup if not configured.
		 */

		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 !== $options_test ) {
			$this->_clearOldSupportComments();
		}
	}

	protected function tearDown(): void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 !== $options_test ) {
			$this->_clearOldSupportComments();
		}

		$this->options = null;
		$this->options_git = null;
		$this->options_git_repo_tests = null;
	}

	/*
	 * Get Pull-Requests implicated.
	 */
	protected function _getPrsImplicated() {
		vipgoci_unittests_output_suppress();

		$ret = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['github-token'],
			array()
		);

		vipgoci_unittests_output_unsuppress();

		return $ret;
	}

	/*
	 * Get generic comments made to a Pull-Request
	 * from GitHub, uncached.
	 */
	protected function _getPrGenericComments(
		$pr_number
	) {
		$pr_comments_ret = array();

		$page = 1;
		$per_page = 100;

		do {
			$github_url =
				VIPGOCI_GITHUB_BASE_URL . '/' .
				'repos/' .
				rawurlencode( $this->options['repo-owner'] ) . '/' .
				rawurlencode( $this->options['repo-name'] ) . '/' .
				'issues/' .
				rawurlencode( $pr_number ) . '/' .
				'comments' .
				'?page=' . rawurlencode( $page ) . '&' .
				'per_page=' . rawurlencode( $per_page );


	                $pr_comments_raw = json_decode(
	                        vipgoci_github_fetch_url(
        	                        $github_url,
                	                $this->options['github-token']
                        	)
	                );

	                foreach ( $pr_comments_raw as $pr_comment ) {
	                        $pr_comments_ret[] = $pr_comment;
        	        }

	                $page++;
		} while ( count( $pr_comments_raw ) >= $per_page );

		return $pr_comments_ret;
	}

	/*
	 * Clear away any old support comments
	 * left behind by us. Do this by looping
	 * through any Pull-Requests implicated and
	 * check if each one has any comments, then
	 * remove them if they were made by us and
	 * are support comments.
	 */
	protected function _clearOldSupportComments() {
		$prs_implicated = $this->_getPrsImplicated();

		foreach( $prs_implicated as $pr_item ) {
			// Check if any comments already exist
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			foreach ( $pr_comments as $pr_comment ) {
				if ( $pr_comment->user->login !== $this->current_user_info->login ) {
					continue;
				}

				// Look for a support-comment
				foreach(
					array_values(
						$this->options['post-generic-pr-support-comments-string']
					)
					as $tmp_support_comment_string
				) {
					// Check if the comment contains the support-comment
					if ( strpos(
						$pr_comment->body,
						$tmp_support_comment_string
					) === 0 ) {
						// Remove comment, submitted by us, is support comment.
						vipgoci_github_pr_generic_comment_delete(
							$this->options['repo-owner'],
							$this->options['repo-name'],
							$this->options['github-token'],
							$pr_comment->id
						);

						break;
					}
				}
			}
		}
	}

	/*
	 * Count number of support comments posted
	 * by the current token-holder.
	 */
	protected function _countSupportCommentsFromUs(
		$pr_comments
	) {	
		$valid_comments_found = 0;

		foreach( $pr_comments as $pr_comment ) {
			if ( $pr_comment->user->login !== $this->current_user_info->login ) {
				continue;
			}

			// Check if the comment contains the support-comment
			foreach(
				array_values(
					$this->options['post-generic-pr-support-comments-string']
				)
				as $tmp_support_comment_string
			) {
				// Check if the comment contains the support-comment
				if ( strpos(
					$pr_comment->body,
					$tmp_support_comment_string
				) === 0 ) {
					// We have found support comment posted by us
					$valid_comments_found++;
					break;
				}
			}
		}

		return $valid_comments_found;
	}

	/**
	 * @covers ::vipgoci_github_pr_generic_support_comment_submit
	 */
	public function testPostingNotConfigured() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}
	
		// Configure branches we can post against
		$this->options['post-generic-pr-support-comments-branches'] =
			array(
				2 => array( 'any' ),
			);

		// Should not post generic support comments
		$this->options['post-generic-pr-support-comments'] = false;

		// Get Pull-Requests
        	$prs_implicated = $this->_getPrsImplicated();

		// Check we have at least one PR
		$this->assertTrue(
			count( $prs_implicated ) > 0
		);

		/*
		 * vipgoci_github_pr_generic_support_comment_submit() will
		 * call vipgoci_github_pr_generic_comments_get_all() that
		 * caches results, causing it to give back wrong
		 * results when called again. Clear the internal cache
		 * here to circumvent this.
		 */

		vipgoci_cache(
			VIPGOCI_CACHE_CLEAR
		);

		// Try to submit support comment
		vipgoci_github_pr_generic_support_comment_submit(
			$this->options,
			$prs_implicated
		);

		// Check if commenting succeeded
		foreach( $prs_implicated as $pr_item ) {
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			$this->assertTrue(
				count( $pr_comments ) === 0
			);

			$this->assertSame(
				0,
				$this->_countSupportCommentsFromUs(
					$pr_comments
				)
			);
		}
	
		vipgoci_cache(
			VIPGOCI_CACHE_CLEAR
		);
	}

	/**
	 * @covers ::vipgoci_github_pr_generic_support_comment_submit
	 */
	public function testPostingWorksAnyBranch() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		// Configure branches we can post against
		$this->options['post-generic-pr-support-comments-branches'] =
			array(
				2 => array( 'any' ),
			);

		// Get Pull-Requests
        	$prs_implicated = $this->_getPrsImplicated();

		// Check we have at least one PR
		$this->assertTrue(
			count( $prs_implicated ) > 0
		);

		// Try to submit support comment
		vipgoci_github_pr_generic_support_comment_submit(
			$this->options,
			$prs_implicated
		);

		// Check if commenting succeeded
		foreach( $prs_implicated as $pr_item ) {
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			if ( $pr_item->draft === true ) {
				$this->assertTrue(
					count( $pr_comments ) === 0
				);

				$this->assertSame(
					0,
					$this->_countSupportCommentsFromUs(
						$pr_comments
					)
				);
			}

			else {
				$this->assertTrue(
					count( $pr_comments ) > 0
				);

				$this->assertSame(
					1,
					$this->_countSupportCommentsFromUs(
						$pr_comments
					)
				);
			}
		}

		/*
		 * Clear cache -- see explanation above
		 */

		vipgoci_cache(
			VIPGOCI_CACHE_CLEAR
		);
		
		// Try re-posting
		vipgoci_github_pr_generic_support_comment_submit(
			$this->options,
			$prs_implicated
		);

		// And make sure it did not succeed
		foreach( $prs_implicated as $pr_item ) {
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			if ( $pr_item->draft === true ) {
				$this->assertTrue(
					count( $pr_comments ) === 0
				);

				$this->assertSame(
					0,
					$this->_countSupportCommentsFromUs(
						$pr_comments
					)
				);
			}

			else {
				$this->assertTrue(
					count( $pr_comments ) > 0
				);

				$this->assertSame(
					1,
					$this->_countSupportCommentsFromUs(
						$pr_comments
					)
				);
			}
		}
	}

	/**
	 * @covers ::vipgoci_github_pr_generic_support_comment_submit
	 */
	public function testPostingWorksSpecificBranch() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		// Configure branches we allow posting against
		$this->options['post-generic-pr-support-comments-branches'] =
			array(
				2 => array( 'master' ),
			);

		// Get Pull-Requests
        	$prs_implicated = $this->_getPrsImplicated();

		// Check we have at least one PR
		$this->assertTrue(
			count( $prs_implicated ) > 0
		);

		// Try to submit support comment
		vipgoci_github_pr_generic_support_comment_submit(
			$this->options,
			$prs_implicated
		);

		// Check if commenting succeeded
		foreach( $prs_implicated as $pr_item ) {
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			if ( $pr_item->draft === true ) {
				$this->assertTrue(
					count( $pr_comments ) === 0
				);

				$this->assertSame(
					0,
					$this->_countSupportCommentsFromUs(
						$pr_comments
					)
				);
			}

			else {
				$this->assertTrue(
					count( $pr_comments ) > 0
				);

				$this->assertSame(
					1,
					$this->_countSupportCommentsFromUs(
						$pr_comments
					)
				);
			}
		}

		/*
		 * Clear cache -- see explanation above
		 */

		vipgoci_cache(
			VIPGOCI_CACHE_CLEAR
		);
		
		// Try re-posting
		vipgoci_github_pr_generic_support_comment_submit(
			$this->options,
			$prs_implicated
		);

		// And make sure it did not succeed
		foreach( $prs_implicated as $pr_item ) {
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			if ( $pr_item->draft === true ) {
				$this->assertTrue(
					count( $pr_comments ) === 0
				);
	
				$this->assertSame(
					0,
					$this->_countSupportCommentsFromUs(
						$pr_comments
					)
				);
			}

			else {
				$this->assertTrue(
					count( $pr_comments ) > 0
				);

				$this->assertSame(
					1,
					$this->_countSupportCommentsFromUs(
						$pr_comments
					)
				);
			}
		}
	}

	/**
	 * @covers ::vipgoci_github_pr_generic_support_comment_submit
	 */
	public function testPostingSkippedInvalidBranch() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		// Configure branches to post against
		$this->options['post-generic-pr-support-comments-branches'] =
			array(
				2 => array( 'myinvalidbranch0xfff' ),
			);

		// Get Pull-Requests
        	$prs_implicated = $this->_getPrsImplicated();

		// Check we have at least one PR
		$this->assertTrue(
			count( $prs_implicated ) > 0
		);

		// Try to submit support comment
		vipgoci_github_pr_generic_support_comment_submit(
			$this->options,
			$prs_implicated
		);

		// Check if commenting succeeded -- should not have, as branch is invalid
		foreach( $prs_implicated as $pr_item ) {
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			$this->assertSame(
				0,
				$this->_countSupportCommentsFromUs(
					$pr_comments
				)
			);
		}

		/*
		 * Clear cache -- see explanation above
		 */

		vipgoci_cache(
			VIPGOCI_CACHE_CLEAR
		);
		
		// Try re-posting
		vipgoci_github_pr_generic_support_comment_submit(
			$this->options,
			$prs_implicated
		);

		// And make sure it did not succeed the second time
		foreach( $prs_implicated as $pr_item ) {
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			$this->assertSame(
				0,
				$this->_countSupportCommentsFromUs(
					$pr_comments
				)
			);
		}
	}

	/**
	 * @covers ::vipgoci_github_pr_generic_support_comment_submit
	 */
	public function testPostingWorksWithDraftPRs() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		// Configure branches we can post against
		$this->options['post-generic-pr-support-comments-branches'] =
			array(
				2 => array( 'any' ),
			);

		// Get Pull-Requests
        	$prs_implicated = $this->_getPrsImplicated();

		// Check we have at least one PR
		$this->assertTrue(
			count( $prs_implicated ) > 0
		);

		// Try to submit support comment
		vipgoci_github_pr_generic_support_comment_submit(
			$this->options,
			$prs_implicated
		);

		// Check if commenting succeeded
		foreach( $prs_implicated as $pr_item ) {
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			if ( $pr_item->draft === true ) {
				$this->assertTrue(
					count( $pr_comments ) === 0
				);
	
				$this->assertSame(
					0,
					$this->_countSupportCommentsFromUs(
						$pr_comments
					)
				);
			}

			else {
				$this->assertTrue(
					count( $pr_comments ) > 0
				);

				$this->assertSame(
					1,
					$this->_countSupportCommentsFromUs(
						$pr_comments
					)
				);
			}
		}

		/*
		 * Clear cache -- see explanation above
		 */

		vipgoci_cache(
			VIPGOCI_CACHE_CLEAR
		);
		
		// Try re-posting
		vipgoci_github_pr_generic_support_comment_submit(
			$this->options,
			$prs_implicated
		);

		// And make sure it did not succeed
		foreach( $prs_implicated as $pr_item ) {
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			if ( $pr_item->draft === true ) {
				$this->assertTrue(
					count( $pr_comments ) === 0
				);
	
				$this->assertSame(
					0,
					$this->_countSupportCommentsFromUs(
						$pr_comments
					)
				);
			}

			else {
				$this->assertTrue(
					count( $pr_comments ) > 0
				);

				$this->assertSame(
					1,
					$this->_countSupportCommentsFromUs(
						$pr_comments
					)
				);
			}
		}

		/*
		 * Re-configure to post on drafts too and re-run
		 */

		vipgoci_cache(
			VIPGOCI_CACHE_CLEAR
		);

		// Post on draft PRs
		$this->options['post-generic-pr-support-comments-on-drafts'] = array(
			2 => true,
		);

		// Try re-posting
		vipgoci_github_pr_generic_support_comment_submit(
			$this->options,
			$prs_implicated
		);

		// And make sure it did succeed
		foreach( $prs_implicated as $pr_item ) {
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			$this->assertTrue(
				count( $pr_comments ) > 0
			);

			$this->assertSame(
				1,
				$this->_countSupportCommentsFromUs(
					$pr_comments
				)
			);
		}
	}

	/**
	 * @covers ::vipgoci_github_pr_generic_support_comment_submit
	 */
	public function testPostingWorksWithLabels() {
		$test_label = 'my-random-label-1596640824';

		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		// Configure branches we can post against
		$this->options['post-generic-pr-support-comments-branches'] =
			array(
				2 => array( 'any' ),
			);

		$this->options['post-generic-pr-support-comments-skip-if-label-exists'] = array(
			2 => $test_label,
		);

		// Get Pull-Requests
        	$prs_implicated = $this->_getPrsImplicated();

		// Check we have at least one PR
		$this->assertTrue(
			count( $prs_implicated ) > 0
		);

		foreach( $prs_implicated as $pr_item ) {
			// Make sure there are no comments
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			$this->assertTrue(
				count( $pr_comments ) === 0
			);
	
			// Add label to make sure no comment is posted
			vipgoci_github_label_add_to_pr(
				$this->options['repo-owner'],
				$this->options['repo-name'],
				$this->options['token'],
				$pr_item->number,
				$test_label
			);
		}

		// Try to submit support comment
		vipgoci_github_pr_generic_support_comment_submit(
			$this->options,
			$prs_implicated
		);

		// Make sure commenting did not succeed
		foreach( $prs_implicated as $pr_item ) {
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			$this->assertTrue(
				count( $pr_comments ) === 0
			);
	
			$this->assertSame(
				0,
				$this->_countSupportCommentsFromUs(
					$pr_comments
				)
			);
		}

		/*
		 * Clear cache -- see explanation above
		 */

		vipgoci_cache(
			VIPGOCI_CACHE_CLEAR
		);
		
		// Try re-posting
		vipgoci_github_pr_generic_support_comment_submit(
			$this->options,
			$prs_implicated
		);

		// And make sure it did not succeed
		foreach( $prs_implicated as $pr_item ) {
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			$this->assertTrue(
				count( $pr_comments ) === 0
			);

			$this->assertSame(
				0,
				$this->_countSupportCommentsFromUs(
					$pr_comments
				)
			);
		}

		/*
		 * Re-configure to post on drafts too and re-run
		 */

		vipgoci_cache(
			VIPGOCI_CACHE_CLEAR
		);

		// Post on draft PRs
		$this->options['post-generic-pr-support-comments-on-drafts'] = array(
			2 => true,
		);

		// Try re-posting
		vipgoci_github_pr_generic_support_comment_submit(
			$this->options,
			$prs_implicated
		);

		// And make sure it did not succeed
		foreach( $prs_implicated as $pr_item ) {
			$pr_comments = $this->_getPrGenericComments(
				$pr_item->number
			);

			$this->assertTrue(
				count( $pr_comments ) === 0
			);

			$this->assertSame(
				0,
				$this->_countSupportCommentsFromUs(
					$pr_comments
				)
			);

			vipgoci_github_pr_label_remove(
				$this->options['repo-owner'],
				$this->options['repo-name'],
				$this->options['token'],
				$pr_item->number,
				$test_label
			);
		}
	}
}

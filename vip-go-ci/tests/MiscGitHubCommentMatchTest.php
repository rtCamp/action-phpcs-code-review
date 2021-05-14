<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class MiscGitHubCommentMatchTest extends TestCase {
	/**
	 * @covers ::vipgoci_github_comment_match
	 */
	public function testCommentMatch1() {
		$prs_comments = array(
			'bla-8.php:3' => array(
				json_decode(
					'{"url":"https:\/\/api.github.com\/repos\/gudmdharalds-a8c\/testing123\/pulls\/comments\/249878202","pull_request_review_id":195129115,"id":249878202,"node_id":"MDI0Ol123","diff_hunk":"@@ -0,0 +1,3 @@\n+<?php\n+\n+echo mysql_query(\"test\");","path":"bla-8.php","position":3,"original_position":3,"commit_id":"aec27de5f13a5495577ca7ba27fc8b10a04ac89f","original_commit_id":"34cbe527fee864dcbaca26649a6613cb2a4b5eeb","user":{},"body":":no_entry_sign: **Error**: All output should be run tttter Handbooks), found \'mysql_query\'.","created_at":"2019-01-22T17:14:24Z","updated_at":"2019-02-11T11:40:43Z","html_url":"https:\/\/github.com\/gudmdharalds-a8c\/testing123\/pull\/32#discussion_r249878202","pull_request_url":"https:\/\/api.github.com\/repos\/gudmdharalds-a8c\/testing123\/pulls\/32","author_association":"OWNER","_links":{"self":{"href":"https:\/\/api.github.com\/repos\/gudmdharalds-a8c\/testing123\/pulls\/comments\/249878202"},"html":{"href":"https:\/\/github.com\/gudmdharalds-a8c\/testing123\/pull\/32#discussion_r249878202"},"pull_request":{"href":"https:\/\/api.github.com\/repos\/gudmdharalds-a8c\/testing123\/pulls\/32"}}}'
				),
				json_decode(
					'{"url":"https:\/\/api.github.com\/repos\/gudmdharalds-a8c\/testing123\/pulls\/comments\/255465011","pull_request_review_id":202053661,"id":255465011,"node_id":"MDI0O01245","diff_hunk":"@@ -0,0 +1,3 @@\n+<?php\n+\n+echo mysql_query(\"test\");","path":"bla-8.php","position":3,"original_position":3,"commit_id":"aec27de5f13a5495577ca7ba27fc8b10a04ac89f","original_commit_id":"aec27de5f13a5495577ca7ba27fc8b10a04ac89f","user":{},"body":":no_entry_sign: **Error**: Extension \'mysql_\' is deprecated since PHP 5.5 and removed since PHP 7.0; Use mysqli instead","created_at":"2019-02-11T11:19:21Z","updated_at":"2019-02-11T11:19:21Z","html_url":"https:\/\/github.com\/gudmdharalds-a8c\/testing123\/pull\/32#discussion_r255465011","pull_request_url":"https:\/\/api.github.com\/repos\/gudmdharalds-a8c\/testing123\/pulls\/32","author_association":"OWNER","_links":{"self":{"href":"https:\/\/api.github.com\/repos\/gudmdharalds-a8c\/testing123\/pulls\/comments\/255465011"},"html":{"href":"https:\/\/github.com\/gudmdharalds-a8c\/testing123\/pull\/32#discussion_r255465011"},"pull_request":{"href":"https:\/\/api.github.com\/repos\/gudmdharalds-a8c\/testing123\/pulls\/32"}}}'
				),
				json_decode(
					'{"url":"https:\/\/api.github.com\/repos\/gudmdharalds-a8c\/testing123\/pulls\/comments\/255465011","pull_request_review_id":202053661,"id":255465011,"node_id":"MDI0O01245","diff_hunk":"@@ -0,0 +1,3 @@\n+<?php\n+\n+echo mysql_query(\"test\");","path":"bla-8.php","position":3,"original_position":3,"commit_id":"aec27de5f13a5495577ca7ba27fc8b10a04ac89f","original_commit_id":"aec27de5f13a5495577ca7ba27fc8b10a04ac89f","user":{},"body":":no_entry_sign: **Error**: Any HTML passed to `innerHTML` gets executed. Consider using `.textContent` or make sure that used variables are properly escaped (*WordPressVIPMinimum.JS.InnerHTML.innerHTML*).","created_at":"2019-02-11T11:19:21Z","updated_at":"2019-02-11T11:19:21Z","html_url":"https:\/\/github.com\/gudmdharalds-a8c\/testing123\/pull\/32#discussion_r255465011","pull_request_url":"https:\/\/api.github.com\/repos\/gudmdharalds-a8c\/testing123\/pulls\/32","author_association":"OWNER","_links":{"self":{"href":"https:\/\/api.github.com\/repos\/gudmdharalds-a8c\/testing123\/pulls\/comments\/255465011"},"html":{"href":"https:\/\/github.com\/gudmdharalds-a8c\/testing123\/pull\/32#discussion_r255465011"},"pull_request":{"href":"https:\/\/api.github.com\/repos\/gudmdharalds-a8c\/testing123\/pulls\/32"}}}'
				)
			),

			// Do not test against these; they are here to make sure nothing bogus is matched
			'bla-8.php:90' => array(
				json_decode(
					'{"url":"https:\/\/api.github.com\/repos\/gudmdharalds-a8c\/testing123\/pulls\/comments\/249878202","pull_request_review_id":195129115,"id":249878202,"node_id":"MDI0Ol123","diff_hunk":"@@ -0,0 +1,3 @@\n+<?php\n+\n+echo mysql_query(\"test\");","path":"bla-8.php","position":3,"original_position":3,"commit_id":"aec27de5f13a5495577ca7ba27fc8b10a04ac89f","original_commit_id":"34cbe527fee864dcbaca26649a6613cb2a4b5eeb","user":{},"body":":no_entry_sign: **Error**: All output should be run tttter Handbooks), found \'mysql_query\'.","created_at":"2019-01-22T17:14:24Z","updated_at":"2019-02-11T11:40:43Z","html_url":"https:\/\/github.com\/gudmdharalds-a8c\/testing123\/pull\/32#discussion_r249878202","pull_request_url":"https:\/\/api.github.com\/repos\/gudmdharalds-a8c\/testing123\/pulls\/32","author_association":"OWNER","_links":{"self":{"href":"https:\/\/api.github.com\/repos\/gudmdharalds-a8c\/testing123\/pulls\/comments\/249878202"},"html":{"href":"https:\/\/github.com\/gudmdharalds-a8c\/testing123\/pull\/32#discussion_r249878202"},"pull_request":{"href":"https:\/\/api.github.com\/repos\/gudmdharalds-a8c\/testing123\/pulls\/32"}}}'
				),
			),

			'bla-9.php:90' => array(
				json_decode(
					'{"url":"https:\/\/api.github.com\/repos\/gudmdharalds-a8c\/testing123\/pulls\/comments\/255465011","pull_request_review_id":202053661,"id":255465011,"node_id":"MDI0O01245","diff_hunk":"@@ -0,0 +1,3 @@\n+<?php\n+\n+echo mysql_query(\"test\");","path":"bla-8.php","position":3,"original_position":3,"commit_id":"aec27de5f13a5495577ca7ba27fc8b10a04ac89f","original_commit_id":"aec27de5f13a5495577ca7ba27fc8b10a04ac89f","user":{},"body":":no_entry_sign: **Error**: Extension \'mysql_\' is deprecated since PHP 5.5 and removed since PHP 7.0; Use mysqli instead","created_at":"2019-02-11T11:19:21Z","updated_at":"2019-02-11T11:19:21Z","html_url":"https:\/\/github.com\/gudmdharalds-a8c\/testing123\/pull\/32#discussion_r255465011","pull_request_url":"https:\/\/api.github.com\/repos\/gudmdharalds-a8c\/testing123\/pulls\/32","author_association":"OWNER","_links":{"self":{"href":"https:\/\/api.github.com\/repos\/gudmdharalds-a8c\/testing123\/pulls\/comments\/255465011"},"html":{"href":"https:\/\/github.com\/gudmdharalds-a8c\/testing123\/pull\/32#discussion_r255465011"},"pull_request":{"href":"https:\/\/api.github.com\/repos\/gudmdharalds-a8c\/testing123\/pulls\/32"}}}'
				),
			),

			'bla-10.php:3' => array(
				json_decode(
					'{"url":"https:\/\/api.github.com\/repos\/gudmdharalds-a8c\/testing123\/pulls\/comments\/255465011","pull_request_review_id":202053661,"id":255465011,"node_id":"MDI0O01245","diff_hunk":"@@ -0,0 +1,3 @@\n+<?php\n+\n+echo mysql_query(\"test\");","path":"bla-8.php","position":3,"original_position":3,"commit_id":"aec27de5f13a5495577ca7ba27fc8b10a04ac89f","original_commit_id":"aec27de5f13a5495577ca7ba27fc8b10a04ac89f","user":{},"body":":no_entry_sign: **Error**: Extension \'mysql_\' is deprecated since PHP 5.5 and removed since PHP 7.0; Use mysqli instead","created_at":"2019-02-11T11:19:21Z","updated_at":"2019-02-11T11:19:21Z","html_url":"https:\/\/github.com\/gudmdharalds-a8c\/testing123\/pull\/32#discussion_r255465011","pull_request_url":"https:\/\/api.github.com\/repos\/gudmdharalds-a8c\/testing123\/pulls\/32","author_association":"OWNER","_links":{"self":{"href":"https:\/\/api.github.com\/repos\/gudmdharalds-a8c\/testing123\/pulls\/comments\/255465011"},"html":{"href":"https:\/\/github.com\/gudmdharalds-a8c\/testing123\/pull\/32#discussion_r255465011"},"pull_request":{"href":"https:\/\/api.github.com\/repos\/gudmdharalds-a8c\/testing123\/pulls\/32"}}}'
				),
			),

			'bla-11.php:5' => array(
				json_decode(
					'{"url":"https:\/\/api.github.com\/repos\/gudmdharalds-a8c\/testing123\/pulls\/comments\/255465011","pull_request_review_id":202053661,"id":255465011,"node_id":"MDI0O01245","diff_hunk":"@@ -0,0 +1,3 @@\n+<?php\n+\n+echo mysql_query(\"test\");","path":"bla-11.php","position":5,"original_position":3,"commit_id":"aec27de5f13a5495577ca7ba27fc8b10a04ac89f","original_commit_id":"aec27de5f13a5495577ca7ba27fc8b10a04ac89f","user":{},"body":":no_entry_sign: **Error( severity 11 )**: Extension \'mysql_\' is deprecated since PHP 5.5 and removed since PHP 7.0; Use mysqli instead","created_at":"2019-02-11T11:19:21Z","updated_at":"2019-02-11T11:19:21Z","html_url":"https:\/\/github.com\/gudmdharalds-a8c\/testing123\/pull\/32#discussion_r255465011","pull_request_url":"https:\/\/api.github.com\/repos\/gudmdharalds-a8c\/testing123\/pulls\/32","author_association":"OWNER","_links":{"self":{"href":"https:\/\/api.github.com\/repos\/gudmdharalds-a8c\/testing123\/pulls\/comments\/255465011"},"html":{"href":"https:\/\/github.com\/gudmdharalds-a8c\/testing123\/pull\/32#discussion_r255465011"},"pull_request":{"href":"https:\/\/api.github.com\/repos\/gudmdharalds-a8c\/testing123\/pulls\/32"}}}'
				),
			),
		);


		$this->assertTrue(
			vipgoci_github_comment_match(
				'bla-8.php',
				3,
				'Extension \'mysql_\' is deprecated since PHP 5.5 and removed since PHP 7.0; Use mysqli instead',
				$prs_comments
			)
		);

		$this->assertTrue(
			vipgoci_github_comment_match(
				'bla-8.php',
				3,
				'Any HTML passed to `innerHTML` gets executed. Consider using `.textContent` or make sure that used variables are properly escaped',
				$prs_comments
			)
		);

		$this->assertFalse(
			vipgoci_github_comment_match(
				'bla-8.php',
				3,
				'The extension \'mysql_\' is deprecated since PHP 5.5 and removed since PHP 7.0; Use mysqli instead',
				$prs_comments	
			)
		);

		$this->assertFalse(
			vipgoci_github_comment_match(
				'bla-8.php',
				4,
				'Extension \'mysql_\' is deprecated since PHP 5.5 and removed since PHP 7.0; Use mysqli instead',
				$prs_comments	
			)
		);

		$this->assertFalse(
			vipgoci_github_comment_match(
				'bla-8.php',
				4,
				'Any HTML passed to `innerHTML` gets executed. Consider using `.textContent` or make sure that used variables are properly escaped',
				$prs_comments
			)
		);


		$this->assertFalse(
			vipgoci_github_comment_match(
				'bla-9.php',
				3,
				'Extension \'mysql_\' is deprecated since PHP 5.5 and removed since PHP 7.0; Use mysqli instead',
				$prs_comments	
			)
		);


		/*
		 * Test with severity level
		 */
		$this->assertTrue(
			vipgoci_github_comment_match(
				'bla-11.php',
				5,
				'Extension \'mysql_\' is deprecated since PHP 5.5 and removed since PHP 7.0; Use mysqli instead',
				$prs_comments	
			)
		);

		$this->assertFalse(
			vipgoci_github_comment_match(
				'bla-11.php',
				5,
				'Extension \'mysql_\' is deprecated since PHP 300 and removed since PHP 700; Use mysqli instead',
				$prs_comments	
			)
		);


	}
}

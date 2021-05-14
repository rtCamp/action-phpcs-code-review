<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class PhpcsScanIssuesFilterIrrellevantTest extends TestCase {
	/**
	 * @covers ::vipgoci_issues_filter_irrellevant
	 */
	public function testDoScanIssuesFilter1() {
		$file_name = 'bla-10.php';
		$file_issues_arr = json_decode(
			'{"bla-10.php":[{"message":"json_encode() is discouraged. Use wp_json_encode() instead.","source":"WordPress.WP.AlternativeFunctions.json_encode_json_encode","severity":5,"fixable":false,"type":"WARNING","line":7,"column":6,"level":"WARNING"}],"bla-8.php":[{"message":"All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found \'mysql_query\'.","source":"WordPress.Security.EscapeOutput.OutputNotEscaped","severity":5,"fixable":false,"type":"ERROR","line":3,"column":6,"level":"ERROR"},{"message":"Extension \'mysql_\' is deprecated since PHP 5.5 and removed since PHP 7.0; Use mysqli instead","source":"PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved","severity":5,"fixable":false,"type":"ERROR","line":3,"column":6,"level":"ERROR"}]}',
			true
		);

		$file_blame_log = json_decode(
			'[{"commit_id":"0131c2739c1a5d2d03bb2645e1be491a6a182091","file_name":"bla-10.php","line_no":1},{"commit_id":"0131c2739c1a5d2d03bb2645e1be491a6a182091","file_name":"bla-10.php","line_no":2},{"commit_id":"0131c2739c1a5d2d03bb2645e1be491a6a182091","file_name":"bla-10.php","line_no":3},{"commit_id":"0131c2739c1a5d2d03bb2645e1be491a6a182091","file_name":"bla-10.php","line_no":4},{"commit_id":"b591cee061d15b1e0187baf9f13a6ab32661bc1b","file_name":"bla-10.php","line_no":5},{"commit_id":"aec27de5f13a5495577ca7ba27fc8b10a04ac89f","file_name":"bla-10.php","line_no":6},{"commit_id":"aec27de5f13a5495577ca7ba27fc8b10a04ac89f","file_name":"bla-10.php","line_no":7}]',
			true
		);

		$pr_item_commits = json_decode(
			'["113b04c7606bf24bd93973e840e303a811ad778f","131db4ad1afc4660554d965172fee09c089990b1","08d431ed1ce9347c6bb2f273136d241dab97e43c","f5003539e4f9245e4cd6585c3b8fb1e016c28ad5","4f27ffde8dc2262c9458ba6b2d8138a556e56a3e","519e2b73fb30a2715cfc854208e56a067475b4ff","22e525abcd18271bd08b39a7b87d33f8ab78d030","7eda9a6451c96267cfcc1f71a03ee2b7220889a4","c26abf84cd79fb910d14d8a8bc3b00672dd5406d","2edf4deea83405be46ce677e6eae300c6a7988e4","3caebbdb4519267e9d5301a4a7e99878bd71cc18","3dc3a4cb1687acd016b294ee3ef9a199527022b9","c84bfe6a2f4263420c4c6cc9f18c7404950b7c8e","770204731d6a05b5132f324fa9ca9ad4e161fe7c","ba93e6b363b60bf00ac9ada13bd7e345d6e27281","34cbe527fee864dcbaca26649a6613cb2a4b5eeb","d0e8a5ac70d0a9741735bce991c43c12fbdcfcb9","0131c2739c1a5d2d03bb2645e1be491a6a182091","b591cee061d15b1e0187baf9f13a6ab32661bc1b","18588f3cbd1e72ec9d39d4a5010b1e59ee2ec667","7431156e0604cdc9469bff5c73d2cf20b0883edc","b69982e110cb9ba31e7a2ab018010b0031f5ceb1","aec27de5f13a5495577ca7ba27fc8b10a04ac89f"]',
			true
		);

		$file_relative_lines = json_decode(
			'{"1":1,"2":2,"3":3,"4":4,"5":5,"6":6,"7":7}',
			true
		);

		$issues_filtered = vipgoci_issues_filter_irrellevant(
			$file_name,
			$file_issues_arr,
			$file_blame_log,
			$pr_item_commits,
			$file_relative_lines
		);

		$this->assertSame(
			array(
				array(
					'message' => 'json_encode() is discouraged. Use wp_json_encode() instead.',
					'source' => 'WordPress.WP.AlternativeFunctions.json_encode_json_encode',
					'severity' => 5,
					'fixable' => false,
					'type' => 'WARNING',
					'line' => 7,
					'column' => 6,
					'level' => 'WARNING',
				)
			),
			$issues_filtered
		);
	}
}

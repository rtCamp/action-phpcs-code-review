<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class SvgScanLookForSpecificTokensTest extends TestCase {
	/**
	 * @covers ::vipgoci_svg_look_for_specific_tokens
	 */
	public function testSpecificTokens1() {
		$temp_file_name = tempnam(
			sys_get_temp_dir(),
			'svg-look-for-specific-tokens1.svg'
		);

		file_put_contents(
			$temp_file_name,
			'<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" height="600px" id="Layer_1" width="600px" x="0px" y="0px" xml:space="preserve">
			    <a href="javascript:alert(2)">test 1</a>
			    <a xlink:href="javascript:alert(2)">test 2</a>
			    <a href="#test3">test 3</a>
			    <a>test 4</a>
			    <?php
				echo "foo";
			    ?>

			    <a xmlns=\'http://www.w3.org/2000/svg\'>test 5</a>
			    <a xmlns=\'http://www.w3.org/2000/svg\'>test 6</a>
			</svg>'
		);

		$results = array(
			'totals'	=> array(
				'errors'	=> 0,
				'warnings'	=> 0,
				'fixable'	=> 0,
			),
			'files'		=> array(),
		);

		vipgoci_svg_look_for_specific_tokens(
			array(
				'<?php',
				'<=',
				'<foo ',
			),
			$temp_file_name,
			$results
		);

		$results_expected = json_decode(
			'{"totals":{"errors":1,"warnings":0,"fixable":0},"files":{"' . addcslashes( $temp_file_name, '/' ) . '":{"errors":1,"messages":[{"message":"Found forbidden tag in SVG file: \'<?php\'","line":6,"level":"ERROR"}]}}}',
			true
		);


		unlink(
			$temp_file_name
		);

		$this->assertSame(
			$results_expected,
			$results
		);
	}
}

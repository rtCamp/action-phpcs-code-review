<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class OptionsGenericSupportCommentsMatchTest extends TestCase {
	public function setUp(): void {
		$this->options = array();
	}
	
	public function tearDown(): void {
		$this->options = null;
	}

	/**
	 * @covers ::vipgoci_option_generic_support_comments_match
	 */
	public function testOptionGenericSupportCommentsMatch () {
		$this->options['myoption1'] =
			'1:key1=value1,key2=value2,key3=value3,key4=value4a,key4=value4b|||2:key1=value1,key10=value10,key20=value20a,key20=value20b,key30=value30';

		vipgoci_option_generic_support_comments_match(
			$this->options,
			'myoption1'
		);

		$this->assertSame(
			array(
				'1' => array(
					'key1' => array( 'value1' ),
					'key2' => array( 'value2' ),
					'key3' => array( 'value3' ),
					'key4' => array( 'value4a', 'value4b' ),
				),

				'2' => array(
					'key1' => array( 'value1' ),
					'key10' => array( 'value10' ),
					'key20' => array( 'value20a', 'value20b' ),
					'key30' => array( 'value30' ),
				),
			),

			$this->options['myoption1']
		);
	}
}

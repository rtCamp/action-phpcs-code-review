<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class OptionsGenericSupportCommentsProcessTest extends TestCase {
	public function setUp(): void {
		$this->options = array();
	}

	public function tearDown(): void {
		$this->options = null;
	}

	/**
	 * @covers ::vipgoci_option_generic_support_comments_process
	 */
	public function testOptionGenericSupportCommentProcessBoolean() {
		$this->options['myoption1'] =
			'1:false|||5:true|||10:false|||15:trUE';

		vipgoci_option_generic_support_comments_process(
			$this->options,
			'myoption1',
			'boolean'
		);

		$this->assertSame(
			array(
				1	=> false,
				5	=> true,
				10	=> false,
				15	=> true,
			),
			$this->options['myoption1']
		);
	}

	/**
	 * @covers ::vipgoci_option_generic_support_comments_process
	 */
	public function testOptionGenericSupportCommentProcessStringStringNotLower() {
		$this->options['myoption2'] =
			'3:bar|||6:foo|||9:bar|||12:foo|||15:false|||20:AbCdEfG';

		vipgoci_option_generic_support_comments_process(
			$this->options,
			'myoption2',
			'string',
			false
		);

		$this->assertSame(
			array(
				3	=> 'bar',
				6	=> 'foo',
				9	=> 'bar',
				12	=> 'foo',
				15	=> 'false',
				20	=> 'AbCdEfG',
			),
			$this->options['myoption2']
		);
	}

	/**
	 * @covers ::vipgoci_option_generic_support_comments_process
	 */
	public function testOptionGenericSupportCommentProcessStringStringLower() {
		$this->options['myoption2'] =
			'3:bar|||6:foo|||9:bar|||12:foo|||15:false|||20:AbCdEfG';

		vipgoci_option_generic_support_comments_process(
			$this->options,
			'myoption2',
			'string',
			true
		);

		$this->assertSame(
			array(
				3	=> 'bar',
				6	=> 'foo',
				9	=> 'bar',
				12	=> 'foo',
				15	=> 'false',
				20	=> 'abcdefg',
			),
			$this->options['myoption2']
		);
	}

	/**
	 * @covers ::vipgoci_option_generic_support_comments_process
	 */
	public function testOptionGenericSupportCommentProcessArrayNotLower() {
		$this->options['myoption3'] =
			'3:foo,bar,test|||6:test,foo,foo|||9:aaa,bbb,ccc|||12:ddd|||15:|||20:AbCdEfG';

		vipgoci_option_generic_support_comments_process(
			$this->options,
			'myoption3',
			'array',
			false
		);

		$this->assertSame(
			array(
				3	=> array(
					'foo', 'bar', 'test'
				),
				6	=> array(
					'test', 'foo', 'foo',
				),
				9	=> array(
					'aaa', 'bbb', 'ccc',
				),
				12	=> array(
					'ddd',
				),
				15	=> array(
				),
				20	=> array(
					'AbCdEfG',
				)
			),
			$this->options['myoption3']
		);
	}

	/**
	 * @covers ::vipgoci_option_generic_support_comments_process
	 */
	public function testOptionGenericSupportCommentProcessArrayLower() {
		$this->options['myoption3'] =
			'3:foo,bar,test|||6:test,foo,foo|||9:aaa,bbb,ccc|||12:ddd|||15:|||20:AbCdEfG';

		vipgoci_option_generic_support_comments_process(
			$this->options,
			'myoption3',
			'array',
			true
		);

		$this->assertSame(
			array(
				3	=> array(
					'foo', 'bar', 'test'
				),
				6	=> array(
					'test', 'foo', 'foo',
				),
				9	=> array(
					'aaa', 'bbb', 'ccc',
				),
				12	=> array(
					'ddd',
				),
				15	=> array(
				),
				20	=> array(
					'abcdefg',
				)
			),
			$this->options['myoption3']
		);
	}
}

<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class PhpcsScanWriteXmlStandardFileTest extends TestCase {
	protected function setUp(): void {
		$this->xml_file_path = tempnam(
			sys_get_temp_dir(),
			'testWriteXmlStandardFile1'
		);
	}

	protected function tearDown(): void {
		unlink(
			$this->xml_file_path
		);
	}

	/**
	 * @covers ::vipgoci_phpcs_write_xml_standard_file
	 */
	public function testWriteXmlStandardFile1() {
		$phpcs_standards = array(
			'WordPress-VIP-Go',
			'MyStandard1'
		);

		$phpcs_sniffs = array(
			'WordPress.DB.RestrictedFunctions',
			'WordPress.DB.PreparedSQL'
		);

		vipgoci_phpcs_write_xml_standard_file(
			$this->xml_file_path,
			$phpcs_standards,
			$phpcs_sniffs
		);

		$xml_content = file_get_contents(
			$this->xml_file_path
		);
	
		$xml_content = str_replace(
			array( "\t", "\n", "\r" ),
			array( "", "", "" ),
			$xml_content
		);

		$this->assertSame(
			'<?xml version="1.0" encoding="UTF-8"?>' .
				'<ruleset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" name="PHP_CodeSniffer" xsi:noNamespaceSchemaLocation="phpcs.xsd">' .
				'<description>Custom coding standard</description>' .
				'<rule ref="WordPress-VIP-Go"/>' .
				'<rule ref="MyStandard1"/>' .
				'<rule ref="WordPress.DB.RestrictedFunctions"/>' .
				'<rule ref="WordPress.DB.PreparedSQL"/>' .
				'</ruleset>',
			$xml_content
		);
	}
}

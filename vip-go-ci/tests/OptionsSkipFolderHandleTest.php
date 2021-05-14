<?php

namespace Vipgoci\tests;

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

final class OptionsSkipFolderHandleTest extends TestCase {
	protected function setUp(): void {
		$this->options = array();
	}

	protected function tearDown(): void {
		$this->options = null;
	}

	/**
	 * @covers ::vipgoci_option_skip_folder_handle
	 */
	public function testOptionSkipFolderHandle1() {
		$this->options['phpcs-skip-folders'] =
			'var/tmp/,/client-mu-plugins/myplugin/,/plugins/myplugin/,/tmp/1,tmp/3';

		$this->options['lint-skip-folders'] =
			'var/tmp2/,/client-mu-plugins/otherplugin/,/plugins/otherplugin/,/tmp/2,tmp/4';

		vipgoci_option_skip_folder_handle(
			$this->options,
			'phpcs-skip-folders'
		);

		vipgoci_option_skip_folder_handle(
			$this->options,
			'lint-skip-folders'
		);

		$this->assertSame(
			array(
				'phpcs-skip-folders'	=> array(
					'var/tmp',
					'client-mu-plugins/myplugin',
					'plugins/myplugin',
					'tmp/1',
					'tmp/3',
				),

				'lint-skip-folders'	=> array(
					'var/tmp2',
					'client-mu-plugins/otherplugin',
					'plugins/otherplugin',
					'tmp/2',
					'tmp/4',
				),
			),
			$this->options
		);
	}
}

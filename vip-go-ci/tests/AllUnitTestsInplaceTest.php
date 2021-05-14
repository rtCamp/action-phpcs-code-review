<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class AllUnitTestsInplaceTest extends TestCase {
	public function testAllUnitTestsInPlace() {
		$files_arr = scandir("tests/");

		/*
		 * Filter away any files that
		 * should be in the tests/ directory,
		 * but should not be tested -- they
		 * are support files, etc. Also
		 * filter away files that will be
		 * tested, based on their names (end
		 * with "Test.php").
		 */
		$files_arr = array_filter(
			$files_arr,
			function( $file_item ) {
				switch( $file_item ) {
					case '.':
					case '..':
					case 'Skeleton.php':
					case 'IncludesForTests.php':
					case 'GitDiffsFetchUnfilteredTrait.php':
						/*
						 * Remove those away from
						 * the resulting array, are
						 * supporting files.
						 */
						return false;
						break;
				}

				$file_item_end = strpos(
					$file_item,
					'Test.php'
				);

				if ( false !== $file_item_end ) {
					/*
					 * If the filename ends with 'Test.php',
					 * skip this file from the final result.
					 */
					return false;
				}

				/*
				 * Any other files,
				 * keep them in.
				 */
				return true;
			}
		);

		/*
		 * We should end with an empty array.
		 */
		$this->assertSame(
			0,
			count( $files_arr )
		);
	}
}

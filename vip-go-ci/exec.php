<?php

/*
 * Call vipgoci_run() when not running a
 * unit-test.
 */
if (
	(
		( ! defined( 'VIPGOCI_UNIT_TESTING' ) ) ||
		( false === VIPGOCI_UNIT_TESTING )
	)
	&&
	(
		( ! defined( 'VIPGOCI_INCLUDED' ) ) ||
		( false === VIPGOCI_INCLUDED )
	)
) {
	/*
	 * 'main()' called
	 */
	$ret = vipgoci_run();

	exit( $ret );
}

<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class A00IrcApiAlertQueueTest extends TestCase {
	/**
	 * @covers ::vipgoci_irc_api_alert_queue
	 */
	public function testIrcQueue1() {
		vipgoci_irc_api_alert_queue(
			'mymessage1'
		);

		vipgoci_irc_api_alert_queue(
			'mymessage2'
		);

		$queue = vipgoci_irc_api_alert_queue(
			null,
			true
		);

		$this->assertSame(
			array(
				'mymessage1',
				'mymessage2',
			),
			$queue
		);
	}
}

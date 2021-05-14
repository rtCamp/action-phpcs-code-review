<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitHubOauth1HeadersGetTest extends TestCase {
	/**
	 * @covers ::vipgoci_oauth1_headers_get
	 */
	public function testOAuthHeaders1() {
		$oauth_method = 'GET';
		$oauth_url = 'https://automattic.com';

		$oauth_keys = array(
			'oauth_consumer_key'	=> '12',
			'oauth_consumer_secret'	=> '34',
			'oauth_token'		=> '56',
			'oauth_token_secret'	=> '78',
		);

		$actual_result_arr = explode(
			', ',
			vipgoci_oauth1_headers_get(
				$oauth_method,
				$oauth_url,
				$oauth_keys
			)
		);

		$actual_result_arr = array_map(
			function( $item) {
				$item_arr = explode(
					'="',
					$item
				);

				$item_arr[1] = rtrim(
					$item_arr[1],
					'"'
				);

				return $item_arr;
			},

			$actual_result_arr
		);

		$actual_result_arr_new = array();

		foreach( $actual_result_arr as $item ) {
			$actual_result_arr_new[ $item[0] ] = $item[1];
		}

		$this->assertSame(
			'12',
			$actual_result_arr_new[ 'OAuth oauth_consumer_key' ]
		);

		$this->assertSame(
			'56',
			$actual_result_arr_new[ 'oauth_token' ]
		);

		$this->assertSame(
			$actual_result_arr_new[ 'oauth_signature_method' ],
			'HMAC-SHA1'
		);

		$this->assertTrue(
			isset(
				$actual_result_arr_new[ 'oauth_timestamp' ]
			)
			&&
			$actual_result_arr_new[ 'oauth_timestamp' ] > 0
		);

		$this->assertTrue(
			is_string(
				$actual_result_arr_new[ 'oauth_nonce' ]
			)
			&&
			strlen(
				$actual_result_arr_new[ 'oauth_nonce' ]
			)
		);

		$signature_expected = hash_hmac(
			'sha1',
			strtoupper( $oauth_method ) . '&' .
				rawurlencode( $oauth_url ) . '&' .
				rawurlencode(
					rawurlencode( 'oauth_consumer_key' ) . '=' . rawurlencode( $oauth_keys[ 'oauth_consumer_key' ] ) . '&' .
					rawurlencode( 'oauth_nonce' ) . '=' . rawurlencode( $actual_result_arr_new[ 'oauth_nonce' ] ) . '&' .
					rawurlencode( 'oauth_signature_method' ) . '=' . rawurlencode( $actual_result_arr_new[ 'oauth_signature_method' ] ) . '&' .
					rawurlencode( 'oauth_timestamp' ) . '=' . rawurlencode( $actual_result_arr_new[ 'oauth_timestamp' ] ) . '&' .
					rawurlencode( 'oauth_token' ) . '=' . rawurlencode( $oauth_keys[ 'oauth_token' ] )
				),
			$oauth_keys['oauth_consumer_secret'] . '&' . $oauth_keys['oauth_token_secret'],
			true
		);

		$signature_expected = rawurlencode(
			base64_encode( 
				$signature_expected
			)
		);

		$this->assertSame(
			$signature_expected,
			$actual_result_arr_new['oauth_signature']
		);
	}
}

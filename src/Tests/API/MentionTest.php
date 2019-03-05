<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@revive.today>
 * @license MIT
 */

namespace Kebabble\Tests\API;

use Kebabble\API\Mention;

use \PHPUnit\Framework\TestCase;
use WP_Mock;
use WP_Mock\Functions;
use DI\Container;

class MentionTest extends TestCase {
    protected $mention;
    public function setUp() {
        $this->mention = ( new Container() )->get( 'Kebabble\API\Mention' );
		WP_Mock::setUp();
	}

	public function tearDown() {
		WP_Mock::tearDown();
	}
	
	public function testCorrectOrderDetermination() {
		$food_items = [ 'Food Roll' ];

		$response = $this->mention->decipher_order( 'Please sir, may I have a food roll?', $food_items );
		$this->assertTrue( isset( $response['operator'], $response['item'], $response['for'] ) );
		$this->assertEquals( $response['item'], 'Food Roll' );
		$this->assertEquals( $response['operator'], 'add' );

		$response = $this->mention->decipher_order( 'remove my fOod ROLL please!', $food_items );
		$this->assertTrue( isset( $response['operator'], $response['item'], $response['for'] ) );
		$this->assertEquals( $response['item'], 'Food Roll' );
		$this->assertEquals( $response['operator'], 'remove' );

		$response = $this->mention->decipher_order( 'This string is invalid!', $food_items );
		$this->assertNull( $response );
	}
}

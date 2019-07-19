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

use PHPUnit\Framework\TestCase;
use WP_Mock;
use WP_Mock\Functions;
use DI\Container;

/**
 * Kebabble mentions test case.
 */
class MentionTest extends TestCase {
	/**
	 * Mentions.
	 *
	 * @var Mention
	 */
	protected $mention;

	/**
	 * Test class constructor.
	 */
	public function setUp():void {
		$this->mention = ( new Container() )->get( Mention::class );
		WP_Mock::setUp();
	}

	/**
	 * Test class destructor.
	 */
	public function tearDown():void {
		WP_Mock::tearDown();
	}

	/**
	 * Rudimentary tests to see if basic operation works.
	 */
	public function testCorrectOrderDetermination() {
		$food_items = [
			'Large Food Roll',
			'Food Roll',
			'Medium Food Roll',
			'Drink',
		];

		$response = $this->mention->decipher_order( 'Please sir, may I have a food roll?', $food_items );
		$this->assertTrue( isset( $response['operator'], $response['item'], $response['for'] ) );
		$this->assertEquals( $response['item'], 'Food Roll' );
		$this->assertEquals( $response['operator'], 'add' );

		$response = $this->mention->decipher_order( 'remove my fOod ROLL please!', $food_items );
		$this->assertTrue( isset( $response['operator'], $response['item'], $response['for'] ) );
		$this->assertEquals( $response['item'], 'Food Roll' );
		$this->assertEquals( $response['operator'], 'remove' );

		$response = $this->mention->decipher_order( 'can I get a large food roll?', $food_items );
		$this->assertTrue( isset( $response['operator'], $response['item'], $response['for'] ) );
		$this->assertEquals( $response['item'], 'Large Food Roll' );
		$this->assertEquals( $response['operator'], 'add' );

		$response = $this->mention->decipher_order( 'This string is invalid!', $food_items );
		$this->assertNull( $response );
	}
}

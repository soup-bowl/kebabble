<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@revive.today>
 * @license MIT
 */

namespace KebabbleTests\API;

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
}

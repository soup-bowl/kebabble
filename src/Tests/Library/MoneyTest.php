<?php
/**
 * Food ordering management system for WordPress.
 *
 * @package kebabble
 * @author soup-bowl <code@revive.today>
 * @license MIT
 */

namespace Kebabble\Tests\Library;

use Kebabble\Library\Money;

use \PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase {
	public function testUKMoneyOutputSyntax() {
		$money = new Money();
		$this->assertEquals( $money->output( 0 ), '0p' );
		$this->assertEquals( $money->output( 99 ), '99p' );
		$this->assertEquals( $money->output( 101 ), '£1.01' );
		$this->assertEquals( $money->output( -1 ), '0p' );
	}
}

<?php
namespace Billmate\WooCommerce\Tests;

use Billmate\WooCommerce\Tests\Inc\PluginTestCase;
use Brain\Monkey\Functions;

/**
 * A class for testing basics and show how Unit tests can be made for WP.
 *
 * https://swas.io/blog/wordpress-plugin-unit-test-with-brainmonkey/
 */
class BootstrapTest extends PluginTestCase {

    /**
     * An example of how to use
     *
     * @throws \Brain\Monkey\Expectation\Exception\ExpectationArgsRequired
     */
    public function test_get_option() {
        Functions\expect( 'get_option' )
            ->once() // called once
            ->with( 'plugin-settings', get_option( 'plugin-settings', [] ) )
            ->andReturn( [] );
    }

    /**
     * A dummy test assertion
     */
    public function test_dummy() {
        $this->assertTrue( true );
    }
}
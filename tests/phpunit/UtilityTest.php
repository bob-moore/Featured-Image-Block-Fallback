<?php
/**
 * Utility Test Cases
 *
 * PHP Version 8.2
 *
 * @package simple-menu-block
 * @subpackage PHPUnit/Tests
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://www.midwestfamilymadison.com
 * @since   1.0.0
 */

namespace Rcm\SimpleMenuBlock;

use WP_Mock;
use WP_Mock\Tools\TestCase;
use stdClass;

/**
 * Services/PostMeta Test Case
 * 
 * @subpackage PHPUnit/Tests/Services
 */
final class UtilityTest extends TestCase
{
    /**
     * Set up the test environment.
     */
    public function setUp(): void
    {
        parent::setUp();
        WP_Mock::setUp();
    }

    /**
     * Tear down the test environment.
     */
    public function tearDown(): void
    {
        WP_Mock::tearDown();
        parent::tearDown();
    }

    /**
     * Test get_menu_layout with a layout specified in block_attributes.
     * 
     * @covers \Rcm\SimpleMenuBlock\get_menu_layout
     */
    public function testGetMenuLayoutWithSpecifiedLayout()
    {
        $menu_args = new stdClass();
        $menu_args->block_attributes = ['layout' => 'vertical'];

        $result = get_menu_layout( $menu_args );

        $this->assertEquals( 'vertical', $result );
    }

    /**
     * Test get_menu_layout with no layout specified in block_attributes.
     * 
     * @covers \Rcm\SimpleMenuBlock\get_menu_layout
     */
    public function testGetMenuLayoutWithNoSpecifiedLayout()
    {
        $menu_args = new stdClass();
        $menu_args->block_attributes = [];

        $result = get_menu_layout( $menu_args );

        $this->assertEquals( 'horizontal', $result );
    }

    /**
     * Test get_menu_layout with no block_attributes.
     * 
     * @covers \Rcm\SimpleMenuBlock\get_menu_layout
     */
    public function testGetMenuLayoutWithNoBlockAttributes()
    {
        $menu_args = new stdClass();

        $result = get_menu_layout( $menu_args );

        $this->assertEquals( 'horizontal', $result );
    }

    /**
     * Test is_simple_menu_block with valid simple menu block.
     * 
     * @covers \Rcm\SimpleMenuBlock\is_simple_menu_block
     */
    public function testIsSimpleMenuBlockValid()
    {
        $menu_args = new stdClass();
        $menu_args->generator = 'simple-menu-block';
        $menu_args->block_attributes = ['layout' => 'horizontal'];

        $this->assertTrue( is_simple_menu_block( $menu_args ) );
    }

    /**
     * Test is_simple_menu_block with missing generator.
     * 
     * @covers \Rcm\SimpleMenuBlock\is_simple_menu_block
     */
    public function testIsSimpleMenuBlockMissingGenerator()
    {
        $menu_args = new stdClass();
        $menu_args->block_attributes = ['layout' => 'horizontal'];

        $this->assertFalse( is_simple_menu_block( $menu_args ) );
    }

    /**
     * Test is_simple_menu_block with incorrect generator.
     * 
     * @covers \Rcm\SimpleMenuBlock\is_simple_menu_block
     */
    public function testIsSimpleMenuBlockIncorrectGenerator()
    {
        $menu_args = new stdClass();
        $menu_args->generator = 'other-menu-block';
        $menu_args->block_attributes = ['layout' => 'horizontal'];

        $this->assertFalse( is_simple_menu_block( $menu_args ) );
    }

    /**
     * Test is_simple_menu_block with missing block_attributes.
     * 
     * @covers \Rcm\SimpleMenuBlock\is_simple_menu_block
     */
    public function testIsSimpleMenuBlockMissingBlockAttributes()
    {
        $menu_args = new stdClass();
        $menu_args->generator = 'simple-menu-block';

        $this->assertFalse( is_simple_menu_block( $menu_args ) );
    }
}
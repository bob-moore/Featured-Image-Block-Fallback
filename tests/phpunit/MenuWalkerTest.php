<?php
/**
 * Menu walker Test Cases
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
final class MenuWalkerTest extends TestCase
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
     * Undocumented function
     *
     * @covers \Rcm\SimpleMenuBlock\inject_nav_item_wrapper
     * @return void
     */
    public function testInjectNavItemWrapper()
	{
		$menu_item = \Mockery::mock('WP_Post');
		$menu_item->classes = [ 'menu-item-has-children' ];
		$item_output = '<li>Menu Item</li>';
        $menu_args = new stdClass();

        \Mockery::mock('alias:Rcm\SimpleMenuBlock\is_simple_menu_block')
            ->shouldReceive('is_simple_menu_block')
            ->andReturn(true);

        \Mockery::mock('alias:Rcm\SimpleMenuBlock\get_menu_layout')
            ->shouldReceive('get_menu_layout')
            ->andReturn(true);

        // WP_Mock::userFunction('alias:Rcm\SimpleMenuBlock\is_simple_menu_block')
        //     ->once()
        //     ->andReturn(true);

        // WP_Mock::userFunction('alias:Rcm\SimpleMenuBlock\get_menu_layout')
        //     ->once()
        //     ->andReturn('vertical');


		$result = inject_nav_item_wrapper($item_output, $menu_item, 1, $menu_args);

        $error_message = sprintf( "Expected container span not found.\nActual output:\n%s",
            $result
        );

        $expected = '<span class="nav-item-container"><li>Menu Item</li></span>';

		$this->assertEquals($expected, $result, $error_message);
	}

    // public function testInjectNavItemInjectsButton()
	// {
	// 	$menu_item = \Mockery::mock('WP_Post');
	// 	$menu_item->classes = [ 'menu-item-has-children' ];
	// 	$item_output = '<li>Menu Item</li>';
    //     $menu_args = new stdClass();

    //     WP_Mock::userFunction('is_simple_menu_block')
    //         ->once()
    //         ->andReturn(true);

    //     WP_Mock::userFunction('get_menu_layout')
    //         ->once()
    //         ->andReturn('dropdown');


	// 	$result = inject_nav_item_wrapper($item_output, $menu_item, 1, $menu_args);

    //     $error_message = sprintf( "Expected container span not found.\nActual output:\n%s",
    //         $result
    //     );

    //     $expected = '<button class="sub-menu-toggle';

	// 	$this->assertStringContainsString($expected, $result, $error_message);
	// }
       /**
     * Undocumented function
     *
     * @covers \Rcm\SimpleMenuBlock\inject_nav_item_wrapper
     * @return void
     */
    // public function testInjectNavItemWrapperWithButton()
	// {
	// 	$menu_item = \Mockery::mock('WP_Post');
	// 	$menu_item->classes = ['menu-item'];

	// 	$item_output = '<li>Menu Item</li>';
	// 	$depth = 1;

	// 	$menu_args = new stdClass();
	// 	$menu_args->block_attributes = ['layout' => 'dropdown'];
	// 	$menu_args->generator = 'simple-menu-block';

	// 	$result = inject_nav_item_wrapper($item_output, $menu_item, $depth, $menu_args);

    //     $error_message = sprintf( "Expected container span not found.\nActual output:\n%s",
    //         $result
    //     );

    //     $expected = '<span class="nav-item-container"><li>Menu Item</li></span>';

	// 	$this->assertEquals($expected, $result, $error_message);
	// }

    // /**
    //  * Test inject_nav_item_wrapper.
    //  * 
    //  * inject_nav_item_wrapper
    //  */
    // public function testInjectNavItemWrapper()
    // {
    //     $menu_item = \Mockery::mock('WP_Post');
    //     $menu_item->classes = ['menu-item'];

    //     $item_output = '<li>Menu Item</li>';
    //     $depth = 1;

    //     $menu_args = new stdClass();
    //     $menu_args->block_attributes = ['layout' => 'vertical'];
    //     $menu_args->generator = 'simple-menu-block';

    //     $result = inject_nav_item_wrapper($item_output, $menu_item, $depth, $menu_args);

    //     // Add your assertions here
    //     $this->assertStringContainsString('<li>Menu Item</li>', $result);
    // }
}
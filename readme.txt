![banner-1544x500](assets/banner-1544x500.jpg)

=== Featured Image Block Fallback ===
Contributors: Bob Moore
Tags: block-extension, featured-image, gutenberg, block editor
Requires at least: 6.5
Tested up to: 6.7.2
Stable tag: 0.3.1
Requires PHP: 8.2
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

![Version](https://img.shields.io/badge/version-0.3.1-blue)
![WordPress](https://img.shields.io/badge/WordPress-6.5%2B-3858e9?logo=wordpress&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green)
![Lint and Build](https://github.com/bob-moore/Featured-Image-Block-Fallback/actions/workflows/lint-build.yml/badge.svg)
[![Try it in the WordPress Playground](https://img.shields.io/badge/Try_in_Playground-v0.3.1-blue?logo=wordpress&logoColor=%23fff&labelColor=%233858e9&color=%233858e9)](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/bob-moore/Featured-Image-Block-Fallback/main/_playground/blueprint-github.json)

Add a fallback image to the core/post-featured-image block for posts that have no featured image set.

== Description ==

Featured Image Block Fallback enhances the core/post-featured-image block by letting you specify a fallback image that displays whenever a post lacks a featured image. No global settings, no unnecessary bloat—just a targeted enhancement that keeps your layouts polished.

* Adds a fallback image control to the core/post-featured-image block in the editor.
* Optionally skips the fallback when the post already contains an inline image.
* Filterable fallback ID (`featured_image_block_fallback_id`) for per-post-type customization.
* Provides a `BasicPlugin` interface for type-safe Composer integration.
* Reusable as a Composer library without activating the plugin.

== Installation ==

= Install as a WordPress plugin =

1. Download the latest release zip from GitHub.
2. In WordPress admin, go to Plugins > Add New Plugin > Upload Plugin.
3. Upload and activate Featured Image Block Fallback.

= Install via Composer in your own plugin or theme =

1. Add the repository and require the package:

`composer require bmd/featured-image-block-fallback`

2. Ensure Composer autoloading is loaded in your bootstrap:

`require_once __DIR__ . '/vendor/autoload.php';`

3. Instantiate and mount the service:

`use Bmd\FeaturedImageBlockFallback;`
`$plugin = new FeaturedImageBlockFallback( plugin_dir_url( __FILE__ ), plugin_dir_path( __FILE__ ) );`
`$plugin->mount();`

== Frequently Asked Questions ==

= Is this plugin available on the WordPress Plugin Repository? =

No. It is distributed via GitHub only.

= Can I set a different fallback image per post type? =

Yes. Use the `featured_image_block_fallback_id` filter:

`add_filter( 'featured_image_block_fallback_id', function( int $id, array $block ): int {`
`    if ( get_post_type( get_the_ID() ) === 'my-post-type' ) {`
`        return 123;`
`    }`
`    return $id;`
`}, 10, 2 );`

= Can I override the asset path or URL without subclassing? =

Yes. Filter `featured_image_block_fallback_plugin_path` or `featured_image_block_fallback_plugin_url` to redirect asset resolution.

= Can I use this without activating the plugin? =

Yes. Because `composer.json` defines this as a `library`, you can include it in your own plugin or theme and call `mount()` yourself.

== Changelog ==

= 0.3.1 =

* Added scoped `bmd/github-wp-updater` bootstrap for GitHub-delivered WordPress updates.
* Added `wpify/scoper` configuration and a dedicated scoped runtime dependency manifest.
* Refreshed release packaging for production GitHub distribution.

= 0.3.0 =

* Added `BasicPlugin` interface; `FeaturedImageBlockFallback` now implements it.
* Renamed `$uri`/`setUri()` to `$url`/`setUrl()` for consistency with the interface.
* Constructor now accepts URL and path directly with sanitized defaults.
* Added `buildPath()` and `buildUrl()` with filterable asset resolution.
* Added `getScriptAssets()` helper supporting both `index.asset.php` and `index.assets.php`.
* Fixed plugin bootstrap to pass URL and path to the constructor.
* Removed hardcoded `version` field from `composer.json`.

= 0.2.0 =

* Restructured plugin to follow standard single-use plugin structure.

= 0.1.8 =

* Updated typo in plugin meta data.
* Explicitly declared asset path in the main plugin file.

= 0.1.5 =

* Added external updater dependency.

= 0.1.4 =

* Finalized initial public stable release.

= 0.1.0 =

* Initial upload.

== Upgrade Notice ==

= 0.3.1 =

Adds the scoped GitHub updater bootstrap and release packaging updates so future releases can be delivered through the native WordPress update UI.

= 0.3.0 =

Adds the `BasicPlugin` interface, filterable asset resolution, and fixes the plugin bootstrap URL/path wiring. If you were using `setUri()` directly, update calls to `setUrl()`.

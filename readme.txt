![banner-1544x500](assets/banner-1544x500.jpg)

=== Featured Image Block Fallback ===
Contributors: Bob Moore
Tags: block-extension, featured-image, gutenberg, block editor
Requires at least: 6.5
Tested up to: 6.7.2
Stable tag: 0.3.4
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
* Reusable as a Composer library with or without a parent PHP-DI container.

== Installation ==

= Install as a WordPress plugin =

1. Download the latest release zip from GitHub.
2. In WordPress admin, go to Plugins > Add New Plugin > Upload Plugin.
3. Upload and activate Featured Image Block Fallback.

= Install via Composer in your own plugin =

Add the package:

`composer require bmd/featured-image-block-fallback`

If your parent plugin uses PHP-DI, require this package's `inc/definitions.php`, merge those definitions into your parent container, and override `Bmd\FeaturedImageBlockFallback\Services\FilePathResolver` plus `Bmd\FeaturedImageBlockFallback\Services\UrlResolver` so their constructor values point to this dependency's installed path and URL.

Then get `Bmd\FeaturedImageBlockFallback\Controller` from your parent container and call `register()`.

If your parent plugin does not use PHP-DI, instantiate `Bmd\FeaturedImageBlockFallback\Main` and let this package build its own container:

`use Bmd\FeaturedImageBlockFallback\Main;`
`$plugin = new Main( array( 'package' => 'featured_image_block_fallback', 'path' => plugin_dir_path( __FILE__ ) . 'vendor/bmd/featured-image-block-fallback/', 'url' => plugin_dir_url( __FILE__ ) . 'vendor/bmd/featured-image-block-fallback/' ) );`
`$plugin->register();`

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

Yes. Because `composer.json` defines this as a `library`, you can include it in your own plugin and either register its controller through your parent PHP-DI container or instantiate `Main`.

== Changelog ==

= 0.3.4 =

* Fixed scoped dependency configuration so standalone ZIP installs can run alongside related plugins.

= 0.3.3 =

* Fixed package URL detection so plugin installs resolve from the root plugin file.
* Guarded theme-context detection against empty theme paths in non-WordPress test/bootstrap environments.

= 0.3.2 =

* Unified plugin architecture around `Main`, `Controller`, service providers, and PHP-DI definitions.
* Added release packaging with scoped vendor dependencies, a compiled container, Docker build support, and wp-env defaults.
* Removed old compatibility wrapper classes before public adoption.

= 0.3.1 =

* Added scoped `bmd/github-wp-updater` bootstrap for GitHub-delivered WordPress updates.
* Added `wpify/scoper` configuration and a dedicated scoped runtime dependency manifest.
* Refreshed release packaging for production GitHub distribution.

= 0.3.0 =

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

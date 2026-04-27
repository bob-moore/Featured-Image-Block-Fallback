![banner-1544x500](https://github.com/user-attachments/assets/2220933d-c599-4317-9f3c-51e3a973d847)

# Featured Image Block Fallback

![Version](https://img.shields.io/badge/version-0.3.1-blue)
![WordPress](https://img.shields.io/badge/WordPress-6.5%2B-3858e9?logo=wordpress&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green)
![Lint and Build](https://github.com/bob-moore/Featured-Image-Block-Fallback/actions/workflows/lint-build.yml/badge.svg)
[![Try it in the WordPress Playground](https://img.shields.io/badge/Try_in_Playground-v0.3.1-blue?logo=wordpress&logoColor=%23fff&labelColor=%233858e9&color=%233858e9)](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/bob-moore/Featured-Image-Block-Fallback/main/_playground/blueprint-github.json)

Add a fallback image to the core/post-featured-image block for posts that have no featured image set.

## What this does

The WordPress `core/post-featured-image` block is great—until a post doesn't have a featured image. By default, it simply renders nothing, leaving an awkward gap in your design.

Featured Image Block Fallback solves this by allowing you to specify a fallback image directly on the block. It displays whenever a post lacks a featured image—no global settings, no unnecessary bloat.

Whether you're building custom templates, using query loops, or designing unique layouts, this plugin ensures your site looks polished even when a featured image is missing.

## Features

- Adds a fallback image control to the `core/post-featured-image` block in the editor
- Optionally skips the fallback when the post already contains an inline image
- Filterable fallback ID (`featured_image_block_fallback_id`) for per-post-type customization
- Works as a standalone plugin or reusable Composer dependency

## Requirements

- WordPress 6.5+
- PHP 8.2+

## Installation

### As a WordPress plugin

1. Download the latest release ZIP from the [GitHub Releases page](https://github.com/bob-moore/Featured-Image-Block-Fallback/releases).
2. In WordPress admin, go to **Plugins > Add New Plugin > Upload Plugin**.
3. Upload the ZIP and activate **Featured Image Block Fallback**.

### As a Composer dependency

1. Require the package from your consuming plugin or theme:

```bash
composer require bmd/featured-image-block-fallback
```

2. Instantiate the plugin class and register its hooks in your bootstrap code:

```php
<?php

use Bmd\FeaturedImageBlockFallback;

$plugin = new FeaturedImageBlockFallback(
    plugin_dir_url( __FILE__ ),
    plugin_dir_path( __FILE__ )
);

$plugin->mount();
```

3. Ensure Composer autoloading is active in the consuming plugin or theme.

> **Note:** When included as a Composer dependency you are responsible for managing updates yourself, since it will no longer be part of the WordPress plugin ecosystem.

## Updates

This plugin is **not available in the WordPress Plugin Repository**. Updates are pushed directly from [GitHub](https://github.com/bob-moore/Featured-Image-Block-Fallback). If you'd like to submit it to the repository and provide support, feel free to fork it.

## Frequently Asked Questions

### Can I set a different fallback image for different post types?

Yes. Since the fallback image is set directly on the block, you can assign different fallback images for each query loop or post template that uses `core/post-featured-image`.

Developers can also customize the fallback dynamically using the `featured_image_block_fallback_id` filter:

```php
add_filter( 'featured_image_block_fallback_id', function( int $fallback_id, array $block ): int {
    if ( get_post_type( get_the_ID() ) === 'my-custom-post-type' ) {
        return 123; // Replace with your fallback image ID.
    }
    return $fallback_id;
}, 10, 2 );
```

### Can I override the asset path or URL without subclassing?

Yes. Filter `featured_image_block_fallback_plugin_path` or `featured_image_block_fallback_plugin_url` to redirect asset resolution.

### Is this plugin available on the official WordPress plugin repository?

No. It is distributed via GitHub only. If you'd like to submit it to the repository and maintain support, you're welcome to fork it.

## Changelog

### 0.3.1

- Added scoped `bmd/github-wp-updater` bootstrap so GitHub releases can be delivered through the WordPress update UI.
- Added `wpify/scoper` release packaging configuration and a dedicated scoped dependency manifest.
- Refreshed release packaging workflow and prepared production release artifacts for GitHub distribution.

### 0.3.0

- Added `BasicPlugin` interface; `FeaturedImageBlockFallback` now implements it.
- Renamed `$uri`/`setUri()` to `$url`/`setUrl()` for consistency with the interface.
- Constructor now accepts URL and path directly with sanitized defaults.
- Added `buildPath()` and `buildUrl()` with filterable asset resolution (`featured_image_block_fallback_plugin_path` / `featured_image_block_fallback_plugin_url`).
- Added `getScriptAssets()` helper supporting both `index.asset.php` and `index.assets.php` naming conventions.
- Fixed plugin bootstrap to pass URL and path to the constructor instead of `mount()`.
- Removed hardcoded `version` field from `composer.json`.

### 0.1.8

- Updated typo in plugin metadata.
- Explicitly declared asset path for the updater in the main plugin file.

### 0.1.5

- Added external updater dependency.

### 0.1.4

- Finalized initial public stable release.

### 0.1.0 – 0.1.3

- Created GitHub updater integration.
- Version bumps for testing updater and releases.
- Initial upload.

## License

GPL-2.0-or-later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).


![banner-1544x500](https://github.com/user-attachments/assets/2220933d-c599-4317-9f3c-51e3a973d847)

# Featured Image Block Fallback

**Contributors:** Bob Moore  
**Tags:** block-extension, featured-image, plugin  
**Requires at least:** 6.5  
**Tested up to:** 6.7.2  
**Stable tag:** 0.1.3  
**Requires PHP:** 8.2  
**License:** GPL-2.0-or-later  
**License URI:** [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)  

## Description  

The WordPress core/post-featured-image block is great—until a post doesn’t have a featured image. By default, it simply renders nothing, leaving an awkward gap in your design.

Featured Image Block Fallback solves this by allowing you to specify a fallback image that will display whenever a post lacks a featured image. No unnecessary bloat, no complex settings—just a simple, effective solution to keep your layouts looking polished at all times.

No extra setup, no global settings—just a simple enhancement that ensures your posts always have a visual presence. Whether you’re building custom templates, using query loops, or designing unique layouts, this plugin ensures your site looks polished, even when a featured image is missing.

## Features:

✔ Adds a fallback image option to the core/post-featured-image block.
✔ Ensures a consistent and professional look across your site.
✔ Lightweight and efficient—does exactly what it says, nothing more.

## Installation  

1. Download the [latest release](https://github.com/bob-moore/Featured-Image-Block-Fallback/releases) zip file.  
2. Upload the plugin files to the `/wp-content/plugins/` directory, or install it via "Upload Plugin" in WordPress.  
3. Activate the plugin through the 'Plugins' screen in WordPress.  

### Updates  
This plugin is **not available in the WordPress Plugin Repository**. Instead, updates are pushed directly from [GitHub](https://github.com/bob-moore/Featured-Image-Block-Fallback). If you'd like to submit it to the repository yourself and provide support, feel free to fork it!  

## Frequently Asked Questions  

### **Is this plugin available on the official WordPress plugin repository?**  
No, this plugin is distributed via GitHub only. If you’d like to submit it to the repository and maintain support, you’re welcome to fork it.  

### **Will I receive updates?**  
Yes. Updates are pushed directly from [GitHub](https://github.com/bob-moore/Featured-Image-Block-Fallback) instead of the WordPress Plugin Repository.  

### **Can I set a different fallback image for different post types?**  
Yes! Since the fallback image is set directly on the block itself, you can assign different fallback images for each query, loop, or post template where the `core/post-featured-image` block is used.  

Additionally, developers can customize the fallback image dynamically using the `featured_image_block_fallback_id` filter. Here’s an example of how to set a different fallback image for a specific post type:  

```php
/**
 * Fallback image ID filter.
 *
 * @param int   $fallback_id The fallback image ID.
 * @param array $block The block attributes.
 *
 * @return int The fallback image ID.
 */
function my_theme_featured_image_fallback( int $fallback_id, array $block ): int
{
    if ( get_post_type( get_the_ID() ) === 'my-custom-post-type' ) {
        $fallback_id = 123; // Replace with your fallback image ID.
    }

    return $fallback_id;
}
add_filter( 'featured_image_block_fallback_id', 'my_theme_featured_image_fallback', 10, 2 );
```

## Changelog

### 0.1.4
	- Finalized initial public stable release.

### 0.1.1 - 0.1.3
	- Created GitHub updater integration.
	- Version bumps for testing updater and releases.

### 0.1.0
	- Initial upload.

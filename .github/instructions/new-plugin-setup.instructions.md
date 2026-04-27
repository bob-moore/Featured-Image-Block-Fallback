---
applyTo: "{composer.json,phpunit.xml,tests/**,_playground/**,.github/workflows/**,readme.md,readme.txt,stylelint.config.js,assets/**}"
description: "Use when: setting up a new plugin with PHPUnit tests, WordPress Playground demo, GitHub Actions CI, and updated readme files with banner and badges."
---

# New Plugin Setup Runbook

Execute these four tasks in order. Each task ends with a commit. Read the plugin's existing source files before starting — you need to know the main class name, namespace, WordPress hooks used, and block names before writing tests or demo content.

---

## Pre-flight: Read the plugin

Before any task, read:

- `inc/` — main class(es), namespace, constructor signature, public methods, WordPress hooks registered in `mount()`
- `plugin.php` — plugin slug, version, and GitHub repo URL
- `composer.json` — package name, namespace, existing scripts
- `package.json` — available npm scripts (especially `lint:js`, `lint:css`, `compile`, `build`)
- `src/` — look for `*.module.scss` files (needed for stylelint fix in Task 3)

---

## Task 1: PHPUnit test suite

### 1a. Update `composer.json`

Ensure these entries exist (add only what is missing):

```json
"require-dev": {
    "10up/wp_mock": "^1.0"
},
"autoload-dev": {
    "psr-4": {
        "Bmd\\Tests\\": "./tests"
    }
},
"scripts": {
    "test": "./vendor/bin/phpunit"
}
```

Run `composer update --no-interaction` to install new dev dependencies.

### 1b. Create `phpunit.xml`

```xml
<?xml version="1.0"?>
<phpunit
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/9.6/phpunit.xsd"
    bootstrap="tests/bootstrap.php"
    colors="true"
    verbose="false"
>
    <testsuites>
        <testsuite name="Unit Tests">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

### 1c. Create `tests/bootstrap.php`

```php
<?php
require_once dirname( __DIR__, 1 ) . '/vendor/autoload.php';
require_once __DIR__ . '/wp-function-mocks.php';
WP_Mock::setUsePatchwork( true );
WP_Mock::bootstrap();
```

### 1d. Create `tests/wp-function-mocks.php`

Start from `.github/instructions/tests/wp-function-mocks.php`. Adapt it for this plugin:

- Keep all standard WordPress function stubs (`trailingslashit`, `wp_normalize_path`, `esc_url_raw`, `esc_html`, `apply_filters`, `add_action`, `add_filter`, etc.).
- Add stub classes for any WordPress objects the plugin uses that are not in the sample (e.g. `WP_Post`, `WP_Block`, `WP_HTML_Tag_Processor`).
- If the plugin uses `WP_HTML_Tag_Processor`, use the improved version that actually iterates through HTML for `tag_name` queries — not just a stub that always returns `true`:

```php
if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
    class WP_HTML_Tag_Processor {
        private string $html;
        public function __construct( string $html ) { $this->html = $html; }
        public function next_tag( array $query = [] ): bool {
            $tag = strtolower( $query['tag_name'] ?? '' );
            if ( $tag === '' ) return false;
            return (bool) preg_match( '/<' . preg_quote( $tag, '/' ) . '[\s>\/]/i', $this->html );
        }
    }
}
```

### 1e. Create `tests/{MainClassName}Test.php`

Model on `.github/instructions/tests/ResponsiveGridExtensionTest.php`. Cover:

- Constructor sets URL and path (via `setUrl` / `setPath`).
- `mount()` registers the expected `add_action` / `add_filter` calls.
- `enqueueBlockAssets()` — test with asset file present and absent.
- Every public method and filter callback in the main class.
- Use **anonymous subclasses** to expose protected properties and methods needed for assertions.
- For filters that may return an empty string or `false`, use `WP_Mock::onFilter('filter_name')->with($arg)->reply($value)` rather than `WP_Mock::userFunction('apply_filters', ['return' => $value])` — the latter silently ignores falsy returns.

Run `composer test` and fix all failures before committing.

### 1f. Commit

```
tests: add PHPUnit test suite with WP_Mock
```

---

## Task 2: WordPress Playground

### 2a. Identify what the plugin needs to demonstrate

Read the plugin's block attributes and hooks to determine:

- What block is being extended and what attributes it adds.
- What conditions cause the plugin to activate (e.g. missing featured image, missing post type, etc.).
- What a visitor would see on a demo page vs. a post.

### 2b. Create `_playground/demo-content.xml`

Write a WordPress WXR file. Rules:

- Use post IDs **10 and above** — IDs 1–3 are taken by WordPress defaults and the importer will skip or renumber collisions.
- Include one **Page** (ID 10+) as the landing page. Its `post_content` must contain the block with plugin-specific attributes set. Use `<!-- wp:... -->` block comment syntax.
- Include **3–4 Posts** — at least one with a featured image (`_thumbnail_id` post meta) and at least two without, so the fallback behavior is visible.
- Include **Attachment** entries for any images. Use stable picsum.photos seed URLs:
  `https://picsum.photos/seed/{descriptive-seed}/{width}/{height}`
  Wire the attachment's `guid` and `<wp:attachment_url>` to the picsum URL. Set `_wp_attached_file` and `_wp_attachment_metadata` post meta accordingly.
- Wire featured image meta: on posts that have a featured image, add `<wp:postmeta>` with `<wp:meta_key>_thumbnail_id</wp:meta_key>` pointing to the attachment ID.
- Page slug must match what `landingPage` in the blueprint uses.

### 2c. Create `_playground/blueprint-github.json`

```json
{
    "$schema": "https://playground.wordpress.net/blueprint-schema.json",
    "landingPage": "/your-demo-page-slug/",
    "login": true,
    "features": { "networking": true },
    "steps": [
        {
            "step": "updateUserMeta",
            "meta": { "admin_color": "modern", "show_welcome_panel": 0 },
            "userId": 1
        },
        {
            "step": "setSiteOptions",
            "options": {
                "blogname": "Plugin Display Name",
                "permalink_structure": "/%postname%/"
            }
        },
        {
            "step": "installPlugin",
            "pluginZipFile": {
                "resource": "url",
                "url": "https://github.com/bob-moore/{Repo-Name}/releases/latest/download/{plugin-slug}.zip"
            }
        },
        {
            "step": "importWxr",
            "file": {
                "resource": "url",
                "url": "https://raw.githubusercontent.com/bob-moore/{Repo-Name}/main/_playground/demo-content.xml"
            }
        }
    ]
}
```

**Important:** The Playground URL query parameter is `?blueprint-url=...` — never `#blueprint-url`.

### 2d. Commit and push, then verify

```
playground: add blueprint and demo content
```

Push to `main`, then construct and test the Playground URL:

```
https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/bob-moore/{Repo-Name}/main/_playground/blueprint-github.json
```

Confirm the demo page loads and the plugin behavior is visible.

---

## Task 3: GitHub Actions CI

### 3a. Create `.github/workflows/lint-build.yml`

```yaml
name: Lint and Build

on:
    pull_request:
    push:
        branches:
            - main

jobs:
    lint-build:
        name: Lint and Build
        runs-on: ubuntu-latest
        steps:
            - name: Checkout
              uses: actions/checkout@v6
            - name: Set up Node
              uses: actions/setup-node@v6
              with:
                  node-version: 20
                  cache: npm
            - name: Install dependencies
              run: npm ci
            - name: Lint JavaScript
              run: npm run lint:js
            - name: Lint CSS
              run: npm run lint:css
            - name: Compile
              run: npm run compile

    php:
        name: PHP Tests
        runs-on: ubuntu-latest
        steps:
            - name: Checkout
              uses: actions/checkout@v6
            - name: Set up PHP
              uses: shivammathur/setup-php@v2
              with:
                  php-version: '8.2'
                  tools: composer
            - name: Install dependencies
              run: composer install --no-interaction --prefer-dist
            - name: PHPCodeSniffer
              run: composer phpsniff
            - name: Static Analysis
              run: composer phpstan
            - name: PHPUnit
              run: composer test
```

Adjust script names (`lint:js`, `lint:css`, `compile`) to match what exists in `package.json`. Adjust PHP version to match `composer.json` platform config.

### 3b. Fix CSS Module lint errors (if applicable)

If `src/` contains `*.module.scss` files with camelCase class names (`.imageContainer`, `.uploadButton`, etc.), add an override to `stylelint.config.js` **before** the `rules` key:

```js
overrides: [
    {
        files: [ '**/*.module.scss' ],
        rules: {
            'selector-class-pattern': null,
        },
    },
],
```

### 3c. Fix PHP lint errors (if applicable)

Common phpcs failures to check before pushing:

- **Indentation**: All PHP files must use tabs, not spaces.
- **Docblocks**: Every method needs a docblock. Every `@param` line needs the type, variable name, and description. Align the description column across all params in a block.
- **Array shapes in docblocks**: `@param array{key: type}` shapes are not parsed by `Squiz.Commenting.FunctionComment`. Use `@param array<string, mixed>` instead.

### 3d. Fix npm lock file errors (if applicable)

If CI fails with `Missing: fsevents@X.Y.Z from lock file`, regenerate the lock file locally:

```bash
rm -rf node_modules package-lock.json
npm install
```

Commit the regenerated `package-lock.json`. The issue is that `playwright` pins `fsevents` at an exact version; `npm ci` on Linux enforces the nested entry strictly.

### 3e. Commit

```
ci: add GitHub Actions lint, build, and PHP test workflow
```

---

## Task 4: Readme updates

### 4a. Ensure `assets/` contains the banner

Confirm `assets/banner-1544x500.jpg` and `assets/banner-772x250.jpg` exist and are committed. If missing, ask for them — do not generate placeholder images.

### 4b. Update `readme.md`

Structure (top to bottom):

```markdown
![banner-1544x500](assets/banner-1544x500.jpg)

# Plugin Name

![Version](https://img.shields.io/badge/version-{VERSION}-blue)
![WordPress](https://img.shields.io/badge/WordPress-{WP_VERSION}%2B-3858e9?logo=wordpress&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-{PHP_VERSION}%2B-777BB4?logo=php&logoColor=white)
![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green)
![Lint and Build](https://github.com/bob-moore/{Repo-Name}/actions/workflows/lint-build.yml/badge.svg)
[![Try it in the WordPress Playground](https://img.shields.io/badge/Try_in_Playground-v{VERSION}-blue?logo=wordpress&logoColor=%23fff&labelColor=%233858e9&color=%233858e9)](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/bob-moore/{Repo-Name}/main/_playground/blueprint-github.json)

Short description.
```

Then: What this does → Features → Requirements → Installation (plugin ZIP + Composer) → Updates → FAQ → Changelog → License.

Composer installation section must use `composer require {composer-package-name}` — not a `repositories` block.

### 4c. Update `readme.txt`

Add the banner and badges block around the WordPress plugin header. Structure:

```
![banner-1544x500](assets/banner-1544x500.jpg)

=== Plugin Name ===
Contributors: ...
... (all WP header fields) ...

![Version](...)
![WordPress](...)
![PHP](...)
![License](...)
![Lint and Build](...)
[![Try it in the WordPress Playground](...)](...)

Short description.

== Description ==
...
```

The banner goes **before** the `===` header line. The badges go **after** the closing WP header block (after the short description line, before `== Description ==`).

Read `.github/instructions/readme.txt` for the exact badge and banner format to copy.

### 4d. Commit

```
docs: add banner, badges, and playground button to readme files
```

---

## Common pitfalls

| Symptom | Cause | Fix |
|---|---|---|
| WP_Mock filter returns wrong value | `userFunction('apply_filters', ['return' => ''])` ignores falsy returns | Use `WP_Mock::onFilter('filter_name')->with($arg)->reply($value)` |
| CSS lint fails on `.imageContainer` | Module files use camelCase, WP pattern disallows it | Add `overrides` block to `stylelint.config.js` for `*.module.scss` |
| phpcs fails on method spacing | File uses spaces instead of tabs | Convert indentation to tabs |
| phpcs fails on docblock | `@param array{...}` shape syntax | Collapse to `@param array<string, mixed>` |
| `npm ci` fails with `Missing: fsevents` | Lock file has wrong nested version | Delete `node_modules` + lock file, run `npm install` |
| Playground shows `InvalidBlueprintError` | Used `#blueprint-url` hash fragment | Change to `?blueprint-url=` query parameter |
| Banner not shown on GitHub | Readme points to CDN attachment URL | Change to relative path `assets/banner-1544x500.jpg` |

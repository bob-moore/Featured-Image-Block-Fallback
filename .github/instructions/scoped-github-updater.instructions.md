---
applyTo: "{composer.json,composer.lock,composer-deps.json,composer-deps.lock,plugin.php,.gitignore}"
description: "Use when: installing bmd/github-wp-updater as a scoped dependency with wpify/scoper, wiring composer-deps.json, keeping lock files tracked, and bootstrapping the updater in plugin.php."
---

# Scoped GitHub Updater Installation Workflow

Follow this workflow whenever a task asks to install or update `bmd/github-wp-updater` as a scoped dependency.

## 1) Ensure scoper is configured

- Confirm `wpify/scoper` exists in `composer.json` under `require-dev`.
- Confirm `config.allow-plugins.wpify/scoper` is set to `true`.
- Confirm `extra.wpify-scoper` is configured with:
  - `prefix`
  - `slug`
  - `folder` set to `./vendor/scoped`
  - `composerjson` set to `composer-deps.json`
  - `composerlock` set to `composer-deps.lock`

## 2) Add updater dependency to scoped dependency file

- Add `bmd/github-wp-updater` to `composer-deps.json` `require`.
- Keep updater-related runtime packages out of the root `composer.json` `require-dev` unless explicitly needed for local tooling.
- Run composer/scoper workflow so classes are generated in `vendor/scoped`.
- Commit both `composer.lock` and `composer-deps.lock`, and make sure `.gitignore` does not exclude them.

## 3) Bootstrap updater in plugin entry file

In `plugin.php`:

- Ensure both autoloaders are loaded:
  - `vendor/autoload.php`
  - `vendor/scoped/autoload.php`
- Instantiate the updater class from the scoped namespace:
  - `Bmd\\ResponsiveGridExtension\\Bmd\\GithubWpUpdater`
- Initialize with:
  - root file path: `__FILE__`
  - config keys:
    - `github.user`
    - `github.repo`
    - `github.branch` (default `main`)
- Call `mount()` so update hooks register in WordPress.

## 4) Verify

- Check `plugin.php` syntax.
- Confirm no unresolved class references for the scoped updater namespace.
- Keep changes minimal and focused only on install/bootstrap requirements.

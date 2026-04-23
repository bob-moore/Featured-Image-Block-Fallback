# Responsive Grid Extension Copilot Baseline

This repository is a WordPress plugin codebase built with TypeScript, PHP, npm, and Composer.

## Core Rules

- Prefer minimal, targeted edits and keep public APIs stable unless a task explicitly requires API changes.
- Preserve WordPress coding conventions and existing file structure.
- When modifying plugin bootstrap logic, verify plugin loading is safe in WordPress and does not run when ABSPATH is undefined.
- For PHP changes, run at least a syntax check on changed bootstrap files.
- For JS/TS changes, favor existing build tooling and avoid introducing new bundlers.

## Dependency Strategy

- Runtime dependencies that may conflict in WordPress should be scoped using wpify/scoper.
- Keep project-local dev tooling in composer require-dev unless preparing a release package.
- Keep scoped runtime dependencies defined via composer-deps.json and built into vendor/scoped.
- Track lock files in git, including package-lock.json, composer.lock, and composer-deps.lock.

## Release Expectations

- Release artifacts must be production-ready and reproducible.
- If node_modules is missing, run `npm ci` before building release assets.
- Ensure production build assets are generated before packaging.
- Use the package zip script defined in package.json when packaging the plugin.
- Push the release commit and a tag that exactly matches the plugin version before creating the GitHub release.
- Ensure packaged zip is clean and temporary local zip files are removed after release attachment.

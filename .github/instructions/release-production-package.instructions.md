---
applyTo: "{composer.json,composer.lock,composer-deps.lock,package.json,package-lock.json,plugin.php,readme.md,readme.txt,.gitignore}"
description: "Use when: preparing a production plugin release zip, keeping lock files tracked, removing Composer dev dependencies, building assets, creating the plugin zip, tagging/pushing the release, and deleting the local zip after release upload."
---

# Production Release Packaging Workflow

Use this workflow when preparing a GitHub release artifact.

## 1) Prepare Composer for release artifact

- Remove Composer dev dependencies in the release context.
- Preferred approach: use `composer install --no-dev --optimize-autoloader` during release packaging.
- If release packaging requires a production `composer.json` snapshot, ensure `require-dev` is removed from that release snapshot before zipping.
- Do not remove required runtime dependencies.

## 2) Build production assets

- If `node_modules` is missing, run `npm ci` first.
- Run `npm run build` before creating any release zip.
- Ensure built assets in `build/` are up to date and included.

## 3) Create zip artifact

- Run the repo zip script from `package.json` (`npm run zip`) to generate the release zip.
- Attach that zip to the GitHub release.

## 4) Publish release metadata

- Commit the release changes.
- Push the release commit to GitHub.
- Create and push a git tag that exactly matches the plugin version in `plugin.php`.
- Create the GitHub release from that matching tag and attach the generated zip.

## 5) Cleanup local artifact

- Delete the generated zip file locally after successful release attachment.
- Keep repository tree clean after release tasks.

## 6) Verification checklist

- Plugin activates with production dependencies only.
- Updater bootstrap remains intact in `plugin.php`.
- Release zip contains production assets and excludes unnecessary development files.
- Lock files remain tracked in git after the release changes.

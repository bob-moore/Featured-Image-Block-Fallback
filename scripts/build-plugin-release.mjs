#!/usr/bin/env node

import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import process from 'node:process';

const rootDir = process.cwd();
const composerJson = readJson( path.join( rootDir, 'composer.json' ) );
const pluginFolder = getPluginFolderName();
const pluginNamespace = getPluginNamespace();
const releaseRoot = path.join( os.tmpdir(), `${ pluginFolder }-release` );
const stagingDir = path.join( releaseRoot, pluginFolder );
const buildPhpIni = path.join( releaseRoot, 'php.ini' );
const zipName = `${ pluginFolder }.zip`;
const zipPath = path.join( rootDir, zipName );

const distributablePaths = composerJson.extra?.[ 'plugin-release' ]?.files ?? [];
const releaseExcludePatterns = composerJson.extra?.[ 'plugin-release' ]?.exclude ?? [];
const composerCommand = getComposerCommand();
const buildPhpEnv = {
	...process.env,
	PHPRC: buildPhpIni,
	PHP_INI_SCAN_DIR: '',
};

main();

function getPluginFolderName() {
	const packageName = composerJson.name ?? path.basename( rootDir );
	const packageSlug = packageName.split( '/' ).pop() ?? packageName;

	return packageSlug.replaceAll( /[^a-z0-9._-]/gi, '-' );
}

function getPluginNamespace() {
	const namespaces = Object.keys( composerJson.autoload?.[ 'psr-4' ] ?? {} );
	const namespace = namespaces[ 0 ] ?? '';

	return namespace.replace( /\\+$/, '' );
}

function getComposerCommand() {
	const result = spawnSync( 'which', [ 'composer' ], {
		encoding: 'utf8',
		shell: false,
	} );
	const composerBin = result.stdout.trim() || 'composer';

	return {
		command: 'php',
		args: [
			'-d',
			'error_reporting=8191',
			composerBin,
		],
	};
}

function main() {
	cleanDirectory( releaseRoot );
	writeBuildPhpIni();
	fs.mkdirSync( stagingDir, { recursive: true } );

	copyDistributableFiles();
	writeReleaseComposerDepsJson();
	writeReleaseComposerJson();
	copyScoperCustomConfig();

	runWpifyScoper();
	patchPluginSourceForScopedRuntime();
	rebuildCompiledContainerCache();
	removeBuildOnlyFiles();
	createZip();

	console.log( `\nRelease zip ready: ${ zipName }` );
}

function writeBuildPhpIni() {
	fs.writeFileSync(
		buildPhpIni,
		[
			'error_reporting = E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED',
			'display_errors = stderr',
			'log_errors = On',
			'memory_limit = -1',
			'date.timezone = UTC',
			'phar.readonly = Off',
			'',
		].join( '\n' )
	);
}

function copyDistributableFiles() {
	for ( const relativePath of distributablePaths ) {
		const source = path.join( rootDir, relativePath );
		const destination = path.join( stagingDir, relativePath );

		if ( ! fs.existsSync( source ) ) {
			continue;
		}

		fs.cpSync( source, destination, {
			recursive: true,
			filter: ( currentSource ) =>
				! shouldExcludeReleasePath( currentSource ),
		} );
	}
}

function shouldExcludeReleasePath( filePath ) {
	const basename = path.basename( filePath );

	return '.DS_Store' === basename || releaseExcludePatterns.some(
		( pattern ) => matchesSimpleGlob( pattern, basename )
	);
}

function matchesSimpleGlob( pattern, value ) {
	const escaped = pattern.replace( /[.+^${}()|[\]\\]/g, '\\$&' ).replaceAll( '*', '.*' );
	const regex = new RegExp( `^${ escaped }$` );

	return regex.test( value );
}

function writeReleaseComposerDepsJson() {
	const config = { 'optimize-autoloader': true };

	if ( composerJson.config?.platform ) {
		config.platform = composerJson.config.platform;
	}

	if ( composerJson.config?.[ 'allow-plugins' ] ) {
		config[ 'allow-plugins' ] = composerJson.config[ 'allow-plugins' ];
	}

	// Merge local composer-deps.json so packages listed there are scoped into
	// the release without being public Composer dependencies.
	const localDeps = readJsonIfExists( path.join( rootDir, 'composer-deps.json' ) );

	const deps = {
		name: `${ composerJson.name }-dependencies`,
		description: `Dependencies for ${ composerJson.name }`,
		config,
		require: { ...( composerJson.require ?? {} ), ...( localDeps?.require ?? {} ) },
	};

	if ( composerJson[ 'minimum-stability' ] !== undefined ) {
		deps[ 'minimum-stability' ] = composerJson[ 'minimum-stability' ];
	}

	if ( composerJson[ 'prefer-stable' ] !== undefined ) {
		deps[ 'prefer-stable' ] = composerJson[ 'prefer-stable' ];
	}

	writeJson( path.join( stagingDir, 'composer-deps.json' ), deps );
}

function writeReleaseComposerJson() {
	const releaseComposer = structuredClone( composerJson );

	// Production deps are scoped via composer-deps.json, not needed in require.
	delete releaseComposer.require;

	// wpify/scoper drives the scoping during composer install; stripped by --no-dev after.
	const wpifyScoperVersion = composerJson[ 'require-dev' ]?.[ 'wpify/scoper' ] ?? '^3.2';
	releaseComposer[ 'require-dev' ] = { 'wpify/scoper': wpifyScoperVersion };

	delete releaseComposer[ 'autoload-dev' ];
	delete releaseComposer.scripts;

	if ( releaseComposer.extra?.[ 'wpify-scoper' ] ) {
		releaseComposer.extra[ 'wpify-scoper' ].autorun = true;
		releaseComposer.extra[ 'wpify-scoper' ].composerjson = 'composer-deps.json';
	}

	writeJson( path.join( stagingDir, 'composer.json' ), releaseComposer );
}

function copyScoperCustomConfig() {
	const src = path.join( rootDir, 'scoper.custom.php' );
	if ( fs.existsSync( src ) ) {
		fs.copyFileSync( src, path.join( stagingDir, 'scoper.custom.php' ) );
	}
}

function runWpifyScoper() {
	run( composerCommand.command, [
		...composerCommand.args,
		'install',
		`--working-dir=${ stagingDir }`,
		'--optimize-autoloader',
	], {
		label: 'composer install (with scoping)',
		env: buildPhpEnv,
	} );

	run( composerCommand.command, [
		...composerCommand.args,
		'install',
		`--working-dir=${ stagingDir }`,
		'--no-dev',
		'--optimize-autoloader',
	], {
		label: 'composer install --no-dev (strip build tools)',
		env: buildPhpEnv,
	} );
}

function patchPluginSourceForScopedRuntime() {
	patchPhpNamespaceReferences( path.join( stagingDir, 'inc' ) );
	patchPhpNamespaceReferences( path.join( stagingDir, `${ pluginFolder }.php` ) );
}

function patchPhpNamespaceReferences( directory ) {
	const prefix = composerJson.extra?.[ 'wpify-scoper' ]?.prefix ?? '';
	const namespacesToPatch = composerJson.extra?.[ 'wpify-scoper' ]?.[ 'source-namespace-patches' ] ?? [];

	const replacements = new Map(
		namespacesToPatch.map( ( ns ) => [ ns, `${ prefix }\\${ ns }` ] )
	);

	if ( 0 === replacements.size ) {
		return;
	}

	for ( const filePath of listFiles( directory ) ) {
		if ( ! filePath.endsWith( '.php' ) ) {
			continue;
		}

		let contents = fs.readFileSync( filePath, 'utf8' );

		for ( const [ search, replace ] of replacements ) {
			contents = contents.split( search ).join( replace );
		}

		fs.writeFileSync( filePath, contents );
	}
}

function rebuildCompiledContainerCache() {
	const cacheDir = path.join( stagingDir, 'cache' );
	cleanDirectory( cacheDir );

	const scopedAutoload = path.join( stagingDir, 'vendor/scoped/autoload.php' );
	const scoperAutoload = path.join( stagingDir, 'vendor/scoped/scoper-autoload.php' );
	const composerAutoload = path.join( stagingDir, 'vendor/autoload.php' );
	const pluginUrl = `https://example.invalid/wp-content/plugins/${ pluginFolder }/`;
	const mainClass = `\\${ pluginNamespace }\\Main`;

	const phpCode = `
		$root = ${ phpString( stagingDir ) };
		$plugin_url = ${ phpString( pluginUrl ) };

		require_once ${ phpString( scopedAutoload ) };
		require_once ${ phpString( scoperAutoload ) };
		require_once ${ phpString( composerAutoload ) };

		if (! defined('ABSPATH')) {
			define('ABSPATH', $root . '/');
		}

		if (function_exists('wp_get_environment_type')) {
			$environment = wp_get_environment_type();
		} else {
			function wp_get_environment_type() {
				return 'production';
			}
		}

		if (! function_exists('wp_normalize_path')) {
			function wp_normalize_path($path) {
				$path = str_replace('\\\\', '/', (string) $path);
				$path = preg_replace('|(?<=.)/+|', '/', $path);

				return $path;
			}
		}

		if (! function_exists('wp_mkdir_p')) {
			function wp_mkdir_p($target) {
				return is_dir($target) || mkdir($target, 0777, true);
			}
		}

		if (! function_exists('plugin_dir_path')) {
			function plugin_dir_path($file) {
				return trailingslashit(dirname((string) $file));
			}
		}

		if (! function_exists('plugin_dir_url')) {
			function plugin_dir_url($file) {
				global $plugin_url, $root;

				$directory = wp_normalize_path(dirname((string) $file));
				$relative = ltrim(str_replace(wp_normalize_path($root), '', $directory), '/');

				return trailingslashit($plugin_url . $relative);
			}
		}

		if (! function_exists('get_theme_file_path')) {
			function get_theme_file_path($file = '') {
				return '';
			}
		}

		if (! function_exists('get_theme_file_uri')) {
			function get_theme_file_uri($file = '') {
				return (string) $file;
			}
		}

		if (! function_exists('add_action')) {
			function add_action($hook_name, $callback, $priority = 10, $accepted_args = 1) {
				return true;
			}
		}

		if (! function_exists('add_filter')) {
			function add_filter($hook_name, $callback, $priority = 10, $accepted_args = 1) {
				return true;
			}
		}

		if (! function_exists('do_action')) {
			function do_action($hook_name, ...$args) {
				return null;
			}
		}

		if (! function_exists('apply_filters')) {
			function apply_filters($hook_name, $value, ...$args) {
				return $value;
			}
		}

		if (! function_exists('sanitize_key')) {
			function sanitize_key($key) {
				$key = strtolower((string) $key);
				return preg_replace('/[^a-z0-9_\\-]/', '', $key);
			}
		}

		if (! function_exists('esc_url_raw')) {
			function esc_url_raw($url) {
				return (string) $url;
			}
		}

		if (! function_exists('trailingslashit')) {
			function trailingslashit($value) {
				return rtrim((string) $value, "/\\\\") . '/';
			}
		}

		if (! function_exists('untrailingslashit')) {
			function untrailingslashit($value) {
				return rtrim((string) $value, "/\\\\");
			}
		}

		$main_class = ${ phpString( mainClass ) };
		$main = new $main_class([
			'cache' => true,
			'path'  => $root,
			'url'   => $plugin_url,
		]);

		$initializer = new \\ReflectionMethod($main_class, 'initContainer');
		$initializer->setAccessible(true);
		$initializer->invoke($main);
	`;

	run( 'php', [ '-r', phpCode ], {
		label: 'rebuild compiled container cache',
		env: buildPhpEnv,
	} );
}

function removeBuildOnlyFiles() {
	const composerJsonPath = path.join( stagingDir, 'composer.json' );
	const releaseComposerJson = readJson( composerJsonPath );
	delete releaseComposerJson[ 'require-dev' ];
	if ( releaseComposerJson.extra?.[ 'wpify-scoper' ] ) {
		delete releaseComposerJson.extra[ 'wpify-scoper' ].composerjson;
	}
	writeJson( composerJsonPath, releaseComposerJson );

	for ( const relativePath of [ 'composer-deps.json', 'composer-deps.lock', 'composer.json', 'composer.lock', 'scoper.custom.php' ] ) {
		fs.rmSync( path.join( stagingDir, relativePath ), { force: true } );
	}
}

function createZip() {
	fs.rmSync( zipPath, { force: true } );

	run( 'zip', [ '-r', zipPath, pluginFolder ], {
		cwd: releaseRoot,
		label: 'create plugin zip',
	} );
}

function cleanDirectory( directory ) {
	fs.rmSync( directory, {
		force: true,
		recursive: true,
	} );
	fs.mkdirSync( directory, { recursive: true } );
}

function readJson( filePath ) {
	return JSON.parse( fs.readFileSync( filePath, 'utf8' ) );
}

function readJsonIfExists( filePath ) {
	return fs.existsSync( filePath ) ? readJson( filePath ) : null;
}

function writeJson( filePath, value ) {
	fs.writeFileSync( filePath, `${ JSON.stringify( value, null, '\t' ) }\n` );
}

function phpString( value ) {
	return `'${ String( value ).replaceAll( '\\', '\\\\' ).replaceAll( '\'', '\\\'' ) }'`;
}

function run( command, args, { label, cwd, env } = {} ) {
	console.log( `\n> ${ label }` );

	const result = spawnSync( command, args, {
		cwd,
		env,
		shell: false,
		stdio: 'inherit',
	} );

	if ( 0 !== result.status ) {
		throw new Error( `${ label } failed.` );
	}
}

function listFiles( directory ) {
	if ( ! fs.existsSync( directory ) ) {
		return [];
	}

	if ( fs.statSync( directory ).isFile() ) {
		return [ directory ];
	}

	return fs.readdirSync( directory, { withFileTypes: true } ).flatMap(
		( entry ) => {
			const entryPath = path.join( directory, entry.name );

			return entry.isDirectory() ? listFiles( entryPath ) : [ entryPath ];
		}
	);
}

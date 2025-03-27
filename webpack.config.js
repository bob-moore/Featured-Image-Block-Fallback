/**
 * Wordpress dependencies
 */
const { getAsBooleanFromENV } = require( '@wordpress/scripts/utils' );
/**
 * External dependencies
 */
const path = require( 'path' );
const RemoveEmptyScriptsPlugin = require( 'webpack-remove-empty-scripts' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const glob = require( 'glob' );
/**
 * Check if the --experimental-modules flag is set.
 */
const hasExperimentalModulesFlag = getAsBooleanFromENV(
	'WP_EXPERIMENTAL_MODULES'
);
/**
 * Get default script config from @wordpress/scripts
 * based on the --experimental-modules flag.
 */
const scriptConfig = hasExperimentalModulesFlag
	? require( '@wordpress/scripts/config/webpack.config' )[ 0 ]
	: require( '@wordpress/scripts/config/webpack.config' );
/**
 * Filter plugins from the default config
 */
const plugins = scriptConfig.plugins.filter( ( item ) => {
	return ! [ 'MiniCssExtractPlugin' ].includes( item.constructor.name );
} );
/**
 * Webpack configuration
 */
module.exports = {
	...scriptConfig,
	entry: path.resolve( __dirname, 'src', 'index.ts' ),
	output: {
		path: path.resolve( __dirname, 'build' ),
		filename: '[name].js',
		clean: true,
	},
	resolve: {
		alias: {
			'@images': path.resolve( __dirname, 'assets/images' ),
		},
	},
	plugins: [
		...plugins,
		new RemoveEmptyScriptsPlugin(),
		new MiniCssExtractPlugin( { filename: '[name].css' } ),
	],
};

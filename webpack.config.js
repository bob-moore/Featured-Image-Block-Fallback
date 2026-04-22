/**
 * Wordpress dependencies
 */
const { getAsBooleanFromENV } = require( '@wordpress/scripts/utils' );
/**
 * External dependencies
 */
const path = require( 'path' );
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
const defaultConfigs = hasExperimentalModulesFlag
	? require( '@wordpress/scripts/config/webpack.config' )
	: [ require( '@wordpress/scripts/config/webpack.config' ) ];
const [ scriptConfig ] = defaultConfigs;

/**
 * Webpack configuration
 */
const assetConfig = {
	...scriptConfig,
	entry: {
		index: path.resolve( __dirname, 'src', 'index.ts' ),
	},
};

module.exports = () => {
	return [ ...defaultConfigs, assetConfig ];
};

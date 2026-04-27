module.exports = {
	extends: '@wordpress/stylelint-config/scss',
	overrides: [
		{
			files: [ '**/*.module.scss' ],
			rules: {
				'selector-class-pattern': null,
			},
		},
	],
	rules: {
		'declaration-no-important': null,
		'no-empty-source': null,
		'no-descending-specificity': null,
		'scss/at-rule-no-unknown': null,
		'no-duplicate-selectors': null,
		'selector-pseudo-class-no-unknown': [
			true,
			{
				ignorePseudoClasses: [ 'global' ],
			},
		],
		'selector-class-pattern': [
			'^[a-z]([-]?[a-z0-9]+)*(__[a-z0-9]([-]?[a-z0-9]+)*)?(--[a-z0-9]([-]?[a-z0-9]+)*)?$',
			{
				/** This option will resolve nested selectors with & interpolation. - https://stylelint.io/user-guide/rules/selector-class-pattern/#resolvenestedselectors-true--false-default-false */
				resolveNestedSelectors: true,
				/**
				 * Custom message
				 * @param {string} selectorValue
				 */
				message: function expected( selectorValue ) {
					return `Expected class selector "${ selectorValue }" to use all lowercase, and separate words with hyphens and/or BEM CSS pattern : https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/`;
				},
			},
		],
	},
};

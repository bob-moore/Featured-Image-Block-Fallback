module.exports = {
	extends: '@wordpress/stylelint-config/scss',
	rules: {
		indentation: 4,
		'number-leading-zero': null,
		'declaration-no-important': true,
		'no-empty-source': null,
		'no-descending-specificity': null,
		'selector-pseudo-class-no-unknown': [
			true,
			{
				ignorePseudoClasses: [ 'global' ],
			},
		],
	},
};

/**
 * This is the new data shape
 * @param settings
 * @param name
 */
export const Attributes = ( settings, name: string ) => {
	if ( 'core/post-featured-image' === name ) {
		settings = {
			...settings,
			attributes: {
				...settings.attributes,
				featuredImageFallback: {
					type: 'object',
					default: {
						url: '',
						id: '',
					},
				},
				useFirstImageFromPost: {
					type: 'boolean',
					default: false,
				},
			},
		};
	}
	return settings;
};

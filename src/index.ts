/**
 * Wordpress dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { Edit } from './Edit';
import { Attributes } from './Attributes';

addFilter( 'editor.BlockEdit', 'bmd/with-fallback-image-edit', Edit );
addFilter(
	'blocks.registerBlockType',
	'bmd/with-fallback-image-attributes',
	Attributes
);

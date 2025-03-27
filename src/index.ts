/**
 * Wordpress dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { Edit } from './Edit.tsx';

addFilter('editor.BlockEdit', 'marked-effect/with-fallback-image-edit', Edit );

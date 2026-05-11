/**
 * External dependencies
 */
import { FC } from 'react';
/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { Spinner } from '@wordpress/components';
/**
 * Internal Dependencies
 */
import { BlockAttributes, BlockEditProps } from './types';
import style from './edit.module.scss';

const FIRST_IMAGE_REGEX =
	/<!--\s+wp:(?:core\/)?image\s+(?<attrs>{(?:(?:[^}]+|}+(?=})|(?!}\s+\/?-->).)*)?}\s+)?-->/;

/**
 * Extract the first core/image attachment ID from a post's serialized block
 * content.
 *
 * This intentionally mirrors the logic in WordPress' core
 * PostFeaturedImageEdit component. Core uses this path when the block's
 * "Use first image from post" option is enabled and the post does not have a
 * real featured image assigned. We need the same answer here so the fallback
 * preview does not replace the core first-image preview.
 *
 * The regex only looks for the opening image block comment and then parses its
 * JSON attributes. If the image block is malformed, has no attributes, or does
 * not store an attachment ID, the function returns undefined and lets the
 * normal fallback logic continue.
 *
 * @param postContent Serialized post block content.
 *
 * @return The first image block attachment ID, or undefined when none is found.
 */
const getFirstImageId = ( postContent = '' ) => {
	const imageOpener = FIRST_IMAGE_REGEX.exec( String( postContent ) );

	if ( ! imageOpener?.groups?.attrs ) {
		return;
	}

	try {
		return JSON.parse( imageOpener.groups.attrs )?.id;
	} catch {}
};

/**
 * Convert the block's loose string scale attribute into a valid CSS object-fit
 * value.
 *
 * WordPress block attributes arrive as plain strings, but React's style types
 * only allow known object-fit values. This guard keeps TypeScript honest and
 * avoids passing unexpected values through to the preview image. Returning
 * undefined means "do not set object-fit" and allows the browser/core styles to
 * handle the image naturally.
 *
 * @param scale The post-featured-image block's scale attribute.
 *
 * @return A valid CSS object-fit value, or undefined.
 */
const getObjectFit = ( scale?: string ) => {
	if (
		'contain' === scale ||
		'cover' === scale ||
		'fill' === scale ||
		'none' === scale ||
		'scale-down' === scale
	) {
		return scale;
	}

	return undefined;
};

type FallbackPreviewProps = BlockEditProps< BlockAttributes > & {
	BlockEdit: FC< BlockEditProps< {} > >;
};

/**
 * Render an editor-only fallback preview for the core Post Featured Image
 * block.
 *
 * The server-side fallback works on the frontend, but the block editor's React
 * component decides what to display before PHP render filters are useful. Core's
 * editor component calculates a local `featuredImage` value from only two
 * sources:
 *
 * 1. the post entity's real `featured_media` value;
 * 2. the first image in post content, when `useFirstImageFromPost` is enabled.
 *
 * Our custom `featuredImageFallback` attribute is invisible to that core
 * calculation, so core shows a placeholder in the editor even though the
 * frontend can render a fallback. This component fills only that missing
 * editor case. If core has a real featured image or a first-image match, this
 * component immediately delegates back to core's original BlockEdit component.
 *
 * The component is intentionally a presentation shim, not a data mutation. It
 * does not write to `featured_media`, does not alter post content, and does not
 * change how the frontend renders. It only swaps the editor preview when the
 * fallback attribute is the only available image source.
 *
 * @param root0
 * @param root0.BlockEdit
 * @return The core BlockEdit output or a fallback-image figure preview.
 */
export const FallbackPreview = ( {
	BlockEdit,
	...props
}: FallbackPreviewProps ) => {
	const { attributes, context } = props;
	const {
		aspectRatio,
		featuredImageFallback,
		height,
		isLink,
		linkTarget,
		scale,
		sizeSlug,
		useFirstImageFromPost,
		width,
	} = attributes;
	const {
		postId,
		postType: postTypeSlug,
	}: { postId?: number; postType?: string } = context;
	const fallbackId = Number( featuredImageFallback?.id || 0 );

	const { postContent, postPermalink, storedFeaturedImage } = useSelect(
		( select ) => {
			if ( ! postId || ! postTypeSlug ) {
				return {
					postContent: '',
					postPermalink: undefined,
					storedFeaturedImage: 0,
				};
			}

			const { getEditedEntityRecord } = select( coreStore );
			const post = getEditedEntityRecord(
				'postType',
				postTypeSlug,
				postId
			);

			return {
				postContent: post?.content || '',
				postPermalink: post?.link,
				storedFeaturedImage: post?.featured_media || 0,
			};
		},
		[ postTypeSlug, postId ]
	);
	const firstImageId = useFirstImageFromPost
		? getFirstImageId( postContent )
		: undefined;
	const previewImageId = storedFeaturedImage || firstImageId || fallbackId;

	const { media } = useSelect(
		( select ) => {
			const { getEntityRecord } = select( coreStore );

			return {
				media:
					previewImageId &&
					getEntityRecord( 'postType', 'attachment', previewImageId, {
						context: 'view',
					} ),
			};
		},
		[ previewImageId ]
	);

	const blockProps = useBlockProps( {
		style: { width, height, aspectRatio },
		className: style.fallbackPreview,
	} );

	if ( storedFeaturedImage || firstImageId || ! fallbackId ) {
		return <BlockEdit { ...props } />;
	}

	const mediaUrl =
		media?.media_details?.sizes?.[ sizeSlug || 'full' ]?.source_url ||
		media?.source_url ||
		featuredImageFallback?.url;
	const image = mediaUrl ? (
		<img
			src={ mediaUrl }
			alt="Featured fallback"
			style={ {
				height: aspectRatio ? '100%' : height,
				width: aspectRatio ? '100%' : undefined,
				objectFit:
					height || aspectRatio ? getObjectFit( scale ) : undefined,
			} }
		/>
	) : (
		<Spinner />
	);

	return (
		<figure { ...blockProps }>
			{ isLink ? (
				<a href={ postPermalink } target={ linkTarget }>
					{ image }
				</a>
			) : (
				image
			) }
		</figure>
	);
};

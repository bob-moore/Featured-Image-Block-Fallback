/**
 * External dependencies
 */
import { FC } from 'react';
/**
 * WordPress dependencies
 */
import {
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
} from '@wordpress/block-editor';
import { createHigherOrderComponent } from '@wordpress/compose';
import { PanelBody, Button, ToggleControl } from '@wordpress/components';
/**
 * Internal Dependencies
 */
import { BlockEditProps, BlockAttributes, Image } from './types';
import style from './edit.module.scss';

const panelState = {
	current: false,
	toggle: () => {
		panelState.current = ! panelState.current;
	},
};

/**
 * Add fallback panel
 */
export const Edit = createHigherOrderComponent<
	FC< BlockEditProps< {} > >,
	FC< BlockEditProps< BlockAttributes > >
>( ( BlockEdit ) => {
	return ( props: BlockEditProps< BlockAttributes > ) => {
		if (
			'core/post-featured-image' !== props.name ||
			'object' !== typeof props.attributes.featuredImageFallback
		) {
			return <BlockEdit { ...props } />;
		}

		const {
			attributes: { featuredImageFallback, useFirstImageFromPost },
			setAttributes,
		} = props;

		const handlePanelToggle = () => {
			panelState.toggle();
		};

		const handleChange = ( media: Image ) => {
			const value = media
				? {
						id: media.id || '',
						url: media.url || '',
				  }
				: { id: '', url: '' };
			setAttributes( { featuredImageFallback: value } );
		};

		// useEffect(() => {
		//     setAttributes( {useFirstImageFromPost: true});
		// }, [setAttributes]);

		const handleRemove = () => {
			setAttributes( {
				featuredImageFallback: {
					id: '',
					url: '',
				},
			} );
		};

		const handleToggleFirstImage = ( value: boolean ) => {
			setAttributes( { useFirstImageFromPost: value } );
		};

		return (
			<>
				<BlockEdit { ...props } />
				<InspectorControls>
					<PanelBody
						title="Fallback Image"
						initialOpen={ panelState.current }
						onToggle={ handlePanelToggle }
					>
						<ToggleControl
							label="Use First Image from Post"
							checked={ useFirstImageFromPost }
							onChange={ handleToggleFirstImage }
						/>
						<MediaUploadCheck>
							<MediaUpload
								onSelect={ handleChange }
								allowedTypes={ [ 'image' ] }
								value={ featuredImageFallback?.id || '' }
								render={ ( { open } ) => (
									<div>
										{ featuredImageFallback?.id ? (
											<div
												className={
													style.imageContainer
												}
											>
												<img
													src={
														featuredImageFallback.url
													}
													alt="Fallback"
												/>
												<div
													className={
														style.hasImageContainer
													}
												>
													<Button
														onClick={ open }
														className={
															style.hasImageButton
														}
													>
														Change
													</Button>
													<Button
														onClick={ handleRemove }
														className={
															style.hasImageButton
														}
														isDestructive
													>
														Remove
													</Button>
												</div>
											</div>
										) : (
											<Button
												onClick={ open }
												className={ style.uploadButton }
											>
												Select Image
											</Button>
										) }
									</div>
								) }
							/>
						</MediaUploadCheck>
					</PanelBody>
				</InspectorControls>
			</>
		);
	};
}, 'Edit' );

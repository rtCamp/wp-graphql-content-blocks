<?php
/**
 * The BlockWithAnchorSupportAttributes Interface Type.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/#anchor
 *
 * @package WPGraphQL\ContentBlocks\BlockSupports
 */

namespace WPGraphQL\ContentBlocks\BlockSupports;

use WPGraphQL\ContentBlocks\Utilities\DOMHelpers;

/**
 * Class Anchor
 */
final class Anchor extends AbstractBlockSupport {
	/**
	 * {@inheritDoc}
	 */
	public static function register(): void {
		register_graphql_interface_type(
			'BlockWithAnchorSupportAttributes',
			[
				'description'     => __( 'Attributes for a Block with Anchor support.', 'wp-graphql-content-blocks' ),
				'eagerlyLoadType' => true,
				'interfaces'      => [ 'EditorBlock' ],
				'fields'          => [
					'anchor' => [
						'type'        => 'String',
						'description' => __( 'The anchor attribute for the block.', 'wp-graphql-content-blocks' ),
						'resolve'     => static function ( $block ) {
							$rendered_block = wp_unslash( $block->renderedHtml );

							if ( empty( $rendered_block ) ) {
								return null;
							}

							return DOMHelpers::parse_first_node_attribute( $rendered_block, 'id' );
						},
					],
				],
			]
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public static function has_block_support( \WP_Block_Type $block_type ): bool {
		return block_has_support( $block_type, [ 'anchor' ], false );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_attributes_interfaces( \WP_Block_Type $block_type ): array {
		if ( ! self::has_block_support( $block_type ) ) {
			return [];
		}

		return [ 'BlockWithAnchorSupportAttributes' ];
	}
}

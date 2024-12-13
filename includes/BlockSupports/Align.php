<?php
/**
 * The BlockWithAlignSupportAttributes Interface Type.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/#align
 *
 * @package WPGraphQL\ContentBlocks\BlockSupports
 */

namespace WPGraphQL\ContentBlocks\BlockSupports;

/**
 * Class Align
 */
final class Align extends AbstractBlockSupport {
	/**
	 * Registers the types to WPGraphQL.
	 */
	public static function register(): void {
		register_graphql_interface_type(
			'BlockWithAlignSupportAttributes',
			[
				'description'     => __( 'Attributes for a block with Align support', 'wp-graphql-content-blocks' ),
				'eagerlyLoadType' => true,
				'interfaces'      => [ 'EditorBlock' ],
				'fields'          => [
					'align' => [
						'type'        => 'String',
						'description' => __( 'The align attribute for the block.', 'wp-graphql-content-blocks' ),
					],
				],
			]
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public static function has_block_support( \WP_Block_Type $block_type ): bool {
		return block_has_support( $block_type, [ 'align' ], false );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_attributes_interfaces( \WP_Block_Type $block_type ): array {
		if ( ! self::has_block_support( $block_type ) ) {
			return [];
		}

		return [ 'BlockWithAlignSupportAttributes' ];
	}
}

<?php
/**
 * The BlockWithShadowSupportAttributes Interface Type.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/#shadow
 *
 * @package WPGraphQL\ContentBlocks\BlockSupports
 */

namespace WPGraphQL\ContentBlocks\BlockSupports;

/**
 * Class Shadow
 */
final class Shadow extends AbstractBlockSupport {
	/**
	 * {@inheritDoc}
	 */
	public static function register(): void {
		register_graphql_interface_type(
			'BlockWithShadowSupportAttributes',
			[
				'description'     => __( 'Attributes for a Block with Shadow support.', 'wp-graphql-content-blocks' ),
				'eagerlyLoadType' => true,
				'interfaces'      => [ 'EditorBlock' ],
				'fields'          => [
					'shadow' => [
						'type'        => 'String',
						'description' => __( 'The shadow attribute for the block.', 'wp-graphql-content-blocks' ),
					],
				],
			]
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public static function has_block_support( \WP_Block_Type $block_type ): bool {
		return block_has_support( $block_type, [ 'shadow' ], false );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_attributes_interfaces( \WP_Block_Type $block_type ): array {
		if ( ! self::has_block_support( $block_type ) ) {
			return [];
		}

		return [ 'BlockWithShadowSupportAttributes' ];
	}
}

<?php
/**
 * The BlockWithColorSupportAttributes Interface Type.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/#color
 *
 * @package WPGraphQL\ContentBlocks\BlockSupports
 */

namespace WPGraphQL\ContentBlocks\BlockSupports;

/**
 * Class Color
 */
final class Color extends AbstractBlockSupport {
	/**
	 * {@inheritDoc}
	 */
	public static function register(): void {
		register_graphql_interface_type(
			'BlockWithColorSupportAttributes',
			[
				'description'     => __( 'Attributes for a Block with Color support.', 'wp-graphql-content-blocks' ),
				'eagerlyLoadType' => true,
				'interfaces'      => [ 'EditorBlock' ],
				'fields'          => [
					'backgroundColor' => [
						'type'        => 'String',
						'description' => __( 'The backgroundColor attribute for the block.', 'wp-graphql-content-blocks' ),
					],
					'textColor'       => [
						'type'        => 'String',
						'description' => __( 'The textColor attribute for the block.', 'wp-graphql-content-blocks' ),
					],
					'gradient'        => [
						'type'        => 'String',
						'description' => __( 'The gradientColor attribute for the block.', 'wp-graphql-content-blocks' ),
					],
				],
			]
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public static function has_block_support( \WP_Block_Type $block_type ): bool {
		return block_has_support( $block_type, [ 'color' ], false );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_attributes_interfaces( \WP_Block_Type $block_type ): array {
		if ( ! self::has_block_support( $block_type ) ) {
			return [];
		}

		return [ 'BlockWithColorSupportAttributes' ];
	}
}

<?php
/**
 * The BlockWithTypographySupportAttributes Interface Type.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/#typography
 *
 * @package WPGraphQL\ContentBlocks\BlockSupports
 */

namespace WPGraphQL\ContentBlocks\BlockSupports;

/**
 * Class Typography
 */
final class Typography extends AbstractBlockSupport {
	/**
	 * Registers the types to WPGraphQL.
	 */
	public static function register(): void {
		register_graphql_interface_type(
			'BlockWithTypographySupportAttributes',
			[
				'description'     => __( 'Attributes for a Block with Typography support.', 'wp-graphql-content-blocks' ),
				'eagerlyLoadType' => true,
				'interfaces'      => [ 'EditorBlock' ],
				'fields'          => [
					'fontSize'   => [
						'type'        => 'String',
						'description' => __( 'The fontSize attribute for the block.', 'wp-graphql-content-blocks' ),
					],
					'fontFamily' => [
						'type'        => 'String',
						'description' => __( 'The fontFamily attribute for the block.', 'wp-graphql-content-blocks' ),
					],
				],
			]
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public static function has_block_support( \WP_Block_Type $block_type ): bool {
		return block_has_support( $block_type, [ 'typography' ], false );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_attributes_interfaces( \WP_Block_Type $block_type ): array {
		if ( ! self::has_block_support( $block_type ) ) {
			return [];
		}

		return [ 'BlockWithTypographySupportAttributes' ];
	}
}

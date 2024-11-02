<?php
/**
 * The BlockWithCustomClassNameSupportAttributes Interface Type.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/#classname
 *
 * @package WPGraphQL\ContentBlocks\BlockSupports
 */

namespace WPGraphQL\ContentBlocks\BlockSupports;

/**
 * Class CustomClassName
 */
final class CustomClassName extends AbstractBlockSupport {
	/**
	 * {@inheritDoc}
	 */
	public static function register(): void {
		register_graphql_interface_type(
			'BlockWithCustomClassNameSupportAttributes',
			[
				'description'     => __( 'Attributes for a block with customClassName support', 'wp-graphql-content-blocks' ),
				'eagerlyLoadType' => true,
				'interfaces'      => [ 'EditorBlock' ],
				'fields'          => [
					'className' => [
						'type'        => 'String',
						'description' => __( 'The custom CSS class name attribute for the block.', 'wp-graphql-content-blocks' ),
					],
				],
			]
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public static function has_block_support( \WP_Block_Type $block_type ): bool {
		return block_has_support( $block_type, [ 'customClassName' ], false );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_attributes_interfaces( \WP_Block_Type $block_type ): array {
		if ( ! self::has_block_support( $block_type ) ) {
			return [];
		}

		return [ 'BlockWithCustomClassNameSupportAttributes' ];
	}
}

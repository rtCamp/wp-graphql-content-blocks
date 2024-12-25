<?php
/**
 * Handles mapping Block Supports to the GraphQL Schema.
 *
 * @package WPGraphQL\ContentBlocks\BlockSupports
 *
 * @since @next-version
 */

namespace WPGraphQL\ContentBlocks\BlockSupports;

/**
 * Class AbstractBlockSupport
 */
abstract class AbstractBlockSupport {
	/**
	 * Registers the types to WPGraphQL.
	 */
	abstract public static function register(): void;

	/**
	 * Checks whether the Block Supports is enabled for the block type.
	 *
	 * @param \WP_Block_Type $block_type The block type.
	 */
	abstract public static function has_block_support( \WP_Block_Type $block_type ): bool;

	/**
	 * Get the attribute GraphQL interfaces for the block type.
	 *
	 * @param \WP_Block_Type $block_type The block type.
	 *
	 * @return string[]
	 */
	abstract public static function get_attributes_interfaces( \WP_Block_Type $block_type ): array;
}

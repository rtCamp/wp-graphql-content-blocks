<?php
/**
 *  Handles mapping a WP_Block_Type to the WPGraphQL Schema
 *
 * @package WPGraphQL\ContentBlocks\Blocks
 */

namespace WPGraphQL\ContentBlocks\Blocks;

use WPGraphQL\ContentBlocks\Registry\Registry;
use WPGraphQL\ContentBlocks\Type\Scalar\Scalar;
use WPGraphQL\ContentBlocks\Utilities\DOMHelpers;
use WPGraphQL\ContentBlocks\Utilities\WPGraphQLHelpers;
use WPGraphQL\Utils\Utils;
use WP_Block_Type;

/**
 * Class Block
 */
class Block {
	/**
	 * The Block Type
	 *
	 * @var \WP_Block_Type
	 */
	protected WP_Block_Type $block;

	/**
	 * The GraphQL type name of the block.
	 *
	 * @var string
	 */
	protected string $type_name;

	/**
	 * The instance of the WPGraphQL block registry.
	 *
	 * @var \WPGraphQL\ContentBlocks\Registry\Registry
	 */
	protected Registry $block_registry;

	/**
	 * The attributes of the block
	 *
	 * @var array|null
	 */
	protected ?array $block_attributes;

	/**
	 * Any Additional attributes of the block not defined in block.json
	 *
	 * @var array|null
	 */
	protected ?array $additional_block_attributes;

	/**
	 * Block constructor.
	 *
	 * @param \WP_Block_Type                             $block The Block Type.
	 * @param \WPGraphQL\ContentBlocks\Registry\Registry $block_registry The instance of the WPGraphQL block registry.
	 */
	public function __construct( WP_Block_Type $block, Registry $block_registry ) {
		$this->block            = $block;
		$this->block_registry   = $block_registry;
		$this->block_attributes = $this->block->attributes;
		$this->type_name        = WPGraphQLHelpers::format_type_name( $block->name );
		$this->register_block_type();
	}

	/**
	 * Registers the Block Type to WPGraphQL.
	 *
	 * @return void
	 */
	private function register_block_type() {
		$this->register_block_attributes_as_fields();
		$this->register_type();
	}

	/**
	 * Registers the block attributes GraphQL type and adds it as a field on the Block.
	 */
	private function register_block_attributes_as_fields(): void {
		// Grab any additional block attributes attached into the class itself
		if ( isset( $this->additional_block_attributes ) ) {
			$block_attributes = ! empty( $this->block_attributes ) ? array_merge( $this->block_attributes, $this->additional_block_attributes ) : $this->additional_block_attributes;
		} else {
			$block_attributes = $this->block_attributes;
		}
		$block_attribute_fields = $this->get_block_attribute_fields( $block_attributes, $this->type_name . 'Attributes' );
		// For each attribute, register a new object type and attach it to the block type as a field
		if ( ! empty( $block_attribute_fields ) ) {
			$block_attribute_type_name = $this->type_name . 'Attributes';
			register_graphql_object_type(
				$block_attribute_type_name,
				[
					'description' => sprintf(
						// translators: %s is the block type name.
						__( 'Attributes of the %s Block Type', 'wp-graphql-content-blocks' ),
						$this->type_name
					),
					'interfaces'  => $this->get_block_attributes_interfaces(),
					'fields'      => $block_attribute_fields,
				]
			);

			register_graphql_field(
				$this->type_name,
				'attributes',
				[
					'type'        => $block_attribute_type_name,
					'description' => sprintf(
						// translators: %s is the block type name.
						__( 'Attributes of the %s Block Type', 'wp-graphql-content-blocks' ),
						$this->type_name
					),
					'resolve'     => static function ( $block ) {
						return $block;
					},
				]
			);
		}//end if
	}

	/**
	 * Returns the type of the block attribute
	 *
	 * @param string              $name The block name
	 * @param array<string,mixed> $attribute The block attribute config
	 * @param string              $prefix Current prefix string to use for the get_query_type
	 *
	 * @return mixed
	 */
	private function get_attribute_type( $name, $attribute, $prefix ) {
		$type = null;

		if ( isset( $attribute['type'] ) ) {
			switch ( $attribute['type'] ) {
				case 'rich-text':
				case 'string':
					$type = 'String';
					break;
				case 'boolean':
					$type = 'Boolean';
					break;
				case 'number':
					$type = 'Float';
					break;
				case 'integer':
					$type = 'Int';
					break;
				case 'array':
					if ( isset( $attribute['query'] ) ) {
						$type = [ 'list_of' => $this->get_query_type( $name, $attribute['query'], $prefix ) ];
					} elseif ( isset( $attribute['items'] ) ) {
						$of_type = $this->get_attribute_type( $name, $attribute['items'], $prefix );

						if ( null !== $of_type ) {
							$type = [ 'list_of' => $of_type ];
						} else {
							$type = Scalar::get_block_attributes_array_type_name();
						}
					} else {
						$type = Scalar::get_block_attributes_array_type_name();
					}
					break;
				case 'object':
					$type = Scalar::get_block_attributes_object_type_name();
					break;
			}
		} elseif ( isset( $attribute['source'] ) ) {
			$type = 'String';
		}

		if ( null !== $type ) {
			$default_value = $attribute['default'] ?? null;

			if ( isset( $default_value ) ) {
				$type = [ 'non_null' => $type ];
			}
		}

		return $type;
	}

	/**
	 * Gets the WPGraphQL field registration config for the block attributes.
	 *
	 * @param ?array $block_attributes The block attributes.
	 * @param string $prefix The current prefix string to use for the get_query_type
	 */
	private function get_block_attribute_fields( ?array $block_attributes, string $prefix = '' ): array {
		$fields = [];

		// Bail early if no attributes are defined.
		if ( null === $block_attributes ) {
			return $fields;
		}

		foreach ( $block_attributes as $attribute_name => $attribute_config ) {
			$graphql_type = $this->get_attribute_type( $attribute_name, $attribute_config, $prefix );

			if ( empty( $graphql_type ) ) {
				continue;
			}
			// Create the field config.
			$fields[ Utils::format_field_name( $attribute_name ) ] = [
				'type'        => $graphql_type,
				'description' => sprintf(
					// translators: %1$s is the attribute name, %2$s is the block name.
					__( 'The "%1$s" field on the "%2$s" block or block attributes', 'wp-graphql-content-blocks' ),
					$attribute_name,
					$prefix
				),
				'resolve'     => function ( $block ) use ( $attribute_name, $attribute_config ) {
					$config = [
						$attribute_name => $attribute_config,
					];
					$result = $this->resolve_block_attributes_recursive( $block['attrs'], wp_unslash( render_block( $block ) ), $config );

					return $result[ $attribute_name ];
				},
			];
		}//end foreach

		return $fields;
	}

	/**
	 * Returns the type of the block query attribute
	 *
	 * @param string $name The block name
	 * @param array  $query The block query config
	 * @param string $prefix The current prefix string to use for registering the new query attribute type
	 */
	private function get_query_type( string $name, array $query, string $prefix ): string {
		$type = $prefix . ucfirst( $name );

		$fields = $this->create_attributes_fields( $query, $type );

		register_graphql_object_type(
			$type,
			[
				'fields'      => $fields,
				'description' => sprintf(
					// translators: %1$s is the attribute name, %2$s is the block attributes field.
					__( 'The "%1$s" field on the "%2$s" block attribute field', 'wp-graphql-content-blocks' ),
					$type,
					$prefix
				),
			]
		);

		return $type;
	}

	/**
	 * Creates the new attribute fields for query types
	 *
	 * @param array  $attributes The query attributes config
	 * @param string $prefix The current prefix string to use for registering the new query attribute type
	 */
	private function create_attributes_fields( $attributes, $prefix ): array {
		$fields = [];
		foreach ( $attributes as $name => $attribute ) {
			$type = $this->get_attribute_type( $name, $attribute, $prefix );

			if ( isset( $type ) ) {
				$default_value = $attribute['default'] ?? null;

				$fields[ Utils::format_field_name( $name ) ] = [
					'type'        => $type,
					'description' => sprintf(
						// translators: %1$s is the attribute name, %2$s is the block attributes field.
						__( 'The "%1$s" field on the "%2$s" block attribute field', 'wp-graphql-content-blocks' ),
						$name,
						$prefix
					),
					'resolve'     => function ( $attributes ) use ( $name, $default_value ) {
						$value = $attributes[ $name ] ?? $default_value;
						return $this->normalize_attribute_value( $value, $attributes['__type'][ $name ]['type'] );
					},
				];
			}
		}

		return $fields;
	}

	/**
	 * Normalizes the value of the attribute
	 *
	 * @param array|string $value The value
	 * @param string       $type The type of the value
	 *
	 * @return array|string|int|float|bool
	 */
	private function normalize_attribute_value( $value, $type ) {
		// @todo use the `source` to normalize array/object values.
		if ( is_array( $value ) ) {
			return $value;
		}

		switch ( $type ) {
			case 'rich-text':
			case 'array':
				// If we're here, we want an array type, even though the value is not an array.
				return isset( $value ) ? [ $value ] : [];
			case 'string':
				return (string) $value;
			case 'number':
				return (float) $value;
			case 'boolean':
				return (bool) $value;
			case 'integer':
				return (int) $value;
			default:
				return $value;
		}
	}

	/**
	 * Gets the GraphQL interfaces that should be implemented by the block.
	 *
	 * @return string[]
	 */
	private function get_block_interfaces(): array {
		return $this->block_registry->get_block_interfaces( $this->block->name );
	}

	/**
	 * Gets the GraphQL interfaces that should be implemented by the block attributes object.
	 *
	 * @return string[]
	 */
	private function get_block_attributes_interfaces(): array {
		return $this->block_registry->get_block_attributes_interfaces( $this->block->name );
	}

	/**
	 * Register the Type for the block. This happens after all other object types are already registered.
	 */
	private function register_type(): void {
		/**
		 * Register the Block Object Type to the Schema
		 */
		register_graphql_object_type(
			$this->type_name,
			[
				'description'     => __( 'A block used for editing the site', 'wp-graphql-content-blocks' ),
				'interfaces'      => $this->get_block_interfaces(),
				'eagerlyLoadType' => true,
				'fields'          => [
					'name' => [
						'type'        => 'String',
						'description' => __( 'The name of the block', 'wp-graphql-content-blocks' ),
						'resolve'     => static function ( $block ) {
							return isset( $block['blockName'] ) ? (string) $block['blockName'] : null;
						},
					],
				],
			]
		);
	}

	/**
	 * Resolved the value of the block attributes based on the specified config
	 *
	 * @param array<string,mixed> $attributes The block current attributes value.
	 * @param string              $html The block rendered html.
	 * @param array<string,mixed> $config The block current attribute configuration, keyed to the attribute name.
	 */
	private function resolve_block_attributes_recursive( $attributes, string $html, array $config ): array {
		$result = [];

		foreach ( $config as $key => $value ) {
			// Get default value.
			$default = $value['default'] ?? null;
			$source  = $value['source'] ?? null;

			switch ( $source ) {
				case 'rich-text':
				case 'html':
					// If there is no selector, we are dealing with single source.
					if ( ! isset( $value['selector'] ) ) {
						$result[ $key ] = $this->parse_single_source( $html, $source );
						break;
					}

					$result[ $key ] = $this->parse_html_source( $html, $value );
					break;
				case 'attribute':
					$result[ $key ] = $this->parse_attribute_source( $html, $value );
					break;
				case 'text':
					$result[ $key ] = $this->parse_text_source( $html, $value );
					break;
				case 'query':
					$result[ $key ] = $this->parse_query_source( $html, $value, $attributes );
					break;
			}

			// Post processing of return value based on configured type
			if ( array_key_exists( $key, $result ) ) {
				switch ( $value['type'] ) {
					case 'integer':
						$result[ $key ] = intval( $result[ $key ] );
						break;
					case 'boolean':
						if ( false === $result[ $key ] ) {
							break;
						}
						if ( is_null( $result[ $key ] ) ) {
							$result[ $key ] = false;
							break;
						}
						$result[ $key ] = true;
						break;
				}
			}

			// Fallback to the attributes or default value if the result is empty.
			if ( empty( $result[ $key ] ) ) {
				$result[ $key ] = $attributes[ $key ] ?? $default;
			}
		}

		return $result;
	}

	/**
	 * Parses the block content of a source only block type
	 *
	 * @param string $html The html value
	 * @param string $source The source type
	 *
	 * @return string|null
	 */
	private function parse_single_source( $html, $source ) {
		$value = null;
		if ( empty( $html ) ) {
			return $value;
		}
		switch ( $source ) {
			case 'html':
				$value = DOMHelpers::findNodes( $html )->innerHTML();
				break;
		}
		return $value;
	}

	/**
	 * Parses the block content of an HTML source block type.
	 *
	 * Includes `multiline` handling.
	 *
	 * @param string              $html The html value.
	 * @param array<string,mixed> $value The value configuration.
	 */
	private function parse_html_source( string $html, $value ): ?string {
		if ( ! isset( $value['selector'] ) ) {
			return null;
		}

		$result = DOMHelpers::parseHTML( $html, $value['selector'] );

		// Multiline values are located somewhere else.
		if ( isset( $value['multiline'] ) && ! empty( $result ) ) {
			$result = DOMHelpers::getElementsFromHTML( $result, $value['multiline'] );
		}

		return $result;
	}

	/**
	 * Parses an attribute source block type.
	 *
	 * @param string              $html The html value.
	 * @param array<string,mixed> $value The value configuration.
	 */
	private function parse_attribute_source( string $html, $value ): ?string {
		if ( ! isset( $value['selector'] ) || ! isset( $value['attribute'] ) ) {
			return null;
		}

		return DOMHelpers::parseAttribute( $html, $value['selector'], $value['attribute'] );
	}

	/**
	 * Parses a text source block type.
	 *
	 * @param string              $html The html value.
	 * @param array<string,mixed> $value The value configuration.
	 */
	private function parse_text_source( string $html, $value ): ?string {
		if ( ! isset( $value['selector'] ) ) {
			return null;
		}

		return DOMHelpers::parseText( $html, $value['selector'] );
	}

	/**
	 * Parses a query source block type.
	 *
	 * @param string              $html The html value.
	 * @param array<string,mixed> $value The value configuration.
	 * @param array<string,mixed> $attributes The block attributes.
	 *
	 * @return ?mixed[]
	 */
	private function parse_query_source( string $html, $value, $attributes ): ?array {
		if ( ! isset( $value['selector'] ) || ! isset( $value['query'] ) ) {
			return null;
		}

		$nodes = DOMHelpers::findNodes( $html, $value['selector'] );

		// Coerce nodes to an array if it's not already.
		if ( ! is_array( $nodes ) ) {
			$nodes = [ $nodes ];
		}

		$temp    = [];
		$results = [];
		foreach ( $nodes as $source_node ) {
			foreach ( $value['query'] as $q_key => $q_value ) {
				/** @var array<string,mixed> $temp_config */
				$temp_config = [
					$q_key => $q_value,
				];

				$res            = $this->resolve_block_attributes_recursive( $attributes, $source_node->html(), $temp_config );
				$temp[ $q_key ] = $res[ $q_key ];
			}

			$results[] = $temp;
		}

		return $results;
	}
}

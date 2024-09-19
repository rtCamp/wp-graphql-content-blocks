<?php
/**
 * A GraphQL Model for a WordPress Block.
 *
 * @package WPGraphQL\ContentBlocks\Model
 *
 * @since @todo
 */

namespace WPGraphQL\ContentBlocks\Model;

use WPGraphQL\ContentBlocks\Data\BlockAttributeResolver;
use WPGraphQL\ContentBlocks\Utilities\WPGraphQLHelpers;
use WPGraphQL\Model\Model;

/**
 * Class - Block
 *
 * @property ?string $clientId
 * @property ?string $parentClientId
 * @property ?string $name
 * @property ?string $blockEditorCategoryName
 * @property bool    $isDynamic
 * @property ?int    $apiVersion
 * @property ?string[] $cssClassNames
 * @property ?string $renderedHtml
 * @property self[]  $innerBlocks
 * @property array<string,mixed> $parsedAttributes
 * @property ?string $type
 * @property \WP_Block $wpBlock
 */
class Block extends Model {
	/**
	 * The underlying \WP_Block instance for the block data.
	 *
	 * @var \WP_Block
	 */
	protected $data;

	/**
	 * The rendered block html.
	 *
	 * @var ?string
	 */
	protected $rendered_block;

	/**
	 * {@inheritDoc}
	 *
	 * @param \WP_Block $block The block data to be modeled.
	 */
	public function __construct( \WP_Block $block ) {
		$this->data = $block;

		// Log a Debug message if the block type is not found.
		if ( ! $this->data->block_type ) {
			graphql_debug(
				sprintf(
					__( 'Block type not found for block: %', 'wp-graphql-content-blocks' ),
					$this->data->name ?? 'Unknown'
				),
				[
					'parsed_block' => $block,
				]
			);
		}

		parent::__construct();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function is_private() {
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function init() {
		if ( empty( $this->fields ) ) {
			$this->fields = [
				'clientId'                => fn (): ?string => $this->data->parsed_block['clientId'] ?? uniqid(),
				'parentClientId'          => fn (): ?string => $this->data->parsed_block['parentClientId'] ?? null,
				'name'                    => fn (): ?string => isset( $this->data->name ) ? $this->data->name : null,
				'blockEditorCategoryName' => fn () => isset( $this->data->block_type->category ) ? $this->data->block_type->category : null,
				'isDynamic'               => fn (): bool => isset( $this->data->block_type->render_callback ) && is_callable( $this->data->block_type->render_callback ),
				'apiVersion'              => fn (): ?int => isset( $this->data->block_type->api_version ) ? $this->data->block_type->api_version : null,
				'cssClassNames'           => fn (): ?array => isset( $this->data->attributes['className'] ) ? explode( ' ', $this->data->attributes['className'] ) : null,
				'renderedHtml'            => fn (): ?string => $this->get_rendered_block(),
				'innerBlocks'             => function (): array {
					$block_list = $this->data->inner_blocks ?? [];

					$models_to_return = [];

					foreach ( $block_list as $block ) {
						$models_to_return[] = new self( $block );
					}

					return $models_to_return;
				},
				'parsedAttributes'        => fn (): array => $this->data->attributes,
				'type'                    => function (): ?string {
					$block_name = $this->name ?? null;

					return isset( $block_name ) ? WPGraphQLHelpers::format_type_name( $block_name ) : null;
				},
				'wpBlock' => function (): ?\WP_Block {
					return $this->data;
				}
			];
		}
	}

	/**
	 * Renders the block html - only once.
	 *
	 * The `render_block()` function causes side effects (such as globally-incrementing the counter used for layout styles), so we only want to call it once.
	 */
	protected function get_rendered_block(): ?string {
		if ( ! isset( $this->rendered_block ) ) {
			$rendered             = $this->data->render();
			$this->rendered_block = do_shortcode( $rendered );
		}

		return $this->rendered_block;
	}

	/**
	 * Resolves the block attributes.
	 *
	 * @return array<string,callable(string,array<string,mixed>):mixed>
	 */
	protected function resolve_attributes(): array {
		$registered_attributes = $this->data->block_type->attributes ?? [];

		$resolvers = [];

		foreach ( array_keys( $registered_attributes ) as $attribute_name ) {
			$resolvers[ $attribute_name ] = fn () => $this->resolve_attribute( $attribute_name );
		}

		return $resolvers;
	}

	/**
	 * Resolves a single block attribute.
	 *
	 * @param string $attribute_name The name of the attribute being resolved.
	 *
	 * @return mixed
	 */
	protected function resolve_attribute( string $attribute_name ) {
		$attribute_config = $this->data->block_type->attributes[ $attribute_name ] ?? [];

		return BlockAttributeResolver::resolve_block_attribute( $attribute_config, $this->get_rendered_block(), $this->data->attributes[ $attribute_name ] );

		$allowed_types = [ 'array', 'object', 'string', 'number', 'integer', 'boolean', 'null' ];
		// If attribute type is set and valid, sanitize value.
		if ( isset( $attribute['type'] ) && in_array( $attribute_config['type'], $allowed_types, true ) && rest_validate_value_from_schema( $result, $attribute_config ) ) {
			$result = rest_sanitize_value_from_schema( $result, $attribute_config );
		}

		return $result;
	}
}

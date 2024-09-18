<?php

namespace WPGraphQL\ContentBlocks\Unit;

final class CorePreformattedTest extends PluginTestCase {
	public $post_id;

	public function setUp(): void {
		parent::setUp();

		$this->post_id = wp_insert_post(
			[
				'post_title'   => 'Post with Preformatted',
				'post_content' => '',
				'post_status'  => 'publish',
			]
		);

		\WPGraphQL::clear_schema();
	}

	public function tearDown(): void {
		parent::tearDown();

		wp_delete_post( $this->post_id, true );

		\WPGraphQL::clear_schema();
	}

	public function query(): string {
		return '
			fragment CorePreformattedBlockFragment on CorePreformatted {
				attributes {
					align
					anchor
					backgroundColor
					className
					content
					fontSize
					gradient
					style
					textColor
				}
			}
			query Post( $id: ID! ) {
				post(id: $id, idType: DATABASE_ID) {
					databaseId
					editorBlocks {
						name
						...CorePreformattedBlockFragment
					}
				}
			}
		';
	}

	public function test_retrieve_core_preformatted_basic_attributes() {
		$block_content = '
			<!-- wp:preformatted -->
			<pre class="wp-block-preformatted">This is preformatted text.
It preserves      spaces and
line breaks.</pre>
			<!-- /wp:preformatted -->
		';

		wp_update_post(
			[
				'ID'           => $this->post_id,
				'post_content' => $block_content,
			]
		);

		$actual = graphql([
			'query' => $this->query(),
			'variables' => ['id' => $this->post_id],
		]);

		$this->assertArrayHasKey('data', $actual, 'GraphQL response is missing "data" key');
		$this->assertArrayHasKey('post', $actual['data'], 'GraphQL response is missing "post" key');
		$this->assertArrayHasKey('editorBlocks', $actual['data']['post'], 'GraphQL response is missing "editorBlocks" key');
		$this->assertNotEmpty($actual['data']['post']['editorBlocks'], 'Editor blocks array is empty');

		$block = $actual['data']['post']['editorBlocks'][0];
		$this->assertArrayHasKey('attributes', $block, 'Block is missing "attributes" key');
		$attributes = $block['attributes'];

		$this->assertEquals('core/preformatted', $block['name']);
		$this->assertEquals("This is preformatted text.
It preserves      spaces and
line breaks.", $attributes['content']);
		$this->assertArrayNotHasKey('align', $attributes);
		$this->assertArrayNotHasKey('anchor', $attributes);
		$this->assertArrayNotHasKey('backgroundColor', $attributes);
		$this->assertArrayNotHasKey('className', $attributes);
	}

	public function test_retrieve_core_preformatted_with_custom_attributes() {
		$block_content = '
			<!-- wp:preformatted {"align":"wide","anchor":"custom-id","backgroundColor":"pale-pink","className":"custom-class","fontSize":"large","textColor":"vivid-red"} -->
			<pre id="custom-id" class="wp-block-preformatted alignwide custom-class has-pale-pink-background-color has-vivid-red-color has-text-color has-background has-large-font-size">Customized preformatted text.</pre>
			<!-- /wp:preformatted -->
		';

		wp_update_post(
			[
				'ID'           => $this->post_id,
				'post_content' => $block_content,
			]
		);

		$actual = graphql([
			'query' => $this->query(),
			'variables' => ['id' => $this->post_id],
		]);

		$this->assertArrayHasKey('data', $actual, 'GraphQL response is missing "data" key');
		$this->assertArrayHasKey('post', $actual['data'], 'GraphQL response is missing "post" key');
		$this->assertArrayHasKey('editorBlocks', $actual['data']['post'], 'GraphQL response is missing "editorBlocks" key');
		$this->assertNotEmpty($actual['data']['post']['editorBlocks'], 'Editor blocks array is empty');

		$block = $actual['data']['post']['editorBlocks'][0];
		$this->assertArrayHasKey('attributes', $block, 'Block is missing "attributes" key');
		$attributes = $block['attributes'];

		$this->assertEquals('Customized preformatted text.', $attributes['content']);
		$this->assertEquals('wide', $attributes['align']);
		$this->assertEquals('custom-id', $attributes['anchor']);
		$this->assertEquals('pale-pink', $attributes['backgroundColor']);
		$this->assertEquals('custom-class', $attributes['className']);
		$this->assertEquals('large', $attributes['fontSize']);
		$this->assertEquals('vivid-red', $attributes['textColor']);
	}

	public function test_retrieve_core_preformatted_with_custom_styles() {
		$block_content = '
			<!-- wp:preformatted {"style":{"color":{"background":"#e0e0e0","text":"#222222","gradient":"linear-gradient(135deg,rgb(6,147,227) 0%,rgb(155,81,224) 100%)"},"typography":{"fontSize":"14px","fontStyle":"italic","fontWeight":"700"}}} -->
			<pre class="wp-block-preformatted has-background" style="background:linear-gradient(135deg,rgb(6,147,227) 0%,rgb(155,81,224) 100%);color:#222222;font-size:14px;font-style:italic;font-weight:700">Styled preformatted text.</pre>
			<!-- /wp:preformatted -->
		';

		wp_update_post(
			[
				'ID'           => $this->post_id,
				'post_content' => $block_content,
			]
		);

		$actual = graphql([
			'query' => $this->query(),
			'variables' => ['id' => $this->post_id],
		]);

		$this->assertArrayHasKey('data', $actual, 'GraphQL response is missing "data" key');
		$this->assertArrayHasKey('post', $actual['data'], 'GraphQL response is missing "post" key');
		$this->assertArrayHasKey('editorBlocks', $actual['data']['post'], 'GraphQL response is missing "editorBlocks" key');
		$this->assertNotEmpty($actual['data']['post']['editorBlocks'], 'Editor blocks array is empty');

		$block = $actual['data']['post']['editorBlocks'][0];
		$this->assertArrayHasKey('attributes', $block, 'Block is missing "attributes" key');
		$attributes = $block['attributes'];

		$this->assertEquals('Styled preformatted text.', $attributes['content']);
		
		$this->assertArrayHasKey('style', $attributes, 'Attributes is missing "style" key');
		$style = json_decode($attributes['style'], true);
		$this->assertIsArray($style, 'Style is not a valid JSON string');
		$this->assertArrayHasKey('color', $style, 'Style is missing "color" key');
		$this->assertArrayHasKey('typography', $style, 'Style is missing "typography" key');
		
		$this->assertEquals('#e0e0e0', $style['color']['background']);
		$this->assertEquals('#222222', $style['color']['text']);
		$this->assertEquals('linear-gradient(135deg,rgb(6,147,227) 0%,rgb(155,81,224) 100%)', $style['color']['gradient']);
		$this->assertEquals('14px', $style['typography']['fontSize']);
		$this->assertEquals('italic', $style['typography']['fontStyle']);
		$this->assertEquals('700', $style['typography']['fontWeight']);
	}
}

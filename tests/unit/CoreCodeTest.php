<?php

namespace WPGraphQL\ContentBlocks\Unit;

final class CoreCodeTest extends PluginTestCase {
	public $post_id;

	public function setUp(): void {
		parent::setUp();

		$this->post_id = wp_insert_post(
			[
				'post_title'   => 'Post with Code',
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
			fragment CoreCodeBlockFragment on CoreCode {
				attributes {
					content
					cssClassName
					fontSize
					style
				}
			}
			query Post( $id: ID! ) {
				post(id: $id, idType: DATABASE_ID) {
					databaseId
					editorBlocks {
						name
						...CoreCodeBlockFragment
					}
				}
			}
		';
	}

	public function test_retrieve_core_code_basic_attributes() {
		$block_content = '
			<!-- wp:code -->
			<pre class="wp-block-code"><code>function helloWorld() {
  console.log("Hello, World!");
}</code></pre>
			<!-- /wp:code -->
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

		$block = $actual['data']['post']['editorBlocks'][0];
		$attributes = $block['attributes'];

		$this->assertEquals('core/code', $block['name']);
		$this->assertEquals('function helloWorld() {
  console.log("Hello, World!");
}', $attributes['content']);
		$this->assertEquals('wp-block-code', $attributes['cssClassName']);
	}

	public function test_retrieve_core_code_with_custom_font_size() {
		$block_content = '
			<!-- wp:code {"fontSize":"large"} -->
			<pre class="wp-block-code has-large-font-size"><code>var x = 10;</code></pre>
			<!-- /wp:code -->
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

		$block = $actual['data']['post']['editorBlocks'][0];
		$attributes = $block['attributes'];

		$this->assertEquals('var x = 10;', $attributes['content']);
		$this->assertEquals('large', $attributes['fontSize']);
	}

	public function test_retrieve_core_code_with_custom_styles() {
		$block_content = '
			<!-- wp:code {"style":{"color":{"background":"#f0f0f0","text":"#333333"},"typography":{"fontSize":"16px"}}} -->
			<pre class="wp-block-code" style="background-color:#f0f0f0;color:#333333;font-size:16px"><code>const y = 20;</code></pre>
			<!-- /wp:code -->
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

		$block = $actual['data']['post']['editorBlocks'][0];
		$attributes = $block['attributes'];

		$this->assertEquals('const y = 20;', $attributes['content']);
		
		$style = json_decode($attributes['style'], true);
		$this->assertEquals('#f0f0f0', $style['color']['background']);
		$this->assertEquals('#333333', $style['color']['text']);
		$this->assertEquals('16px', $style['typography']['fontSize']);
	}
}

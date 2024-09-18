<?php

namespace WPGraphQL\ContentBlocks\Unit;

final class CoreParagraphTest extends PluginTestCase {
	public $post_id;

	public function setUp(): void {
		parent::setUp();

		$this->post_id = wp_insert_post(
			[
				'post_title'   => 'Post with Paragraph',
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
			fragment CoreParagraphBlockFragment on CoreParagraph {
				attributes {
					align
					backgroundColor
					className
					content
					dropCap
					fontSize
					gradient
					placeholder
					style
					textColor
				}
			}
			query Post( $id: ID! ) {
				post(id: $id, idType: DATABASE_ID) {
					databaseId
					editorBlocks {
						name
						...CoreParagraphBlockFragment
					}
				}
			}
		';
	}

	public function test_retrieve_core_paragraph_basic_attributes() {
		$block_content = '
			<!-- wp:paragraph {"align":"center"} -->
			<p class="has-text-align-center">This is a centered paragraph.</p>
			<!-- /wp:paragraph -->
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

		$this->assertEquals('core/paragraph', $block['name']);
		$this->assertEquals('This is a centered paragraph.', $attributes['content']);
		$this->assertEquals('center', $attributes['align']);
	}

	public function test_retrieve_core_paragraph_with_colors_and_font_size() {
		$block_content = '
			<!-- wp:paragraph {"backgroundColor":"vivid-red-background","textColor":"vivid-green-cyan","fontSize":"large"} -->
			<p class="has-vivid-red-background-color has-vivid-green-cyan-color has-text-color has-background has-large-font-size">Colored paragraph with large font size.</p>
			<!-- /wp:paragraph -->
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

		$this->assertEquals('Colored paragraph with large font size.', $attributes['content']);
		$this->assertEquals('vivid-red-background', $attributes['backgroundColor']);
		$this->assertEquals('vivid-green-cyan', $attributes['textColor']);
		$this->assertEquals('large', $attributes['fontSize']);
	}

	public function test_retrieve_core_paragraph_with_drop_cap() {
		$block_content = '
			<!-- wp:paragraph {"dropCap":true} -->
			<p class="has-drop-cap">This paragraph has a drop cap.</p>
			<!-- /wp:paragraph -->
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

		$this->assertEquals('This paragraph has a drop cap.', $attributes['content']);
		$this->assertTrue($attributes['dropCap']);
	}

	public function test_retrieve_core_paragraph_with_custom_styles() {
		$block_content = '
			<!-- wp:paragraph {"style":{"color":{"gradient":"linear-gradient(135deg,rgb(6,147,227) 0%,rgb(155,81,224) 100%)"},"typography":{"fontSize":"18px","fontStyle":"italic","fontWeight":"700"}}} -->
			<p class="has-background" style="background:linear-gradient(135deg,rgb(6,147,227) 0%,rgb(155,81,224) 100%);font-size:18px;font-style:italic;font-weight:700">Paragraph with custom styles.</p>
			<!-- /wp:paragraph -->
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

		$this->assertEquals('Paragraph with custom styles.', $attributes['content']);
		
		$style = json_decode($attributes['style'], true);
		$this->assertEquals('linear-gradient(135deg,rgb(6,147,227) 0%,rgb(155,81,224) 100%)', $style['color']['gradient']);
		$this->assertEquals('18px', $style['typography']['fontSize']);
		$this->assertEquals('italic', $style['typography']['fontStyle']);
		$this->assertEquals('700', $style['typography']['fontWeight']);
	}
}

<?php

namespace WPGraphQL\ContentBlocks\Unit;

final class CoreSeparatorTest extends PluginTestCase {
	/**
	 * The ID of the post created for the test.
	 *
	 * @var int
	 */
	public $post_id;

	public function setUp(): void {
		parent::setUp();

		$this->post_id = wp_insert_post(
			[
				'post_title'   => 'Post with Separator',
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
			fragment CoreSeparatorBlockFragment on CoreSeparator {
				attributes {
					align
					anchor
					backgroundColor
					className
					cssClassName
					gradient
					lock
					# metadata
					opacity
					style
				}
			}

			query Post( $id: ID! ) {
				post(id: $id, idType: DATABASE_ID) {
					databaseId
					editorBlocks {
						apiVersion
						blockEditorCategoryName
						clientId
						cssClassNames
						innerBlocks {
							name
						}
						isDynamic
						name
						parentClientId
						renderedHtml
						...CoreSeparatorBlockFragment
					}
				}
			}
		';
	}

	public function test_core_separator_fields_and_attributes() {
		$block_content = '
		<!-- wp:separator {"lock":{"move":true,"remove":true},"align":"wide","className":"is-style-dots","style":{"color":{"gradient":"linear-gradient(135deg,rgb(6,147,227) 1%,rgb(155,81,224) 100%)"}}} -->
		<hr class="wp-block-separator alignwide has-alpha-channel-opacity"/>
		<!-- /wp:separator -->
		';

		// Set post content.
		wp_update_post(
			[
				'ID'           => $this->post_id,
				'post_content' => $block_content,
			]
		);

		$query     = $this->query();
		$variables = [
			'id' => $this->post_id,
		];

		$actual = graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual, 'There should not be any errors' );
		$this->assertArrayHasKey( 'data', $actual, 'The data key should be present' );
		$this->assertArrayHasKey( 'post', $actual['data'], 'The post key should be present' );

		// Verify that the ID of the first post matches the one we just created.
		$this->assertEquals( $this->post_id, $actual['data']['post']['databaseId'], 'The post ID should match' );

		// There should be only one block using that query when not using flat: true
		$this->assertEquals( 1, count( $actual['data']['post']['editorBlocks'] ) );

		$block = $actual['data']['post']['editorBlocks'][0];

		// Verify the block data.
		$this->assertNotEmpty( $block['apiVersion'], 'The apiVersion should be present' );
		$this->assertEquals( 'design', $block['blockEditorCategoryName'], 'The blockEditorCategoryName should be text' );
		$this->assertNotEmpty( $block['clientId'], 'The clientId should be present' );
		$this->assertNotEmpty( $block['cssClassNames'], 'There should be cssClassNames' );
		$this->assertEmpty( $block['innerBlocks'], 'There should be no inner blocks' );
		$this->assertEquals( 'core/separator', $block['name'], 'The block name should be core/separator' );
		$this->assertEmpty( $block['parentClientId'], 'There should be no parentClientId' );
		$this->assertNotEmpty( $block['renderedHtml'], 'The renderedHtml should be present' );

		// Verify the attributes.
		$this->assertEquals(
			[
				'align'           => 'wide',
				'anchor'          => null,
				'backgroundColor' => null,
				'className'       => 'is-style-dots',
				'cssClassName'    => 'wp-block-separator alignwide has-alpha-channel-opacity',
				'gradient'        => null, // @todo: getting null returned here, but should be the gradient value.
				'opacity'         => 'alpha-channel',
				'style'           => '{"color":{"gradient":"linear-gradient(135deg,rgb(6,147,227) 1%,rgb(155,81,224) 100%)"}}',
				'lock'            => '{"move":true,"remove":true}',
			],
			$block['attributes'],
		);
	}

	public function test_core_separator_untested_attributes() {
		$block_content = '
			<!-- wp:separator {"backgroundColor":"accent-4"}  -->
			<hr class="wp-block-separator has-text-color has-accent-4-color has-alpha-channel-opacity has-accent-4-background-color has-background" id="test-anchor"/>
			<!-- /wp:separator -->
		';

		// Set post content.
		wp_update_post(
			[
				'ID'           => $this->post_id,
				'post_content' => $block_content,
			]
		);

		$query     = $this->query();
		$variables = [
			'id' => $this->post_id,
		];

		$actual = graphql( compact( 'query', 'variables' ) );

		$this->assertArrayNotHasKey( 'errors', $actual, 'There should not be any errors' );
		$this->assertArrayHasKey( 'data', $actual, 'The data key should be present' );
		$this->assertArrayHasKey( 'post', $actual['data'], 'The post key should be present' );

		// Verify that the ID of the first post matches the one we just created.
		$this->assertEquals( $this->post_id, $actual['data']['post']['databaseId'], 'The post ID should match' );

		// There should be only one block using that query when not using flat: true
		$this->assertEquals( 1, count( $actual['data']['post']['editorBlocks'] ) );
		$this->assertEquals( 'core/separator', $actual['data']['post']['editorBlocks'][0]['name'], 'The block name should be core/separator' );

		// Verify the attributes.
		$this->assertEquals(
			[
				'align'           => null,
				'anchor'          => 'test-anchor',
				'backgroundColor' => 'accent-4',
				'className'       => null,
				'cssClassName'    => 'wp-block-separator has-text-color has-accent-4-color has-alpha-channel-opacity has-accent-4-background-color has-background',
				'gradient'        => null,
				'opacity'         => 'alpha-channel',
				'style'           => null,
				'lock'            => null,
			],
			$actual['data']['post']['editorBlocks'][0]['attributes'],
		);
	}
}
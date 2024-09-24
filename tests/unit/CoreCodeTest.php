<?php

namespace WPGraphQL\ContentBlocks\Unit;

final class CoreCodeTest extends PluginTestCase {
	/**
	 * The ID of the post created for the test.
	 *
	 * @var int
	 */
	public $post_id;

	/**
	 * Set up the test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->post_id = wp_insert_post(
			[
				'post_title'   => 'Post with Code Block',
				'post_content' => '',
				'post_status'  => 'publish',
			]
		);

		\WPGraphQL::clear_schema();
	}

	/**
	 * Tear down the test environment.
	 */
	public function tearDown(): void {
		parent::tearDown();

		wp_delete_post( $this->post_id, true );

		\WPGraphQL::clear_schema();
	}

	/**
	 * Provide the GraphQL query for testing.
	 *
	 * @return string The GraphQL query.
	 */
	public function query(): string {
		return '
            fragment CoreCodeBlockFragment on CoreCode {
                attributes {
                    align
                    anchor
                    backgroundColor
                    borderColor
                    className
                    content
                    cssClassName
                    fontFamily
                    fontSize
                    gradient
                    lock
                    metadata
                    style
                    textColor
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

	/**
	 * Test the retrieval of core/code block attributes.
	 *
	 * Attributes covered:
	 * - content
	 * - cssClassName
	 * - backgroundColor
	 * - textColor
	 * - fontSize
	 * - fontFamily
	 *
	 * @return void
	 */
	public function test_retrieve_core_code_attributes() {
		$block_content = '
            <!-- wp:code {"backgroundColor":"pale-cyan-blue","textColor":"vivid-red","fontSize":"large","fontFamily":"monospace"} -->
            <pre class="wp-block-code has-vivid-red-color has-pale-cyan-blue-background-color has-text-color has-background has-large-font-size has-monospace-font-family"><code>function hello_world() {
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

		$actual = graphql(
			[
				'query'     => $this->query(),
				'variables' => [ 'id' => $this->post_id ],
			]
		);

		$block      = $actual['data']['post']['editorBlocks'][0];
		$attributes = $block['attributes'];

		$this->assertEquals( 'core/code', $block['name'] );
		$this->assertEquals(
			[
				'align'           => null,
				'anchor'          => null,
				'backgroundColor' => 'pale-cyan-blue',
				'borderColor'     => null,
				'className'       => null,
				'content'         => "function hello_world() {\n    console.log(\"Hello, World!\");\n}",
				'cssClassName'    => 'wp-block-code has-vivid-red-color has-pale-cyan-blue-background-color has-text-color has-background has-large-font-size has-monospace-font-family',
				'fontFamily'      => 'monospace',
				'fontSize'        => 'large',
				'gradient'        => null,
				'lock'            => null,
				'metadata'        => null,
				'style'           => null,
				'textColor'       => 'vivid-red',
			],
			$attributes
		);
	}

	/**
	 * Test retrieval of core/code block with custom styles and border color.
	 *
	 * Attributes covered:
	 * - borderColor
	 * - style
	 * - className
	 * - align
	 *
	 * @return void
	 */
	public function test_retrieve_core_code_with_custom_styles() {
		$block_content = '
            <!-- wp:code {"borderColor":"vivid-cyan-blue","className":"custom-class","align":"wide","style":{"border":{"width":"2px"},"spacing":{"padding":{"top":"20px","right":"20px","bottom":"20px","left":"20px"}}}} -->
            <pre class="wp-block-code alignwide custom-class has-border-color has-vivid-cyan-blue-border-color" style="border-width:2px;padding-top:20px;padding-right:20px;padding-bottom:20px;padding-left:20px"><code>const greeting = "Hello, styled code!";</code></pre>
            <!-- /wp:code -->
        ';

		wp_update_post(
			[
				'ID'           => $this->post_id,
				'post_content' => $block_content,
			]
		);

		$actual = graphql(
			[
				'query'     => $this->query(),
				'variables' => [ 'id' => $this->post_id ],
			]
		);

		$block      = $actual['data']['post']['editorBlocks'][0];
		$attributes = $block['attributes'];

		$this->assertEquals(
			[
				'align'           => 'wide',
				'anchor'          => null,
				'backgroundColor' => null,
				'borderColor'     => 'vivid-cyan-blue',
				'className'       => 'custom-class',
				'content'         => 'const greeting = "Hello, styled code!";',
				'cssClassName'    => 'wp-block-code alignwide custom-class has-border-color has-vivid-cyan-blue-border-color',
				'fontFamily'      => null,
				'fontSize'        => null,
				'gradient'        => null,
				'lock'            => null,
				'metadata'        => null,
				'style'           => wp_json_encode(
					[
						'border'  => [
							'width' => '2px',
						],
						'spacing' => [
							'padding' => [
								'top'    => '20px',
								'right'  => '20px',
								'bottom' => '20px',
								'left'   => '20px',
							],
						],
					]
				),
				'textColor'       => null,
			],
			$attributes
		);
	}

	/**
	 * Test retrieval of core/code block with gradient and additional attributes.
	 *
	 * Attributes covered:
	 * - gradient
	 * - anchor
	 * - lock
	 * - metadata
	 *
	 * @return void
	 */
	public function test_retrieve_core_code_with_gradient_and_additional_attributes() {
		$block_content = '
            <!-- wp:code {"anchor":"test-anchor","gradient":"vivid-cyan-blue-to-vivid-purple","lock":{"move":true,"remove":true},"metadata":{"someKey":"someValue"}} -->
            <pre id="test-anchor" class="wp-block-code has-vivid-cyan-blue-to-vivid-purple-gradient-background"><code>console.log("Gradient and locked code block");</code></pre>
            <!-- /wp:code -->
        ';

		wp_update_post(
			[
				'ID'           => $this->post_id,
				'post_content' => $block_content,
			]
		);

		$actual = graphql(
			[
				'query'     => $this->query(),
				'variables' => [ 'id' => $this->post_id ],
			]
		);

		$block      = $actual['data']['post']['editorBlocks'][0];
		$attributes = $block['attributes'];

		$this->assertEquals(
			[
				'align'           => null,
				'anchor'          => 'test-anchor',
				'backgroundColor' => null,
				'borderColor'     => null,
				'className'       => null,
				'content'         => 'console.log("Gradient and locked code block");',
				'cssClassName'    => 'wp-block-code has-vivid-cyan-blue-to-vivid-purple-gradient-background',
				'fontFamily'      => null,
				'fontSize'        => null,
				'gradient'        => 'vivid-cyan-blue-to-vivid-purple',
				'lock'            => '{"move":true,"remove":true}',
				'metadata'        => '{"someKey":"someValue"}',
				'style'           => null,
				'textColor'       => null,
			],
			$attributes
		);
	}
}
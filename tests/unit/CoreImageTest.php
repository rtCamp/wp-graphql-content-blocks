<?php

namespace WPGraphQL\ContentBlocks\Unit;

final class CoreImageTest extends PluginTestCase {
	public $instance;
	public $post_id;
	public $attachment_id;

	public function setUp(): void {
		parent::setUp();

		$this->attachment_id = $this->factory->attachment->create_upload_object( WP_TEST_DATA_DIR . '/images/test-image.jpg' );

		$this->post_id = wp_insert_post(
			[
				'post_title'   => 'Post Title',
				'post_content' => preg_replace(
					'/\s+/',
					' ',
					trim(
						'
							<!-- wp:image {"width":500,"height":500,"sizeSlug":"full","linkDestination":"none", "id":' . $this->attachment_id . '} -->
							<figure class="wp-block-image size-full is-resized"><img src="http://mysite.local/wp-content/uploads/2023/05/online-programming-course-hero-section-bg.svg" alt="" class="wp-image-1432" width="500" height="500"/></figure>
							<!-- /wp:image -->
						'
					)
				),
				'post_status'  => 'publish',
			]
		);

		\WPGraphQL::clear_schema();
	}

	public function tearDown(): void {
		// your tear down methods here
		wp_delete_post( $this->post_id, true );
		\WPGraphQL::clear_schema();

		parent::tearDown();
	}

	public function test_retrieve_core_image_media_details() {
		$query  = '
			fragment CoreImageBlockFragment on CoreImage {
				attributes {
					id
				}
				mediaDetails {
					height
					width
				}
			}
			
			query GetPosts {
				posts(first: 1) {
					nodes {
						editorBlocks {
							...CoreImageBlockFragment
						}
					}
				}
			}
		';
		$actual = graphql( [ 'query' => $query ] );
		$node   = $actual['data']['posts']['nodes'][0];

		$this->assertEquals(
			[
				'width'  => 50,
				'height' => 50,
			],
			$node['editorBlocks'][0]['mediaDetails']
		);
	}

	public function test_retrieve_core_image_attributes() {
		$query  = '
			fragment CoreImageBlockFragment on CoreImage {
				attributes {
					id
					width
					height
					alt
					src
					style
					sizeSlug
					linkClass
					linkTarget
					linkDestination
					align
					caption
					cssClassName
				}
			}
			
			query GetPosts {
				posts(first: 1) {
					nodes {
					databaseId
						editorBlocks {
							name
							...CoreImageBlockFragment
						}
					}
				}
			}
		';
		$actual = graphql( [ 'query' => $query ] );
		$node   = $actual['data']['posts']['nodes'][0];

		// Verify that the ID of the first post matches the one we just created.
		$this->assertEquals( $this->post_id, $node['databaseId'] );
		// There should be only one block using that query when not using flat: true
		$this->assertEquals( 1, count( $node['editorBlocks'] ) );
		$this->assertEquals( 'core/image', $node['editorBlocks'][0]['name'] );

		$this->assertEquals(
			[
				'width'           => '500',
				'height'          => 500.0,
				'alt'             => '',
				'id'              => $this->attachment_id,
				'src'             => 'http://mysite.local/wp-content/uploads/2023/05/online-programming-course-hero-section-bg.svg',
				'style'           => null,
				'sizeSlug'        => 'full',
				'linkClass'       => null,
				'linkTarget'      => null,
				'linkDestination' => 'none',
				'align'           => null,
				'caption'         => '',
				'cssClassName'    => 'wp-block-image size-full is-resized',
			],
			$node['editorBlocks'][0]['attributes']
		);
	}
}

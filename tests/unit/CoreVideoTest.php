<?php

namespace WPGraphQL\ContentBlocks\Unit;

final class CoreVideoTest extends PluginTestCase {
	public $instance;
	public $post_id;

	public function setUp(): void {
		parent::setUp();

		$this->post_id = wp_insert_post(
			[
				'post_title'   => 'Post Title',
				'post_content' => preg_replace(
					'/\s+/',
					' ',
					trim(
						'
							<!-- wp:video {"id":1636} -->
							<figure class="wp-block-video"><video autoplay loop poster="http://mysite.local/wp-content/uploads/2023/05/pexels-egor-komarov-14420089-scaled.jpg" preload="auto" src="http://mysite.local/wp-content/uploads/2023/07/pexels_videos_1860684-1440p.mp4" playsinline></video></figure>
							<!-- /wp:video -->
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

	public function test_retrieve_core_video_attributes() {
		$query  = '	
			fragment CoreVideoBlockFragment on CoreVideo {
				attributes {
						align
						anchor
						autoplay
						tracks
						muted
						caption
						preload
						src
						playsInline
						controls
						loop
						poster
						id
					}
				}
			
			query GetPosts {
				posts(first: 1) {
					nodes {
					databaseId
						editorBlocks {
							name
							...CoreVideoBlockFragment
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
		$this->assertEquals( 'core/video', $node['editorBlocks'][0]['name'] );

		$this->assertEquals(
			[
				'align'       => null,
				'anchor'      => null,
				'autoplay'    => true,
				'tracks'      => [],
				'muted'       => null,
				'caption'     => null,
				'preload'     => 'auto',
				'src'         => 'http://mysite.local/wp-content/uploads/2023/07/pexels_videos_1860684-1440p.mp4',
				'playsInline' => true,
				'controls'    => true,
				'loop'        => true,
				'poster'      => 'http://mysite.local/wp-content/uploads/2023/05/pexels-egor-komarov-14420089-scaled.jpg',
				'id'          => 1636.0,
			],
			$node['editorBlocks'][0]['attributes']
		);
	}
}

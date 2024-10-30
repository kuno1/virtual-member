<?php

namespace Kunoichi\VirtualMember\Rest;

use Kunoichi\VirtualMember\Rest\Pattern\RestApiPattern;

/**
 * REST API for post authors.
 */
class SearchAuthorsApi extends RestApiPattern {

	protected function route(): string {
		return '/authors/search/?';
	}

	protected function get_args( $method ): array {
		return [
			's' => [
				'type'              => 'string',
				'required'          => true,
				'validate_callback' => function ( $s ) {
					return ! empty( $s );
				},
			],
		];
	}

	/**
	 * List register authors.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function handle_get( $request ) {
		$author_query = new \WP_Query( [
			'post_type'      => $this->instance()->post_type(),
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'orderby'        => [ 'post_title' => 'ASC' ],
			's'              => $request->get_param( 's' ),
		] );
		return new \WP_REST_Response( array_map( function( $post ) {
			return $this->convert( $post );
		},$author_query->posts ) );
	}

	public function permission_callback( $request ) {
		return current_user_can( 'edit_posts' );
	}
}

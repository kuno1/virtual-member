<?php

namespace Kunoichi\VirtualMember\Rest;

use Kunoichi\VirtualMember\Rest\Pattern\RestApiPattern;

/**
 * REST API for post authors.
 */
class PostAuthorsApi extends RestApiPattern {

	protected function route(): string {
		return '/authors/of/(?P<post_id>\d+)';
	}

	protected function get_args( $method ): array {
		return [
			'post_id' => [
				'type'              => 'integer',
				'required'          => true,
				'validate_callback' => function ( $post_id ) {
					$post = get_post( $post_id );
					return $post && $this->use_member( $post->post_type );
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
		return new \WP_REST_Response( array_map( function ( $post ) {
			return $this->convert( $post );
		}, $this->get_members( $request->get_param( 'post_id' ), -1, true ) ) );
	}

	public function permission_callback( $request ) {
		return current_user_can( 'edit_post', $request->get_param( 'post_id' ) );
	}
}

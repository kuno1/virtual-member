<?php

namespace Kunoichi\VirtualMember\Rest\Pattern;


use Kunoichi\VirtualMember\Pattern\Singleton;
use Kunoichi\VirtualMember\PostType;
use Kunoichi\VirtualMember\Utility\CommonMethods;

/**
 * REST API pattern
 *
 * If this class has public methods `handle_{http_method}` then it will be registered as REST API.
 *
 * @method \WP_REST_Response|\WP_Error handle_get( \WP_REST_Request $request )
 * @method \WP_REST_Response|\WP_Error handle_post( \WP_REST_Request $request )
 * @method \WP_REST_Response|\WP_Error handle_delete( \WP_REST_Request $request )
 * @method \WP_REST_Response|\WP_Error handle_put( \WP_REST_Request $request )
 * @method \WP_REST_Response|\WP_Error handle_patch( \WP_REST_Request $request )
 */
abstract class RestApiPattern extends Singleton {

	use CommonMethods;

	/**
	 * Namespace of REST API
	 *
	 * @return string
	 */
	protected function namespace() {
		return 'kvm/v1';
	}

	/**
	 * Define route
	 *
	 * @return string
	 */
	abstract protected function route(): string;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	protected function init() {
		add_action( 'rest_api_init', [ $this, 'register_rest_api' ] );
	}

	/**
	 * Register REST API.
	 *
	 * @return void
	 */
	public function register_rest_api() {
		$apis = [];
		foreach ( [ 'get', 'post', 'delete', 'put', 'patch', 'delete' ] as $method ) {
			$handler = 'handle_' . $method;
			if ( ! method_exists( $this, $handler ) ) {
				continue;
			}
			$apis[] = [
				'methods'             => strtoupper( $method ),
				'args'                => $this->get_args( strtoupper( $method ) ),
				'callback'            => [ $this, $handler ],
				'permission_callback' => [ $this, 'permission_callback' ],
			];
		}
		if ( ! empty( $apis ) ) {
			register_rest_route( $this->namespace(), $this->route(), $apis );
		}
	}

	/**
	 * Get arguments for REST API.
	 *
	 * @param string $method HTTP methods.
	 * @return array
	 */
	abstract protected function get_args( $method ): array;

	/**
	 * Permission callback for API.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool|\WP_Error
	 */
	public function permission_callback( $request ) {
		return is_user_logged_in();
	}

	/**
	 * Convert user object to array.
	 *
	 * @param \WP_Post $user User object.
	 * @return array{}
	 */
	public function convert( $user ) {
		$terms  = get_the_terms( $user, $this->instance()->taxonomy() );
		$groups = ( $terms && ! is_wp_error( $terms ) ) ? array_map( function ( $term ) {
			return [
				'id'   => $term->term_id,
				'name' => $term->name,
			];
		}, $terms ) : [];
		return [
			'id'           => $user->ID,
			'name'         => get_the_title( $user ),
			'default'      => $this->instance()::default_user() === $user->ID,
			'organization' => $this->instance()::is_organization( $user ),
			'represents'   => $this->instance()::is_representative( $user ),
			'group'        => $groups,
		];
	}
}

<?php

namespace Kunoichi\VirtualMember;


use Hametuha\SingletonPattern\Singleton;

/*
 * Post type.
 *
 * @package kvm
 */
class PostType extends Singleton {

	protected $post_type = 'staff';

	protected $taxonomy = 'member-group';

	protected $post_types_having_author = [];

	protected $default_user = 0;

	protected $args = [];

	/**
	 * Constructor
	 */
	protected function init() {
		$po = sprintf( dirname( dirname( dirname( __DIR__ ) ) ) . '/languages/kvm-%s.mo', get_user_locale() );
		load_textdomain( 'kvm', $po );
		add_action( 'init', [ $this, 'register_post_type' ] );
	}

	/**
	 * Set settings.
	 *
	 * @param array $settings
	 * @param string $post_type
	 */
	protected function set_setting( $settings = [], $post_type = '' ) {
		if ( $post_type ) {
			$this->post_type = $post_type;
		}
		$this->args = wp_parse_args( $settings, [
			'label'  => __( 'Staff', 'kvm' ),
			'labels' => [
				'featured_image'        => __( 'Profile Picture', 'kvm' ),
				'set_featured_image'    => __( 'Set profile picture', 'kvm' ),
				'remove_featured_image' => __( 'Remove profile picture', 'kvm' ),
				'use_featured_image'    => __( 'Use as profile picture', 'kvm' ),
			],
			'public' => false,
			'show_ui' => true,
			'supports' => [ 'title', 'excerpt', 'editor', 'thumbnail', 'custom-fields' ],
			'show_in_rest' => true,
			'menu_icon' => 'dashicons-groups',
		] );
	}

	/**
	 * Register post type.
	 */
	public function register_post_type() {
		// Post type.
		register_post_type( $this->post_type, $this->args );
		// Taxonomy.
		$objects = apply_filters( 'virtual_member_taxonomy_applied_to', [ $this->post_type ] );
		$taxonomy_args = apply_filters( 'virtual_member_taxonomy_args', [
			'label' => __( 'Group', 'kvm' ),
			'show_in_rest' => true,
			'hierarchical' => true,
		] );
		register_taxonomy( $this->taxonomy, $objects, $taxonomy_args );
	}

	/**
	 * Regsiter this as post types.
	 *
	 * @param string[] $post_types
	 * @param int      $default_user_id
	 * @return static
	 *
	 */
	public function use_as_post_author( $post_types, $default_user_id = 0 ) {
		$this->post_types_having_author = $post_types;
		$this->default_user = (int) $default_user_id;
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post', [ $this, 'save_post' ], 10, 2 );
		return $this;
	}

	/**
	 * Render meta box.
	 *
	 * @param string $post_type
	 */
	public function add_meta_boxes( $post_type ) {
		if ( ! in_array( $post_type, $this->post_types_having_author ) ) {
			return;
		}
		$post_type_object = get_post_type_object( $this->post_type );
		add_meta_box( 'virtual-member-id', $post_type_object->label, function( \WP_Post $post ) {
			$post_type_object = get_post_type_object( $this->post_type );
			wp_nonce_field( 'virtual_member_as_author', '_vmnonce', false );
			$users = get_posts( [
				'post_type'      => $this->post_type,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			] );
			$current_id = (int) get_post_meta( $post->ID, '_virtual_author_id', true );
			?>
			<p>
				<select name="virtual-author-id" id="virtual-author-id">
					<option value="0" <?php selected( $current_id, 0 ) ?>><?php esc_html_e( 'Not specify', 'kvm' ) ?></option>
					<?php foreach ( $users as $user ) :
						$group = '';
						$terms = get_the_terms( $user, $this->taxonomy );
						if ( $terms && ! is_wp_error( $terms ) ) {
							$group = implode( ', ', array_map( function( $term ) {
								return $term->name;
							}, $terms ) );
						}
						?>
					<option value="<?php echo esc_attr( $user->ID ) ?>"<?php selected( $user->ID, $current_id ) ?>>
						<?php echo esc_html( sprintf( _x( '%1$s(%2$s)', 'user selector', 'kvm' ), get_the_title( $user ), $group ) ); ?>
					</option>
					<?php endforeach; ?>
				</select>
			</p>
			<p class="description">
				<?php printf( __( 'To change post author to %s, please specify.', 'kvm' ), $post_type_object->label ); ?>
			</p>
			<?php
		}, $post_type, 'side' );
	}

	/**
	 * Save post authors.
	 *
	 * @param int      $post_id
	 * @param \WP_Post $post
	 */
	public function save_post( $post_id, $post ) {
		if ( ! in_array( $post->post_type, $this->post_types_having_author ) ) {
			return;
		}
		if ( ! wp_verify_nonce( filter_input( INPUT_POST, '_vmnonce' ), 'virtual_member_as_author' ) ) {
			return;
		}
		$author_id = (int) filter_input( INPUT_POST, 'virtual-author-id' );
		if ( $author_id ) {
			update_post_meta( $post_id, '_virtual_author_id', $author_id );
		} else {
			delete_post_meta( $post_id, '_virtual_author_id' );
		}
	}

	/**
	 * Register staff.
	 *
	 * @param array $settings
	 * @param string $post_type
	 * @return static
	 */
	public static function register( $settings = [], $post_type = '' ) {
		$instance = static::get_instance();
		$instance->set_setting( $settings, $post_type );
		return $instance;
	}

	/**
	 * Get post type.
	 *
	 * @return string
	 */
	public static function post_type() {
		return static::get_instance()->post_type;
	}
}

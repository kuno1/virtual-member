<?php

namespace Kunoichi\VirtualMember;


use Kunoichi\VirtualMember\Pattern\Singleton;
use Kunoichi\VirtualMember\Ui\MemberEditor;
use Kunoichi\VirtualMember\Ui\PublicScreen;
use Kunoichi\VirtualMember\Ui\SettingScreen;

/*
 * Post type.
 *
 * @package kvm
 */
class PostType extends Singleton {

	private static $is_activated = false;

	protected $post_type = 'member';

	/**
	 * @var null|string[] Post types.
	 */
	protected $post_types_having_author = null;

	/**
	 * @var int|null User ID.
	 */
	protected $default_user = null;

	protected $args = [];

	/**
	 * Constructor
	 */
	protected function init() {
		if ( ! defined( 'KVM_AS_PLUGIN' ) ) {
			// This is not plugin. Needs original translation.
			$po = sprintf( dirname( dirname( dirname( __DIR__ ) ) ) . '/languages/kvm-%s.mo', get_user_locale() );
			load_textdomain( 'kvm', $po );
		}
		// Register controllers.
		SettingScreen::get_instance();
		MemberEditor::get_instance();
		PublicScreen::get_instance();
		// Register post type.
		add_action( 'init', [ $this, 'register_post_type' ] );
		self::$is_activated = true;
	}

	/**
	 * Set settings.
	 *
	 * @param array $settings
	 * @param string $post_type
	 */
	protected function set_setting( $settings = [], $post_type = '' ) {
		if ( $post_type ) {
			// Explicitly set, use it.
			$this->post_type = $post_type;
		} else {
			// Get from option.
			$this->post_type = get_option( 'kvm_post_type', '' ) ?: 'member';
		}
		$this->args = wp_parse_args( $settings, [
			'label'        => $this->get_post_type_label(),
			'labels'       => [
				'featured_image'        => __( 'Profile Picture', 'kvm' ),
				'set_featured_image'    => __( 'Set profile picture', 'kvm' ),
				'remove_featured_image' => __( 'Remove profile picture', 'kvm' ),
				'use_featured_image'    => __( 'Use as profile picture', 'kvm' ),
			],
			'public'       => self::virtual_member_is_public(),
			'show_ui'      => true,
			'supports'     => [ 'title', 'excerpt', 'editor', 'slug', 'thumbnail', 'custom-fields', 'page-attributes' ],
			'show_in_rest' => true,
			'rewrite'      => [
				'slug'       => $this->get_post_type_rewrite(),
				'with_front' => false,
			],
			'menu_icon'    => 'dashicons-groups',
		] );
	}

	/**
	 * Register post type.
	 */
	public function register_post_type() {
		// Post type.
		register_post_type( $this->post_type, apply_filters( 'kvm_post_type_args', $this->args ) );
		// Taxonomy.
		$objects       = apply_filters( 'virtual_member_taxonomy_applied_to', [ $this->post_type ] );
		$taxonomy_args = apply_filters( 'virtual_member_taxonomy_args', [
			'label'             => apply_filters( 'kvm_taxonomy_label', __( 'Group', 'kvm' ) ),
			'show_in_rest'      => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
		] );
		register_taxonomy( $this->taxonomy(), $objects, $taxonomy_args );
	}

	/**
	 * Override post types.
	 *
	 * @param string[] $post_types
	 * @param int      $default_user_id
	 * @return static
	 *
	 */
	public function use_as_post_author( $post_types, $default_user_id = 0 ) {
		$this->post_types_having_author = $post_types;
		$this->default_user             = (int) $default_user_id;
		return $this;
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
	 * Detect if this is active.
	 *
	 * @return bool
	 */
	public static function is_active() {
		return self::$is_activated;
	}

	/**
	 * Get post type.
	 *
	 * @return string
	 */
	public static function post_type() {
		return static::get_instance()->post_type;
	}

	/**
	 * Get available post types.
	 *
	 * @return string[]
	 */
	public static function available_post_types() {
		$instance = self::get_instance();
		if ( is_null( $instance->post_types_having_author ) ) {
			$instance->post_types_having_author = get_option( 'kvm_available_post_types', [] );
		}
		return $instance->post_types_having_author;
	}

	/**
	 * Default user.
	 *
	 * @return int
	 */
	public static function default_user() {
		$instance = self::get_instance();
		if ( is_null( $instance->default_user ) ) {
			$instance->default_user = (int) get_option( 'kvm_default_user', 0 );
		}
		return $instance->default_user;
	}

	/**
	 * Get post type label.
	 *
	 * @return string
	 */
	protected function get_post_type_label() {
		return get_option( 'kvm_post_type_label' ) ?: __( 'Member', 'kvm' );
	}

	/**
	 * Detect if virual member is public.
	 *
	 * @return bool
	 */
	public function virtual_member_is_public() {
		return (bool) get_option( 'kvm_post_type_is_public' );
	}

	/**
	 * Get post type rewrite.
	 *
	 * @return string
	 */
	public function get_post_type_rewrite() {
		return get_option( 'kvm_post_type_prefix' ) ?: 'member';
	}

	/**
	 * Taxonomy name.
	 *
	 * @return string
	 */
	public function taxonomy() {
		return apply_filters( 'kvm_taxonomy', 'member-group' );
	}
}

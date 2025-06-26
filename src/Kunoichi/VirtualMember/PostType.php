<?php

namespace Kunoichi\VirtualMember;


use Kunoichi\VirtualMember\Helpers\PerformAs;
use Kunoichi\VirtualMember\Helpers\Rewrite;
use Kunoichi\VirtualMember\Pattern\Singleton;
use Kunoichi\VirtualMember\Rest\PostAuthorsApi;
use Kunoichi\VirtualMember\Rest\SearchAuthorsApi;
use Kunoichi\VirtualMember\Services\StructuredDataProvider;
use Kunoichi\VirtualMember\Ui\AdminTables;
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
		// Register translations.
		$this->load_textdomain();
		// Register controllers.
		SettingScreen::get_instance();
		MemberEditor::get_instance();
		AdminTables::get_instance();
		if ( ! is_admin() ) {
			PublicScreen::get_instance();
		}
		StructuredDataProvider::get_instance();
		// Rewrite rules.
		Rewrite::get_instance();
		// User Helpers.
		PerformAs::get_instance();
		// REST API
		PostAuthorsApi::get_instance();
		SearchAuthorsApi::get_instance();
		// Register post type.
		add_action( 'init', [ $this, 'register_post_type' ], 11 );
		// Register assets.
		add_action( 'init', [ $this, 'register_assets' ], 12 );
		self::$is_activated = true;
	}

	/**
	 * Load text domain.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		$basedir = dirname( __DIR__, 3 );
		if ( ! defined( 'KVM_AS_PLUGIN' ) ) {
			// This is not a plugin. Needs original translation.
			$po = sprintf( $basedir . '/languages/kvm-%s.mo', get_user_locale() );
			load_textdomain( 'kvm', $po );
		} else {
			load_plugin_textdomain( 'kvm', false, basename( $basedir ) . '/languages' );
		}
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
	 * Register assets.
	 *
	 * @return void
	 */
	public function register_assets() {
		$base = dirname( __DIR__, 3 );
		$path = $base . '/wp-dependencies.json';
		if ( ! file_exists( $path ) ) {
			trigger_error( 'wp-dependencies.json not found.', E_USER_WARNING );
		}
		$dependencies = json_decode( file_get_contents( $path ), true );
		if ( ! $dependencies ) {
			trigger_error( 'wp-dependencies.json is invalid.', E_USER_WARNING );
		}
		if ( str_contains( $base, WP_PLUGIN_DIR ) || str_contains( $base, WPMU_PLUGIN_DIR ) ) {
			// This is plugin.
			$url = plugin_dir_url( $base . '/assets' );
		} elseif ( str_contains( $base, get_theme_root() ) ) {
			// This is theme.
			$url = str_replace( get_theme_root(), get_theme_root_uri(), $base );
		} else {
			$url = str_replace( ABSPATH, network_home_url( '/' ), $base );
		}
		foreach ( $dependencies as $dep ) {
			if ( empty( $dep['path'] ) ) {
				continue;
			}
			$src = trailingslashit( $url ) . $dep['path'];
			switch ( $dep['ext'] ) {
				case 'js':
					wp_register_script( $dep['handle'], $src, $dep['deps'], $dep['hash'], [
						'in_footer' => true,
					] );
					if ( in_array( 'wp-i18n', $dep['deps'], true ) ) {
						wp_set_script_translations( $dep['handle'], 'kvm', $base . '/languages' );
					}
					break;
				case 'css':
					wp_register_style( $dep['handle'], $src, $dep['deps'], $dep['hash'], 'screen' );
					break;
			}
		}
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
		add_action( 'init', function () use ( $instance, $settings, $post_type ) {
			$instance->set_setting( $settings, $post_type );
		}, 10 );
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
	 * Get the post-type.
	 *
	 * @return string
	 */
	public static function post_type() {
		return static::get_instance()->post_type;
	}

	/**
	 * Get available post-types.
	 *
	 * @return string[]
	 */
	public static function available_post_types() {
		$instance = self::get_instance();
		if ( is_null( $instance->post_types_having_author ) ) {
			$instance->post_types_having_author = array_values( array_filter( (array) get_option( 'kvm_available_post_types', [] ) ) );
		}
		return (array) $instance->post_types_having_author;
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
	public function get_post_type_label() {
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

	/**
	 * Archive URL of this memer's posts.
	 *
	 * @param null|int|\WP_Post $post  Post type object.
	 * @param int               $paged Page number.
	 *
	 * @return string|null
	 */
	public static function archive_url( $post = null, $paged = 1 ) {
		$post = get_post( $post );
		if ( ! $post || self::post_type() !== $post->post_type ) {
			return '';
		}
		if ( ! is_post_type_viewable( $post->post_type ) ) {
			return '';
		}
		$paged = max( 1, absint( $paged ) );
		if ( 1 < $paged ) {
			$url_suffix = sprintf( '%d/page/%d', $post->ID, $paged );
		} else {
			$url_suffix = sprintf( '%d', $post->ID );
		}
		return home_url( sprintf( '/%s/%s', self::get_instance()->get_post_type_rewrite(), $url_suffix ) );
	}

	/**
	 * Is specified post is an organization.
	 *
	 *
	 * @param null|int|\WP_Post $post Writer object.
	 * @return bool
	 */
	public static function is_organization( $post = null ) {
		$post = get_post( $post );
		if ( ! $post || self::post_type() !== $post->post_type ) {
			return false;
		}
		return (bool) get_post_meta( $post->ID, '_is_organization', true );
	}

	/**
	 * Is specified post represents whole site.
	 *
	 *
	 * @param null|int|\WP_Post $post Writer object.
	 * @return bool
	 */
	public static function is_representative( $post = null ) {
		$post = get_post( $post );
		if ( ! $post || self::post_type() !== $post->post_type ) {
			return false;
		}
		return (bool) get_post_meta( $post->ID, '_is_representative', true );
	}

	/**
	 * Get post count for the member.
	 *
	 * @param null|int|\WP_Post $member Member object or ID.
	 * @param string[]          $post_type Post type names. If empty, use all post types.
	 * @return int
	 */
	public static function post_count( $member = null, $post_type = [] ) {
		$member = get_post( $member );
		if ( ! $member || self::post_type() !== $member->post_type ) {
			return 0;
		}
		if ( empty( $post_type ) ) {
			$post_type = self::available_post_types();
		}
		if ( ! is_array( $post_type ) ) {
			$post_type = [ $post_type ];
		}
		$query = new \WP_Query( [
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_query'     => [
				[
					'key'     => '_virtual_author_id',
					'value'   => $member->ID,
					'compare' => '=',
				],
			],
		] );
		return $query->found_posts;
	}
}

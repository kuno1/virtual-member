<?php

namespace Kunoichi\VirtualMember\Utility;


use Kunoichi\VirtualMember\PostType;

/**
 * Utility functions.
 *
 * @package kvm
 */
trait CommonMethods {

	/**
	 * Get plugin version.
	 *
	 * @return string
	 */
	public function version() {
		static $version = null;
		if ( is_null( $version ) ) {
			$data    = get_file_data( $this->plugin_dir() . '/virtual-mmeber.php', [
				'version' => 'Version',
			] );
			$version = $data['version'];
		}
		return apply_filters( 'kvm_version', $version );
	}

	/**
	 * Detect if this is loaded as plugin.
	 *
	 * @return bool
	 */
	public function as_plugin() {
		return defined( 'KVM_AS_PLUGIN' ) && KVM_AS_PLUGIN;
	}

	/**
	 * Get root directory.
	 *
	 * @return string
	 */
	public function plugin_dir() {
		return dirname( dirname( dirname( dirname( __DIR__ ) ) ) );
	}

	/**
	 * Get root URL.
	 *
	 * @return string.
	 */
	public function plugin_url() {
		$dir        = $this->plugin_dir();
		$theme_root = get_theme_root();
		if ( $this->as_plugin() || false !== strpos( $dir, WP_PLUGIN_DIR ) ) {
			// In plugin dir.
			return plugin_dir_url( $dir . '/example.php' );
		} elseif ( false !== strpos( $dir, WPMU_PLUGIN_DIR ) ) {
			// AS MU plugin.
			return str_replace( WPMU_PLUGIN_DIR, WPMU_PLUGIN_URL, $dir );
		} elseif ( false !== strpos( $dir, $theme_root ) ) {
			// This is inside themes.
			return str_replace( $theme_root, get_theme_root_uri(), $dir );
		} else {
			// Otherwise, anywhere in root.
			return str_replace( ABSPATH, home_url( '/' ), $dir );
		}
	}

	/**
	 * Meta key.
	 *
	 * @return string
	 */
	protected function meta_key() {
		return '_virtual_author_id';
	}

	/**
	 * Get post type.
	 *
	 * @return string
	 */
	protected function post_type() {
		return PostType::post_type();
	}

	/**
	 * Taxonomy name.
	 *
	 * @return string
	 */
	protected function taxonomy() {
		return PostType::get_instance()->taxonomy();
	}

	/**
	 * Get registered users.
	 *
	 * @param string $context Context.
	 * @return \WP_Post[]
	 */
	public function users( $context = '' ) {
		$args  = apply_filters( 'kvm_users', [
			'post_type'           => $this->post_type(),
			'post_status'         => 'publish',
			'posts_per_page'      => -1,
			'order'               => 'ASC',
			'orderby'             => 'title',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		], $context );
		$query = new \WP_Query( $args );
		return $query->posts;
	}

	/**
	 * Does the post type use member?
	 *
	 * @param string $post_type Post type name.
	 *
	 * @return bool
	 */
	protected function use_member( $post_type ) {
		return in_array( $post_type, PostType::available_post_types(), true );
	}

	/**
	 * Get author.
	 *
	 * @param null|int|\WP_Post $post
	 *
	 * @return \WP_Post|null
	 */
	protected function get_member( $post = null ) {
		$post = get_post( $post );
		if ( ! $post || ! $this->use_member( $post->post_type ) ) {
			return null;
		}
		$author_id = get_post_meta( $post->ID, $this->meta_key(), true );
		if ( $author_id ) {
			$author_query = new \WP_Query( [
				'post_type'      => $this->post_type(),
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'p'              => $author_id,
			] );
			if ( $author_query->have_posts() ) {
				return $author_query->posts[0];
			}
		}
		// Author not found. If default is set, get.
		$default_author = PostType::default_user();
		if ( ! $default_author ) {
			return null;
		}
		$author = get_post( $default_author );
		if ( $author && $this->post_type() === $author->post_type && 'publish' === $author->post_status ) {
			return $author;
		}
		return null;
	}

	/**
	 * Get post type archive.
	 *
	 * @param int|null|\WP_Post $post Post object.
	 * @return string
	 */
	protected function get_author_archive( $post = null ) {
		$member = $this->get_member( $post );
		if ( ! $member ) {
			return '';
		}
		if ( get_post_type_object( $member->post_type )->public ) {
			return get_permalink( $member );
		} elseif ( get_option( 'rewrite_rules' ) ) {
			// Using permalink.
			return home_url( sprintf( '/%s/%d', PostType::get_instance()->get_post_type_rewrite(), $member->ID ) );
		} else {
			return add_query_arg( [
				'kvm_id' => $member->ID,
			], home_url() );
		}
	}
}

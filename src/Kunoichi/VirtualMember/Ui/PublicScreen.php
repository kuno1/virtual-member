<?php

namespace Kunoichi\VirtualMember\Ui;


use Kunoichi\VirtualMember\Pattern\Singleton;
use Kunoichi\VirtualMember\PostType;
use Kunoichi\VirtualMember\Utility\CommonMethods;

/**
 * Handle public display.
 *
 * @package kvm
 */
class PublicScreen extends Singleton {

	use CommonMethods;

	/**
	 * @inheritDoc
	 */
	protected function init() {
		add_filter( 'the_author', [ $this, 'hook_the_author' ] );
		add_filter( 'author_link', [ $this, 'hook_author_archive' ], 10, 3 );
		// Add query vars.
		add_filter( 'query_vars', function( $vars ) {
			$vars[] = 'kvm_id';
			return $vars;
		} );
		// Rewrite rules.
		add_filter( 'rewrite_rules_array', [ $this, 'rewrite_rules' ] );
		// Hook wp_query
		add_action( 'pre_get_posts', [ $this, 'hook_wp_query' ] );
		// Avatar.
		add_filter( 'get_avatar_data', [ $this, 'hook_avatar' ], 10, 2 );
		// Meta data.
		add_filter( 'get_the_author_description', [ $this, 'override_description' ], 10, 2 );
		add_filter( 'get_the_author_user_url', [ $this, 'override_user_url' ], 10, 2 );
		add_filter( 'get_the_author_display_name', [ $this, 'override_display_name' ], 10, 2 );
	}

	/**
	 * Author name.
	 *
	 * @param string $author Author name.
	 * @return string|null
	 */
	public function hook_the_author( $author ) {
		$member = $this->get_member();
		if ( ! $member ) {
			return $author;
		}
		return get_the_title( $member );
	}

	/**
	 * Filter author name.
	 *
	 * @param string $link
	 * @param int    $author_id
	 * @param string $author_nicename
	 * @return string
	 */
	public function hook_author_archive( $link, $author_id, $author_nicename ) {
		if ( (int) get_post()->post_author !== $author_id ) {
			// This is not in loop.
			return $link;
		}
		$author_archive = $this->get_author_archive();
		if ( $author_archive ) {
			$link = $author_archive;
		}
		return $link;
	}

	/**
	 * Add rewrite rules.
	 *
	 * @param string[] $rules Rewrite rules.
	 * @return string[]
	 */
	public function rewrite_rules( $rules ) {
		$prefix    = PostType::get_instance()->get_post_type_rewrite();
		$new_rules = apply_filters( 'kvm_rewrite_rules', [
			'^' . $prefix . '/(\d+)/page/(\d+)?$' => 'index.php?kvm_id=$matches[1]&paged=$matches[2]',
			'^' . $prefix . '/(\d+)/?$'           => 'index.php?kvm_id=$matches[1]',
		] );
		return array_merge( $new_rules, $rules );
	}

	/**
	 * Hook wp_query.
	 *
	 * @param \WP_Query $wp_query WP Query.
	 */
	public function hook_wp_query( &$wp_query ) {
		$kvm_id = $wp_query->get( 'kvm_id' );
		if ( ! $kvm_id ) {
			return;
		}
		if ( ! $wp_query->get( 'post_type' ) ) {
			$wp_query->set( 'post_type', PostType::available_post_types() );
		}
		$meta_query = [
			'key'   => $this->meta_key(),
			'value' => $kvm_id,
		];
		if ( $wp_query->get( 'meta_query' ) ) {
			$wp_query->query_vars['meta_query'][] = $meta_query;
		} else {
			$wp_query->set( 'meta_query', [ $meta_query ] );
		}
		$wp_query->set( 'ignore_sticky_posts', true );
	}

	/**
	 * Override avatar.
	 *
	 * @param array $args
	 * @param int|string|\WP_User|\WP_Post|\WP_Comment $id_or_email
	 * @return array
	 */
	public function hook_avatar( $args, $id_or_email ) {
		if ( is_a( $id_or_email, 'WP_Comment' ) ) {
			$post    = get_post( $id_or_email->comment_post_ID );
			$user_id = $id_or_email->user_id ?: email_exists( $id_or_email->comment_author_email );
		} elseif ( is_a( $id_or_email, 'WP_Post' ) ) {
			$post    = $id_or_email;
			$user_id = $post->post_author;
		} elseif ( is_numeric( $id_or_email ) ) {
			$user_id = $id_or_email;
			$post    = get_post();
		} elseif ( is_email( $id_or_email ) ) {
			$user_id = email_exists( $id_or_email );
			$post    = get_post();
		} else {
			return $args;
		}
		$user_id = (int) $user_id;
		if ( ! $user_id || ! $post ) {
			// No user found.
			return $args;
		}
		if ( $user_id !== (int) $post->post_author ) {
			// Not the author.
			return $args;
		}
		if ( ! $this->use_member( $post->post_type ) ) {
			return $args;
		}
		$member = $this->get_member( $post );
		if ( ! $member ) {
			return $args;
		}

		// This should be the author's avavar.
		if ( has_post_thumbnail( $member ) ) {
			// Override avatar.
			$args['url'] = get_the_post_thumbnail_url( $member, [ $args['width'], $args['height'] ] );
		}
		$args['alt'] = get_the_title( $member );
		return $args;
	}

	/**
	 * Override description with excerpt.
	 *
	 * @param string  $value   Value.
	 * @param int     $user_id User ID.
	 * @return string
	 */
	public function override_description( $value, $user_id ) {
		$member = $this->get_member_in_loop( $user_id );
		if ( $member ) {
			$value = $member->post_excerpt;
		}
		return $value;
	}

	/**
	 * Override Author display name.
	 *
	 * @param string  $value   Value.
	 * @param int     $user_id User ID.
	 * @return string
	 */
	public function override_display_name( $value, $user_id ) {
		$member = $this->get_member_in_loop( $user_id );
		if ( $member ) {
			$value = get_the_title( $member );
		}
		return $value;
	}

	/**
	 * Override author url.
	 *
	 * @param string  $value   Value.
	 * @param int     $user_id User ID.
	 * @return string
	 */
	public function override_user_url( $value, $user_id ) {
		$member = $this->get_member_in_loop( $user_id );
		if ( $member ) {
			$value = get_post_type_object( $member->post_type )->public ? get_permalink( $member ) : '';
		}
		return $value;
	}

	/**
	 * If user id is author of current post, return member if exists.
	 *
	 * @param int               $user_id User ID.
	 * @param \WP_Post|int|null $post    Default, current post.
	 * @return \WP_Post|null
	 */
	protected function get_member_in_loop( $user_id, $post = null ) {
		$post = get_post( $post );
		if ( ! $post || ! $this->use_member( $post->post_type ) || (int) $user_id !== (int) $post->post_author ) {
			// This is not post author.
			return null;
		}
		return $this->get_member( $post );
	}
}

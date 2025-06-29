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
		// Avatar.
		add_filter( 'get_avatar_data', [ $this, 'hook_avatar' ], 10, 2 );
		// Meta data.
		add_filter( 'get_the_author_description', [ $this, 'override_description' ], 10, 2 );
		add_filter( 'get_the_author_user_url', [ $this, 'override_user_url' ], 10, 2 );
		add_filter( 'get_the_author_display_name', [ $this, 'override_display_name' ], 10, 2 );
		// Add filter for meta data.
		add_action( 'template_redirect', function () {
			foreach ( wp_get_user_contact_methods() as $key => $label ) {
				add_filter( 'get_the_author_' . $key, [ $this, 'override_contact_method_' . $key ], 10, 3 );
			}
		} );
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
		if ( is_admin() || ! in_the_loop() ) {
			return $link;
		}
		$post = get_post();
		if ( ! $post ) {
			return $link;
		}
		if ( (int) $post->post_author !== $author_id ) {
			// This is not in loop.
			return $link;
		}
		$author_archive = $this->get_author_archive( $post );
		if ( $author_archive ) {
			$link = $author_archive;
		}
		return $link;
	}

	/**
	 * Override avatar.
	 *
	 * @param array $args
	 * @param int|string|\WP_User|\WP_Post|\WP_Comment $id_or_email
	 * @return array
	 */
	public function hook_avatar( $args, $id_or_email ) {
		if ( is_admin_bar_showing() && 0 < did_action( 'admin_bar_menu' ) && 0 === did_action( 'wp_after_admin_bar_render' ) ) {
			return $args; // Do not override avatar in admin bar.
		}
		if ( is_a( $id_or_email, 'WP_User' ) ) {
			$user_id = $id_or_email->user_id;
			$post    = get_post();
		} elseif ( is_a( $id_or_email, 'WP_Comment' ) ) {
			$post    = get_post( $id_or_email->comment_post_ID );
			$user_id = $id_or_email->user_id ?: email_exists( $id_or_email->comment_author_email );
		} elseif ( is_a( $id_or_email, 'WP_Post' ) ) {
			$post    = $id_or_email;
			$user_id = $post->post_author;
		} elseif ( is_numeric( $id_or_email ) ) {
			$user_id = $id_or_email;
			$post    = get_post();
		} elseif ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
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
			$value      = get_post_type_object( $member->post_type )->public ? get_permalink( $member ) : '';
			$meta_value = get_post_meta( $member->ID, 'user_url', true );
			if ( $meta_value ) {
				$value = $meta_value;
			}
		}
		return $value;
	}

	/**
	 * If user id is author of current post, return member if exists.
	 *
	 * @param int|\WP_User      $user_id User ID.
	 * @param \WP_Post|int|null $post    Default, current post.
	 * @return \WP_Post|null
	 */
	protected function get_member_in_loop( $user_id, $post = null ) {
		$post = get_post( $post );
		if ( is_a( $user_id, 'WP_User' ) ) {
			$user_id = $user_id->ID;
		} else {
			$user_id = (int) $user_id;
		}
		if ( ! $post || ! $this->use_member( $post->post_type ) || $user_id !== (int) $post->post_author ) {
			// This is not post author.
			return null;
		}
		return $this->get_member( $post );
	}

	/**
	 * A caller.
	 *
	 * @param string $name      Function name.
	 * @param array  $arguments Function arguments.
	 *
	 * @return mixed
	 */
	public function __call( $name, $arguments ) {
		if ( preg_match( '/override_contact_method_(.*)/', $name, $matches ) ) {
			list( $match, $key )                        = $matches;
			list( $value, $user_id, $original_user_id ) = $arguments;
			return $this->override_contact_method( $value, $key, $user_id );
		} else {
			return null;
		}
	}

	/**
	 * Call user method.
	 *
	 * @param string $value   Value.
	 * @param string $key     Meta key name.
	 * @param int    $user_id User ID.
	 *
	 * @return string
	 */
	public function override_contact_method( $value, $key, $user_id ) {
		// Is virtual member?
		$member = $this->get_member_in_loop( $user_id );
		if ( ! $member ) {
			return $value;
		}
		// Try to get meta value.
		$meta_value = get_post_meta( $member->ID, $key, true );
		if ( $meta_value ) {
			$value = $meta_value;
		}
		return $value;
	}
}

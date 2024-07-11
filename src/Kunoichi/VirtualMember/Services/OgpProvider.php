<?php

namespace Kunoichi\VirtualMember\Services;


use Kunoichi\VirtualMember\Pattern\Singleton;
use Kunoichi\VirtualMember\PostType;
use Kunoichi\VirtualMember\Ui\PublicScreen;

/**
 * OGP provider
 *
 *
 */
class OgpProvider extends Singleton {

	/**
	 * Display OGP meta tags on writer single page.
	 *
	 * @return void
	 */
	protected function init() {
		add_action( 'wp_head', [ $this, 'display_ogp' ] );
	}

	/**
	 * Display OGP.
	 *
	 * @return void
	 */
	public function display_ogp() {
		if ( ! is_singular( PostType::post_type() ) ) {
			return;
		}
		$post = get_queried_object();
		if ( ! $post ) {
			return;
		}
		$profile = $this->get_ogp( $post );
		if ( ! $profile ) {
			return;
		}
		$ogp = [
			'@context'     => 'https://schema.org',
			'@type'        => 'ProfilePage',
			'dateCreated'  => mysql2date( \DateTime::ATOM, $post->post_date ),
			'dateModified' => mysql2date( \DateTime::ATOM, $post->post_modified ),
			'mainEntity'   => $profile,
		];
		$ogp = json_encode( $ogp, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
		printf( "<script type=\"application/ld+json\">\n%s</script>\n", $ogp );
	}

	/**
	 * Get author object.
	 *
	 * @param null|int|\WP_Post $post Post object.
	 *
	 * @return array
	 */
	public function get_ogp( $post = null ) {
		$post = get_post( $post );
		if ( ! $post || PostType::post_type() !== $post->post_type ) {
			return [];
		}
		$is_organization = PostType::is_organization( $post );
		$representing    = PostType::is_representative( $post );
		$ogp             = [
			'@context'   => 'http://schema.org/',
			'@type'      => $is_organization ? 'Organization' : 'Person',
			'name'       => get_the_title( $post ),
			'url'        => $representing ? get_bloginfo( 'url' ) : get_permalink( $post ),
			'identifier' => get_permalink( $post ),
		];
		if ( has_post_thumbnail( $post ) ) {
			$key         = $is_organization ? 'logo' : 'image';
			$ogp[ $key ] = get_the_post_thumbnail_url( $post, 'full' );
		}
		$positions = get_the_terms( $post, PostType::get_instance()->taxonomy() );
		if ( $positions && ! is_wp_error( $positions ) && ! $is_organization ) {
			$ogp[ 'jobTitle' ] = $positions[ 0 ]->name;
		}
		if ( has_excerpt( $post ) ) {
			$ogp[ 'description' ] = wp_strip_all_tags( $post->post_excerpt );
		}
		$urls = [];
		foreach ( PublicScreen::get_instance()->custom_contact_methods() as $key => $label ) {
			$meta_value = get_post_meta( $post->ID, $key, true );
			if ( $meta_value && preg_match( '#^https?://#u', $meta_value ) ) {
				$urls[] = $meta_value;
			}
		}
		if ( ! empty( $urls ) ) {
			$ogp[ 'sameAs' ] = $urls;
		}

		return $ogp;
	}
}

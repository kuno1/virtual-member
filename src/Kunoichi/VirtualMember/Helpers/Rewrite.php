<?php

namespace Kunoichi\VirtualMember\Helpers;

use Kunoichi\VirtualMember\Pattern\Singleton;
use Kunoichi\VirtualMember\PostType;
use Kunoichi\VirtualMember\Utility\CommonMethods;

/**
 * Rewriter rules handler.
 */
class Rewrite extends Singleton {

	use CommonMethods;

	/**
	 * {@inheritDoc}
	 */
	protected function init() {
		// Add query vars.
		add_filter( 'query_vars', [ $this, 'query_vars' ] );
		// Rewrite rules.
		add_filter( 'rewrite_rules_array', [ $this, 'rewrite_rules' ] );
		// Hook wp_query
		add_action( 'pre_get_posts', [ $this, 'hook_wp_query' ] );
	}

	/**
	 * Get meta key for member.
	 *
	 * @return string
	 */
	public function query_vars( $vars ) {
		$vars[] = 'kvm_id';
		return $vars;
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
		$default_user = PostType::default_user();
		if ( (string) $kvm_id === (string) $default_user ) {
			// This is default user. should include all.
			$meta_query = [
				'relation' => 'OR',
				[
					'key'     => $this->meta_key(),
					'value'   => [ $kvm_id, '', '0' ],
					'compare' => 'IN',
				],
				[
					'key'     => $this->meta_key(),
					'compare' => 'NOT EXISTS',
				],
			];
		} else {
			$meta_query = [
				'key'   => $this->meta_key(),
				'value' => $kvm_id,
			];
		}
		if ( $wp_query->get( 'meta_query' ) ) {
			$wp_query->query_vars['meta_query'][] = $meta_query;
		} else {
			$wp_query->set( 'meta_query', [ $meta_query ] );
		}
		$wp_query->set( 'ignore_sticky_posts', true );
	}
}

<?php

namespace Kunoichi\VirtualMember\Ui;


use Kunoichi\VirtualMember\Helpers\PerformAs;
use Kunoichi\VirtualMember\Pattern\Singleton;
use Kunoichi\VirtualMember\PostType;
use Kunoichi\VirtualMember\Utility\CommonMethods;

/**
 * Admin tables for member management.
 *
 *
 */
class AdminTables extends Singleton {

	use CommonMethods;

	/**
	 * {@inheritDoc}
	 */
	protected function init() {
		// Add columns to admin list.
		add_action( 'admin_init', [ $this, 'register_admin_columns' ] );
	}

	/**
	 * Register admin columns for member list.
	 *
	 * @return void
	 */
	public function register_admin_columns() {
		// Add custom columns to the member list table.
		add_filter( 'manage_' . PostType::post_type() . '_posts_columns', [ $this, 'add_custom_columns' ] );
		add_action( 'manage_' . PostType::post_type() . '_posts_custom_column', [ $this, 'render_custom_columns' ], 10, 2 );
		add_filter( 'post_row_actions', [ $this, 'member_row_actions' ], 10, 2 );
		// Add custom columns to posts.
		foreach ( PostType::available_post_types() as $post_type ) {
			add_filter( 'manage_' . $post_type . '_posts_columns', [ $this, 'add_member_columns' ] );
			add_action( 'manage_' . $post_type . '_posts_custom_column', [ $this, 'render_member_columns' ], 10, 2 );
		}
	}

	/**
	 * Add custom columns in the member list table.
	 *
	 * @param string[] $columns Column names.
	 * @return string[]
	 */
	public function add_custom_columns( $columns ) {
		$new_columns = [];
		foreach ( $columns as $column => $label ) {
			if ( 'date' === $column ) {
				// Add a custom column for the member's default user.
				$new_columns['kvm_perform_as']  = __( 'Used By', 'kvm' );
				$new_columns['kvm_assigned_to'] = __( 'Post Count', 'kvm' );
			}
			$new_columns[ $column ] = $label;
		}
		return $new_columns;
	}

	/**
	 * Render custom columns in the member list table.
	 *
	 * @param string $column
	 * @param int $post_id
	 *
	 * @return void
	 */
	public function render_custom_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'kvm_perform_as':
				$users = PerformAs::get_users( $post_id );
				if ( ! empty( $users ) ) {
					echo implode( ', ', array_map( function ( $user ) {
						// Get user display name.
						return esc_html( $user->display_name );
					}, $users ) );
				} else {
					echo '---';
				}
				break;
			case 'kvm_assigned_to':
				echo esc_html( PostType::post_count( $post_id ) );
				break;
			default:
				// For other columns, do nothing.
				break;
		}
	}

	/**
	 * Add member column in the post list table.
	 *
	 * @param string[] $columns Column names.
	 * @return string[]
	 */
	public function add_member_columns( $columns ) {
		$new_columns = [];
		foreach ( $columns as $column => $label ) {
			$new_columns[ $column ] = $label;
			if ( 'title' === $column ) {
				$new_columns['kvm_member'] = get_post_type_object( PostType::post_type() )->label;
			}
		}
		return $new_columns;
	}

	/**
	 * Row actioss for member post type.
	 *
	 * @param array $acitons
	 * @param \WP_Post $post
	 *
	 * @return array
	 */
	public function member_row_actions( $actions, $post ) {
		if ( PostType::post_type() !== $post->post_type ) {
			return $actions;
		}
		if ( ! is_post_type_viewable( $post->post_type ) ) {
			return $actions;
		}
		// Add a link to the member's archive.
		$actions['kvm-archive'] = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( PostType::archive_url( $post ) ),
			esc_html__( 'View Archive', 'kvm' )
		);
		return $actions;
	}

	/**
	 * Render custom columns in the post list table.
	 *
	 * @param string $column
	 * @param int $post_id
	 *
	 * @return void
	 */
	public function render_member_columns( $column, $post_id ) {
		if ( 'kvm_member' !== $column ) {
			// Only handle the member column.
			return;
		}
		$members = $this->get_members( $post_id );
		if ( empty( $members ) ) {
			echo '---';
			return;
		}
		$edit_screen       = admin_url( 'edit.php' );
		$current_post_type = get_post_type( $post_id );
		if ( 'post' !== $current_post_type ) {
			$edit_screen = add_query_arg( [
				'post_type' => $current_post_type,
			], $edit_screen );
		}
		echo implode( ', ', array_map( function ( $member ) use ( $edit_screen ) {
			// Display member name with a link to the member's archive.
			return sprintf(
				'<a href="%s">%s</a>',
				esc_url( add_query_arg( [ 'kvm_id' => $member->ID ], $edit_screen ) ),
				esc_html( get_the_title( $member ) )
			);
		}, $members ) );
	}
}

<?php

namespace Kunoichi\VirtualMember\Helpers;


use Kunoichi\VirtualMember\Pattern\Singleton;
use Kunoichi\VirtualMember\PostType;

/**
 * Perform as virtual member.
 *
 *
 */
class PerformAs extends Singleton {

	/**
	 * @inheritDoc
	 */
	protected function init() {
		// Add profile screen.
		add_action( 'show_user_profile', [ $this, 'profile_field' ] );
		add_action( 'edit_user_profile', [ $this, 'profile_field' ] );
		// Save profile field.
		add_action( 'personal_options_update', [ $this, 'save_profile_field' ] );
		add_action( 'edit_user_profile_update', [ $this, 'save_profile_field' ] );
		// Add column to member list.
		add_action( 'admin_init', [ $this, 'add_columns_to_admin_list' ] );
	}

	/**
	 * Save user profile field.
	 *
	 * @param \WP_User $user User.
	 */
	public function profile_field( $user ) {
		if ( ! $user->has_cap( 'edit_posts' ) ) {
			return;
		}
		// Get all virtual members.
		$members = get_posts([
			'post_type'      => PostType::post_type(),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'post__not_in'   => [ PostType::default_user() ],
		]);
		$selected = array_map( 'intval', array_filter( (array) get_user_meta( $user->ID, '_kvm_perform_as' ) ) );
		wp_nonce_field( 'kvm_perform_as_setting', '_kvm_perform_as_nonce', false );
		?>
		<h2><?php esc_html_e( 'Post Setting', 'kvm' ); ?></h2>
		<table class="form-table">
			<tr>
				<th>
					<?php printf( esc_html__( 'Default %s', 'kvm' ), esc_html( get_post_type_object( PostType::post_type() )->label ) ); ?>
				</th>
				<td>
					<?php foreach ( $members as $member ) : ?>
						<label>
							<input type="checkbox" name="kvm_default_member[]" value="<?php echo esc_attr( $member->ID ); ?>" <?php checked( in_array( $member->ID, $selected, true ) ); ?> />
							<?php echo esc_html( get_the_title( $member ) ); ?>
						</label>
					<?php endforeach; ?>
					<p class="description">
						<?php printf( esc_html__( 'This will be default value when %s makes a new post.', 'kvm' ), esc_html( $user->display_name ) ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save profile field for user.
	 *
	 * @param int $user_id
	 * @return void
	 */
	public function save_profile_field( $user_id ) {
		if ( ! wp_verify_nonce( filteR_input( INPUT_POST, '_kvm_perform_as_nonce' ), 'kvm_perform_as_setting' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}
		// Remove all.
		delete_user_meta( $user_id, '_kvm_perform_as' );
		// Then add new.
		if ( ! empty( $_POST['kvm_default_member'] ) && is_array( $_POST['kvm_default_member'] ) ) {
			// Sanitize and save.
			$members = array_map( 'intval', array_filter( $_POST['kvm_default_member'] ) );
			foreach ( $members as $member_id ) {
				add_user_meta( $user_id, '_kvm_perform_as', $member_id );
			}
		}
	}

	/**
	 * Get default member ID for the user.
	 *
	 * @param int|null $user_id If null, use current user ID.
	 * @return int[]
	 */
	public static function members( $user_id = null ) {
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		if ( ! $user_id ) {
			return [];
		}
		return array_map( 'intval', array_filter( get_user_meta( $user_id, '_kvm_perform_as' ) ) );
	}

	/**
	 * Add columns to admin list.
	 *
	 * @return void
	 */
	public function add_columns_to_admin_list() {
		add_filter( 'manage_' . PostType::post_type() . '_posts_columns', [ $this, 'add_column' ] );
		add_action( 'manage_' . PostType::post_type() . '_posts_custom_column', [ $this, 'render_column' ], 10, 2 );
	}

	/**
	 * Add column to member list.
	 *
	 * @param array $columns Column names.
	 * @return array
	 */
	public function add_column( $columns ) {
		$new_columns = [];
		foreach ( $columns as $key => $value ) {
			if ( 'date' === $key ) {
				$new_columns['kvm_perform_as'] = __( 'Used By', 'kvm' );
			}
			$new_columns[ $key ] = $value;
		}
		return $new_columns;
	}

	/**
	 * List users who use this member as post author.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 *
	 * @return void
	 */
	public function  render_column( $column, $post_id ) {
		if ( 'kvm_perform_as' !== $column ) {
			return;
		}
		global $wpdb;
		$query = <<<SQL
			SELECT DISTINCT user_id
			FROM {$wpdb->usermeta}
			WHERE meta_key = '_kvm_perform_as'
			  AND meta_value = %d
SQL;
		$user_ids = $wpdb->get_col( $wpdb->prepare( $query, $post_id ) );
		if ( ! empty( $user_ids ) ) {
			// Search users.
			$users = get_users( [
				'include' => $user_ids,
				'fields'  => [ 'ID', 'display_name' ],
			] );
			if ( ! empty( $users ) ) {
				$names = array_map( function ( $user ) {
					return esc_html( $user->display_name );
				}, $users );
				echo implode( ', ', $names );
				return;
			}
		}
		echo '---';
	}
}

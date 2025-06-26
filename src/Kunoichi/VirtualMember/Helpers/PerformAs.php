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
		$members  = get_posts([
			'post_type'      => PostType::post_type(),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'post__not_in'   => [ PostType::default_user() ],
		]);
		$selected = array_map( 'intval', array_filter( (array) get_user_meta( $user->ID, self::meta_key() ) ) );
		wp_nonce_field( 'kvm_perform_as_setting', '_kvm_perform_as_nonce', false );
		?>
		<style>
			.kvm-default-user-choice {
				display: inline-block;
				margin-right: 1em;
				margin-bottom: 0.5em;
				padding: 0.5em;
				border: 1px solid #ddd;
				background-color: #f9f9f9;
			}
			.kvm-default-user-choice input[type="checkbox"]:checked + span {
				font-weight: bold;
			}
		</style>
		<h2><?php esc_html_e( 'Post Setting', 'kvm' ); ?></h2>
		<table class="form-table">
			<tr>
				<th>
					<?php printf( esc_html__( 'Default %s', 'kvm' ), esc_html( get_post_type_object( PostType::post_type() )->label ) ); ?>
				</th>
				<td>
					<?php foreach ( $members as $member ) : ?>
						<label class="kvm-default-user-choice">
							<input type="checkbox" name="kvm_default_member[]" value="<?php echo esc_attr( $member->ID ); ?>" <?php checked( in_array( $member->ID, $selected, true ) ); ?> />
							<span>
								<?php echo esc_html( get_the_title( $member ) ); ?>
							</span>
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
		delete_user_meta( $user_id, self::meta_key() );
		// Then add new.
		if ( ! empty( $_POST['kvm_default_member'] ) && is_array( $_POST['kvm_default_member'] ) ) {
			// Sanitize and save.
			$members = array_map( 'intval', array_filter( $_POST['kvm_default_member'] ) );
			foreach ( $members as $member_id ) {
				add_user_meta( $user_id, self::meta_key(), $member_id );
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
		return array_map( 'intval', array_filter( get_user_meta( $user_id, self::meta_key() ) ) );
	}

	/**
	 * Get users who perform as virtual member.
	 *
	 * @param int|\WP_Post|null $post Post ID or post object.
	 * @return \WP_User[]
	 */
	public static function get_users( $post = null ) {
		$post = get_post( $post );
		if ( ! $post || PostType::post_type() !== $post->post_type ) {
			return [];
		}
		$users = new \WP_User_Query( [
			'meta_query' => [
				[
					'key'     => self::meta_key(),
					'value'   => $post->ID,
					'compare' => '=',
				],
			],
			'number'     => -1,
		] );
		return $users->get_results();
	}

	/**
	 * Get meta key for perform as.
	 *
	 * This is used to save the member ID when a user performs as a virtual member.
	 * Considering that this is a site-specific setting, it includes the current blog ID.
	 *
	 * @return string
	 */
	public static function meta_key() {
		$blog_id = get_current_blog_id();
		if ( 1 < $blog_id ) {
			return sprintf( '_kvm_perform_as_%d', get_current_blog_id() );
		}
		return '_kvm_perform_as';
	}
}

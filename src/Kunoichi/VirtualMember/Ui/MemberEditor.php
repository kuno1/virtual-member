<?php

namespace Kunoichi\VirtualMember\Ui;


use Kunoichi\VirtualMember\Pattern\Singleton;
use Kunoichi\VirtualMember\PostType;
use Kunoichi\VirtualMember\Utility\CommonMethods;

/**
 * Editor for author.
 *
 * @package kvm
 */
class MemberEditor extends Singleton {

	use CommonMethods;

	/**
	 * @inheritDoc
	 */
	protected function init() {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post', [ $this, 'save_post' ], 10, 2 );
	}

	/**
	 * Render meta box.
	 *
	 * @param string $post_type
	 */
	public function add_meta_boxes( $post_type ) {
		if ( ! $this->use_member( $post_type ) ) {
			return;
		}
		$post_type_object = get_post_type_object( $this->post_type() );
		add_meta_box( 'virtual-member-id', $post_type_object->label, [ $this, 'render_meta_box' ], $post_type, 'side' );
	}

	/**
	 * Render meta box.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_meta_box( $post ) {
		$post_type_object = get_post_type_object( PostType::post_type() );
		wp_nonce_field( 'virtual_member_as_author', '_kvmnonce', false );
		$users      = get_posts( [
			'post_type'      => $this->post_type(),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		] );
		$current_id = (int) get_post_meta( $post->ID, $this->meta_key(), true );
		?>
		<p class="description">
			<?php
			// translators: %s is post type.
			printf( __( 'To change post author of %s, please specify.', 'kvm' ), $post_type_object->label );
			?>
		</p>
		<p>
			<select name="virtual-author-id" id="virtual-author-id" style="box-sizing: border-box; max-width: 100%;">
				<option value="0" <?php selected( $current_id, 0 ); ?>><?php esc_html_e( 'Not specify', 'kvm' ); ?></option>
				<?php
				foreach ( $users as $user ) :
					$group = '';
					$terms = get_the_terms( $user, $this->taxonomy() );
					if ( $terms && ! is_wp_error( $terms ) ) {
						$group = implode( ', ', array_map( function( $term ) {
							return $term->name;
						}, $terms ) );
					}
					?>
					<option value="<?php echo esc_attr( $user->ID ); ?>"<?php selected( $user->ID, $current_id ); ?>>
						<?php
						// Title.
						echo esc_html( get_the_title( $user ) );
						// Group.
						if ( ! empty( $group ) ) {
							// translators: %s is list of group.
							printf( esc_html_x( ' (%s)', 'user-group', 'kvm' ), esc_html( $group ) );
						}
						// Default.
						if ( PostType::default_user() === $user->ID ) {
							printf( '- <strong>%s</strong>', esc_html__( 'Default', 'kvm' ) );
						}
						?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}

	/**
	 * Save post authors.
	 *
	 * @param int      $post_id
	 * @param \WP_Post $post
	 */
	public function save_post( $post_id, $post ) {
		if ( ! $this->use_member( $post->post_type ) ) {
			return;
		}
		if ( ! wp_verify_nonce( filter_input( INPUT_POST, '_kvmnonce' ), 'virtual_member_as_author' ) ) {
			return;
		}
		$author_id = (int) filter_input( INPUT_POST, 'virtual-author-id' );
		if ( $author_id ) {
			update_post_meta( $post_id, $this->meta_key(), $author_id );
		} else {
			delete_post_meta( $post_id, $this->meta_key() );
		}
	}
}

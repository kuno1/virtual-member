<?php

namespace Kunoichi\VirtualMember\Ui;


use Kunoichi\VirtualMember\Pattern\Singleton;
use Kunoichi\VirtualMember\PostType;
use Kunoichi\VirtualMember\Utility\CommonMethods;
use TYPO3\CMS\Reports\Status;

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
		add_action( 'save_post', [ $this, 'save_member' ], 10, 2 );
		add_filter( 'display_post_states', [ $this, 'post_states' ], 10, 2 );
		add_filter( 'user_contactmethods', [ $this, 'user_contact_methods' ], 10, 2 );
	}

	/**
	 * Render meta box.
	 *
	 * @param string $post_type
	 */
	public function add_meta_boxes( $post_type ) {
		$post_type_object = get_post_type_object( $this->post_type() );
		if ( $this->post_type() === $post_type ) {
			add_meta_box( 'virtual-member-meta', __( 'Contact Methods', 'kvm' ), [ $this, 'render_member_meta_box' ], $post_type, 'advanced' );
		} elseif ( $this->use_member( $post_type ) ) {
			add_meta_box( 'virtual-member-id', $post_type_object->label, [ $this, 'render_post_meta_box' ], $post_type, 'side' );
		}
	}

	/**
	 * Render meta box for single author.
	 *
	 * @param \WP_Post   $post  Post object.
	 * @param \WP_Post[] $users User objects.
	 * @return void
	 */
	protected function meta_box_for_single( $post, $users ) {
		$current_id = (int) get_post_meta( $post->ID, $this->meta_key(), true );
		?>
		<p>
			<select name="virtual-author-id[]" id="virtual-author-id" style="box-sizing: border-box; max-width: 100%;">
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
	 * Render meta box for single author.
	 *
	 * @param \WP_Post   $post  Post object.
	 * @param \WP_Post[] $users User objects.
	 *
	 * @return void
	 */
	protected function meta_box_for_multiple( $post, $users ) {
		$current_ids = array_map( 'intval', get_post_meta( $post->ID, $this->meta_key() ) );
		?>
		<p>
			<?php foreach ( $users as $user ) :
				$group = '';
				$terms = get_the_terms( $user, $this->taxonomy() );
				if ( $terms && ! is_wp_error( $terms ) ) {
					$group = implode( ', ', array_map( function( $term ) {
						return $term->name;
					}, $terms ) );
				}
				$checked = in_array( $user->ID, $current_ids, true );
				?>
				<label style="display: block; padding: 2px 3px;">
					<input type="checkbox" name="virtual-author-id[]" value="<?php echo esc_attr( $user->ID ); ?>" <?php checked( $checked ); ?> />
					<?php echo esc_html( get_the_title( $user ) ); ?>
					<?php if ( ! empty( $group ) ) : ?>
					<small><?php printf( esc_html_x( ' (%s)', 'user-group', 'kvm' ), esc_html( $group ) ) ?></small>
					<?php endif; ?>
					<?php if ( PostType::default_user() === $user->ID ) : ?>
					- <strong><?php esc_html_e( 'Default', 'kvm' ); ?></strong>
					<?php endif; ?>
				</label>
			<?php endforeach; ?>
		</p>
		<?php
	}

	/**
	 * Render meta box.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_post_meta_box( $post ) {
		wp_nonce_field( 'virtual_member_as_author', '_kvmnonce', false );
		$users      = get_posts( [
			'post_type'      => $this->post_type(),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		] );
		$post_type_object = get_post_type_object( PostType::post_type() );
		?>
		<p class="description">
			<?php
			// translators: %s is post type.
			printf( __( 'To change post author of %s, please specify.', 'kvm' ), $post_type_object->label );
			?>
		</p>
		<?php
		if ( get_option( 'kvm_allow_multiple_author' ) ) {
			$this->meta_box_for_multiple( $post, $users );
		} else {
			$this->meta_box_for_single( $post, $users );
		}
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
		$author_id = filter_input( INPUT_POST, 'virtual-author-id', FILTER_DEFAULT, [
			'flags' => FILTER_REQUIRE_ARRAY
		] );
		delete_post_meta( $post_id, $this->meta_key() );
		foreach ( $author_id as $id ) {
			add_post_meta( $post_id, $this->meta_key(), $id );
		}
	}

	/**
	 * Save post object.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 *
	 * @return void
	 */
	public function save_member( $post_id, $post ) {
		if ( $this->post_type() !== $post->post_type ) {
			return;
		}
		if ( ! wp_verify_nonce( filter_input( INPUT_POST, '_kvmmmebernonce' ), 'kvm_meta_update' ) ) {
			return;
		}
		foreach ( $this->get_custom_metas() as $key => $label ) {
			update_post_meta( $post_id, $key, filter_input( INPUT_POST, $key ) );
		}
	}

	/**
	 * Get custom metas.
	 *
	 * @return string[]
	 */
	protected function get_custom_metas() {
		return array_merge( [
			'user_url' => __( 'Web Site', 'kvm' ),
		], wp_get_user_contact_methods() );
	}

	/**
	 * Render meta box for member.
	 *
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function render_member_meta_box( $post ) {
		wp_nonce_field( 'kvm_meta_update', '_kvmmmebernonce', false );
		foreach ( $this->get_custom_metas() as $key => $label ) :
			$type = ( 'user_url' === $key ) ? 'url' : 'text';
			?>
			<p>
				<label><?php echo esc_html( $label ); ?><br />
					<?php
					printf(
						'<input class="widefat" type="%s" name="%s" value="%s" />',
						esc_attr( $type ),
						esc_attr( $key ),
						esc_attr( get_post_meta( $post->ID, $key, true ) )
					);
					?>
				</label>
			</p>
			<?php
		endforeach;
	}

	/**
	 * Add hint.
	 *
	 * @param string[] $states Post status.
	 * @param \WP_Post $post   Post object
	 *
	 * @return string[]
	 */
	public function post_states( $states, $post ) {
		if ( PostType::post_type() !== $post->post_type ) {
			return $states;
		}
		if ( PostType::default_user() !== $post->ID ) {
			return $states;
		}
		$states['kvm_default'] = __( 'Default Member', 'kvm' );
		return $states;
	}

	/**
	 * Get user contact methods.
	 *
	 * @param string[] $methods Methods.
	 * @param \WP_User $user    User object.
	 *
	 * @return string[]
	 */
	public function user_contact_methods( $methods, $user = null ) {
		foreach ( $this->custom_contact_methods() as $key => $label ) {
			$methods[ $key ] = $label;
		}
		return $methods;
	}
}

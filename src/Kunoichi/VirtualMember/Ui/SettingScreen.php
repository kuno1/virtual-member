<?php

namespace Kunoichi\VirtualMember\Ui;


use Kunoichi\VirtualMember\Pattern\Singleton;
use Kunoichi\VirtualMember\PostType;
use Kunoichi\VirtualMember\Utility\CommonMethods;

/**
 * Get member.
 *
 * @package kvm
 */
class SettingScreen extends Singleton {

	use CommonMethods;

	protected $page = 'kvm-setting';

	/**
	 * @inheritDoc
	 */
	protected function init() {
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Title of menul
	 *
	 * @return string
	 */
	protected function title() {
		return __( 'Member Setting', 'kvm' );
	}

	/**
	 * Register setting screen.
	 */
	public function admin_menu() {
		$title = $this->title();
		add_submenu_page( 'edit.php?post_type=' . $this->post_type(), $title, $title, 'manage_options', $this->page, [ $this, 'menu_page' ] );
	}

	/**
	 * Render setting screen.
	 */
	public function menu_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( $this->title() ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'The settings below will take effect on Virtual Member.', 'kvm' ); ?>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
				<?php
				settings_fields( $this->page );
				do_settings_sections( $this->page );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		//
		// Basic setting.
		//
		add_settings_section( 'kvm-default', __( 'Basic Setting', 'kvm' ), function() {
			// Do something.
		}, $this->page );
		// Post Type.
		add_settings_field( 'kvm_available_post_types', __( 'Post Types', 'kvm' ), function() {
			$post_types = array_filter( get_post_types( [ 'public' => true ], OBJECT ), function( $post_type ) {
				return ! in_array( $post_type->name, [ 'attachment', $this->post_type() ], true );
			} );
			foreach ( $post_types as $post_type ) {
				printf(
					'<label style="display: inline-block; margin: 0 1em 1em 0;"><input type="checkbox" name="kvm_available_post_types[]" value="%s" %s /> %s</label>',
					esc_attr( $post_type->name ),
					checked( $this->use_member( $post_type->name ), true, false ),
					esc_html( $post_type->label )
				);
			}
			printf(
				'<p class="description">%s</p>',
				esc_html__( 'Post types checked above will have member meta box.', 'kvm' )
			);
		}, $this->page, 'kvm-default' );
		register_setting( $this->page, 'kvm_available_post_types' );
		// Default
		add_settings_field( 'kvm_default_user', __( 'Default Author', 'kvm' ), function() {
			$users = $this->users( 'choose_default' );
			if ( empty( $users ) ) {
				printf( 'No member is registered. Please register at least 1 member.' );
			} else {
				$default = PostType::default_user();
				?>
				<select name="kvm_default_user">
					<option value="0" <?php selected( $default, 0 ); ?>><?php esc_html_e( 'Not Set', 'kvm' ); ?></option>
					<?php
					foreach ( $users as $user ) {
						printf(
							'<option value="%s" %s>%s%s</option>',
							esc_attr( $user->ID ),
							selected( $default, $user->ID, false ),
							esc_html( get_the_title( $user ) ),
							( ( $user->ID === $default ) ) ? esc_html__( '(Default)', 'kvm' ) : ''
						);
					}
					?>
				</select>
				<?php
			}
			printf(
				'<p class="description">%s</p>',
				esc_html__( 'By setting default user, any post without specified virtual member will be owned by the default user..', 'kvm' )
			);
		}, $this->page, 'kvm-default' );
		register_setting( $this->page, 'kvm_default_user' );
		// Is public.
		add_settings_field( 'kvm_post_type_is_public', __( 'Public', 'kvm' ), function() {
			printf(
				'<label><input type="checkbox" name="kvm_post_type_is_public" value="1" %s/> %s</label><p class="description">%s</p>',
				checked( (bool) get_option( 'kvm_post_type_is_public' ), true, false ),
				esc_html__( 'Post Type is public', 'kvm' ),
				esc_html__( 'If checked, post type for virtual member will have its own permalink and displayed. Never forget to refresh permalink structure after change.', 'kvm' )
			);
		}, $this->page, 'kvm-default' );
		register_setting( $this->page, 'kvm_post_type_is_public' );
		// Contact Methods.
		add_settings_field( 'kvm_contact_methods', __( 'Contact Methods', 'kvm' ), function() {
			printf(
				'<textarea name="kvm_contact_methods" placeholder="%s">%s</textarea><p class="description">%s</p>',
				implode( '&#13;&#10;', array_map( 'esc_html', [ 'facebook,Facebook', 'twitter,Twitter' ] ) ),
				esc_textarea( get_option( 'kvm_contact_methods' ) ),
				esc_html__( 'This will add extra contact methods to the user profile editor and also add meta box of member editor. Enter key and label in CSV format. e.g. facebook,Facebook', 'kvm' )
			);
		}, $this->page, 'kvm-default' );
		register_setting( $this->page, 'kvm_contact_methods' );
		// Allow multiple assign.
		add_settings_field( 'kvm_allow_multiple_author', __( 'Allow Multiple Assign', 'kvm' ), function() {
			foreach ( [
				__( '1 author', 'kvm' ) => false,
				__( 'Multiple Authors', 'kvm' ) => true,
			] as $label => $value ) {
				printf(
					'<label style="display: inline-block; margin: 0 1em 1em 0;"><input type="radio" name="kvm_allow_multiple_author" value="%s" %s /> %s</label>',
					( $value ? '1' : '' ),
					checked( get_option( 'kvm_allow_multiple_author', '' ), $value, false ),
					esc_html( $label )
				);
			}
			printf(
				'<p class="description">%s</p>',
				esc_html__( 'If you allowed multiple author, the page order affects which author to be displayed, depending the theme.', 'kvm' )
			);
		}, $this->page, 'kvm-default' );
		register_setting( $this->page, 'kvm_allow_multiple_author' );
		//
		// Override default.
		//
		add_settings_section( 'kvm-labels', __( 'Labels', 'kvm' ), function() {

		}, $this->page );
		// Post type label.
		add_settings_field( 'kvm_post_type_label', __( 'Post Type Label', 'kvm' ), function() {
			printf(
				'<input class="regular-text" name="kvm_post_type_label" type="text" value="%s" placeholder="%s" />',
				esc_attr( get_option( 'kvm_post_type_label' ) ),
				esc_attr__( 'Member', 'kvm' )
			);
		}, $this->page, 'kvm-labels' );
		register_setting( $this->page, 'kvm_post_type_label' );
		// Prefix.
		add_settings_field( 'kvm_post_type_prefix', __( 'URL Prefix', 'kvm' ), function() {
			printf(
				'<input type="text" value="%s" name="kvm_post_type_prefix" placeholder="member" /><p class="description">%s</p>',
				esc_attr( get_option( 'kvm_post_type_prefix' ) ),
				esc_html__( 'This will be a part of URL if virtual member is a public post type.', 'kvm' )
			);
		}, $this->page, 'kvm-labels' );
		register_setting( $this->page, 'kvm_post_type_prefix' );
	}
}

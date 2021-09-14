<?php
/**
Plugin Name: Virtual Member
Plugin URI: https://wordpress.org/plugins/virtual-member/
Description: Add virtual member to represents authors who are not users of WordPress.
Author: Kunoichi INC.
Version: nightly
Author URI: https://tarosky.co.jp/
License: GPL3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: kvm
Domain Path: /languages
 */

defined( 'ABSPATH' ) or die();

const KVM_AS_PLUGIN = true;

/**
 * Init plugins.
 */
function kvm_init() {
	// Register translations.
	load_plugin_textdomain( 'kvm', false, basename( __DIR__ ) . '/languages' );
	// Composer.
	$composer = __DIR__ . '/vendor/autoload.php';
	if ( ! file_exists( $composer ) ) {
		trigger_error( __( 'Compose file is missing.', 'kvm' ), E_USER_ERROR );
	}
	// Boostrap.
	require_once $composer;
	\Kunoichi\VirtualMember\PostType::register();
}

// Register hooks.
add_action( 'plugins_loaded', 'kvm_init' );

<?php
/**
Plugin Name: Virtual Member
Plugin URI: https://wordpress.org/plugins/virtual-member/
Description: Add virtual member to represent authors who are not users of WordPress.
Author: Kunoichi INC.
Version: nightly
Tested up to: 6.8
Requires at least: 6.3
Requires PHP: 7.4
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
	// Composer.
	$composer = __DIR__ . '/vendor/autoload.php';
	if ( ! file_exists( $composer ) ) {
		trigger_error( 'Compose file is missing.', E_USER_ERROR );
	}
	// Boostrap.
	require_once $composer;
	\Kunoichi\VirtualMember\PostType::register();
}

// Register hooks.
add_action( 'plugins_loaded', 'kvm_init' );


// Flush rewrite rules on activation.
register_activation_hook( __FILE__, function () {
	flush_rewrite_rules();
} );

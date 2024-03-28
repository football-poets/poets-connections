<?php
/**
 * Plugin Name: Football Poets Connections
 * Description: Creates Connections between CPTs for the Football Poets site.
 * Plugin URI:  https://github.com/football-poets/poets-connections
 * Version:     0.3.2a
 * Author:      Christian Wach
 * Author URI:  https://haystack.co.uk
 * Text Domain: poets-connections
 * Domain Path: /languages
 *
 * @package Poets_Connections
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Set our version here.
define( 'POETS_CONNECTIONS_VERSION', '0.3.2a' );

// Set our debug flag here.
if ( ! defined( 'POETS_CONNECTIONS_DEBUG' ) ) {
	define( 'POETS_CONNECTIONS_DEBUG', false );
}

// Store reference to this file.
if ( ! defined( 'POETS_CONNECTIONS_FILE' ) ) {
	define( 'POETS_CONNECTIONS_FILE', __FILE__ );
}

// Store URL to this plugin's directory.
if ( ! defined( 'POETS_CONNECTIONS_URL' ) ) {
	define( 'POETS_CONNECTIONS_URL', plugin_dir_url( POETS_CONNECTIONS_FILE ) );
}

// Store PATH to this plugin's directory.
if ( ! defined( 'POETS_CONNECTIONS_PATH' ) ) {
	define( 'POETS_CONNECTIONS_PATH', plugin_dir_path( POETS_CONNECTIONS_FILE ) );
}

/**
 * Football Poets Connections Plugin Class.
 *
 * A class that encapsulates plugin functionality.
 *
 * @since 0.1
 */
class Poets_Connections_Plugin {

	/**
	 * Config object.
	 *
	 * @since 0.1
	 * @access public
	 * @var Poets_Connections_Config
	 */
	public $config;

	/**
	 * Poet Profile Claim object.
	 *
	 * @since 0.1
	 * @access public
	 * @var Poets_Connections_Claim
	 */
	public $claim;

	/**
	 * Member Profile Form object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object Poets_Connections_Profile_Sync
	 */
	public $profile_sync;

	/**
	 * Cover Image object.
	 *
	 * @since 0.1
	 * @access public
	 * @var Poets_Connections_Cover_Image
	 */
	public $cover_image;

	/**
	 * BuddyForms object.
	 *
	 * @since 0.3
	 * @access public
	 * @var Poets_Connections_BuddyForms
	 */
	public $buddyforms;

	/**
	 * Comments object.
	 *
	 * @since 0.3
	 * @access public
	 * @var Poets_Connections_Comments
	 */
	public $comments;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// Bootstrap plugin.
		$this->include_files();
		$this->setup_globals();
		$this->register_hooks();

	}

	/**
	 * Includes files.
	 *
	 * @since 0.1
	 */
	public function include_files() {

		// Include class files.
		include_once POETS_CONNECTIONS_PATH . 'includes/poets-connections-config.php';
		include_once POETS_CONNECTIONS_PATH . 'includes/poets-connections-claim.php';
		include_once POETS_CONNECTIONS_PATH . 'includes/poets-connections-profile-sync.php';
		include_once POETS_CONNECTIONS_PATH . 'includes/poets-connections-cover-image.php';
		include_once POETS_CONNECTIONS_PATH . 'includes/poets-connections-buddyforms.php';
		include_once POETS_CONNECTIONS_PATH . 'includes/poets-connections-comments.php';
		include_once POETS_CONNECTIONS_PATH . 'includes/poets-connections-functions.php';

	}

	/**
	 * Sets up objects.
	 *
	 * @since 0.1
	 */
	public function setup_globals() {

		// Instatiate objects.
		$this->config       = new Poets_Connections_Config( $this );
		$this->claim        = new Poets_Connections_Claim( $this );
		$this->profile_sync = new Poets_Connections_Profile_Sync( $this );
		$this->cover_image  = new Poets_Connections_Cover_Image( $this );
		$this->buddyforms   = new Poets_Connections_BuddyForms( $this );
		$this->comments     = new Poets_Connections_Comments( $this );

	}

	/**
	 * Registers hook callbacks.
	 *
	 * @since 0.1
	 */
	public function register_hooks() {

		// Always use translation.
		add_action( 'plugins_loaded', [ $this, 'translation' ] );

		// Register hooks in classes.
		$this->config->register_hooks();
		$this->claim->register_hooks();
		$this->profile_sync->register_hooks();
		$this->cover_image->register_hooks();
		$this->buddyforms->register_hooks();
		$this->comments->register_hooks();

	}

	/**
	 * Loads translation.
	 *
	 * @since 0.1
	 */
	public function translation() {

		// Allow translations to be added.
		// phpcs:ignore WordPress.WP.DeprecatedParameters.Load_plugin_textdomainParam2Found
		load_plugin_textdomain(
			'poets-connections', // Unique name.
			false, // Deprecated argument.
			dirname( plugin_basename( POETS_CONNECTIONS_FILE ) ) . '/languages/'
		);

	}

	/**
	 * Performs plugin activation tasks.
	 *
	 * @since 0.1
	 */
	public function activate() {

	}

	/**
	 * Performs plugin deactivation tasks.
	 *
	 * @since 0.1
	 */
	public function deactivate() {

	}

	/**
	 * Write to the error log.
	 *
	 * @since 0.3.2
	 *
	 * @param array $data The data to write to the log file.
	 */
	public function log_error( $data = [] ) {

		// Skip if not debugging.
		if ( POETS_CONNECTIONS_DEBUG === false ) {
			return;
		}

		// Skip if empty.
		if ( empty( $data ) ) {
			return;
		}

		// Format data.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		$error = print_r( $data, true );

		// Write to log file.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $error );

	}

}

/**
 * Plugin reference getter.
 *
 * @since 0.1
 *
 * @return obj $poets_connections The plugin object.
 */
function poets_connections() {
	static $poets_connections;
	if ( ! isset( $poets_connections ) ) {
		$poets_connections = new Poets_Connections_Plugin();
	}
	return $poets_connections;
}

// Bootstrap plugin immediately.
poets_connections();

// Activation.
register_activation_hook( __FILE__, [ poets_connections(), 'activate' ] );

// Deactivation.
register_deactivation_hook( __FILE__, [ poets_connections(), 'deactivate' ] );

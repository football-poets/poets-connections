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
	 * @var object $config The Config object.
	 */
	public $config;

	/**
	 * Poet Profile Claim object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $claim The Poet Profile Claim object.
	 */
	public $claim;

	/**
	 * Member Profile Form object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $profile_sync The Member Profile Form object.
	 */
	public $profile_sync;

	/**
	 * Cover Image object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $cover_image The Cover Image object.
	 */
	public $cover_image;

	/**
	 * BuddyForms object.
	 *
	 * @since 0.3
	 * @access public
	 * @var object $buddyforms The BuddyForms compatibility object.
	 */
	public $buddyforms;

	/**
	 * Comments object.
	 *
	 * @since 0.3
	 * @access public
	 * @var object $comments The Comments compatibility object.
	 */
	public $comments;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// Include files.
		$this->include_files();

		// Setup globals.
		$this->setup_globals();

		// Register hooks.
		$this->register_hooks();

	}

	/**
	 * Include files.
	 *
	 * @since 0.1
	 */
	public function include_files() {

		// Include Config class.
		include_once POETS_CONNECTIONS_PATH . 'includes/poets-connections-config.php';

		// Include Claim class.
		include_once POETS_CONNECTIONS_PATH . 'includes/poets-connections-claim.php';

		// Include Profile Sync class.
		include_once POETS_CONNECTIONS_PATH . 'includes/poets-connections-profile-sync.php';

		// Include Cover Image class.
		include_once POETS_CONNECTIONS_PATH . 'includes/poets-connections-cover-image.php';

		// Include BuddyForms file.
		include_once POETS_CONNECTIONS_PATH . 'includes/poets-connections-buddyforms.php';

		// Include Comments file.
		include_once POETS_CONNECTIONS_PATH . 'includes/poets-connections-comments.php';

		// Include functions file.
		include_once POETS_CONNECTIONS_PATH . 'includes/poets-connections-functions.php';

	}

	/**
	 * Set up objects.
	 *
	 * @since 0.1
	 */
	public function setup_globals() {

		// Init Config object.
		$this->config = new Poets_Connections_Config( $this );

		// Init Poet Profile Claim object.
		$this->claim = new Poets_Connections_Claim( $this );

		// Init Profile Sync object.
		$this->profile_sync = new Poets_Connections_Profile_Sync( $this );

		// Init Cover Image object.
		$this->cover_image = new Poets_Connections_Cover_Image( $this );

		// Init BuddyForms object.
		$this->buddyforms = new Poets_Connections_BuddyForms( $this );

		// Init Comments object.
		$this->comments = new Poets_Connections_Comments( $this );

	}

	/**
	 * Register WordPress hooks.
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
	 * Load translation if present.
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
	 * Perform plugin activation tasks.
	 *
	 * @since 0.1
	 */
	public function activate() {

	}

	/**
	 * Perform plugin deactivation tasks.
	 *
	 * @since 0.1
	 */
	public function deactivate() {

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

// Instantiate the class.
poets_connections();

// Activation.
register_activation_hook( __FILE__, [ poets_connections(), 'activate' ] );

// Deactivation.
register_deactivation_hook( __FILE__, [ poets_connections(), 'deactivate' ] );

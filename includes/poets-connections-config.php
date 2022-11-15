<?php
/**
 * Football Poets Connections Config Class.
 *
 * Handles plugin config variables and connection-related methods.
 *
 * @since 0.1
 * @package Poets_Connections
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Football Poets Connections Config Class.
 *
 * A class that holds plugin config variables and connection-related methods.
 *
 * @since 0.1
 */
class Poets_Connections_Config {

	/**
	 * Plugin object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $parent_obj The plugin object.
	 */
	public $plugin;

	/**
	 * Post meta key used to identify all Profile Claims.
	 *
	 * This is always set when a Poet has been claimed by a User as their Poet
	 * Profile. The value is the claiming User ID. When it is decided that the
	 * Poet is a nom de plume of the User, the User becomes the Post author of
	 * the Poet Profile Post and this meta is discarded.
	 *
	 * @since 0.2
	 * @access public
	 * @var str $claim_user_id The Profile Claim meta key.
	 */
	public $claim_key = '_poet_connections_claim_user_id';

	/**
	 * Post meta key used to identify Primary Profile Claims.
	 *
	 * This is set only when a Poet has been claimed by a User as their Primary
	 * Poet Profile. The value is the claiming User ID. This meta is discarded
	 * when the Claim is closed and a P2P connection is made between the Poet
	 * Profile Post and the User.
	 *
	 * @since 0.2
	 * @access public
	 * @var str $claim_primary_key The Primary Profile Claim meta key.
	 */
	public $claim_primary_key = '_poet_connections_claim_primary';

	/**
	 * User meta and Post meta key used to identify Primary Profile User or Post.
	 *
	 * These are set once the Poet has been positively identified as the claiming
	 * User. The values are the connected User ID or Post ID.
	 *
	 * @since 0.2
	 * @access public
	 * @var str $primary_key The Primary Profile User meta key.
	 */
	public $primary_key = '_poet_connections_primary';

	/**
	 * User meta key used to identify that a User wants no further Profile Claims.
	 *
	 * @since 0.2
	 * @access public
	 * @var str $claim_disable_key The no further Profile Claims meta key.
	 */
	public $claim_disable_key = '_poet_connections_claim_disable';

	/**
	 * Comment meta key used to identify that a Poet Profile for a comment.
	 *
	 * @since 0.3
	 * @access public
	 * @var str $comment_key The comment meta key that hold the Poet Profile ID.
	 */
	public $comment_key = '_poet_connections_profile_id';

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param object $plugin A reference to the plugin object.
	 */
	public function __construct( $plugin = null ) {

		// Store reference to "parent".
		$this->plugin = $plugin;

	}

	/**
	 * Register WordPress hooks.
	 *
	 * @since 0.1
	 */
	public function register_hooks() {

		// Add connection types.
		add_action( 'p2p_init', [ $this, 'add_connection_types' ] );

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

	// -------------------------------------------------------------------------

	/**
	 * Adds connection types.
	 *
	 * @since 0.1
	 */
	public function add_connection_types() {

		/**
		 * Connect Poems and Poets.
		 *
		 * This is a many-to-many connection because Poets can have many Poems
		 * and Poems can be written by multiple Poets as "joint authors".
		 */
		p2p_register_connection_type( [
			'name' => 'poets_to_poems',
			'from' => 'poet',
			'to' => 'poem',
			'title' => [
				'from' => __( 'Poems written', 'poets-connections' ),
				'to' => __( 'Poem authors', 'poets-connections' ),
			],
			'from_labels' => [
				'singular_name' => __( 'Poet', 'poets-connections' ),
				'search_items' => __( 'Search poets', 'poets-connections' ),
				'not_found' => __( 'No poets found.', 'poets-connections' ),
				'create' => __( 'Add poet', 'poets-connections' ),
			],
			'to_labels' => [
				'singular_name' => __( 'Poem', 'poets-connections' ),
				'search_items' => __( 'Search poems', 'poets-connections' ),
				'not_found' => __( 'No poems found.', 'poets-connections' ),
				'create' => __( 'Add poems', 'poets-connections' ),
			],
		] );

		/**
		 * Connect Poets and members as "primary".
		 *
		 * This is a one-to-one connection because there can only be one Primary
		 * Profile per member. All "noms de plume" are created by members such
		 * that they are the author of the Poet Profile. Only Primary Profiles
		 * have this connection, however.
		 */
		p2p_register_connection_type( [
			'name' => 'poets_to_users',
			'from' => 'poet',
			'to' => 'user',
			'cardinality' => 'one-to-one',
			'title' => [
				'from' => __( 'User profile', 'poets-connections' ),
				'to' => __( 'Poet profile', 'poets-connections' ),
			],
			'from_labels' => [
				'singular_name' => __( 'Poet', 'poets-connections' ),
				'search_items' => __( 'Search poets', 'poets-connections' ),
				'not_found' => __( 'No poets found.', 'poets-connections' ),
				'create' => __( 'Add poet', 'poets-connections' ),
			],
			'to_labels' => [
				'singular_name' => __( 'User', 'poets-connections' ),
				'search_items' => __( 'Search users', 'poets-connections' ),
				'not_found' => __( 'No users found.', 'poets-connections' ),
				'create' => __( 'Add users', 'poets-connections' ),
			],
		] );

	}

	/**
	 * Make a Primary connection between a Poet Profile and a WordPress User.
	 *
	 * @since 0.1
	 *
	 * @param int $poet_id The ID of the Poet Post.
	 * @param int $user_id The ID of the WordPress User.
	 * @return bool True if connected, false otherwise.
	 */
	public function connect_as_primary( $poet_id, $user_id ) {

		// Flag User as having a Primary Profile.
		bp_update_user_meta( $user_id, $this->primary_key, $poet_id );

		// Add User ID as the Poet's Primary User.
		add_post_meta( $poet_id, $this->primary_key, $user_id, true );

		// Create connection.
		p2p_type( 'poets_to_users' )->connect( $poet_id, $user_id, [
			'date' => current_time( 'mysql' ),
		] );

		// --<
		return true;

	}

	/**
	 * Undo a Primary connection between a Poet Profile and a WordPress User.
	 *
	 * @since 0.3
	 *
	 * @param int $poet_id The ID of the Poet Post.
	 * @param int $user_id The ID of the WordPress User.
	 * @return bool True if disconnected, false otherwise.
	 */
	public function disconnect_as_primary( $poet_id, $user_id ) {

		// Delete User meta.
		bp_delete_user_meta( $user_id, $this->primary_key );

		// Delete Post meta.
		delete_post_meta( $poet_id, $this->primary_key );

		// Destroy connection.
		p2p_type( 'poets_to_users' )->disconnect( $poet_id, $user_id );

		// --<
		return true;

	}

	/**
	 * Get a Poet's connected WordPress User.
	 *
	 * Depending on the circumstances, it may be more efficient to grab the
	 * Primary User's ID from the Poet's postmeta.
	 *
	 * @since 0.1
	 *
	 * @param WP_Post $poet The WordPress Poet Post to get a connected User for.
	 * @return bool|WP_User False if no connected User, User object otherwise.
	 */
	public function get_primary_user( $poet ) {

		// Sanity check.
		if ( ! ( $poet instanceof WP_Post ) ) {
			return false;
		}

		// Define args.
		$connected_user_args = [
			'connected_type' => 'poets_to_users',
			'connected_items' => $poet,
		];

		// Query Users.
		$connected_users = get_users( $connected_user_args );

		// If no connected User, return false.
		if ( empty( $connected_users ) ) {
			return false;
		}

		// Return connected User.
		$connected_user = array_pop( $connected_users );

		// --<
		return $connected_user;

	}

	/**
	 * Get a WordPress User's connected Poet.
	 *
	 * Depending on the circumstances, it may be more efficient to grab the
	 * Primary Poet's ID from the User's usermeta.
	 *
	 * @since 0.1
	 *
	 * @param int $user_id The WordPress User to get a connected Poet Post for.
	 * @return bool|WP_Post False if no connected Poet, Post object otherwise.
	 */
	public function get_primary_poet( $user_id ) {

		// Construct args to get the current User's Poet Post.
		$args = [
			'connected_type' => 'poets_to_users',
			'connected_items' => new WP_User( $user_id ),
			'suppress_filters' => false,
			'nopaging' => true,
		];

		// Get all Posts (though there will only be one).
		$connected_poets = get_posts( $args );

		// Bail if they are already connected to a "Poet".
		if ( empty( $connected_poets ) ) {
			return false;
		}

		// Return connected Poet.
		$connected_poet = array_pop( $connected_poets );

		// --<
		return $connected_poet;

	}

	/**
	 * Get the Poet Profiles for a Poem.
	 *
	 * @since 0.2
	 *
	 * @param int $poem_id The ID of the Poem.
	 * @return array|bool The array of Poet Profile Posts or false if none found.
	 */
	public function get_poets_for_poem( $poem_id ) {

		// Define query args.
		$query_args = [
			'connected_type' => 'poets_to_poems',
			'connected_items' => get_post( $poem_id ),
			'nopaging' => true,
			'no_found_rows' => true,
		];

		// The query.
		$posts = get_posts( $query_args );

		// Get Poet.
		if ( count( $posts ) > 0 ) {
			return $posts;
		}

		// Fallback.
		return false;

	}

	/**
	 * Assign a Poet Profile to a Poem.
	 *
	 * @since 0.3
	 *
	 * @param int $poet_id The ID of the Poet Profile.
	 * @param int $poem_id The ID of the Poem.
	 * @return bool True on success, false otherwise.
	 */
	public function connect_poet_and_poem( $poet_id, $poem_id ) {

		// Create connection.
		p2p_type( 'poets_to_poems' )->connect( $poet_id, $poem_id, [
			'date' => current_time( 'mysql' ),
		] );

		// Always true for now.
		return true;

	}

}

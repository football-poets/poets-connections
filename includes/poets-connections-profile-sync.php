<?php
/**
 * Football Poets Connections "Member Profile" Class.
 *
 * Handles the sync functionality between a BuddyPress Member Profile and their
 * "Primary Poet Profile".
 *
 * @since 0.1
 * @package Poets_Connections
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Football Poets Connections "Member Profile" Class.
 *
 * A class that encapsulates the sync functionality between a BuddyPress Member
 * Profile and their "Primary Poet Profile".
 *
 * The BuddyPress Member is the "author" of all of their Poet Profiles, but is
 * connected via Posts 2 Posts to their "Primary Poet Profile".
 *
 * Members can have as many Poet Profiles as they want, but can have only one
 * "Primary Poet Profile".
 *
 * @since 0.1
 */
class Poets_Connections_Profile_Sync {

	/**
	 * Plugin object.
	 *
	 * @since 0.1
	 * @access public
	 * @var Poets_Connections_Plugin
	 */
	public $plugin;

	/**
	 * "About" xProfile field ID.
	 *
	 * @since 0.1
	 * @access public
	 * @var integer
	 */
	public $about_id = 4;

	/**
	 * "Twitter" xProfile field ID.
	 *
	 * @since 0.2
	 * @access public
	 * @var integer
	 */
	public $twitter_id = 6;

	/**
	 * "Website" xProfile field ID.
	 *
	 * @since 0.2
	 * @access public
	 * @var integer
	 */
	public $website_id = 7;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param Poets_Connections_Plugin $plugin A reference to the plugin object.
	 */
	public function __construct( $plugin ) {

		// Store reference to plugin.
		$this->plugin = $plugin;

		// Init when this plugin is loaded.
		add_action( 'poets_connections/loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Initialises this class.
	 *
	 * @since 0.3.2
	 */
	public function initialise() {

		// Only do this once.
		static $done;
		if ( isset( $done ) && true === $done ) {
			return;
		}

		// Bootstrap class.
		$this->register_hooks();

		/**
		 * Broadcast that this class is now loaded.
		 *
		 * @since 0.3.2
		 */
		do_action( 'poets_connections/profile_sync/loaded' );

		// We're done.
		$done = true;

	}

	/**
	 * Register hook callbacks.
	 *
	 * @since 0.1
	 */
	private function register_hooks() {

		// Before we show Profile edit content.
		add_action( 'bp_before_profile_edit_content', [ $this, 'before_profile_edit' ] );

		// Sync content to Poet when Member Profile saved.
		add_action( 'xprofile_updated_profile', [ $this, 'update_poet' ], 9, 3 );

		// Sync content to Member Profile when Poet saved.
		add_action( 'save_post', [ $this, 'save_post_intercept' ], 100, 2 );

		// Delete User metadata when a Poet is about to be permanently deleted.
		add_action( 'before_delete_post', [ $this, 'delete_post_intercept' ], 10, 1 );

		// Override Profile updates.
		add_filter( 'bp_xprofile_format_activity_action_updated_profile', [ $this, 'filter_profile_update' ], 20, 2 );

		// Tweak Profile navigation menu.
		add_action( 'bp_actions', [ $this, 'profile_nav_tweaks' ], 100 );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Check access to Profile Group 2.
	 *
	 * @since 0.1
	 */
	public function before_profile_edit() {

		// Sanity check.
		if ( ! bp_is_user_profile_edit() ) {
			return;
		}

		// Bail if not Group 2.
		if ( 2 !== bp_get_current_profile_group_id() ) {
			return;
		}

		// Allow super admins to edit others.
		if ( get_current_user_id() !== bp_displayed_user_id() ) {
			return;
		}

		// Get their "Primary Poet Profile" Post.
		$poet = $this->plugin->config->get_primary_poet( get_current_user_id() );

		// Bail if they are already connected to a "Poet".
		if ( false !== $poet ) {
			return;
		}

		// Show Claim.
		$this->show_claim();

		// Wrap the BuddyPress form.
		add_action( 'bp_before_profile_field_content', [ $this, 'wrapper_open' ] );
		add_action( 'bp_after_profile_field_content', [ $this, 'wrapper_close' ] );

	}

	/**
	 * Show "Claim Poet" instead of Group 2.
	 *
	 * @since 0.1
	 */
	public function show_claim() {

		// Show User Profile form.
		include POETS_CONNECTIONS_PATH . 'assets/templates/profile-form.php';

	}

	/**
	 * Open form wrapper.
	 *
	 * @since 0.1
	 */
	public function wrapper_open() {
		echo '<div class="poet-not-connected">' . "\n\n";
	}

	/**
	 * Close form wrapper.
	 *
	 * @since 0.1
	 */
	public function wrapper_close() {
		echo '</div>' . "\n\n";
	}

	// -----------------------------------------------------------------------------------

	/**
	 * Stores our additional params.
	 *
	 * @since 0.1
	 *
	 * @param int $post_id The ID of the Post (or revision).
	 * @param int $post The Post object.
	 */
	public function save_post_intercept( $post_id, $post ) {

		// We don't use post_id because we're not interested in revisions.
		$this->save_post_process( $post );

	}

	/**
	 * When a Post is saved, this also saves the metadata.
	 *
	 * @since 0.1
	 *
	 * @param WP_Post $post_obj The object for the Post (or revision).
	 */
	private function save_post_process( $post_obj ) {

		// If no Post, kick out.
		if ( ! $post_obj ) {
			return;
		}

		// Is this an auto save routine?
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_obj->ID ) ) {
			return;
		}

		// Check for revision.
		if ( 'revision' === $post_obj->post_type ) {

			// Get parent.
			if ( 0 !== (int) $post_obj->post_parent ) {
				$post = get_post( $post_obj->post_parent );
			} else {
				$post = $post_obj;
			}

		} else {
			$post = $post_obj;
		}

		// Bail if not Poet Post Type.
		if ( 'poet' !== $post->post_type ) {
			return;
		}

		// Now process content.

		// Get connected User.
		$user = $this->plugin->config->get_primary_user( $post );

		// Bail if we didn't get one.
		if ( false === $user ) {
			return;
		}

		// Sync to BuddyPress xProfile.
		$this->update_user( $post, $user->ID );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Delete our additional params.
	 *
	 * @since 0.3
	 *
	 * @param int $post_id The ID of the deleted Post.
	 */
	public function delete_post_intercept( $post_id ) {

		// Get Post data.
		$post = get_post( $post_id );

		// Bail if no Post.
		if ( ! ( $post instanceof WP_Post ) ) {
			return;
		}

		// Bail if not Poet Post Type.
		if ( 'poet' !== $post->post_type ) {
			return;
		}

		// Get the User ID from Post meta since P2P data seems to have been deleted.
		$user_id = get_post_meta( $post_id, $this->plugin->config->primary_key, true );

		// Bail if this isn't a Primary Poet.
		if ( empty( $user_id ) ) {
			return;
		}

		// Sever connection.
		$this->plugin->config->disconnect_as_primary( $post_id, $user_id );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Intercept BuddyPress xProfile data on save.
	 *
	 * @since 0.1
	 *
	 * @param int   $user_id The WordPress User ID.
	 * @param array $posted_field_ids The IDs of the Fields being saved.
	 * @param bool  $errors True if there are errors, false otherwise.
	 */
	public function update_poet( $user_id, $posted_field_ids, $errors ) {

		// Make sure we have a User.
		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		// Bail if we don't.
		if ( empty( $user_id ) ) {
			return;
		}

		// Get "Primary Poet Profile" Post.
		$poet = $this->plugin->config->get_primary_poet( $user_id );

		// Don't recurse when we create and/or update the Post.
		remove_action( 'save_post', [ $this, 'save_post_intercept' ], 100, 2 );

		// If we don't have a Poet yet.
		if ( false === $poet ) {

			// Create a fresh Poet Profile.
			$poet                        = new stdClass();
			$poet->post_status           = 'publish';
			$poet->post_type             = 'poet';
			$poet->post_parent           = 0;
			$poet->comment_status        = 'closed';
			$poet->ping_status           = 'closed';
			$poet->to_ping               = ''; // Quick fix for Windows.
			$poet->pinged                = ''; // Quick fix for Windows.
			$poet->post_content_filtered = ''; // Quick fix for Windows.
			$poet->post_excerpt          = ''; // Quick fix for Windows.

			// Post title is User fullname.
			$poet->post_title = bp_get_displayed_user_fullname();

			// Save Poet Profile Post.
			$poet_id = wp_insert_post( $poet );

			// Add ID to object.
			$poet->ID = $poet_id;

			// Set as "primary" Poet Profile.
			$this->plugin->config->connect_as_primary( $poet_id, $user_id );

		}

		// Make sure we have an array.
		if ( ! is_array( $posted_field_ids ) ) {
			$posted_field_ids = [];
		}

		// Make sure array contains integers.
		array_walk(
			$posted_field_ids,
			function( &$item ) {
				$item = (int) $item;
			}
		);

		// Look for the "about" field ID.
		if ( in_array( $this->about_id, $posted_field_ids, true ) ) {

			// Get the field's content.
			$content = xprofile_get_field_data( $this->about_id, $user_id );

			// Overwrite content.
			$poet->post_content = $content;

			// Update Poet.
			wp_update_post( $poet );

		}

		// Look for the "Twitter" field ID.
		if ( in_array( $this->twitter_id, $posted_field_ids, true ) ) {

			// Get meta key.
			$db_key = poets_poets()->metaboxes->twitter_meta_key;

			// Get the field's content.
			$value = xprofile_get_field_data( $this->twitter_id, $user_id );

			// Strip @ symbol if present.
			if ( substr( $value, 0, 1 ) === '@' ) {
				$value = substr( $value, 1 );
			}

			// Strip https://twitter.com/ if present.
			if ( substr( $value, 0, 20 ) === 'https://twitter.com/' ) {
				$value = substr( $value, 20 );
			}

			// Save for this Post.
			$this->save_meta( $poet, $db_key, $value );

		}

		// Look for the "website" field ID.
		if ( in_array( $this->website_id, $posted_field_ids, true ) ) {

			// Get meta key.
			$db_key = poets_poets()->metaboxes->website_meta_key;

			// Get the field's content.
			$value = xprofile_get_field_data( $this->website_id, $user_id );

			// Save for this Post.
			$this->save_meta( $poet, $db_key, $value );

		}

	}

	/**
	 * Sync a Poet Post data to a Member's xProfile fields.
	 *
	 * @since 0.1
	 *
	 * @param int|WP_Post $post_id The ID of the Post (or the Post object).
	 * @param int         $user_id The WordPress User ID.
	 */
	public function update_user( $post_id, $user_id ) {

		// Get Post data if ID is passed.
		if ( is_numeric( $post_id ) ) {
			$post = get_post( $post_id );
		} else {
			$post = $post_id;
		}

		// Update "about".

		// Write Post content to "about" xProfile field.
		xprofile_set_field_data( $this->about_id, $user_id, $post->post_content );

		// Update "twitter".

		// Get meta key.
		$db_key = poets_poets()->metaboxes->twitter_meta_key;

		// Init value.
		$twitter = '';

		// Get value if the custom field already has one.
		$existing = get_post_meta( $post->ID, $db_key, true );
		if ( false !== $existing ) {
			$twitter = get_post_meta( $post->ID, $db_key, true );
		}

		// Write Post meta to "twitter" xProfile field.
		xprofile_set_field_data( $this->twitter_id, $user_id, $twitter );

		// Update "website".

		// Get meta key.
		$db_key = poets_poets()->metaboxes->website_meta_key;

		// Init value.
		$website = '';

		// Get value if the custom field already has one.
		$existing = get_post_meta( $post->ID, $db_key, true );
		if ( false !== $existing ) {
			$website = get_post_meta( $post->ID, $db_key, true );
		}

		// Write Post meta to "website" xProfile field.
		xprofile_set_field_data( $this->website_id, $user_id, $website );

	}

	/**
	 * Utility to automate metadata saving.
	 *
	 * @since 0.2
	 *
	 * @param WP_Post $post The WordPress Post object.
	 * @param string  $key The meta key.
	 * @param mixed   $data The data to be saved.
	 * @return mixed $data The data that was saved.
	 */
	private function save_meta( $post, $key, $data = '' ) {

		// If the custom field already has a value.
		$existing = get_post_meta( $post->ID, $key, true );
		if ( false !== $existing ) {

			// Update the data.
			update_post_meta( $post->ID, $key, $data );

		} else {

			// Add the data.
			add_post_meta( $post->ID, $key, $data, true );

		}

		// --<
		return $data;

	}

	/**
	 * Filter the Profile update activity item action string.
	 *
	 * @since 0.2
	 *
	 * @param str    $action The existing action string.
	 * @param object $activity The existing activity object.
	 * @return str $action The modified action string.
	 */
	public function filter_profile_update( $action, $activity ) {

		// Does this User have a Primary Poet?
		$connected_poet = $this->plugin->config->get_primary_poet( $activity->user_id );

		// If they do have a Primary Poet.
		if ( $connected_poet instanceof WP_Post ) {

			// Construct link.
			$name = bp_core_get_user_displayname( $activity->user_id );
			$link = '<a href="' . get_permalink( $connected_poet->ID ) . '">' . $name . '</a>';

			// Overwrite action.
			$action = sprintf(
				/* translators: %s: The link to the Poet Profile. */
				__( "%s's profile was updated", 'poets-connections' ),
				$link
			);

		}

		// --<
		return $action;

	}

	/**
	 * Manage the display of navigation items on a User's Profile menu.
	 *
	 * @since 0.2
	 */
	public function profile_nav_tweaks() {

		// Bail if it's not a Profile view.
		if ( ! bp_is_user() ) {
			return;
		}

		// Get BuddyPress object.
		$bp = buddypress();

		// Init unset list.
		$unset = [];

		// Define nav items.
		$nav_items = [
			'my-poems',
			'my-poets',
		];

		// Loop through menu items.
		foreach ( $nav_items as $key => $item ) {

			// If not on own Profile.
			if ( bp_displayed_user_id() !== bp_loggedin_user_id() ) {

				// Get current object.
				$obj = $bp->members->nav->get( $item );

				// Did we get one?
				if ( is_object( $obj ) ) {

					// Remove "My" from link.
					$title = str_replace( 'My ', '', $obj->name );

					// Resave.
					$bp->members->nav->edit_nav( [ 'name' => $title ], $item );

				}

			}

			// Item specific.
			switch ( $item ) {

				case 'my-poems':
					// Remove from User's own Profile if they have no Primary Poet.
					if ( bp_displayed_user_id() === bp_loggedin_user_id() ) {
						if ( false === $this->plugin->config->get_primary_poet( bp_loggedin_user_id() ) ) {
							$unset[] = $item;
						}
					} else {
						// Remove if displayed User has no Primary Poet.
						if ( false === $this->plugin->config->get_primary_poet( bp_displayed_user_id() ) ) {
							$unset[] = $item;
						}
					}
					break;

				case 'my-poets':
					// Remove from User's own Profile if they have no Primary Poet.
					if ( bp_displayed_user_id() === bp_loggedin_user_id() ) {
						if ( false === $this->plugin->config->get_primary_poet( bp_loggedin_user_id() ) ) {
							$unset[] = $item;
						}
					} else {
						// Remove to protect Poet Profiles.
						$unset[] = $item;
					}
					break;

			}

		}

		// Never hide items from super admins.
		if ( is_super_admin() ) {
			return;
		}

		// Never hide items from editors.
		if ( current_user_can( 'edit_posts' ) ) {
			return;
		}

		// Remove items completely.
		foreach ( $unset as $link ) {
			$bp->members->nav->delete_nav( $link );
		}

	}

}

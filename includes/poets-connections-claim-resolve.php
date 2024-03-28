<?php
/**
 * Football Poets Connections "Poet Profile Claim Resolution Form" Class.
 *
 * Handles claiming a Poet Profile.
 *
 * @since 0.1
 * @package Poets_Connections
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Football Poets Connections "Poet Profile Claim Resolution Form" Class.
 *
 * A class that encapsulates the functionality for claiming a Poet Profile. The
 * Claim is first for a User's "Primary Poet Profile" and later for any further
 * Poet Profiles that the User may have created in the past.
 *
 * @since 0.1
 */
class Poets_Connections_Resolve_Form {

	/**
	 * Plugin object.
	 *
	 * @since 0.1
	 * @access public
	 * @var Poets_Connections_Plugin
	 */
	public $plugin;

	/**
	 * Claim Resolution Callback.
	 *
	 * @since 0.1
	 * @access public
	 * @var string
	 */
	public $resolve_callback = 'claim_process';

	/**
	 * Number of Items to process per AJAX submission.
	 *
	 * @since 0.2
	 * @access public
	 * @var string
	 */
	public $batch_step = 10;

	/**
	 * Database key for the batching process.
	 *
	 * @since 0.2
	 * @access public
	 * @var string
	 */
	public $batch_key = '_poets_resolution_step';

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

	}

	/**
	 * Register WordPress hooks.
	 *
	 * @since 0.1
	 */
	public function register_hooks() {

		// Add meta boxes.
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );

		// Intercept save Poet.
		add_action( 'save_post', [ $this, 'save_post_intercept' ], 10, 2 );

		// Add AJAX handlers.
		add_action( 'wp_ajax_' . $this->resolve_callback, [ $this, 'claim_box_process' ] );

	}

	// -------------------------------------------------------------------------

	/**
	 * Adds meta boxes to admin screens.
	 *
	 * @since 0.1
	 */
	public function add_meta_boxes() {

		// Access current Post.
		global $post;

		// Sanity check.
		if ( ! ( $post instanceof WP_Post ) ) {
			return;
		}

		// If this Poet Profile has an outstanding Claim on it.
		if ( poets_connections_poet_has_pending_claim( $post->ID ) ) {

			// Add Claim Resolution meta box.
			add_meta_box(
				'poets_connections_claim',
				__( 'Claim Resolution', 'poets-connections' ),
				[ $this, 'claim_box_render' ],
				'poet',
				'side',
				'high'
			);

		}

	}

	/**
	 * Adds a meta box to CPT edit screens for resolving Claims.
	 *
	 * @since 0.1
	 *
	 * @param WP_Post $post The object for the current Post or Page.
	 */
	public function claim_box_render( $post ) {

		// Use nonce for verification.
		wp_nonce_field( 'poets_connections_claimbox', 'poets_connections_claim_nonce' );

		// Get claiming User ID.
		$user_id = get_post_meta( $post->ID, '_poet_connections_' . $this->plugin->config->claim_key, true );

		// If there's no meta, there's no Claim.
		if ( empty( $user_id ) ) {
			return;
		}

		// Default Claim type.
		$claim_type = 'standard';

		// Get the User ID of the Primary Claim meta, if there is one.
		$primary_user_id = get_post_meta( $post->ID, '_poet_connections_' . $this->plugin->config->claim_key, true );

		// If the Claim is for a Primary Poet.
		if ( ! empty( $primary_user_id ) ) {
			$claim_type = 'primary';
		}

		// Link to claiming User.
		$user_link = bp_core_get_userlink( $user_id );

		// Add our metabox javascript in the footer.
		wp_enqueue_script(
			'poets_connections_resolve_form_js',
			POETS_CONNECTIONS_URL . '/assets/js/poets-connections-resolve-form.js',
			[ 'jquery' ],
			POETS_CONNECTIONS_VERSION,
			true
		);

		// Init localisation.
		$localisation = [
			'sending'  => __( 'Resolving...', 'poets-connections' ),
			'choose'   => __( 'Please choose an option', 'poets-connections' ),
			'finished' => __( 'Claim resolved', 'poets-connections' ),
		];

		// Init settings.
		$settings = [
			'ajax_url'      => admin_url( 'admin-ajax.php' ),
			'spinner_url'   => POETS_CONNECTIONS_URL . '/assets/images/ajax-loader.gif',
			'ajax_callback' => $this->resolve_callback,
			'post_id'       => $post->ID,
			'user_id'       => $user_id,
		];

		// Localisation array.
		$vars = [
			'localisation' => $localisation,
			'settings'     => $settings,
		];

		// Localise.
		wp_localize_script(
			'poets_connections_resolve_form_js',
			'Poets_Connections_Claim_Resolver_Settings',
			$vars
		);

		// Show Claim resolution form.
		include POETS_CONNECTIONS_PATH . 'assets/templates/resolve-form.php';

	}

	// -------------------------------------------------------------------------

	/**
	 * The Claim Resolution AJAX callback.
	 *
	 * @since 0.2
	 */
	public function claim_box_process() {

		// Init AJAX return.
		$data = [
			'message' => '',
			'error'   => '',
			'status'  => '',
		];

		/*
		// Logging.
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'POST' => $_POST,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Get claiming User ID.
		$user_id = isset( $_POST['claiming_user_id'] ) ? absint( $_POST['claiming_user_id'] ) : null;
		if ( is_null( $user_id ) || ! is_int( $user_id ) ) {
			$data['error'] = __( 'Oh dear, something went wrong. No user ID was received.', 'poets-connections' );
		}

		// Make sure we have a valid User.
		$user = new WP_User( $user_id );
		if ( ! $user->exists() ) {
			$data['error'] = __( 'Oh dear, something went wrong. We couldn\'t find that user.', 'poets-connections' );
		}

		// Get claimed Poet ID.
		$poet_id = isset( $_POST['claimed_poet_id'] ) ? absint( $_POST['claimed_poet_id'] ) : null;
		if ( is_null( $poet_id ) || ! is_int( $poet_id ) ) {
			$data['error'] = __( 'Oh dear, something went wrong. No poet ID was received.', 'poets-connections' );
		}

		// Make sure we have a valid Poet.
		$poet = get_post( $poet_id );
		if ( is_null( $poet ) ) {
			$data['error'] = __( 'Oh dear, something went wrong. We couldn\'t find that poet.', 'poets-connections' );
		}

		// Get resolution.
		$decision = isset( $_POST['resolution'] ) ? sanitize_text_field( wp_unslash( $_POST['resolution'] ) ) : null;
		if ( is_null( $decision ) || ! in_array( $decision, [ 'accept', 'reject' ] ) ) {
			$data['error'] = __( 'Oh dear, something went wrong. No decision was received.', 'poets-connections' );
		}

		// Build message components if there's no error.
		if ( empty( $data['error'] ) ) {

			// Get step.
			$step = $this->get_step();

			// What do we do?
			switch ( $step ) {

				// First step.
				case 0:
					// Set finished flag.
					$data['finished'] = 'false';

					// Resolve Claim.
					$this->claim_resolve( $poet_id, $user_id );

					// Increment step.
					$this->update_step( $step );

					// Status message.
					$data['status'] = __( 'Assigned poet profile', 'poets-connections' );

					break;

				// All other steps.
				default:
					// Assign Poems and find out if process is finished.
					$done = $this->poems_process_stepped( $poet_id, $user_id, $step );

					// Finished?
					if ( ! $done ) {

						// Set finished flag.
						$data['finished'] = 'false';

						// Increment step.
						$this->update_step( $step );

					} else {

						// Set finished flag.
						$data['finished'] = 'true';

						// Delete step.
						$this->delete_step();

						// Close Claim.
						$this->claim_primary_close( $poet_id, $user_id );

					}

					// Status message.
					$data['status'] = sprintf(
						/* translators: 1: The number of the first Poem, 2: The number of the last Poem. */
						__( 'Assigning poems: %1$d - %2$d', 'poets-connections' ),
						( ( $step - 1 ) * $this->batch_step ),
						( $step * $this->batch_step )
					);

					break;

			}

		} else {

			// Status message.
			$data['status'] = __( 'Please reload the page and try again.', 'poets-connections' );

			// Delete step.
			$this->delete_step();

			// Set finished flag.
			$data['finished'] = 'true';

		}

		/*
		// Logging.
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'POST' => $_POST,
			'data' => $data,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Send data to browser.
		if ( wp_doing_ajax() ) {
			wp_send_json( $data );
		}

	}

	/**
	 * Intercept Post save procedure.
	 *
	 * This stays because people may not use the provided "Resolve" button. But
	 * unfortunately, there's no AJAX batch processing when done this way. Which
	 * is probably fine for Poets with few Poems, but not so good for those with
	 * a great many.
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
	 * When a Post is saved, this processes the Profile Claim if there is one.
	 *
	 * @since 0.1
	 *
	 * @param WP_Post $post_obj The object for the Post (or revision).
	 */
	public function save_post_process( $post_obj ) {

		// If no Post, kick out.
		if ( ! $post_obj ) {
			return;
		}

		// Bail if no choice was made.
		if ( ! isset( $_POST['claim_resolved'] ) ) {
			return;
		}

		// Authenticate.
		$nonce = isset( $_POST['poets_connections_claim_nonce'] ) ?
			sanitize_text_field( wp_unslash( $_POST['poets_connections_claim_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'poets_connections_claimbox' ) ) {
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

		// Bail if not Poem Post Type.
		if ( 'poet' !== $post->post_type ) {
			return;
		}

		// We're through - now process Claim.

		// Get claiming User ID.
		$user_id = get_post_meta( $post->ID, '_poet_connections_' . $this->plugin->config->claim_key, true );

		// If there's no meta, there's no Claim.
		if ( empty( $user_id ) ) {
			return;
		}

		/*
		// Logging.
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'POST' => $_POST,
			'user_id' => $user_id,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Get choice.
		$choice = sanitize_text_field( wp_unslash( $_POST['claim_resolved'] ) );

		// If successful, resolve Claim.
		$decision = ( 'accept' === $choice ) ? 'success' : 'failure';
		if ( 'success' === $decision ) {

			// Claim type?

			// Resolve Claim - make connections between Post and User.
			$this->claim_resolve( $post->ID, $user_id );

			// Reassign Poems to User.
			$this->poems_process_all( $post->ID, $user_id );

		}

		// Always close the Claim.
		$this->claim_primary_close( $post->ID, $user_id );

	}

	// -------------------------------------------------------------------------

	/**
	 * Resolve a pending Claim on a Poet by a WordPress User.
	 *
	 * All Poet Profiles are linked to Users through the User becoming the Post
	 * author, which enables User editing via BuddyForms.
	 *
	 * Primary Profiles are further linked to Users in three ways: there is a
	 * P2P connection between them, the User's ID is logged as metadata on the
	 * Post and vice versa.
	 *
	 * @since 0.1
	 *
	 * @param int $poet_id The ID of the Poet Post.
	 * @param int $user_id The ID of the WordPress User.
	 * @return bool False if Claim fails, true otherwise.
	 */
	private function claim_resolve( $poet_id, $user_id ) {

		// Default Claim type.
		$claim_type = 'standard';

		// Get the User ID from the Primary Claim meta, if there is one.
		$primary_user_id = get_post_meta( $post->ID, '_poet_connections_' . $this->plugin->config->claim_key, true );

		// If the Claim is for a Primary Poet.
		if ( ! empty( $primary_user_id ) ) {
			$claim_type = 'primary';
		}

		// If Primary.
		if ( 'primary' === $claim_type ) {

			// Flag User as having a Primary Profile.
			bp_update_user_meta( $user_id, '_poet_connections_' . $this->plugin->config->primary_key, $poet_id );

			// Add User ID as the Poet's Primary User.
			add_post_meta( $poet_id, '_poet_connections_' . $this->plugin->config->primary_key, $user_id, true );

			// Create connection.
			$args = [ 'date' => current_time( 'mysql' ) ];
			p2p_type( 'poets_to_users' )->connect( $poet_id, $user_id, $args );

		}

		// Unhook our save Post callbacks.
		remove_action( 'save_post', [ $this, 'save_post_intercept' ], 10, 2 );
		remove_action( 'save_post', [ $this->plugin->profile_sync, 'save_post' ], 100, 2 );

		// Set author of Poet Profile.
		$args = [
			'ID'          => $poet_id,
			'post_author' => $user_id,
		];
		wp_update_post( $args );

		// Sync to User.
		$this->plugin->profile_sync->update_user( $poet_id, $user_id );

		// Always successful for now.
		return true;

	}

	/**
	 * Close a pending Claim on a Poet by a WordPress User.
	 *
	 * @since 0.2
	 *
	 * @param int $poet_id The ID of the Poet Post.
	 * @param int $user_id The ID of the WordPress User.
	 * @return bool False if closing the Claim fails, true otherwise.
	 */
	private function claim_close( $poet_id, $user_id ) {

		// Delete Claim Post meta.
		delete_post_meta( $poet_id, '_poet_connections_' . $this->plugin->config->claim_key );

		// Get existing Poet IDs.
		$claimed_poet_ids = bp_get_user_meta( $user_id, '_poet_connections_' . $this->plugin->config->claim_key, true );

		// If already in usermeta array.
		if ( is_array( $claimed_poet_ids ) && in_array( $claimed_poet_ids ) ) {

			// Remove Poet.
			$claimed_poet_ids = array_diff( $claimed_poet_ids, [ $poet_id ] );

			// If there are none left.
			if ( count( $claimed_poet_ids ) === 0 ) {

				// Delete Claim User meta.
				bp_delete_user_meta( $user_id, '_poet_connections_' . $this->plugin->config->claim_key );

			} else {

				// Update the User's Claims.
				bp_update_user_meta( $user_id, '_poet_connections_' . $this->plugin->config->claim_key, $claimed_poet_ids );

			}

		}

		// Always successful for now.
		return true;

	}

	/**
	 * Close a pending Primary Claim on a Poet by a WordPress User.
	 *
	 * @since 0.2
	 *
	 * @param int $poet_id The ID of the Poet Post.
	 * @param int $user_id The ID of the WordPress User.
	 * @return bool False if opening the Claim fails, true otherwise.
	 */
	private function claim_primary_close( $poet_id, $user_id ) {

		// Delete Claim Post meta.
		delete_post_meta( $poet_id, '_poet_connections_' . $this->plugin->config->claim_primary_key );

		// Always successful for now.
		return true;

	}

	// -------------------------------------------------------------------------

	/**
	 * AJAX-driven method to process Poems in chunks.
	 *
	 * @since 0.1
	 *
	 * @param int $post_id the ID of the Poet Profile Post.
	 * @param int $user_id the ID of the claiming User.
	 * @param int $step The batch step to process.
	 * @return bool $finished True if there are no more to process, false otherwise.
	 */
	private function poems_process_stepped( $post_id, $user_id, $step ) {

		// Define query args.
		$query_args = [
			'connected_type'  => 'poets_to_poems',
			'connected_items' => get_post( $post_id ),
			'post_per_page'   => $this->batch_step,
			'paged'           => $step,
		];

		/*
		// Logging.
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'query_args' => $query_args,
			//'backtrace' => $trace,
		], true ) );
		*/

		// The query.
		$query = new WP_Query( $query_args );

		// Set as author of Poems if there are there any.
		if ( $query->have_posts() ) {

			// Set finished flag.
			$finished = false;

			// Loop and set up Post.
			while ( $query->have_posts() ) {
				$query->the_post();

				/*
				// Logging.
				$e = new \Exception();
				$trace = $e->getTraceAsString();
				error_log( print_r( [
					'method' => __METHOD__,
					'updating' => get_the_ID(),
					//'backtrace' => $trace,
				], true ) );
				*/

				// Assign author.
				$args = [
					'ID'          => get_the_ID(),
					'post_author' => $user_id,
				];
				wp_update_post( $args );

			}

		} else {

			// Set finished flag.
			$finished = true;

		}

		// --<
		return $finished;

	}

	/**
	 * Sends a BuddyPress private message to the claiming User informing them of the decision.
	 *
	 * TO DO ==============================
	 *
	 * @since 0.2
	 *
	 * @param int $user_id The ID of the claiming User.
	 * @param int $poet_id The ID of the claimed Poet Post.
	 * @param str $claim_type The type of Claim ('primary' or 'standard').
	 */
	private function message_send( $user_id, $poet_id, $claim_type ) {

		// Link to claiming User.
		$user_link = bp_core_get_userlink( $user_id );

		// Link to claimed Poet.
		$poet_name      = esc_html( get_the_title( $poet_id ) );
		$poet_link      = '<a href="' . get_permalink( $poet_id ) . '">' . $poet_name . '</a>';
		$poet_edit_link = '<a href="' . get_edit_post_link( $poet_id, 'display' ) . '">' . $poet_name . '</a>';

		// Define subject.
		$username = bp_core_get_user_displayname( $user_id );
		/* translators: %s: The name of the User. */
		$subject = sprintf( __( '%s has claimed a poet', 'poets-connections' ), $username );

		// Build content.
		/* translators: 1: The name of the User, 2: The name of the Poet. */
		$content  = sprintf( __( '%1$s has claimed the poet %2$s.', 'poets-connections' ), $user_link, $poet_link );
		$content .= "\n\n";
		$content .= sprintf(
			/* translators: 1: The name of the User, 2: The name of the Poet, 3: The link to the User. */
			__( 'To see whether %1$s has a valid claim on this poet profile, visit the "Edit" page for %2$s, look at the details there and accept or reject the claim. If you are not certain that the claim is valid, use this message thread to communicate with %3$s to find out more.', 'poets-connections' ),
			$username,
			$poet_edit_link,
			$user_link
		);

		// Set up message.
		$message_args = [
			'sender_id'  => $user_id,
			'thread_id'  => false,
			'recipients' => [ 1 ], // Can be an array of usernames, user_ids or mixed.
			'subject'    => $subject,
			'content'    => $content,
		];

		/*
		// Logging.
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'message_args' => $message_args,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Send now.
		messages_new_message( $message_args );

	}

	/**
	 * Get batch step.
	 *
	 * @since 0.2
	 *
	 * @return int $step The step number.
	 */
	private function get_step() {

		// If the step value doesn't exist.
		if ( 'fgffgs' === get_option( $this->batch_key, 'fgffgs' ) ) {

			// Start at the beginning.
			$step = 0;
			add_option( $this->batch_key, '0' );

		} else {

			// Use the existing value.
			$step = intval( get_option( $this->batch_key, '0' ) );

		}

		// --<
		return $step;

	}

	/**
	 * Update the batch step.
	 *
	 * @since 0.2
	 *
	 * @param int $step The current step.
	 */
	private function update_step( $step ) {

		// Increment step.
		$new_step = intval( $step ) + 1;

		// Store new step.
		update_option( $this->batch_key, (string) $new_step );

	}

	/**
	 * Delete the batch step.
	 *
	 * @since 0.2
	 */
	private function delete_step() {

		// Delete the option to start from the beginning.
		delete_option( $this->batch_key );

	}

	// -------------------------------------------------------------------------

	/**
	 * Make a WordPress User the author of all a Poet's Poems.
	 *
	 * This may be done more efficiently with a direct SQL query, since it is
	 * not being done in a chunked way, but all in one go. What this won't do
	 * is generate the BuddyPress activity items because it would skip the
	 * 'save_post' callback.
	 *
	 * @since 0.2
	 *
	 * @param int $poet_id The ID of the Poet Post.
	 * @param int $user_id The ID of the WordPress User.
	 * @return bool False if something goes wrong, true otherwise.
	 */
	private function poems_process_all( $poet_id, $user_id ) {

		// Define query args.
		$query_args = [
			'connected_type'  => 'poets_to_poems',
			'connected_items' => get_post( $poet_id ),
			'nopaging'        => true,
			'no_found_rows'   => true,
		];

		// The query.
		$query = new WP_Query( $query_args );

		// Set as author of Poems if there are there any.
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				/*
				// Logging.
				$e = new \Exception();
				$trace = $e->getTraceAsString();
				error_log( print_r( [
					'method' => __METHOD__,
					'updating' => get_the_ID(),
					//'backtrace' => $trace,
				], true ) );
				*/

				// Make the User the author.
				$args = [
					'ID'          => get_the_ID(),
					'post_author' => $user_id,
				];
				wp_update_post( $args );

			}
		}

		// Always successful for now.
		return true;

	}

}

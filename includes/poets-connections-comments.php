<?php
/**
 * Football Poets Connections Comments Class.
 *
 * Handles amends to comments so that they can be assigned to Poet Profiles.
 *
 * @since 0.3
 * @package Poets_Connections
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Football Poets Connections Comments Class.
 *
 * A class that encapsulates amends to comments so that they can be assigned to
 * Poet Profiles.
 *
 * @since 0.3
 */
class Poets_Connections_Comments {

	/**
	 * Plugin object.
	 *
	 * @since 0.3
	 * @access public
	 * @var Poets_Connections_Plugin
	 */
	public $plugin;

	/**
	 * "Football Poets" User ID.
	 *
	 * This is a generic User that is the author of all unclaimed Poems and Poet
	 * Profiles. It is also the User associated with the activity items of Poet
	 * Profiles that are not the User's Primary Profile. This is so that those
	 * activity items do not show up on the User's timeline, thus giving away
	 * the identity of the nom de plume.
	 *
	 * @since 0.1
	 * @access public
	 * @var integer
	 */
	public $generic_user_id = 5;

	/**
	 * Constructor.
	 *
	 * @since 0.3
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
		do_action( 'poets_connections/comments/loaded' );

		// We're done.
		$done = true;

	}

	/**
	 * Register hook callbacks.
	 *
	 * @since 0.3
	 */
	private function register_hooks() {

		// Add our dropdown (or hidden input) to comment form.
		add_filter( 'comment_id_fields', [ $this, 'get_comment_profile_selector' ], 10, 3 );

		// Hook into comment save process.
		add_action( 'comment_post', [ $this, 'save_comment_profile_id' ], 10, 2 );

		// Add custom comment activity.
		add_action( 'bp_activity_before_save', [ $this, 'custom_comment_activity' ], 30, 1 );

		// Override the custom comment action.
		add_filter( 'bp_activity_custom_post_type_comment_action', [ $this, 'custom_comment_action' ], 30, 2 );

		// On the front end, BuddyPress overrides the action string - doh!
		add_filter( 'bp_activity_generate_action_string', [ $this, 'custom_comment_action' ], 30, 2 );

		/*
		// Filter the comment link so replies are done in CommentPress.
		add_filter( 'bp_get_activity_comment_link', [ $this, 'filter_comment_link' ] );
		*/

		// Add filters on CommentPress reply to links.
		add_filter( 'commentpress_reply_to_para_link_text', [ $this, 'override_reply_to_text' ], 10, 2 );
		add_filter( 'commentpress_reply_to_para_link_href', [ $this, 'override_reply_to_href' ], 10, 2 );
		add_filter( 'commentpress_reply_to_para_link_onclick', [ $this, 'override_reply_to_onclick' ], 10, 1 );

		// Filter comment author.
		add_filter( 'get_comment_author', [ $this, 'override_comment_author' ], 10, 3 );

		// Filter the comment avatar.
		add_filter( 'get_avatar', [ $this, 'override_comment_avatar' ], 10, 6 );

		// Filter the CommentPress comment author link.
		add_filter( 'commentpress_get_user_link', [ $this, 'override_comment_user_link' ], 20, 3 );

	}

	/**
	 * Perform plugin activation tasks.
	 *
	 * @since 0.3
	 */
	public function activate() {

	}

	/**
	 * Perform plugin deactivation tasks.
	 *
	 * @since 0.3
	 */
	public function deactivate() {

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Inserts a dropdown (or hidden input) into the comment form.
	 *
	 * @since 0.3
	 *
	 * @param str $html Existing markup to be sent to browser.
	 * @param int $comment_id The comment ID.
	 * @param int $reply_to_id The comment ID to which this comment is a reply.
	 * @return str $html The modified markup sent to the browser.
	 */
	public function get_comment_profile_selector( $html, $comment_id, $reply_to_id ) {

		// Bail if not logged in.
		if ( ! is_user_logged_in() ) {
			return $html;
		}

		// Init args.
		$poets_args = [
			'post_type'     => 'poet',
			'post_status'   => 'publish',
			'author'        => get_current_user_id(),
			'orderby'       => 'title',
			'order'         => 'ASC',
			'nopaging'      => true,
			'no_found_rows' => true,
		];

		// Get Poets for this User.
		$poets = get_posts( $poets_args );

		// Bail if we get none.
		if ( empty( $poets ) ) {
			return $html;
		}

		// Init options.
		$options = [];

		// Build select options.
		foreach ( $poets as $poet ) {
			$options[] = '<option value="' . $poet->ID . '">' . get_the_title( $poet->ID ) . '</option>';
		}

		// Build markup.
		$html .= '<span id="poet-profile-selector">' . "\n";
		$html .= '<span>' . __( 'Post comment as', 'poets-connections' ) . ':</span>' . "\n";
		$html .= '<select id="poet-profile-id" name="poet-profile-id">' . "\n";
		$html .= implode( "\n", $options );
		$html .= '</select>' . "\n";
		$html .= '</span>' . "\n";

		// --<
		return $html;

	}

	/**
	 * When a comment is saved, save the ID of the Poet Profile that the comment
	 * was submitted by.
	 *
	 * @since 0.3
	 *
	 * @param int $comment_id The ID of the comment.
	 * @param int $comment_status The approval status of the comment.
	 */
	public function save_comment_profile_id( $comment_id, $comment_status ) {

		// We don't need to look at approval status.
		$poet_id = $this->get_poet_id_from_comment_form();

		// Bail if not a comment by a Poet.
		if ( false === $poet_id ) {
			return;
		}

		// If the custom field already has a value.
		if ( get_comment_meta( $comment_id, $this->plugin->config->comment_key, true ) !== '' ) {

			// Update the data.
			update_comment_meta( $comment_id, $this->plugin->config->comment_key, $poet_id );

		} else {

			// Add the data.
			add_comment_meta( $comment_id, $this->plugin->config->comment_key, $poet_id, true );

		}

	}

	/**
	 * Get the ID of the submitted comment Poet Profile.
	 *
	 * @since 0.3
	 *
	 * @return bool|int $poet_id The ID of the Poet Profile, false if not found.
	 */
	public function get_poet_id_from_comment_form() {

		// Init as false.
		$poet_id = false;

		// Get Poet ID if this comment has been assigned to a Profile.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( isset( $_POST['poet-profile-id'] ) && is_numeric( wp_unslash( $_POST['poet-profile-id'] ) ) ) {
			$poet_id = (int) sanitize_text_field( wp_unslash( $_POST['poet-profile-id'] ) );
		}

		// --<
		return $poet_id;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Filter the comment activity item before it is saved.
	 *
	 * For a new blog comment, BuddyPress sets the 'item_id' to the ID of the
	 * blog, and the 'secondary_item_id' to the ID of the comment.
	 *
	 * For this kind of activity item, we only replace the 'action' with one
	 * that points to the User's Poet Profile, if they have specified one.
	 *
	 * @since 0.3
	 *
	 * @param object $activity The soon-to-be-saved activity object, passed by reference.
	 */
	public function custom_comment_activity( $activity ) {

		// Only deal with comments.
		if ( 'new_poem_comment' !== $activity->type ) {
			return $activity;
		}

		// Get Poet ID from POST.
		$poet_id = $this->get_poet_id_from_comment_form();

		// Bail if not a comment by a Poet.
		if ( false === $poet_id ) {
			return $activity;
		}

		// Okay, let's get the Poet.
		$poet = get_post( $poet_id );

		// Bail if we don't get a valid Poet.
		if ( ! ( $poet instanceof WP_Post ) ) {
			return $activity;
		}

		// Get title.
		$title = get_the_title( $poet->ID );

		// Construct Poet Profile link.
		$poet_link = '<a href="' . esc_url( get_permalink( $poet->ID ) ) . '" title="' . esc_attr( $title ) . '">' . esc_html( $title ) . '</a>';

		// Overwrite the action.
		$activity->action = sprintf(
			/* translators: 1: The link to the Poet, 2: The URL of the Poem, 3: The name of the site. */
			__( '%1$s commented on a <a href="%2$s">poem</a> on %3$s', 'poets-connections' ),
			$poet_link,
			$activity->primary_link,
			get_blog_option( $activity->item_id, 'blogname' )
		);

		// Get the Primary Profile for this User.
		$primary_poet = $this->plugin->config->get_primary_poet( $activity->user_id );

		// If we get one and it's not the commenter's Primary Profile.
		if ( ( $primary_poet instanceof WP_Post ) && $poet->ID !== $primary_poet->ID ) {

			// Assign activity item to generic Football Poets User.
			$activity->user_id = $this->generic_user_id;

		}

	}

	/**
	 * Filter the comment activity item's action.
	 *
	 * This is required because the comment is on a Custom Post Type. BuddyPress
	 * handles these differently to comments on Posts and Pages.
	 *
	 * For a new blog comment, BuddyPress sets the 'item_id' to the ID of the
	 * blog, and the 'secondary_item_id' to the ID of the comment.
	 *
	 * For this kind of activity item, we only replace the 'action' with one
	 * that points to the User's Poet Profile, if they have specified one.
	 *
	 * @since 0.3
	 *
	 * @param str    $action The existing activity action.
	 * @param object $activity The soon-to-be-saved activity object, passed by reference.
	 * @return str $action The modified activity action.
	 */
	public function custom_comment_action( $action, $activity ) {

		// Only deal with comments.
		if ( 'new_poem_comment' !== $activity->type ) {
			return $action;
		}

		// Get Poet ID from POST.
		$poet_id = $this->get_poet_id_from_comment_form();

		// If no Poet found in POST, we may be rendering the item.
		if ( false === $poet_id ) {

			// Get Poet ID from comment meta.
			$poet_id = get_comment_meta( $activity->secondary_item_id, $this->plugin->config->comment_key, true );

			// Bail if it's empty.
			if ( empty( $poet_id ) ) {
				return $action;
			}

		}

		// Okay, let's get the Poet.
		$poet = get_post( $poet_id );

		// Bail if we don't get a valid Poet.
		if ( is_null( $poet ) ) {
			return $action;
		}

		// Get title.
		$title = get_the_title( $poet->ID );

		// Construct Poet Profile link.
		$poet_link = '<a href="' . get_permalink( $poet->ID ) . '" title="' . esc_attr( $title ) . '">' . $title . '</a>';

		// Overwrite the action.
		$action = sprintf(
			/* translators: 1: The link to the Poet, 2: The URL of the Poem, 3: The name of the site. */
			__( '%1$s commented on a <a href="%2$s">poem</a> on %3$s', 'poets-connections' ),
			$poet_link,
			$activity->primary_link,
			get_blog_option( $activity->item_id, 'blogname' )
		);

		// --<
		return $action;

	}

	/**
	 * Filter the comment reply link on activity items.
	 *
	 * This is called during the loop, so we can assume that the activity item
	 * API will work.
	 *
	 * @since 0.3
	 *
	 * @param str $link The existing comment reply link.
	 * @return str $link The modified comment reply link.
	 */
	public function filter_comment_link( $link ) {

		// Get type of activity.
		$type = bp_get_activity_action_name();

		return $link;

		/*
		// Our custom activity types.
		$types = [ 'new_poem_comment' ];

		// Not one of ours?
		if ( ! in_array( $type, $types ) ) {
			return $link;
		}
		*/

		/*
		// Old code.
		if ( $type == 'new_groupsite_comment' ) {
			$link_text = __( 'Reply', 'bpwpapers' );
		}

		// Construct new link to actual comment.
		$link = '<a href="' . bp_get_activity_feed_item_link() . '" class="button acomment-reply bp-primary-action">' .
			$link_text .
		'</a>';
		*/

		/*
		// --<
		return bp_get_activity_feed_item_link();
		*/

	}

	/**
	 * Override content of the reply to link.
	 *
	 * @since 0.3
	 *
	 * @param string $link_text The full text of the reply to link.
	 * @param string $paragraph_text Paragraph text.
	 * @return string $link_text Updated content of the reply to link.
	 */
	public function override_reply_to_text( $link_text, $paragraph_text ) {

		// If not logged in...
		if ( ! is_user_logged_in() ) {

			// Is registration allowed?
			if ( bp_get_signup_allowed() ) {
				$link_text = __( 'Create an account to leave a comment', 'poets-connections' );
			} else {
				$link_text = __( 'Login to leave a comment', 'poets-connections' );
			}

			// Show helpful message.
			return apply_filters( 'poets_connections_override_reply_to_text_denied', $link_text, $paragraph_text );

		}

		// --<
		return $link_text;

	}

	/**
	 * Override content of the reply to link target.
	 *
	 * @since 0.3
	 *
	 * @param string $href The existing target URL.
	 * @param string $text_sig The text signature of the paragraph.
	 * @return string $href Overridden target URL.
	 */
	public function override_reply_to_href( $href, $text_sig ) {

		// If not logged in.
		if ( ! is_user_logged_in() ) {

			// Is registration allowed?
			if ( bp_get_signup_allowed() ) {
				$href = bp_get_signup_page();
			} else {
				$href = wp_login_url( get_permalink() );
			}

			// --<
			return apply_filters( 'poets_connections_override_reply_to_href_denied', $href );

		}

		// --<
		return $href;

	}

	/**
	 * Override content of the reply to link.
	 *
	 * @since 0.3
	 *
	 * @param string $onclick The reply-to onclick attribute.
	 * @return string $onclick The modified reply-to onclick attribute.
	 */
	public function override_reply_to_onclick( $onclick ) {

		// --<
		return '';

	}

	/**
	 * Override the returned comment author name.
	 *
	 * @since 0.3
	 *
	 * @param string     $author The comment author's username.
	 * @param int        $comment_id The comment ID.
	 * @param WP_Comment $comment The comment object.
	 */
	public function override_comment_author( $author, $comment_id, $comment ) {

		// Get Poet ID from comment meta.
		$poet_id = get_comment_meta( $comment_id, $this->plugin->config->comment_key, true );

		// Bail if it's empty.
		if ( empty( $poet_id ) ) {
			return $author;
		}

		// Get avatar source.
		$poet = get_post( $poet_id );

		// Bail if it's not a proper Post.
		if ( ! ( $poet instanceof WP_Post ) ) {
			return $author;
		}

		// --<
		return get_the_title( $poet );

	}

	/**
	 * Override the comment author avatar.
	 *
	 * @since 0.3
	 *
	 * @param string            $avatar The avatar path passed to 'get_avatar'.
	 * @param int|string|object $comment A User ID, email address, or comment object.
	 * @param int               $size Size of the avatar image ('thumb' or 'full').
	 * @param string            $default URL to a default image to use if no avatar is available.
	 * @param string            $alt Alternate text to use in image tag. Default: ''.
	 * @param array             $args Arguments passed to get_avatar_data(), after processing.
	 * @return string $avatar The avatar path if found, else the original avatar path.
	 */
	public function override_comment_avatar( $avatar, $comment, $size, $default, $alt = '', $args = [] ) {

		// Bail if not a comment avatar request.
		if ( ! ( $comment instanceof WP_Comment ) ) {
			return $avatar;
		}

		// Get Poet ID from comment meta.
		$poet_id = get_comment_meta( $comment->comment_ID, $this->plugin->config->comment_key, true );

		// Bail if it's empty.
		if ( empty( $poet_id ) ) {
			return $avatar;
		}

		// Get avatar source.
		$poet = get_post( $poet_id );

		// Bail if it's not a proper Post.
		if ( ! ( $poet instanceof WP_Post ) ) {
			return $avatar;
		}

		// Get User's Primary Poet.
		$primary_poet = poets_connections()->config->get_primary_poet( $comment->user_id );

		// Bail if it's not a proper Post.
		if ( ! ( $primary_poet instanceof WP_Post ) ) {
			return $avatar;
		}

		// If this is the Primary Poet keep Profile avatar.
		if ( $poet->ID === $primary_poet->ID ) {
			return $avatar;
		}

		// Get Poet Profile thumbnail.
		$post_thumbnail = get_the_post_thumbnail( $poet->ID, [ 32, 32 ], [ 'class' => 'avatar' ] );

		// Did we get one?
		if ( ! empty( $post_thumbnail ) ) {

			// We're done.
			return $post_thumbnail;

		}

		// Fall back to mystery man.
		$src = POETS_CONNECTIONS_URL . '/assets/images/subbuteo-shakespeare-mid.jpg';
		/* translators: %s: The name of the Poet. */
		$alt            = sprintf( __( 'Profile picture of %s', 'poets-connections' ), get_the_title( $poet ) );
		$post_thumbnail = '<img src="' . $src . '" class="avatar avatar-50" width="36" height="36" alt="' . $alt . '" />';
		$avatar         = '<a href="' . get_permalink( $poet->ID ) . '">' . $post_thumbnail . '</a>';

		// --<
		return $avatar;

	}

	/**
	 * Override target of the comment User link.
	 *
	 * @since 0.3
	 *
	 * @param string $link The target of the comment User link.
	 * @param object $user The WordPress User object.
	 * @param object $comment The WordPress comment object.
	 * @return string $link The modified target of the comment User link.
	 */
	public function override_comment_user_link( $link, $user, $comment ) {

		// Get Poet ID from comment meta.
		$poet_id = get_comment_meta( $comment->comment_ID, $this->plugin->config->comment_key, true );

		// Bail if it's empty.
		if ( empty( $poet_id ) ) {
			return $link;
		}

		// Get avatar source.
		$poet = get_post( $poet_id );

		// Bail if it's not a proper Post.
		if ( ! ( $poet instanceof WP_Post ) ) {
			return $link;
		}

		// --<
		return get_permalink( $poet );

	}

}

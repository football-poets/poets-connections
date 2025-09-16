<?php
/**
 * Football Poets Connections Functions.
 *
 * These are globallly available functions that relate to connections.
 *
 * @since 0.1
 * @package Poets_Connections
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Check if a Poet has a pending Claim from a WordPress User.
 *
 * @since 0.2
 *
 * @param int $poet_id The ID of the Poet Post.
 * @return bool True if there's a Claim, false otherwise.
 */
function poets_connections_poet_has_pending_claim( $poet_id ) {

	// Get plugin.
	$pc = poets_connections();

	// Get claiming User IDs for this Poet.
	$user_id         = get_post_meta( $poet_id, $pc->config->claim_key, true );
	$primary_user_id = get_post_meta( $poet_id, $pc->config->claim_primary_key, true );

	// Return false if there's no Claim.
	if ( empty( $primary_user_id ) && empty( $user_id ) ) {
		return false;
	}

	// There's a Claim.
	return true;

}

/**
 * Check if a Poet has a pending Primary Claim from a WordPress User.
 *
 * @since 0.1
 *
 * @param int $poet_id The ID of the Poet Post.
 * @return bool|int False if not currently claimed, User ID otherwise.
 */
function poets_connections_poet_has_pending_primary_claim( $poet_id ) {

	// Get plugin.
	$pc = poets_connections();

	// Get claiming User ID for this Poet.
	$claiming_user_id = get_post_meta( $poet_id, $pc->config->claim_primary_key, true );

	// Return false if there's no Claim.
	if ( empty( $claiming_user_id ) ) {
		return false;
	}

	// Return User ID.
	return $claiming_user_id;

}

/**
 * Check if a WordPress User has a pending Primary Claim on a Poet.
 *
 * @since 0.1
 *
 * @param int $user_id The ID of the WordPress User.
 * @return bool|int False if no currently claimed Poet, Post ID otherwise.
 */
function poets_connections_user_has_pending_primary_claim( $user_id ) {

	// Get plugin.
	$pc = poets_connections();

	// Get Poet ID for this User.
	$claimed_poet_id = bp_get_user_meta( $user_id, $pc->config->claim_primary_key, true );

	// Did we get one?
	if ( ! empty( $claimed_poet_id ) ) {
		return $claimed_poet_id;
	}

	// Fallback.
	return false;

}

/**
 * Check if a Poet has a pending Primary Claim from a WordPress User.
 *
 * @since 0.1
 *
 * @param int $poet_id The ID of the Poet Post.
 * @param int $user_id The ID of the WordPress User.
 * @return bool False if no Poet is not claimed by User, true otherwise.
 */
function poets_connections_poet_is_claimed_as_primary_by_user( $poet_id, $user_id ) {

	// Get plugin.
	$pc = poets_connections();

	// Get claiming User ID for this Poet.
	$claiming_user_id = get_post_meta( $poet_id, $pc->config->claim_primary_key, true );

	// Return false if there's no Claim.
	if ( false === $claiming_user_id ) {
		return false;
	}

	// Return false if the Claim is not from the current User.
	if ( (int) $claiming_user_id !== (int) $user_id ) {
		return false;
	}

	// There is a Claim.
	return true;

}

/**
 * Get the Poet Profiles for a Poem.
 *
 * @since 0.2
 *
 * @param int $poem_id The ID of the Poem.
 * @return array|bool The array of Poet Profile Posts or false if none found.
 */
function poets_connections_get_poets_for_poem( $poem_id ) {

	// Pass to plugin and return.
	return poets_connections()->config->get_poets_for_poem( $poem_id );

}

/**
 * Show the thumbnail for the Poet Profile on activity items.
 *
 * @since 0.2
 */
function poets_connections_get_poet_avatar() {

	global $activities_template;

	/*
	 * Within the activity comment loop, the current activity should be set
	 * to current_comment, otherwise, just use activity.
	 */
	$current_activity_item = isset( $activities_template->activity->current_comment ) ?
		$activities_template->activity->current_comment :
		$activities_template->activity;

	/*
	// Logging.
	$e = new \Exception();
	$trace = $e->getTraceAsString();
	error_log( print_r( [
		'method' => __METHOD__,
		'current_activity_item' => $current_activity_item,
		//'backtrace' => $trace,
	], true ) );
	*/

	// Filter Poems.
	if ( 'new_poem' === $current_activity_item->type ) {
		poets_connections_get_poet_avatar_poem_new( $current_activity_item );
		return;
	}

	// Filter Poem comments.
	if ( 'new_poem_comment' === $current_activity_item->type ) {
		poets_connections_get_poet_avatar_poem_comment_new( $current_activity_item );
		return;
	}

	// Filter Profile updates.
	if ( 'updated_profile' === $current_activity_item->type ) {

		// Get plugin.
		$pc = poets_connections();

		// Does this User have a Primary Poet?
		$connected_poet = $pc->config->get_primary_poet( $current_activity_item->user_id );

		// If they do have a Primary Poet.
		if ( $connected_poet instanceof WP_Post ) {

			// Show Primary avatar but link to Poet.
			?>
			<a href="<?php echo esc_url( get_permalink( $connected_poet->ID ) ); ?>"><?php bp_activity_avatar(); ?></a>
			<?php

			// We're done.
			return;

		}

	}

	// Fall back to standard avatar.
	?>
	<a href="<?php bp_activity_user_link(); ?>"><?php bp_activity_avatar(); ?></a>
	<?php

}

/**
 * Show the thumbnail for the Poet Profile on a new Poem.
 *
 * @since 0.3
 *
 * @param obj $item The current activity item.
 */
function poets_connections_get_poet_avatar_poem_new( $item ) {

	// Get Poet IDs.
	$poets = poets_connections_get_poets_for_poem( $item->secondary_item_id );

	/*
	// Logging.
	$e = new \Exception();
	$trace = $e->getTraceAsString();
	error_log( print_r( [
		'method' => __METHOD__,
		'poets' => $poets,
		//'backtrace' => $trace,
	], true ) );
	*/

	// Sanity check.
	if ( ! empty( $poets ) ) {

		// Use first as avatar source.
		$poet = array_pop( $poets );

		// Get User's Primary Poet.
		$primary_poet = poets_connections()->config->get_primary_poet( $item->user_id );

		/*
		// Logging.
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'poets' => $poets,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Show the avatar if it has one.
		poets_connections_poet_avatar_render( $primary_poet, $poet );

		// We're done.
		return;

	}

	// All other cases.
	?>
	<a href="<?php bp_activity_user_link(); ?>"><?php bp_activity_avatar(); ?></a>
	<?php

}

/**
 * Show the thumbnail for the Poet Profile on a new Poem comment.
 *
 * @since 0.3
 *
 * @param obj $item The current activity item.
 */
function poets_connections_get_poet_avatar_poem_comment_new( $item ) {

	// Get plugin.
	$pc = poets_connections();

	// Get Poet ID from comment meta.
	$poet_id = get_comment_meta( $item->secondary_item_id, $pc->config->comment_key, true );

	// Did we get one?
	if ( ! empty( $poet_id ) ) {

		// Get avatar source.
		$poet = get_post( $poet_id );

		// Get User's Primary Poet.
		$primary_poet = poets_connections()->config->get_primary_poet( $item->user_id );

		// Show the avatar if it has one.
		poets_connections_poet_avatar_render( $primary_poet, $poet );

		// We're done.
		return;

	}

	// All other cases.
	?>
	<a href="<?php bp_activity_user_link(); ?>"><?php bp_activity_avatar(); ?></a>
	<?php

}

/**
 * Get the thumbnail for the Poet Profile on a new Poem comment.
 *
 * @since 0.3
 *
 * @param WP_Post $primary_poet The Primary Poet object.
 * @param WP_Post $poet The Poet object.
 * @return str $avatar The Poet avatar wrapped in an anchor tag.
 */
function poets_connections_poet_avatar_get( $primary_poet, $poet ) {

	// Sanity checks.
	if ( ! ( $primary_poet instanceof WP_Post ) ) {
		return '';
	}
	if ( ! ( $poet instanceof WP_Post ) ) {
		return '';
	}

	// If it is the Primary Poet.
	if ( $poet->ID === $primary_poet->ID ) {

		// Show Primary avatar but link to Poet.
		$avatar = '<a href="' . get_permalink( $poet->ID ) . '">' . bp_get_activity_avatar() . '</a>';

		// We're done.
		return $avatar;

	}

	// Get Poet Profile thumbnail.
	$post_thumbnail = get_the_post_thumbnail( $poet->ID, [ 70, 70 ], [ 'class' => 'avatar' ] );

	// Did we get one?
	if ( ! empty( $post_thumbnail ) ) {

		// Show it.
		$avatar = '<a href="' . get_permalink( $poet->ID ) . '">' . $post_thumbnail . '</a>';

		// We're done.
		return $avatar;

	}

	// Fall back to mystery man.
	$src = POETS_CONNECTIONS_URL . '/assets/images/subbuteo-shakespeare-mid.jpg';
	/* translators: %s: The name of the Poet. */
	$alt            = sprintf( __( 'Profile picture of %s', 'poets-connections' ), get_the_title( $poet ) );
	$post_thumbnail = '<img src="' . $src . '" class="avatar avatar-50" width="50" height="50" alt="' . $alt . '" />';
	$avatar         = '<a href="' . get_permalink( $poet->ID ) . '">' . $post_thumbnail . '</a>';

	// --<
	return $avatar;

}

/**
 * Show the thumbnail for the Poet Profile on a new Poem comment.
 *
 * @since 0.3
 *
 * @param WP_Post $primary_poet The Primary Poet object.
 * @param WP_Post $poet The Poet object.
 */
function poets_connections_poet_avatar_render( $primary_poet, $poet ) {

	// If it is the Primary Poet.
	if (
		$primary_poet instanceof WP_Post &&
		$poet instanceof WP_Post &&
		$poet->ID === $primary_poet->ID
	) {

		// Show Primary avatar but link to Poet.
		?>
		<a href="<?php echo esc_url( get_permalink( $poet->ID ) ); ?>"><?php bp_activity_avatar(); ?></a>
		<?php

		// We're done.
		return;

	}

	// Sanity check Poet.
	if ( $poet instanceof WP_Post ) {

		// Get Poet Profile thumbnail.
		$post_thumbnail = get_the_post_thumbnail( $poet->ID, [ 70, 70 ], [ 'class' => 'avatar' ] );

		// Did we get one?
		if ( ! empty( $post_thumbnail ) ) {

			// Show it.
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<a href="' . esc_url( get_permalink( $poet->ID ) ) . '">' . $post_thumbnail . '</a>';

			// We're done.
			return;

		}

	}

	// Fall back to mystery man.
	$src = POETS_CONNECTIONS_URL . '/assets/images/subbuteo-shakespeare-mid.jpg';
	/* translators: %s: The name of the Poet. */
	$alt            = sprintf( esc_html__( 'Profile picture of %s', 'poets-connections' ), get_the_title( $poet ) );
	$post_thumbnail = '<img src="' . esc_url( $src ) . '" class="avatar avatar-50" width="50" height="50" alt="' . esc_attr( $alt ) . '" />';
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<a href="' . esc_url( get_permalink( $poet ) ) . '">' . $post_thumbnail . '</a>';

}

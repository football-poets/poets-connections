<?php
/**
 * Football Poets Connections "Member Profile Form" Template.
 *
 * @since 0.1
 * @package Poets_Connections
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div class="poet-profile-linking-options">

	<h4><?php esc_html_e( 'Your primary profile as a poet', 'poets-connections' ); ?></h4>

	<p><?php _e( 'It looks like you haven\'t got a primary profile as a poet yet. To publish poems on this site, you\'re going to need one so people know who you are. Once you\'ve created (or claimed) a primary profile, you\'ll be able to create (or claim) as many <em>noms de plume</em> (pen names) as you like. The real identity of your <em>noms de plume</em> will only be known by you the site editors.', 'poets-connections' ); ?> <em><?php esc_html_e( 'So let\'s get started!', 'poets-connections' ); ?></em></p>

	<h5><?php esc_html_e( '1. If you have already posted a poem to this site', 'poets-connections' ); ?></h5>

	<p><?php echo sprintf( __( 'If you have already posted a poem to this site, you may find your primary profile on the site already. To find out, search for your name or <em>nom de plume</em> below. If you find your profile listed, click the name of the poet to visit their profile, check that the poems by that poet are yours and click the "Claim this profile" button to send a message to a site editor. If they are satisfied that you are indeed one and the same person, they will connect you with the profile you have claimed. All poems associated with that poet profile will be transferred to you.', 'poets-connections' ), get_post_type_archive_link( 'poet' ) ); ?></p>

	<div class="poet-search">
		<h3><?php esc_html_e( 'Search Poets', 'poets-connections' ); ?></h3>
		<form role="search" action="<?php echo get_post_type_archive_link( 'poet' ); ?>" method="get" id="searchform">
			<input type="hidden" name="post_type" value="poet" />
			<input type="text" name="s" placeholder="<?php esc_attr_e( 'Search Poets', 'poets-connections' ); ?>"/>
			<input type="submit" alt="<?php esc_attr_e( 'Search', 'poets-connections' ); ?>" value="<?php esc_attr_e( 'Search', 'poets-connections' ); ?>" />
		</form>
	</div>

	<h5><?php esc_html_e( '2. If you have never posted a poem to this site', 'poets-connections' ); ?></h5>

	<p><?php esc_html_e( 'If you have not posted a poem to this site before (or cannot find an existing profile by following the instructions above) fill out the form below to create a public profile of yourself as a poet. Your profile will have the name that you signed up with.', 'poets-connections' ); ?></p>

</div>

<?php
/**
 * Football Poets Connections "Claim Form" Template.
 *
 * @since 0.1
 * @package Poets_Connections
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div class="claim-this-poet">

	<form id="claim_form" class="claim_form" method="post" action="">
		<div class="claim_text">
			<p>
			<?php if ( $show_primary ) : ?>
				<?php esc_html_e( 'It looks like you don\'t have a main poet profile yet. Is this yours?', 'poets-connections' ); ?>
				<input type="hidden" id="claim_type" value="primary" />
			<?php else : ?>
				<?php
				echo sprintf(
					/* translators: 1: The opening em tag, 2: The closing em tag. */
					esc_html__( 'Is this profile one of your %1$snoms de plume%2$s?', 'poets-connections' ),
					'<em>',
					'</em>'
				);
				?>
				<input type="hidden" id="claim_type" value="standard" />
			<?php endif; ?>
			</p>
		</div>
		<input type="hidden" id="claiming_user_id" value="<?php echo esc_attr( get_current_user_id() ); ?>" />
		<input type="hidden" id="poet_id" value="<?php echo get_the_ID(); ?>" />
		<p class="claim_actions">
			<input id="claim_submit" type="submit" value="<?php esc_attr_e( 'Claim this profile', 'poets-connections' ); ?>" />
			<?php if ( $show_primary ) : ?>
				<a href="<?php echo esc_url( $profile_edit_link ); ?>" class="create-new-profile"><?php esc_html_e( 'Create new profile', 'poets-connections' ); ?></a>
				<input type="hidden" id="claim_stop" value="no" />
			<?php else : ?>
				<a href="#" id="claim_stop" class="button"><?php esc_html_e( 'Stop claiming profiles', 'poets-connections' ); ?></a>
			<?php endif; ?>
		</p>
	</form>

</div>

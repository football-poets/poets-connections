<?php
/**
 * Football Poets Connections "Claim Resolution Form" Template.
 *
 * The resolver buttons should probably be injected with Javascript since they
 * don't do anything unless Javascript is enabled.
 *
 * @since 0.1
 * @package Poets_Connections
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<?php if ( 'primary' === $claim_type ) : ?>
	<input type="hidden" id="claim_type" value="primary" />
<?php else : ?>
	<input type="hidden" id="claim_type" value="standard" />
<?php endif; ?>

<p class="resolver-description">
	<?php

	echo sprintf(
		/* translators: %s: The link to the User. */
		__( '%s has claimed this poet. To resolve this claim, select an option below:', 'poets-connections' ),
		$user_link
	);

	?>
</p>

<p class="resolver-options">
	<label class="resolver-label" for="claim_accept">
		<input type="radio" id="claim_accept" name="claim_resolved" value="accept" />
		<?php esc_html_e( 'Accept claim', 'poets-connections' ); ?>
	</label><br>
	<label class="resolver-label" for="claim_reject">
		<input type="radio" id="claim_reject" name="claim_resolved" value="reject" />
		<?php esc_html_e( 'Reject claim', 'poets-connections' ); ?>
	</label>
</p>

<div class="resolver-feedback"></div>

<p class="resolver-buttons"><a id="resolver-button" class="button" href="#"><?php esc_html_e( 'Resolve', 'poets-connections' ); ?></a></p>

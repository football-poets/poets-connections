/**
 * Poets Connections "Claim Form" Javascript.
 *
 * Implements claim form functionality on Poet Profile pages.
 *
 * @package Poets_Connections
 */



/**
 * Create Poets Connections Claim Form object.
 *
 * This works as a "namespace" of sorts, allowing us to hang properties, methods
 * and "sub-namespaces" from it.
 *
 * @since 0.2
 */
var Poets_Connections_Claim_Form = Poets_Connections_Claim_Form || {};



/**
 * Pass the jQuery shortcut in.
 *
 * @since 0.2
 *
 * @param {Object} $ The jQuery object.
 */
( function( $ ) {

	/**
	 * Create Settings Object.
	 *
	 * @since 0.2
	 */
	Poets_Connections_Claim_Form.settings = new function() {

		// prevent reference collisions
		var me = this;

		/**
		 * Initialise Settings.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.2
		 */
		this.init = function() {

			// init localisation
			me.init_localisation();

			// init settings
			me.init_settings();

		};

		/**
		 * Do setup when jQuery reports that the DOM is ready.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.2
		 */
		this.dom_ready = function() {

		};

		// init localisation array
		me.localisation = [];

		/**
		 * Init localisation from settings object.
		 *
		 * @since 0.2
		 */
		this.init_localisation = function() {
			if ( 'undefined' !== typeof Poets_Connections_Claim_Form_Settings ) {
				me.localisation = Poets_Connections_Claim_Form_Settings.localisation;
			}
		};

		/**
		 * Getter for localisation.
		 *
		 * @since 0.2
		 *
		 * @param {String} The identifier for the desired localisation string
		 * @return {String} The localised string
		 */
		this.get_localisation = function( identifier ) {
			return me.localisation[identifier];
		};

		// init settings array
		me.settings = [];

		/**
		 * Init settings from settings object.
		 *
		 * @since 0.2
		 */
		this.init_settings = function() {
			if ( 'undefined' !== typeof Poets_Connections_Claim_Form_Settings ) {
				me.settings = Poets_Connections_Claim_Form_Settings.settings;
			}
		};

		/**
		 * Getter for retrieving a setting.
		 *
		 * @since 0.2
		 *
		 * @param {String} The identifier for the desired setting
		 * @return The value of the setting
		 */
		this.get_setting = function( identifier ) {
			return me.settings[identifier];
		};

	};

	/**
	 * Create Form Object.
	 *
	 * @since 0.2
	 */
	Poets_Connections_Claim_Form.form = new function() {

		// prevent reference collisions
		var me = this;

		/**
		 * Initialise Form.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.2
		 */
		this.init = function() {

		};

		/**
		 * Do setup when jQuery reports that the DOM is ready.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.2
		 */
		this.dom_ready = function() {

			// set up instance
			me.setup();

			// enable listeners
			me.listeners();

		};

		/**
		 * Set up Form instance.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.2
		 */
		this.setup = function() {

			// assign properties
			me.container = $( '.claim-this-poet' ),
			me.claim_form = $('#claim_form'),
			me.claim_actions = $('.claim_actions'),
			me.claim_submit = $('#claim_submit'),
			me.claim_text = $('.claim_text'),
			me.claim_submitting = false;

			// create AJAX spinner
			me.claim_submit.after(
				'<img src="' +
				Poets_Connections_Claim_Form.settings.get_setting( 'spinner_url' ) +
				'" class="poets-ajax-spinner poets-ajax-spinner-sending" alt="' +
				Poets_Connections_Claim_Form.settings.get_localisation( 'sending' ) +
				'" />'
			);

			// grab the new element and hide
			me.spinner = $('.poets-ajax-spinner-sending');
			me.spinner.hide();

			// bail if not standard claim form
			if ( 'standard' != Poets_Connections_Claim_Form.settings.get_setting( 'claim_type' ) ) {
				return;
			}

			// find stop button
			me.claim_stop = $('#claim_stop');

			// create AJAX spinner
			me.claim_stop.after(
				'<img src="' +
				Poets_Connections_Claim_Form.settings.get_setting( 'spinner_url' ) +
				'" class="poets-ajax-spinner poets-ajax-spinner-stopping" alt="' +
				Poets_Connections_Claim_Form.settings.get_localisation( 'stopping' ) +
				'" />'
			);

			// grab the new element and hide
			me.spinner_stop = $('.poets-ajax-spinner-stopping');
			me.spinner_stop.hide();

		};

		/**
		 * Initialise listeners.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.2
		 */
		this.listeners = function() {

			/**
			 * Form submission method.
			 *
			 * @since 0.1
			 */
			me.claim_form.on( 'submit', function( event ) {

				// always prevent default
				if (event.preventDefault) {
					event.preventDefault();
				}

				// check flag
				if ( me.claim_submitting === true ) {
					return false;
				}

				// set global flag
				me.claim_submitting = true;

				// UI changes
				me.spinner.show();
				me.claim_submit.prop( 'disabled', 'disabled' );
				me.claim_submit.prop( 'value', Poets_Connections_Claim_Form.settings.get_localisation( 'sending' ) );
				if ( 'standard' == Poets_Connections_Claim_Form.settings.get_setting( 'claim_type' ) ) {
					me.claim_stop.hide();
				}

				// send claim
				me.send_claim();

				// --<
				return false;

			});

			// bail if not standard claim form
			if ( 'standard' != Poets_Connections_Claim_Form.settings.get_setting( 'claim_type' ) ) {
				return;
			}

			/**
			 * Add a click event listener to stop claim forms from appearing in future.
			 *
			 * @param {Object} event The event object
			 */
			me.claim_stop.on( 'click', function( event ) {

				// prevent form submission
				if ( event.preventDefault ) {
					event.preventDefault();
				}

				// UI changes
				me.spinner_stop.show();
				me.claim_stop.text( Poets_Connections_Claim_Form.settings.get_localisation( 'stopping' ) );

				// send
				me.send_stop();

			});

		};

		/**
		 * Send AJAX request.
		 *
		 * @since 0.2.8
		 */
		this.send_claim = function() {

			// use post method
			$.post(

				// set URL
				Poets_Connections_Claim_Form.settings.get_setting( 'ajax_url' ),

				// add data
				{

					// send WordPress token
					action: 'claim_poet',

					// send user ID
					claiming_user_id: me.claim_form.find( '#claiming_user_id' ).val(),

					// send poet ID
					claimed_poet_id: me.claim_form.find( '#poet_id' ).val(),

					// send claim type
					claim_type: me.claim_form.find( '#claim_type' ).val(),

				},

				// callback
				function( data, textStatus ) {

					//console.log( data );
					//console.log( textStatus );

					// if success
					if ( textStatus == 'success' ) {

						if ( data.error != '' ) {
							me.claim_text.html( '<span class="error">' + data.error + '</span>' );
						} else {
							me.claim_text.html( '<span class="pending">' + data.message + '</span>' );
						}

					} else {

						// show error
						if ( console.log ) {
							console.log( textStatus );
						}

					}

					// hide the spinner, submit button and text
					me.spinner.hide();
					me.claim_submit.hide();
					me.claim_actions.hide();

					// reset flag
					me.claim_submitting = false;

					// reset form
					setTimeout( function() {
						me.reset( data );
					}, 5000 );

					// --<
					return false;

				},

				// expected format
				'json'

			);

		};

		/**
		 * Send AJAX request.
		 *
		 * @since 0.2.8
		 */
		this.send_stop = function() {

			// use post method
			$.post(

				// set URL
				Poets_Connections_Claim_Form.settings.get_setting( 'ajax_url' ),

				// add data
				{

					// send WordPress token
					action: 'claim_stop',

					// send user ID
					claiming_user_id: me.claim_form.find( '#claiming_user_id' ).val(),

					// send claim stop
					claim_stop: 'yes'

				},

				// callback
				function( data, textStatus ) {

					//console.log( data );
					//console.log( textStatus );

					// if success
					if ( textStatus == 'success' ) {

						if ( data.error != '' ) {
							me.claim_text.html( '<span class="error">' + data.error + '</span>' );
						} else {
							me.claim_text.html( '<span class="pending">' + data.message + '</span>' );
						}

					} else {

						// show error
						if ( console.log ) {
							console.log( textStatus );
						}

					}

					// hide the spinner, submit button and text
					me.spinner.hide();
					me.claim_submit.hide();
					me.claim_actions.hide();

					// reset flag
					me.claim_submitting = false;

					// reset form
					setTimeout( function() {
						me.reset( data );
					}, 3000 );

					// --<
					return false;

				},

				// expected format
				'json'

			);

		};

		/**
		 * Reset claim form and hide or show feedback.
		 *
		 * @since 0.2
		 *
		 * @param {Array} data The JSON data returned by the server
		 */
		this.reset = function( data ) {

			// hide the thing
			me.container.slideUp( 'slow', function() {

				// is there a status message?
				if ( data.status != '' ) {

					// wrap the message
					me.claim_text.html('<span class="status">' + data.status + '</span>');

					// change class
					if ( data.error != '' ) {
						me.container.addClass( 'error' );
					} else {
						me.container.addClass( 'pending' );
					}

					// slide down again
					me.container.slideDown( 'slow' );

				}

			});

		};

	};

	// init settings
	Poets_Connections_Claim_Form.settings.init();

	// init Progress Bar
	Poets_Connections_Claim_Form.form.init();

} )( jQuery );



/**
 * Trigger dom_ready methods where necessary.
 *
 * @since 0.2
 */
jQuery(document).ready(function($) {

	// The DOM is loaded now
	Poets_Connections_Claim_Form.settings.dom_ready();

	// The DOM is loaded now
	Poets_Connections_Claim_Form.form.dom_ready();

}); // end document.ready()




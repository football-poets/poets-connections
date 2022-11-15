/**
 * Poets Connections "Claim Resolve Form" Javascript.
 *
 * Implements claim resolution functionality on the Poet Profile's edit page.
 *
 * @package Poets_Connections
 */



/**
 * Create Poets Connections "Resolve Form" object.
 *
 * This works as a "namespace" of sorts, allowing us to hang properties, methods
 * and "sub-namespaces" from it.
 *
 * @since 0.2
 */
var Poets_Connections_Resolve_Form = Poets_Connections_Resolve_Form || {};



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
	Poets_Connections_Resolve_Form.settings = new function() {

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
			if ( 'undefined' !== typeof Poets_Connections_Resolve_Form_Settings ) {
				me.localisation = Poets_Connections_Resolve_Form_Settings.localisation;
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
			if ( 'undefined' !== typeof Poets_Connections_Resolve_Form_Settings ) {
				me.settings = Poets_Connections_Resolve_Form_Settings.settings;
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
	 * Create Progress Object.
	 *
	 * @since 0.2
	 */
	Poets_Connections_Resolve_Form.progress = new function() {

		// prevent reference collisions
		var me = this;

		/**
		 * Initialise Progress.
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
		 * Set up Progress instance.
		 *
		 * @since 0.2
		 */
		this.setup = function() {

			// init decision
			me.decision = '';

			// assign properties
			me.feedback = $('.resolver-feedback');
			me.description = $('.resolver-description');
			me.options = $('.resolver-options');
			me.buttons = $('.resolver-buttons');
			me.button = $('#resolver-button');

			// create AJAX spinner
			me.button.after(
				'<img src="' +
				Poets_Connections_Resolve_Form.settings.get_setting( 'spinner_url' ) +
				'" class="poets-ajax-spinner" alt="' +
				Poets_Connections_Resolve_Form.settings.get_localisation( 'sending' ) +
				'" style="margin-left: 10px;" />'
			);

			// grab the new element and hide
			me.spinner = $('.poets-ajax-spinner');
			me.spinner.hide();

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
			 * Add a click event listener to start resolution.
			 *
			 * @param {Object} event The event object
			 */
			me.button.on( 'click', function( event ) {

				// prevent form submission
				if ( event.preventDefault ) {
					event.preventDefault();
				}

				// get decision
				if ( $('#claim_accept').is(':checked') ) {
					me.decision = 'accept';
				}
				if ( $('#claim_reject').is(':checked') ) {
					me.decision = 'reject';
				}

				// sanity check
				if ( me.decision == '' ) {
					me.feedback.html( Poets_Connections_Resolve_Form.settings.get_localisation( 'choose' ) );
					return;
				}

				// UI changes
				me.spinner.show();
				me.button.text( Poets_Connections_Resolve_Form.settings.get_localisation( 'sending' ) );

				// send
				me.send();

			});

		};

		/**
		 * Send AJAX request.
		 *
		 * @since 0.2
		 */
		this.send = function( decision ) {

			// use jQuery post
			$.post(

				// URL to post to
				Poets_Connections_Resolve_Form.settings.get_setting( 'ajax_url' ),

				{

					// token received by WordPress
					action: Poets_Connections_Resolve_Form.settings.get_setting( 'ajax_callback' ),

					// send user ID
					claiming_user_id: Poets_Connections_Resolve_Form.settings.get_setting( 'user_id' ),

					// send poet ID
					claimed_poet_id: Poets_Connections_Resolve_Form.settings.get_setting( 'post_id' ),

					// send resolution
					resolution: me.decision,

				},

				// callback
				function( data, textStatus ) {

					// if success
					if ( textStatus == 'success' ) {

						// update
						me.update( data );

					} else {

						// show error
						if ( console.log ) {
							console.log( textStatus );
						}

						// show error
						me.feedback.html( data.error );

					}

				},

				// expected format
				'json'

			);

		};

		/**
		 * Callback for successful AJAX request.
		 *
		 * @since 0.2
		 *
		 * @param {Array} data The data received from the server
		 */
		this.update = function( data ) {

			console.log( data );

			// update feedback
			me.feedback.html( data.status );

			// are we still in progress?
			if ( data.finished == 'false' ) {

				// trigger next batch
				me.send();

			} else {

				// reset the form
				setTimeout(function () {
					me.reset();
				}, 2000 );

			}

		};

		/**
		 * Reset form after successful AJAX request.
		 *
		 * @since 0.2
		 */
		this.reset = function() {

			// clean up form
			me.spinner.hide();
			me.button.hide();
			me.description.remove();
			me.options.remove();
			me.buttons.remove();

			// show finished message
			me.feedback.html( Poets_Connections_Resolve_Form.settings.get_localisation( 'finished' ) );

		};

	};

	// init settings
	Poets_Connections_Resolve_Form.settings.init();

	// init Progress Bar
	Poets_Connections_Resolve_Form.progress.init();

} )( jQuery );



/**
 * Trigger dom_ready methods where necessary.
 *
 * @since 0.2
 */
jQuery(document).ready(function($) {

	// The DOM is loaded now
	Poets_Connections_Resolve_Form.settings.dom_ready();

	// The DOM is loaded now
	Poets_Connections_Resolve_Form.progress.dom_ready();

}); // end document.ready()




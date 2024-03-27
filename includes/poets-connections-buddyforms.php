<?php
/**
 * Football Poets Connections BuddyForms Class.
 *
 * Handles plugin config variables and connection-related methods.
 *
 * @since 0.3
 * @package Poets_Connections
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Football Poets Connections BuddyForms Class.
 *
 * A class that holds plugin config variables and connection-related methods.
 *
 * @since 0.3
 */
class Poets_Connections_BuddyForms {

	/**
	 * Plugin object.
	 *
	 * @since 0.3
	 * @access public
	 * @var object $parent_obj The plugin object.
	 */
	public $plugin;

	/**
	 * Constructor.
	 *
	 * @since 0.3
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
	 * @since 0.3
	 */
	public function register_hooks() {

		// Legacy sidebar filter.
		add_filter( 'buddyforms_add_form_element_to_sidebar', [ $this, 'sidebar_elements' ], 20, 1 );

		// Current select filter.
		add_filter( 'buddyforms_add_form_element_to_select', [ $this, 'select_elements' ], 20, 1 );

		/*
		// Inspect form fields.
		add_filter( 'buddyforms_formbuilder_fields_options', [ $this, 'field_inspect' ], 20, 3 );
		*/

		// Add form field to form builder.
		add_filter( 'buddyforms_form_element_add_field', [ $this, 'field_add' ], 20, 4 );

		// Add form element to front end.
		add_filter( 'buddyforms_create_edit_form_display_element', [ $this, 'field_render' ], 20, 2 );

		// Save data when Post is saved.
		add_action( 'buddyforms_after_save_post', [ $this, 'field_save' ], 20, 1 );

		// Filter the taxonomy add label.
		add_filter( 'buddyforms_create_new_tax_title', [ $this, 'filter_tax_title' ], 20, 3 );

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

	// -------------------------------------------------------------------------

	/**
	 * Add elements to sidebar.
	 *
	 * @since 0.3
	 *
	 * @param array $sidebar_elements The existing sidebar elements.
	 * @return array $sidebar_elements The modified sidebar elements.
	 */
	public function sidebar_elements( $sidebar_elements ) {

		/*
		// Logging.
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'sidebar_elements' => $sidebar_elements,
			//'backtrace' => $trace,
		], true ) );
		*/

		// --<
		return $sidebar_elements;

	}

	/**
	 * Add elements to select dropdown.
	 *
	 * @since 0.3
	 *
	 * @param array $select_elements The existing select elements.
	 * @return array $select_elements The modified select elements.
	 */
	public function select_elements( $select_elements ) {

		/*
		// Logging.
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'select_elements' => $select_elements,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Add a custom element to the dropdown.
		$select_elements['poets'] = [
			'label'     => __( 'Poets', 'poets-connections' ),
			'fields'    => [
				'poet'     => [
					'label'     => __( 'Poets', 'poets-connections' ),
				],
			],
		];

		// --<
		return $select_elements;

	}

	/**
	 * Inspect BuddyForms fields.
	 *
	 * @since 0.3
	 *
	 * @param array $form_fields The existing form fields.
	 * @param str $field_type The field type.
	 * @param int $field_id The field ID.
	 * @return array $form_fields The modified form fields.
	 */
	public function field_inspect( $form_fields, $field_type, $field_id ) {

		/*
		// Logging.
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'form_fields' => $form_fields,
			'field_type' => $field_type,
			'field_id' => $field_id,
			//'backtrace' => $trace,
		], true ) );
		*/

		// --<
		return $form_fields;

	}

	/**
	 * Add a BuddyForms field.
	 *
	 * @since 0.3
	 *
	 * @param array $form_fields The existing form fields.
	 * @param str $form_slug The form slug.
	 * @param str $field_type The field type.
	 * @param int $field_id The field ID.
	 * @return array $form_fields The modified form fields.
	 */
	public function field_add( $form_fields, $form_slug, $field_type, $field_id ) {

		/*
		// Logging.
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'form_fields' => $form_fields,
			'form_slug' => $form_slug,
			'field_type' => $field_type,
			'field_id' => $field_id,
			//'backtrace' => $trace,
		], true ) );
		*/

		// --<
		return $form_fields;

	}

	/**
	 * Render a BuddyForms field in the front end form.
	 *
	 * @since 0.3
	 *
	 * @param array $form The existing form.
	 * @param str $form_args The form arguments.
	 * @return array $form The modified form.
	 */
	public function field_render( $form, $form_args ) {

		// Only handle our form.
		if ( 'poems' != $form_args['form_slug'] ) {
			return $form;
		}

		// Only handle our form type.
		if ( 'poet' != sanitize_title( $form_args['customfield']['type'] ) ) {
			return $form;
		}

		// Init args.
		$poets_args = [
			'post_type' => 'poet',
			'post_status' => 'publish',
			'author' => get_current_user_id(),
			'orderby' => 'title',
			'order' => 'ASC',
			'nopaging' => true,
			'no_found_rows' => true,
		];

		// Get Poets for this User.
		$poets = get_posts( $poets_args );

		// Bail if we get none.
		if ( empty( $poets ) ) {
			return $form;
		}

		// Get name.
		$name = $form_args['customfield']['name'];

		// Get slug.
		$slug = $form_args['customfield']['slug'];

		// Init options.
		$options = [];

		// Build select options.
		foreach ( $poets as $poet ) {
			$options[ $poet->ID ] = get_the_title( $poet->ID );
		}

		// Init attributes.
		$element_attr = [
			'id'        => str_replace( '-', '', $form_args['customfield']['slug'] ),
			'value'     => $form_args['customfield_val'],
			'class'     => $form_args['customfield']['custom_class'],
			'shortDesc' => $form_args['customfield']['description'],
		];

		/*
		// Add Select2.
		$element_attr['class'] = $element_attr['class'] . ' bf-select2';
		*/

		// Maybe add required.
		if ( isset( $form_args['customfield']['required'] ) ) {
			$element_attr['required'] = true;
		}

		// Init element.
		$element = new Element_Select( $name, $slug, $options, $element_attr );

		// Add to form.
		$form->addElement( $element );

		/*
		// Logging.
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'form' => $form,
			'form_args' => $form_args,
			//'backtrace' => $trace,
		], true ) );
		*/

		// --<
		return $form;

	}

	/**
	 * Save the Poet Profile for a Poem.
	 *
	 * @since 0.3
	 *
	 * @param int $poem_id The ID of the Poem.
	 * @return array|bool The array of Poet Profile Posts or false if none found.
	 */
	public function field_save( $poem_id ) {

		/*
		 * I have hard-coded 'publish_as' in the BuddyForm for now - but this really
		 * needs to be retrieved dynamically from the form fields.
		 */

		// Get Poet ID.
		$poet_id = isset( $_POST['publish_as'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['publish_as'] ) ) : 0;

		/*
		// Logging.
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'POST' => $_POST,
			'poem_id' => $poem_id,
			'poet_id' => $poet_id,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Sanity check.
		if ( empty( $poet_id ) ) {
			return;
		}

		// Make a connection.
		$this->plugin->config->connect_poet_and_poem( $poet_id, $poem_id );

	}

	/**
	 * Filter the title of the "Create a new" taxonomy term field.
	 *
	 * @since 0.3
	 *
	 * @param str $title The existing title.
	 * @param str $form_slug The form slug.
	 * @param array $customfield The custom field.
	 * @return str $title The modified title.
	 */
	public function filter_tax_title( $title, $form_slug, $customfield ) {

		// Only handle our form.
		if ( 'poems' != $form_slug ) {
			return $title;
		}

		// Only our free taxonomy.
		if ( ! isset( $customfield['taxonomy'] ) ) {
			return $title;
		}
		if ( 'poemtag' != $customfield['taxonomy'] ) {
			return $title;
		}

		/*
		// Logging.
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'title' => $title,
			'form_slug' => $form_slug,
			'customfield' => $customfield,
			//'backtrace' => $trace,
		], true ) );
		*/

		$title = __( 'Create a new tag', 'poets-connections' );

		// --<
		return $title;

	}

}

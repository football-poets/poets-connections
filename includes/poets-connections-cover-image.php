<?php
/**
 * Football Poets Connections "Cover Image" Class.
 *
 * Handles the functionality of Cover Images.
 *
 * @since 0.1
 * @package Poets_Connections
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Football Poets Connections "Cover Image" Class.
 *
 * A class that encapsulates the functionality of Cover Images.
 *
 * @since 0.1
 */
class Poets_Connections_Cover_Image {

	/**
	 * Plugin object.
	 *
	 * @since 0.1
	 * @access public
	 * @var Poets_Connections_Plugin
	 */
	public $plugin;

	/**
	 * Cover image height in pixels.
	 *
	 * The default is 300px - the same as Feature Images in CommentPress.
	 *
	 * @since 0.1
	 * @access public
	 * @var integer
	 */
	public $image_height = 300;

	/**
	 * Cover image URL.
	 *
	 * @since 0.1
	 * @access public
	 * @var string
	 */
	public $cover_image_url = '';

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
		do_action( 'poets_connections/cover_image/loaded' );

		// We're done.
		$done = true;

	}

	/**
	 * Register hook callbacks.
	 *
	 * @since 0.1
	 */
	private function register_hooks() {

		// Filter the Cover Image settings.
		$bp_version = bp_get_version();
		if ( version_compare( $bp_version, '6.0.0', '>=' ) ) {
			add_filter( 'bp_before_members_cover_image_settings_parse_args', [ $this, 'cover_image' ] );
		} else {
			add_filter( 'bp_before_xprofile_cover_image_settings_parse_args', [ $this, 'cover_image' ] );
		}

		// Filter the Feature Image on a "Poet" page.
		add_filter( 'commentpress_has_feature_image', [ $this, 'has_feature_image' ] );
		add_filter( 'commentpress_get_feature_image', [ $this, 'get_feature_image' ], 10, 2 );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Filter Cover Image settings.
	 *
	 * @since 0.1
	 *
	 * @param array $settings The existing settings.
	 * @return array $settings The modified settings.
	 */
	public function cover_image( $settings = [] ) {

		// Set height.
		$settings['height'] = $this->image_height;

		// Set callback.
		$settings['callback'] = [ $this, 'cover_image_callback' ];

		// --<
		return $settings;

	}

	/**
	 * Filter Cover Image.
	 *
	 * This is an adapted clone of bp_legacy_theme_cover_image()
	 *
	 * @since 0.2
	 *
	 * @param array $params The existing settings.
	 * @return array $params The modified settings.
	 */
	public function cover_image_callback( $params = [] ) {

		// Bail if no params.
		if ( empty( $params ) ) {
			return;
		}

		/*
		// Logging.
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'params' => $params,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Avatar height - padding - 1/2 avatar height.
		$avatar_offset = $params['height'] - 5 - round( (int) bp_core_avatar_full_height() / 2 );

		// Header content offset + spacing.
		$top_offset  = bp_core_avatar_full_height() - 10;
		$left_offset = bp_core_avatar_full_width() + 20;

		// Get Cover Image if one exists.
		$cover_image = '';
		if ( ! empty( $params['cover_image'] ) ) {
			$cover_image = 'background-image: url(' . $params['cover_image'] . ');';
		} else {

			// Does the Primary Poet have a Feature Image?
			$primary_poet = $this->plugin->config->get_primary_poet( $params['object_id'] );

			// If it's not the Primary Poet.
			if ( ! empty( $primary_poet ) ) {

				// Get thumbnail ID.
				$post_thumbnail_id = get_post_thumbnail_id( $primary_poet->ID );

				// Did we get one?
				if ( ! empty( $post_thumbnail_id ) ) {

					// Get URL to image.
					$post_thumbnail_url = wp_get_attachment_url( $post_thumbnail_id );

					// Did we get one?
					if ( ! empty( $post_thumbnail_url ) ) {

						// Override.
						$cover_image = 'background-image: url(' . $post_thumbnail_url . ');';

					}

				}

			}

		}

		$hide_avatar_style = '';

		// Adjust the Cover Image header, in case avatars are completely disabled.
		if ( ! buddypress()->avatar->show_avatars ) {
			$hide_avatar_style = '
				#buddypress #item-header-cover-image #item-header-avatar {
					display: none;
				}
			';

			if ( bp_is_user() ) {
				$hide_avatar_style = '
					#buddypress #item-header-cover-image #item-header-avatar a {
						display: block;
						height: ' . $top_offset . 'px;
						margin: 0 15px 19px 0;
					}

					#buddypress div#item-header #item-header-cover-image #item-header-content {
						margin-left: auto;
					}
				';
			}
		}

		// Build styles.
		$style = '
			/* Cover image */
			#buddypress #header-cover-image {
				height: ' . $params['height'] . 'px;
				' . $cover_image . '
			}

			#buddypress #create-group-form #header-cover-image {
				margin: 1em 0;
				position: relative;
			}

			.bp-user #buddypress #item-header {
				padding-top: 0;
			}

			#buddypress #item-header-cover-image #item-header-avatar {
				margin-top: ' . $avatar_offset . 'px;
				float: left;
				overflow: visible;
				width: auto;
			}

			#buddypress div#item-header #item-header-cover-image #item-header-content {
				clear: both;
				float: left;
				margin-left: ' . $left_offset . 'px;
				margin-top: -' . $top_offset . 'px;
				width: auto;
			}

			body.single-item.groups #buddypress div#item-header #item-header-cover-image #item-header-content,
			body.single-item.groups #buddypress div#item-header #item-header-cover-image #item-actions {
				clear: none;
				margin-top: ' . $params['height'] . 'px;
				margin-left: 0;
				max-width: 50%;
			}

			body.single-item.groups #buddypress div#item-header #item-header-cover-image #item-actions {
				max-width: 20%;
				padding-top: 20px;
			}

			' . $hide_avatar_style . '

			#buddypress div#item-header-cover-image .user-nicename a,
			#buddypress div#item-header-cover-image .user-nicename {
				font-size: 200%;
				color: #fff;
				margin: 0 0 0.6em;
				text-rendering: optimizelegibility;
				text-shadow: 0 0 3px rgba( 0, 0, 0, 0.8 );
			}

			#buddypress #item-header-cover-image #item-header-avatar img.avatar {
				background: rgba( 255, 255, 255, 0.8 );
				border: solid 2px #fff;
			}

			#buddypress #item-header-cover-image #item-header-avatar a {
				border: 0;
				text-decoration: none;
			}

			#buddypress #item-header-cover-image #item-buttons {
				margin: 0 0 10px;
				padding: 0 0 5px;
			}

			#buddypress #item-header-cover-image #item-buttons:after {
				clear: both;
				content: "";
				display: table;
			}

			@media screen and (max-width: 782px) {
				#buddypress #item-header-cover-image #item-header-avatar,
				.bp-user #buddypress #item-header #item-header-cover-image #item-header-avatar,
				#buddypress div#item-header #item-header-cover-image #item-header-content {
					width: 100%;
					text-align: center;
				}

				#buddypress #item-header-cover-image #item-header-avatar a {
					display: inline-block;
				}

				#buddypress #item-header-cover-image #item-header-avatar img {
					margin: 0;
				}

				#buddypress div#item-header #item-header-cover-image #item-header-content,
				body.single-item.groups #buddypress div#item-header #item-header-cover-image #item-header-content,
				body.single-item.groups #buddypress div#item-header #item-header-cover-image #item-actions {
					margin: 0;
				}

				body.single-item.groups #buddypress div#item-header #item-header-cover-image #item-header-content,
				body.single-item.groups #buddypress div#item-header #item-header-cover-image #item-actions {
					max-width: 100%;
				}

				#buddypress div#item-header-cover-image h2 a,
				#buddypress div#item-header-cover-image h2 {
					color: inherit;
					text-shadow: none;
					margin: 25px 0 0;
					font-size: 200%;
				}

				#buddypress #item-header-cover-image #item-buttons div {
					float: none;
					display: inline-block;
				}

				#buddypress #item-header-cover-image #item-buttons:before {
					content: "";
				}

				#buddypress #item-header-cover-image #item-buttons {
					margin: 5px 0;
				}
			}
		';

		// --<
		return $style;

	}

	/**
	 * Maybe override whether a Poet has a feature_image.
	 *
	 * @since 0.1
	 *
	 * @param bool $has_image The existing value.
	 * @return bool $has_image The modified value.
	 */
	public function has_feature_image( $has_image ) {

		// Get queried object.
		$post = get_queried_object();

		// Bail unless Poet CPT.
		if ( ! isset( $post->post_type ) ) {
			return $has_image;
		}
		if ( 'poet' !== $post->post_type ) {
			return $has_image;
		}

		// Bail if we've already done this.
		if ( ! empty( $this->cover_image_url ) ) {
			return true;
		}

		// Get Primary member.
		$user = $this->plugin->config->get_primary_user( $post );

		// Bail if we didn't get one.
		if ( false === $user ) {
			return $has_image;
		}

		// Init URL.
		$this->cover_image_url = '';

		// Get the member's Cover Image.
		$this->cover_image_url = bp_attachments_get_attachment( 'url', [ 'item_id' => $user->ID ] );

		// If we got one, then we'll use it later.
		if ( ! empty( $this->cover_image_url ) ) {
			$has_image = true;
		}

		/*
		// Logging.
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'has_image' => $has_image,
			'user' => $user,
			'cover_image_url' => $this->cover_image_url,
			//'backtrace' => $trace,
		], true ) );
		*/

		// --<
		return $has_image;

	}

	/**
	 * Maybe override Feature Image.
	 *
	 * @since 0.1
	 *
	 * @param str     $html The existing Feature Image markup.
	 * @param WP_Post $post The WordPress Post object.
	 * @return str $html The modified Feature Image markup.
	 */
	public function get_feature_image( $html, $post ) {

		// Bail if we don't have a Cover Image.
		if ( empty( $this->cover_image_url ) ) {
			return $html;
		}

		/*
		// Logging.
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'html' => $html,
			'post' => $post,
			'cover_image_url' => $this->cover_image_url,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Construct new HTML for the image.
		$html = '<img src="' . $this->cover_image_url . '" class="wp-post-image" />';

		// --<
		return $html;

	}

}

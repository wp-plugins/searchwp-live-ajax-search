<?php

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class SearchWP_Live_Search_Form
 *
 * The SearchWP Live Ajax Search search form and it's configuration
 *
 * @since 1.0
 */
class SearchWP_Live_Search_Form extends SearchWP_Live_Search {

	/**
	 * The default configuration
	 *
	 * Developers can add their own configs using the searchwp_live_search_configs filter which is applied at runtime.
	 * You are responsible for keeping the $configs array in tact, and either substituting your own customizations in
	 * the existing data, or adding your own by appending your own array key with values based on the default
	 *
	 * To use: set the data-swpconfig attribute value on your search form input to be the config you want to use
	 *
	 * @since 1.0
	 *
	 * @var array All configurations available for use at runtime
	 */
	public $configs = array(
			'default' => array(                         // 'default' config
				'engine' => 'default',                  // search engine to use (if SearchWP is available)
				'input' => array(
					'delay'     => 500,                 // wait 500ms before triggering a search
					'min_chars' => 3,                   // wait for at least 3 characters before triggering a search
				),
				'results' => array(
					'position'  => 'bottom',            // where to position the results (bottom|top)
					'width'     => 'auto',              // whether the width should automatically match the input (auto|css)
					'offset'    => array(
						'x' => 0,                       // x offset (in pixels)
						'y' => 5                        // y offset (in pixels)
					),
				),
				'spinner' => array(                     // powered by http://fgnass.github.io/spin.js/
					'lines'         => 10,              // number of lines in the spinner
					'length'        => 8,               // length of each line
					'width'         => 4,               // line thickness
					'radius'        => 8,               // radius of inner circle
					'corners'       => 1,               // corner roundness (0..1)
					'rotate'        => 0,               // rotation offset
					'direction'     => 1,               // 1: clockwise, -1: counterclockwise
					'color'         => '#000',          // #rgb or #rrggbb or array of colors
					'speed'         => 1,               // rounds per second
					'trail'         => 60,              // afterglow percentage
					'shadow'        => false,           // whether to render a shadow
					'hwaccel'       => false,           // whether to use hardware acceleration
					'className'     => 'spinner',       // CSS class assigned to spinner
					'zIndex'        => 2000000000,      // z-index of spinner
					'top'           => '50%',           // top position (relative to parent)
					'left'          => '50%',           // left position (relative to parent)
				),
			),
		);

	/**
	 * Equivalent of __construct() — implement our hooks
	 *
	 * @since 1.0
	 *
	 * @uses add_action() to trigger asset enqueue and output base styles in the footer
	 * @uses add_filter() to filter search forms generated by get_search_form()
	 * @uses apply_filters() to ensure developer can filter the configs array via searchwp_live_search_configs filter
	 */
	function setup() {
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
		add_filter( 'get_search_form', array( $this, 'get_search_form' ) );
		add_action( 'wp_footer', array( $this, 'base_styles' ) );

		// the configs store all of the various configuration arrays that can be used at runtime
		$this->configs = apply_filters( 'searchwp_live_search_configs', $this->configs );
	}

	/**
	 * Register, localize, and enqueue all necessary JavaScript and CSS
	 *
	 * @since 1.0
	 *
	 * @uses wp_enqueue_style() to enqueue CSS
	 * @uses wp_enqueue_script() to enqueue JavaScript
	 * @uses wp_register_script() to register JavaScript
	 * @uses wp_localize_script() to pass PHP variables to JavaScript at runtime
	 * @uses json_encode() to prepare the (potentially filtered) configs array
	 */
	function assets() {
		// styles
		wp_enqueue_style( 'searchwp-live-search', $this->url . '/assets/styles/style.css', null, $this->version );

		// scripts
		wp_enqueue_script( 'jquery' );
		wp_register_script( 'swp-live-search-client', $this->url . '/assets/javascript/searchwp-live-search.min.js', array( 'jquery' ), $this->version, false );

		// set up our parameters
		$params = array(
			'ajaxurl'               => admin_url( 'admin-ajax.php' ),
			'config'                => $this->configs,
			'msg_no_config_found'   => __( 'No valid SearchWP Live Search configuration found!', 'searchwp' ),
		);

		// we need to JSON encode the configs
		$encoded_data = array(
			'l10n_print_after' => 'searchwp_live_search_params = ' . json_encode( $params ) . ';'
		);

		// localize and enqueue the script with all of the variable goodness
		wp_localize_script( 'swp-live-search-client', 'searchwp_live_search_params', $encoded_data );
		wp_enqueue_script( 'swp-live-search-client' );
	}

	/**
	 * Callback to the get_search_form filter, allows us to automagically enable live search on form fields
	 * generated using get_search_form()
	 *
	 * @since 1.0
	 *
	 * @param $html string The generated markup for the search form
	 *
	 * @uses apply_filters() to allow devs to disable this functionality
	 * @uses apply_filters() to allow devs to set the default SearchWP search engine
	 * @uses apply_filters() to allow devs to set the default config to use
	 * @uses str_replace() to inject our HTML5 data attributes where we want them
	 * @uses esc_attr() to escape the search engine and config name
	 *
	 * @return string Markup for the search form
	 */
	function get_search_form( $html ) {
		if ( apply_filters( 'searchwp_live_search_hijack_get_search_form', true ) ) {
			$engine = apply_filters( 'searchwp_live_search_get_search_form_engine', 'default' );
			$config = apply_filters( 'searchwp_live_search_get_search_form_config', 'default' );
			// we're going to use 'name="s"' as our anchor
			$html = str_replace( 'name="s"', 'name="s" data-swplive="true" data-swpengine="' . esc_attr( $engine ) . '" data-swpconfig="' . esc_attr( $config ) . '"', $html );
		}
		return $html;
	}

	/**
	 * Output the base styles (absolutely minimal) necessary to properly set up the results wrapper
	 *
	 * @since 1.0
	 *
	 * @uses apply_filters() to allow devs to disable this functionality
	 */
	function base_styles() {
		if ( apply_filters( 'searchwp_live_search_base_styles', true ) ) {
			?>
				<style type="text/css">
					.searchwp-live-search-results {
						opacity:0;
						transition:opacity .25s ease-in-out;
						-moz-transition:opacity .25s ease-in-out;
						-webkit-transition:opacity .25s ease-in-out;
						height:0;
						overflow:hidden;
						z-index:9999;
						position:absolute;
						display:none;
					}

					.searchwp-live-search-results-showing {
						display:block;
						opacity:1;
						height:auto;
						overflow:auto;
					}

					.searchwp-live-search-no-results {
						padding:3em 2em 0;
						text-align:center;
					}
				</style>
			<?php
		}
	}

}

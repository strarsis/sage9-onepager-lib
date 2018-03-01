<?php
/*
Code from https://github.com/WordPress/WordPress/blob/aaf99e691391cfceb004d848450dbbf3344b1bee/wp-content/themes/twentyseventeen/inc/customizer.php
*/

namespace strarsis\Sage9Onepager;

class Controls {

	public static function init() {
		add_action( 'customize_register',                 '\strarsis\Sage9Onepager\Controls::customize_register' );
		add_action( 'customize_controls_enqueue_scripts', '\strarsis\Sage9Onepager\Controls::panels_js' );
		add_action( 'customize_preview_init', 		      '\strarsis\Sage9Onepager\Controls::customize_preview_js' );
	}

	public static function customize_register( $wp_customize ) {
		$wp_customize->add_section(
			'theme_options', array(
				'title'    => __( 'Pages', 'onepager' ),
				'priority' => 130,
			)
		);

		/**
		 * Filter number of front page sections
		 *
		 * @param int $num_sections Number of front page sections.
		 */
		$num_sections = apply_filters( 'onepager_front_page_sections', 4 );
		// Create a setting and control for each of the sections available in the theme.
		for ( $i = 1; $i < ( 1 + $num_sections ); $i++ ) {
			$wp_customize->add_setting(
				'panel_' . $i, array(
					'default'           => false,
					'sanitize_callback' => 'absint',
					'transport'         => 'postMessage',
				)
			);
			$wp_customize->add_control(
				'panel_' . $i, array(
					/* translators: %d is the front page section number */
					'label'           => sprintf( __( 'Front Page Section %d Content', 'onepager' ), $i ),
					'description'     => ( 1 !== $i ? '' : __( 'Select pages to feature in each area from the dropdowns. Add an image to a section by setting a featured image in the page editor. Empty sections will not be displayed.', 'onepager' ) ),
					'section'         => 'theme_options',
					'type'            => 'dropdown-pages',
					'allow_addition'  => true,
					'active_callback' => '\strarsis\Sage9Onepager\Controls::is_static_front_page',
				)
			);
			$wp_customize->selective_refresh->add_partial(
				'panel_' . $i, array(
					'selector'            => '#panel' . $i,
					'render_callback'     => '\strarsis\Sage9Onepager\Controls::onepager_front_page_section',
					'container_inclusive' => true,
				)
			);
		}

	}

	/**
	 * Return whether we're previewing the front page and it's a static page.
	 */
	public static function is_static_front_page() {
		return ( is_front_page() && ! is_home() );
	}

	/**
	 * Bind JS handlers to instantly live-preview changes.
	 */
	public static function customize_preview_js() {
		wp_enqueue_script( 'onepager-customize-preview',  self::get_lib_url() . '/assets/js/customize-preview.js' ,  array( 'customize-preview' ), '1.0', true );
	}

	/**
	 * Load dynamic logic for the customizer controls area.
	 */
	public static function panels_js() {
		wp_enqueue_script( 'onepager-customize-controls', self::get_lib_url() . '/assets/js/customize-controls.js' , array(), '1.0', true );
	}


	/**
	 * Count our number of active panels.
	 *
	 * Primarily used to see if we have any panels active, duh.
	 */
	public static function panel_count() {
		$panels = array( '1', '2', '3', '4' );
		$panel_count = 0;
		foreach ( $panels as $panel ) {
			if ( get_theme_mod( 'panel_' . $panel ) ) {
				$panel_count++;
			}
		}
		return $panel_count;
	}
	
	/**
	 * Get the IDs of the posts assigned to a panel
	 *
	 * Primarily used to see if we have any panels active, duh.
	 */
	public static function get_panel_ids() {
	    $panel_count = \strarsis\Sage9Onepager\Controls::panel_count();
	    $panel_ids   = array();
	    for($panel = 0; $panel <= $panel_count; $panel++) {
		$panel_ids[] = get_theme_mod( 'panel_' . $panel );
	    }
	    return $panel_ids;
	}

	/**
	 * Display a front page section.
	 *
	 * @param WP_Customize_Partial $partial Partial associated with a selective refresh request.
	 * @param integer              $id Front page section to display.
	 */
	public static function front_page_section( $partial = null, $id = 0 ) {
		if ( is_a( $partial, 'WP_Customize_Partial' ) ) {
			// Find out the id and set it up during a selective refresh.
			global $onepagercounter;
			$id                     = str_replace( 'panel_', '', $partial->id );
			$onepagercounter = $id;
		}
		global $post; // Modify the global post object before setting up post data.
		if ( get_theme_mod( 'panel_' . $id ) ) {
			$post = get_post( get_theme_mod( 'panel_' . $id ) );
			setup_postdata( $post );
			set_query_var( 'panel', $id );
			echo \App\template('single-panels');
			wp_reset_postdata();
		} elseif ( is_customize_preview() ) {
			// The output placeholder anchor.
			echo '<article class="panel-placeholder panel onepager-panel onepager-panel' . $id . '" id="panel' . $id . '"><span class="onepager-panel-title">' . sprintf( __( 'Front Page Section %1$s Placeholder', 'onepager' ), $id ) . '</span></article>';
		}
	}


	public static function get_lib_url() {
		$prefix = '/vendor/strarsis/onepager';
		return get_template_directory_uri() . '/..' . $prefix;
	}
}

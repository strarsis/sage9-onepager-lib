<?php
/*
Code from https://github.com/WordPress/WordPress/blob/aaf99e691391cfceb004d848450dbbf3344b1bee/wp-content/themes/twentyseventeen/inc/customizer.php
*/

namespace strarsis\Sage9Onepager;

class Controls {

	public static function init() {
		add_action( 'customize_register',				 '\strarsis\Sage9Onepager\Controls::customize_register' );
		add_action( 'customize_controls_enqueue_scripts', '\strarsis\Sage9Onepager\Controls::panels_js' );
		add_action( 'customize_preview_init', 			  '\strarsis\Sage9Onepager\Controls::customize_preview_js' );
	}

	public static function customize_register( $wp_customize ) {
		$wp_customize->add_section(
			'theme_options', array(
				'title'	=> __( 'Pages', 'onepager' ),
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
					'default'		   => false,
					'sanitize_callback' => 'absint',
					'transport'		 => 'postMessage',
				)
			);
			$wp_customize->add_control(
				'panel_' . $i, array(
					/* translators: %d is the front page section number */
					'label'		   => sprintf( __( 'Front Page Section %d Content', 'onepager' ), $i ),
					'description'	 => ( 1 !== $i ? '' : __( 'Select pages to feature in each area from the dropdowns. Add an image to a section by setting a featured image in the page editor. Empty sections will not be displayed.', 'onepager' ) ),
					'section'		 => 'theme_options',
					'type'			=> 'dropdown-pages',
					'allow_addition'  => true,
					'active_callback' => '\strarsis\Sage9Onepager\Controls::is_static_front_page',
				)
			);
			$wp_customize->selective_refresh->add_partial(
				'panel_' . $i, array(
					'selector'			=> '#panel' . $i,
					'render_callback'	 => '\strarsis\Sage9Onepager\Controls::onepager_front_page_section',
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
		return count(self::panels());
	}

	/**
	 * Get the the posts assigned as panel
	 *
	 * Primarily used to see if we have any panels active, duh.
	 */
	public static function panels() {
		$panels = array();
		$panelIndex = 1; // starts with 1!
		while( !empty($panel = get_theme_mod( 'panel_' . $panelIndex )) ) {
			$panels[] = $panel;
			$panelIndex++;
		}
		return $panels;
	}

	// Translation plugin support
	public static function translated_post_id( $post_id = 0 ) {

		// Polylang support
		if(  function_exists('pll_get_post')  ) {
			$post_translated_id = pll_get_post($post_id);
			if($post_translated_id)
				return $post_translated_id;
		}

		// WPML support
		if(  function_exists('icl_object_id')  ) {
			$post_translated_id = icl_object_id($post_id);
			if($post_translated_id)
				return $post_translated_id;
		}

		return $post_id;
	}

	/**
	 * Display a front page section.
	 *
	 * @param WP_Customize_Partial $partial Partial associated with a selective refresh request.
	 * @param integer			  $id Front page section to display.
	 */
	public static function front_page_section( $partial = null, $id = 0 ) {
		if ( is_a( $partial, 'WP_Customize_Partial' ) ) {
			// Find out the id and set it up during a selective refresh.
			global $onepagercounter;
			$id			  = str_replace( 'panel_', '', $partial->id );
			$onepagercounter = $id;
		}
		global $post; // Modify the global post object before setting up post data.


		$post_id = self::translated_post_id( get_theme_mod( 'panel_' . $id ) );
        if ( !$post_id ) {
            //self::output_placeholder_anchor($id);
            return;
        }

		if($post_id)
			$post = get_post( $post_id );
        if ( !$post ) {
            //self::output_placeholder_anchor($id);
            return;
        }


		setup_postdata( $post );
		set_query_var( 'panel', $id );

		$template_data = array();
		if(  function_exists('mesh_display_sections')  ) {
			$template_data['end'] = mesh_display_sections( $post->ID, false );

			// reset $post (Mesh plugin support (mesh_display_sections))
			$post = get_post( get_theme_mod( 'panel_' . $id ) );
			setup_postdata( $post );
			set_query_var( 'panel', $id );
		}

		echo \App\template(  'single-panels', $template_data  );


		wp_reset_postdata();
	}

    protected static function output_placeholder_anchor($id) {
        echo '<article class="panel-placeholder panel onepager-panel onepager-panel' . $id . '" id="panel' . $id . '"><span class="onepager-panel-title">' . sprintf( __( 'Front Page Section %1$s Placeholder', 'onepager' ), $id ) . '</span></article>';
    }

	/**
	 * Returns an accessibility-friendly link to edit a post or page.
	 *
	 * This also gives us a little context about what exactly we're editing
	 * (post or page?) so that users understand a bit more where they are in terms
	 * of the template hierarchy and their content. Helpful when/if the single-page
	 * layout with multiple posts/pages shown gets confusing.
	 */
	public static function edit_link() {
		edit_post_link(
			sprintf(
				/* translators: %s: Name of current post */
				__( 'Edit<span class="screen-reader-text"> "%s"</span>', 'twentyseventeen' ),
				get_the_title()
			),
			'<span class="edit-link">',
			'</span>'
		);
	}

	public static function get_lib_url() {
		$prefix = '/vendor/strarsis/sage9-onepager-lib';
		return get_template_directory_uri() . '/..' . $prefix;
	}
}

<?php
namespace strarsis\Sage9Onepager;

class Helpers {

	/**
	 * Returns the number of pages minus one
	 *
	 * @return int
	 */
	public static function default_front_page_sections() {
		return wp_count_posts('page')->publish - 1;
	}


	/**
	 * Add classes to post_class()
	 *
	 * @param array $classes array with post classes.
	 *
	 * @return Array
	 */
	public static function panel_post_classes( $classes ) {
		global $post;
		return self::blade_clean_classnames(
			array_merge($classes, self::post_body_classes($post->ID))
		);
	}


	/**
	 * Remove template classes from front page body class
     * Note: Use with higher filter priority (>= 100)
	 *
	 * @param array $classes array with body classes.
	 *
	 * @return Array
	 */
	public static function panels_front_page_body_class(array $classes) {
		if(!is_front_page()) return $classes; // skip

		$template_classes = self::post_body_classes(get_the_ID());
		return self::blade_clean_classnames(
			array_diff($classes, $template_classes)
		);
	}


	/**
	 * Exclude pages on start page from Yoast sitemap
	 *
	 * @param string $url
	 * @param string $type
	 * @param object $object
	 *
	 * @return String|Boolean
	 */
	public static function exclude_included_pages_from_xml_sitemap( $url, $type, $object ) {
        $panel_post_ids = Controls::panels();
        foreach($panel_post_ids as $panel_post_id) {
            $panel_post_translated_ids = self::translated_post_ids( $panel_post_id );
            if(in_array($object->ID, $panel_post_translated_ids))
                return false;
        }
		return $url;
	}


	/**
	 * Redirects from pages assigned to front page to front page
	 *
	 * @return String|Void
	 */
	public static function redirect_included_pages_to_frontpage() {
		global $post;
		if(!isset($post)) return $url;

        $panel_post_ids = Controls::panels();
        foreach($panel_post_ids as $panel_post_id) {
            $panel_post_translated_ids = self::translated_post_ids( $panel_post_id );
            if(in_array($post->ID, $panel_post_translated_ids)) {
				wp_redirect( home_url() . '#' . $post->post_name );
				die();
				return;
			}
		}
	}




	protected static function post_template_classes( $post_id ) {
		$post_type = get_post_type( $post_id );
		$template_slug  = get_page_template_slug( $post_id );

		if ( empty($template_slug) /*is_page_template() equivalent for $post*/ ) {
			$classes[] = "{$post_type}-template-default";
			return $classes;
		}

		$classes[] = "{$post_type}-template";
		$template_parts = explode( '/', $template_slug );
		foreach ( $template_parts as $part ) {
			$classes[] = "{$post_type}-template-" .
							sanitize_html_class(
								str_replace(
									array( '.', '/' ), '-',
									basename( $part, '.php' )
								)
							);
		}

		$classes[] = "{$post_type}-template-" .
						sanitize_html_class(
							str_replace( '.', '-', $template_slug )
						);

		return self::blade_clean_classnames($classes);
	}

	protected static function post_body_classes( $post_id ) {
		$classes = self::post_template_classes( $post_id );

		/** Add page slug if it doesn't exist */
		if (!in_array(basename(get_permalink($post_id)), $classes)) {
			$classes[] = basename(get_permalink($post_id));
		}

		return $classes;
	}

	/** Clean up class names for custom templates */
	protected static function blade_clean_classnames( $classes ) {
		$classes = array_map(function ($class) {
			return preg_replace(['/-blade(-php)?$/', '/^page-template-views/'], '', $class);
		}, $classes);

		return array_filter($classes);
	}


    // Plural (ids), returns all post ids of all translations of a given post id
    protected static function translated_post_ids($post_id) {
        $post_ids = array( $post_id );

        // Polylang support
        if(  function_exists('pll_get_post')  ) {
            $pll_languages = pll_languages_list();
            foreach($pll_languages as $language) {
                $post_translated_id = pll_get_post($post_id, $language);
    			if($post_translated_id)
                    $post_ids[] = $post_translated_id;
            }
        }

        // WPML support
		if(  function_exists('icl_object_id')  ) {
            $wpml_languages = icl_get_languages('skip_missing=0&orderby=KEY&order=DIR&link_empty_to=str');
            foreach($wpml_languages as $language) {
                $post_translated_id = icl_object_id($post_id, null, false, $language);
                if($post_translated_id)
                    $post_ids[] = $post_translated_id;
            }
        }

        return array_unique($post_ids);
    }
}

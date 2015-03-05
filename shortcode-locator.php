<?php
	
	/*
	Plugin Name: Shortcode Locator
	Plugin URI: http://travislop.es/plugins/shortcode-locator/
	Description: Quickly locate what and where shortcodes are being used
	Version: 1.0.1
	Author: travislopes
	Author URI: http://travislop.es
	*/
	
	class Shortcode_Locator {
		
		public static $admin_page_slug = 'shortcode_locator';
		public static $basename;
		public static $post_types = array();
		public static $settings;
		private static $admin_page_title = 'Shortcode Locator';
		private static $_instance = null;
	
		public static function get_instance() {
			
			if ( self::$_instance == null )
				self::$_instance = new Shortcode_Locator();
	
			return self::$_instance;
			
		}
	
		public function __construct() {
			
			/* Set basename */
			self::$basename = plugin_basename( __FILE__ );
			
			/* Include settings page class */
			include_once 'shortcode-locator-settings.php';
			
			/* Assign settings to this class */
			self::$settings = Shortcode_Locator_Settings::get_settings();
			
			/* Register admin page */
			add_action( 'admin_menu', array( __CLASS__, 'register_admin_page' ) );
			
			/* Add and render shortcode column to selected post types */
			foreach( self::$settings['display_column'] as $post_type ) {
				
				add_filter( 'manage_'. $post_type .'_posts_columns', array( __CLASS__, 'add_shortcode_post_column' ) );
				add_filter( 'manage_'. $post_type .'_posts_custom_column', array( __CLASS__, 'render_shortcode_post_column' ), 10, 2 );
				
			}
			
			/* Set available post types */
			add_action( 'admin_init', array( __CLASS__, 'set_post_types' ) );
			
		}

		/* Register admin page */
		function register_admin_page() {
			
			add_submenu_page( 'tools.php', self::$admin_page_title, self::$admin_page_title, 'edit_posts', self::$admin_page_slug, array( __CLASS__, 'render_admin_page' ) );
			
		}
		
		/* Render admin page */
		function render_admin_page() {
						
			/* Load table class */
			include_once 'shortcode-locator-table.php';

			/* Open page */
			echo '<div class="wrap">';
			
			/* Page title */
			echo '<h2>'. self::$admin_page_title .'</h2>';
			
			/* Display table */
			$shortcode_locator_table = new Shortcode_Locator_Table();
			$shortcode_locator_table->prepare_items();
			$shortcode_locator_table->display();			
			
			/* Close page */
			echo '</div>';
			
		}

		/* Add shortcode column to selected post types */
		function add_shortcode_post_column( $columns ) {
			
			$columns['shortcodes'] = 'Shortcodes Located';
			return $columns;
			
		}

		/* Render shortcode column to selected post types */
		function render_shortcode_post_column( $column, $post_id ) {
			
			/* Get shortcodes in post */
			$shortcodes_located = self::get_shortcodes_for_post( get_post_field( 'post_content', $post_id ) );
			
			/* Display if found */
			if ( ! empty( $shortcodes_located ) )
				echo implode( '<br />', $shortcodes_located );
			
		}
		
		/* Set available post types */
		function set_post_types() {
			
			/* Set list of post types to exclude */
			$excluded_post_types = apply_filters( 'shortcode_locator_excluded_post_types', array( 'attachment', 'revision', 'nav_menu_item' ) );
			
			/* Get registered post types */
			$registered_post_types = get_post_types( array(), 'objects' );
			
			/* Loop through registered post types and push to class array if not excluded */
			foreach( $registered_post_types as $post_type ) {
								
				if( ! in_array( $post_type->name, $excluded_post_types ) )
					self::$post_types[$post_type->name] = $post_type->label;				
				
			}
			
		}

		/* Get shortcodes for post */
		function get_shortcodes_for_post( $post_content, $shortcode = null ) {
			
			/* Get shortcode regex */
			$shortcode_regex = ( is_null( $shortcode ) ) ? get_shortcode_regex() : self::get_specific_shortcode_regex( $shortcode );
			
			/* Search for shortcodes */
			preg_match_all( '/'. $shortcode_regex .'/', $post_content, $shortcodes_located );
			
			/* Loop through the shortcodes located array and push them to a separate array */
			$shortcodes = array();
			foreach( $shortcodes_located as $child_array ) {
				
				foreach( $child_array as $key => $value ) {
				
					if ( ! isset( $shortcodes[$key] ) ) 
						$shortcodes[$key] = array();
						
					$shortcodes[$key][] = $value;
					
				}
				
			}
			
			/* Loop through the shortcodes again and put together shortcode string */
			$shortcode_strings = array();
			foreach( $shortcodes as &$shortcode ) {
				
				$shortcode_string = '['. $shortcode[2];
				
				/* If arguments exist, add them to the shortcode string */
				if ( ! empty( $shortcode[3] ) )
					$shortcode_string .= $shortcode[3];
				
				/* Close shortcode string */
				$shortcode_string .= ']';
				
				/* Add to array */
				$shortcode_strings[] = $shortcode_string;
				
			}
			
			/* Return found shortcodes */
			return $shortcode_strings;
			
		}
		
		/* Get regex for specific shortcode */
		function get_specific_shortcode_regex( $shortcode ) {
			
			return
				  '\\['                              // Opening bracket
				. '(\\[?)'                           // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
				. "($shortcode)"                     // 2: Shortcode name
				. '(?![\\w-])'                       // Not followed by word character or hyphen
				. '('                                // 3: Unroll the loop: Inside the opening shortcode tag
				.     '[^\\]\\/]*'                   // Not a closing bracket or forward slash
				.     '(?:'
				.         '\\/(?!\\])'               // A forward slash not followed by a closing bracket
				.         '[^\\]\\/]*'               // Not a closing bracket or forward slash
				.     ')*?'
				. ')'
				. '(?:'
				.     '(\\/)'                        // 4: Self closing tag ...
				.     '\\]'                          // ... and closing bracket
				. '|'
				.     '\\]'                          // Closing bracket
				.     '(?:'
				.         '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
				.             '[^\\[]*+'             // Not an opening bracket
				.             '(?:'
				.                 '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
				.                 '[^\\[]*+'         // Not an opening bracket
				.             ')*+'
				.         ')'
				.         '\\[\\/\\2\\]'             // Closing shortcode tag
				.     ')?'
				. ')'
				. '(\\]?)';                          // 6: Optional second closing brocket for escaping shortcodes: [[tag]]
			
			
		}
		
	}

	Shortcode_Locator::get_instance();
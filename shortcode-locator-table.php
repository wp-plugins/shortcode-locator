<?php
	
	/* Load WP_List_Table class if it does not exist yet */
	if ( ! class_exists( 'WP_List_Table' ) )
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

	class Shortcode_Locator_Table extends WP_List_Table {
		
		public $items = array();
		private $per_page = 25;
		private $shortcodes = array();
		
		function __construct() {
			
			global $status, $page;
			
			parent::__construct( array(
				'singular'		=>	'post',
				'plural'		=>	'posts',
				'ajax'			=>	false
			) );
						
			
			/* Set available shortcodes */
			$this->set_shortcodes();
			
		}
		
		/* Set table columns */
		function get_columns() {
			
			return array(
				'title'			=>	'Post Title',
				'type'			=>	'Post Type',
				'shortcodes'	=>	'Shortcodes Used'
			);
			
		}

		/* Set table sortable columns */
		function get_sortable_columns() {
			
			return array();
			
		}
				
		/* Prepare shortcodes for table */
		function prepare_items() {
			
			/* Define column headers */
			$columns = $this->get_columns();
			$hidden = array();
			$sortable = $this->get_sortable_columns();
			$this->_column_headers = array($columns, $hidden, $sortable);
			
			/* Prepare filter for query */
			$query_post_types = ( ! empty( $_REQUEST['filter_post_type'] ) ) ? sanitize_key( $_REQUEST['filter_post_type'] ) : array_keys( Shortcode_Locator::$post_types );
			$query_orderby = ( isset( $_GET['orderby'] ) ) ? sanitize_text_field( $_GET['orderby'] ) : 'title';
			$query_order = ( isset( $_GET['order'] ) ) ? sanitize_text_field( $_GET['order'] ) : 'ASC';
			$query_shortcode = ( ! empty( $_REQUEST['filter_shortcode'] ) ) ? '['. $_REQUEST['filter_shortcode'] : '';
						
			/* Run query to fetch posts */
			$posts = new WP_Query( array(
				'order'				=>	$query_order,
				'orderby'			=>	$query_orderby,
				'paged'				=>	$this->get_pagenum(),
				'posts_per_page'	=>	$this->per_page,
				'post_type'			=>	$query_post_types,
				's'					=>	$query_shortcode
			) );
			
			/* Push posts to table data */
			foreach( $posts->posts as $post ) {
				
				$this->items[] = array(
					'id'			=>	$post->ID,
					'shortcodes'	=>	Shortcode_Locator::get_shortcodes_for_post( $post->post_content ),
					'title'			=>	$post->post_title,
					'type'			=>	Shortcode_Locator::$post_types[$post->post_type]
				);
				
			}
			
			/* Setup pagination data */
			$this->set_pagination_args( array(
				'per_page'		=>	$this->per_page,
				'total_items'	=>	$posts->found_posts
			) );
						
		}
		
		/* Render default column */
		function column_default( $item, $column_name ) {
			
			return $item[$column_name];
			
		}
		
		/* Render title column */
		function column_title( $item ) {
			
			$actions = array(
				'edit'		=>	'<a href="'. get_edit_post_link( $item['id'] ) .'">Edit</a>',
				'view'		=>	'<a href="'. get_permalink( $item['id'] ) .'">View</a>'
			);
			
			return $item['title'] . $this->row_actions( $actions );
			
		}
		
		/* Render shortcodes column */
		function column_shortcodes( $item ) {
			
			return implode( '<br />', $item['shortcodes'] );
			
		}
		
		/* Add table actions */
		function extra_tablenav( $position ) {
			
			/* Open table actions container and form */
			echo '<div class="alignleft actions"><form method="get"><input type="hidden" name="page" value="'. Shortcode_Locator::$admin_page_slug .'">';
			
			/* Open post types filter */
			echo '<select name="filter_post_type">';
			
			/* Set default post types filter value */
			echo '<option value="">Filter By Post Type</option>';
			
			/* Loop through post types and add to post types filter drop down */
			foreach( Shortcode_Locator::$post_types as $value => $label )	
				echo '<option value="'. $value .'" '. selected( ( isset( $_REQUEST['filter_post_type'] ) ) ? $_REQUEST['filter_post_type'] : null, $value, false ) .'>'. $label .'</option>';
			
			/* Close post types filter */
			echo '</select>';

			/* Open shortcodes filter */
			echo '<select name="filter_shortcode">';
			
			/* Set default shortcodes filter value */
			echo '<option value="">Filter By Shortcode</option>';
			
			/* Loop through shortcodes and add to shortcodes filter drop down */
			foreach( $this->shortcodes as $value => $label )	
				echo '<option value="'. $value .'" '. selected( ( isset( $_REQUEST['filter_shortcode'] ) ) ? $_REQUEST['filter_shortcode'] : null, $value, false ) .'>'. $label .'</option>';
			
			/* Close shortcodes filter */
			echo '</select>';
			
			/* Add submit button */
			echo '<input type="submit" id="post-query-submit" class="button" value="Filter">';
			
			/* Close table actions form and container */
			echo '</form></div>';
			
			
		}
		
		/* Display tablenav */
		protected function display_tablenav( $which ) {
	
			echo '<div class="tablenav '. esc_attr( $which ) .'">';
			
			$this->extra_tablenav( $which );
			$this->pagination( $which );

			echo '<br class="clear" /></div>';
		}
				
		/* Set available shortcodes */
		function set_shortcodes() {
			
			global $shortcode_tags;
			
			/* Loop through registered shortcodes and push to class array */
			foreach( $shortcode_tags as $shortcode => $function )
				$this->shortcodes[$shortcode] = '['. $shortcode .']';
			
		}
		
		/* Set no items found message */
		function no_items() {
			
			echo 'No posts found.';
			
		}
		
	}
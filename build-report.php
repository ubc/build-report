<?php 
/*
	Plugin Name: Build Report 
	Plugin URI: 
	Description: A set of shorcodes to allow the user to set up a way to any post type to be added to a que for printing.
	Version: 0.1
	Author: CTLT, Enej 
	License: GPL2
	
	Copyright 2013  Enej  (email : PLUGIN AUTHOR EMAIL)
	
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/


Class CTLT_Build_Report{
	
	
	public static $cookie;
	public static $add_scripts;
	
	/**
	 * init function.
	 * 
	 * @access public
	 * @return void
	 */
	function init(){
		
		self::$cookie = ( isset( $_COOKIE['ctlt_report_builder'] ) ? explode( ',' , $_COOKIE['ctlt_report_builder'] ) : array() );
		self::$add_scripts = false;
		
		add_action( 'init', array( __CLASS__, 'start' ) );
		add_action( 'wp_footer', array(__CLASS__, 'print_scripts') );
		
		
		
		add_shortcode( 'add_to_report', array(__CLASS__, 'add_to_report' ) );
		add_shortcode( 'report_list', array(__CLASS__, 'report_list' ) );
		
		add_shortcode( 'display_report', array(__CLASS__, 'display_report' ) );
		
		add_action( 'wp_ajax_report_list', array(__CLASS__, 'updated_report_list' ) );
		
		// just for testing 
		if( isset( $_GET['delete-cookie'] ) ):
			self::delete_cookie();
		endif;
		
		if( isset( $_GET['print-report'] ) ):
		
			add_filter( 'body_class', array(__CLASS__, 'body_class_filter' ) );
			
			add_action( 'wp_enqueue_scripts', array(__CLASS__,  'add_stylesheet' ) );
		
		endif;
	}
	
	/**
	 * start function.
	 * 
	 * @access public
	 * @return void
	 */
	function start() {
		
		wp_register_script( 'build-report', plugins_url( '/js/build-report.js', __FILE__ ) , array( 'jquery'), '1', true );
			
		if(isset( $_GET['report_builder'] ) && is_numeric( $_GET['report_builder'] ) ):
			self::add_or_remove_from_builder( $_GET['report_builder'] );
			
			add_action( 'template_redirect', array( __CLASS__, 'redirect_to' ), 2 );
		
		endif;
		
	}
	
	/**
	 * redirect_to function.
	 * 
	 * @access public
	 * @return void
	 */
	function redirect_to(){
	
		$redirect = remove_query_arg( array( 'report_builder' ) );
		wp_redirect( $redirect );
		exit();
	
	}
	
	
	/**
	 * body_class_filter function.
	 * 
	 * @access public
	 * @param mixed $body_class
	 * @return void
	 */
	function body_class_filter($body_class) {
			
		$body_class[] = 'print-report';
		
		return $body_class;
	
	}
	
	 /**
     * Enqueue plugin style-file
     */
    function add_stylesheet() {
        // Respects SSL, Style.css is relative to the current file
        wp_register_style( 'print-report', plugins_url('css/print.css', __FILE__) );
        wp_enqueue_style( 'print-report' );
    }
	
	
	/**
	 * print_scripts function.
	 * 
	 * @access public
	 * @return void
	 */
	function print_scripts(){
		
		
		if( self::$add_scripts ):
			wp_localize_script( 'build-report', 'build_report_ajaxurl', admin_url( 'admin-ajax.php' ) );
			wp_enqueue_script( 'build-report' );
		endif;
	}
	/**
	 * add_to_report function.
	 *
	 * shortcode function
	 *
	 * @access public
	 * @param mixed $atts
	 * @return string
	 */
	function add_to_report( $atts ){
		global $post;
		
		self::$add_scripts = true;
		
		extract( shortcode_atts( array(
			'remove_text' 	=> 'Remove from my report',
			'add_text' 		=> 'Add to my report',
			'post_id'		=> $post->ID,
			'class'			=> 'btn'
			), $atts ) );
			
		if( is_singular() ):
			$in_report = self::in_report( $post_id );
			$text = ( $in_report ? $remove_text : $add_text );
			$action = ( $in_report ? 'remove' : 'add' );
			$url  = "?report_builder=". $post_id;
			$html = '<a href="'.esc_url($url).'" data-post_id="'.esc_attr($post_id).'" data-add_text="'.$add_text.'" data-remove_text="'.$remove_text.'" data-action="'.esc_attr($action).'"  class="build-report-action action-'.$action.'-post '.esc_attr($class).'">'.esc_html($text).'</a>';
			
		else:
			$html = '';
		endif;
		
		return $html;
	}
	
	/* ajax */
	
	function updated_report_list() {
		
		$query2 =  self::get_report_data();
		
		$data = array();
		
		while( $query2->have_posts() ): $query2->the_post();
			
			$data[ $post->post_type ][] = array(
				'title' => get_the_title(),
				'url'   => get_permalink(),
				'id' => get_the_ID()
			);
			
		endwhile;
		
		wp_reset_postdata();
		
		$html = '';
		if( !empty( $data ) ):
			
			ob_start();
			
			self::report_list_html( $data );
			
			$html = ob_get_contents();
			ob_end_clean();
			
		endif;
		
		echo $html;
		die();
	
	}
	
	/**
	 * report_list function.
	 * 
	 * @access public
	 * @return void
	 */
	function report_list( $atts ){
		global $post;
		
		if(empty( self::$cookie ) )
			return '';
		
		self::$add_scripts = true;
		
		extract( shortcode_atts( array(
			'print_url' 	=> null
			), $atts ) );
		
		$query2 =  self::get_report_data();
		
		$data = array();
		
		while( $query2->have_posts() ): $query2->the_post();
			
			$data[ $post->post_type ][] = array(
				'title' => get_the_title(),
				'url'   => get_permalink(),
				'id' => get_the_ID()
			);
			
		endwhile;
		
		wp_reset_postdata();
		
		
		ob_start();
		
		?>
		<div class="report-list-shell">
		
		<div class="report-list-wrap">
		<?php 
		if( !empty( $data ) ):
			self::report_list_html( $data );
		endif; ?>
		</div>
		<?php
		if( $print_url ):
		?>
		<a href="<?php echo $print_url; ?>?print-report" title="Print Report" class="btn">Print report <i class="icon-print"></i></a>
		
		<?php
		else: ?>
			<span class="error">Please Specify the print_url attribute</span>
		<?php 
		endif; ?>
		</div><!-- end of report list -->
		<?php
		$html = ob_get_contents();
		ob_end_clean();
		
		return $html;
	
	}
	
	/**
	 * report_list_html function.
	 * 
	 * @access public
	 * @param mixed $data
	 * @return void
	 */
	function report_list_html( $data ) {
	
		foreach($data as $post_type => $post_type_array): ?>
			<h3 class="report-list-title"><?php echo $post_type; ?></h3>
			<ul class="report_list report_list_<?php echo $post_type; ?>">
			
			<?php foreach( $post_type_array as $post_info ): ?>
				<li><a href="<?php echo $post_info['url']; ?>" class=""><?php echo $post_info['title']; ?></a> <a href="?report_builder=<?php echo $post_info['id']; ?>" class="build-report-action action-remove-post">remove</a></li>
			<?php endforeach; ?>
			
			</ul>
		<?php	
		endforeach;
		
	}
	
	
	/**
	 * display_report function.
	 * 
	 * @access public
	 * @param mixed $attr
	 * @return void
	 */
	function display_report( $attr ){
	
		global $post;
		
		if(empty( self::$cookie ) )
			return '';
		
		self::$add_scripts = true;
		
		extract( shortcode_atts( array(
			'print_url' 	=> null
			), $atts ) );
		
		
		$query2 =  self::get_report_data();
		
		$data = array();
		
		while( $query2->have_posts() ): $query2->the_post();
			
			$data[ $post->post_type ][] = array(
				'title' => get_the_title(),
				'url'   => get_permalink(),
				'id' => get_the_ID(),
				'content' => get_the_content()
			);
			
		endwhile;
		
		wp_reset_postdata();
		
		if( !empty( $data ) ):
			ob_start();
			
			?>
			<div class="report-list-shell">
			<?php
			foreach($data as $post_type => $post_type_array):?>
				
				<?php foreach( $post_type_array as $post_info ): ?>
					<div <?php post_class( '', $post_info['id']); ?>>
						<h3 class="page-<?php echo $post_type; ?> entry-title"><?php echo apply_filters( 'the_title',$post_info['title']); ?></h3>
					
						<div class="entry-content"><?php echo apply_filters( 'the_content', $post_info['content'] ); ?></div>
					
					</div>
				<?php endforeach; ?>
				
				</ul>
			<?php	
			endforeach;
			
			if( $print_url ):
			?>
			<a href="<?php echo $print_url; ?>?print-report" title="Print Report" target="_blank" class="btn">Print report <i class="icon-print"></i></a>
			
			<?php
			else: ?>
				<span class="error">Please Specify the print_url attribute</span>
			<?php 
			endif; ?>
			</div><!-- end of report list -->
			
			<?php if( isset( $_GET['print-report'] ) && $_GET['print-report'] == 'print' ): ?>
				<script>
				window.print();
				</script>
				<?php
			endif;
			$html = ob_get_contents();
			ob_end_clean();
			
			return $html;
		endif;
		
		return '';

	
	
	}
	/**
	 * in_report function.
	 *
	 * returns if post_id is saved in the cookie
	 *
	 * @access public
	 * @param int $post_id
	 * @return bool
	 */
	function in_report( $post_id ){
		
		if( !empty( self::$cookie ) ):
			
			return ( in_array( $post_id, self::$cookie ) ? true : false); 
		
		endif;
		
		return false;
	}
	
	
	/**
	 * get_report_data function.
	 * 
	 * @access public
	 * @return void
	 */
	function get_report_data() {
	
		return new WP_Query( array( 'post_type' => 'any', 'post__in' => self::$cookie, 'orderby' => 'menu_order date', 'order' => 'ASC' ) );
	
	}
	
	
	
	/**
	 * add_or_remove_from_builder function.
	 * 
	 * either add to the report or remove from it
	 *
	 * @access public
	 * @param mixed $post_id
	 * @return void
	 */
	function add_or_remove_from_builder( $post_id ){
		
		if( !self::in_report( $post_id ) ):
			// lets add the post id to the report
			self::$cookie[] = $post_id;
			
			
		else:
			// remove the cookie 
			if(($key = array_search( $post_id, self::$cookie)) !== false) {
    			unset(self::$cookie[$key]);
			}
			
		endif;
		
		self::set_cookie();
	}
	
	/**
	 * set_cookie function.
	 * set the cookie for 14 days
	 * @access public
	 * @return void
	 */
	function set_cookie() {
	
		
		// set the cookie for 14 days 
		setcookie('ctlt_report_builder', implode( ',', self::$cookie ), time()+60*60*14 , COOKIEPATH, COOKIE_DOMAIN, false);
		
	}
	
	/**
	 * delete_cookie function.
	 * 
	 * @access public
	 * @return void
	 */
	function delete_cookie(){
		
		setcookie('ctlt_report_builder', null , time()-60*60*14 );
		
	}

}

CTLT_Build_Report::init();
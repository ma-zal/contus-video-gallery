<?php
/*
  Name: Wordpress Video Gallery
  Plugin URI: http://www.apptha.com/category/extension/Wordpress/Video-Gallery
  Description: Wordpress Video Gallery Video Search Widget.
  Version: 2.6
  Author: Apptha
  Author URI: http://www.apptha.com
  License: GPL2
 */

class Widget_ContusVideoSearch_init extends WP_Widget {

	function Widget_ContusVideoSearch_init() {
		$widget_ops = array( 'classname' => 'Widget_ContusVideoSearch_init ', 'description' => 'Displays Product tag link in product page' );
		$this->WP_Widget( 'Widget_ContusVideoSearch', 'Contus Video Search', $widget_ops );
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => 'Video Search' ) );
		$title = esc_attr( $instance['title'] );
		?>
		<p><label for='<?php echo esc_html( $this->get_field_id( 'title' ) ); ?>'>Title: <input class='widefat' id='<?php echo esc_html( $this->get_field_id( 'title' ) ); ?>' name='<?php echo esc_html( $this->get_field_name( 'title' ) ); ?>' type='text' value='<?php echo esc_html( $title ); ?>' /></label></p>
		<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = $new_instance['title'];
		return $instance;
	}

	function widget( $args, $instance ) {
		## and after_title are the array keys." - These are set up by the theme
		extract( $args, EXTR_SKIP );
		$title = empty( $instance['title'] ) ? ' ' : apply_filters( 'widget_title', $instance['title'] );
		global $wpdb;
		## These are our own options
		?>
		<!-- For Getting The Page Id More and Video-->
		<?php
		$moreName = $wpdb->get_var( 'SELECT ID FROM ' . $wpdb->prefix . 'posts WHERE post_content="[videomore]" AND post_status="publish" AND post_type="page" LIMIT 1' );
		## Video Search
		$searchVal = __( 'Video Search ...', 'video_gallery' );
		$focusVal  = 'onfocus="if(this.value == \'' . $searchVal . '\' )this.value= \'\' "';
		$blurVal   = ' onblur="if(this.value == \'\' )this.value= \'' . $searchVal . '\' "';
		$div       = '<div id="videos-search"  class="widget widget_search sidebar-wrap "><h3 class="widget-title">' . $title . '</h3>';
		$div      .= '<form role="search" method="get" id="videosearchform" action="' . home_url( '/' ) . '" >
					<div><label class="screen-reader-text" >' . __( 'Search for:' ) . '</label>
					<input type="hidden" value="' . $moreName . '" name="page_id" id="page_id"  />
					<input type="text" value="' . $searchVal . '" ' . $focusVal . $blurVal . ' name="video_search" id="video_search"  />
					<input type="submit" id="videosearchsubmit" value="' . __( 'Search', 'video_gallery' ) . '" />
					</div>
					</form>';
		$div     .= '</div>';
		echo balanceTags( $div );
	}
}
add_action( 'widgets_init', create_function( '', 'return register_widget("Widget_ContusVideoSearch_init" );' ) ); ##adding product tag widget
?>
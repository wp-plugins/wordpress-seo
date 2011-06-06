<?php 

class WPSEO_Widgets {
	function WPSEO_Widgets() {
		$options = get_wpseo_options();
		
		if ( isset($options['replacemetawidget']) && $options['replacemetawidget'] == "on" ) {
			add_action( 'plugins_loaded', array(&$this, 'load_widgets'), 10 );
			add_action( 'widgets_init', array(&$this, 'widgets_init'), 99 );
		}	
	}
	
	function widgets_init() {
		unregister_widget('WP_Widget_Meta');
		register_widget('WPSEO_Widget_Meta');
	}	
	
	function load_widgets() {
		require WPSEO_PATH.'inc/class-meta-widget.php';
	}
}

$wpseo_widgets = new WPSEO_Widgets();
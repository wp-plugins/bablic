<?php
/*
Plugin Name: Bablic
Plugin URI: http://www.bablic.com/docs#wordpress'
Description: Integrates your site with Bablic localization cloud service.
Version: 0.1
Author: Ishai Jaffe
Author URI: http://www.bablic.com
License: GPLv3
	Copyright 2012 Bablic
*/
class bablic {
	// declare globals
	var $options_name = 'bablic_item';
	var $options_group = 'bablic_option_option';
	var $options_page = 'bablic';
	var $plugin_homepage = 'http://www.bablic.com/docs#wordpress';
	var $plugin_name = 'Bablic';
	var $plugin_textdomain = 'Bablic';

	// constructor
	function bablic() {
		$options = $this->optionsGetOptions();
		add_filter( 'plugin_row_meta', array( &$this, 'optionsSetPluginMeta' ), 10, 2 ); // add plugin page meta links
		add_action( 'admin_init', array( &$this, 'optionsInit' ) ); // whitelist options page
		add_action( 'admin_menu', array( &$this, 'optionsAddPage' ) ); // add link to plugin's settings page in 'settings' menu on admin menu initilization
		add_action( 'wp_footer', array( &$this, 'getBablicCode' ), 99999 ); 
		register_activation_hook( __FILE__, array( &$this, 'optionsCompat' ) );
	}

	// load i18n textdomain
	function loadTextDomain() {
		load_plugin_textdomain( $this->plugin_textdomain, false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) . 'lang/' );
	}
	
	
	// compatability with upgrade from version <1.4
	function optionsCompat() {
		$old_options = get_option( 'ssga_item' );
		if ( !$old_options ) return false;
		
		$defaults = optionsGetDefaults();
		foreach( $defaults as $key => $value )
			if( !isset( $old_options[$key] ) )
				$old_options[$key] = $value;
		
		add_option( $this->options_name, $old_options, '', false );
		delete_option( 'ssga_item' );
		return true;
	}
	
	// get default plugin options
	function optionsGetDefaults() { 
		$defaults = array( 
			'site_id' => '', 
			'activate' => 0
		);
		return $defaults;
	}
	
	function optionsGetOptions() {
		$options = get_option( $this->options_name, $this->optionsGetDefaults() );
		return $options;
	}
	
	// set plugin links
	function optionsSetPluginMeta( $links, $file ) { 
		$plugin = plugin_basename( __FILE__ );
		if ( $file == $plugin ) { // if called for THIS plugin then:
			$newlinks = array( '<a href="options-general.php?page=' . $this->options_page . '">' . __( 'Settings', $this->plugin_textdomain ) . '</a>' ); // array of links to add
			return array_merge( $links, $newlinks ); // merge new links into existing $links
		}
	return $links; // return the $links (merged or otherwise)
	}
	
	// plugin startup
	function optionsInit() { 
		register_setting( $this->options_group, $this->options_name, array( &$this, 'optionsValidate' ) );
	}
	
	// create and link options page
	function optionsAddPage() { 
		add_options_page( $this->plugin_name . ' ' . __( 'Settings', $this->plugin_textdomain ), __( 'Bablic', $this->plugin_textdomain ), 'manage_options', $this->options_page, array( &$this, 'optionsDrawPage' ) );
	}
	
	// sanitize and validate options input
	function optionsValidate( $input ) { 
		$input['activate'] = ( $input['activate'] ? 1 : 0 ); 	// (checkbox) if TRUE then 1, else NULL
		return $input;
	}
	
	// draw a checkbox option
	function optionsDrawCheckbox( $slug, $label, $style_checked='', $style_unchecked='' ) { 
		$options = $this->optionsGetOptions();
		if( !$options[$slug] ) 
			if( !empty( $style_unchecked ) ) $style = ' style="' . $style_unchecked . '"';
			else $style = '';
		else
			if( !empty( $style_checked ) ) $style = ' style="' . $style_checked . '"';
			else $style = ''; 
	?>
		 <!-- <?php _e( $label, $this->plugin_textdomain ); ?> -->
			<tr valign="top">
				<th scope="row">
					<label<?php echo $style; ?> for="<?php echo $this->options_name; ?>[<?php echo $slug; ?>]">
						<?php _e( $label, $this->plugin_textdomain ); ?>
					</label>
				</th>
				<td>
					<input name="<?php echo $this->options_name; ?>[<?php echo $slug; ?>]" type="checkbox" value="1" <?php checked( $options[$slug], 1 ); ?>/>
				</td>
			</tr>
			
	<?php }
	
	// draw the options page
	function optionsDrawPage() { ?>
		<div class="wrap">
		<div class="icon32" id="icon-options-general"><br /></div>
			<h2><?php echo $this->plugin_name . __( ' Settings', $this->plugin_textdomain ); ?></h2>
			<form name="form1" id="form1" method="post" action="options.php">
				<?php settings_fields( $this->options_group ); // nonce settings page ?>
				<?php $options = $this->optionsGetOptions();  //populate $options array from database ?>
				
				<!-- Description -->
				<p style="font-size:0.95em"><?php 
					printf( __( 'This plugin injects Bablic code snippet into your site. For localization management, access your account in <a href="%1$s">www.bablic.com</a>. If you have any questions, bug reports, or feature suggestions contact us at support@bablic.com.', $this->plugin_textdomain ), $this->plugin_homepage ); ?></p>
				<table class="form-table">
	
					<?php $this->optionsDrawCheckbox( 'activate', 'Activate', '', 'color:#f00;' ); ?>					 
	
					<tr valign="top"><th scope="row"><label for="<?php echo $this->options_name; ?>[site_id]">
					<?php _e( 'Bablic Site ID', $this->plugin_textdomain ); ?>: </label></th>
						<td>
							<input type="text" name="<?php echo $this->options_name; ?>[site_id]" value="<?php echo $options['site_id']; ?>" style="width:150px;" />
							<a href="http://www.bablic.com/console/new"><?php __('Create Site ID', $this->plugin_textdomain) ?></a>
						</td>
					</tr>
					
				</table>
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e( 'Save Changes', $this->plugin_textdomain ) ?>" />
				</p>
			</form>
		</div>
		
		<?php
	}
	
	// 	the Bablic snippet to be inserted
	function getBablicCode() { 
		$options = $this->optionsGetOptions();
	
	// header
	$header = sprintf( 
		__( '<!-- 
			Plugin: Bablic
	Plugin URL: %1$s', $this->plugin_textdomain ), 
		$this->plugin_name );
	
	// footer
	$footer = '
	-->';
	
	// code removed for all pages
	$disabled = $header . __( 'You\'ve chosen to prevent the snippet from being inserted on 
	any page. 
	
	You can enable the insertion of the snippet by going to 
	Settings > Bablic on the Dashboard.', $this->plugin_textdomain ) . $footer;
	
	// core snippet
	$core = sprintf( '<script src="//api.bablic.com/js/lib/jquery.js" type="text/javascript"></script>
					<script type="text/javascript">
						document.body.style.visibility="hidden";var bablic=bablic||{};(function(){bablic._pl=[];var e=["on","processElement","suppress","__","__n","getLocal","getLink","redirectTo"];for(var t=0;t<e.length;t++)bablic[e[t]]=function(e){return function(){return bablic._pl.push({n:e,a:arguments}),null}}(e[t]);setTimeout(function(){document.body.style.visibility=""},2e3);var n=document.createElement("script");n.type="text/javascript",n.async=!0,n.src=("https:"==document.location.protocol?"https://":"http://")+"api.bablic.com/js/bablic.js";var r=document.getElementsByTagName("script")[0];r.parentNode.insertBefore(n,r)})();
						bablic.Site="%1$s";
					</script>', $options['site_id']);
	
	// build code
	if( !$options['activate'] || $options['site_id'] == '' ) 
		echo $disabled; 
	elseif( current_user_can( 'manage_options' )) 
		echo ""; 
	else
		echo $header . "\n\n" . $footer . "\n\n" . $core ; 
	}
} // end class

$bablic_instance = new bablic;
?>
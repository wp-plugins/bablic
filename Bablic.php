<?php
/*
Plugin Name: Bablic
Plugin URI: http://www.bablic.com/docs#wordpress'
Description: Integrates your site with Bablic localization cloud service.
Version: 1.2
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
	var $bablic_docs = 'http://www.bablic.com/docs';
	var $plugin_name = 'Bablic';
	var $plugin_textdomain = 'Bablic';

	// constructor
	function bablic() {
		$options = $this->optionsGetOptions();
		add_filter( 'plugin_row_meta', array( &$this, 'optionsSetPluginMeta' ), 10, 2 ); // add plugin page meta links
		add_action( 'admin_init', array( &$this, 'optionsInit' ) ); // whitelist options page
		add_action( 'admin_menu', array( &$this, 'optionsAddPage' ) ); // add link to plugin's settings page in 'settings' menu on admin menu initilization

		add_action( 'wp_head', array( &$this, 'getBablicCode' ));
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

	    function addAdminScripts($hook_suffix){
            global $my_settings_page;

            wp_enqueue_script(
                    'bablic-admin',
                    plugins_url('/admin.js', __FILE__)
                );
        }
	
	// create and link options page
	function optionsAddPage() {
        global $my_settings_page;
		$my_settings_page = add_options_page( $this->plugin_name . ' ' . __( 'Settings', $this->plugin_textdomain ), __( 'Bablic', $this->plugin_textdomain ), 'manage_options', $this->options_page, array( &$this, 'optionsDrawPage' ) );


        add_action( 'admin_enqueue_scripts',array( &$this, 'addAdminScripts' ));
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
					<input id="<?php echo $this->options_name; ?>_<?php echo $slug; ?>"  name="<?php echo $this->options_name; ?>[<?php echo $slug; ?>]" type="checkbox" value="1" <?php checked( $options[$slug], 1 ); ?>/>
				</td>
			</tr>
			
	<?php }
	
	// draw the options page
	function optionsDrawPage() { ?>
		<div class="wrap" style="background: #fff url(http://www.bablic.com/images/body-bg.png) 0 0 repeat-x;
                                 padding: 5px;">
		<div class="icon32" id="icon-options-general"><br /></div>
			<h2><?php echo $this->plugin_name . __( ' Settings', $this->plugin_textdomain ); ?></h2>
			<form name="form1" id="form1" method="post" action="options.php">
				<?php settings_fields( $this->options_group ); // nonce settings page ?>
				<?php $options = $this->optionsGetOptions();  //populate $options array from database ?>
				
				<!-- Description -->
				<p style="font-size:0.95em"><?php 
					printf( __( 'Bablic provides cloud localization. Need help getting started? visit <a target="_blank" href="%1$s">bablic documentation</a> or
					 <a target="_blank" href="http://www.bablic.com/contacts">contact us</a>.', $this->plugin_textdomain ), $this->bablic_docs ); ?></p>
				<table class="form-table">
	
					<?php $this->optionsDrawCheckbox( 'activate', 'Activate', '', 'color:#f00;' ); ?>					 
	
					<tr valign="top"><th scope="row"><label for="<?php echo $this->options_name; ?>[site_id]">
					<?php _e( 'Bablic Site ID', $this->plugin_textdomain ); ?>: </label></th>
						<td>
							<input placeholder="In case you already have a Bablic ID" type="text" id="bablic_item_site_id"  name="<?php echo $this->options_name; ?>[site_id]" value="<?php echo $options['site_id']; ?>" style="width:400px;" />
							<a href="http://www.bablic.com/console/new"><?php __('Create Site ID', $this->plugin_textdomain) ?></a>
						</td>
					</tr>
					
				</table>
				<p class="submit">
                	<input type="submit" class="button-primary" value="<?php _e( 'Save Changes', $this->plugin_textdomain ) ?>" />
                </p>
				<div>
				<iframe id="bablic_embedded" src=""
				style="width:100%; height:990px; display:<?php echo $options['activate'] ?'' : 'none'; ?>" />
				</div>
		 			</form>
         		</div>

		<?php
	}
	
	// 	the Bablic snippet to be inserted
	function getBablicCode() { 
		$options = $this->optionsGetOptions();
	
	// header
	$header = '<!-- start Bablic -->';

	// footer
	$footer = '<!-- end Bablic -->';

	// code removed for all pages
	$disabled = $header . "\n\n" . __( 'You\'ve chosen to prevent the snippet from being inserted on
	any page. 
	
	You can enable the insertion of the snippet by going to 
	Settings > Bablic on the Dashboard.', $this->plugin_textdomain ) . "\n\n" .  $footer;
	
	// core snippet
	$core = sprintf('<meta name="google" value="notranslate" />
	    <script type="text/javascript">
	       var bablic=bablic||{};
	       bablic.Site="%1$s";
	       </script>
	       <script type="text/javascript" src="//api.bablic.com/js/bablic.js"></script>
	       <script>
	            bablic.exclude("#wpadminbar");
	       </script>
	       ', $options['site_id']);
	
	// build code
	if( !$options['activate'] || $options['site_id'] == '' ) 
		echo $disabled; 
	elseif( is_admin())
		echo ""; 
	else
		echo $header . "\n\n"  . $core . "\n\n" . $footer ;
	}
} // end class

$bablic_instance = new bablic;
?>
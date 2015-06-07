<?php
/*
Plugin Name: Bablic
Plugin URI: https://www.bablic.com/docs#wordpress'
Description: Integrates your site with Bablic localization cloud service.
Version: 1.7
Author: Ishai Jaffe
Author URI: https://www.bablic.com
License: GPLv3
	Copyright 2012 Bablic
*/
class bablic {
	// declare globals
	var $options_name = 'bablic_item';
	var $options_group = 'bablic_option_option';
	var $options_page = 'bablic';
	var $plugin_homepage = 'https://www.bablic.com/docs#wordpress';
	var $bablic_docs = 'https://www.bablic.com/docs';
	var $plugin_name = 'Bablic';
	var $plugin_textdomain = 'Bablic';
	var $bablic_version = '1.6';
    var $query_var = 'bablic_locale';
	// constructor
	function bablic() {
		$options = $this->optionsGetOptions();
		add_filter( 'plugin_row_meta', array( &$this, 'optionsSetPluginMeta' ), 10, 2 ); // add plugin page meta links
		add_action( 'admin_init', array( &$this, 'optionsInit' ) ); // whitelist options page
		add_action( 'admin_menu', array( &$this, 'optionsAddPage' ) ); // add link to plugin's settings page in 'settings' menu on admin menu initilization

		add_action( 'wp_head', array( &$this, 'getBablicCode' ));
		add_filter('rewrite_rules_array', array(&$this, 'bablic_insert_rewrite_rules'));
		//add_filter('generate_rewrite_rules',array( &$this,'after' ));
//		add_filter( 'rewrite_rules_array',array( &$this,'bablic_insert_rewrite_rules' ));
        //add_action( 'wp_loaded',array( &$this,'bablic_flush_rules' ));
        //add_filter( 'query_vars',array( &$this,'bablic_insert_query_vars' ));
		register_activation_hook( __FILE__, array( &$this, 'optionsCompat' ) );
        register_activation_hook(__FILE__, array(&$this, 'flush_rules'));
        register_deactivation_hook(__FILE__, array(&$this, 'flush_rules'));
		
		// replace all links
		add_filter( 'post_link', array(&$this, 'append_prefix'), 10, 3 );
		add_filter( 'page_link', array(&$this, 'append_prefix'), 10, 3 );
		add_filter( 'author_link', array(&$this, 'append_prefix'), 10, 3);
		add_filter( 'attachment_link', array(&$this, 'append_prefix'), 10, 3);
		add_filter( 'comment_reply_link', array(&$this, 'append_prefix'), 10, 3 );
		add_filter( 'post_type_link', array(&$this, 'append_prefix'), 10, 3 );
		add_filter( 'day_link', array(&$this, 'append_prefix'), 10, 3 );
		add_filter( 'get_comment_author_link', array(&$this, 'append_prefix'), 10, 3 );
		add_filter( 'get_comment_author_url_link', array(&$this, 'append_prefix'), 10, 3 );
		add_filter( 'month_link', array(&$this, 'append_prefix'), 10, 3 );
		add_filter( 'the_permalink', array(&$this, 'append_prefix'), 10, 3 );
		add_filter( 'year_link', array(&$this, 'append_prefix'), 10, 3 );
		add_filter( 'tag_link', array(&$this, 'append_prefix'), 10, 3 );
		add_filter( 'term_link', array(&$this, 'append_prefix'), 10, 3 );
		
		add_filter('locale', array(&$this, 'get_locale'));

        add_action( 'admin_notices', array(&$this, 'bablic_admin_messages') );
        add_action('wp_ajax_bablicHideRating',array(&$this, 'bablic_hide_rating'));

	}
	
	function get_locale_from_url($url){
		$options = $this->optionsGetOptions();	
		$locales = $options['locales'];
		$locale_regex = '('.implode('|',$locales).')';
		$pattern = '/^\\/'.$locale_regex.'\\//';
		if(preg_match($pattern, $url, $matches))
			return $matches[1];
		
		$pattern = '/(\?|&)'.$this->query_var.'='.$locale_regex.'/';
		if(preg_match($pattern, $url, $matches))
			return $matches[2];
		return $options['orig'];
	}
	function get_locale($locale){
		$url = $_SERVER['REQUEST_URI'];
		return $this->get_locale_from_url($url);
	}
	
	function after($rules){
//		print_r('sta');
//		print_r($rules);
	}
	
	function make_locale_url($url,$locale,$is_sub_dir){
		if($is_sub_dir) {
			$prefix = $locale . '/';				
			if(strpos($url,'//') !== false)
				$url = preg_replace('/\/\/([^\/]+)\//', '//$1/'.$prefix, $url);	
			else
				$url = $prefix . $url;
			return str_replace('/'.$prefix.$prefix,'/'.$prefix,$url);
		}
		if(strpos($url,$this->query_var .'=') !== false)
			return $url;
		if(strpos($url,'?') !== false)
			return $url . '&'. $this->query_var .'=' . $locale;
		return $url . '?'. $this->query_var .'=' . $locale;
	}
	
	function append_prefix( $url){
		global $wp_rewrite;
	    $is_sub_dir = ($wp_rewrite->permalink_structure) !== '';
		$options = $this->optionsGetOptions();	
		if($options['dont_permalink'])
			return $url;

	    $locale = $this->get_locale_from_url($_SERVER['REQUEST_URI']);
		if($locale == $options['orig'])
			return $url;
		return $this->make_locale_url($url,$locale,$is_sub_dir);
	}
	 
	function flush_rules(){
		global $wp_rewrite;
    	$wp_rewrite->flush_rules();
	}
	
	function bablic_insert_rewrite_rules($old_rules) {
		//print_r($old_rules);
        $new_rules = array();
		$options = $this->optionsGetOptions();
		if($options['dont_permalink'])
			return $old_rules;
		$locales = $options['locales'];
		
        $locale_regex = "(" . implode("|",$locales) . ")/";
        $locale_replace = "&".$this->query_var."=\$matches[1]";
        $new_rules['^' . $locale_regex . "?$"] = "index.php?". $this->query_var ."=\$matches[1]";

        foreach ($old_rules as $regex => $replace) {
            $save_regex = $regex;
            $save_replace = $replace;
			
            $regex = $locale_regex . $regex;
            for ($param=0; $param<=10; $param++) {
                $replace = str_replace('[' . (9-$param) . ']', '[' . (10-$param) . ']', $replace);
            }
            $replace .= $locale_replace;
            $new_rules[$regex] = $replace;
            $new_rules[$save_regex] = $save_replace;
        }
        return $new_rules;
    }
	
	// flush_rules() if our rules are not yet included
    function bablic_flush_rules(){
		$options = $this->optionsGetOptions();
		$a = $options['locales'];
		//$newrules['^(' . implode('|',$a) . ')/([^?]*)?(.*)$'] = '$matches[2]?matches[3]&locale=$matches[1]';
		//$newrules['^(' . implode('|',$a) . ')/([^?]*)$'] = '$matches[2]?locale=$matches[1]';
		//add_rewrite_rule('^es/?', 'index.php?locales=es', 'top');
		//add_rewrite_rule('^(es|fr)/(.*)$', 'matches[2]?locale=$matches[1]', 'top');
		//add_rewrite_rule('^es/[a-z]*$', 'index.php?locale=es', 'top');
		//add_rewrite_rule('^es$', 'index.php?locale=es', 'top');
		//add_rewrite_rule('.', 'index.php?locale=es', 'top');
    	//$rules = get_option( 'rewrite_rules' );
    	//$newrules = $this->get_rules();		
		//$to_flush = false;
		//foreach($newrules as $key => $value)
		//	if(! isset( $rules[$key] )  )
		//		$to_flush = true;
		//i//f($to_flush){ 
    		//global $wp_rewrite;
    	   	//$wp_rewrite->flush_rules();
    	//}			
    }
	/*
	function get_rules(){
    	$newrules = array();		
    	return $newrules;
	}
	
*/

	// Adding the id var so that WP recognizes it
    function bablic_insert_query_vars( $vars )
    {
        array_push($vars, $this->query_var);
        //array_push($vars, 'what');
        return $vars;
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
			'locales' => array('en','es','fr','it'),
			'orig' => 'en',
			'dont_permalink' => false,
			'date' => '',
			'rated' => 'no'
		);
		return $defaults;
	}
	
	function optionsGetOptions() {
		$options = get_option( $this->options_name, $this->optionsGetDefaults() );
		if(!$options['date'] || $options['date'] == ''){
		    $options['date'] = new DateTime('NOW');
		    update_option($this->options_name, $options);
		}
		$defaults = $this->optionsGetDefaults();
		foreach($defaults as $key => $value){
			if(!isset($options[$key]))
				$options[$key] = $value;
		}
		return $options;
	}

	function updateOptions($options){
	    update_option($this->options_name, $options);
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
                    'bablic-admin-sdk',
                    'https://www.bablic.com/dist/js/sdk.min.js'
                );
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
		
		add_action('load-'.$my_settings_page,array(&$this,'do_on_my_plugin_settings_save'));

 	}
	
	function do_on_my_plugin_settings_save(){
	  if(isset($_GET['settings-updated']) && $_GET['settings-updated'])
	   {
		    global $wp_rewrite;
    	   	$wp_rewrite->flush_rules();
	   }
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
	function optionsDrawPage() { 
		$options = $this->optionsGetOptions();
		$isFirstTime = $options['site_id'] == '';
	?>
		<div class="wrap" style="background: #fff; padding: 5px;">
		<div class="icon32" id="icon-options-general"><br /></div>
			<h2><?php echo $this->plugin_name; ?></h2>
			<form name="form1" id="form1" method="post" action="options.php">
				<?php settings_fields( $this->options_group ); // nonce settings page ?>
				<?php $options = $this->optionsGetOptions();  //populate $options array from database ?>
				
				<!-- Description -->
				<p style="font-size:0.95em"><?php 
				echo $isFirstTime ? __('Bablic makes Wordpress translation easy. Just click START NOW below and enter your website\'s URL in the popup in order to translate it through our user-friendly editor.', $this->plugin_textdomain) :
				__('Have any questions or concerns? Need help? Email <a href="mailto:support@bablic.com">support@bablic.com</a> for free support.'); ?></p>
				<table class="form-table">
	

	
					<tr valign="top">
						<td>
						<?php if(!$isFirstTime) { ?>						
						To make translation changes, visit Bablic's editor by clicking the button below: <br><br>
						<?php } ?>
							<input type="hidden" id="bablic_item_site_id"  name="<?php echo $this->options_name; ?>[site_id]" value="<?php echo $options['site_id']; ?>" style="width:400px;" />
							<button id="bablic_login" class="button button-primary"><?php echo $isFirstTime ? 'START NOW' : 'BABLIC EDITOR' ?></button>
						</td>
					</tr>
					
				</table>
				<div>
				<div id="bablicLoggedIn">				    
					<input type="hidden" id="bablic_item_orig" name="<?php echo $this->options_name; ?>[orig]" value="<?php echo $options['orig']; ?>" />
					<?php foreach($options['locales'] as $i => $locale): ?>
						<input type="hidden" id="bablic_item_locales[<?php echo $i; ?>]" name="<?php echo $this->options_name; ?>[locales][<?php echo $i; ?>]" value="<?php echo $locale; ?>" />
					<?php endforeach; ?>
				</div>
				<?php if(!$isFirstTime) { ?>
				<div>
					<h3>Settings</h3>
					<input type="hidden" id="bablic_dont_permalink_hidden" name="<?php echo $this->options_name; ?>[dont_permalink]" value="<?php echo $options['dont_permalink']; ?>" />
					<label><input type="checkbox" id="bablic_dont_permalink" <?php checked( 1, !$options['dont_permalink'], true ) ?>  > Generate SEO-friendly localization urls (for example: /es/, /fr/about, ...)</label>
				</div>
				<?php } ?>
				</div>
		 			</form>
         		</div>

		<?php
	} 
	
	// 	the Bablic snippet to be inserted
	function getBablicCode() { 
		global $wp_rewrite;
		$options = $this->optionsGetOptions();
		
	    $is_sub_dir = !$options['dont_permalink'] && ($wp_rewrite->permalink_structure) !== '';
		
		$url = $_SERVER['REQUEST_URI'];
	    $locale = $this->get_locale_from_url($url);
		$locales = $options['locales'];
		$locale_regex = "(" . implode("|",$locales) . ")";		
		$no_locale = preg_replace('/^\/'.$locale_regex.'\//','/',$url);
		$no_locale = preg_replace('/(\\?|&)'.$this->query_var.'='.$locale_regex.'/','$1',$no_locale);
		$no_locale = substr($no_locale,1);
		foreach( $locales as $alt){
			if($alt != $locale)
				echo '<link rel="alternate" href="/' . $this->make_locale_url($no_locale,$alt,$is_sub_dir) . '" hreflang="'.$alt.'">';
		}
		if($locale != $options['orig'])
			echo '<link rel="alternate" href="/' . $no_locale . '" hreflang="'.$options['orig'].'">';
			
	// header
	$header = '<!-- start Bablic -->';

	// footer
	$footer = '<!-- end Bablic -->';

	// code removed for all pages
	$disabled = $header .  $footer;


	// core snippet
	$core = sprintf('
	   <script type="text/javascript">var bablic=bablic||{};bablic.Site="%1$s";bablic.locale="%2$s",bablic.localeURL="%3$s"</script><script type="text/javascript" src="//api.bablic.com/js/bablic.js?v=1.6"></script>
       <script>
            bablic.exclude("#wpadminbar,#wp-admin-bar-my-account");
       </script>
	       ', $options['site_id'],$options['dont_permalink'] ? '' : $locale,$is_sub_dir ? 'subdir' : 'querystring');
	
	// build code
	if( $options['site_id'] == '' )
		echo $disabled; 
	elseif( is_admin())
		echo ""; 
	else
		echo $header . "\n\n"  . $core . "\n\n" . $footer ;
	}




	function bablic_admin_messages() {
	    $options = $this->optionsGetOptions();
		//print_r $options;
	    $install_date = $options['date'];
        $display_date = date('Y-m-d h:i:s');
    	$datetime1 = $install_date;
    	$datetime2 = new DateTime($display_date);
    	$diff_intrval = round(($datetime2->format('U') - $datetime1->format('U')) / (60*60*24));
        if($diff_intrval >= 7 && $options['rated'] == 'no') {
    	 echo '<div class="bablic_fivestar" style="box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);">
        	<p>Love Bablic? Help us by rating it 5? on <a href="https://wordpress.org/support/view/plugin-reviews/bablic" class="thankyou bablicRate" target="_new" title="Ok, you deserved it" style="font-weight:bold;">WordPress.org</a> 
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:void(0);" class="bablicHideRating" style="font-weight:bold; font-size:9px;">Don\'t show again</a>
			</p>
        </div>
        <script>
        jQuery( document ).ready(function( $ ) {

        jQuery(\'.bablicHideRating,.bablicRate\').click(function(){
            var data={\'action\':\'bablicHideRating\'}
                 jQuery.ajax({

            url: "'.admin_url( 'admin-ajax.php' ).'",
            type: "post",
            data: data,
            dataType: "json",
            async: !0,
            success: function(e) {
			   jQuery(\'.bablic_fivestar\').slideUp(\'slow\');
            }
             });
            })

        });
        </script>
        ';
        }
    }


    function bablic_hide_rating(){
        $options = $this->optionsGetOptions();
        $options['rated'] = 'yes';
        $this->updateOptions($options);
        echo json_encode(array("success")); exit;
    }

} // end class

$bablic_instance = new bablic;
?>
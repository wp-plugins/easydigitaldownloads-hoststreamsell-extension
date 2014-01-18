<?php
/*
Plugin Name: Easy Digital Downloads HSS Extension for Streaming Video
Plugin URI: http://hoststreamsell.com
Description: Sell Streaming Video Through WordPress (extends functionality in Easy Digital Downloads plugin)
Author: Gavin Byrne
Author URI: http://hoststreamsell.com
Contributors: 
Version: 0.96

Easy Digital Downloads HSS Extension for Streaming Video is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or 
any later version.

Easy Digital Downloads HSS Extension for Streaming Video is distributed in the hope that it will be useful, 
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Easy Digital Downloads. If not, see <http://www.gnu.org/licenses/>.
*/

/*requires_wordpress_version() {
	global $wp_version;
	$plugin = plugin_basename( __FILE__ );
	$plugin_data = get_plugin_data( __FILE__, false );
 
	if ( version_compare($wp_version, "3.3", "<" ) ) {
		if( is_plugin_active($plugin) ) {
			deactivate_plugins( $plugin );
			wp_die( "'".$plugin_data['Name']."' requires WordPress 3.3 or higher! Deactivating Plugin.<br /><br />Back to <a href='".admin_url()."'>WordPress admin</a>." );
		}
	}
}
add_action( 'admin_init', 'requires_wordpress_version' );
*/


/*
|--------------------------------------------------------------------------
| ERRORS DISPLAY
|--------------------------------------------------------------------------
*/

//$WP_DEBUG = true;

/*
|--------------------------------------------------------------------------
| CONSTANTS
|--------------------------------------------------------------------------
*/

// plugin folder url
if(!defined('EDD_HSS_PLUGIN_URL')) {
	define('EDD_HSS_PLUGIN_URL', plugin_dir_url( __FILE__ ));
}
// plugin folder path
if(!defined('EDD_HSS_PLUGIN_DIR')) {
	define('EDD_HSS_PLUGIN_DIR', plugin_dir_path( __FILE__ ));
}
// plugin root file
if(!defined('EDD_HSS_PLUGIN_FILE')) {
	define('EDD_HSS_PLUGIN_FILE', __FILE__);
}

/*
|--------------------------------------------------------------------------
| GLOBALS
|--------------------------------------------------------------------------
*/

global $edd_options;


/*
|--------------------------------------------------------------------------
| INCLUDES
|--------------------------------------------------------------------------
*/

include_once(EDD_HSS_PLUGIN_DIR . 'includes/add-download.php');

if(!function_exists('_log')){
  function _log( $message ) {
        $upload_dir = wp_upload_dir();
        if (!file_exists($upload_dir['basedir'].'/hss_edd')) {
            mkdir($upload_dir['basedir'].'/hss_edd', 0777, true);
        }
        $fh = fopen($upload_dir['basedir'].'/hss_edd/log.txt', 'a');
        if( is_array( $message ) || is_object( $message ) ){
                fwrite($fh, print_r( $message, true ) );
        }else{
                fwrite($fh, $message."\n");
        }
    if( WP_DEBUG === true ){
      if( is_array( $message ) || is_object( $message ) ){
        error_log( print_r( $message, true ) );
      } else {
        error_log( $message );
      }
    }
  }
}


<?php

register_activation_hook(__FILE__, 'hss_edd_add_defaults');
register_uninstall_hook(__FILE__, 'hss_edd_delete_plugin_options');
add_action('admin_init', 'hss_edd_init' );

function hss_edd_add_defaults() {
        $tmp = get_option('hss_options');
    if(($tmp['chk_default_options_db']=='1')||(!is_array($tmp))) {
                delete_option('hss_options'); // so we don't have to reset all the 'off' checkboxes too! (don't think this is needed but leave for now)
                $arr = array(   "api_key" => "","jwplayer_stretching" => "uniform","logging" => "NORMAL", "database_id" => "0" );
                update_option('hss_options', $arr);
        }
}

function hss_edd_delete_plugin_options() {
        delete_option('hss_options');
}

// Register style sheet.
add_action( 'wp_enqueue_scripts', 'register_plugin_styles' );

/**
 * Register style sheet.
 */
function register_plugin_styles() {
	wp_register_style( 'easydigitaldownloads-hoststreamsell-extension', plugins_url( 'easydigitaldownloads-hoststreamsell-extension/css/hss-edd.css' ) );
	wp_enqueue_style( 'easydigitaldownloads-hoststreamsell-extension' );
}

function hss_edd_init(){
        register_setting( 'hss_edd_plugin_options', 'hss_options', 'hss_edd_validate_options' );
	$options = get_option('hss_options');
	if(is_array($options)){
		if (array_key_exists('database_id', $options)) {	
			if($options['database_id'] == ""){
				$options['database_id'] = "0";
				update_option('hss_options', $options);
			}
		}else{
			$options['database_id'] = "0";
			update_option('hss_options', $options);
		}
	        if (array_key_exists('watching_video_text', $options)==false) {
	                $options['watching_video_text'] = "You have access to this video";
	                update_option('hss_options', $options);
	        }
	}
}

function hss_edd_validate_options($input) {
         // strip html from textboxes
        $input['api_key'] =  trim(wp_filter_nohtml_kses($input['api_key']));
	
	if (!is_numeric($input['database_id'])) {
		$input['database_id'] = "0";
	}else{
		$input['database_id'] =  trim(wp_filter_nohtml_kses($input['database_id']));
	}
        return $input;
}

add_action('admin_head', 'hss_edd_action_javascript');

function hss_edd_action_javascript() {
?>
<script type="text/javascript" >
jQuery(document).ready(function($) {
    $('#hss_edd_ajax').click(function(){
        var data = {
            action: 'hss_edd_action'
        };
	$("#updateprogress").html("Updating... please wait!");

        // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
        $.post(ajaxurl, data, function(response) {
	    $("#updateprogress").html("");
            alert(response);
	    
        });
    });
});
</script>
<?php
}

add_action('wp_ajax_hss_edd_action', 'hss_edd_action_callback');

function hss_edd_action_callback() {
	$res = hss_edd_update_videos();
	if($res==True)
		echo "Success";
	else
		echo "Error occurred ".$res;
	die(); // this is required to return a proper result
}






add_action('wp_head','hss_edd_ajaxurl');
function hss_edd_ajaxurl() {
?>
<script type="text/javascript">
var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
</script>
<?php
}

add_action('wp_ajax_get_download_links', 'get_download_links_callback');
function get_download_links_callback() {
 $purchase_id = $_POST['purchase_id'];
 $video_id = get_post_meta($purchase_id, '_edd_video_id', true);
 echo hss_edd_get_video_download_links($video_id);

 die(); // this is required to return a proper result
}
 
add_action('wp_print_footer_scripts', 'get_download_links_javascript');
 
function get_download_links_javascript() {
?>
<script type="text/javascript" >
jQuery(document).ready(function($) {
    $('.myajaxdownloadlinks').attr("disabled", false);
    $('.myajaxdownloadlinks').click(function(event){
	$('#'+event.target.id).attr("disabled", true);
        var data = {
            action: 'get_download_links',
            purchase_id: event.target.id
        };

        // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
        $.post(ajaxurl, data, function(response) {
	    //$('#'+event.target.id).css("visibility", "hidden");
            $("#download_links_"+event.target.id).html(response);
	    setTimeout(function() {
		    $('#download_links_'+event.target.id).html("");
		    $('#'+event.target.id).attr("disabled", false);
		    //$('#'+event.target.id).css("visibility", "visible");
	    }, 240000);
        });
    });
});
</script>
<?php
}





function hss_edd_options_page () {

	if(isset($_POST['hss_edd_action']) && wp_verify_nonce($_GET['_wpnonce'], 'update_hss_edd_videos')) {
		?>
	        <div class="wrap">
		<div class="icon32" id="icon-options-general"><br></div>
	        <H2>Add Video Access</H2>
		<?php

		global $post;
		//echo (int)$_POST['video_ppv'];
		if (isset($_POST['video_ppv'])) {
	                $wp_user_info = get_userdata((int)$_POST['hss_edd_user_id']);
	                $user_info = array(
	                        'id'         => $wp_user_info->ID,
	                        'email'      => $wp_user_info->user_email,
	                        'first_name' => $wp_user_info->user_firstname,
	                        'last_name'  => $wp_user_info->user_lastname,
	                        'address'    => $wp_user_info->address
	                );
			$downloads = array();
			$details = array();

			$optionArray = $_POST['video_ppv'];
			for ($i=0; $i<count($optionArray); $i++) {
				$args = array(
				  'post_type'   => 'download',
				  'meta_query'  => array(
				    array(
				      'key' => '_edd_ppv_id',
				      'value' => (int)$optionArray[$i]
				    )
				  )
				);
				$my_query = new WP_Query( $args );
	
				if( $my_query->have_posts() ) {
					$my_query->the_post();

               			        $details[ $key ]  = array(
			                        'name'        => the_title("","",FALSE),
		                                'id'          => $post->ID,
		                                'item_number' => $item,
		                                'price'       => "0.00",
		                                'quantity'    => 1,
		                                'tax'         => 0,
		                        );

		                       $download = array(
		                                'id' => $post->ID,
		                                'options' => array('price_id' => (int)$optionArray[$i])
		                       );
					array_push($downloads, $download);

				}else{
					$args = array(
					  'post_type'   => 'download',
					  'meta_query'  => array(
					    array(
					      'key' => '_variable_pricing',
					      'value' => 1
					    )
					  )
					);
					$my_query = new WP_Query( $args );
					if( $my_query->have_posts() ) {
						while ($my_query->have_posts()) : $my_query->the_post();
							$prices = get_post_meta($post->ID, 'edd_variable_prices', true);
							foreach($prices as $key => $value) {
								if($key == (int)$optionArray[$i]){
									$details[ $key ]  = array(
							                        'name'        => get_the_title( $post->ID ),
							                        'id'          => $post->ID,
							                        'item_number' => $item,
							                        'price'       => "0.00",
							                        'quantity'    => 1,
							                        'tax'         => 0,
							                );
		
									//echo "TEST " . the_title("","",FALSE) . " " . $key;
									$download = array(
										'id' => $post->ID,
										'options' => array('price_id' => (int)$optionArray[$i])
									);
									array_push($downloads, $download);			
								}
							}
						endwhile;
					}
				}
			}
                       	$purchase_data = array(
	                       'downloads' => $downloads,
	                       'price' => "0",
	                       'purchase_key' => md5(uniqid(rand(), true)),
	                       'user_email' => $wp_user_info->user_email,
	                       'date' => date('Y-m-d H:i:s'),
	                       'user_id' => (int)$_POST['hss_edd_user_id'],
	                       //'post_data' => $_POST,
	                       'user_info' => $user_info,
	                       'cart_details' => $details,
	                );
	                // Record the pending payment
	                $payment = edd_insert_payment( $purchase_data );

	                if ( $payment ){
	                       edd_update_payment_status( $payment, 'publish' );
        	               ?>
                	       <p>Successfully added video access!</p>
	                <?php
			}
		}
		?>
		</div>
		<?php
        }elseif(isset($_GET['update_user_videos']) && wp_verify_nonce($_GET['_wpnonce'], 'update_hss_edd_videos')) {
		global $post;
?>
        <div class="wrap">
	<div class="icon32" id="icon-options-general"><br></div>
	<H2>Add Video Access</H2>
	<BR>
	<form id="hss-edd-user-video-update" action="" method="POST">
	<?php //<select name="video_ppv"> ?>
	<?php  $loop = new WP_Query( array( 'post_type' => 'download', 'posts_per_page' => -1 ) ); ?>
<?php while ( $loop->have_posts() ) : $loop->the_post();
	$post_id = $post->ID;
	if((get_post_meta($post_id, '_variable_pricing',true))==0 and (get_post_meta($post_id, '_edd_hide_purchase_link',true))==0)
        {
		$ppv_id = get_post_meta($post_id, '_edd_ppv_id', true);
		?>
		<input type="checkbox" name="video_ppv[]" value="<?php echo $ppv_id; ?>"><?php echo  the_title("","",FALSE) . " " . $amount; ?><BR>
		<?php
	}else{
                $prices = get_post_meta($post_id, 'edd_variable_prices', true);
                if(is_array($prices)) {
                	$count = 1;
                        foreach($prices as $key => $value) {
				$name = isset($prices[$key]['name']) ? $prices[$key]['name'] : '';
                                $amount = isset($prices[$key]['amount']) ? $prices[$key]['amount'] : '';
				$ppv_id = $key;
		                ?>
				<input type="checkbox" name="video_ppv[]" value="<?php echo $ppv_id; ?>"><?php echo  the_title("","",FALSE) . " " . $name . " "  . $amount; ?><BR>
                		<?php
			}
		}


	}
	endwhile; wp_reset_query(); ?>
	<?php //</select> ?>
			<p class="submit">
					<input type="hidden" name="hss_edd_user_id" value="<?php echo $_GET['user']; ?>"/>
					<input type="hidden" name="hss_edd_action" value="hss_edd_add_action"/>
					<input type="hidden" name="hss_edd_add_nonce" value="<?php echo wp_create_nonce('hss_edd_add_nonce'); ?>"/>
					<input type="submit" value="Add Video Access" class="button-primary"/>
				</p>
			</form>
	</div>
<?php
	echo ob_get_clean();
	}else{
?>

        <div class="wrap">

                <!-- Display Plugin Icon, Header, and Description -->
                <div class="icon32" id="icon-options-general"><br></div>
                <h2>HostStreamSell Plugin Settings</h2>
                <p>Please enter the settings below...</p>

                <!-- Beginning of the Plugin Options Form -->
                <form method="post" action="options.php">
                        <?php settings_fields('hss_edd_plugin_options'); ?>
                        <?php $options = get_option('hss_options'); ?>

                        <!-- Table Structure Containing Form Controls -->
                        <!-- Each Plugin Option Defined on a New Table Row -->
                        <table class="form-table">

                                <!-- Textbox Control -->
                                <tr>
                                        <th scope="row">HostStreamSell API Key<BR><i>(available from your account on www.hoststreamsell.com)</i></th>
                                        <td>
                                                <input type="text" size="40" name="hss_options[api_key]" value="<?php echo $options['api_key']; ?>" />
                                        </td>
                                </tr>
                                <tr>
                                        <th scope="row">Website Reference ID<BR><i>(leave at 0 unless you sell the same videos from multiple WordPress websites, in which case each website needs a unique reference ID)</i></th>
                                        <td>
                                                <input type="text" size="40" name="hss_options[database_id]" value="<?php echo $options['database_id']; ?>" />
                                        </td>
                                </tr>
                                <tr>
                                        <th scope="row">Video Player Size<BR><i>(leave blank to use defaults)</i></th>
                                        <td>
                                                Width <input type="text" size="10" name="hss_options[player_width_default]" value="<?php echo $options['player_width_default']; ?>" /> Height  <input type="text" size="10" name="hss_options[player_height_default]" value="<?php echo $options['player_height_default']; ?>" />
                                        </td>
                                </tr>
                                <tr>
                                        <th scope="row">Mobile Device Video Player Size<BR><i>(leave blank to use defaults)</i></th>
                                        <td>
                                                Width <input type="text" size="10" name="hss_options[player_width_mobile]" value="<?php echo $options['player_width_mobile']; ?>" /> Height  <input type="text" size="10" name="hss_options[player_height_mobile]" value="<?php echo $options['player_height_mobile']; ?>" />
                                        </td>
                                </tr>
                                <tr>
                                        <th scope="row">Make Player Width and Height Responsive</th>
                                        <td>
                                                <input type="checkbox" name="hss_options[responsive_player]" value="1"<?php checked( $options['responsive_player'], 1); ?> />
                                        </td>
                                </tr>
                                <tr>
                                        <th scope="row">Reponsive Player Max Width<BR><i>(default is 640 if left blank, only used when Reponsive Player checkbox is checked)</i></th>
                                        <td>
                                                Width <input type="text" size="10" name="hss_options[player_responsive_max_width]" value="<?php echo $options['player_responsive_max_width']; ?>" />
                                        </td>
                                </tr>
                                <tr>
                                        <th scope="row">JW Player License Key<BR><i>(available from www.longtailvideo.com)</i></th>
                                        <td>
                                                <input type="text" size="50" name="hss_options[jwplayer_license]" value="<?php echo $options['jwplayer_license']; ?>" />
                                        </td>
                                </tr>
                                <tr>
                                        <th scope="row">Subtitle Font Size<BR><i>(leave blank for default size)</i></th>
                                        <td>
                                                <input type="text" size="5" name="hss_options[subtitle_font_size]" value="<?php echo $options['subtitle_font_size']; ?>" />
                                        </td>
                                </tr>
                                <tr>
                                        <th scope="row">JW Player Stretching<BR><i>(http://www.longtailvideo.com/support/jw-player/28839/embedding-the-player)</i></th>
                                        <td>
                                                <select name="hss_options[jwplayer_stretching]">
						<?
                                                if (($options['jwplayer_stretching']=="uniform") or ($options['jwplayer_stretching']=="")){
                                                        ?><option value="uniform" SELECTED>uniform</option><?
                                                }else{
                                                        ?><option value="uniform">uniform</option><?
                                                }
						if ($options['jwplayer_stretching']=="none"){
							?><option value="none" SELECTED>none</option><?
						}else{
							?><option value="none">none</option><?
                                                }
						if ($options['jwplayer_stretching']=="exactfit"){
                                                        ?><option value="exactfit" SELECTED>exactfit</option><?
                                                }else{
                                                        ?><option value="exactfit">exactfit</option><?
                                                }
						if ($options['jwplayer_stretching']=="fill"){
                                                        ?><option value="fill" SELECTED>fill</option><?
                                                }else{
                                                        ?><option value="fill">fill</option>
						<?} ?>
						</select>

                                        </td>
                                </tr>
				<tr>
                                        <th scope="row">Logging Level - <i>Logs to <BR>wp-content/uploads/hss_edd/log.txt <?php echo $options['logging'];?></i></th>
                                        <td>
                                                <select name="hss_options[logging]">
						<?
                                                if (($options['logging']=="NORMAL") or ($options['logging']=="")){
                                                        ?><option value="NORMAL" SELECTED>Normal</option><?
                                                }else{
                                                        ?><option value="NORMAL">Normal</option><?
                                                }
                                                if ($options['logging']=="DEBUG"){
                                                        ?><option value="DEBUG" SELECTED>Debug</option><?
                                                }else{
                                                        ?><option value="DEBUG">Debug</option><?
                                                } ?>
						</select>
                                        </td>
                                </tr>
                                <tr>
                                        <th scope="row">Disable updating video descriptions</th>
                                        <td>
                                                <input type="checkbox" name="hss_options[disable_desc_updates]" value="1"<?php checked( $options['disable_desc_updates'], 1); ?> />
                                        </td>
                                </tr>		
                                <tr>
                                        <th scope="row">Watching Trailer Text (leave blank for no message)</th>
                                        <td>
                                                <input type="text" size="50" name="hss_options[watching_trailer_text]" value="<?php echo $options['watching_trailer_text']; ?>" />
                                        </td>
                                </tr>
                                <tr>
                                        <th scope="row">Watching Full Video Text (leave blank for no message)</th>
                                        <td>
                                                <input type="text" size="50" name="hss_options[watching_video_text]" value="<?php echo $options['watching_video_text']; ?>" />
                                        </td>
                                </tr>		
				<tr>
				        <th scope="row">Add/Update Videos</th>
				        <td>
						<div><input type="button" value="Update" id="hss_edd_ajax" /></div>
                                        <div id="updateprogress"></div></td>
                                </tr>
                        </table>
                        <p class="submit">
                        <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
                        </p>
                </form>
        </div>
<?
	}
}

function hss_menu () {
        add_options_page('HostStreamSell Admin','HSS Admin','manage_options','hss_admin', 'hss_edd_options_page');
}

add_action('admin_menu','hss_menu');


function add_hss_video_hidden_fields($post_id) {
        global $edd_options;
                        if(get_post_meta($post_id, 'is_streaming_video', true)){
				echo '<p><b><i><font color=red>WARNING: To update pricing log into your HostStreamSell account and make your changes, then perform the update in HSS Settings, as any changes will be lost on next sync</font></i></b></p>';
				echo '<p><b><i>Note that you can however add files for download with purchase of this video</i></b></p><br>';
                                echo '<input type="hidden" name="is_streaming_video_bundle" value="0"/>';
                                echo '<input type="hidden" name="is_streaming_video" value="1"/>';
                                echo '<input type="hidden" name="_edd_video_id" value="'.(get_post_meta($post_id, '_edd_video_id', true)).'"/>';
                        }
                        if(get_post_meta($post_id, 'is_streaming_video_bundle', true)){
				echo '<p><b><i><font color=red>WARNING: To update pricing log into your HostStreamSell account and make your changes, then perform the update in HSS Settings, as any changes will be lost on next sync</font></i></b></p>';
				echo '<p><b><i>Note that you can however add files for download with purchase of this video group</i></b></p><br>';
                                echo '<input type="hidden" name="is_streaming_video_bundle" value="1"/>';
                                echo '<input type="hidden" name="is_streaming_video" value="0"/>';
                                echo '<input type="hidden" name="_edd_group_id" value="'.(get_post_meta($post_id, '_edd_group_id', true)).'"/>';
                        }
}


function hss_edd_is_stream($post_id) {
        global $edd_options;
?>
        <p>
                <strong><?php _e( 'Pricing Options:', 'edd' ); ?></strong>
        </p>
<?
			if(get_post_meta($post_id, 'is_streaming_video', true)){
                                echo '<input type="hidden" name="is_streaming_video_bundle" value="0"/>';
				echo '<input type="hidden" name="is_streaming_video" value="1"/>';
				echo '<input type="hidden" name="_edd_video_id" value="'.(get_post_meta($post_id, '_edd_video_id', true)).'"/>';
			}
                        if(get_post_meta($post_id, 'is_streaming_video_bundle', true)){
                                echo '<input type="hidden" name="is_streaming_video_bundle" value="1"/>';
                                echo '<input type="hidden" name="is_streaming_video" value="0"/>';
				echo '<input type="hidden" name="_edd_group_id" value="'.(get_post_meta($post_id, '_edd_group_id', true)).'"/>';
                        }

			echo '<p><b><i>Note: To update pricing log into your HostStreamSell account and make your changes, then perform the update in HSS Settings</i></b></p>';
				$field_html = '';
				if((get_post_meta($post_id, '_variable_pricing',true))==0)
				{
					//echo '<p>'.get_post_meta($post_id, '_price_details',true).' <input name="edd_price" id="edd_price" value="'.get_post_meta($post_id, 'edd_price',true).'" size="30" style="width:80px;" placeholder="9.99" type="text" readonly="readonly"></p>';
				$price = get_post_meta($post_id, 'edd_price',true);
?>
        <div id="edd_regular_price_field" class="edd_pricing_fields" <?php echo $price_display; ?>>
                <?php if(!isset( $edd_options['currency_position'] ) || $edd_options['currency_position'] == 'before') : ?>
                        <?php echo get_post_meta($post_id, '_price_details',true).' '.edd_currency_filter(''); ?><input type="text" name="edd_price" id="edd_price" value="<?php echo isset( $price ) ? esc_attr( edd_format_amount( $price ) ) : ''; ?>" size="30" style="width:80px;" placeholder="9.99" readonly="readonly" />
                <?php else : ?>
                        <?php echo get_post_meta($post_id, '_price_details',true); ?> <input type="text" name="edd_price" id="edd_price" value="<?php echo isset( $price ) ? esc_attr( edd_format_amount( $price ) ) : ''; ?>" size="30" style="width:80px;" placeholder="9.99" readonly="readonly" /><?php echo edd_currency_filter(''); ?>
                <?php endif; ?>
        </div>
<?
					
					echo '<input type="hidden" name="_variable_pricing" value="0"/>';
				}else{
					$prices = get_post_meta($post_id, 'edd_variable_prices', true);
					echo '<input type="hidden" name="_variable_pricing" value="1"/>';
        	                        if(is_array($prices)) {
                	                        $count = 1;
	                                        foreach($prices as $key => $value) {
        	                                        $field_html .= '<div class="edd_variable_prices_wrapper">';
                	                                        $name = isset($prices[$key]['name']) ? $prices[$key]['name'] : '';
                        	                                $amount = isset($prices[$key]['amount']) ? $prices[$key]['amount'] : '';
                                	                        $field_html .= '<input type="text" class="edd_variable_prices_name" placeholder="' . __('price option name', 'edd') . '" name="edd_variable_prices[' . $key . '][name]" id="edd_variable_prices[' . $key . '][name]" value="' . esc_attr( $name ) . '" size="30" style="width:80%" readonly="readonly" />';
                                        	                $field_html .= '<input type="text" class="edd_variable_prices_amount text" placeholder="' . __('9.99', 'edd') . '" name="edd_variable_prices[' . $key . '][amount]" id="edd_variable_prices[' . $key . '][amount]" value="' . $amount . '" size="30" style="width:50px;" readonly="readonly" />';
                                                	$field_html .= '</div>';
	                                                $count++;
        	                                }
                	                } 
					echo $field_html;
				}
	//echo '</td></tr>';
}
#remove_action('edd_meta_box_fields', 'edd_render_price_field', 10);
#add_action('edd_meta_box_fields', 'hss_edd_is_stream', 20);

add_action('edd_meta_box_fields', 'add_hss_video_hidden_fields', 0);

function edd_download_meta_box_save_stream($post_id) {
        global $post;


        // check autosave
        if ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || ( defined('DOING_AJAX') && DOING_AJAX) || isset($_REQUEST['bulk_edit']) ) return $post_id;

        //don't save if only a revision
        if ( isset($post->post_type) && $post->post_type == 'revision' ) return $post_id;

        // check permissions
        if (isset($_POST['post_type']) && 'page' == $_POST['post_type']) {
                if (!current_user_can('edit_page', $post_id)) {
                        return $post_id;
                }
        } elseif (!current_user_can('edit_post', $post_id)) {
                return $post_id;
        }

        // these are the default fields that get saved
        $fields = array(
			'_edd_video_id',
			'_edd_group_id',
	                'is_streaming_video',
	                'is_streaming_video_bundle'
                
        );
        foreach($fields as $field) {
                if(isset($_POST[$field])) {
                        $old = get_post_meta($post_id, $field, true);
                        if($old != $_POST[$field]) {
                                if( is_string( $_POST[$field] ) ) {
                                        $new = esc_attr( $_POST[$field] );
                                } else {
                                        $new = $_POST[$field];
                                }
                                update_post_meta($post_id, $field, $new);
                        }
                } else {
                        delete_post_meta($post_id, $field);
                }
        }
}
add_action('save_post', 'edd_download_meta_box_save_stream');




function hss_edd_before_download_content($download_id) {
        global $post;
	global $is_iphone;
        global $user_ID;
	$video = "";
        if($post->post_type == 'download' && is_singular() && is_main_query()) {

			if(get_post_meta($post->ID, 'is_streaming_video', true)) {
				$options = get_option('hss_options');
				$userId = $user_ID;
        
				//if(edd_has_user_purchased($user_ID, $post->ID))
				//	$video = "<center>You have access to this video</center>";
		
				$hss_video_id = get_post_meta($post->ID, '_edd_video_id', true);
				if($userId!=0){
					$hss_errors = get_user_meta( $userId, "hss_errors", true );
					if (!empty($hss_errors)){
						_log("there are hss_errors");
						_log($hss_errors);
						foreach ($hss_errors as $key => $ppv_option) {
		        	                        $params = array(
			                                   'method' => 'secure_videos.add_user_ppv',
			                                   'api_key' => $options['api_key'],
			                                   'ppv_id' => $ppv_option,
			                                   'private_user_id' => $userId,
							   'database_id' => $options['database_id']
			                                );
			                                _log($params);
			                                $response = wp_remote_post( "https://www.hoststreamsell.com/services/api/rest/xml/", array(
			                                        'method' => 'POST',
			                                        'timeout' => 15,
			                                        'redirection' => 5,
			                                        'httpversion' => '1.0',
			                                        'blocking' => true,
			                                        'headers' => array(),
			                                        'body' => $params,
			                                        'cookies' => array()
			                                    )
			                                );

	                		                // need to add method to record failed rest requests for retry

			                                if( is_wp_error( $response ) ) {
			                                        _log("error msg: ".$response->get_error_message()."\n");
			                                }else if( $response['response']['code'] != "200" ) {
			                                        _log("request code bad: ".$response['response']['code']."\n");
			                                }else{
			                                        _log("request code good: ".$key."=>".$ppv_option." ".$response['response']['code']."\n");
			                                        unset($hss_errors[$key]);
			                                        update_user_meta( $userId, "hss_errors", $hss_errors);
								$hss_errors_new = get_user_meta( $userId, "hss_errors", true );
								_log($hss_errors_new);
			                                }
						}
					}
				}
                                $response = wp_remote_post( "https://www.hoststreamsell.com/api/1/xml/videos?api_key=".$options['api_key']."&video_id=$hss_video_id&private_user_id=$userId&database_id=".$options['database_id']."&expands=playback_details&force_allows=no", array(
		                        'method' => 'GET',
		                        'timeout' => 15,
		                        'redirection' => 5,
		                        'httpversion' => '1.0',
		                        'blocking' => true,
		                        'headers' => array(),
		                        'body' => $params,
		                        'cookies' => array()
		                    )
		                );
		                $res = "";
		                if( is_wp_error( $response ) ) {
		                   $return_string .= 'Error occured retieving video information, please try refresh the page';
		                } else {
		                   $res = $response['body'];
		                }
		
		                $xml = new SimpleXMLElement($res);
				if($options['logging']=="DEBUG")
					_log($xml);
		                $title = $xml->result->title;
		                $hss_video_title = $title;
		                $user_has_access = $xml->result->user_has_access;
				$user_can_download = $xml->result->user_can_download;
				//$video = "".$user_has_access;
                                if($user_has_access=="true")
                                        $video .= '<div class="hss_watching_video_text">'.$options['watching_video_text'].'</div>';
                                else
                                        $video .= '<div class="hss_watching_trailer_text">'.$options['watching_trailer_text'].'</div>';
		                $description = $xml->result->description;
		                $feature_duration = $xml->result->feature_duration;
		                $trailer_duration = $xml->result->trailer_duration;
                		$video_width = $xml->result->width;
		                $video_height = $xml->result->height;
				$aspect_ratio = $xml->result->aspect_ratio;
		                if($video_width>640){
					$video_width = "640";
					$video_height = "390";
		                }
		                $referrer = site_url();
				$hss_video_user_token = $xml->result->user_token;
		
		                $hss_video_mediaserver_ip = $xml->result->wowza_ip;
		
		                $hss_video_smil_token = "?privatetoken=".$hss_video_user_token;
		                $hss_video_mediaserver_ip = $xml->result->wowza_ip;
		
		                $hss_video_smil = $xml->result->smil;
		                $hss_video_big_thumb_url = $xml->result->big_thumb_url;
		                $hss_rtsp_url = $xml->result->rtsp_url;
		                $referrer = site_url();
		
				$content_width = $video_width;
				$content_height = $video_height;

		                if($is_iphone){
		                	if($content_width<320){
			                        $content_width=320;
					}
		                }

		                if($video_width>$content_width){
		                        $mod = $content_width%40;
		                        $video_width = $content_width-$mod;
		                        $multiple = $video_width/40;
		                        $video_height = $multiple*30;
		                }
				
				if($is_iphone){
	                                if($options['player_width_mobile']!="")
	                                        $video_width=$options['player_width_mobile'];
	                                if($options['player_height_mobile']!="")
	                                        $video_height=$options['player_height_mobile'];
				}else{
	                                if($options['player_width_default']!="")
	                                        $video_width=$options['player_width_default'];
        	                        if($options['player_height_default']!="")
                	                        $video_height=$options['player_height_default'];
				}
                                $httpString = "http";
                                if (is_ssl()) {
                                        $httpString = "https";
                                }

                                $subtitle_count = $xml->result->subtitle_count;
                                $subtitle_index = 1;
                                $subtitle_text = "";
                                $captions = "";
                                if($subtitle_count>0){
                                        $subtitle_text = ",
                                                tracks: [{";
                                        while($subtitle_index <= $subtitle_count)
                                        {
                                                $subtitle_label = (string)$xml->result[0]->subtitles->{'subtitle_label_'.$subtitle_index}[0];
                                                $subtitle_file = (string)$xml->result[0]->subtitles->{'subtitle_file_'.$subtitle_index}[0];
                                                $subtitle_text .= "
                                                    file: \"https://www.hoststreamsell.com/mod/secure_videos/subtitles/$subtitle_file?rand=".randomString()."\",
                                                    label: \"$subtitle_label\",
                                                    kind: \"captions\",
                                                    \"default\": true";
                                                $subtitle_index += 1;
                                                if($subtitle_index <= $subtitle_count){
                                                        $subtitle_text .= "
                                                },{";
						}
                                        }
                                        $subtitle_text .= "
                                                }]";

					$fontSize = "";
					if($options["subtitle_font_size"]!=""){
						$fontSize = "
							fontSize: ".$options["subtitle_font_size"].",";
					}
                                        $captions = "
                                                captions: {
                                                        color: '#FFFFFF',".$fontSize."
                                                        backgroundOpacity: 0
                                                },";
                                }

		                $video = $video."
		                <script type=\"text/javascript\" src=\"https://www.hoststreamsell.com/mod/secure_videos/jwplayer-6/jwplayer.js\"></script>
				<script type=\"text/javascript\">jwplayer.key=\"".$options['jwplayer_license']."\";</script>";
				if($options["responsive_player"]==1){
					$responsive_width="640";
					if($options["player_responsive_max_width"]!="")
						$responsive_width=$options["player_responsive_max_width"];
					$video.="<div class='hss_video_player' style='max-width:".$responsive_width."px;'>";
				}else{
                                        $video.="<div class='hss_video_player'>";
				}
                		$video.="<div id='videoframe'>An error occurred setting up the video player</div>
				<SCRIPT type=\"text/javascript\">

		                var viewTrailer = false;
                		var videoFiles = new Array();
		                var trailerFiles = new Array();

                		var agent=navigator.userAgent.toLowerCase();
		                var is_iphone = (agent.indexOf('iphone')!=-1);
		                var is_ipad = (agent.indexOf('ipad')!=-1);
		                var is_playstation = (agent.indexOf('playstation')!=-1);
		                var is_safari = (agent.indexOf('safari')!=-1);
		                var is_iemobile = (agent.indexOf('iemobile')!=-1);
		                var is_blackberry = (agent.indexOf('BlackBerry')!=-1);
		                var is_android = (agent.indexOf('android')!=-1);
		                var is_webos = (agent.indexOf('webos')!=-1);
	
				if (is_iphone) { html5Player();}
                                else if (is_ipad) { html5Player(); }
				else if (is_android) { rtspPlayer(); }
				else if (is_webos) { rtspPlayer(); }
				else if (is_blackberry) { rtspPlayer(); }
				else if (is_playstation) { newJWPlayer(); }
				else { newJWPlayer(); }
		
		                function newJWPlayer()
		                {
					jwplayer('videoframe').setup({
					    stretching:'".$options["jwplayer_stretching"]."',
					    playlist: [{
					        image: '$hss_video_big_thumb_url',
				        	sources: [{
					            file: '$httpString://www.hoststreamsell.com/mod/secure_videos/private_media_playlist_v2.php?params=".$hss_video_id."!".urlencode($referrer)."!".$hss_video_user_token."!',
					            type: 'rtmp'
					        },{
				        	    file: 'http://".$hss_video_mediaserver_ip.":1935/hss/smil:".$hss_video_smil."/playlist.m3u8".$hss_video_smil_token."&referer=".urlencode($referrer)."'
						 }]$subtitle_text
                                            }],$captions
					    primary: 'flash',	";
				if($options["responsive_player"]==1){	
				        $video.="                  width: '100%',
                                            aspectratio: '".$aspect_ratio."'";
				}else{
        				$video.="                 height: $video_height,
                       			  width: $video_width";
				}

	$video.="			});
		                }

				function rtspPlayer()
				{
			                var player=document.getElementById(\"videoframe\");
					player.innerHTML='<A HREF=\"rtsp://".$hss_video_mediaserver_ip."/hss/mp4:".$hss_rtsp_url."".$hss_video_smil_token."&referer=".urlencode($referrer)."\">'+
					'<IMG SRC=\"".$hss_video_big_thumb_url."\" '+
					'ALT=\"Start Mobile Video\" '+
					'BORDER=\"0\" '+
					'HEIGHT=\"$video_height\"'+
					'WIDTH=\"$video_width\">'+
					'</A>';
				}

                                function html5Player()
                                {
                                        var player=document.getElementById(\"videoframe\");
                                        player.innerHTML='<video controls '+
                                        'src=\"http://".$hss_video_mediaserver_ip.":1935/hss/smil:".$hss_video_smil."/playlist.m3u8".$hss_video_smil_token."&referer=".urlencode($referrer)."\" '+
                                        'HEIGHT=\"".$video_height."\" '+
                                        'WIDTH=\"".$video_width."\" '+
                                        'poster=\"".$hss_video_big_thumb_url."\" '+
                                        'title=\"".$hss_video_title."\">'+
                                        '</video>';
                                }

			        </script>
				</div>
			        ";
			        $video .= "<BR>";

			}
        }
	echo $video;
}
add_action( 'edd_before_download_content', 'hss_edd_before_download_content' );

function hss_edd_after_download_content($download_id) {
        global $post;

        ob_start();
        do_action( 'hss_edd_show_video_purchase_details', $post->ID );
        $content .= ob_get_clean();

        echo $content;
}
add_action( 'edd_after_download_content', 'hss_edd_after_download_content', 5 );

function hss_edd_complete_purchase_add_video($payment_id, $new_status, $old_status) {
	_log("hss_edd_complete_purchase_add_video - ".$payment_id." - ".$new_status." - ".$old_status);
        if( $old_status == 'publish' || $old_status == 'complete')
                return; // make sure that payments are only completed once

                $payment_data   = get_post_meta($payment_id, '_edd_payment_meta', true);
                $downloads              = maybe_unserialize($payment_data['downloads']);
                $user_info              = maybe_unserialize($payment_data['user_info']);
                $cart_details   = maybe_unserialize($payment_data['cart_details']);
                        
		_log($payment_data);
		_log($downloads);
		_log($user_info);
		_log($cart_details);

                // increase purchase count and earnings
                foreach($downloads as $download) {

			if((get_post_meta($download['id'], 'is_streaming_video', true)) or (get_post_meta($download['id'], 'is_streaming_video_bundle', true))) {
                                $options = get_option('hss_options');
                                $userId = $user_info['id'];

				$ppv_option = null;
				if(empty($download['options']))
					$ppv_option = get_post_meta($download['id'], '_edd_ppv_id', true);
				else
					$ppv_option = $download['options']['price_id'];
				_log("ppv option = ".$ppv_option);

			        $params = array(
			           'method' => 'secure_videos.add_user_ppv',
			           'api_key' => $options['api_key'],
			           'ppv_id' => $ppv_option,
			           'private_user_id' => $userId,
				   'database_id' => $options['database_id']
			        );
               			_log($params); 
				$response = wp_remote_post( "https://www.hoststreamsell.com/services/api/rest/xml/", array(
		                        'method' => 'POST',
		                        'timeout' => 15,
	                	        'redirection' => 5,
		                        'httpversion' => '1.0',
		                        'blocking' => true,
		                        'headers' => array(),
		                        'body' => $params,
		                        'cookies' => array()
        		            )
        		        );

		                if( is_wp_error( $response ) ) {
                		        _log("error msg: ".$response->get_error_message()."\n");
					$hss_errors = get_user_meta( $userId, "hss_errors", true );
					$hss_errors[] = $ppv_option;
					update_user_meta( $userId, "hss_errors", $hss_errors);
		                }else if( $response['response']['code'] != "200" ) {
                		        _log("request code bad: ".$response['response']['code']."\n");
                                        $hss_errors = get_user_meta( $userId, "hss_errors", true );
                                        $hss_errors[] = $ppv_option;
                                        update_user_meta( $userId, "hss_errors", $hss_errors);
		                }else{
                		        _log("request code good: ".$response['response']['code']."\n");
                		}
                   		$res = $response['body'];

		                $xml = new SimpleXMLElement($res);
                		_log($xml);
			}
                }

        // empty the shopping cart
        edd_empty_cart();
}
add_action('edd_update_payment_status', 'hss_edd_complete_purchase_add_video', 10, 3);


function hss_edd_update_videos()
{
	#global $post;
	$options = get_option('hss_options');

        $params = array(
          'method' => 'secure_videos.get_user_video_groups',
          'api_key' => $options['api_key']
        );

        $group_response = wp_remote_post( "https://www.hoststreamsell.com/services/api/rest/xml/", array(
                'method' => 'POST',
                'timeout' => 15,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array(),
                'body' => $params,
                'cookies' => array()
            )
        );
        $group_res = "";
        if( is_wp_error( $group_response ) ) {
   	   _log("ERROR: ".$group_response->get_error_message());
	   return $group_response->get_error_message();
        } else {
           $group_res = $group_response['body'];
        }


        $group_xml = new SimpleXMLElement($group_res);
	_log($group_xml);

        $status = $group_xml->status;
        _log("STATUS: ".$status);
        if($status == "0")
        {
		$seen_videos = array();

        	$group_count = $group_xml->result->video_group_count;
                $group_index = 1;
                while($group_index <= $group_count)
                {
                	$group_video_count = (int)$group_xml->result[0]->{'video_group'.$group_index}[0]->video_count;
			_log("video count = ".$group_video_count);
                        if($group_video_count > 0){
				$group_video_post_ids = array();
				$group_video_post_index=0;
                        	$group_id = (string)$group_xml->result[0]->{'video_group'.$group_index}[0]->video_group_id;
                                $group_title = (string)$group_xml->result[0]->{'video_group'.$group_index}[0]->title[0];
                                $group_description = (string)$group_xml->result[0]->{'video_group'.$group_index}[0]->description[0];
				$group_thumbnail = (string)$group_xml->result[0]->{'video_group'.$group_index}[0]->thumbnail[0];
				_log("Group id=".$group_id);
				if( !term_exists( $group_title,'download_category' )){
				 	_log("Creating category ".$group_title);   	
					wp_insert_term(
					  $group_title, // the term 
					  'download_category' // the taxonomy
					);
				}
				$category_term = get_term_by('name', $group_title, 'download_category');
				_log($category_term->term_id);
				$params = array(
			          'method' => 'secure_videos.get_user_video_list_by_group_with_purchase_options',
			          'api_key' => $options['api_key'],
			          'group_id' => $group_id,
			        );
				_log("group_id=".$params['group_id']);
			        $response = wp_remote_post( "https://www.hoststreamsell.com/services/api/rest/xml/", array(
                        		   'method' => 'POST',
                     			   'timeout' => 15,
                     			   'redirection' => 5,
                     			   'httpversion' => '1.0',
                     			   'blocking' => true,
                     			   'headers' => array(),
                     			   'body' => $params,
                     			   'cookies' => array()
                 			)
                		);
			        $res = "";
			        if( is_wp_error( $response ) ) {
				   _log("ERROR");
			        } else {
			        	$res = $response['body'];
                		}

			        $xml = new SimpleXMLElement($res);
				_log($xml);
				_log("STATUS: ".$status);
			        if($status == "0")
                		{
                       			$count = (int)$xml->result->video_count;
					_log("Video count=".$count);
		                        $index = 1;
		                        while($index <= $count)
		                        {
						_log("checking video");
		                                $video_id = (string)$xml->result[0]->{'video'.$index}[0]->video_id;
                       			        $title = (string)$xml->result[0]->{'video'.$index}[0]->title[0];
		                                $description = (string)$xml->result[0]->{'video'.$index}[0]->description[0];
                       			        $thumbnail = (string)$xml->result[0]->{'video'.$index}[0]->thumbnail[0];
						$args=array(
						  'meta_key'=>'_edd_video_id',
						  'meta_value'=> $video_id,
						  'post_type' => 'download',
						);
						_log($args);
						$my_query = null;
						$my_query = new WP_Query($args);
						$post_ID = -1;
						if( $my_query->have_posts() ) {
							_log("Video already a post");
							$video_post = $my_query->next_post();
							_log("video_post ID=".$video_post->ID);
							if($options['disable_desc_updates']==1){
								$my_post = array(
								     'ID' => $video_post->ID,
								     'post_title' => $title,
								);
							}else{
                                                                $my_post = array(
                                                                     'ID' => $video_post->ID,
                                                                     'post_title' => $title,
                                                                     'post_content' => $description,
                                                                 );
							}
							// Update the post into the database
							#remove_action('save_post', 'wpse51363_save_post');
							$post_ID = wp_update_post( $my_post );
							_log("RESULT FROM UPDATE: ".$post_ID);
							#add_action('save_post', 'wpse51363_save_post');
						}else{
							// Create post object
							_log("Create video post");
							$my_post = array(
				  			     'post_title' => $title,
							     'post_content' => $description,
							     'post_status' => 'publish',
							     'post_author' => 1,
							     'post_type' => 'download',
							);
							
							// Insert the post into the database
							$post_ID = wp_insert_post( $my_post );

							$url = $thumbnail; 
							$tmp = download_url( $url );
    							$file_array = array(
    							    'name' => basename( $url ),
    							    'tmp_name' => $tmp
    							);

    							// Check for download errors
    							if ( is_wp_error( $tmp ) ) {
								_log($tmp);
        							@unlink( $file_array[ 'tmp_name' ] );
        							#return $tmp;
    							}

    							$thumb_id = media_handle_sideload( $file_array, 0 );
    							// Check for handle sideload errors.
    							if ( is_wp_error( $thumb_id ) ) {
								_log($thumb_id);
        							@unlink( $file_array['tmp_name'] );
							        #return $thumb_id;
    							}

    							$attachment_url = wp_get_attachment_url( $thumb_id );
							#_log("Attachment URL (".$thumb_id."): ".$attachment_url);
    							// Do whatever you have to here
							set_post_thumbnail( $post_ID, $thumb_id );

						}
						$category_found = false;
						$terms = array();
						if(!in_array($video_id,$seen_videos))
						        array_push($seen_videos,$video_id);
						else
							$terms = wp_get_object_terms($post_ID,'download_category');
						$vid_cats = array();
						if(!empty($terms)){
						  	if(!is_wp_error( $terms )){
								foreach($terms as $term){
									array_push($vid_cats,$term->name);
								}
							}
						}
						_log($vid_cats);
						if(!in_array($group_title,$vid_cats)){
							_log("adding term");
							array_push($vid_cats,$group_title);
							_log($vid_cats);
							wp_set_object_terms($post_ID,$vid_cats,'download_category');
						}
						$term = get_term_by( 'name',$group_title,'download_category');
						wp_update_term($term->term_id, 'download_category', array('description' => $group_description));
						update_post_meta($post_ID, '_edd_video_id', $video_id);

						$group_video_post_ids[$group_video_post_index] = $post_ID;
						$group_video_post_index+=1;
					        $purchase_option_count = (int)$xml->result[0]->{'video'.$index}[0]->option_count;
						$prices = array();
						$option_index = 1;
						$option_price = "";
						$lowest_price = 0;
						$option_name = "";
						_log("purchase_option_count=".$purchase_option_count);
						if($purchase_option_count > 0)
						{
							$purchase_option_details = array();
						        while($option_index <= $purchase_option_count)
						        {
						        	$option_id = (int)$xml->result[0]->{'video'.$index}[0]->{'option'.$option_index}[0]->option_id;
						                $option_type = (string)$xml->result[0]->{'video'.$index}[0]->{'option'.$option_index}[0]->type;
						                $option_price = (string)$xml->result[0]->{'video'.$index}[0]->{'option'.$option_index}[0]->price;
								if( ( ( (float)$option_price) < $lowest_price) or ($lowest_price==0))
									$lowest_price = (float)$option_price;

					                        $bandwidth_cap = (string)$xml->result[0]->{'video'.$index}[0]->{'option'.$option_index}[0]->bandwidth_cap;
					                        $time_limit = (string)$xml->result[0]->{'video'.$index}[0]->{'option'.$option_index}[0]->time_limit;
					                        $rate_limit = (string)$xml->result[0]->{'video'.$index}[0]->{'option'.$option_index}[0]->rate_limit;
						                $download_limit = (string)$xml->result[0]->{'video'.$index}[0]->{'option'.$option_index}[0]->download_limit;
								$option_name = $time_limit.' streaming access';
								if($bandwidth_cap!="Unlimited")
									$option_name = $option_name.' '.$bandwidth_cap.' Data Cap';
								if($rate_limit!="No limit")
                                                                        $option_name = $option_name.' rate limited to '.$rate_limit.' kbps';
								if($download_limit=="No Downloads")
                                                                        $option_name = $option_name.' (no download access)';
								elseif($download_limit=="Any Bitrate Available")
                                                                        $option_name = $option_name.' (includes download access)';
								else
									$option_name = $option_name.' (download accesss '.$download_limit.')';

								$prices[$option_id] = array('name' => $option_name,'amount' => $option_price);
								_log("option id=".$option_id);
								_log($prices[$option_id]["name"]." - ".$prices[$option_id]["amount"]);
						                $option_index+=1;
						        }
						}


						_log("PostID=".$post_ID." - option_index=".$option_index);
						if($option_index==1){
							//add no selling options
							_log("add no selling options");
							update_post_meta($post_ID, '_variable_pricing','0');
							delete_post_meta($post_ID, 'edd_price');
							delete_post_meta($post_ID, '_price_details');
							delete_post_meta($post_ID, '_edd_ppv_id');
							update_post_meta($post_ID, '_edd_hide_purchase_link',1);
						}elseif($option_index==2){
							_log("add one selling options");
							delete_post_meta($post_ID, '_edd_hide_purchase_link');
							update_post_meta($post_ID, '_variable_pricing','0');
							update_post_meta($post_ID, 'edd_price',$option_price);
							update_post_meta($post_ID, '_price_details',$option_name);
							update_post_meta($post_ID, '_edd_ppv_id',$option_id);
						}else{
							_log("add multiple selling options");
							delete_post_meta($post_ID, '_edd_hide_purchase_link');
							update_post_meta($post_ID, '_variable_pricing','1');
							update_post_meta($post_ID, 'edd_variable_prices',$prices);
						}
						update_post_meta($post_ID, 'is_streaming_video',true);
						$meta_label = 'group_order_'.$category_term->term_id;
						update_post_meta($post_ID, $meta_label,$group_video_post_index);
						$index+=1;
					}
                                }

				$prices = array();
                                $option_index = 1;
                                $purchase_option_count = (int)$xml->result->group_option_count;
                                $option_name = "";
                                if($purchase_option_count > 0)
                                {
	                                $args=array(
        	                                'meta_key'=>'_edd_group_id',
                	                        'meta_value'=> $group_id,
                        	                'post_type' => 'download',
	                                );
        	                        _log($args);
                	                $my_query = null;
	                                $my_query = new WP_Query($args);
        	                        $post_ID = -1;
                	                if( $my_query->have_posts() ) {
                        	                _log("Video group already a post");
	                                        $video_group_post = $my_query->next_post();
        	                                _log("video_group_post ID=".$video_group_post->ID);
                	                        if($options['disable_desc_updates']==1){
                        	                        $my_post = array(
	                                                   'ID' => $video_group_post->ID,
        	                                           'post_title' => $group_title,
                	                                );
	                                        }else{
        	                                        $my_post = array(
                	                                   'ID' => $video_group_post->ID,
                        	                           'post_title' => $group_title,
                                	                   'post_content' => $group_description,
	                                                );
        	                                }
                	                        // Update the post into the database
                        	                $post_ID = wp_update_post( $my_post );
                                	        _log("RESULT FROM UPDATE: ".$post_ID);
	                                }else{
        	                                // Create post object
                	                        _log("Create video group post");
                        	                $my_post = array(
                                	           'post_title' => $group_title,
	                                           'post_content' => $group_description,
        	                                   'post_status' => 'publish',
                	                           'post_author' => 1,
                        	                   'post_type' => 'download',
	                                        );

        	                                // Insert the post into the database
                	                        $post_ID = wp_insert_post( $my_post );
                        	                $url = $group_thumbnail;
	                                        $tmp = download_url( $url );
        	                                $file_array = array(
	                                           'name' => basename( $url ),
        	                                   'tmp_name' => $tmp
	                                        );
        	                                // Check for download errors
                	                        if ( is_wp_error( $tmp ) ) {
	                                                _log($tmp);
	                                                @unlink( $file_array[ 'tmp_name' ] );
	                                                return $tmp;
	                                        }
	
	                                        $thumb_id = media_handle_sideload( $file_array, 0 );
	                                        // Check for handle sideload errors.
	                                        if ( is_wp_error( $thumb_id ) ) {
	                                                _log($thumb_id);
	                                                @unlink( $file_array['tmp_name'] );
	                                                return $thumb_id;
	                                        }
	
	                                        $attachment_url = wp_get_attachment_url( $thumb_id );
	                                        _log("Attachment URL (".$thumb_id."): ".$attachment_url);
	                                        // Do whatever you have to here
	                                        set_post_thumbnail( $post_ID, $thumb_id );
	
	                                }

                                	update_post_meta($post_ID, '_edd_group_id', $group_id);

	                                $purchase_option_details = array();
                                        while($option_index <= $purchase_option_count)
        	                        {
                                                $option_id = (int)$xml->result[0]->{'group_option'.$option_index}[0]->option_id;
                                                $option_type = (string)$xml->result[0]->{'group_option'.$option_index}[0]->type;
                                                $option_price = (string)$xml->result[0]->{'group_option'.$option_index}[0]->price;
                                                if( ( ( (float)$option_price) < $lowest_price) or ($lowest_price==0))
                 	                               $lowest_price = (float)$option_price;
                                                $bandwidth_cap = (string)$xml->result[0]->{'group_option'.$option_index}[0]->bandwidth_cap;
                                                $time_limit = (string)$xml->result[0]->{'group_option'.$option_index}[0]->time_limit;
                                                $rate_limit = (string)$xml->result[0]->{'group_option'.$option_index}[0]->rate_limit;
                                                $download_limit = (string)$group_xml->result[0]->{'group_option'.$option_index}[0]->download_limit;
                                                $option_name = $time_limit.' streaming access';
                                                if($bandwidth_cap!="Unlimited")
                                                	$option_name = $option_name.' '.$bandwidth_cap.' Data Cap';
                                                if($rate_limit!="No limit")
                                                         $option_name = $option_name.' rate limited to '.$rate_limit.' kbps';
                                                if($download_limit=="No Downloads")
                                                         $option_name = $option_name.' (no download access)';
                                                elseif($download_limit=="Any Bitrate Available")
                                                         $option_name = $option_name.' (includes download access)';
                                                else
                                                         $option_name = $option_name.' (download accesss '.$download_limit.')';
                                                $prices[$option_id] = array('name' => $option_name,'amount' => $option_price);
                                                _log("group option id=".$option_id);
                                                _log($prices[$option_id]["name"]);
                                                $option_index+=1;
                                        }
                                
                                	_log("PostID=".$post_ID);
					if($option_index==1){
        	                                update_post_meta($post_ID, '_variable_pricing','0');
                	                        delete_post_meta($post_ID, 'edd_price');
	                                        delete_post_meta($post_ID, '_price_details');
	                                        delete_post_meta($post_ID, '_edd_ppv_id');
	                                        update_post_meta($post_ID, '_edd_hide_purchase_link',1);	
					}
	                                if($option_index==2){
						delete_post_meta($post_ID, '_edd_hide_purchase_link');
		                                update_post_meta($post_ID, '_variable_pricing','0');
	                                        update_post_meta($post_ID, 'edd_price',$option_price);
	                                        update_post_meta($post_ID, '_price_details',$option_name);
	                                        update_post_meta($post_ID, '_edd_ppv_id',$option_id);
	                                }else{
						delete_post_meta($post_ID, '_edd_hide_purchase_link');
	                                        update_post_meta($post_ID, '_variable_pricing','1');
	                                        update_post_meta($post_ID, 'edd_variable_prices',$prices);
	                                }
	                                update_post_meta($post_ID, 'is_streaming_video_bundle',true);
					update_post_meta($post_ID, '_edd_product_type','bundle');
					update_post_meta($post_ID, '_edd_bundled_products', $group_video_post_ids);
				}			

			}
                        $group_index+=1;
                }
	}
	return True;
}

function hss_edd_get_video_download_links($hss_video_id) {

        global $user_ID;
        $options = get_option('hss_options');
        $userId = $user_ID;


                $params = array(
                   'method' => 'secure_videos.get_all_video_download_links',
                   'api_key' => $options['api_key'],
                   'video_id' => $hss_video_id,
                   'private_user_id' => $userId,
		   'database_id' => $options['database_id']
                );
                _log($params);
                $response = wp_remote_post( "https://www.hoststreamsell.com/services/api/rest/xml/", array(
                        'method' => 'POST',
                        'timeout' => 15,
                        'redirection' => 5,
                        'httpversion' => '1.0',
                        'blocking' => true,
                        'headers' => array(),
                        'body' => $params,
                        'cookies' => array()
                    )
                );
                $res = "";
                if( is_wp_error( $response ) ) {
                   $return_string .= 'Error occured retieving video information, please try refresh the page';
                } else {
                   $res = $response['body'];
                }

                $xml = new SimpleXMLElement($res);
                _log($xml);

                $purchase_option_count = (int)$xml->result[0]->download_option_count;
                $option_index = 1;
		$return_string = "";
                if($purchase_option_count > 0)
                {
			$return_string = "<div>Video file downloads:</div>";
                	while($option_index <= $purchase_option_count)
                        {
                        	$url = $xml->result[0]->{'download_option'.$option_index}[0]->url;
                        	$name = $xml->result[0]->{'download_option'.$option_index}[0]->name;
				$return_string = $return_string.'<div class="edd_download_file"><a href="'.$url.'">'.$name.'</a></div>';
				$option_index+=1;
			}
		}else{
			$return_string = "<div>No Video file downloads</div>";
		}


		return $return_string;
}

function hss_edd_user_action_links($actions, $user_object) {
	$actions['hss_edd_add_video_access'] = '<a href="' . wp_nonce_url(admin_url('options-general.php?page=hss_admin&update_user_videos=yes&user=' . $user_object->ID), 'update_hss_edd_videos', '_wpnonce') . '">' . __('Add Video Access', 'hss-edd') . '</a>';
	return $actions;
}
if(is_main_site()) {
	add_filter('user_row_actions', 'hss_edd_user_action_links', 10, 2);
}

function hss_edd_set_download_labels($labels) {
	$labels = array(
	'name' => _x('Products', 'post type general name', 'edd'),
	'singular_name' => _x('Product', 'post type singular name', 'edd'),
	'add_new' => __('Add New', 'edd'),
	'add_new_item' => __('Add New Download', 'edd'),
	'edit_item' => __('Edit', 'edd'),
	'new_item' => __('New', 'edd'),
	'all_items' => __('All', 'edd'),
	'view_item' => __('View', 'edd'),
	'search_items' => __('Search', 'edd'),
	'not_found' => __('No Videos found', 'edd'),
	'not_found_in_trash' => __('No Videos found in Trash', 'edd'),
	'parent_item_colon' => '',
	'menu_name' => __('Videos & Downloads', 'edd')
	);
	return $labels;
}
add_filter('edd_download_labels', 'hss_edd_set_download_labels');

/*
* Create a random string
*@param $length the length of the string to create
* @return $str the string
*/
function randomString($length = 12) {
        $str = "";
        $characters = array_merge(range('A','Z'), range('a','z'), range('0','9'));
        $max = count($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
                $rand = mt_rand(0, $max);
                $str .= $characters[$rand];
        }
        return $str;
}

?>

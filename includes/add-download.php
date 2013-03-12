<?

register_activation_hook(__FILE__, 'hss_add_defaults');
register_uninstall_hook(__FILE__, 'hss_delete_plugin_options');
add_action('admin_init', 'hss_init' );

function hss_add_defaults() {
        $tmp = get_option('hss_options');
    if(($tmp['chk_default_options_db']=='1')||(!is_array($tmp))) {
                delete_option('hss_options'); // so we don't have to reset all the 'off' checkboxes too! (don't think this is needed but leave for now)
                $arr = array(   "api_key" => "",
                );
                update_option('hss_options', $arr);
        }
}

function hss_delete_plugin_options() {
        delete_option('hss_options');
}

function hss_init(){
        register_setting( 'hss_plugin_options', 'hss_options', 'hss_validate_options' );
}

function hss_validate_options($input) {
         // strip html from textboxes
        $input['api_key'] =  wp_filter_nohtml_kses($input['api_key']); // Sanitize textarea input (strip html tags, and escape characters)
        return $input;
}

add_action('admin_head', 'my_action_javascript');

function my_action_javascript() {
?>
<script type="text/javascript" >
jQuery(document).ready(function($) {
    $('#myajax').click(function(){
        var data = {
            action: 'my_action'
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

add_action('wp_ajax_my_action', 'my_action_callback');

function my_action_callback() {
	$res = update_videos();
	if($res==True)
		echo "Success";
	else
		echo "Error occurred ".$res;
	die(); // this is required to return a proper result
}






add_action('wp_head','pluginname_ajaxurl');
function pluginname_ajaxurl() {
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
 echo get_video_download_links($video_id);

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





function hss_options_page () {
?>
        <div class="wrap">

                <!-- Display Plugin Icon, Header, and Description -->
                <div class="icon32" id="icon-options-general"><br></div>
                <h2>HostStreamSell S2Member Plugin Settings</h2>
                <p>Please enter the settings below...</p>

                <!-- Beginning of the Plugin Options Form -->
                <form method="post" action="options.php">
                        <?php settings_fields('hss_plugin_options'); ?>
                        <?php $options = get_option('hss_options'); ?>

                        <!-- Table Structure Containing Form Controls -->
                        <!-- Each Plugin Option Defined on a New Table Row -->
                        <table class="form-table">

                                <!-- Textbox Control -->
                                <tr>
                                        <th scope="row">API KEY</th>
                                        <td>
                                                <input type="text" size="57" name="hss_options[api_key]" value="<?php echo $options['api_key']; ?>" />
                                        </td>
                                </tr>
                                <tr>
                                        <th scope="row">Disable updating video descriptions</th>
                                        <td>
                                                <input type="checkbox" name="hss_options[disable_desc_updates]" value="1"<?php checked( 1 == $options['disable_desc_updates']); ?> />
                                        </td>
                                </tr>				
				<tr>
				        <th scope="row">Add/Update Videos</th>
				        <td>
						<div><input type="button" value="Update" id="myajax" /></div>
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

function hss_menu () {
        add_options_page('HostStreamSell Admin','HSS Admin','manage_options','hss_admin', 'hss_options_page');
}

add_action('admin_menu','hss_menu');




function is_stream($post_id) {
        global $edd_options;
?>
        <p>
                <strong><?php _e( 'Pricing Options:', 'edd' ); ?></strong>
        </p>
<?
			echo '<input type="hidden" name="is_streaming_video" value="1"/>';
			echo '<input type="hidden" name="_edd_video_id" value="'.(get_post_meta($post_id, '_edd_video_id', true)).'"/>';
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
                        <? echo get_post_meta($post_id, '_price_details',true); ?> <input type="text" name="edd_price" id="edd_price" value="<?php echo isset( $price ) ? esc_attr( edd_format_amount( $price ) ) : ''; ?>" size="30" style="width:80px;" placeholder="9.99" readonly="readonly" /><?php echo edd_currency_filter(''); ?>
                <?php endif; ?>
        </div>
<?
					
					//echo '<p>'.get_post_meta($post_id, '_price_details',true).': '.get_post_meta($post_id, 'edd_price',true).'</p>';
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
remove_action('edd_meta_box_fields', 'edd_render_price_field', 10);
add_action('edd_meta_box_fields', 'is_stream', 20);


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
	                'is_streaming_video'
                
        );
	_log("***");
	_log($fields);
        foreach($fields as $field) {
                if(isset($_POST[$field])) {
			_log("new=".$field);
                        $old = get_post_meta($post_id, $field, true);
			_log("old=".$old);
                        if($old != $_POST[$field]) {
                                if( is_string( $_POST[$field] ) ) {
                                        $new = esc_attr( $_POST[$field] );
                                } else {
                                        $new = $_POST[$field];
                                }
				_log("update new=".$new);
                                update_post_meta($post_id, $field, $new);
                        }
                } else {
                        delete_post_meta($post_id, $field);
                }
        }
}
add_action('save_post', 'edd_download_meta_box_save_stream');




#function edd_append_purchase_link_stream($content) {
function edd_append_purchase_link_stream($download_id) {
        global $post;
	global $is_iphone;
        global $user_ID;
	$video = "";
        if($post->post_type == 'download' && is_singular() && is_main_query()) {
                if(!get_post_meta($post->ID, '_edd_hide_purchase_link', true)) {

			if(get_post_meta($post->ID, 'is_streaming_video', true)) {
				$options = get_option('hss_options');
				$userId = $user_ID;
        
		if(edd_has_user_purchased($user_ID, $post->ID))
			$video = "<center>You have access to this video</center>";

		$hss_video_id = get_post_meta($post->ID, '_edd_video_id', true);
                $params = array(
                   'method' => 'secure_videos.get_video_playback_details',
                   'api_key' => $options['api_key'],
                   'video_id' => $hss_video_id,
                   'private_user_id' => $userId
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
                $title = $xml->result->title;
                $hss_video_title = $title;
                $user_has_access = $xml->result->user_has_access;
                $description = $xml->result->description;
                $feature_duration = $xml->result->feature_duration;
                $trailer_duration = $xml->result->trailer_duration;
                $video_width = $xml->result->width;
                $video_height = $xml->result->height;
                if($video_width>640){
                        $width_height = "640,390";
			$video_width = "640";
			$video_height = "390";
                }else
                        $width_height = $video_width.",".$video_height;
                $referrer = site_url();
		$hss_video_user_token = $xml->result->user_token;

                $hss_video_mediaserver_ip = $xml->result->wowza_ip;

                $hss_video_smil_token = "?privatetoken=".$hss_video_user_token;
                $hss_video_mediaserver_ip = $xml->result->wowza_ip;

                $hss_video_smil = $xml->result->smil;
                $hss_video_big_thumb_url = $xml->result->big_thumb_url;
                
                $referrer = site_url();

		$content_width = $video_width;
		$content_height = $video_height;

                if($is_iphone){
                        if(isset($options['mobile_device_content_width']))
                                if(trim($options['mobile_device_content_width'])!="")
                                        $content_width = trim($options['mobile_device_content_width']);
                }elseif($content_width<320){
                        $content_width=320;
                }

                if($video_width>$content_width){
                        $mod = $content_width%40;
                        $video_width = $content_width-$mod;
                        $multiple = $video_width/40;
                        $video_height = $multiple*30;
                }
                $apple_video_width = $video_width;
                $apple_video_height = $video_height;

                $width_height = $video_width.",".$video_height;
                $video = $video."

                <script type=\"text/javascript\" src=\"http://hoststreamsell.com/vendors/jquery/jquery-1.6.2.min.js\"></script>
                <script type=\"text/javascript\" src=\"http://hoststreamsell.com/mod/secure_videos/jwplayer/swfobject.js\"></script>
                <script type=\"text/javascript\" src=\"http://hoststreamsell.com/mod/secure_videos/jwplayer/jwplayer.js\"></script>

                <center>
                <div>
                <div id='videoframe'>If you are seing this you may not have Flash installed!</div>

                <SCRIPT type=\"text/javascript\">

                var viewTrailer = false;
                var videoFiles = new Array();;
                var trailerFiles = new Array();;

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
                else { newJWPlayerDynamic(); }


                function newJWPlayerDynamic()
                {
                  var so = new SWFObject('http://www.hoststreamsell.com/mod/secure_videos/jwplayer/player.swf','ply',$width_height,'9','#ffffff');
                  so.addParam('allowfullscreen','true');
                  so.addParam('allowscriptaccess','always');
                  so.addParam('wmode','opaque');
                  so.addVariable('autostart','false');
                  so.addVariable('file', 'http://www.hoststreamsell.com/mod/secure_videos/private_media_playlist.php?params=".$hss_video_id."!".urlencode($referrer)."!".$hss_video_user_token."!');
                  so.addVariable('skin', 'http://www.hoststreamsell.com/mod/secure_videos/jwplayer/glow_hss.zip'); 
                  so.write('videoframe');
                }

                function sethtml5PlayerBitrate(bitrate)
                {
                var player=document.getElementById(\"videoframe\");
                player.innerHTML='<VIDEO '+
                'SRC=\"http://".$hss_video_mediaserver_ip.":1935/hss/'+bitrate+'/playlist.m3u8".$hss_video_smil_token."&referer=".urlencode($referrer)."\" '+
                'HEIGHT=\"".$apple_video_height."\" '+
                'WIDTH=\"".$apple_video_width."\" '+
                'poster=\"".$hss_video_big_thumb_url."\" '+
                'title=\"".$hss_video_title."\">'+
                '</video>';
                }

                function html5Player()
                {
                var player=document.getElementById(\"videoframe\");
                player.innerHTML='<video controls '+
                'src=\"http://".$hss_video_mediaserver_ip.":1935/hss/smil:".$hss_video_smil."/playlist.m3u8".$hss_video_smil_token."&referer=".urlencode($referrer)."\" '+
                'HEIGHT=\"".$apple_video_height."\" '+
                'WIDTH=\"".$apple_video_width."\" '+
                'poster=\"".$hss_video_big_thumb_url."\" '+
                'title=\"".$hss_video_title."\">'+
                '</video>';
                }
                </SCRIPT>
                </div>
                </center>
                <BR>";

			}

                }
        }
	echo $video;
}
add_action( 'edd_before_download_content', 'edd_append_purchase_link_stream' );

function edd_append_purchase_link_stream_two( $download_id ) {
	if(!edd_has_variable_prices($download_id))
		echo "<BR>".get_post_meta($download_id, '_price_details', true)."<BR><BR>";
	else
		echo "<BR>";
}
add_action( 'edd_after_download_content', 'edd_append_purchase_link_stream_two' ,5);

function edd_complete_purchase_add_video($payment_id, $new_status, $old_status) {

        if( $old_status == 'publish' || $old_status == 'complete')
                return; // make sure that payments are only completed once

        if( ! edd_is_test_mode() ) {
                           
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


			if(get_post_meta($download['id'], 'is_streaming_video', true)) {
                                $options = get_option('hss_options');

                                $userId = $user_info['id'];
		                $hss_video_id = get_post_meta($download['id'], '_edd_video_id', true);
				$ppv_option = null;
				if(empty($download['options']))
					$ppv_option = get_post_meta($download['id'], '_edd_ppv_id', true);
				else
					$ppv_option = $download['options']['price_id'];
				_log("video id = ".$hss_video_id);
				_log("ppv option = ".$ppv_option);

			        $params = array(
			           'method' => 'secure_videos.add_user_ppv',
			           'api_key' => $options['api_key'],
			           'ppv_id' => $ppv_option,
			           'private_user_id' => $userId
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
		                        #sleep(10);
		                }else if( $response['response']['code'] != "200" ) {
                		        _log("request code bad: ".$response['response']['code']."\n");
		                        #sleep(10);
		                }else{
                		        _log("request code good: ".$response['response']['code']."\n");
		                        #$request_success = True;
                		}
                   		$res = $response['body'];

		                $xml = new SimpleXMLElement($res);
                		_log($xml);
			}

                }

        }

        // empty the shopping cart
        edd_empty_cart();
}
add_action('edd_update_payment_status', 'edd_complete_purchase_add_video', 10, 3);


function update_videos()
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
   	   _log("ERROR");
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
                        	$group_id = (string)$group_xml->result[0]->{'video_group'.$group_index}[0]->video_group_id;
                                $group_title = (string)$group_xml->result[0]->{'video_group'.$group_index}[0]->title[0];
                                $group_description = (string)$group_xml->result[0]->{'video_group'.$group_index}[0]->description[0];
				_log("Group id=".$group_id);
				_log(get_cat_ID( $group_title ));
				if( !term_exists( $group_title,'download_category' )){
				 	_log("Creating category ".$group_title);   	
					wp_insert_term(
					  $group_title, // the term 
					  'download_category' // the taxonomy
					);
				}
				    	#$group_term = term_exists(
                                        #  $group_title, // the term 
                                        #  'download_category' // the taxonomy
                                        #);
					#$cat_ID = $group_term['term_id'];
					#wp_set_object_terms($cat_ID,
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
							  #'post_status' => 'publish',
							  #'posts_per_page' => -1,
							  #'caller_get_posts'=> 1
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
									     //'post_content' => $description,
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
								     #'post_category' => array($cat_ID)
								  );
							
								// Insert the post into the database
								$post_ID = wp_insert_post( $my_post );

	_log("1");
    $url = $thumbnail; 
    $tmp = download_url( $url );
    $file_array = array(
        'name' => basename( $url ),
        'tmp_name' => $tmp
    );
	_log("2");

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

					                $purchase_option_count = (int)$xml->result[0]->{'video'.$index}[0]->option_count;
							$prices = array();
						               $option_index = 1;
							$option_price = "";
							$lowest_price = 0;
							$option_name = "";
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
					                                #$purchase_options[$option_index] = $purchase_option_details;
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
									//$prices[$option_id] = array('name' => 'Option '.$option_index,'amount' => $option_price);
									_log("option id=".$option_id);
									_log($prices[$option_id]["name"]);
						                        $option_index+=1;
						                }
						        }
							_log("PostID=".$post_ID);
							if($option_index==2){
								update_post_meta($post_ID, '_variable_pricing','0');
								update_post_meta($post_ID, 'edd_price',$option_price);
								update_post_meta($post_ID, '_price_details',$option_name);
								update_post_meta($post_ID, '_edd_ppv_id',$option_id);
							}else{
								update_post_meta($post_ID, '_variable_pricing','1');
								update_post_meta($post_ID, 'edd_variable_prices',$prices);
							}
							update_post_meta($post_ID, 'is_streaming_video',true);
							
							#Need to check if any other meta data is set and reapply it
								
							$index+=1;
						}
                        			

			                
                                }
			}
                        $group_index+=1;
                }
	}
	return True;
}

function get_video_download_links($hss_video_id) {

	#secure_videos_get_video_download_link($api_key,$video_id,$encode_id,$private_user_id)
        global $user_ID;
        $options = get_option('hss_options');
        $userId = $user_ID;

	//$encode_id = 162;

                $params = array(
                   'method' => 'secure_videos.get_all_video_download_links',
                   'api_key' => $options['api_key'],
                   'video_id' => $hss_video_id,
		   //'encode_id' => $encode_id,
                   'private_user_id' => $userId
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
				#$return_string = $return_string.'<LI><a href="'.$url.'">'.$name.'</a></LI>';
				$return_string = $return_string.'<div class="edd_download_file"><a href="'.$url.'">'.$name.'</a></div>';
				$option_index+=1;
			}
			//$return_string = $return_string."</UL>";
		}else{
			$return_string = "<div>No Video file downloads</div>";
		}


		return $return_string;
}


/*function pw_edd_product_labels( $labels ) {
	$labels = array(
	   'singular' => __('Video', 'http://wordpress2.hoststreamsell.com'),
	   'plural' => __('Videos', 'http://wordpress2.hoststreamsell.com')
	);
	return $labels;
}
add_filter('edd_default_downloads_name', 'pw_edd_product_labels');
*/
function set_download_labels($labels) {
	$labels = array(
	'name' => _x('Videos', 'post type general name', 'edd'),
	'singular_name' => _x('Video', 'post type singular name', 'edd'),
	'add_new' => __('Add New', 'edd'),
	'add_new_item' => __('Add New Video', 'edd'),
	'edit_item' => __('Edit Video', 'edd'),
	'new_item' => __('New Video', 'edd'),
	'all_items' => __('All Videos', 'edd'),
	'view_item' => __('View Video', 'edd'),
	'search_items' => __('Search Videos', 'edd'),
	'not_found' => __('No Videos found', 'edd'),
	'not_found_in_trash' => __('No Videos found in Trash', 'edd'),
	'parent_item_colon' => '',
	'menu_name' => __('Videos', 'edd')
	);
	return $labels;
}
add_filter('edd_download_labels', 'set_download_labels');

/*function pw_edd_product_labels( $labels ) {
        $labels = array(
           'singular' => __('Product', 'http://wordpress2.hoststreamsell.com'),
           'plural' => __('Products', 'http://wordpress2.hoststreamsell.com')
        );
        return $labels;
}
add_filter('edd_default_downloads_name', 'pw_edd_product_labels');

function set_download_labels($labels) {
        $labels = array(
        'name' => _x('Products', 'post type general name', 'http://wordpress2.hoststreamsell.com'),
        'singular_name' => _x('Product', 'post type singular name', 'http://wordpress2.hoststreamsell.com'),
        'add_new' => __('Add New', 'http://wordpress2.hoststreamsell.com'),
        'add_new_item' => __('Add New Product', 'http://wordpress2.hoststreamsell.com'),
        'edit_item' => __('Edit Product', 'http://wordpress2.hoststreamsell.com'),
        'new_item' => __('New Product', 'http://wordpress2.hoststreamsell.com'),
        'all_items' => __('All Products', 'http://wordpress2.hoststreamsell.com'),
        'view_item' => __('View Product', 'http://wordpress2.hoststreamsell.com'),
        'search_items' => __('Search Products', 'http://wordpress2.hoststreamsell.com'),
        'not_found' => __('No Products found', 'http://wordpress2.hoststreamsell.com'),
        'not_found_in_trash' => __('No Products found in Trash', 'http://wordpress2.hoststreamsell.com'),
        'parent_item_colon' => '',
        'menu_name' => __('Products', 'http://wordpress2.hoststreamsell.com')
        );
        return $labels;
}
add_filter('edd_download_labels', 'set_download_labels');

*/

?>

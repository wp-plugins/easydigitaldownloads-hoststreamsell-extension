=== Easy Digital Downloads HSS Extension for Streaming Video ===
Author URI: http://www.hoststreamsell.com
Plugin URI: http://wordpress2.hoststreamsell.com
Contributors: hoststreamsell
Tags: sell,video,streaming,cart
Requires at least: 3.3
Tested up to: 3.8
Stable tag: 0.96
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


Sell access to streaming videos through WordPress by integrating the HostStreamSell video platform with the great EasyDigitalDownloads plugin

== Description ==

Sell access to streaming videos with total control over how long you want to
give access for, whether you want to allow downloads or be stream only, and
whether you want to limit the amount of usage in terms of a bandwidth usage cap.


**Follow this plugin on [GitHub](https://github.com/hoststreamsell/easydigitaldownloads_hoststreamsell_ext)**

Features of the plugin include:

* Extend the flexibility of the EasyDigitalDownloads plugin to sell access to videos hosted on the HostStreamSell platform

More information at [HostStreamSell.com](http://hoststreamsell.com/).


== Installation ==

1. Activate the plugin
2. Go to Settings > HSS Admin and enter API key from your HostStreamSell
account
3. Click the Update key to Pull video information from HostStreamSell platform
and insert into the system automatically (also to update)


See https://easydigitaldownloads.com/themes/ for some pre built options for themes specifically designed to work with Easy DigitalDownloads.
https://easydigitaldownloads.com/themes/edd-starter-theme/ is a nice free
theme which is a good starting point.

Create a page with the [download_history] shortcode if you want customers to
be able to see all videos purchased and download a capy.

Add the following to your template functions.php file to add some extra
information on the video page.

function hss_edd_append_purchase_info_and_links( $download_id ) {
        global $post;
        $video = "";
        if($post->post_type == 'download' && is_singular() && is_main_query())
	{
                if(get_post_meta($post->ID, 'is_streaming_video', true)) {
                        $options = get_option('hss_options');
                        $userId = $user_ID;
                        $hss_video_id = get_post_meta($post->ID,'_edd_video_id', true);

                        $args=array(
                           'meta_key'=> 'is_streaming_video_bundle',
                           'meta_value'=> true,
                           'post_type' => 'download',
                        );
                        _log($args);
                        $my_query = null;
                        $my_query = new WP_Query($args);
                        $groups_found = false;
                        if( $my_query->have_posts() ) {
                                while ( $my_query->have_posts() ) {
                                        $video_group_post =$my_query->next_post();
                                        $bundled_videos =get_post_meta($video_group_post->ID, '_edd_bundled_products', true);
                                        if (in_array($post->ID,$bundled_videos)){
                                                if($groups_found==false){
                                                        $video =$video."<div><BR></div>";
                                                        $video =$video."<div><br>This video can be purchased in the following series:</div>";
                                                        $groups_found=true;
                                                }
                                                $video = $video."<div><ahref='".get_permalink($video_group_post)."'>".$video_group_post->post_title."</a></div>";
                                        }
                                }
                                if((!get_post_meta($post->ID,'_edd_hide_purchase_link', true)) and ($groups_found==true))
                                        $video = $video."<BR>This video can bepurchased on its own:";
                                elseif(!get_post_meta($post->ID,'_edd_hide_purchase_link', true))
                                        $video = $video."<BR>Video purchaseoptions:";
                        }
                }
                if(!edd_has_variable_prices($download_id))
                        $video = $video."<BR>".get_post_meta($download_id,'_price_details', true)."<BR>";
                else
                        $video = $video."<BR>";
        }
        if(get_post_meta($post->ID, 'is_streaming_video_bundle', true)) {
                $options = get_option('hss_options');
                $userId = $user_ID;
                $video = "<div>Videos included in this series:</div>";
                $bundled_videos = get_post_meta($post->ID,'_edd_bundled_products', true);
                $count = sizeof($bundled_videos);
                for($counter=0;$counter<$count;$counter++){
                        $vidpost = get_post($bundled_videos[$counter]);
                        $video = $video."<div>- <ahref='".get_permalink($bundled_videos[$counter])."'>".$vidpost->post_title."</a></div>";
                }
                $video = $video."<div><BR></div>";
        }

        echo $video;
}
add_action( 'hss_edd_show_video_purchase_details','hss_edd_append_purchase_info_and_links' ,5);



== Frequently Asked Questions ==

= Does this work with other video platforms =

No this only works with the HostStreamSell video platform

== Screenshots ==


== Changelog ==

= 0.2 =

* Initial version uploaded to WordPress

= 0.5 =

* Release with fix for viewing on android devices

= 0.6 =

* Add support for selling groups of videos as one purchase

= 0.7 =

*Add support to set video player width and height as plugin settings

= 0.8 =

*Added filter to be able to control what text or links are shown after the
video player from within a theme

= 0.9 =

*Added setting to add a JW Player license to remove the logo

= 0.91 =

*changed js and jwplayer resource links to https

= 0.92 =

*included jwplayer option to stretch thumbnail image to fill player

= 0.93 =

*made the jwplayer stretching setting a plugin option so that this can be
tweaked as needed

= 0.94 =

*fixed default jwplayer stretching option to be uniform. fixed some logic around creating groups

= 0.95 =

*Added recording of failed rest calls after purchase of a video which can be retried later

= 0.96 =

*Added automatic log file generation in wp-content/uploads/hss_edd/log.txt and the ability to set the log level*

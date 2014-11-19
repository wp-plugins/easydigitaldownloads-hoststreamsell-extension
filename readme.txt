=== Easy Digital Downloads HSS Extension for Streaming Video ===
Author URI: https://www.hoststreamsell.com
Plugin URI: http://edd_demo.hoststreamsell.com
Contributors: hoststreamsell
Tags: sell,video,streaming,cart
Requires at least: 3.3
Tested up to: 4.0
Stable tag: 1.15
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


Sell access to streaming videos through WordPress by integrating the HostStreamSell video platform with the great EasyDigitalDownloads plugin

== Description ==

Sell access to streaming videos with total control over how long you want to
give access for, whether you want to allow downloads or be stream only, and
whether you want to limit the amount of usage in terms of a bandwidth usage cap.

Features of the plugin include:

* Extend the flexibility of the EasyDigitalDownloads plugin to sell access to videos hosted on the HostStreamSell platform
* Sell a single video as a single item
* Sell a group of videos as a single purchasable item
* Have multiple selling options for a video or group of videos

This plugin requires your customer to register on your website, as their WordPress user ID in your database will be used in our system for adding (and later verifying) access to a video. This requires that they log in every time they come back to your website and want to stream or download their purchased videos. A sample history-downloads.php template file is included with this plugin to allow you easily create a page showing a users purchased videos, links to the videos, and download links (if you have allowed download access). See [EasyDigitalDownloads.com](https://easydigitaldownloads.com/docs/customizing-the-download-history-template/) for more details on using this template.

Please read Installation instructions!

More information at [www.HostStreamSell.com](https://www.hoststreamsell.com).

Demo at [edd_demo.hoststreamsell.com](http://edd_demo.hoststreamsell.com).

== Installation ==

1. Activate the plugin
2. Go to Settings > HSS Admin and enter API key from your HostStreamSell
account
3. Click the Update key to Pull video information from HostStreamSell platform
and insert into the system automatically (also to update)
4. Go to Videos > Settings and then select the Misc tab at the top of the
page. Check the box for 'Disable Guest Checkout' and press Save Changes


See https://easydigitaldownloads.com/themes/ for some pre built options for themes specifically designed to work with Easy DigitalDownloads.
https://easydigitaldownloads.com/themes/edd-starter-theme/ is a nice free
theme which is a good starting point.

Create a page with the [download_history] shortcode if you want customers to be able to see all videos purchased and download a copy. You will place the history-downloads.php template file provided in a directory called edd_templates in your theme directory (rename from history-downloads.php.txt to history-downloads.php).

Add the following to your template functions.php file to add some extra information on the video page. Feel free to customize this to your needs - that is why it is outside of the main plugin :)

`function hss_edd_append_purchase_info_and_links( $download_id ) {
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
                                                $video = $video."<div><a href='".get_permalink($video_group_post)."'>".$video_group_post->post_title."</a></div>";
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
                        $video = $video."<div>- <a href='".get_permalink($bundled_videos[$counter])."'>".$vidpost->post_title."</a></div>";
                }
                $video = $video."<div><BR></div>";
        }

        echo $video;
}
add_action( 'hss_edd_show_video_purchase_details','hss_edd_append_purchase_info_and_links' ,5);`

To create a custom receipt which provides links to the videos purchased, use the following code in your functions.php file, and the EasyDigitalDownloads receipt template

`function custom_edd_email_tags($message, $payment_data) {
                $downloads = maybe_unserialize($payment_data['downloads']);

                $links = "<ul>";
                foreach($downloads as $download) {

                        if((get_post_meta($download['id'],'is_streaming_video', true)) or (get_post_meta($download['id'],'is_streaming_video_bundle', true))) {
                               $links .= "<li><a href=\"".get_permalink($download['id'])."\">".get_the_title($download['id'])."</a></li>";

                                if(get_post_meta($download['id'],'is_streaming_video_bundle', true)){
                                        $bundled_videos = get_post_meta($download['id'], '_edd_bundled_products', true);
                                        $count = sizeof($bundled_videos);
                                        for($counter=0;$counter<$count;$counter++){
                                                $links .= "<li>- <a href='".get_permalink($bundled_videos[$counter])."'>".get_the_title($bundled_videos[$counter])."</a></li>";
                                        }
                                }
                        }
                }
                $links .= "</ul>";

        $message = str_replace('{list_video_pages}', $links, $message);
        return $message;
}
add_filter('edd_email_template_tags', 'custom_edd_email_tags', 10, 2);`

`Hi {name},

Thank you for your recent purchase.

You can access your videos with the following link(s):

Note: you need to be logged in to view the full videos

{list_video_pages} 

Price: {price}

Receipt ID: {receipt_id}

Date: {date}`

== Frequently Asked Questions ==

= Does this work with other video platforms =

No this only works with the HostStreamSell video platform

= How do I style the text which appears above the video player to say whether
it is the trailer or full video?

Add the following to your theme's style.css to for example make the text centered, and the text red for the trailer and green for the full video:

.hss_watching_trailer_text { text-align:center; color:red; }
.hss_watching_video_text { text-align:center; color:green; }

You can set what the text says (or whether to show any text at all through the plugin's settings)

= How do I manually add video access for a user? =

In your WordPress dashboard, go to your list of users. Mouse over a user in the list and you will see a new Add Video Access link to the right of the Edit and Delete links. This will bring you to another page where you choose the video access you want to add for this user and press the Add Video Access button. Note that a new payment entry will be added for this user which shows $0.00, and the user will be sent an email just as if they had purchased the video.

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

= 0.97 =

*Fixed a typo in the readme file which shows an example of the function hss_edd_append_purchase_info_and_links. Added code to add links to videos in the receipt*

= 0.98 =

*Updates to the readme file for installation instructions

= 0.99 =

* added meta key to each video post which can be used for ordering the posts when viewing a category

= 1.00 =

* added support to set a Website Reference ID. The default for this will be 0 (zero), and should only be changed in the event that you want multiple WordPress websites selling the same videos. You set a different ID for each website, which is used to distinguish for example a customer with WordPress user ID of 5 on one website, with a totally different user on another website with the same user ID of 5

= 1.01 =
*Added ability to configure text above video when showing trailer or full video through a plugin setting

= 1.02 =
*Added ability to manually add video access for a registered user on your wordpress website

= 1.03 =
*Changed drop-down for manually adding access to be a list with checkboxes. Also fixed up some things on the EDD add/edit screens to allow adding normal file downloads for sale

= 1.04 =
fixed an issue with manually adding video access

= 1.05 =
Update for subtitle support

= 1.06 =
Syntax fixes

= 1.07 =
Syntax fixes

= 1.1 =
Added support for responsive JW Player

= 1.11 =

Made improvements to enable videos still be created if upload directory is not present or not writeable

= 1.13 =

Remove spaces from API key and database ID settings when updated

= 1.14 =

Added action for outputing content under the video if user has purchased

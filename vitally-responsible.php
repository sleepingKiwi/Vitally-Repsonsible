<?php
/**
 * Plugin Name: Vitally Responsible
 * Plugin URI: https://github.com/sleepingKiwi/vitally-responsible
 * Description: Altering WordPress content for lazy responsive images.
 * Author: Tedworth & Oscar
 * Version: 2.0.1
 * Author URI: http://tedworthandoscar.co.uk
 */


/**
 * For Reference:
 * http://wordpress.stackexchange.com/questions/25910/uninstall-activate-deactivate-a-plugin-typical-features-how-to/25979#25979
 * http://wordpress.stackexchange.com/questions/113387/when-is-the-post-content-filtered-column-in-database-cleared-by-wordpress
 * http://www.php.net/manual/en/language.oop5.php
 * http://net.tutsplus.com/tutorials/php/object-oriented-php-for-beginners/
 * http://us3.php.net/manual/en/domdocument.getelementsbytagname.php
 * http://stackoverflow.com/questions/4824503/php-domdocument-need-to-change-replace-an-existing-html-tag-w-a-new-one
 * http://nacin.com/2010/05/18/rethinking-template-tags-in-plugins/
 *
 * Thanks to Hammy for a lot of the code here - https://github.com/noeltock/hammy/blob/master/hammy.php
 */


/**
 * Include WPThumb
 */
if ( !function_exists('wpthumb') ) {
        include_once('WPThumb/wpthumb.php');
}

/**
 * Setting up Options Page
 */
include_once( 'inc/options.php' );


/**
 * Automatic Updates
 * http://w-shadow.com/blog/2010/09/02/automatic-updates-for-any-plugin/
 */
require 'inc/plugin-updater/plugin-update-checker.php';
$MyUpdateChecker = PucFactory::buildUpdateChecker(
    'http://dist.tedworthandoscar.co.uk/vitally-responsible-2/metadata.json',
    __FILE__,
    'vitally-responsible'
);


/**
 * Registering hooks for activating/deactivating/uninstalling this plugin.
 */
register_activation_hook(   __FILE__, array( 'Vitally_Responsible', 'activation' ) );
register_deactivation_hook( __FILE__, array( 'Vitally_Responsible', 'deactivation' ) );
register_uninstall_hook( __FILE__, array( 'Vitally_Responsible', 'uninstall' ) );


/**
 * The plugin class itself. beautiful.
 */
class Vitally_Responsible {


    /**
     * Helper functions for transients
     */

        //deleting all transients saved by this plugin.
    public static function delete_vresp_transients(){
        global $wpdb;

        $transient_string = '_transient_vresp_%';
        $transient_timeout_string = '_transient_timeout_vresp_%';

        $sql = "DELETE FROM $wpdb->options WHERE option_name LIKE '$transient_string'";
        $clean_one = $wpdb -> query( $sql );
        $sql_two = "DELETE FROM $wpdb->options WHERE option_name LIKE '$transient_timeout_string'";
        $clean_two = $wpdb -> query( $sql_two );
    }


    /**
     * The plugin has been activated.
     *
     * Here we check to see whether there are any existing options saved for the plugin.
     * If we do it was probably deactivated but not uninstalled.
     * If not we set up some defaults
     */
    public static function activation(){
        
        // check permissions
        if ( ! current_user_can( 'activate_plugins' ) )
            return;

        // check for existing options
        if( !get_option('vitally_responsible_options') )
            self::vital_defaults();

    }


    /**
     * The plugin has been deactivated.
     *
     * deactivation removes all responsive content transient data currently
     */
    public static function deactivation(){
        
        // check permissions
        if ( ! current_user_can( 'activate_plugins' ) )
            return;

        //delete all transients saved by this plugin
        self::delete_vresp_transients();

        //for testing
        //delete_option( 'vitally_responsible_options' ); 

    }


    /**
     * The plugin was uninstalled!
     *
     * Checks user permissions etc. then deletes all options for this plugin.
     * Also deletes all transient data saved for responsive content
     */
    public static function uninstall(){
        
        //check permissions
        if ( ! current_user_can( 'activate_plugins' ) )
            return;

        // check if the file is the one that was registered during the uninstall hook.
        if ( __FILE__ != WP_UNINSTALL_PLUGIN )
            return;

        //cleaning up
        delete_option( 'vitally_responsible_options' ); 
        
        //delete all transients saved by this plugin
        self::delete_vresp_transients();
        
    }


    /**
     * Set defaults for the plugin options
     *
     * If plugin was freshly activated then set defaults for the options page.
     */
    private static function vital_defaults(){
        $vd = array( 
            'vital_sizes' => '(min-width: 81.25em) 1200px, (min-width: 30.1em) 90vw, 100vw', 
            'vital_crops' => '480,800,1200,1600,2400', 
            'vital_ignore' => 'thumbnail,vital-non-responsive', 
            'vital_padding' => 'true', 
            'vital_pixelholder' => 'false',
            'vital_quality' => '100'
        );
        update_option( 'vitally_responsible_options', $vd );
    }




    /**
     * Adding admin/option page styles
     */
    public static function admin_styles(){

        wp_enqueue_style( 'vitally-responsible-css', plugins_url( 'css/vitally-responsible-admin.css' , __FILE__ ), array(), '1.0', 'all' );

    }




    /**
     * Our own copy of the_content that caches/filters content automatically
     */
    public static function vital_the_content( $more_link_text = null, $strip_teaser = false) {

        global $post, $more; //warning: long term exposure to the global scope and static functions can cause health problems.

        //checking if this post has a <!--more-->
        //same check for more tag used by get_the_content in http://core.trac.wordpress.org/browser/tags/3.7.1/src/wp-includes/post-template.php
        $contains_more = preg_match( '/<!--more(.*?)?-->/', $post->post_content);

        // do we already have filtered content saved for this post? 
        $filtered_content_more = get_transient('vresp_more_'.$post->ID);

        $cached = false;

        if($contains_more === 1 && !$more){

            //there's a flipping <!--more--> tag to care about and we're on a page that requires less than the full content...
            $filtered_content_less = get_transient('vresp_less_'.$post->ID);
            if( false !== $filtered_content_less ){

                //there's filtered content, return it
                $content = $filtered_content_less;
                $cached = true;

            }

        }else{

            //there's either no <!--more--> or we're on a page that wants the full content
            if( false !== $filtered_content_more ){

                //there's filtered content, return it
                $content = $filtered_content_more;
                $cached = true;

            }

        }

        // if there's no filtered content already saved generate/save it
        if(!$cached){

            //get the content and filter it like this was a normal the_content call
            $content = get_the_content( $more_link_text, $strip_teaser );
            $content = apply_filters( 'the_content', $content );

            //do some magic to replace the <img>
            $filtered_content = self::responsible_filtering($content);

            //save the results for next time
            if($contains_more === 1 && !$more){
                // this is the 'preview' version of the post - with <!--more--> replaced
                $saved_content = $filtered_content . '<!-- transient (vresp_less_'.$post->ID.') saved:' . date('Y-m-d H:i:s') . '-->';
                set_transient('vresp_less_'.$post->ID, $saved_content, DAY_IN_SECONDS);
            }else{
                $saved_content = $filtered_content . '<!-- transient (vresp_more_'.$post->ID.') saved:' . date('Y-m-d H:i:s') . '-->';
                set_transient('vresp_more_'.$post->ID, $saved_content, DAY_IN_SECONDS);
            }

            $content = $filtered_content;
                
        }

        $content = str_replace( ']]>', ']]&gt;', $content );
        echo $content;
        if($cached)
echo '

<!-- ^^ content retrieved from cached post meta by Vitally Responsible ^^ -->

';
    }


    /**
     * This is the function that actually does all of the filtering/replacing
     *
     * We have to use regex to allow for replacing content in the_content seamlessly unfortunately
     * I'd like to use DOMDocument for everything but it's hard to convert back to a string 
     * because it wraps in new tags and tries to repair misformatted html etc.
     */
    public static function responsible_filtering( $content ){

        $vital_options = get_option('vitally_responsible_options');

        // paste into http://gskinner.com/RegExr/ for breakdown of regex
        // basically matches '<' then 0 or more (*) whitespace characters (\s) then 'img' then 0 or more (*) characters that aren't '>' ([^>]) then '>'. 
        // /i makes it case insensitive
        // http://php.net/manual/en/function.preg-match-all.php
        preg_match_all('/<\s*img[^>]*>/i', $content, $reg_images);

        if( !empty($reg_images) ){

            //$reg_images[0] is an array of all 'full pattern matches'
            foreach ($reg_images[0] as $key => $value) {
                
                //convert to DOMDocument to easily grab attributes
                $dom = new DOMDocument();
                $dom->loadHTML( $value );
                $images = $dom->getElementsByTagName('img');

                foreach ($images as $img) {

                    //grab image info
                    $o_src = $img->getAttribute('src');
                    $classes = $img->getAttribute('class');
                    $width = $img->getAttribute('width');
                    $height = $img->getAttribute('height');
                    $alt = $img->getAttribute('alt');
                    $title = $img->getAttribute('title');

                        //we're now deferring as the default
                    //$defer = '';
                    //if( $vital_options['vital_defer'] === 'true' ){
                        //$defer = 'data-deferred';
                        $classes .= ' js--vitally-responsible--deferred';
                    //}


                    $pad_class= '';
                    if($width && $height){
                        $ratio = round( (($height/$width)*100), 4);

                        if( $vital_options['vital_padding'] === 'true' ){
                            $classes .= ' js--vitally-responsible--padded';
                        }
                    }else{
                        $ratio = 'not-available';
                    }
                    

                    
                    

                    // are we ignoring it? (thanks to hammy for this!)
                    $ignoreClasses = explode( ",", $vital_options['vital_ignore'] );
                    $ignorelist = '/' . implode( "|", $ignoreClasses ) . '/';

                    if ( ! preg_match( $ignorelist, $classes ) ) {

                        
                        /**
                         * First we build up the srcset string - this is built up of all of the 
                         * cropped image sizes that are viable given our initial image size
                         */
                        $crop_sizes = explode( ",", $vital_options['vital_crops']);
                            //we save the largest source, 
                            //that's what our noscript element gets as the default src and what
                            //we use as a fallback link for worst cases.
                        $largest_source = '';
                        $largest_size = '';
                        $vital_resp_srcset = '';

                        foreach ( $crop_sizes as $size_key => $size ) {

                            if ( $size < $width ) {

                                //obviously we only add a crop if the original image was already bigger
                                $resized_image = wpthumb( $o_src, 'width=' . $size . '&crop=0&jpeg_quality='.$vital_options['vital_quality'] );

                                $largest_source = $resized_image;
                                $largest_size = $size;

                                if( $size_key !== 0 ){
                                    $vital_resp_srcset .= ', ';
                                }
                                $vital_resp_srcset .= $resized_image.' '.$size.'w';

                            }else{

                                /**
                                 * if we ever reach a point where the original is smaller than the
                                 * desired crop size we just add the original and break the loop...
                                 */
                                $largest_source = $o_src;
                                $largest_size = $width;

                                if( $size_key !== 0 ){
                                    $vital_resp_srcset .= ', ';
                                }
                                $vital_resp_srcset .= $o_src.' '.$width.'w';

                                break;

                            }

                        }

                            //if no crop sizes have been specified by user we make sure to add the full sized url...
                        if( count($crop_sizes) === 0 ){
                            $largest_source = $o_src;
                            $largest_size = $width;
                            $vital_resp_srcset = $o_src.' '.$width.'w';
                        }



                        $width_style = '';
                        if( ($width && $height) && $vital_options['vital_padding'] === 'true' ){
                                //this ensures the padders aren't padding too big!
                                //coupled with max-width in the css
                            $width_style = 'style="width:'.$largest_size.'px;"';
                        }




                            //building out the actual HTML structure
                            //the backup <a tag contains the alt text if it's longer than 0 chars 
                            //long otherwise the href.
                        $vital_resp = '<span class="js--vitally-responsible ' . $classes . '"
data-responsible
title="' . $title . '"
data-sizes="' . $vital_options['vital_sizes'] . '"
data-srcset="' . $vital_resp_srcset . '"
data-fallback="' . $largest_source . '"
data-alt="'. $alt .'"
data-width="'. $width .'" 
data-height="'. $height .'" 
data-padding="'. $ratio .'"
'. $width_style .'
>
<span class="js--vitally-responsible__alt" >'. $alt .'</span>';
                        


                            //adding the padding and placeholder elements if required
                        if( ($width && $height) && $vital_options['vital_padding'] === 'true' ){
                            $vital_resp .= '<span class="js--vitally-responsible__padder" style="padding-bottom:'.$ratio.'%;">';
                            if( isset($vital_options['vital_pixelholder']) ){
                                if($vital_options['vital_pixelholder'] === 'true'){
                                    $vital_resp .= '<span class="js--vitally-responsible__padder-back-image" style="background-image:url('.wpthumb( $o_src, 'width=16&crop=0&jpeg_quality=50' ).');"></span>';
                                }
                            }
                            $vital_resp .= '</span>';
                        }

                        
                            //adding the noscript fallback
                        $vital_resp .= '<noscript>
<img 
src="' . $largest_source . '" 
alt="' . $alt . '" 
title="' . $title . '" 
sizes="' . $vital_options['vital_sizes'] . '"
srcset="' . $vital_resp_srcset . '"
>
</noscript>
</span>';


                        $content = str_replace( $reg_images[0][$key], $vital_resp, $content );

                    }//end if no classes are ignored preg_match
                    
                }//end for each of DOMDocument image/s

            }// end for each of all matching images

        }// end if(!empty($reg_images))

        return $content;

    }


    /**
     * Clearing our cached filtered content whenever a post is updated in any way. They will be rebuilt next time the post/page is visited by anyone
     */
    public static function save_content_responsibly( $post_id ) {

        //if( isset($_POST['post_type']) && ($_POST['post_type'] == 'post' || $_POST['post_type'] == 'page') ){

            delete_transient('vresp_less_'.$post_id);
            delete_transient('vresp_more_'.$post_id);

        //}

    }




}// end class Vitally_Responsible



/**
 * Adding actions and filters
 */
// option page and admin styles
add_action( 'admin_print_styles', array('Vitally_Responsible', 'admin_styles'));

/**
 * Custom action to avoid our filter on the_content unwittingly filtering things that it's not meant to.
 *
 * The problem with adding our filter on the_content is that apply_filters( 'the_content', $whatever );
 * is thrown around quite liberally so even if we check that we're in the loop we can't be sure the
 * theme doesn't have something else in that loop applying the_content filters.
 * If we were just filtering out images every time this wouldn't be a big deal but because we'd rather just
 * cache the data and serve the saved meta data each time there could be issues with replacing the wrong content
 * with data stored in the global $post object. By only caching in this way on our own action we can be sure
 * that the_content filters continue to run as expected.
 *
 * http://codex.wordpress.org/Function_Reference/add_action
 * http://nacin.com/2010/05/18/rethinking-template-tags-in-plugins/
 */
add_action( 'vitally_responsible_content', array('Vitally_Responsible', 'vital_the_content'), 10, 2); 

//save filtered content on post update/save
add_action( 'save_post', array('Vitally_Responsible', 'save_content_responsibly') );

?>
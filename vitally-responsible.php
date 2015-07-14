<?php
/**
 * Plugin Name: Vitally Responsible
 * Plugin URI: https://github.com/sleepingKiwi/vitally-responsible
 * Description: Automatic responsive image plugin by Tedworth & Oscar, used in many of our bespoke themes. 
 * Author: Tedworth & Oscar
 * Version: 0.6.2
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
    'http://dist.tedworthandoscar.co.uk/vitally-responsible/metadata.json',
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
     * This is just for testing purposes really - deactivation removes all metadata currently
     */
    public static function deactivation(){
        
        // check permissions
        if ( ! current_user_can( 'activate_plugins' ) )
            return;

        delete_post_meta_by_key( 'vitally_filtered_responsibly_less' );
        delete_post_meta_by_key( 'vitally_filtered_responsibly_more' );

        //for testing
        //delete_option( 'vitally_responsible_options' ); 

    }


    /**
     * The plugin was uninstalled!
     *
     * Checks user permissions etc. then deletes all options for this plugin.
     * Also deletes all filtered content from all posts/pages where it was stored as meta
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
        delete_post_meta_by_key( 'vitally_filtered_responsibly_less' );
        delete_post_meta_by_key( 'vitally_filtered_responsibly_more' );
        
    }


    /**
     * Set defaults for the plugin options
     *
     * If plugin was freshly activated then set defaults for the options page.
     */
    private static function vital_defaults(){
        $vd = array( 'vital_breaks' => '480,1000', 'vital_crops' => '440,700,1000', 'vital_ignore' => 'nextgen,thumbnail', 'vital_padding' => 'false', 'vital_defer' => 'false', 'vital_enqueue' => 'false', 'vital_filter_content' => 'false', 'vital_one_point_five' => 'false', 'vital_quality' => '100');
        update_option( 'vitally_responsible_options', $vd );
    }


    /**
     * Enqueue standalone picturefill js if the options demand it!
     *
     * The options page gives the option of including picturefill automatically or for admins to handle it all themselves... 
     */
    public static function enqueue_picturefill(){

        $ops = get_option('vitally_responsible_options');

        if( $ops['vital_enqueue'] === 'true' ){
            wp_register_script( 'picturefill', plugins_url( 'js/vitally-responsible-picturefill.min.js' , __FILE__ ), array(), '1.0', true );
            wp_enqueue_script( 'picturefill' );
        }

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
        $filtered_content_more = get_post_meta($post->ID, 'vitally_filtered_responsibly_more', true);

        $cached = false;

        if($contains_more === 1 && !$more){

            //there's a flipping <!--more--> tag to care about and we're on a page that requires less than the full content...
            $filtered_content_less = get_post_meta($post->ID, 'vitally_filtered_responsibly_less', true);
            if( !empty( $filtered_content_less ) ){

                //there's filtered content, return it
                $content = $filtered_content_less;
                $cached = true;

            }

        }else{

            //there's either no <!--more--> or we're on a page that wants the full content
            if( !empty( $filtered_content_more ) ){

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
                update_post_meta($post->ID, 'vitally_filtered_responsibly_less', $filtered_content);
            }else{
                update_post_meta($post->ID, 'vitally_filtered_responsibly_more', $filtered_content);
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

                    $defer = '';
                    if( $vital_options['vital_defer'] === 'true' ){
                        $defer = 'data-deferred';
                        $classes .= ' data-deferred';
                    }


                    $pad_class= '';
                    if($width && $height){
                        $ratio = round( (($height/$width)*100), 2);

                        if( $vital_options['vital_padding'] === 'true' ){
                            $pad_class= 'picturefill-wrap-padded ';
                        }
                    }else{
                        $ratio = 'not-available';
                    }
                    

                    
                    

                    // are we ignoring it? (thanks to hammy for this!)
                    $ignoreClasses = explode( ",", $vital_options['vital_ignore'] );
                    $ignorelist = '/' . implode( "|", $ignoreClasses ) . '/';

                    if ( ! preg_match( $ignorelist, $classes ) ) {

                        $break_sizes = explode( ",", $vital_options['vital_breaks'] );
                        array_unshift($break_sizes, 0); //put a zero in first!
                        $crop_sizes = explode( ",", $vital_options['vital_crops']);


                        // Building markup using picturefill format - https://github.com/scottjehl/picturefill
                        $picturefill_one =  '<span data-picture data-alt="'. $alt .'" class="picturefill-wrap picturefill '. $pad_class . $classes .'" title="' . $title . '" '. $defer .' data-width="'. $width .'" data-height="'. $height .'" data-padding="'. $ratio .'" ';

                        $picturefill_two = '>';

                        if( ($width && $height) && $vital_options['vital_padding'] === 'true' ){
                            $picturefill_two .= '<span class="picturefill-padder" style="padding-bottom:'.$ratio.'%;"></span>';
                        }

                        $widest='0';
                        foreach ( $break_sizes as $size_key => $size ) {

                            if ( $crop_sizes[$size_key] < $width ) { // if the original is larger than our current size (first is 0 so we always get that at least)

                                $resized_image = wpthumb( $o_src, 'width=' . $crop_sizes[$size_key] . '&crop=0&jpeg_quality='.$vital_options['vital_quality'] );

                                //RETINA IMAGES ENABLED
                                $retina_one_point_five = false;
                                if($vital_options['vital_one_point_five'] === 'true'){
                                    //is the image big enough for the 1.5 scale crop?
                                    if ( $crop_sizes[$size_key]*1.5 < $width ) {
                                        $resized_one_point_five = wpthumb( $o_src, 'width=' . $crop_sizes[$size_key]*1.5 . '&crop=0&jpeg_quality='.$vital_options['vital_quality'] );
                                        $retina_one_point_five = true;
                                    }
                                }

                                if ( $size == 0 ) {
                                    $picturefill_two .= '<span data-src="' . $resized_image . '"></span>';
                                    if($retina_one_point_five){
                                        $picturefill_two .= '<span data-src="' . $resized_one_point_five . '" data-media="(-webkit-min-device-pixel-ratio: 1.5),(min-resolution: 144dpi)"></span>';
                                    }
                                }else{
                                    $picturefill_two .= '<span data-src="' . $resized_image . '" data-media="(min-width:'. $size .'px)"></span>';
                                    if($retina_one_point_five){
                                        $picturefill_two .= '<span data-src="' . $resized_one_point_five . '" data-media="(min-width:'. $size .'px) and (-webkit-min-device-pixel-ratio: 1.5), (min-width:'. $size .'px) and (min-resolution: 144dpi)"></span>';
                                    }
                                }

                                $widest = $crop_sizes[$size_key];

                            }else{

                                if( $size == 0 ) {
                                    //if the image is smaller than first crop size just add the original source...
                                    $picturefill_two .= '<span data-src="' . $o_src . '"></span>';
                                }else{
                                    $picturefill_two .= '<span data-src="' . $o_src . '" data-media="(min-width:'. $size .'px)"></span>';
                                }

                                $widest = $width;

                            }

                        }//end for each $break_sizes

                        $picturefill_two .= '<!-- IE lt 10 get the original source --> <!--[if (lt IE 10) & (!IEMobile)]> <span data-src="' . $o_src . '"></span> <![endif]--> <!-- Fallback content for non-JS browsers. Same img src as the initial, unqualified source element. --><noscript><img src="' . $o_src . '" alt="' . $alt . '" title="' . $title . '"></noscript></span>';

                        $width_style = '';
                        if( ($width && $height) && $vital_options['vital_padding'] === 'true' ){
                            $width_style = 'style="width:'.$widest.'px;"';
                        }
                        $picturefill = $picturefill_one.$width_style.$picturefill_two;

                        $content = str_replace( $reg_images[0][$key], $picturefill, $content );

                    }//end if no classes are ignored preg_match
                    
                }//end for each of DOMDocument image/s

            }// end for each of all matching images

        }// end if(!empty($reg_images))

        return $content;

    }


    /**
     * This is the good stuff. Filtering the content
     *
     * Filters the_content and replaces it with the filtered content (containing responsive image markup).
     * If there's no filtered content stored in vitally_filtered_responsibly meta field we generate and save it here. 
     * Things are complicated by the existence of <!--more--> and <!--noteaser--> and the fact that the 'more' text
     * can be customised in a lot of places (filtered, specified in <!--more-->, passed to the_content()/get_thecontent())
     */
    public static function make_content_responsible( $content ){

        // Only filter content in the loop because people are always chucking apply_filters( 'the_content', $whatever ); around
        // TODO - test with is_main_query() as well
        if( in_the_loop() ) { 

            $content = self::responsible_filtering($content);

        }

        return $content;

    }


    /**
     * Clearing our cached filtered content whenever a post is updated in any way. They will be rebuilt next time the post/page is visited by anyone
     */
    public static function save_content_responsibly( $post_id ) {

        //if( isset($_POST['post_type']) && ($_POST['post_type'] == 'post' || $_POST['post_type'] == 'page') ){

            delete_post_meta($post_id, 'vitally_filtered_responsibly_less');
            delete_post_meta($post_id, 'vitally_filtered_responsibly_more');

        //}

    }




}// end class Vitally_Responsible



/**
 * Adding actions and filters
 */

// enqueue scripts
add_action( 'wp_enqueue_scripts', array('Vitally_Responsible', 'enqueue_picturefill'));

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

/**
 * filter the_content if the options say we should
 *
 * priority of 11 ensures our filter is run after WordPress does it's formatting
 * WordPress chucks a load  of filters on the_content at default priority (of 10)
 * We get in there after so formatting is still applied and shortcodes are all expanded etc.
 * And then we can replace image content (if we need to) without worrying about our changes being overwritten
 * By an overzealous filter...
 * http://core.trac.wordpress.org/browser/tags/3.7.1/src/wp-includes/default-filters.php#L131
 */
$vital_options = get_option('vitally_responsible_options');

if( $vital_options['vital_filter_content'] === 'true' ){
    add_filter( 'the_content', array('Vitally_Responsible', 'make_content_responsible'), 11 );
}

//save filtered content on post update/save
add_action( 'save_post', array('Vitally_Responsible', 'save_content_responsibly') );

?>
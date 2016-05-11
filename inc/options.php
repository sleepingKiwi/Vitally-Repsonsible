<?php

add_action( 'admin_init', 'vitally_responsible_options_init' );
add_action( 'admin_menu', 'vitally_responsible_add_page' );


/**
 * Create a new page in the settings menu
 */
function vitally_responsible_add_page() {

    add_options_page( 'Vitally Responsible - Responsive Images', 'Responsive Images', 'manage_options', 'vitally-responsible', 'vitally_responsible_options_page' );

}

/**
 * Establish all of the settings to go on that page
 *
 * http://ottopress.com/2009/wordpress-settings-api-tutorial/
 * http://codex.wordpress.org/Function_Reference/add_settings_section
 * http://codex.wordpress.org/Function_Reference/add_settings_field
 */
function vitally_responsible_options_init() {

    register_setting( 'vitally_responsible_options', 'vitally_responsible_options', 'vitally_responsible_options_updated' );
    add_settings_section( 'vitally_responsible_section', '', 'vitally_responsible_options_content', 'vitally-responsible' );
    add_settings_field( 'vital_sizes', '', 'vital_option_sizes', 'vitally-responsible', 'vitally_responsible_section' );
    add_settings_field( 'vital_quality', '', 'vital_option_quality', 'vitally-responsible', 'vitally_responsible_section' );
    add_settings_field( 'vital_crops', '', 'vital_option_crops', 'vitally-responsible', 'vitally_responsible_section' );
    add_settings_field( 'vital_ignores', '', 'vital_option_ignores', 'vitally-responsible', 'vitally_responsible_section' );
    add_settings_field( 'vital_padding', '', 'vital_option_padding', 'vitally-responsible', 'vitally_responsible_section' );    
    add_settings_field( 'vital_pixelholder', '', 'vital_option_pixelholder', 'vitally-responsible', 'vitally_responsible_section' );
}


/**
 * callback to register_settings (meant for validation) - used to empty caches when options change!
 */
function vitally_responsible_options_updated($input){
    delete_post_meta_by_key( 'vitally_filtered_responsibly_less' );
    delete_post_meta_by_key( 'vitally_filtered_responsibly_more' );
    return $input;
}


/**
 * Additional content in the header of the 'section'
 *
 * This is the callback from add_settings_section above.
 */
function vitally_responsible_options_content(){
    ?>
    <h2 class="vr-header">Vitally Responsible - lazy loaded responsive images</h2>

    <p>Includes no css styling by default - that's left up to the theme.</p>

    <div id="code-warning" >
        <p><strong>For best results with this plugin:</strong></p>
        <p>For best results replace calls to <code>the_content();</code> in your theme files with this code:</p>
        <pre>if( class_exists('Vitally_Responsible') ){
     do_action( 'vitally_responsible_content');
}else{
    the_content();
}</pre>
    <p><small>This extra code won't break if you choose to disable the plugin and will revert to using the default <code>the_content</code> function.<br><br>Whilst the plugin is active it allows us to cache the filtered content, improving performance by preventing complex search/replaces running on every page load.<br><br>It also helps prevents potential conflicts with your theme and other plugins.</small></p>
    </div>

    <?php
}




function vital_option_sizes(){

    $options = get_option( 'vitally_responsible_options' );
    $value = $options['vital_sizes'];

    ?>

    <hr>

    <h3>String for the <em>sizes</em> attribute</h3>
    <p><strong>The full string used in the <em>sizes</em> attribute for the generated image tags.</strong></p>
    <p>For full info on the sizes attribute:<br>http://ericportis.com/posts/2014/srcset-sizes/<br>https://jakearchibald.com/2015/anatomy-of-responsive-images/</p>
    <p>The default value is: <code>(min-width: 81.25em) 1200px, (min-width: 30.1em) 90vw, 100vw</code>. This assumes:</p>
    <ol>
        <li>That under 480px (30em) wide, images are 100% width</li> 
        <li>That images above 480px are a max of 90% screen width</li>
        <li>That there is a maximum image width of 1200px and that it's reached by 1300px (81.25em)</li>
    </ol>

    <label for="vitally_responsible_options[vital_sizes]"><em>sizes</em> attribute string:</label>
    <input type="text" id='vital-sizes' name='vitally_responsible_options[vital_sizes]' value='<?php echo $value; ?>' />

    <?php

}

function vital_option_crops(){

    $options = get_option( 'vitally_responsible_options' );
    $value = $options['vital_crops'];

    ?>

    <hr>

    <h3>Set Image Crop Sizes</h3>
    <p><strong>A list of all cropped image sizes which will be picked by the browser based on the sizes above, pixel density, etc.</strong></p>
    <p><strong>If you want to support retinahigh pixel densities then include 1.5x/2x your maximum image width here...</strong></p>
    <p>An example of expected input could be <code>480,800,1200,1600,2400</code></p>

    <label for="vitally_responsible_options[vital_crops]">List Image Crop Widths</label>
    <input type="text" id='vital-crops' name='vitally_responsible_options[vital_crops]' value='<?php echo $value; ?>' />

    <?php

}

function vital_option_quality(){

    $options = get_option( 'vitally_responsible_options' );
    $value = $options['vital_quality'];

    ?>

    <hr>

    <h3>Set Quality of resized images</h3>
    <p><strong>Number from 1-100 indicating no compression (100) or full compression (1)</strong></p>

    <label for="vitally_responsible_options[vital_quality]">Set Quality</label>
    <input type="text" id='vital-quality' name='vitally_responsible_options[vital_quality]' value='<?php echo $value; ?>' />

    <?php

}

function vital_option_ignores(){

    $options = get_option( 'vitally_responsible_options' );
    $value = $options['vital_ignore'];

    ?>

    <hr>

    <h3>Add Classes to Ignore</h3>
    <p><strong>A list of classes (separated by commas) that will be left as <code>&lt;img&gt;</code> tags.</strong></p>
    <p>An example of expected input could be <code>thumbnail,vital-non-responsive</code></p>

    <input type="text" id='vital-ignores' name='vitally_responsible_options[vital_ignore]' value='<?php echo $value; ?>' />
   
    <?php

}


function vital_option_padding(){

    $options = get_option( 'vitally_responsible_options' );
    $value = $options['vital_padding'];

    ?>

    <hr>

    <h3>Add padding-bottom to Picturefill spans?</h3>
    <p><strong>Turn this setting on to have padding-bottom (in the picture ratio) automatically included as an inline style on Picturefill spans.</strong></p>
    <p>This option needs supporting css to function properly but alongside appropriate styles allows the picturefill elements to reserve space the same way normal img elements would.</p>
    <p><strong>If you haven't included appropriate css this option will break the site - all images will have extra padding. Don't enable this option unless you've included some appropriate css!</strong></p>
    <select id="vital-padding" name="vitally_responsible_options[vital_padding]">
      <option value="true" <?php if ( $value == 'true' ) echo 'selected'; ?>>On</option>
      <option value="false" <?php if ( $value == 'false') echo 'selected'; ?>>Off</option>
    </select>

    <?php

}

function vital_option_pixelholder(){

    $options = get_option( 'vitally_responsible_options' );
    $value = $options['vital_pixelholder'];

    ?>

    <hr>

    <h3>Add low res placeholder images?</h3>
    <p><strong>THIS WILL ADD CONSIDERABLY TO PAGE WEIGHT/NUMBER OF REQUESTS</strong></p>
    <p>Adds very low res placeholders if used alongside the padding option. Not styled by default. One day I'll move our generic styling in here...</p>
    <select id="vital-pixelholder" name="vitally_responsible_options[vital_pixelholder]">
      <option value="true" <?php if ( $value == 'true' ) echo 'selected'; ?>>On</option>
      <option value="false" <?php if ( $value == 'false') echo 'selected'; ?>>Off</option>
    </select>

    <?php

}

/**
 * Actually adding all of this to the page!
 */
function vitally_responsible_options_page(){
    ?>

    <div class="wrap vitally-responsible">

        <form action="options.php" method="post">
            <?php settings_fields( 'vitally_responsible_options' ); ?>
            <?php do_settings_sections( 'vitally-responsible' ); ?>
            <?php submit_button(); ?>
        </form>

        <p style="color:#777;font-size:11px;margin-top:20px">Many thanks to Noel Tock and <a href="https://github.com/noeltock/hammy" target="_blank">Hammy</a> for the inspiration for this plugin. Cropped images are generated using<a href="https://github.com/humanmade/WPThumb"  target="_blank">WPThumb</a></p>
   
    </div>

<?php } 

?>
<?php

add_action( 'admin_init', 'vitally_responsible_options_init' );
add_action( 'admin_menu', 'hammy_add_page' );


/**
 * Create a new page in the settings menu
 */
function hammy_add_page() {

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
    add_settings_field( 'vital_breakpoints', '', 'vital_option_breakpoints', 'vitally-responsible', 'vitally_responsible_section' );
    add_settings_field( 'vital_quality', '', 'vital_option_quality', 'vitally-responsible', 'vitally_responsible_section' );
    add_settings_field( 'vital_crops', '', 'vital_option_crops', 'vitally-responsible', 'vitally_responsible_section' );
    add_settings_field( 'vital_ignores', '', 'vital_option_ignores', 'vitally-responsible', 'vitally_responsible_section' );
    add_settings_field( 'vital_one_point_five', '', 'vital_option_one_point_five', 'vitally-responsible', 'vitally_responsible_section' );
    add_settings_field( 'vital_padding', '', 'vital_option_padding', 'vitally-responsible', 'vitally_responsible_section' );
    add_settings_field( 'vital_defer', '', 'vital_option_defer', 'vitally-responsible', 'vitally_responsible_section' );
    add_settings_field( 'vital_enqueue', '', 'vital_option_enqueue', 'vitally-responsible', 'vitally_responsible_section' );
    add_settings_field( 'vital_filter_content', '', 'vital_option_filter_content', 'vitally-responsible', 'vitally_responsible_section' );
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
    <h2 class="vr-header">Vitally Responsible - Responsive Images</h2>

    <p>Automatic responsive images for post and page content using version 1 of the <a href="https://github.com/scottjehl/picturefill" target="_blank">Picturefill</a> syntax.</p>

    <p>Includes no css styling by default - that's left up to the theme - but can optionally add padding-bottom to the Picturefill container to facilitate placeholders (if you choose to leave this feature enabled make sure that you have the correct styles in your theme).</p>

    <div id="code-warning">
        <p><strong>For best results with this plugin:</strong></p>
        <p>For best results replace calls to <code>the_content();</code> in your theme files with this code:</p>
        <pre>if( class_exists('Vitally_Responsible') ){
     do_action( 'vitally_responsible_content');
}else{
    the_content();
}</pre>
    <p>This extra code won't break if you choose to disable the plugin and will revert to using the default <code>the_content</code> function.<br>Whilst the plugin is active it allows us to cache the filtered content, improving performance by preventing complex search/replaces running on every page load.<br>It also helps prevents potential conflicts with your theme and other plugins.</p>
    </div>

    <!--<pre><span class="nt">&lt;span</span> <span class="na">data-picture</span> <span class="na">data-alt=</span><span class="s">"A giant stone face at The Bayon temple in Angkor Thom, Cambodia"</span><span class="nt">&gt;</span>
    <span class="nt">&lt;span</span> <span class="na">data-src=</span><span class="s">"small.jpg"</span><span class="nt">&gt;&lt;/span&gt;</span>
    <span class="nt">&lt;span</span> <span class="na">data-src=</span><span class="s">"medium.jpg"</span>     <span class="na">data-media=</span><span class="s">"(min-width: 400px)"</span><span class="nt">&gt;&lt;/span&gt;</span>
    <span class="nt">&lt;span</span> <span class="na">data-src=</span><span class="s">"large.jpg"</span>      <span class="na">data-media=</span><span class="s">"(min-width: 800px)"</span><span class="nt">&gt;&lt;/span&gt;</span>
    <span class="nt">&lt;span</span> <span class="na">data-src=</span><span class="s">"extralarge.jpg"</span> <span class="na">data-media=</span><span class="s">"(min-width: 1000px)"</span><span class="nt">&gt;&lt;/span&gt;</span>

    <span class="c">&lt;!-- Fallback content for non-JS browsers. Same img src as the initial, unqualified source element. --&gt;</span>
    <span class="nt">&lt;noscript&gt;</span>
        <span class="nt">&lt;img</span> <span class="na">src=</span><span class="s">"external/imgs/small.jpg"</span> <span class="na">alt=</span><span class="s">"A giant stone face at The Bayon temple in Angkor Thom, Cambodia"</span><span class="nt">&gt;</span>
    <span class="nt">&lt;/noscript&gt;</span>
<span class="nt">&lt;/span&gt;</span></pre>-->
    <?php
}




function vital_option_breakpoints(){

    $options = get_option( 'vitally_responsible_options' );
    $value = $options['vital_breaks'];

    ?>

    <hr>

    <h3>Set Breakpoint Sizes (min-widths)</h3>
    <p><strong>A list of all breakpoint sizes (minimum widths) for which you'd like a different image crop to load</strong></p>
    <ol>
        <li>A 0 breakpoint will automatically be included so your first value should be the first breakpoint above 0</li> 
        <li>For now all values will be treated as px widths so <strong>there's no need to specify a unit</strong></li>
        <li>Breakpoint values should be separated by a comma</li>
    </ol>
    <p>An example of expected input could be <code>400,800,1000</code></p>

    <label for="vitally_responsible_options[vital_breaks]">List Responsive Breakpoint Widths</label>
    <input id='vital-breakpoints' name='vitally_responsible_options[vital_breaks]' value='<?php echo $value; ?>' />

    <?php

}

function vital_option_crops(){

    $options = get_option( 'vitally_responsible_options' );
    $value = $options['vital_crops'];

    ?>

    <hr>

    <h3>Set Image Crop Sizes</h3>
    <p><strong>A list of all image sizes to be used at the different breakpoints above.</strong></p>
    <ol>
        <li>The first value is used as the default image size, until the width of the site is greater than the first breakpoint</li>
        <li>So there should be one more value in this list than the <strong>Breakpoint Sizes</strong> list above</li> 
        <li>For now all values will be treated as px widths so <strong>there's no need to specify a unit</strong></li>
        <li>Width values should be separated by a comma</li>
    </ol>
    <p>An example of expected input could be <code>380,700,1000,1300</code></p>

    <label for="vitally_responsible_options[vital_crops]">List Image Crop Widths</label>
    <input id='vital-crops' name='vitally_responsible_options[vital_crops]' value='<?php echo $value; ?>' />

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
    <input id='vital-quality' name='vitally_responsible_options[vital_quality]' value='<?php echo $value; ?>' />

    <?php

}

function vital_option_ignores(){

    $options = get_option( 'vitally_responsible_options' );
    $value = $options['vital_ignore'];

    ?>

    <hr>

    <h3>Add Classes to Ignore</h3>
    <p><strong>A list of classes (separated by commas) that will be left as <code>&lt;img&gt;</code> tags.</strong></p>
    <p>An example of expected input could be <code>thumbnail,non-responsive</code></p>

    <input id='vital-ignores' name='vitally_responsible_options[vital_ignore]' value='<?php echo $value; ?>' />
   
    <?php

}


function vital_option_one_point_five(){

    $options = get_option( 'vitally_responsible_options' );
    $value = $options['vital_one_point_five'];

    ?>

    <hr>

    <h3>Automatically Add 1.5x Scale Images On Retina Screens?</h3>
    <p>This setting will automatically add images at 1.5x your regular image sizes and display them on retina/hiDPI screens in the place of your normal images.</p><p>Non-retina devices will show your regular sized images and there will be no double downloads.</p>
    <select id="vital-one_point_five" name="vitally_responsible_options[vital_one_point_five]">
      <option value="true" <?php if ( $value == 'true' ) echo 'selected'; ?>>On</option>
      <option value="false" <?php if ( $value == 'false') echo 'selected'; ?>>Off</option>
    </select>

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



function vital_option_defer(){

    $options = get_option( 'vitally_responsible_options' );
    $value = $options['vital_defer'];

    ?>

    <hr>

    <h3>Add data-deferred class and attribute?</h3>
    <p><strong>Turn this setting on to have all picturefill spans generated with a <code>data-deferred</code> class and a <code>data-deferred</code> attribute.</strong></p>
    <p>This option effectively does nothing without supporting javascript/css but can be used alongside a modified version of the picturefill script and some additional lazyload logic to defer the loading of images.</p>
    <select id="vital-defer" name="vitally_responsible_options[vital_defer]">
      <option value="true" <?php if ( $value == 'true' ) echo 'selected'; ?>>On</option>
      <option value="false" <?php if ( $value == 'false') echo 'selected'; ?>>Off</option>
    </select>

    <?php

}




function vital_option_enqueue(){

    $options = get_option( 'vitally_responsible_options' );
    $value = $options['vital_enqueue'];

    ?>

    <hr>

    <h3>Automatically Enqueue Picturefill Javascript</h3>
    <p><strong>Turn this setting on if you do not want to manually include the js for picturefill in your theme files.</strong></p>
    <p>This option is turned off by default so that you can add the very short js from <a href="https://github.com/scottjehl/picturefill" target="_blank">https://github.com/scottjehl/picturefill</a> with your other js and save an http:// request, or include a version customised for your theme.</p>
    <p>If turned on this setting will automatically enqueue the javascript needed for loading responsive images</p>
    <select id="vital-enqueue" name="vitally_responsible_options[vital_enqueue]">
      <option value="true" <?php if ( $value == 'true' ) echo 'selected'; ?>>On</option>
      <option value="false" <?php if ( $value == 'false') echo 'selected'; ?>>Off</option>
    </select>

    <?php

}

function vital_option_filter_content(){

    $options = get_option( 'vitally_responsible_options' );
    $value = $options['vital_filter_content'];

    ?>

    <hr>

    <h3>Automatically Filter <code>the_content</code></h3>
    <p><strong>Turn this setting on if you do not want to manually edit your theme with the code at the top of this page.</strong><p>
    <p>This option is turned off by default as performance may be slightly better (and there is less risk of conflict with other plugins) if you can use the <a href="#code-warning">code</a> mentioned at the top of this page in your theme files.</p>
    <p>If turned on this setting will automatically filter the_content so will work without requiring any changes to theme files</p>
    <select id="vital-filter-content" name="vitally_responsible_options[vital_filter_content]">
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
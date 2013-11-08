**Vitally Responsible** is a WordPress plugin to automatically replace image tags in WordPress content with a version of the [picturefill](https://github.com/scottjehl/picturefill) syntax.

Owes a big debt to [Hammy](https://github.com/noeltock/hammy/blob/master/hammy.php) for providing inspiration and a lot of help with the filtering code.

It currently uses the [WPThumb](https://github.com/humanmade/WPThumb) class to handle the generation of new image sizes but a *todo* is to break that dependancy and bake in the much smaller subset of resizing/caching code to this plugin.

The plugin offers an alternative to the_content which saves filtered content to post meta-data for each post and loads this in place of `the_content` on the front end to avoid having to rewrite every `<img>` every time a page is generated. Alternatively it can automatically filter on the_content.

---

>**TODO:**

>* Break WPThumb dependancy and bake into plugin using core wp_image_editor functions
>* Pretty up the options page
>* FAQ, instructions, general user-friendliness...

---

== Installation ==

1. Upload this "vitally-responsible" directory to the "/wp-content/plugins/" directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Add some settings to the plugin page
4. ???????
5. Responsive Images

== Changelog ==

= 0.1 =

* Initial release.

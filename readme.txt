=== WP Counter Plugin ===
Contributors: your-wp-org-username
Tags: counter, shortcode, statistics, visitor counter, ajax
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight counter with an admin settings page and a [counter] shortcode. Increments over REST/AJAX, supports shortcode attributes, custom colours and a one-click reset.

== Description ==

WP Counter Plugin adds a simple, good looking counter to your site.

Admins get a settings screen under Settings → WP Counter where they can set the
counter title, the starting value, the step, an accent colour and whether the
"+" button is shown. Everywhere the `[counter]` shortcode is used, the value is
rendered on the page and visitors can increase it with one click - the value is
saved to the database and the page never reloads.

= Features =

* Settings page built with the Settings API (Settings → WP Counter)
* `[counter]` shortcode for posts, pages and text widgets
* Shortcode attributes: title, step, color, button
* AJAX/REST updates - no page reload when the counter changes
* Reset button with nonce verification
* Colour picker (wp-color-picker) for the accent colour
* Input validation with admin error messages (e.g. step must be 1 or more)
* Escaped output, sanitized input, capability checks, REST nonce verification
* Light rate limiting on the public endpoint
* `uninstall.php` removes all data when the plugin is deleted
* Translation ready (text domain: wp-counter-plugin) and mobile responsive

== Installation ==

1. In wp-admin go to Plugins → Add New Plugin → Upload Plugin.
2. Choose `wp-counter-plugin.zip` and press Install Now, then Activate.
3. Open Settings → WP Counter, adjust the title, starting value, step and
   colour, then press Save Changes.
4. Add `[counter]` to any post or page.

Alternatively, unzip the plugin and upload the `wp-counter-plugin` folder to
`/wp-content/plugins/`.

== Frequently Asked Questions ==

= Where is the counter value stored? =

In a single option (`wcp_counter_value`). Every shortcode shows the same value.

= Can each page have its own counter? =

Not in this version. The counter is global to the site.

= Does it work without JavaScript? =

Yes. The current value is rendered by WordPress. JavaScript only adds the
"increment without reloading" behaviour.

= What happens when I delete the plugin? =

`uninstall.php` deletes both options, so no data is left behind.

== Screenshots ==

1. The settings screen (title, starting value, step, colour, reset, shortcode help).
2. The counter on the frontend with the "+" button.

== Changelog ==

= 1.0.0 =
* Initial release: settings page, [counter] shortcode, REST/AJAX updates, reset, colour options.

== Upgrade Notice ==

= 1.0.0 =
First release.

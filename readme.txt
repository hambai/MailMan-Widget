=== MailMan Widget ===
Contributors: hm2k
Donate link: http://tinyurl.com/hm2kpaypal
Tags: mailman, widget, newsletter
Requires at least: 3.0.1
Tested up to: 3.2.1
Stable tag: trunk

Uses php-mailman to integrate the GNU Mailman mailing list manager with Wordpress.

== Description ==

This plugin provides an easy, lightweight way to let your users sign up for your MailMan list. You can use it to sign up users for 
several different lists by creating multiple instances of the widget. Once a user has subscribed, a cookie is stored on their machine to
prevent the subscription form for that particular list from displaying. Subscriptions for other lists will display.

The MailMan Widget:

*	is easy to use
*	is AJAX-enabled, but degrades gracefully if Javascript isn't turned on
*	encourages the collection of only information that you actually need (i.e., an email address) to send your mailers

If you find this plugin useful, please rate it and/or make a donation.

== Installation ==

1. Upload the "mailman_widget" directory to "/wp-content/plugins/" directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Enter a valid MailMan Admin URL on the plugin admin page ("Settings" >> "MailMan Widget").
4. Drag the widget into your sidebar from the "Widgets" menu in WordPress.
5. Select a mailing list and you're ready to go!
6. Please rate the plugin.

== Frequently Asked Questions ==

= I can't activate the plugin because it triggers this error: "Parse error: syntax error, unexpected '{' in .../wp-content/plugins/mailman-widget/mailman-widget.php on line 40." What's going on? =

Check your PHP version. You need at least PHP 5.1.2 to use this plugin.

= Is my admin password secure? =

Unfortunately, admin passwords must be stored in plain text in order to perform the subscribe action. Ideally MailMan would have an API which would not require use of the admin password to perform trivial actions.

== Screenshots ==

1. Just add your MailMan Admin URL.
2. Select your Widget Options.
3. The widget displays in your sidebar.

== Changelog ==

= 2.0 =
* Beta, based on the MailChimp Widget and the PHP-MailMan Class.

= 1.x =
* Alpha, based on the poorly executed, python powered wp-mailman plugin.

== Upgrade Notice ==

= 2.0 =
Now stable for public release.

== Credits ==

* Sponsored by [Phurix Web Hosting](http://www.phurix.co.uk/)
* Based on [MailChimp Widget](https://github.com/kalchas/MailChimp-Widget)
* Powered by [PHP MailMan](https://sourceforge.net/projects/php-mailman/)

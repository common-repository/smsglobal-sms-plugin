=== SMSGlobal SMS Plugin MKII ===
Contributors: smsglobal
Tags: sms, bulk sms, mobile, text message, plugin, SMSGlobal
Requires at least: 4.6.0
Requires PHP: 5.6
Tested up to: 6.1
Stable tag: 3.2.1
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin adds the ability to send SMS messages using powerful, secure and robust enterprise-grade APIs to ensure successful SMS deliveries.

== Description ==

SMSGlobal's SMS Plugin MKII enables WordPress store admins to configure automated SMS notifications. That means important order status updates are automatically sent to the administrator and customers if WooCommerce is installed. The plugin also offers great flexibility in sending individual SMS or bulk SMS messages to various groups and customers. The plugin is free but requires a SMSGlobal MXT account to send messages. Signing up with our service is free, and you only pay for the SMS messages.

SMSGlobal's SMS Plugin MKII allows you to reach people and not just their inbox. Since SMS guarantees a very high response rate, it is a very effective business communication tool to have in your customer communication strategy kit.

This WordPress plugin uses powerful, secure, and robust enterprise-grade APIs to ensure successful SMS deliveries.

= SMSGlobal Benefits =
* Competitive mass and bulk SMS pricing
* Wholesale pricing
* 99.9% On-net network redundancy
* 99.9% Uptime availability
* Enterprise scalability
* API flexibility
* No setup fees, no contracts, no catches

= Integration Compatibility =
* WordPress : <= 6.1
* WooCommerce : <= 5.0.0
* Ultimate Member : <=2.1.15

= Features =
* Easy configuration login to SMSGlobal to set the plugin settings
* Display user information and balance when the user is logged in
* Send custom SMS
* Schedule SMS
* SMS history to see the list of SMS sent
* Supports links

= WooCommerce Features =
* Configure the sender ID (from name) to either the post author or a custom sender ID for messages sent to the customer when an order is placed.
* Configure templates for customer and admin messages to be sent automatically when an order is **Pending**, **Processing**, **On Hold**, **Completed**, **Cancelled**, **Refunded**, **Failed**, and when a **new note is added to an order**.

= Ultimate Member Features =
* Configure a template for messages sent to **newly registered users** on your Wordpress site.

= How to get started =
To send SMS via SMS Plugin MKII, please follow these steps:

1. Create a SMSGlobal Account by visiting <a href="https://www.smsglobal.com">https://www.smsglobal.com</a>
1. Once your account is created, please top-up your account with credits.
1. Login to the SMSGlobal plugin login page with your MXT API key and secret. You can generate these inside your MXT account under the `API & Integrations` section.
1. You can now start sending SMS. You can do this by going to the **Send** page in the plugin, or automatically SMS your customers if you are using the WooCommerce or Ultimate Members integration.
1. Go to the **SMS Log** page to see the delivery status of every SMS that you have sent.

= Want to receive incoming messages? =
You may wish for customers to be able to respond to your SMS sent via WordPress. These responses can either be received via email or viewed on our web platform at <a href="https://mxt.smsglobal.com/report/sms-incoming">https://mxt.smsglobal.com/report/sms-incoming</a>. To forward SMS responses to your email, please visit our MXT Knowledge Base to learn how to set it up - <a href="https://mxt.smsglobal.com/support/article/897">https://mxt.smsglobal.com/support/article/897</a>


== Installation ==
1. Go to 'Plugins' in the admin menu
1. Search 'SMSGlobal' in the search bar
1. Select 'SMSGlobal SMS Plugin MKII' by SMSGlobal
1. Click 'Download,' and the zip file for the plugin should download
1. Go to your WordPress admin interface
1. Select 'Plugins' > 'Add New' > 'Upload Plugin' > 'Choose File'
1. Upload the zip file of the plugin
1. Activate the plugin after installation
1. SMSGlobal SMS Plugin MKII should now appear in your admin menu

== Frequently Asked Questions ==
= Do I need an SMSGlobal account to send SMS? =
Yes, you must register for an account with SMSGlobal to send any SMS using this plugin (<a href="http://smsglobal.com/sign-up/" target="smsglobal">http://smsglobal.com/sign-up/</a>).

= Can I send SMS to multiple numbers at once? =
Yes, SMSGlobal allows you to input as many numbers as you want. Simply, use a comma to separate each number.

= What's the correct number format?
Although various number formats can be recognized, the most reliable format is 'country code + phone number.'
For example, +614xxxxxxxx for an Australian phone number

= Can I send Unicode?
Yes, but the message length may differ based on your chosen language and, therefore, could result in a multi-part message.

== Screenshots ==
1. Login page
2. Send message page
3. Templates configuration page
4. Select where to send order notifications page

== Changelog ==

= 3.2.1 =
* Increased compatibility for Wordpress through to 5.6
* Increased compatibility for WooCommerce through to 5.0

= 3.2.0 =
* Added new "SMS Sent From" field to allow you to include a custom sender in your notification SMS

= 3.1.0 =
* Security improvements - Removed login form to instead use API keys

= 3.0.3 =
* Minor fixes

= 3.0.2 =
* Minor bug fixes and changes

= 3.0.1 =
* Minor bug fix

= 3.0.0 =
* Increased compatibility for Wordpress through to 5.4
* Added log for SMS sent
* Introduced support for Woocommerce plugin
* Introduced support for Ultimate Members plugin

= 2.0.1 =
* `readme.txt` file fixes.

= 2.0 =
* Re-written from the ground up using new REST API v2
* Improved send message page
* New SMS report page

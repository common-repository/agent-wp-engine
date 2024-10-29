=== Agent WP Engine ===
Contributors: asmartbear, wpengine
Donate link: http://portal.wpengine.com
Tags: admin, reporting, monitoring, alerting
Requires at least: 3.1
Tested up to: 3.2.1
Stable tag: trunk

Connectivity to our amazing, free WordPress management portal, providing single sign-on, site health checks, and other tools.

== Description ==

Manage all your WordPress installations from one place.

1. **Never type another username and password again.** Just click to log in. Yes, it's secure; we don't even store passwords.
1. **Check site health in 2 seconds.** Is it up? Fast? Have malware? Domain issues? Latest version of WordPress? Get reports with one click.
1. **Free. Forever.** Don't worry, no bait and switch. Just enjoy. Consider [hosting WordPress with us](http://wpengine.com/), but no pressure.

Install this agent plugin to power blogs on the portal. Managing multiple WordPress installations just got a lot easier!

== Installation ==

1. Install this plugin through the WordPress Plugin Repository, or [download it here](http://downloads.wordpress.org/plugin/agent-wp-engine.zip)
and install it manually by clicking "`Plugins`" from the sidebar, then "`Add New`" from near the top of the screen, then switch to the "`Upload`" tab, select the ZIP file you just downloaded, and submit that.
1. At the bottom of the resulting screen click "`Activate Plugin`".
1. Visit [our free management portal](http://portal.wpengine.com) and sign in to get your Account ID and API token.
1. Back on your blog, navigate to this plugin's configuration page by expanding the "`Dashboard`" section in the sidebar, then click "`Agent WP Engine`".
1. Paste in your Account ID and API token which is displayed by our Portal.  Save that form.
1. Visit [the portal](http://portal.wpengine.com)  again and you'll see your blog there!
1. **Repeat** with all your blogs.

== Frequently Asked Questions ==

= How secure is the Single Sign-on feature? =

Very. We don't even store your password, as you can tell (because you never have to type in your password).

We use security tokens for both your Account ID and API Token which are too large to guess or brute-force.  We also do all communication over SSL (i.e. https) so no one can snoop in on the requests that go between the plugin and the portal.

= Does this work for all hosting providers? =

Yes.  Although (full disclosure) we at WP Engine are ourselves a [WordPress hosting provider](http://wpengine.com), and we'd love your business, this plugin and our portal are specifically designed to work with any WordPress installation on any hosting provider.

If you experience a bug, we'll fix it even if it's the "fault" of your hosting provider.  We won't point fingers, we just want to get things fixed.

We believe in karma.  We believe that if we create a truly useful product, you'll want to use the portal every day, and then you'll remember us for those times when we do happen to be the best hosting choice for you.

== Changelog ==

= 0.22 =
* Disable the agent when running under WP Engine Staging environment
* Fixed issue where user list is empty

= 0.21 =
* Limited "log in as" user list to only those users with "administrator" access.

= 0.20 =
* Fixed SSO for Multisite.

= 0.18 =
* Reporting site_url() and home_url() separately, so we can construct the correct URL to the blog in the portal.

= 0.17 =
* Remove requirement of get_plugin_data() which sometimes isn't available in WordPress.

= 0.16 =
* Remove dependency on gethostname() which isn't supported by old PHP installations.

= 0.15 =
* Improved authentication system for Insta-Login function.

= 0.14 =
* Reporting back enough information to identify your hosting company
* Error messages about server connectivity or PHP errors from the Portal API

= 0.10 =
* Initial public release

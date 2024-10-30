=== Link Finder ===

Contributors: bvandevliet
Tags: links, hyperlinks, 404, permalinks, maintenance
Requires at least: 4.6
Tested up to: 5.9
Requires PHP: 7.2
Stable tag: 2022.01.05
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl.html

Find and repair broken links throughout your website.


== Description ==

<p>
  No more broken links. Speed up your website by avoiding redirects and improve your search engine ranking. Link Finder is a very simple and lightweight plugin to easily find and repair broken links throughout your website.
</p>

<p>
  When you navigate to `Tools >> Link Finder` in the admin panel, Link Finder will parse the links in the content of all your posts and pages. Depending on the size of your website and the amount of links, this could take a moment. It extracts the links it finds in all html `href` and `src` attributes and attempts to follow them. All redirected links and broken links will be listed, for example those that result in a 404 Page Not Found error. From within this list, you can easily make corrections and then repair all optimizable and broken links in one simple click.
</p>

<p>
  To not affect the speed of your website, Link Finder does not perform active monitoring. It also does not store any additional data to the database. Use it as a tool to manually check for broken links regularly or after changing permalinks or moving your website to another domain.
</p>


== Installation ==

= Manually via FTP =

1. Download the plugin.
1. Unpack the .zip
1. Upload the `linkfinder` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin from within your WP Admin.

= Manually via WP Admin =

1. Download the plugin.
1. Goto `Plugins` > `Add New` > `Upload Plugin`.
1. Upload the .zip
1. Activate the plugin.

= Automatic via WP Admin =

1. Goto `Plugins` > `Add New`.
1. Search for `linkfinder`.
1. Find the plugin and click `Install Now`.
1. Activate the plugin.


== Changelog ==

= 2022.01.05 =
* Added feature to sort the table.

= 2021.10.29 =
* Replaced some Javascript functions to also fully support Internet Explorer (>=5.5).

= 2021.10.14 =
* Added filter for errors, warnings and other to improve usability on large websites.

= 2021.10.12 =
* Javascript bugfixes and improved error handling.
* Style update.

= 2021.09.17 =
* Ran codebase through wpcs with phpcs.

= 2020.12.22 =
* Updated Javascript to ES6 standards.

= 2020.06.15 =
* Added the website's home url as referer to the request headers.

= 2020.06.11 =
* First introduction.


== Upgrade Notice ==

= 2021.10.12 =
This is a major update and fixes some critical issues.

=== Tor Router ===
Contributors: OnionBazaar
Donate link: https://onionbazaar.org/?p=donation
Tags: tor, proxy, socks, connection, proxies, firewall, block
Requires at least: 2.8
Tested up to: 5.7
Stable tag: 1.4.1
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Routes outgoing traffic through Tor or any HTTP / SOCKS Proxy.

== Description ==

Tor Router directs all connections through a HTTP / SOCKS Proxy. Use this plugin if your network requires a Proxy for outgoing traffic, or if you want to anonymize it via Tor or external Proxy. 

A built-in Firewall allows you to block all outgoing traffic and define exceptions for allowed URLs or Plugins.

To check if the routing works properly, there is a button "Check IP" in the Tor Router settings. This connects to https://icanhazip.com to display your external IP.

If you set up a cron job for the tor-router.sh script, you can activate the Tor KeepAlive feature, which checks the connection once per hour and restarts the Tor Service on failure. It also allows to restart it manually from the Tor Router settings.

For support, head over to the [WordPress Support Forum](https://wordpress.org/support/plugin/tor-router) or [https://onionbazaar.org/?p=help](https://onionbazaar.org/?p=help) for direct support.

== Installation ==

1. Upload the entire `/tor-router` directory to the `/wp-content/plugins/` directory.
2. Activate Tor Router through the 'Plugins' menu in WordPress.
3. Open `Settings` -> `Tor Router` to setup the plugin.
4. To route through Tor you need to have it installed on your server (e.g. `apt-get install tor`), use SOCKS Proxy Mode in the Tor Router settings, and set Proxy Host to `localhost` and Proxy Port to `9050` (default)
5. To enable the Tor KeepAlive feature and the Restart Tor button, set up a cron job to run the script `tor-router.sh` every minute (as root), e.g:
sudo nano /etc/crontab
* * * * * root bash /var/www/html/wp-content/plugins/tor-router/tor-router.sh

== Screenshots ==

1. View of Tor Router Settings

== Changelog ==

= 1.4.1 - 2021-03-05 =
* Compatibility to WordPress 5.7  

= 1.4.0 - 2020-07-25 =
* Added support for all proxy types, firewall exceptions per plugin, Tor KeepAlive feature and Tor Restart button  

= 1.3.0 - 2020-05-19 =
* Added SOCKS5 DNS, required to resolve onion domains

= 1.2.1 - 2020-04-25 =
* Bugfix Firewall

= 1.2.0 - 2020-04-22 =
* Added Firewall to block outgoing connections

= 1.1.0 - 2020-04-15 =
* Added SOCKS functionality

= 1.0.0 - 2020-03-25 =
* Initial release. The management and development of the plugin is done by [OnionBazaar](https://onionbazaar.org)

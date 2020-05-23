<?php
/*
Plugin Name: Tor Router
Plugin URI: https://wordpress.org/plugins/tor-router/
Description: Routes outgoing traffic through Tor or any HTTP / SOCKS Proxy.
Version: 1.3.0
Author: OnionBazaar
Author URI: http://onionbazaar.org
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Text Domain: tor-router
*/

function obztorrouter_load_plugin_textdomain() {
    load_plugin_textdomain( 'tor-router' );
}
add_action( 'plugins_loaded', 'obztorrouter_load_plugin_textdomain' );

function obztorrouter_settings_link( $links )
{
	$_link = '<a href="options-general.php?page=obztorrouter">'.esc_html__( 'Settings', 'tor-router' ).'</a>';
	$links[] = $_link;
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'obztorrouter_settings_link' );
 
function obztorrouter_register_settings() {
	add_option( 'obztorrouter_enabled', '1');
	add_option( 'obztorrouter_host', '127.0.0.1');
	add_option( 'obztorrouter_port', '9050');
	add_option( 'obztorrouter_socks', '1');
	add_option( 'obztorrouter_socksdns', '1');
	add_option( 'obztorrouter_block_connections', '0');
	add_option( 'obztorrouter_block_connections_exceptions', 'https://api.wordpress.org' . "\n" . 'https://icanhazip.com' );
	register_setting( 'obztorrouter_options_group', 'obztorrouter_enabled' );
	register_setting( 'obztorrouter_options_group', 'obztorrouter_host' );
	register_setting( 'obztorrouter_options_group', 'obztorrouter_port' );
	register_setting( 'obztorrouter_options_group', 'obztorrouter_socks' );
	register_setting( 'obztorrouter_options_group', 'obztorrouter_socksdns' );
	register_setting( 'obztorrouter_options_group', 'obztorrouter_block_connections');
	register_setting( 'obztorrouter_options_group', 'obztorrouter_block_connections_exceptions');
}
add_action( 'admin_init', 'obztorrouter_register_settings' );

function obztorrouter_register_options_page() {
  add_options_page('Tor Router', 'Tor Router', 'manage_options', 'obztorrouter', 'obztorrouter_options_page');
}
add_action('admin_menu', 'obztorrouter_register_options_page');

function obztorrouter_options_page()
{
	if (isset($_POST['obztorrouter_checkip']))
	{
		$obztorrouter_request = 'https://icanhazip.com';
		$obztorrouter_response = wp_remote_get($obztorrouter_request);
		if (is_wp_error($obztorrouter_response) || $obztorrouter_response['response']['code'] !== 200) {
			$obztorrouter_checkedtext = '&nbsp; '.esc_html__( 'Connection Error', 'tor-router' );
		}
		else
		{
			$obztorrouter_checkedtext = '&nbsp; '.$obztorrouter_response['body'];
		}
	}
	else
	{
		$obztorrouter_checkedtext = null;
	}
	$obztorrouter_snitch_installed = false;
	$obztorrouter_plugins_list = get_plugins();
	foreach ($obztorrouter_plugins_list as $key => $value) {
		if ( $key == 'snitch/snitch.php' ) $obztorrouter_snitch_installed = true;
	}
	if ( $obztorrouter_snitch_installed ) {
		if ( is_plugin_active( 'snitch/snitch.php' ) ) {
			$obztorrouter_snitch_link = sprintf(
				esc_html__( 'See connection log in the %1$sSnitch plugin%2$s', 'tor-router' ),
				'<a href="edit.php?post_type=snitch">',
				'</a>'
				);
		}
		else
		{
			$obztorrouter_snitch_link = sprintf(
				esc_html__( 'See connection log in the Snitch plugin (%1$snot enabled%2$s)', 'tor-router' ),
				'<a href="plugins.php">',
				'</a>'
				);
		}
	}
	else
	{
		$obztorrouter_snitch_link = sprintf(
				esc_html__( 'Get the %1$sSnitch plugin%2$s to see connection log', 'tor-router' ),
				'<a href="https://wordpress.org/plugins/snitch" target="_blank">',
				'</a>'
				);
	}
	echo '<div><br><h1>'.esc_html__( 'Tor Router', 'tor-router' ).'</h1>
	<br>
	<p>'.esc_html__( 'Route all outgoing connections through Tor or any HTTP / SOCKS Proxy.', 'tor-router' ).'</p>
	<br>
	<form method="post" action="options.php">';
	settings_fields( 'obztorrouter_options_group' );
	echo '
	<table width="550">
	<tr>
	<td width="150" valign="top">'.esc_html__( 'Enable Routing', 'tor-router' ).'</td>
	<td width="400" ><input type="checkbox" name="obztorrouter_enabled" id="obztorrouter_enabled" value="1" ';
	checked('1', get_option('obztorrouter_enabled'));
	echo '/> <label for="obztorrouter_enabled">'.esc_html__( 'Route traffic through the following Proxy', 'tor-router' ).'</label>
	</td>
	</tr>
	<tr><td colspan="2"><br></td></tr>
	<tr>
	<td width="150" valign="top">'.esc_html__( 'SOCKS', 'tor-router' ).'</td>
	<td width="400" ><input type="checkbox" name="obztorrouter_socks" id="obztorrouter_socks" value="1" ';
	checked('1', get_option('obztorrouter_socks'));
	echo '/> <label for="obztorrouter_socks">'.esc_html__( 'Use SOCKS instead of HTTP Proxy', 'tor-router' ).'</label>
	</td>
	</tr>
	<tr><td colspan="2"><br></td></tr>
	<tr>
	<td width="150" valign="top">'.esc_html__( 'SOCKS DNS', 'tor-router' ).'</td>
	<td width="400" ><input type="checkbox" name="obztorrouter_socksdns" id="obztorrouter_socksdns" value="1" ';
	checked('1', get_option('obztorrouter_socksdns'));
	echo '/> <label for="obztorrouter_socksdns">'.esc_html__( 'Resolve Hostnames via SOCKS5', 'tor-router' ).'</label>
	</td>
	</tr>
	<tr><td colspan="2"><br></td></tr>
	<tr>
	<td valign="top">'.esc_html__( 'Proxy Host', 'tor-router' ).'</td>
	<td><input type="text" name="obztorrouter_host" id="obztorrouter_host" style=" width: 100%;" value="'.get_option('obztorrouter_host').'">
	</td>
	</tr>
	<tr><td colspan="2"><br></td></tr>
	<tr>
	<td valign="top">'.esc_html__( 'Proxy Port', 'tor-router' ).'</td>
	<td><input type="text" name="obztorrouter_port" id="obztorrouter_port" style=" width: 100%;" value="'.get_option('obztorrouter_port').'">
	</td>
	</tr>
	<tr><td colspan="2"><br><br></td></tr>
	<tr>
	<td valign="top">'.esc_html__( 'Enable Firewall', 'tor-router' ).'</td>
	<td><input type="checkbox" name="obztorrouter_block_connections" id="obztorrouter_block_connections" value="1" ';
	checked('1', get_option('obztorrouter_block_connections'));
	echo '/> <label for="obztorrouter_block_connections">'.esc_html__( 'Block outbound connections', 'tor-router' ).'</label>
	<br><br>'.esc_html__( 'Exceptions:', 'tor-router' ).'<br>
	<textarea name="obztorrouter_block_connections_exceptions" id="obztorrouter_block_connections_exceptions" style=" width: 100%; height: 200px;">'.get_option('obztorrouter_block_connections_exceptions').'</textarea>
	</td>
	</tr>
	<tr><td colspan="2"><br><br></td></tr>
	<tr>
	<td valign="top">'.esc_html__( 'Monitor', 'tor-router' ).'</td>
	<td>'.$obztorrouter_snitch_link.'
	</td>
	</tr>
	</table>';
	submit_button();
	echo '</form>
	<a name="checkip"></a>
	<form method="post" name="obztorrouter_checkip" action="#checkip">
	<table width="550">
	<tr>
	<td>
	<input type="submit" class="button button-primary" name="obztorrouter_checkip" value="'.esc_attr__( 'Check IP', 'tor-router' ).'"/> <span style="font-size: 18px; vertical-align: -7px;">'.$obztorrouter_checkedtext.'</span>
	<p class="description">'.esc_html__( 'retrieves your IP from icanhazip.com', 'tor-router' ).'</p></td>
	</tr>
	</table>
	</form>
	</div>';
} 

if (get_option('obztorrouter_enabled')=='1') {
	if ( ( !defined( 'WP_PROXY_HOST' ) ) AND ( !defined( 'WP_PROXY_PORT' ) ) ) {
		if (get_option('obztorrouter_socks')=='1') {
			$obztorrouter_host = 'socks://'.get_option('obztorrouter_host');
		}
		else {
			$obztorrouter_host = get_option('obztorrouter_host');
		}
		define('WP_PROXY_HOST', $obztorrouter_host);
		define('WP_PROXY_PORT', get_option('obztorrouter_port'));
	}
	if (get_option('obztorrouter_socksdns')=='1') {
		add_action('http_api_curl', function( $handle ) {
			curl_setopt($handle, CURLOPT_PROXY, get_option('obztorrouter_host'));
			curl_setopt($handle, CURLOPT_PROXYPORT, get_option('obztorrouter_port'));
			curl_setopt($handle, CURLOPT_PROXYTYPE, 7);
		}, 10);
	}
}

if (get_option('obztorrouter_block_connections')=='1') {
	add_filter(
		'pre_http_request',
		'obztorrouter_inspect_request',
		10,
		3
	);
}

function obztorrouter_inspect_request($pre, $args, $url)
{
	/* Empty url */
	if ( empty($url) ) {
		return $pre;
	}

	/* Invalid host */
	if ( ! $host = parse_url($url, PHP_URL_HOST) ) {
		return $pre;
	}

	/* Check for internal requests */
	if ( defined('SNITCH_IGNORE_INTERNAL_REQUESTS') && SNITCH_IGNORE_INTERNAL_REQUESTS && obztorrouter_is_internal($host) ) {
		return $pre;
	}

	/* Timer start */
	timer_start();

	/* Backtrace data */
	$backtrace = obztorrouter_debug_backtrace();

	/* No reference file found */
	if ( empty($backtrace['file']) ) {
		return $pre;
	}

	/* Show your face, file */
	$meta = obztorrouter_face_detect($backtrace['file']);

	/* Init data */
	$file = str_replace(ABSPATH, '', $backtrace['file']);
	$line = (int)$backtrace['line'];

	$obz_allow = false;
	$obz_exceptions = get_option('obztorrouter_block_connections_exceptions');
	$obz_exceptions = $obz_exceptions . "\n" . get_site_url();
	foreach(preg_split("/((\r?\n)|(\r\n?))/", $obz_exceptions) as $line) {
		if ((substr($url, 0, strlen($line))==$line) AND (trim($line)!='')) $obz_allow = true; }

	if ($obz_allow)	{
		return $pre;
	} else { 
		return obztorrouter_insert_post(
			(array)apply_filters(
				'snitch_inspect_request_insert_post',
				array(
					'url'      => esc_url_raw($url),
					'code'     => NULL,
					'host'     => $host,
					'file'     => $file,
					'line'     => $line,
					'meta'     => $meta,
					'state'    => 1,
					'postdata' => obztorrouter_get_postdata($args)
				)
			)
		);
	}
}

function obztorrouter_debug_backtrace() 
{
	/* Reverse items */
	$trace = array_reverse(debug_backtrace());

	/* Loop items */
	foreach( $trace as $index => $item ) {
		if ( ! empty($item['function']) && strpos($item['function'], 'wp_remote_') !== false ) {
			/* Use prev item */
			if ( empty($item['file']) ) {
				$item = $trace[-- $index];
			}

			/* Get file and line */
			if ( ! empty($item['file']) && ! empty($item['line']) ) {
				return $item;
			}
		}
	}
}
	
function obztorrouter_face_detect($path)
{
	/* Default */
	$meta = array(
		'type' => 'WordPress',
		'name' => 'Core'
	);

	/* Empty path */
	if ( empty($path) ) {
		return $meta;
	}

	/* Search for plugin */
	if ( $data = obztorrouter_localize_plugin($path) ) {
		return array(
			'type' => 'Plugin',
			'name' => $data['Name']
		);

	/* Search for theme */
	} else if ( $data = obztorrouter_localize_theme($path) ) {
		return array(
			'type' => 'Theme',
			'name' => $data->get('Name')
		);
	}

	return $meta;
}

function obztorrouter_insert_post($meta)
{
	/* Empty? */
	if ( empty($meta) ) {
		return;
	}

	/* Create post */
	$post_id = wp_insert_post(
		array(
			'post_status' => 'publish',
			'post_type'   => 'snitch'
		)
	);

	/* Add meta values */
	foreach($meta as $key => $value) {
		add_post_meta(
			$post_id,
			'_snitch_' .$key,
			$value,
			true
		);
	}

	return $post_id;
}

function obztorrouter_localize_plugin($path)
{
	/* Check path */
	if ( strpos($path, WP_PLUGIN_DIR) === false ) {
		return false;
	}

	/* Reduce path */
	$path = ltrim(
		str_replace(WP_PLUGIN_DIR, '', $path),
		DIRECTORY_SEPARATOR
	);

	/* Get plugin folder */
	$folder = substr(
		$path,
		0,
		strpos($path, DIRECTORY_SEPARATOR)
	) . DIRECTORY_SEPARATOR;

	/* Frontend */
	if ( ! function_exists('get_plugins') ) {
		require_once(ABSPATH. 'wp-admin/includes/plugin.php');
	}

	/* All active plugins */
	$plugins = get_plugins();

	/* Loop plugins */
	foreach( $plugins as $path => $plugin ) {
		if ( strpos($path, $folder) === 0 ) {
			return $plugin;
		}
	}
}

function obztorrouter_localize_theme($path)
{
	/* Check path */
	if ( strpos($path, get_theme_root()) === false ) {
		return false;
	}

	/* Reduce path */
	$path = ltrim(
		str_replace(get_theme_root(), '', $path),
		DIRECTORY_SEPARATOR
	);

	/* Get theme folder */
	$folder = substr(
		$path,
		0,
		strpos($path, DIRECTORY_SEPARATOR)
	);

	/* Get theme */
	$theme = wp_get_theme($folder);

	/* Check & return theme */
	if ( $theme->exists() ) {
		return $theme;
	}

	return false;
}

function obztorrouter_get_postdata($args)
{
	/* No POST data? */
	if ( empty($args['method']) OR $args['method'] !== 'POST' ) {
		return NULL;
	}

	/* No body data? */
	if ( empty($args['body']) ) {
		return NULL;
	}

	return $args['body'];
}

function obztorrouter_is_internal($host) 
{
	/* Get the blog host */
	$blog_host = parse_url(
		get_bloginfo('url'),
		PHP_URL_HOST
	);

	return ( $blog_host === $host );
}

?>

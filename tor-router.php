<?php
/*
Plugin Name: Tor Router
Plugin URI: https://wordpress.org/plugins/tor-router/
Description: Routes outgoing traffic through Tor or any HTTP / SOCKS Proxy.
Version: 1.4.1
Author: OnionBazaar
Author URI: http://onionbazaar.org
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Text Domain: tor-router
*/

define( 'TORROUTER_VERSION', '1.4.1' );

function torrouter_upgrade130to140() {
	add_option( 'torrouter_version', TORROUTER_VERSION );
	if (get_option( 'obztorrouter_enabled' ) == '1' ) {
		if (get_option( 'obztorrouter_socksdns' ) == '1' ) {
			add_option( 'torrouter_proxymode', '7' ); }
		else {
			if (get_option( 'obztorrouter_socks' ) == '1' ) {
				add_option( 'torrouter_proxymode', '4' ); }
			else {
				add_option( 'torrouter_proxymode', '1' ); }
		}
	}
	else {
		add_option( 'torrouter_proxymode', '0' ); }
	
	add_option( 'torrouter_host', get_option( 'obztorrouter_host' ) );
	add_option( 'torrouter_port', get_option( 'obztorrouter_port' ) );
	
	if (get_option( 'obztorrouter_block_connections' )=='1' ) {
		add_option( 'torrouter_firewall', '1' ); }
	else {
		add_option( 'torrouter_firewall', '' ); }
	$obztorrouter_firewall_exceptions_urls = array();
	$obzs_exceptions = get_option( 'obztorrouter_block_connections_exceptions' );
	foreach(preg_split("/((\r?\n)|(\r\n?))/", $obzs_exceptions) as $line) {
		if (trim($line)!='' ) array_push($obztorrouter_firewall_exceptions_urls, trim($line)); }
	add_option( 'torrouter_firewall_exceptions_urls', $obztorrouter_firewall_exceptions_urls );
	delete_option( 'obztorrouter_enabled' );
	delete_option( 'obztorrouter_host' );
	delete_option( 'obztorrouter_port' );
	delete_option( 'obztorrouter_socks' );
	delete_option( 'obztorrouter_socksdns' );
	delete_option( 'obztorrouter_block_connections' );
	delete_option( 'obztorrouter_block_connections_exceptions' );
}

if ( get_option( 'obztorrouter_host' ) ) {
	add_action( 'admin_init', 'torrouter_upgrade130to140', 5 );
}

function torrouter_load_plugin_textdomain() {
    load_plugin_textdomain( 'tor-router' );
}
add_action( 'plugins_loaded', 'torrouter_load_plugin_textdomain' );

function torrouter_settings_link( $links )
{
	$_link = '<a href="options-general.php?page=torrouter">' . esc_html__( 'Settings', 'tor-router' ) . '</a>';
	$links[] = $_link;
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'torrouter_settings_link' );
 
function torrouter_register_settings() {
	$TORROUTER_VERSION = get_option( 'torrouter_version' );
	if ( version_compare( $TORROUTER_VERSION, TORROUTER_VERSION, '<' ) ) {
		update_option( 'torrouter_version', TORROUTER_VERSION );
	}

	add_option( 'torrouter_proxymode', '7' );
	add_option( 'torrouter_host', 'localhost' );
	add_option( 'torrouter_port', '9050' );
	add_option( 'torrouter_firewall', '' );
	$torrouter_firewall_exceptions_urls = array(
		'https://api.wordpress.org',
		'https://icanhazip.com'
	);
	add_option( 'torrouter_firewall_exceptions_urls', $torrouter_firewall_exceptions_urls );
	$torrouter_firewall_exceptions_plugins = array();
	add_option( 'torrouter_firewall_exceptions_plugins', $torrouter_firewall_exceptions_plugins );
	add_option( 'torrouter_torkeepalive', '' );
	register_setting( 'torrouter_options_group', 'torrouter_proxymode' );
	register_setting( 'torrouter_options_group', 'torrouter_host' );
	register_setting( 'torrouter_options_group', 'torrouter_port' );
	register_setting( 'torrouter_options_group', 'torrouter_firewall' );
	register_setting( 'torrouter_options_group', 'torrouter_firewall_exceptions_urls', 'torrouter_firewall_exceptions_urls_to_array' );
	register_setting( 'torrouter_options_group', 'torrouter_firewall_exceptions_plugins' );
	register_setting( 'torrouter_options_group', 'torrouter_torkeepalive' );
}
add_action( 'admin_init', 'torrouter_register_settings' );

function torrouter_firewall_exceptions_urls_to_array( $input ) {
	return preg_split( "/\r\n|\n|\r/", $input );
}

function torrouter_register_options_page() {
  add_options_page( 'Tor Router', 'Tor Router', 'manage_options', 'torrouter', 'torrouter_options_page' );
}
add_action( 'admin_menu', 'torrouter_register_options_page' );

function torrouter_options_page()
{
	if ( isset( $_POST['torrouter_checkip'] ) && isset( $_POST['torrouter_checkip_nonce'] ) && wp_verify_nonce( $_POST['torrouter_checkip_nonce'], 'torrouter_checkip_action' ) ) {
		$torrouter_request = 'https://icanhazip.com';
		$torrouter_response = wp_remote_get( $torrouter_request );

		if ( is_wp_error( $torrouter_response ) ) {
			$torrouter_checkedtext = '&nbsp; ' . esc_html__( 'Connection Error', 'tor-router' );
		}
		else {
			if ( 200 == (int) wp_remote_retrieve_response_code( $torrouter_response ) ) {
				$torrouter_checkedtext = '&nbsp; ' . wp_remote_retrieve_body( $torrouter_response );
			}
			else {
				$torrouter_checkedtext = '&nbsp; ' . esc_html__( 'Connection Refused', 'tor-router' );
			}
		}
	}
	else {
		$torrouter_checkedtext = null;
	}

	if ( isset( $_POST['torrouter_restart_tor'] ) && isset( $_POST['torrouter_restart_tor_nonce'] ) && wp_verify_nonce( $_POST['torrouter_restart_tor_nonce'], 'torrouter_restart_tor_action' ) ) {
		if ( is_writable( WP_CONTENT_DIR . '/tor-router-cron' ) ) {
			file_put_contents( WP_CONTENT_DIR . '/tor-router-cron', 'restart' );
			$torrouter_restarttext = '&nbsp; ' . esc_html__( 'Tor restart triggered. Wait up to 1 minute.', 'tor-router' );
		}
		else {
			$torrouter_restarttext = '&nbsp; ' . WP_CONTENT_DIR . '/tor-router-cron ' . esc_html__( 'not writable.', 'tor-router' );
		}
	
	}
	else {
		$torrouter_restarttext = null;
	}

	$torrouter_snitch_installed = false;
	$torrouter_plugins_list = get_plugins();
	foreach ($torrouter_plugins_list as $key => $value) {
		if ( $key == 'snitch/snitch.php' ) $torrouter_snitch_installed = true;
	}
	if ( $torrouter_snitch_installed ) {
		if ( is_plugin_active( 'snitch/snitch.php' ) ) {
			$torrouter_snitch_link = sprintf(
				esc_html__( 'See connection log in the %1$sSnitch plugin%2$s', 'tor-router' ),
				'<a href="edit.php?post_type=snitch">',
				'</a>'
				);
		}
		else {
			$torrouter_snitch_link = sprintf(
				esc_html__( 'See connection log in the Snitch plugin (%1$snot enabled%2$s)', 'tor-router' ),
				'<a href="plugins.php">',
				'</a>'
				);
		}
	}
	else {
		$torrouter_snitch_link = sprintf(
				esc_html__( 'Get the %1$sSnitch plugin%2$s to see connection log', 'tor-router' ),
				'<a href="https://wordpress.org/plugins/snitch" target="_blank">',
				'</a>'
				);
	}

	$torrouter_restart_tor_disabled = ' disabled';
	$torrouter_restart_tor_notice = '<p>cron is not set up</p>';
	$torrouter_torkeepalive_notice = '<p>cron is not set up</p>';
	if ( is_readable( WP_CONTENT_DIR . '/tor-router-cron' ) ) {
		$torrouter_cron_lastrun = time()-filemtime( WP_CONTENT_DIR . '/tor-router-cron' );
		if ( $torrouter_cron_lastrun < 300) {
			$torrouter_restart_tor_disabled = null;
			$torrouter_restart_tor_notice = null;
			$torrouter_torkeepalive_notice = null;
		}
	}

	echo '<div><br><h1>' . esc_html__( 'Tor Router', 'tor-router' ) . '</h1>
	<br>
	<p>' . esc_html__( 'Route all outgoing connections through Tor or any HTTP / SOCKS Proxy.', 'tor-router' ) . '</p>
	<br>
	<form method="post" action="options.php">';
	settings_fields( 'torrouter_options_group' );
	$torrouter_proxymode = get_option( 'torrouter_proxymode' );
	echo '
	<table width="550">
	<tr>
	<td width="150" valign="top">' . esc_html__( 'Proxy Mode', 'tor-router' ) . '</td>
	<td width="400" ><select name="torrouter_proxymode" id="torrouter_proxymode" style="width: 100%;">
	<option value="0"'; selected( $torrouter_proxymode, 0 ); echo '>DIRECT (No Proxy)</option>
	<option value="1"'; selected( $torrouter_proxymode, 1 ); echo '>HTTP</option>
	<option value="2"'; selected( $torrouter_proxymode, 2 ); echo '>HTTPS</option>
	<option value="4"'; selected( $torrouter_proxymode, 4 ); echo '>SOCKS4</option>
	<option value="5"'; selected( $torrouter_proxymode, 5 ); echo '>SOCKS4a</option>
	<option value="6"'; selected( $torrouter_proxymode, 6 ); echo '>SOCKS5</option>
	<option value="7"'; selected( $torrouter_proxymode, 7 ); echo '>SOCKS5 + DNS</option>
	</select>
	</td>
	</tr>
	<tr><td colspan="2"><br></td></tr>
	<tr>
	<td valign="top">' . esc_html__( 'Proxy Host', 'tor-router' ) . '</td>
	<td><input type="text" name="torrouter_host" id="torrouter_host" style="width: 100%;" value="' . get_option( 'torrouter_host' ) . '">
	</td>
	</tr>
	<tr><td colspan="2"><br></td></tr>
	<tr>
	<td valign="top">' . esc_html__( 'Proxy Port', 'tor-router' ) . '</td>
	<td><input type="text" name="torrouter_port" id="torrouter_port" style="width: 100%;" value="' . get_option( 'torrouter_port' ) . '">
	</td>
	</tr>
	<tr><td colspan="2"><br><br></td></tr>
	<tr>
	<td valign="top">' . esc_html__( 'Enable Firewall', 'tor-router' ) . '</td>
	<td><input type="checkbox" name="torrouter_firewall" id="torrouter_firewall" value="1" ';
	checked( '1', get_option( 'torrouter_firewall' ) );
	echo '/> <label for="torrouter_firewall">' . esc_html__( 'Block outbound connections', 'tor-router' ) . '</label>
	<br><br><p>' . esc_html__( 'Exceptions (URLs):', 'tor-router' ) . '</p>
	<textarea name="torrouter_firewall_exceptions_urls" id="torrouter_firewall_exceptions_urls" style="width: 100%; height: 200px;">';
	$torrouter_firewall_exceptions_urls = get_option( 'torrouter_firewall_exceptions_urls' );
	$torrouter_fweuf = false;
	foreach ( $torrouter_firewall_exceptions_urls as $thisurl ) {
		if ( $torrouter_fweuf ) echo "\n"; else $torrouter_fweuf = true;
		echo $thisurl;
	}
	echo '</textarea>
	<br><br>';
	$torrouter_firewall_exceptions_plugins = get_option( 'torrouter_firewall_exceptions_plugins' );
	if ( isset( $torrouter_firewall_exceptions_plugins['wordpresscore'] ) ) $corechecked = ' checked'; else $corechecked = null;
	echo '<p>' . esc_html__( 'Exceptions (Plugins):', 'tor-router' ) . '</p>
	<input type="checkbox" id="torrouter_firewall_exceptions_plugins[wordpresscore]" name="torrouter_firewall_exceptions_plugins[wordpresscore]"' . $corechecked . '> <label for="torrouter_firewall_exceptions_plugins[wordpresscore]">WordPress Core</label><br>';
	foreach ( $torrouter_plugins_list as $key => $value ) {
		$torrouter_plugins_list_sub = explode( "/", $key, 2 );
		$thisplugin = $torrouter_plugins_list_sub[0];
		$thischecked = null;
		if ( isset( $torrouter_firewall_exceptions_plugins[$thisplugin] ) ) $thischecked = ' checked';
		echo '<input type="checkbox" id="torrouter_firewall_exceptions_plugins[' . $thisplugin . ']" name="torrouter_firewall_exceptions_plugins[' . $thisplugin . ']"' . $thischecked . '> <label for="torrouter_firewall_exceptions_plugins[' . $thisplugin . ']">' . $thisplugin . '</label><br>';
	}
	echo '
	</td>
	</tr>
	<tr><td colspan="2"><br><br></td></tr>
	<tr>
	<td valign="top">' . esc_html__( 'Monitor', 'tor-router' ) . '</td>
	<td>' . $torrouter_snitch_link . '
	</td>
	</tr>
	<tr><td colspan="2"><br><br></td></tr>
	<tr>
	<td valign="top">' . esc_html__( 'Tor KeepAlive', 'tor-router' ) . '</td>
	<td><input type="checkbox" name="torrouter_torkeepalive" id="torrouter_torkeepalive" value="1" ';
	checked( '1', get_option( 'torrouter_torkeepalive' ) );
	echo '> <label for="torrouter_torkeepalive">' . esc_html__( 'Restart Tor if hourly check fails', 'tor-router' ) . '</label>'.$torrouter_torkeepalive_notice.'
	</td>
	</tr>
	</table>';
	submit_button();
	echo '</form>
	<a name="checkip"></a>
	<form method="post" name="torrouter_checkip" action="#checkip">';
	wp_nonce_field( 'torrouter_checkip_action', 'torrouter_checkip_nonce' );
	echo '
	<table style="margin-top: 18px; width: 550px;">
	<tr>
	<td>
	<input type="submit" class="button button-primary" name="torrouter_checkip" value="' . esc_attr__( 'Check IP', 'tor-router' ) . '"/> <span style="font-size: 18px; vertical-align: -7px;">'.$torrouter_checkedtext.'</span>
	<p class="description">' . esc_html__( 'retrieves your IP from icanhazip.com', 'tor-router' ) . '</p></td>
	</tr>
	</table>
	</form>
	<br>
	<a name="restarttor"></a>
	<form method="post" name="torrouter_restart_tor" action="#restarttor">';
	wp_nonce_field( 'torrouter_restart_tor_action', 'torrouter_restart_tor_nonce' );
	echo '<input type="submit" class="button button-primary" name="torrouter_restart_tor" value="' . esc_attr__( 'Restart Tor', 'tor-router' ) . '"'.$torrouter_restart_tor_disabled.'> <span style="font-size: 18px; vertical-align: -7px;">'.$torrouter_restarttext.'</span>'.$torrouter_restart_tor_notice.'</form>
	</div>';
} 

/* This method is the more common way to route through a proxy. We replaced it with curl options to allow all types of Proxies.
if (get_option( 'torrouter_enabled' )=='1' ) {
	if ( ( !defined( 'WP_PROXY_HOST' ) ) AND ( !defined( 'WP_PROXY_PORT' ) ) ) {
		if (get_option( 'torrouter_socks' )=='1' ) {
			$torrouter_host = 'socks://'.get_option( 'torrouter_host' );
		}
		else {
			$torrouter_host = get_option( 'torrouter_host' );
		}
		define( 'WP_PROXY_HOST', $torrouter_host);
		define( 'WP_PROXY_PORT', get_option( 'torrouter_port' ));
	}
}
*/

if ( get_option( 'torrouter_proxymode' ) != '0' ) {
	add_action( 'http_api_curl', 'torrouter_proxify' );
}

function torrouter_proxify ( $handle ) {
	curl_setopt($handle, CURLOPT_PROXY, get_option( 'torrouter_host' ));
	curl_setopt($handle, CURLOPT_PROXYPORT, get_option( 'torrouter_port' ));
	curl_setopt($handle, CURLOPT_PROXYTYPE, get_option( 'torrouter_proxymode' ));
}

add_filter(
	'pre_http_request',
	'torrouter_inspect_request',
	10,
	3
);

function torrouter_inspect_request($pre, $args, $url)
{
	/* Empty url */
	if ( empty($url ) ) {
		return $pre;
	}

	/* Invalid host */
	if ( ! $host = parse_url( $url, PHP_URL_HOST ) ) {
		return $pre;
	}

	/* Check for internal requests */
	if ( torrouter_is_internal($host) ) {
		if ( ( !( torrouter_is_onion($host) ) ) && ( get_option( 'torrouter_proxymode' )!='0' ) ) {
			remove_action( 'http_api_curl', 'torrouter_proxify' );
		}
	}

	if ( defined( 'SNITCH_IGNORE_INTERNAL_REQUESTS' ) && SNITCH_IGNORE_INTERNAL_REQUESTS && torrouter_is_internal($host) ) {
		return $pre;
	}

	/* Timer start */
	timer_start();

	/* Backtrace data */
	$backtrace = torrouter_debug_backtrace();

	/* No reference file found */
	if ( empty( $backtrace['file'] ) ) {
		return $pre;
	}

	/* Show your face, file */
	$meta = torrouter_face_detect( $backtrace['file'] );
	$pluginslug = substr( ltrim( str_replace( WP_PLUGIN_DIR, '', $backtrace['file'] ), DIRECTORY_SEPARATOR ), 0, strpos( ltrim( str_replace( WP_PLUGIN_DIR, '', $backtrace['file'] ), DIRECTORY_SEPARATOR ), DIRECTORY_SEPARATOR ) );

	/* Init data */
	$file = str_replace( ABSPATH, '', $backtrace['file'] );
	$line = (int)$backtrace['line'];

	if (get_option( 'torrouter_firewall' ) == '1' ) {
		$torrouter_allow = false;
		$torrouter_firewall_exceptions_urls = get_option( 'torrouter_firewall_exceptions_urls' );
		array_push($torrouter_firewall_exceptions_urls, get_site_url());
		foreach ( $torrouter_firewall_exceptions_urls as $thisurl ) {
			if ( ( substr( $url, 0, strlen( $thisurl ) ) == $thisurl ) AND ( trim( $thisurl ) != '' ) ) $torrouter_allow = true; 
		}
		$torrouter_firewall_exceptions_plugins = get_option( 'torrouter_firewall_exceptions_plugins' );
		if ( $meta['type'] == 'WordPress' ) { 
			if ( isset( $torrouter_firewall_exceptions_plugins['wordpresscore'] ) ) $torrouter_allow = true;
		}
		else {
			if ( isset( $torrouter_firewall_exceptions_plugins[$pluginslug] ) ) $torrouter_allow = true;
		}
		if ( $torrouter_allow ) {
			return $pre;
		} else { 
			return torrouter_insert_post(
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
						'postdata' => torrouter_get_postdata($args)
					)
				)
			);
		}
	}
	else
	{
		return $pre;
	}
}

function torrouter_debug_backtrace() 
{
	/* Reverse items */
	$trace = array_reverse(debug_backtrace() );

	/* Loop items */
	foreach( $trace as $index => $item ) {
		if ( ! empty($item['function'] ) && strpos($item['function'], 'wp_remote_' ) !== false ) {
			/* Use prev item */
			if ( empty( $item['file'] ) ) {
				$item = $trace[-- $index];
			}

			/* Get file and line */
			if ( ! empty( $item['file'] ) && ! empty( $item['line'] ) ) {
				return $item;
			}
		}
	}
}
	
function torrouter_face_detect( $path )
{
	/* Default */
	$meta = array(
		'type' => 'WordPress',
		'name' => 'Core'
	);

	/* Empty path */
	if ( empty( $path ) ) {
		return $meta;
	}

	/* Search for plugin */
	if ( $data = torrouter_localize_plugin( $path ) ) {
		return array(
			'type' => 'Plugin',
			'name' => $data['Name']
		);

	/* Search for theme */
	} else if ( $data = torrouter_localize_theme( $path ) ) {
		return array(
			'type' => 'Theme',
			'name' => $data->get( 'Name' )
		);
	}

	return $meta;
}

function torrouter_insert_post( $meta )
{
	/* Empty? */
	if ( empty( $meta ) ) {
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
	foreach( $meta as $key => $value ) {
		add_post_meta(
			$post_id,
			'_snitch_' .$key,
			$value,
			true
		);
	}

	return $post_id;
}

function torrouter_localize_plugin( $path )
{
	/* Check path */
	if ( strpos( $path, WP_PLUGIN_DIR ) === false ) {
		return false;
	}

	/* Reduce path */
	$path = ltrim(
		str_replace( WP_PLUGIN_DIR, '', $path ),
		DIRECTORY_SEPARATOR
	);

	/* Get plugin folder */
	$folder = substr(
		$path,
		0,
		strpos( $path, DIRECTORY_SEPARATOR )
	) . DIRECTORY_SEPARATOR;

	/* Frontend */
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once( ABSPATH. 'wp-admin/includes/plugin.php' );
	}

	/* All active plugins */
	$plugins = get_plugins();

	/* Loop plugins */
	foreach( $plugins as $path => $plugin ) {
		if ( strpos( $path, $folder ) === 0 ) {
			return $plugin;
		}
	}
}

function torrouter_localize_theme( $path )
{
	/* Check path */
	if ( strpos( $path, get_theme_root() ) === false ) {
		return false;
	}

	/* Reduce path */
	$path = ltrim(
		str_replace( get_theme_root(), '', $path ),
		DIRECTORY_SEPARATOR
	);

	/* Get theme folder */
	$folder = substr(
		$path,
		0,
		strpos( $path, DIRECTORY_SEPARATOR )
	);

	/* Get theme */
	$theme = wp_get_theme( $folder );

	/* Check & return theme */
	if ( $theme->exists() ) {
		return $theme;
	}

	return false;
}

function torrouter_get_postdata( $args )
{
	/* No POST data? */
	if ( empty( $args['method'] ) OR $args['method'] !== 'POST' ) {
		return NULL;
	}

	/* No body data? */
	if ( empty( $args['body'] ) ) {
		return NULL;
	}

	return $args['body'];
}

function torrouter_is_internal( $host ) 
{
	/* Get the blog host */
	$blog_host = parse_url(
		get_bloginfo( 'url' ),
		PHP_URL_HOST
	);

	return ( $blog_host === $host );
}

function torrouter_is_onion( $host ) 
{
	return ( substr( $host, -6 ) === '.onion' );
}

?>

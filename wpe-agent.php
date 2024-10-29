<?php
/*
Plugin Name: Agent WP Engine
Plugin URI: http://portal.wpengine.com
Description: Connectivity to our amazing, free WordPress management portal
Author: WP Engine Hosting
Author URI: http://wpengine.com
Version: 0.22
*/

class WpeAgent
{
	const PARENT_MENU_PAGE = 'index.php';
	const SLUG = 'wpe-agent';
	const PORTAL_NAME = 'WP Engine WordPress Portal';
	
	public function __construct()
	{
		$this->_nonce_counter = 0;
		$this->_did_sso = false;
		$this->messages = array();
		$this->errors = array();
		$this->portal_url = "https://portal.wpengine.com";		// use SSL!
	}
	
	// Singleton instance
	public static function instance()
	{
		static $self = false;
		if ( ! $self ) $self = new WpeAgent();
		return $self;
	}
	
	// Parses the header data for this plugin, or empty array if we cannot.
	public function get_header_data()
	{
		if ( function_exists('get_plugin_data') )
			return get_plugin_data( __FILE__ );
		return array();
	}
	
	public function get_plugin_title()
	{
		return "Agent WP Engine";
	}
	
	// Initialize hooks
	public function wp_hook_init()
	{
		add_action('wpe_agent_hourly_hook', array('WpeAgent','do_hourly'));
		add_action('plugins_loaded',array($this,'do_response_sso_api'));
		add_action('wp_loaded',array($this,'wp_loaded'));
		if ( is_admin() )
		{
			add_action('admin_menu',array($this,'wp_hook_admin_menu'));
			if( is_multisite() )
			{
				remove_action('admin_menu',array( $this,'wp_hook_admin_menu' ));
				add_action( 'network_admin_menu', array( $this, 'wp_hook_admin_menu' ) );
			}
		}
		register_activation_hook( __FILE__, array($this,'wp_plugin_activate') );
		register_deactivation_hook( __FILE__, array($this,'wp_plugin_deactivate') );
	}
	
	public function wp_plugin_activate()
	{
		wp_schedule_event(time(), 'hourly', 'wpe_agent_hourly_hook' );
		$this->enqueue_event( 'info', 'plugin_activate', "Plugin " . $this->get_plugin_title() . " was activated." );
	}
	
	public function wp_plugin_deactivate()
	{
		wp_clear_scheduled_hook('wpe_agent_hourly_hook');
	}
	
	public function wp_hook_admin_menu()
	{
		$is_mu = is_multisite();
		add_submenu_page(
			WpeAgent::PARENT_MENU_PAGE,		// under "Dashboard"
			WpeAgent::PORTAL_NAME . " Agent",			// page title
			'Agent WP Engine',			// menu title
			$is_mu ? 'manage_network' : 'manage_options',		// required capability to view
			WpeAgent::SLUG,				// slug
			array( $this, 'wpe_admin_page')		// renderer
		);
	}
	
	public function wp_loaded()
	{
        // If we did a single-sign-on, redirect to the admin area
        if ( $this->_did_sso ) {
			wp_redirect( $this->get_my_admin_url() );
            exit();
        }
	}
	
	// The URL to this plugin admin screen, correcting for Multisite
	public function get_my_admin_url()
	{
		$my_url = WpeAgent::PARENT_MENU_PAGE . '?page=' . WpeAgent::SLUG;
		if ( is_multisite() )
			$my_url = network_admin_url( $my_url );
		else
			$my_url = admin_url( $my_url );
		return $my_url;
	}
	
	public function do_response_sso_api()
	{
		if ( isset($_REQUEST['wpe_sso_sig']) && isset($_REQUEST['wpe_sso_nonce']) && isset($_REQUEST['cmd']) ) {
			$their_sig = $_REQUEST['wpe_sso_sig'];
			$nonce = $_REQUEST['wpe_sso_nonce'];
			$cmd = $_REQUEST['cmd'];
			$requested_login_counter = 0;
			if ( isset($_REQUEST['wpe_sso_counter']) ) {
				$requested_login_counter = intval($_REQUEST['wpe_sso_counter']);
				$nonce .= "|$requested_login_counter";
			}
			$my_sig = $this->get_signature( $nonce );
			if ( $their_sig != $my_sig ) {
				error_log("ALERT: Portal command [$cmd] attempt with incorrect signature: nonce=$nonce, counter=$requested_login_counter, my_sig=$my_sig, their_sig=$their_sig");
				return;
			}
			switch ( $cmd ) {
				case 'login':
					$user_id = $this->get_option("sso_user_id");
					if ( ! $user_id ) {
						error_log("FAIL: Portal command [$cmd] attempted on invalid user id: $user_id");
						return;
					}
					if ( ! $requested_login_counter || $requested_login_counter <= 0 )
						die("ALERT: Portal login attempt with invalid login counter: $requested_login_counter");
					$current_login_counter = $this->get_option("login_counter");
					if ( $requested_login_counter <= $current_login_counter ) {
						error_log("WARNING: Portal login attempt with old login counter: $requested_login_counter; current is $current_login_counter");
						$this->api_send_status();		// ensure the portal is up-to-date with the right login counter
						@ob_clean();		// make an attempt to clean anything already sent; it's OK if we fail
						?>
						<html><head><title>Try Again.</title></head><body>
							<h3>Outdated authentication token detected.</h3>
							<p>Please visit <a href="<?php echo $this->portal_url?>">the Portal</a> and try your authentication again.</p>
						<body></html>
						<?php
						exit(0);
					}
					$this->set_option("login_counter",$requested_login_counter);		// update this first; it's single-use regardless what happens next
					wp_set_auth_cookie( $user_id );
					$this->_did_sso = true;
					break;
				case 'send-status':
					$this->api_send_status();
					echo("Success.");
					exit(0);
				default:
					error_log("FAIL: SSO attempted with unknown command: $cmd");
					return;
			}
		}
	}
	
	public static function do_hourly()
	{
		$inst = WpeAgent::instance();
		$inst->api_send_status();
	}
	
	public function wpe_admin_page()
	{
		if (!current_user_can('manage_options'))
			return false;

		if( MULTISITE && !is_super_admin() )
		{
			//echo 'You do not have permission';
			?>
				<div class="wrap">
					<h2>Error</h2>
					<p>You do not have permission to access this.</p>
				</div>
			<?php
			return false;
		}
		
		require_once(dirname(__FILE__)."/wpe-agent-admin.php");
	}
	
	public function get_option( $name, $default = null )
	{
		$this->init_options();
		return get_option("wpe_agent_$name",$default);
	}
	
	public function set_option( $name, $value )
	{
		$this->init_options();
		update_option("wpe_agent_$name",$value);		
	}
	
	private function init_options()
	{
		if ( ! isset($this->_options_inited) ) {
			add_option("wpe_agent_account_id","","",false);
			add_option("wpe_agent_api_token","","",false);
			add_option("wpe_agent_sso_user_id",get_current_user_id() > 0 ? get_current_user_id() : 1,"",false);
			add_option("wpe_agent_wordpress_id",$this->get_nonce(),"",false);
			add_option("wpe_agent_event_log",array(),"",false);
			add_option("wpe_agent_login_counter",1,"",false);
			$this->_options_inited = true;
		}
	}
	
	public function enqueue_event( $severity, $hook, $html_text, $push_to_portal = true )
	{
		// Load
		$log = $this->get_option("event_log");
		
		// Pop off old events if needed
		$max_events = 10;
		while ( count($log) > $max_events )
			array_shift($log);
		
		// Add this event
		$event = array (
			'tstamp' => time(),
			'hook' => $hook,
			'severity' => $severity,
			'html_text' => $html_text,
			);
		array_push($log,$event);
		
		// Save
		$this->set_option("event_log",$log);
		
		// Send status update to portal
		if ( $push_to_portal )
			$this->api_send_status();
	}
	
	public function api_send_status()
	{
		global $wp_version;
		
		$header_data = $this->get_header_data();
		
		$status = array("system"=>array(),"portal"=>array());
		$status['wordpress_id'] = $this->get_option("wordpress_id");
		$status['site_url'] = site_url();
		$status['home_url'] = home_url();
		$status['title'] = get_bloginfo('name');
		if ( is_string($wp_version) && strpos($wp_version,'.') )		// sometimes it's a number... ?
			$status['system']['version'] = $wp_version;
		if ( isset($header_data['Version']) )
			$status['system']['agent_version'] = $header_data['Version'];
		$status['system']['hostname'] = php_uname("n");
		$status['portal']['login_counter'] = $this->get_option("login_counter");
		$status['event_log'] = $this->get_option("event_log");		// sending ALL -- should we send just the last N?
		$r = $this->api_call("status_update",$status);
		if ( $r ) {
			$this->messages[] = "Updated blog status.";
			return true;
		}
		return false;
	}
	
	private function api_call( $cmd, $payload )
	{
		$aid = $this->get_option("account_id");
		$nonce = $this->get_nonce();
		$sig = $this->get_signature( $nonce );
		$cmd = urlencode($cmd);
		$url = $this->portal_url . "/api.php?v=1&a=${aid}&nonce=${nonce}&sig=${sig}&cmd=${cmd}";
		$opts = array('http' =>
		    array(
		        'method'  => 'PUT',
		        'header'  => 'Content-type: application/json',
		        'content' => json_encode($payload),
		    )
		);
		$context = stream_context_create($opts);
		$r = file_get_contents( $url, false, $context );
		if ( ! $r ) {
			$this->errors[] = "Couldn't connect to, or didn't get a response from the ".PORTAL_NAME.".  Please try again later.";
			return false;
		}
		$r = json_decode($r);
		if ( ! $r ) {
			$this->errors[] = "Couldn't decode JSON: Here's what the server returned: <pre>" . htmlspecialchars($r) . "</pre>";
			return false;
		}
		if ( ! $r->success ) {
			$this->errors[] = $r->error->html_message;
			return false;
		}
		return $r->data;
	}
	
	public function get_display_event_log()
	{
		$log = $this->get_option("event_log");
		$log = array_reverse($log);
		return $log;
	}
	
	private function get_signature( $nonce )
	{
		$aid = $this->get_option("account_id");
		$api_token = $this->get_option("api_token");
		return sha1( $aid . $api_token . $nonce );
	}
	
	private function get_nonce()
	{
		return sha1( 'ab-normal|' . time() . '|' . rand() . '|' . php_uname() . '|' . @$_SERVER['REMOTE_ADDR'] . '|' . ($this->_nonce_counter++) );
	}
	
	private function emit_ui_select( $name, $choices, $selected = null )
	{
		echo("<select name=\"$name\">");
		foreach ( $choices as $key => $display ) {
			$attrs = "value=\"".htmlspecialchars($key)."\"";
			if ( $key == $selected )
				$attrs .= " selected";
			echo("<option $attrs>".htmlspecialchars($display)."</option>\n");
		}
		echo("</select>\n");
	}
	
	private function get_users_for_select()
	{
		global $wpdb;
		$r = array();
		$sql_capabilities_like = $wpdb->escape('%s:13:"administrator";s:1:"1";%');
		$sql = "SELECT u.ID,u.user_login
			FROM
				$wpdb->users u INNER JOIN $wpdb->usermeta m ON ( u.ID = m.user_id )
			WHERE
				    m.meta_key = 'wp_capabilities'
				AND m.meta_value LIKE '$sql_capabilities_like'
			ORDER BY user_login
			LIMIT 1000
		";
		$rows = $wpdb->get_results($sql);
		if ( count($rows) == 0 ) {
			$sql = "SELECT u.ID,u.user_login
				FROM
					$wpdb->users u
				ORDER BY user_login
				LIMIT 1000
			";
			$rows = $wpdb->get_results($sql);
		}
		foreach ( $rows as $row )
			$r[$row->ID] = $row->user_login;
		return $r;
	}
}

// Create an instance to get all our hooks installed, but not if we're running on the WP Engine staging area
if ( ! defined('IS_WPE_SNAPSHOT') ) {
	$wpe_agent = WpeAgent::instance();
	$wpe_agent->wp_hook_init();
}

<?php
/*-----------------------------------------------------------------------------
* Domain Registry Control Panel                                               *
*                                                                             *
* Main Author: Vaggelis Koutroumpas vaggelis@koutroumpas.gr (c)2014 for AWMN  *
* Credits: see CREDITS file                                                   *
*                                                                             *
* This program is free software: you can redistribute it and/or modify        *
* it under the terms of the GNU General Public License as published by        * 
* the Free Software Foundation, either version 3 of the License, or           *
* (at your option) any later version.                                         *
*                                                                             *
* This program is distributed in the hope that it will be useful,             *
* but WITHOUT ANY WARRANTY; without even the implied warranty of              *
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the                *
* GNU General Public License for more details.                                *
*                                                                             *
* You should have received a copy of the GNU General Public License           *
* along with this program. If not, see <http://www.gnu.org/licenses/>.        *
*                                                                             *
*-----------------------------------------------------------------------------*/

//Set some global parameters
//error_reporting(E_ALL ^ E_NOTICE);

//MySQL Connection script
$db = @mysql_connect( $CONF['db_host'], $CONF['db_user'], $CONF['db_pass'] );
//@mysql_query('set names utf8'); 
@mysql_select_db($CONF['db'],$db);

//In case of mysql error, exit with message
if (mysql_error()){
    exit("<html>\n<head>\n<title>Error!</title>\n</head>\n<body>\nAn error occured while connecting to database.</body>\n</html>");
}

//GET SETTINGS FROM DB
$SELECT_SETTINGS = mysql_query("SELECT Name, Value FROM settings", $db);
while ($SETTINGS = mysql_fetch_array($SELECT_SETTINGS)){
	$CONF[$SETTINGS['Name']] = $SETTINGS['Value'];
}

$CONF['CREDITS'] = "<a href=\"https://github.com/Cha0sgr/DNS-Registry\" target=\"_blank\">DNS Registry Control Panel</a> &copy; 2014 - ". date("Y");

//Start gzip compression & session
if(php_sapi_name() != "cli" && stristr($_SERVER['PHP_SELF'], "js.php") == FALSE  && stristr($_SERVER['PHP_SELF'], "css.php") == FALSE ) {
	require ("sessions.php");
	ob_start();
	session_start();
}

//Load SSH libraries
require('Net/SSH2.php');
require('Crypt/RSA.php');	


//Set global var $SECTION
if (isset($_GET['section'])){
    $SECTION = $_GET['section'];
}

//Set global vars for TITLE, Section heading & CSS class for section heading
if (isset($SECTION) && $SECTION == 'domains'){
    $maintitle_class = 'maintitle_domains';
    $maintitle_title = 'My Domain Names';
}elseif (isset($SECTION) && $SECTION == 'domain'){
    $maintitle_class = 'maintitle_domain';
    $maintitle_title = 'Manage Hosted Domain';
}elseif (isset($SECTION) && $SECTION == 'domain_ns'){
    $maintitle_class = 'maintitle_domain_ns';
    $maintitle_title = 'Manage Domain Nameservers';
}elseif (isset($SECTION) && $SECTION == 'nameservers'){
    $maintitle_class = 'maintitle_nameservers';
    $maintitle_title = 'Nameservers Registration (Glue Records)';
}elseif (isset($SECTION) && $SECTION == 'transfers'){
    $maintitle_class = 'maintitle_transfers';
    $maintitle_title = 'Manage Domain Transfer Requests';
}elseif (isset($SECTION) && $SECTION == 'whois'){
    $maintitle_class = 'maintitle_whois';
    $maintitle_title = 'Web WHOIS Lookup';
}elseif (isset($SECTION) && $SECTION == 'tlds'){
    $maintitle_class = 'maintitle_tlds';
    $maintitle_title = 'Allowed Top Level Domains (TLDs)';
}elseif (isset($SECTION) && $SECTION == 'root_ns'){
    $maintitle_class = 'maintitle_root_ns';
    $maintitle_title = 'Root Nameservers for TLDs';
}elseif (isset($SECTION) && $SECTION == 'root_ns_unicast'){
    $maintitle_class = 'maintitle_root_ns_unicast';
    $maintitle_title = 'Root Nameservers Unicast NOTIFY IPs';
}elseif (isset($SECTION) && $SECTION == 'slave_zones'){
    $maintitle_class = 'maintitle_slave_zones';
    $maintitle_title = 'Slave Zones';
}elseif (isset($SECTION) && $SECTION == 'communities'){
    $maintitle_class = 'maintitle_communities';
    $maintitle_title = 'Wireless Communities Management';
}elseif (isset($SECTION) && $SECTION == 'users'){
    $maintitle_class = 'maintitle_users';
    $maintitle_title = 'User Management';
}elseif (isset($SECTION) && $SECTION == 'user'){
    $maintitle_class = 'maintitle_users';
    $maintitle_title = 'My Account Settings';
}elseif (isset($SECTION) && $SECTION == 'settings'){
    $maintitle_class = 'maintitle_settings';
    $maintitle_title = 'System Settings';
}else{
    $maintitle_class = 'maintitle_home';
    $maintitle_title = 'Dashboard';
}


#########################################
#        User Login Functions           #
#########################################

// create sessions - cookie
function admin_create_sessions($id,$username,$password,$remember, $help, $level, $default_ttl_domains, $default_ttl_records,  $impersonate=false){
    global $CONF, $db, $_SESSION;
    
    if ($impersonate == true){
		if ($id != $_SESSION['admin_orig']){
			$_SESSION['admin_orig'] = $_SESSION['admin_id'];
		}else{
			unset($_SESSION['admin_orig']);			
		}
    }else{
    	if ($id == $_SESSION['admin_orig']){
			unset($_SESSION['admin_orig']);
		}
	}

    //session_register();
    $_SESSION['admin_id'] = $id;
    $_SESSION['admin_username'] = $username;
    $_SESSION['admin_sha1part'] = substr(sha1($password),0,10);
    $_SESSION['admin_help'] = $help;
    $_SESSION['admin_level'] = $level;
    $_SESSION['admin_default_ttl_domains'] = $default_ttl_domains;
    $_SESSION['admin_default_ttl_records'] = $default_ttl_records;
    
    if (!$_SESSION['admin_orig']){
		mysql_query("UPDATE users SET last_login = UNIX_TIMESTAMP(), last_ip = '".mysql_real_escape_string($_SERVER['REMOTE_ADDR'])."'  WHERE id = '" . $id ."'", $db);		
    }
    
    $IS_SUSPENDED = mysql_num_rows(mysql_query("SELECT 1 FROM users WHERE suspended > '0' AND id = '" . $id ."' AND active = '1' ", $db ));
    if ($IS_SUSPENDED){
		//Enable account, user came back!
		mysql_query("UPDATE users SET suspended = '0' WHERE id = '" . $id ."'", $db);
		mysql_query("UPDATE records SET disabled = '0' WHERE user_id = '" . $id ."'", $db);
		mysql_query("DELETE FROM users_notifications WHERE user_id = '".$id."' AND type = 'LAST_LOGIN_MAX_SUSPEND' ", $db);
		mysql_query("DELETE FROM users_notifications WHERE user_id = '".$id."' AND type = 'LAST_LOGIN_MAX_START_ALERTS' ", $db);
	}
    	    
    if(isset($remember)){
        setcookie($CONF['COOKIE_NAME'], $_SESSION['admin_id'] . "||" . $_SESSION['admin_username']  ."||" . $_SESSION['admin_sha1part']. "||" . $_SESSION['admin_help'] . "||" . $_SESSION['admin_default_ttl_domains'] . "||" . $_SESSION['admin_default_ttl_records'], time()+60*60*24*15, "/");
        return;
    }
}

// do admin login
function admin_login($username,$password,$remember){
    global $db;
        
    $sha1pass = sha1($password);
    $username = mysql_real_escape_string($username);
    $USER_SELECT = @mysql_query("SELECT * FROM `users` WHERE username='".addslashes($username)."' AND password='".addslashes($sha1pass)."' AND active='1' LIMIT 1",$db);
    $user_check = @mysql_num_rows($USER_SELECT);     

    if ($user_check) { 
        $USER = @mysql_fetch_array($USER_SELECT);
        admin_create_sessions($USER['id'], $USER['username'], $USER['password'], $remember, $USER['Help'], $USER['Admin_level'], $USER['default_ttl_domains'], $USER['default_ttl_records']);
        return true;
    } else {         
        return false;
    }
}

// do admin logout
function admin_logout(){
	global $CONF;
		
    session_unset();
    setcookie ($CONF['COOKIE_NAME'], "",time()-60*60*24*15, "/");
}

// check if admin is logged
function admin_logged(){
    global $db, $CONF;

    if(isset($_SESSION['admin_username']) && isset($_SESSION['admin_sha1part'])) {
        $USER_SELECT = @mysql_query("SELECT * FROM `users` WHERE id='".$_SESSION['admin_id']."' AND username='".$_SESSION['admin_username']."' AND active='1' LIMIT 1",$db);
        $USER_CHECK = @mysql_num_rows($USER_SELECT);
        if ($USER_CHECK) {
        		if (!$_SESSION['admin_orig']){
        			mysql_query("UPDATE users SET last_login = UNIX_TIMESTAMP() WHERE id='".$_SESSION['admin_id']."' AND username='".$_SESSION['admin_username']."'  ", $db);
				}
                return true;
        } else {
            admin_logout();
            return false;
        }
    } elseif(isset($_COOKIE[$CONF['COOKIE_NAME']])) {
        $cookie = explode("||", $_COOKIE[$CONF['COOKIE_NAME']]);
        $USER_SELECT = @mysql_query("SELECT * FROM `users` WHERE id='".addslashes($cookie[0])."' AND username='".addslashes($cookie[1])."' AND active='1' LIMIT 1",$db);
        $USER_CHECK = @mysql_num_rows($USER_SELECT);
        if ($USER_CHECK) {
            $USER = @mysql_fetch_array($USER_SELECT);
            if (substr(sha1($USER['password']),0,10) == $cookie[2]) {
                admin_create_sessions($USER['id'], $USER['username'], $USER['password'], 1, $USER['Help'], $USER['Admin_level'], $USER['default_ttl_domains'], $USER['default_ttl_records']);
                return true;
            }
        } else {
            admin_logout();
            return false;
        }
    } else {
        admin_logout();
        return false;
    }
}

// quick protect a page. examples :
//
// default parameters:
// admin_auth();
//
// admin access only:
// admin_auth(9);
//
// custom parameters:
// admin_auth(9, "login.php", "access_denied.php");
                                                                         
function admin_auth($login_page = "login.php", $access_denied_page = "access_denied.php"){
    global $db, $CONF;
    
    if (!admin_logged()) {
        header("Location: " . $login_page);
        exit();
    } else {
        /*
        $USERS_SELECT = @mysql_query("SELECT Admin_level, active FROM `users` WHERE id='".addslashes($_SESSION['admin_id'])."' LIMIT 1",$db);
        $USERS = @mysql_fetch_array($USERS_SELECT);
        if (!$USERS['active']){ 
            admin_logout();
            header("Location: ./" . $login_page);
            exit();
        }
        
        $_SESSION['admin_access'] = $USERS['Admin_level'];
        
        if ($_SESSION['admin_access'] == 'editor' && $level != 'editor') {
            header("Location: ./" . $access_denied_page);
            exit();
        }
        */
        return true;
        
    }

}


//IMPERSONATE USER
if ($_GET['action'] == 'switch_user' && $_POST['user_id'] && ($_SESSION['admin_level'] == 'admin' || $_SESSION['admin_orig']) ){
	
	$SELECT_SWITCH_USER= mysql_query("SELECT * FROM users WHERE id = '".(int)$_POST['user_id']."' ", $db);
	$SWITCH_USER = mysql_fetch_array($SELECT_SWITCH_USER);

	if(isset($_COOKIE[$CONF['COOKIE_NAME']])) {
		$remember = '1'; 	
	}else{
		$remember = '0';
	}
	
	if ($_SESSION['admin_orig']){
		$impersonate = false;
	}else{
		$impersonate = true;
	}
	
	if ($SWITCH_USER['Admin_level'] == 'admin' && $_SESSION['admin_orig'] == $SWITCH_USER['id']){
		$user_level = 'admin';
		
	}elseif ($SWITCH_USER['Admin_level'] == 'admin' && $_SESSION['id'] != $SWITCH_USER['id'] ){
		$user_level = 'user';
	}else{
		$user_level = 'user';
	}	
	
	admin_create_sessions($SWITCH_USER['id'],$SWITCH_USER['username'],$SWITCH_USER['password'],$remember, 1, $user_level, $SWITCH_USER['default_ttl_domains'], $SWITCH_USER['default_ttl_records'], $impersonate);
	
	exit('ok');
	
}

// HELPER FUNCTIONS

// create sort link for table listings
function create_sort_link($attr, $title) {
    global $_SERVER, $_GET, $search_vars, $SECTION;

    if ($_GET['sort'] == $attr) {
        if ($_GET['by'] == "desc") {
            $by_value = "asc";
            $image = " <img src=\"images/sort_down.gif\" align=\"absmiddle\" />";
        } else {
            $by_value = "desc";
            $image = " <img src=\"images/sort_up.gif\" align=\"absmiddle\" />";
        }
    }

    return "<a href=\"index.php?section=$SECTION&sort=".$attr."&by=".$by_value."&pageno=".$_GET['pageno']. $search_vars ."\">".$title."</a> ". $image;
}

// Simple function to show tipsy help bubbles or not based on user profile settings
function staff_help(){
    global $_SESSION;
    
    if ($_SESSION['admin_help']){
        return TRUE;
    }else{
        return FALSE;
    }
    
}   


function get_soa_record($domain_id) {
	global $db;

	$SELECT_SOA = mysql_query("SELECT content FROM records WHERE type = 'SOA' AND domain_id = '".$domain_id."' ", $db);
	$SOA = mysql_fetch_array($SELECT_SOA);

	$result = $SOA['content'];	
	return $result;
}

function get_soa_serial($soa_rec) {
	$soa = explode(" ", $soa_rec);
	return $soa[2];
}

function update_soa_record($domain_id, $content) {
	global $db;
	
	$UPDATE = mysql_query("UPDATE records SET content = '".$content."' WHERE domain_id = '".$domain_id."' AND type = 'SOA'", $db);
	
	
	if (!$UPDATE) {  
		return false; 
	}
	
	return true;
}

function set_soa_serial($soa_rec, $serial) {
	// Split content of current SOA record into an array. 
	$soa = explode(" ", $soa_rec);
	$soa[2] = $serial;
	
	// Build new SOA record content
	$soa_rec = join(" ", $soa);
	chop($soa_rec);
	
	return $soa_rec;
}


function get_next_serial($curr_serial, $today = '') {
	// Zone transfer to zone slave(s) will occur only if the serial number
	// of the SOA RR is arithmetically greater that the previous one 
	// (as defined by RFC-1982).

	// The serial should be updated, unless:
	//  - the serial is set to "0", see http://doc.powerdns.com/types.html#id482176
	//
	//  - set a fresh serial ONLY if the existing serial is lower than the current date
	//
	//	- update date in serial if it reaches limit of revisions for today or do you 
	//	think that ritual suicide is better in such case?
	//
	// "This works unless you will require to make more than 99 changes until the new 
	// date is reached - in which case perhaps ritual suicide is the best option."
	// http://www.zytrax.com/books/dns/ch9/serial.html

	if ($today == '') {
		//set_timezone();
		$today = date('Ymd');
	}
	
	$revision = (int) substr($curr_serial, -2);
	$ser_date = substr($curr_serial, 0, 8);
	
	if ($curr_serial == '0') {
		$serial = $curr_serial;

	} elseif ($curr_serial == $today . '99') {
		$serial = get_next_date($today) . '00';
	
	} else {
		if (strcmp($today, $ser_date) === 0) {
			// Current serial starts with date of today, so we need to update the revision only.
			++$revision;
			
		} elseif (strncmp($today, $curr_serial, 8) === -1) {
			// Reuse existing serial date if it's in the future
			$today = substr($curr_serial, 0, 8);

			// Get next date if revision reaches maximum per day (99) limit otherwise increment the counter
			if ($revision == 99) {
				$today = get_next_date($today);
				$revision = "00";
			} else {
				++$revision;
			}
			
		} else {
			// Current serial did not start of today, so it's either an older 
			// serial, therefore set a fresh serial
			$revision = "00";
		}

		// Create new serial out of existing/updated date and revision
		$serial = $today . str_pad($revision, 2, "0", STR_PAD_LEFT);
	}
	
	return $serial;
}

function update_soa_serial_byid($domain_id) {
	$soa_rec = get_soa_record($domain_id);
    if ($soa_rec == NULL) {
        return false;
    }

	$curr_serial = get_soa_serial($soa_rec);
	$new_serial = get_next_serial($curr_serial);
	
	if ($curr_serial != $new_serial) {
		$soa_rec = set_soa_serial($soa_rec, $new_serial);
		return update_soa_record($domain_id, $soa_rec);
	}

	return true;
}


function update_soa_serial ($tld, $domain=false){
	global $db;
	
	if ($domain){
	
		//Skip soa serial update if domain is reverse (is that ok? need to check it further)
		$hostname_labels = explode('.', $tld);
		$label_count = count($hostname_labels);    
		if ($hostname_labels[$label_count - 1] == "arpa" ) {
            return true;
		}else{
			$tld_parts = explode (".", $tld);
			
			$tld_parts[0] = false;
			$tld = implode(".", $tld_parts);
			$tld =  substr($tld, 1);
																	
			//$tld_parts_rev = array_reverse($tld_parts);
			//$tld = $tld_parts_rev[0];
		
		}
	
	}
	
	$SELECT_TLD = mysql_query("SELECT id  FROM domains WHERE name = '".addslashes($tld)."' ", $db);
	$TLD = mysql_fetch_array($SELECT_TLD);
	
	return update_soa_serial_byid($TLD['id']);  
	
}

// DETECT IF GIVEN IP IS IN GIVEN IP-RANGE
function netMatch ($CIDR,$IP) {
    list ($net, $mask) = explode ('/', $CIDR);
    return ( ip2long ($IP) & ~((1 << (32 - $mask)) - 1) ) == ip2long ($net);
}

// Find if given domain contains a TLD that we manage
function getTLD ($domain){
	global $db;
	
	$domain_parts = array_reverse( explode (".", $domain) );

	$tld = '';	
	for ($i = 0; $i <= count($domain_parts); $i++) {
		$tld = $domain_parts[$i] . $tld ;
		if (mysql_num_rows(mysql_query("SELECT 1  FROM `tlds` WHERE name = '".$tld."' ", $db))){
			return $tld;
		}else{
			$tld = "." . $tld ;
		}
	}
	
	return false;
	
}


// Force SOA serial update to all domains to force slaves sync
if ($_GET['force_soa_update'] == '1' && $_SESSION['admin_level'] == 'admin' && $_GET['return']){

	$SELECT_DOMAINS = mysql_query("SELECT id, type, name FROM domains", $db);
	while ($DOMAINS = mysql_fetch_array($SELECT_DOMAINS)){
		if ($DOMAINS['id'] != '1' && $DOMAINS['type'] != 'SLAVE'){
			$soa_update = update_soa_serial_byid($DOMAINS['id']);
			
			// Run pdns_control notify to push the new SOA update to our slaves immediately. Fire and forget.
			exec ($CONF['PDNS_CONTROL_PATH'] . " --remote-address=".$CONF['PDNS_CONTROL_IP']." --remote-port=".$CONF['PDNS_CONTROL_PORT']." --secret=".$CONF['PDNS_CONTROL_KEY']." notify " . $DOMAINS['name'] . " > /dev/null 2>/dev/null &" );
        	
		}
	}
	
	if (strstr($_GET['return'], "?")){
		$return_url = urldecode($_GET['return']) . "&";
	}else{
		$return_url = $_SERVER['PHP_SELF'] . "?";
	}
	header ("Location: " . $return_url . "soa_updated=1");
	exit();
	
}

// Force Cache Flush to all BIND servers
if ($_GET['cache_flush'] == '1' && $_SESSION['admin_level'] == 'admin' && $_GET['return']){

	$SELECT_UNICAST_NS = mysql_query("SELECT ip FROM root_ns_unicast WHERE active = '1' ", $db);
	while ($UNICAST_NS = mysql_fetch_array($SELECT_UNICAST_NS)){
		if ($UNICAST_NS['ip']){
			
			// Connect via SSH to root ns and issue rndc flush/reload
			ssh_client2($UNICAST_NS['ip'], "/usr/sbin/rndc flush ; /usr/sbin/rndc reload &");
		}
	}
	
	if (strstr($_GET['return'], "?")){
		$return_url = urldecode($_GET['return']) . "&";
	}else{
		$return_url = $_SERVER['PHP_SELF'] . "?";
	}
	header ("Location: " . $return_url . "cache_flushed=1");
	exit();
	
}

// SEND JSON WITH SLAVE AND TLD ZONES TO ROOT NS BIND CACHES
if ($_GET['fetch_slaves_tlds'] == "1"){
	    
    $ROOT_NS = mysql_num_rows(mysql_query("SELECT 1 FROM root_ns_unicast WHERE ip = '".mysql_real_escape_string($_SERVER['REMOTE_ADDR'])."' AND active = '1' ", $db));
    if ($ROOT_NS == '1'){
    	$o = 0;
		$SELECT_SLAVES = mysql_query("SELECT name, masters FROM slave_zones WHERE active = '1' ", $db);
		while($SLAVES = mysql_fetch_array($SELECT_SLAVES)){
			$zones['slaves'][$o]['name'] = $SLAVES['name'];
			$master = explode(",", $SLAVES['masters']);
			for ($i = 0; $i <= count($master)-1; $i++) {
				$zones['slaves'][$o]['masters'][] = $master[$i];
			}
			$o++;
		}
	
		$o=0;
		$SELECT_TLDs = mysql_query("SELECT name FROM tlds WHERE active = '1' ", $db);
		while($TLDs = mysql_fetch_array($SELECT_TLDs)){
			$zones['tlds'][$o]['name'] = $TLDs['name'];
			$SELECT_ROOT_NS_IPS = mysql_query("SELECT ip FROM root_ns WHERE active = '1' ", $db);
			while($ROOT_NS_IPS = mysql_fetch_array($SELECT_ROOT_NS_IPS)){
				$zones['tlds'][$o]['masters'][] = $ROOT_NS_IPS['ip'];				
			}
			$o++;			
		}
		
		//Update last root ns access time
		mysql_query("UPDATE root_ns_unicast SET last_ping = UNIX_TIMESTAMP() WHERE ip = '".mysql_real_escape_string($_SERVER['REMOTE_ADDR'])."' AND active = '1' ", $db);
	
		ob_clean();		
		echo json_encode($zones);
    }else{
    	ob_clean();
		echo json_encode('access denied');
    }
    
    exit();
}


// SEND JSON WITH PUBLIC SSH KEYS
if ($_GET['fetch_ssh_keys'] == "1"){
	    
    $ROOT_NS = mysql_num_rows(mysql_query("SELECT 1 FROM root_ns_unicast WHERE ip = '".mysql_real_escape_string($_SERVER['REMOTE_ADDR'])."' AND active = '1' ", $db));
    if ($ROOT_NS == '1'){
    	$o = 0;
		$SELECT_USERS = mysql_query("SELECT username, ssh_key FROM users WHERE active = '1' AND Admin_level = 'admin' ", $db);
		while($USERS = mysql_fetch_array($SELECT_USERS)){
			if ($USERS['ssh_key']){
				$keys[$o]['username'] = $USERS['username'];
				$keys[$o]['ssh_key']  = $USERS['ssh_key'];
				$o++;
			}
		}
		
		ob_clean();		
		if ($keys){
			echo json_encode($keys);
		}else{
			echo json_encode("none");
		}
    }else{
    	ob_clean();
		echo json_encode('access denied');
    }
    
    exit();
}

//SSH Connection Client
function ssh_client2($IP,$COMMAND){
	global $CONF;
	
	$ssh = new Net_SSH2($IP, $CONF['ROOT_NS_SSH_PORT']);
	
	$key = new Crypt_RSA();
	$key->loadKey($CONF['MASTER_SSH_KEY_PRIVATE']);
	
	if (!$ssh->login("root", $key)) {
		return false;
	}

	if ($data = $ssh->exec($COMMAND) ){
		return $data;
	}else{
		return false;
	}

	$ssh->disconnect();	

}  
  
?>
#!/usr/bin/php -q
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

// Check PHP dependencies
if( ! extension_loaded('sockets' ) ) {
	echo "This program requires sockets extension (http://www.php.net/manual/en/sockets.installation.php)\n";
	exit(-1);
}
if( ! extension_loaded('pcntl' ) ) {
	echo "This program requires PCNTL extension (http://www.php.net/manual/en/pcntl.installation.php)\n";
	exit(-1);
}

// Include required libraries
require(dirname(__FILE__)."/includes/supersocket.class.php");
require("System/Daemon.php");

// Allowed arguments & their defaults
$runmode = array(
	'no-daemon' => false,
	'help' => false,
	'write-initd' => false,
);

// Scan command line attributes for allowed arguments
foreach ($argv as $k=>$arg) {
	if (substr($arg, 0, 2) == '--' && isset($runmode[substr($arg, 2)])) {
		$runmode[substr($arg, 2)] = true;
	}
}

// Help mode. Shows allowed argumentents and quit directly
if ($runmode['help'] == true) {
	echo 'Usage: '.$argv[0].' [runmode]' . "\n";
	echo 'Available runmodes:' . "\n";
	foreach ($runmode as $runmod=>$val) {
		echo ' --'.$runmod . "\n";
	}
	die();
}

// Setup
$options = array(
	'appName' => 'whoisd',
	'appDir' => dirname(__FILE__),
	'appDescription' => "DNS Registry Panel - WHOIS Server",
	'authorName' => 'Vaggelis Koutroumpas',
	'authorEmail' => 'vaggelis@koutroumpas.gr',
	'sysMaxExecutionTime' => '0',
	'sysMaxInputTime' => '0',
	'sysMemoryLimit' => '128M'
);

// Set setup options
System_Daemon::setOptions($options);

// This program can also be run in the forground with runmode --no-daemon
if (!$runmode['no-daemon']) {
	// Spawn Daemon
	System_Daemon::start();
}

// With the runmode --write-initd, this program can automatically write a
// system startup file called: 'init.d'
// This will make sure your daemon will be started on reboot
if (!$runmode['write-initd']) {
	//System_Daemon::info('not writing an init.d script this time');
} else {
	if (($initd_location = System_Daemon::writeAutoRun()) === false) {
		System_Daemon::notice('unable to write init.d script');
	} else {
		System_Daemon::info( 'sucessfully written startup script: %s', $initd_location );
	}
}


//Include system files
require_once(dirname(__FILE__)."/includes/config.php");
require_once(dirname(__FILE__)."/includes/functions.php");


// Callback to reply to WHOIS query
function whois_reply($socket_id, $channel_id, $buffer, &$obj){
		global $db, $CONF;

		$DOMAIN_lookup = trim($buffer);
		
		$SELECT_DOMAIN = mysql_query("SELECT user_id, created, change_date FROM records WHERE name = '".mysql_real_escape_string($DOMAIN_lookup)."' AND type = 'NS' LIMIT 0,1 ", $db);
		$DOMAIN = mysql_fetch_array($SELECT_DOMAIN);
		
		$SELECT_OWNER = mysql_query("SELECT username FROM users WHERE id = '".$DOMAIN['user_id']."' ", $db);
		$OWNER = mysql_fetch_array($SELECT_OWNER);
		        
		$whois_reply  = "% DNS Registry WHOIS Server for ".$CONF['APP_NAME']." (c)2014-".date("Y")."\n\r";
		$whois_reply .= "% For more information regarding this WHOIS service please visit ".$CONF['WHOIS_URL']."\n\r";
		$whois_reply .= "% \n\r";

		$whois_reply .= "% WHOIS ".$DOMAIN_lookup."\n\r";
		$whois_reply .= "% \n\r";
		$whois_reply .= "\n\r";
        
        if (mysql_num_rows($SELECT_DOMAIN)){
        	
	        $whois_reply .= "Domain ".$DOMAIN_lookup." \n\r";
	        $whois_reply .= "\n\r";
	        $whois_reply .= "Registration Date \n\r";
	        $whois_reply .= "\t".date("d-m-Y g:i a",$DOMAIN['created'])."\n\r";
	        $whois_reply .= "\n\r";
	        $whois_reply .= "Updated Date \n\r";
	        $whois_reply .= "\t".date("d-m-Y g:i a",$DOMAIN['change_date'])."\n\r";
	        $whois_reply .= "\n\r";
	        $whois_reply .= "Registrant:\n\r";
	        $whois_reply .= "\t".$OWNER['username']."\n\r";
	        $whois_reply .= "\n\r";
	        $whois_reply .= "Name Servers:\n\r";
	        
	        $SELECT_NS = mysql_query("SELECT content FROM records WHERE name = '".$DOMAIN_lookup."' AND type='NS' ORDER BY content ASC", $db);
	        while ($NS = mysql_fetch_array($SELECT_NS)){
        		$whois_reply .= "\t".$NS['content']."\n\r";
	        }
	        
	        $whois_reply .= "\n\r";
	        
		}else{
			
			$whois_reply .= "Domain ".$DOMAIN_lookup." is not registered.\n\r";
			$whois_reply .= "\n\r";
			
		}
        
        $obj->write($socket_id, $channel_id, $whois_reply);
        $obj->close($socket_id, $channel_id);
        
};

// Set IP:PORT to listen to and create TCP LISTEN socket 
$socket = new SuperSocket(array("*:43"));
$socket->assign_callback("DATA_SOCKET_CHANNEL", "whois_reply");
$socket->start();

//Drop root privileges
$USER_DETAILS = posix_getpwnam($CONF['WHOIS_USER']);
if ($USER_DETAILS){
	posix_setuid($USER_DETAILS['uid']);
	posix_setgid($USER_DETAILS['gid']);
}else{
	//user not found
	System_Daemon::err("User ".$CONF['WHOIS_USER']." does not exist. Quitting...");
	$socket->stop();
	System_Daemon::stop();
}

//Keep running the daemon
$socket->loop();


//Daemon end
System_Daemon::stop();
?>
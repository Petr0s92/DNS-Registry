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

// Check if we are run from cli
if(php_sapi_name() != "cli"){
	echo "This program requires to be run via terminal (php-cli)\n";
	exit(-1);
}

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
require("System/Daemon.php");

// Setup
$options = array(
	'appName' => 'whoisd',
	'appDir' => dirname(__FILE__),
	'appDescription' => "DNS Registry Panel - WHOIS Server",
	'sysMaxExecutionTime' => '0',
	'sysMaxInputTime' => '0',
	'logVerbosity' => '3'
);

// Set setup options
System_Daemon::setOptions($options);


// Run in background
System_Daemon::start();


//Include system files
require_once(dirname(__FILE__)."/includes/config.php");
require_once(dirname(__FILE__)."/includes/functions.php");

//Close initial MySQL connection
mysql_close($db);

// handle client disconnect
function closeClient($i){
	global $client;

	//print "Closing client[$i] \n";
	socket_close($client[$i]);
	$client[$i] = null;
}

//Send results to client
function handle_client($allclient, $socket, $buf) {
	global $CONF;
	
	//MySQL Connection script
	$db = @mysql_connect( $CONF['db_host'], $CONF['db_user'], $CONF['db_pass'] );
	@mysql_select_db($CONF['db'],$db);	

	$DOMAIN_lookup = trim($buf);

	// Select domain and owner from database
	$SELECT_DOMAIN = mysql_query("SELECT user_id, created, change_date, type, domain_id FROM records WHERE name = '".mysql_real_escape_string($DOMAIN_lookup)."' AND ( type = 'NS' OR type = 'A' ) LIMIT 0,1 ", $db);
	$DOMAIN = mysql_fetch_array($SELECT_DOMAIN);

	$SELECT_OWNER = mysql_query("SELECT username, nodeid FROM users WHERE id = '".$DOMAIN['user_id']."' ", $db);
	$OWNER = mysql_fetch_array($SELECT_OWNER);

	//Prepare reply header
	$whois_reply  = "% \n";
	$whois_reply .= "% WHOIS Server for: ".$CONF['APP_NAME']."\n";
	$whois_reply .= "% For more information regarding this WHOIS service\n";
	$whois_reply .= "% please visit ".$CONF['WHOIS_URL']."\n";
	$whois_reply .= "% \n";

	$whois_reply .= "% WHOIS ".$DOMAIN_lookup."\n";
	$whois_reply .= "% \n";
	$whois_reply .= "\n";

	//Domain exists, prepare reply
	if (mysql_num_rows($SELECT_DOMAIN)){

		$whois_reply .= "Domain: ".$DOMAIN_lookup." \n";
		$whois_reply .= "\n";
		$whois_reply .= "Registration Date: \n";
		$whois_reply .= "\t".date("d-m-Y g:i a",$DOMAIN['created'])."\n";
		$whois_reply .= "\n";
		$whois_reply .= "Updated Date: \n";
		$whois_reply .= "\t".date("d-m-Y g:i a",$DOMAIN['change_date'])."\n";
		$whois_reply .= "\n";
		
		//Show user & nameservers only if domain is delegated.
		if ($DOMAIN['user_id'] > 0 ){
			if ($OWNER['username'] || $OWNER['nodeid']){
				$whois_reply .= "Registrant:\n";
			}
			if ($OWNER['username']){			
				$whois_reply .= "\tUsername: ".$OWNER['username']."\n";
			}
			if ($OWNER['nodeid']){
				$whois_reply .= "\tNode ID: #".$OWNER['nodeid']."\n";
			}
			if ($OWNER['username'] || $OWNER['nodeid']){
				$whois_reply .= "\n";
			}
		
			$whois_reply .= "Name Servers:\n";

			//Select domain nameservers
			$SELECT_NS = mysql_query("SELECT content FROM records WHERE name = '".mysql_real_escape_string($DOMAIN_lookup)."' AND type='NS' ORDER BY content ASC", $db);
			while ($NS = mysql_fetch_array($SELECT_NS)){
				$whois_reply .= "\t".$NS['content']."\n";
			}
		}else{
			$whois_reply .= "This is a System domain.\n";
		}

		$whois_reply .= "\n";

	}else{
	//Domain does not exist

		$whois_reply .= "Domain ".$DOMAIN_lookup." is not registered.\n";
		$whois_reply .= "\n";

	}

	// send CR/LF to client	
	$whois_reply .= "\n\r";

	// Send the reply to client
	socket_write($allclient[$socket], $whois_reply, strlen($whois_reply));
	
	//Terminate MySQL connection
	mysql_close($db);

}


// set up the file descriptors and sockets...
// $listenfd only listens for a connection, it doesn't handle anything
// but initial connections, after which the $client array takes over...

$listenfd = socket_create(AF_INET, SOCK_STREAM, 0);
if ($listenfd){
	System_Daemon::info("Daemon Started");
}else{
	System_Daemon::err("Cannot create socket!");
	System_Daemon::stop();
	die();
}

// Set socket options
socket_setopt($listenfd, SOL_SOCKET, SO_REUSEADDR, 1);

if (!socket_bind($listenfd, $CONF['WHOIS_ADDRESS'], $CONF['WHOIS_PORT'])){
	socket_close($listenfd);
	System_Daemon::err("Couldn't bind socket to ".$CONF['WHOIS_ADDRESS'].":".$CONF['WHOIS_PORT']."!");
	System_Daemon::stop();
	die();
}

// Set listen queue length
socket_listen($listenfd, $CONF['WHOIS_LISTENQ']);


//Drop root privileges
$USER_DETAILS = posix_getpwnam($CONF['WHOIS_USER']);
if ($USER_DETAILS){
	posix_setuid($USER_DETAILS['uid']);
	posix_setgid($USER_DETAILS['gid']);
}else{
	//user not found
	System_Daemon::err("User ".$CONF['WHOIS_USER']." does not exist. Quitting...");
	socket_close($listenfd);
	System_Daemon::stop();
}

// set up our clients. After listenfd receives a connection,
// the connection is handed off to a $client[]. $maxi is the
// set to the highest client being used, which is somewhat
// unnecessary, but it saves us from checking each and every client
// if only, say, the first two are being used.

$maxi = -1;
for ($i = 0; $i < $CONF['WHOIS_MAXCONN']; $i++){
	$client[$i] = null;
}

// Loop as long as the daemon is running fine (no SIGTERM etc)
while(!System_Daemon::isDying()){

	$rfds[0] = $listenfd;

	for ($i = 0; $i < $CONF['WHOIS_MAXCONN']; $i++){
		if ($client[$i] != null){
			$rfds[$i + 1] = $client[$i];
		}
	}

	// block indefinitely until we receive a connection...
	$nready = socket_select($rfds, $null, $null, null);

	// if we have a new connection, stick it in the $client array,
	if (in_array($listenfd, $rfds)){

		//print "Listenfd heard something, setting up new client\n";
		for ($i = 0; $i < $CONF['WHOIS_MAXCONN']; $i++){
			if ($client[$i] == null){
				$client[$i] = socket_accept($listenfd);
				socket_setopt($client[$i], SOL_SOCKET, SO_REUSEADDR, 1);
				//print "Accepted Connection from client[$i]\n";
				break;
			}

			if ($i == $CONF['WHOIS_MAXCONN'] - 1){
				trigger_error("Too many clients", E_USER_ERROR);
				exit;
			}
		}
		
		if ($i > $maxi){
			$maxi = $i;
		}

		if (--$nready <= 0){
			continue;
		}
	}


	// check the clients for incoming data.
	for ($i = 0; $i <= $maxi; $i++){
		if ($client[$i] == null){
			continue;
		}

		if (in_array($client[$i], $rfds)){

			$n = trim(socket_read($client[$i], $CONF['WHOIS_MAXLENGTH']));

			if (!$n){
				//client did not send a query. disconnect
				closeClient($i);
			}else{
				// a client sent some data, pass it to handle_client()
				for ($j = 0; $j <= $maxi; $j++){
					if ($j == $i){
						handle_client($client, $i, $n);
						closeClient($i);
					}
				}
			}

			if  (--$nready <= 0){
				break;
			}
		}
	}

}

//If we get to this point something went wrong. Close socket and stop daemon.
socket_close($listenfd);

//Daemon end
System_Daemon::stop();
?>
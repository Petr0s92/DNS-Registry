#!/usr/bin/php
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



// Script arguments
// provision_root_ns.php "ANYCAST_NS_IP" "UNICAST_NS_IP" "ANYCAST_CACHE_IP" "ROOT_NS_NAME" "CP_URL" "TSIG_KEY" "USER_SSH_KEY"

//Assign script arguments to vars
$ANYCAST_NS_IP    = trim($argv[1]);
$UNICAST_NS_IP    = trim($argv[2]);
$ANYCAST_CACHE_IP = trim($argv[3]);
$ROOT_NS_NAME     = trim($argv[4]);
$CP_URL           = trim($argv[5]);
$TSIG_KEY         = trim($argv[6]);
$USER_SSH_KEY     = trim($argv[7]);

//Validate input
if(!filter_var($ANYCAST_NS_IP, FILTER_VALIDATE_IP)){
	exit("Invalid ANYCAST_NS_IP\n");
}

if(!filter_var($UNICAST_NS_IP, FILTER_VALIDATE_IP)){
	exit("Invalid UNICAST_NS_IP\n");
}

if(!filter_var($ANYCAST_CACHE_IP, FILTER_VALIDATE_IP)){
	exit("Invalid ANYCAST_CACHE_IP\n");
}

if (!preg_match("/^(?!-)^(?!\.)[a-z0-9-\.]{1,63}(?<!-)(?<!\.)$/", $ROOT_NS_NAME)) {
	exit("Invalid ROOT_NS_NAME\n");
}

if (!$CP_URL) {
	exit("Invalid Control Panel URL.\n");
}

if (strlen($TSIG_KEY) <= 15) {
	exit("Invalid TSIG_KEY. Need at least 16 chars key.\n");
}

if (strlen($USER_SSH_KEY) > 1 && preg_match("/^(ssh-rsa|ssh-dss) AAAA[0-9A-Za-z+\\/]+[=]{0,3}/", $USER_SSH_KEY) != 1 ){
	exit("Invalid USER_SSH_KEY. Enter a valid User SSH Public Key.\n");
}


//All input validated, let do this!


//Configure interfaces file
$INTERFACES_FILE = "auto lo
iface lo inet loopback

auto eth0
iface eth0 inet dhcp


# ROOT NS ANYCAST IP
auto lo:1
iface lo:1 inet static
        address ".$ANYCAST_NS_IP."
        netmask 255.255.255.255

# ROOT NS UNICAST IP
auto lo:2
iface lo:2 inet static
        address ".$UNICAST_NS_IP."
        netmask 255.255.255.255

# ROOT NS CACHING NS ANYCAST IP
auto lo:3
iface lo:3 inet static
        address ".$ANYCAST_CACHE_IP."
        netmask 255.255.255.255
";

file_put_contents ("/etc/network/interfaces", $INTERFACES_FILE);

//Bring up UNICAST IP to be able to fetch the SSH Keys from the control panel later on
system ("ifup lo:2");


//Set hostname
$hostname_parts = explode(".", $ROOT_NS_NAME);
$hostname_parts_rev = array_reverse($hostname_parts);
$hostname_parts_rev[0] = false;
$hostname_parts = array_reverse($hostname_parts_rev);
$hostname = $hostname_parts[0];

file_put_contents ("/etc/hostnaeme", $hostname);



//Check if USB Stick is present and create new partition
if (file_exists("/dev/sda")){
		
	//Delete all partitions and create a new linux one.	
	system ('echo -e "o\nn\np\n1\n\n\nw" | fdisk /dev/sda');
	
	//Format new partition
	system ('mkfs.ext4 /dev/sda1');
	
	//Add new partition to fstab
	system ("echo \"/dev/sda1 /data ext4 errors=remount-ro,noatime 0 0\">> /etc/fstab");
	
	//Mount new partition
	system ("mount /data");
	
}

//Copy data-skel to /data
system("cp -a /data-skel/* /data/");



//Configure NSD
$NSD_CONF = file_get_contents("/etc/nsd/nsd.conf");
$NSD_CONF = str_replace("--YOUR--ROOT-NS--ANYCAST--IP--HERE--", $ANYCAST_NS_IP, $NSD_CONF); 
$NSD_CONF = str_replace("--YOUR--ROOT-NS--UNICAST--IP--HERE--", $UNICAST_NS_IP, $NSD_CONF); 
$NSD_CONF = str_replace("--YOUR--ROOT--NS--NAME--", $ROOT_NS_NAME, $NSD_CONF); 
$NSD_CONF = str_replace("--YOUR--TSIG--KEY--NAME--", $ROOT_NS_NAME, $NSD_CONF); 
$NSD_CONF = str_replace("--YOUR--TSIG--KEY--", $TSIG_KEY, $NSD_CONF);
file_put_contents("/etc/nsd/nsd.conf", $NSD_CONF); 


//Configure BIND
$BIND_CONF = file_get_contents("/etc/bind/named.conf.options");
$BIND_CONF = str_replace("--YOUR--ROOT-NS--CACHING--ANYCAST--IP--HERE--", $ANYCAST_CACHE_IP, $BIND_CONF); 
$BIND_CONF = str_replace("--YOUR--ROOT-NS--CACHING--UNICAST--IP--HERE--", $UNICAST_NS_IP, $BIND_CONF); 
file_put_contents("/etc/bind/named.conf.options", $BIND_CONF); 


//Configure nsd_superslave.pl
$SUPERSLAVE_CONF = file_get_contents("/usr/local/bin/nsd_superslave.pl");
$SUPERSLAVE_CONF = str_replace("--YOUR--ROOT-NS--UNICAST--IP--HERE--", $UNICAST_NS_IP, $SUPERSLAVE_CONF); 
file_put_contents("/usr/local/bin/nsd_superslave.pl", $SUPERSLAVE_CONF); 


//Configure nsd_cleanup_zones.php
$CLEANUP_ZONES_CONF = file_get_contents("/usr/local/bin/nsd_cleanup_zones.php");
$CLEANUP_ZONES_CONF = str_replace("--YOUR--ROOT-NS--UNICAST--IP--HERE--", $UNICAST_NS_IP, $CLEANUP_ZONES_CONF); 
file_put_contents("/usr/local/bin/nsd_cleanup_zones.php", $CLEANUP_ZONES_CONF); 

//Configure nsd_superslave.pl
$SYNC_ZONES_CONF = file_get_contents("/usr/local/bin/bind_sync_zones.php");
$SYNC_ZONES_CONF = str_replace("--YOUR--ROOT-NS--UNICAST--IP--HERE--", $UNICAST_NS_IP, $SYNC_ZONES_CONF); 
$SYNC_ZONES_CONF = str_replace("https://your.domain/path/to", $CP_URL, $SYNC_ZONES_CONF); 
file_put_contents("/usr/local/bin/bind_sync_zones.php", $SYNC_ZONES_CONF); 


//Fetch SSH Public Keys
$curl = curl_init($CP_URL);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
if (stristr($CP_URL,"https://")){
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
}
curl_setopt($curl, CURLOPT_INTERFACE, $UNICAST_NS_IP);
$curl_result = curl_exec($curl);
curl_close($curl);

$SSH_KEYS = json_decode($curl_result, true);
$formatted_keys = '';
if (isset($SSH_KEYS)){
	for ($i = 0; $i <= count($SSH_KEYS) -1; $i++) {
		if ($SSH_KEYS[$i]['ssh_key']){
			$formatted_keys .= $SSH_KEYS[$i]['ssh_key'] . " " . $SSH_KEYS[$i]['username'] . "\n";
		}
	}
}

//Add new keys to root
file_put_contents("/root/.ssh/authorized_keys", $formatted_keys, FILE_APPEND);

//Add new user key to 'owner' account
file_put_contents("/home/owner/.ssh/authorized_keys", $USER_SSH_KEY);



//All services configured, enable them on boot
system("update-rc nsd defaults");
system("update-rc bind9 defaults");
system("update-rc cron defaults");

//enable superslave on boot
$SUPERSLAVE_BOOT = file_get_contents("/etc/rc.local");
$SUPERSLAVE_BOOT = str_replace("#screen -dmS nsd_superslave /usr/local/bin/nsd_superslave.pl", "screen -dmS nsd_superslave /usr/local/bin/nsd_superslave.pl", $SUPERSLAVE_BOOT); 
file_put_contents("/etc/rc.local", $SUPERSLAVE_BOOT);

 
//Configuration Complete! Reboot to apply!

system ("init 6");

?>
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

//CONFIGURATION
$CONF['unicast_ip'] = '10.1.1.214';
$CONF['metazone']   = 'meta.meta'; // leave this as is
$CONF['nsdcontrol'] = '/usr/local/sbin/nsd-control';
$CONF['zones_path'] = '/data/tmp/zones';
$CONF['deleted_zones'] = '/data/tmp/deleted_zones.txt';
$CONF['debug'] = false;
//END OF CONFIGURATION


$deleted_zones = file($CONF['deleted_zones']);
$deleted_zones = array_map('trim', $deleted_zones);

require_once 'Net/DNS.php';

$resolver = new Net_DNS_Resolver();
$resolver->debug = $CONF['debug'];
$resolver->nameservers = array($CONF['unicast_ip']);
$response = $resolver->axfr($CONF['metazone']);

if (count($response)) {
	foreach ($response as $rr) {
  		if ($rr->type == 'TXT'){
      		$record = str_replace(".".$CONF['metazone'], "", $rr->name);
      		if ($record && !in_array($record, $deleted_zones)){
                exec ($CONF['nsdcontrol'] . " delzone " . $record, $delzone);
				if ($delzone[0] == 'ok'){
					if (file_exists($CONF['zones_path'] . "/" . $record)){
						exec ("rm -rf " . $CONF['zones_path'] . "/" . $record);
					}
					file_put_contents($CONF['deleted_zones'], $record."\n", FILE_APPEND);
					echo "Deleted zone " . $record . " from NSD.\n";
				}elseif (strstr($delzone[0], "not present")){
					file_put_contents($CONF['deleted_zones'], $record."\n", FILE_APPEND);
					echo "Zone " . $record . " was already deleted from NSD. Updated DB.\n";										
				}else{
					echo "Could not delete zone " . $record . " from NSD\n";
				}			
        	}else{
				if ($CONF['debug']){
					echo "Zone " . $record. " is already deleted\n";
				}
        	}
    	}
	}
}else{
	echo "Could not AXFR zone " . $CONF['metazone'] . " from " . $CONF['unicast_ip'] . "\n";
}


?>
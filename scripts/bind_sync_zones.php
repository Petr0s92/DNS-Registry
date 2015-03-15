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
$CONF['control_panel_url'] = 'https://www.own/registry/index.php?fetch_slaves_tlds=1';
$CONF['sqlite_db'] = '/data/bind/zones.sqlite';
$CONF['rndc'] = '/usr/sbin/rndc';
$CONF['bind_zones_path'] = '/data/bind/var/cache/bind';
//END OF CONFIGURATION


//Initialize SQLite DB
class MyDB extends SQLite3{
	function __construct(){
		global $CONF;
		$this->open($CONF['sqlite_db']);
	}
}

$db = new MyDB();
if(!$db) die ($db->lastErrorMsg());

$sql = "CREATE TABLE IF NOT EXISTS zones (
		name varchar(255) PRIMARY KEY, 
        type text CHECK(type IN ('S', 'T')), 
        masters varchar(255) NOT NULL
        )";
$ret = $db->exec($sql);

if (!$ret) die("Cannot initialize database." . $db->lastErrorMsg());


//Fetch Zones from Control Panel
$curl = curl_init($CONF['control_panel_url']);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
if (stristr($CONF['control_panel_url'],"https://")){
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
}
curl_setopt($curl, CURLOPT_INTERFACE, $CONF['unicast_ip']);
$curl_result = curl_exec($curl);
curl_close($curl);

$ZONES_DATA = json_decode($curl_result, true);

$zone_name_d = false;
$need_reload = false;
$need_flush = false;

//Process New Slave Zones
if (isset($ZONES_DATA['slaves'])){
	for ($i = 0; $i <= count($ZONES_DATA['slaves']) -1; $i++) {
		$zone_name = $ZONES_DATA['slaves'][$i]['name'];
		$zone_name_d[] = $ZONES_DATA['slaves'][$i]['name']; 	
	    if ($zone_name){	
			//Check if zone is in local database
			$rows = $db->query("SELECT COUNT(*) AS count FROM zones WHERE name = '".SQLite3::escapeString($zone_name)."' AND type = 'S' ");
			$row = $rows->fetchArray();
			$ZONE = $row['count'];
			
			//If zone does not exist on this system, proceed adding it to BIND
			if (!$ZONE && !file_exists($CONF['bind_zones_path']."/".$zone_name.".slave") && !file_exists($CONF['bind_zones_path']."/".$zone_name.".stub") ){
				//Prepare masters
				$masters = '';
				$masters_q = '';	
				for ($e = 0; $e <= count($ZONES_DATA['slaves'][$i]['masters']) -1; $e++) {
				    $masters .= $ZONES_DATA['slaves'][$i]['masters'][$e] . ";\n\t\t";
				    $masters_q .= $ZONES_DATA['slaves'][$i]['masters'][$e];
				    if ( $e < count($ZONES_DATA['slaves'][$i]['masters']) -1 ) {
						$masters_q .= ",";
					}
				}
				
				//Add slave zone to BIND
				$rndc_cmd = $CONF['rndc'] . ' addzone '.$zone_name.' in "
	{
		type slave;
		file \"'.$zone_name.'.slave\";
		notify no;
		masters {
			'.$masters.'
		};
	};"';
				
				$addzone=exec($rndc_cmd); 

				if (!$addzone){
					$INSERT = $db->exec("INSERT INTO zones (name, type, masters) VALUES ('".SQLite3::escapeString($zone_name)."', 'S', '".SQLite3::escapeString($masters_q)."')");
   					if ($INSERT){
   						$need_flush = true;
						echo "New Slave Zone '".$zone_name."' was added to BIND.\n";
					}else{
						echo "An error occured adding new Slave Zone '".$zone_name."' to DB. ".$db->lastErrorMsg()."\n";
					}				
				}else{
					echo "An error occured adding new Slave Zone '".$zone_name."' to BIND.\n";				
				} 
			}
		}	
	}
}

//Process New TLDs
if (isset($ZONES_DATA['tlds'])){
	for ($i = 0; $i <= count($ZONES_DATA['tlds']) -1; $i++) {
		$zone_name = $ZONES_DATA['tlds'][$i]['name'];	
	    $zone_name_d[] = $ZONES_DATA['tlds'][$i]['name']; 	
	    if ($zone_name){	
			//Check if zone is in local database
			$rows2 = $db->query("SELECT COUNT(*) AS count FROM zones WHERE name = '".SQLite3::escapeString($zone_name)."' AND type = 'T' ");
			$row2 = $rows2->fetchArray();
			$ZONE = $row2['count'];
			
			//If zone does not exist on this system, proceed adding it to BIND
			if (!$ZONE && !file_exists($CONF['bind_zones_path']."/".$zone_name.".slave") && !file_exists($CONF['bind_zones_path']."/".$zone_name.".stub") ){
				//Prepare masters
				$masters = '';
				$masters_q = '';	
				for ($e = 0; $e <= count($ZONES_DATA['tlds'][$i]['masters']) -1; $e++) {
				    $masters .= $ZONES_DATA['tlds'][$i]['masters'][$e] . ";\n\t\t";
				    $masters_q .= $ZONES_DATA['tlds'][$i]['masters'][$e];
				    if ( $e < count($ZONES_DATA['tlds'][$i]['masters']) -1 ) {
						$masters_q .= ",";
					}
				}
				
				//Add stub zone to BIND
				$rndc_cmd = $CONF['rndc'] . ' addzone '.$zone_name.' in "
	{
		type stub;
		file \"'.$zone_name.'.stub\";
		masters {
			'.$masters.'
		};
	};"';
				
				$addzone=exec($rndc_cmd);
				
				if (!$addzone){
					$INSERT = $db->exec("INSERT INTO zones (name, type, masters) VALUES ('".SQLite3::escapeString($zone_name)."', 'T', '".SQLite3::escapeString($masters_q)."')");
   					if ($INSERT){
						$need_flush = true;
						echo "New Stub (TLD) Zone '".$zone_name."' was added to BIND.\n";
					}else{
						echo "An error occured adding new Stub (TLD) Zone '".$zone_name."' to DB. ".$db->lastErrorMsg()."\n";
					}				
				}else{
					echo "An error occured adding new Stub (TLD) Zone '".$zone_name."' to BIND.\n";				
				} 
			}
		}	
	}
}


//Process Deleted Zones
$zone_names = '';
for ($i = 0; $i <= count($zone_name_d) -1; $i++) {
		if ($zone_name_d[$i]){
		$zone_names .= "'".$zone_name_d[$i]."'";
		if ($i < count($zone_name_d) -1){
			$zone_names .= ",";
		}
	}	
}

$zones = $db->query("SELECT name, type FROM zones WHERE name NOT IN ( ".$zone_names." ) ");
if(!$zones){
      echo $db->lastErrorMsg();
}
while($zone = $zones->fetchArray(SQLITE3_ASSOC) ){
	if ($zone['name']){
		$delzone = exec($CONF['rndc'] . " delzone " . $zone['name']);
		if (!$delzone){
			
			$db->query("DELETE FROM zones WHERE name = '".SQLite3::escapeString($zone['name'])."' ");			
			
			if ($zone['type'] == 'T'){
				$type = 'stub';
			}elseif ($zone['type'] == 'S'){
				$type = 'slave';
			}
			if ($type){
				exec ("rm -rf " . $CONF['bind_zones_path'] . "/" . $zone['name'] . "." . $type);
				$need_reload = true;
				echo ucfirst($type) . " zone ".$zone['name']." was deleted from BIND.\n";
			}else{
				echo "An error occured while deleting zone file " . $zone['name'] . ". Zone was removed from BIND & DB.\n"; 
			}
		}else{
			echo "An error occured while deleting zone " . $zone['name'] . " from BIND.\n"; 
		}
	}	
}

if ($need_flush == true){
	exec($CONF['rndc'] . " flush");
}
if ($need_reload == true){
	exec($CONF['rndc'] . " reload");
}


//Terminate sqlite handle 
$db->close();

?>
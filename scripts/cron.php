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

error_reporting(E_ALL ^ E_NOTICE);

//Include system files
require_once(dirname(__FILE__)."/../includes/config.php");
require_once(dirname(__FILE__)."/../includes/functions.php");
require_once 'Net/DNS.php';



//Check users that haven't logged in for LAST_LOGIN_MAX
$time = time() - ($CONF['LAST_LOGIN_MAX'] * 86400);
$SELECT_USERS = mysql_query("SELECT username, last_login, id FROM users WHERE last_login > '0' AND last_login < '".$time. "' AND active = '1' AND suspended = '0' AND admin_level != 'admin' ", $db);
while ($USERS = mysql_fetch_array($SELECT_USERS)){
	
	//Suspend account
	mysql_query("UPDATE records SET disabled = '1' WHERE user_id = '".$USERS['id']."' ", $db);
	mysql_query("UPDATE users SET suspended = UNIX_TIMESTAMP() WHERE id = '".$USERS['id']."' ", $db);
	
	//Send email notification that the account just got suspended
	
	//no need to send mail here, it will be sent further down on suspend checks
	echo "User " . $USERS['username'] . " hasn't logged in for over " . $CONF['LAST_LOGIN_MAX'] . " days. Last recorded login at: ". date("d-m-Y g:i a", $USERS['last_login']) ." Account suspended.\n";	
		
}



//Check suspended accounts that have passed LAST_LOGIN_MAX_SUSPEND
$SELECT_USERS = mysql_query("SELECT username, last_login, id, suspended FROM users WHERE last_login > '0' AND active = '1' AND suspended > '0' AND admin_level != 'admin' ", $db);
while ($USERS = mysql_fetch_array($SELECT_USERS)){

	$days_suspended = round((time() - $USERS['suspended']) / 86400);
	if ($days_suspended > $CONF['LAST_LOGIN_MAX_SUSPEND']){
	
		//DELETE ACCOUNT
		
		//Delete user hosted domains
	    $SELECT_USER_DOMAINS = mysql_query("SELECT domain_id FROM records WHERE user_id = '".$USERS['id']."' AND type = 'SOA' ", $db);
		while ($USER_DOMAINS = mysql_fetch_array($SELECT_USER_DOMAINS)){
			mysql_query("DELETE FROM domains WHERE id = '".$USER_DOMAINS['domain_id']."' ", $db);
		}
		
		//Delete user delegated domains
		mysql_query("DELETE FROM records WHERE user_id = '".$USERS['id']."' ", $db);
		
		//Delete user account
		mysql_query("DELETE FROM users WHERE id = '".$USERS['id']."' ", $db);
		
		//Delete user notifications
		mysql_query("DELETE FROM users_notifications WHERE user_id = '".$USERS['id']."' ", $db);
		
		//Send email notification that the account just got suspended
		echo "User " . $USERS['username'] . " hasn't logged in for over " . ($CONF['LAST_LOGIN_MAX'] + $CONF['LAST_LOGIN_MAX_SUSPEND']) . " days. Account was already suspended and passed the suspend period. DELETING. Sending email.\n";	
	
	}
		
}



//Check users that are getting close to LAST_LOGIN_MAX to notify them before account suspend
$time = time() - (($CONF['LAST_LOGIN_MAX'] - $CONF['LAST_LOGIN_MAX_START_ALERTS']) * 86400);
$SELECT_USERS = mysql_query("SELECT username, last_login, id FROM users WHERE last_login > '0' AND last_login < '".$time. "' AND active = '1' AND suspended = '0' AND admin_level != 'admin' ", $db);
while ($USERS = mysql_fetch_array($SELECT_USERS)){
		
    $last_login_days =  round(( time() - $USERS['last_login'] ) / 86400 );
	$days = $CONF['LAST_LOGIN_MAX_START_ALERTS'] - round ( $last_login_days - ($CONF['LAST_LOGIN_MAX'] - $CONF['LAST_LOGIN_MAX_START_ALERTS']) );

	//now check if user has already had an email sent in the last month.
	
	//check in users_notifications table 
	$SELECT_PAST_NOTIF = mysql_query("SELECT * FROM users_notifications WHERE user_id = '".$USERS['id']."' AND type = 'LAST_LOGIN_MAX_START_ALERTS' ORDER BY time DESC", $db);
	$PAST_NOTIF = mysql_fetch_array($SELECT_PAST_NOTIF);
	$TOTAL_SENT_NOTIF = mysql_num_rows($SELECT_PAST_NOTIF);
	
	if ($TOTAL_SENT_NOTIF > 0){
		$TOTAL_NOTIF = round ($CONF['LAST_LOGIN_MAX_START_ALERTS'] / $CONF['LAST_LOGIN_MAX_START_ALERTS_INTERVAL']);
	}else{
		$TOTAL_NOTIF = 1; 		
	}
	
	$last_sent_notif =  round(( time() - $PAST_NOTIF['time'] ) / 86400 );
	
	if ( ( $PAST_NOTIF['time'] < (time() - ($CONF['LAST_LOGIN_MAX_START_ALERTS_INTERVAL'] * 86400) ) && $TOTAL_SENT_NOTIF < $TOTAL_NOTIF ) || $days <= '1' && $last_sent_notif >= '1' ){
        
        //SEND EMAIL NOTIFICATION
		mysql_query("INSERT INTO users_notifications (`user_id`, `type`, `time`) VALUES ('".$USERS['id']."', 'LAST_LOGIN_MAX_START_ALERTS' , UNIX_TIMESTAMP() ) ", $db);
		echo "User " . $USERS['username'] . " hasn't logged in for " . $last_login_days . " days. Last recorded login at: ". date("d-m-Y g:i a",$USERS['last_login']) .". User has ".$days." days before account suspend. Sending email.\n";	
				
	}else{
		
		//DONT SEND EMAIL - IT'S NOT TIME YET
		echo "User " . $USERS['username'] . " was already informed. Last notification sent at: ". date("d-m-Y g:i a",$PAST_NOTIF['time']) .". User has ".$days." days before account suspend.\n";
	}		
	

}



//Check users that suspended and notify them that their account will be deleted soon
$SELECT_USERS = mysql_query("SELECT username, last_login, id, suspended FROM users WHERE last_login > '0' AND active = '1' AND suspended > '0' AND admin_level != 'admin' ", $db);
while ($USERS = mysql_fetch_array($SELECT_USERS)){
		
    $suspend_days =  round(( time() - $USERS['suspended'] ) / 86400 );
    $days = $CONF['LAST_LOGIN_MAX_SUSPEND'] - $suspend_days;

	//now check if user has already had an email sent in the last month.
	
	//check in users_notifications table 
	$SELECT_PAST_NOTIF = mysql_query("SELECT * FROM users_notifications WHERE user_id = '".$USERS['id']."' AND type = 'LAST_LOGIN_MAX_SUSPEND' ORDER BY time DESC", $db);
	$PAST_NOTIF = mysql_fetch_array($SELECT_PAST_NOTIF);
	$TOTAL_SENT_NOTIF = mysql_num_rows($SELECT_PAST_NOTIF);
	
	if ($TOTAL_SENT_NOTIF > 0){
		$TOTAL_NOTIF = round ($CONF['LAST_LOGIN_MAX_SUSPEND'] / $CONF['LAST_LOGIN_MAX_SUSPEND_ALERT_INTERVAL']);
	}else{
		$TOTAL_NOTIF = 1; 		
	}
	
	$last_sent_notif =  round(( time() - $PAST_NOTIF['time'] ) / 86400 );
	
	if ( ( $PAST_NOTIF['time'] < (time() - ($CONF['LAST_LOGIN_MAX_SUSPEND_ALERT_INTERVAL'] * 86400) ) && $TOTAL_SENT_NOTIF < $TOTAL_NOTIF ) || $days <= '1' && $last_sent_notif >= '1' ){
        
        //SEND EMAIL NOTIFICATION
		mysql_query("INSERT INTO users_notifications (`user_id`, `type`, `time`) VALUES ('".$USERS['id']."', 'LAST_LOGIN_MAX_SUSPEND' , UNIX_TIMESTAMP() ) ", $db);
		echo "User " . $USERS['username'] . " is supended! User has ".$days." days before account delete. Sending email.\n";	
				
	}else{
		
		//DONT SEND EMAIL - IT'S NOT TIME YET
		echo "User " . $USERS['username'] . " was already informed. Last notification sent at: ". date("d-m-Y g:i a",$PAST_NOTIF['time']) .". User has ".$days." days before account delete.\n";
	}		
	

}


// Check for new domains that havent been activated and passed NEW_DOMAIN_ENABLE_PERIOD
$SELECT_DOMAINS = mysql_query("SELECT * FROM records WHERE type = 'NS' AND user_id > '0' AND disabled = '1' GROUP BY name ", $db);
while ($DOMAINS = mysql_fetch_array($SELECT_DOMAINS)){
                                                                    
	//Check if domain is disabled due to periodic validation failed checks and ignore because it's not newly registered
	$SELECT_OLD_DOMAIN = mysql_query("SELECT 1 FROM users_notifications WHERE user_id = '".$DOMAINS['user_id']."' AND domain = '".$DOMAINS['name']."' AND type = 'DOMAIN_REP_FAILED_VAL' ", $db);
	if (!mysql_num_rows($SELECT_OLD_DOMAIN)){
		
		$SELECT_ISHOSTED = mysql_query("SELECT id FROM domains WHERE name = '".$DOMAINS['name']."' ", $db);
		$HOSTEDID = mysql_fetch_array($SELECT_ISHOSTED);
		$ISHOSTED = mysql_num_rows($SELECT_ISHOSTED);
		
		$SELECT_LAST_UPDATED  = mysql_query("SELECT `change_date` FROM `records` WHERE name LIKE '%".$DOMAINS['name']."' ORDER BY change_date DESC LIMIT 0, 1",$db);
		$LAST_UPDATED = mysql_fetch_array($SELECT_LAST_UPDATED);

		$o=0;
		$ns='';
		$SELECT_ROOT_NS = mysql_query("SELECT name FROM root_ns WHERE active = '1' ", $db);
		$ROOT_NS_TOTAL = mysql_num_rows($SELECT_ROOT_NS);
		while ($ROOT_NS = mysql_fetch_array($SELECT_ROOT_NS)){
			$o++;
			$ns .= "'".$ROOT_NS['name']."'";
			if ($o < $ROOT_NS_TOTAL){
				$ns .=", ";
			}
		}
						  
		$SELECT_DOMAIN_RECORDS = mysql_query("SELECT 1 FROM records WHERE domain_id = '".$HOSTEDID['id']."'  AND type != 'SOA' AND content NOT IN (".$ns.") AND user_id > '0' " , $db);
		$DOMAIN_RECORDS = mysql_num_rows($SELECT_DOMAIN_RECORDS);
			
		// If domain is new and disabled for more that NEW_DOMAIN_ENABLE_PERIOD days then delete.
		$days_disabled = round((time() - $LAST_UPDATED['change_date']) / 86400);
		
		//echo "Days disabled: " .$days_disabled ."\n";
		
	    $days_disabled_left =  round(( time() - $LAST_UPDATED['change_date'] ) / 86400 );
	    $days_left = $CONF['NEW_DOMAIN_ENABLE_PERIOD'] - $days_disabled_left;	
		
		//echo "Days left: " .$days_left ."\n";
		
	    if ($days_disabled> $CONF['NEW_DOMAIN_ENABLE_PERIOD'] ){
		
			//Domain did not get enabled in time. Deleting...
			mysql_query("DELETE FROM records WHERE name = '".$DOMAINS['name']."' ", $db);
			mysql_query("DELETE FROM records WHERE name LIKE '%.".$DOMAINS['name']."' ", $db);
			mysql_query("DELETE FROM domains WHERE name = '".$DOMAINS['name']."' ", $db);
			mysql_query("DELETE FROM users_notifications WHERE domain = '".$DOMAINS['name']."' ", $db);
			
			//Send mail to user about domain delete	
		    echo "New Domain " . $DOMAINS['name'] . " was DELETED! User did not enable it before automatic delete. Sending email.\n";	
			
	    }else{
			
			//check in users_notifications table 
			$SELECT_PAST_NOTIF = mysql_query("SELECT * FROM users_notifications WHERE user_id = '".$DOMAINS['user_id']."' AND domain = '".$DOMAINS['name']."' AND type = 'NEW_DOMAIN_ENABLE_PERIOD' ORDER BY time DESC", $db);
			$PAST_NOTIF = mysql_fetch_array($SELECT_PAST_NOTIF);
			$TOTAL_SENT_NOTIF = mysql_num_rows($SELECT_PAST_NOTIF);
			
			$TOTAL_NOTIF = 1; 		

			$last_sent_notif =  round(( time() - $PAST_NOTIF['time'] ) / 86400 );
			
			//If time has come or it's last day send notification
			if ( ( $DOMAINS['change_date'] < (time() - (($CONF['NEW_DOMAIN_ENABLE_PERIOD'] - $CONF['NEW_DOMAIN_ENABLE_PERIOD_START_ALERT']) * 86400) ) && $TOTAL_SENT_NOTIF < $TOTAL_NOTIF ) || $days_left <= '1' && $last_sent_notif == '1' ){
		        
		        //SEND EMAIL NOTIFICATION
				mysql_query("INSERT INTO users_notifications (`user_id`, `domain`, `type`, `time`) VALUES ('".$DOMAINS['user_id']."', '".$DOMAINS['name']."', 'NEW_DOMAIN_ENABLE_PERIOD' , UNIX_TIMESTAMP() ) ", $db);
				echo "Domain " . $DOMAINS['name'] . " is about to be deleted! User has ".$days_left." days to enable it before automatic delete. Sending email.\n";	
						
			}		
					
	    }
    
	}
    
}



// Re-validate all domains and suspend those that fail for DOMAIN_REP_FAILED_VAL days in a row
$SELECT_DOMAINS = mysql_query("SELECT * FROM records WHERE type = 'NS' AND user_id > '0' AND disabled = '0' GROUP BY name ", $db);
while ($DOMAINS = mysql_fetch_array($SELECT_DOMAINS)){

	$SELECT_ISHOSTED = mysql_query("SELECT id FROM domains WHERE name = '".$DOMAINS['name']."' ", $db);
	$HOSTEDID = mysql_fetch_array($SELECT_ISHOSTED);
	$ISHOSTED = mysql_num_rows($SELECT_ISHOSTED);
	
	if (!$ISHOSTED){	
		//echo "Enabled domain to validate again: " . $DOMAINS['name'] . "\n";
		
		// VALIDATION CHECKS
		$DOMAIN_FAILED = false;
		
		$d = mysql_real_escape_string($DOMAINS['name'], $db);
				
   		$resolver = new Net_DNS_Resolver();
		//Set resolver options
		$resolver->debug = 0; // Turn on debugging output to show the query
		$resolver->usevc = 0; // Force the use of TCP instead of UDP
		$resolver->port = 53; // DNS Server port
		$resolver->recurse = 0; // Disable recursion
		$resolver->retry = $CONF['DNS_VALIDATE_WAIT']; // How long to wait for answer
		$resolver->retrans = $CONF['DNS_VALIDATE_RETRY']; // How many times to retry for answer

    	$SELECT_DOMAIN_NS = mysql_query("SELECT content FROM records WHERE name = '".$d."' AND type = 'NS' ORDER BY content ASC", $db);
    	$r=0;
		$all_domain_errors = array();
    	while($DOMAIN_NS = mysql_fetch_array($SELECT_DOMAIN_NS)){
    		$r++;
    		$domain_errors = array();
			
			$SELECT_NS_IP = mysql_query("SELECT content FROM records WHERE name = '".$DOMAIN_NS['content']."' AND type = 'A' ", $db);
			$NS_IP = mysql_fetch_array($SELECT_NS_IP);
				
			$OUR_TLD = getTLD($DOMAIN_NS['content']);
			
			if ($OUR_TLD){
			    //echo "<h4><img src='images/ico_info.png' align='absmiddle'> &nbsp;Checking Nameserver: <strong class='blue'>" .$DOMAIN_NS['content'] ." (".$NS_IP['content'].")</strong></h4>\n";
			}else{
				//echo "<h4><img src='images/ico_info.png' align='absmiddle'> &nbsp;Checking Nameserver: <strong class='blue'>" .$DOMAIN_NS['content'] ." (3rd Party TLD)</strong></h4>\n";
				$NS_IP['content'] = $DOMAIN_NS['content'];					
			}
			//echo "<div style='margin-left: 40px;'>\n";
							
			if ($DOMAIN_NS['content'] != 'unconfigured'){
				
				//Set resolver nameserver IP to use for lookup
				$resolver->nameservers = array($NS_IP['content']);

			    // Get SOA record from nameserver
				$response = $resolver->rawQuery($d, 'SOA');
				
				$aa = $response->header->aa;
				$rcode = $response->header->rcode;
				$ancount = $response->header->ancount;
				
               	//print_r($response);
			   	// Check if we got a response at all from the nameserver				
				if ($response){
					
					// Check if we got a valid response from the nameserver	(SERVFAIL?)			
					if ($rcode == 'NOERROR'){
						
						// Validate if response has the aa bit (authoritative)				
						if ($aa == 1 ){
						
							// Check if we got an answers from the nameserver (NXDOMAIN?)				
							if ($ancount > 0){
									
								// GET NS RECORDS
								$response = $resolver->rawQuery($d, 'NS');
								//print_r($response);
								for ($i = 0; $i <= count($response->answer); $i++) {
									$NS[$i] = $response->answer[$i];
								}	
								//echo "<img src='images/ico_valid.png' align='absmiddle' > <strong class='blue'>SOA</strong> record check <strong class='green'>[OK]</strong><br /><br />\n";
								//print_r($NS);
								
								//Compare received NS to registry NS
								$ns_error = false;					
								for ($i = 0; $i <= count($NS)-2; $i++) {
									$NS_AR = (array) $NS[$i];
									$NS_IN_DB = mysql_num_rows(mysql_query("SELECT 1 FROM records WHERE name = '".$d."' AND content = '".mysql_real_escape_string($NS_AR['nsdname'])."' ", $db));
									if (!$NS_IN_DB){
										$ns_error = true;
									}						
								}
								
								//Validate NS records and move to further checks					
								if ($ns_error == false){
									
									$NS_TOTAL = mysql_num_rows(mysql_query("SELECT 1 FROM records WHERE name = '".$d."' AND type = 'NS' " .$user_id, $db));
									//Check if we got the same quantity of NS records as configured in registry
									if ($NS_TOTAL == count($NS)-1){											
																			
										//echo "<img src='images/ico_valid.png' align='absmiddle' > <strong class='blue'>NS</strong> records check <strong class='green'>[OK]</strong><br /><br />\n";
										
										$glue_errors = array();
										
										//If NS records are TLD of ours, check Glue Records if needed
										if ($OUR_TLD){
											
											$SELECT_DOMAIN_NS_GLUES = mysql_query("SELECT content FROM records WHERE name = '".$d."' AND type = 'NS' ORDER BY content ASC", $db);
    										while($DOMAIN_NS_GLUES = mysql_fetch_array($SELECT_DOMAIN_NS_GLUES)){
    					                
												$local_ns_found = false;
									            
									            //Check if db NS record is part of the domain (so that we need to check it's A/Glue records)
												$dbns_parts = explode(".", $DOMAIN_NS_GLUES['content']);
												//print_r($dbns_parts);
												$dbns_parts[0] = false;
												$ns_parent_domain = implode(".", $dbns_parts);
												$ns_parent_domain =  substr($ns_parent_domain, 1);
												//$dbns_parts = array_reverse($dbns_parts);
												//$ns_parent_domain = $dbns_parts[1] . ".". $dbns_parts[0];
												//echo $ns_parent_domain;
												
								                //Check for A records on the proper nameservers because not always the nameserver we are iterating now is authoritative for the A/glue record.
		                                        $SELECT_NS_PARENT = mysql_query("SELECT content FROM records WHERE name = '".$ns_parent_domain."' AND type ='NS' ORDER BY content ASC", $db);
		                                        while ($NS_PARENT = mysql_fetch_array($SELECT_NS_PARENT)){
													
													if ($NS_PARENT['content'] == $DOMAIN_NS_GLUES['content'] ){
														$RESOLVER_IP = $NS_IP['content'];
			                                            $local_ns_found = true;
			                                            break;
                                                	}else{
														$SELECT_NS_PARENT_IP = mysql_query("SELECT content FROM records WHERE name = '".$NS_PARENT['content']."' AND type = 'A' ", $db);
				                                        $NS_PARENT_IP = mysql_fetch_array($SELECT_NS_PARENT_IP);
														$RESOLVER_IP = $NS_PARENT_IP['content'];
														$RESOLVER_NAME = $NS_PARENT['content'];
														if ($local_ns_found){
															break;
														}
		                                        	}
													
		                                        }
		                                        
												//Skip check if A record is not under our TLDs
												
												if (getTLD($DOMAIN_NS_GLUES['content'])){
												
													//Set resolver nameserver IP to use for lookup
													$resolver->nameservers = array($RESOLVER_IP);
		                                            //echo $RESOLVER_IP . "<br>";
													// Get A records from nameserver
    		                            			$response = $resolver->rawQuery($DOMAIN_NS_GLUES['content'], 'A');
					                                //echo "<pre>";
													//print_r($response);
													//echo "</pre>";
													
													$SELECT_NS_GLUE = mysql_query("SELECT content FROM records WHERE name = '".$DOMAIN_NS_GLUES['content']."' AND type = 'A' ", $db);
													$NS_GLUE = mysql_fetch_array($SELECT_NS_GLUE);							
													
													if ($response->header->rcode != 'NXDOMAIN'){
														if ($response->answer[0]->address == $NS_GLUE['content']){
															//echo "<img src='images/ico_valid.png' align='absmiddle' > Glue <strong class='blue'>".$NS_GLUE['content']."</strong> for A record <strong class='blue'>".$DOMAIN_NS_GLUES['content']."</strong>  check <strong class='green'>[OK]</strong><br />\n";
														}else{
															//echo "<img src='images/ico_invalid.png' align='absmiddle' > Glue response: <strong class='red'>".$response->answer[0]->address."</strong> for A record <strong class='blue'>".$DOMAIN_NS_GLUES['content']."</strong> does not match the glue record in registry (<strong class='blue'>".$NS_GLUE['content']."</strong>)<br />\n";
															$glue_errors[] = true;
														}
													}else{
														//echo "<img src='images/ico_invalid.png' align='absmiddle' > Glue <strong class='blue'>".$NS_GLUE['content']."</strong> for A record <strong class='blue'>".$DOMAIN_NS_GLUES['content']."</strong> does not exist (<strong class='red'>NXDOMAIN</strong>)<br />\n";
														$glue_errors[] = true;
													}
													
													if ($RESOLVER_IP != $NS_IP['content']){
														//echo "<span class='small' style='margin-left: 20px;'>(Authoritative NS for ".$DOMAIN_NS_GLUES['content']." > ".$RESOLVER_IP." - ".$RESOLVER_NAME.")</span><br /><br />\n";
													}else{
														//echo "<br />\n";
													}
													
												}
											}
										
										}
										
									}elseif($NS_TOTAL > count($NS)-2){
										//echo "\n<img src='images/ico_invalid.png' align='absmiddle' > Nameserver <strong class='blue'>".$NS_IP['content']." (".$DOMAIN_NS['content'].")</strong> responded with less NS Records than configured in Registry's Database.<br /><br />\n";
										$domain_errors[] = true;
									}else{
										//echo "\n<img src='images/ico_invalid.png' align='absmiddle' > Nameserver <strong class='blue'>".$NS_IP['content']." (".$DOMAIN_NS['content'].")</strong> responded with more NS Records than configured in Registry's Database.<br /><br />\n";
										$domain_errors[] = true;
									} 					
								
								}else{
									//echo "\n<img src='images/ico_invalid.png' align='absmiddle' > Nameserver <strong class='blue'>".$NS_IP['content']." (".$DOMAIN_NS['content'].")</strong> responded with NS Records not configured in Registry's Database.<br /><br />\n";
									$domain_errors[] = true;
								}					
							
							}else{
								//echo "\n<img src='images/ico_invalid.png' align='absmiddle' > Nameserver <strong class='blue'>".$NS_IP['content']." (".$DOMAIN_NS['content'].")</strong> did not reply (<strong class='red'>NO ANSWER</strong>).<br /><br />\n";
								$domain_errors[] = true;
							}

						}else{
							//echo "\n<img src='images/ico_invalid.png' align='absmiddle' > Nameserver <strong class='blue'>".$NS_IP['content']." (".$DOMAIN_NS['content'].")</strong> is <strong class='red'>not authoritative</strong> for this domain.<br /><br />\n";
							$domain_errors[] = true;
						}
                        				
					}else{
						//echo "\n<img src='images/ico_invalid.png' align='absmiddle' > Nameserver <strong class='blue'>".$NS_IP['content']." (".$DOMAIN_NS['content'].")</strong> did not answer properly (<strong class='red'>SERVFAIL</strong>).<br /><br />\n";
						$domain_errors[] = true;
					}
				
				}else{
					//echo "\n<img src='images/ico_invalid.png' align='absmiddle' > Nameserver <strong class='blue'>".$NS_IP['content']." (".$DOMAIN_NS['content'].")</strong> did not respond (<strong class='red'>TIMEOUT</strong>).<br /><br />\n";
					$domain_errors[] = true;
				}
			
			}else{
				//echo "\n<img src='images/ico_invalid.png' align='absmiddle' > You haven't configured any nameservers for your domain.<br /><br />\n";
				$domain_errors[] = true;
			}
			
			if (count($glue_errors)  == false && count($domain_errors) == 0){
				//echo "\n<h3><img src='images/ico_valid_medium.png' align='absmiddle' > Domain <strong class='blue'>".$d."</strong> passed validation on NS: <strong class='blue'>".$DOMAIN_NS['content']." (".$NS_IP['content'].")</strong></h3>\n";
			}else{
				//echo "\n<h3><img src='images/ico_invalid_medium.png' align='absmiddle' > Domain <strong class='blue'>".$d."</strong> failed validation on NS: <strong class='red'>".$DOMAIN_NS['content']." (".$NS_IP['content'].")</strong></h3>\n";
				$all_domain_errors[] = true;
			}
							
			//echo "</div>\n";
			//echo "\n\n<hr height='1' width='90%' align='left'><br />\n\n\n";
		
		}
    	
    	//echo "<div style='margin-left:40px;'>\n";
		if (count($all_domain_errors) || $r==0){
			
			//check in users_notifications table 
			$SELECT_PAST_FAILS = mysql_query("SELECT * FROM users_notifications WHERE user_id = '".$DOMAINS['user_id']."' AND domain = '".$DOMAINS['name']."' AND type = 'DOMAIN_REP_FAILED_VAL' ORDER BY time ASC", $db);
			$PAST_FAILS = mysql_fetch_array($SELECT_PAST_FAILS);
			$TOTAL_PAST_FAILS = mysql_num_rows($SELECT_PAST_FAILS);
		
			if ($PAST_FAILS['time']){
				$first_fail = round(( time() - $PAST_FAILS['time'] ) / 86400 );
			}else{
				$first_fail = 0;
			}		  
			//echo "First fail was " . $first_fail . " days before\n";
			//echo  $TOTAL_PAST_FAILS . " fails in a row\n";			
					
			if ($first_fail < $CONF['DOMAIN_REP_FAILED_VAL'] && $TOTAL_PAST_FAILS < $CONF['DOMAIN_REP_FAILED_VAL'] ){
				
				echo "Domain ".$DOMAINS['name']." failed, but hasn't reached fail limit to be suspended yet\n";				
				mysql_query("INSERT INTO users_notifications (`user_id`, `domain`, `type`, `time`) VALUES ('".$DOMAINS['user_id']."', '".$DOMAINS['name']."', 'DOMAIN_REP_FAILED_VAL' , UNIX_TIMESTAMP() ) ", $db);
				
			}else{
				
				if ($first_fail >= $CONF['DOMAIN_REP_FAILED_VAL'] ){
					echo "Domain ".$DOMAINS['name']." has failed for ".$first_fail." days in a row! SUSPENDING. Sending email.\n"; 
					//mysql_query("DELETE FROM users_notifications WHERE user_id = '".$DOMAINS['user_id']."' AND domain = '".$DOMAINS['name']."' AND type = 'DOMAIN_REP_FAILED_VAL' ", $db);
					
					mysql_query("UPDATE records SET `disabled` = '1' WHERE `name` = '".$DOMAINS['name']."' AND user_id = '".$DOMAINS['user_id']."' ",$db);
	    			mysql_query("UPDATE records SET `disabled` = '1' WHERE `name` LIKE '%.".$DOMAINS['name']."' AND user_id = '".$DOMAINS['user_id']."' ",$db);					
					
				}else{
					echo "Domain ".$DOMAINS['name']." reached ".$TOTAL_PAST_FAILS." notifications but first notification date (".$first_fail.") is not older than " . $CONF['DOMAIN_REP_FAILED_VAL']. " ! Not suspending yet. Maybe cron was out of date (run more than 1 time per day)\n"; 
					
				}
				
				
			}
			
			//echo "Domain " . $DOMAINS['name'] . " has FAILED the validations! \n";
			
			
    	}else{
	
			
			echo "Domain ".$DOMAINS['name']." has passed Validation. Resetting any previous fails.\n"; 
			mysql_query("DELETE FROM users_notifications WHERE user_id = '".$DOMAINS['user_id']."' AND domain = '".$DOMAINS['name']."' AND type = 'DOMAIN_REP_FAILED_VAL' ", $db);
			mysql_query("DELETE FROM users_notifications WHERE user_id = '".$DOMAINS['user_id']."' AND domain = '".$DOMAINS['name']."' AND type = 'DOMAIN_REP_FAILED_VAL_DIS_PER' ", $db);
			
			
			//echo "Domain " . $DOMAINS['name'] . " has passed the validations!\n";
						
		}
			
	}	
		
}



//Check for suspended domains that have passed DOMAIN_REP_FAILED_VAL_DIS_PER and DELETE them
$SELECT_DOMAINS = mysql_query("SELECT * FROM records WHERE type = 'NS' AND user_id > '0' AND disabled = '1' GROUP BY name ", $db);
while ($DOMAINS = mysql_fetch_array($SELECT_DOMAINS)){
                                                                    
	//Check if domain is disabled due to periodic validation failed checks
	$SELECT_OLD_DOMAIN = mysql_query("SELECT time FROM users_notifications WHERE user_id = '".$DOMAINS['user_id']."' AND domain = '".$DOMAINS['name']."' AND type = 'DOMAIN_REP_FAILED_VAL' ORDER BY time DESC", $db);
	if (mysql_num_rows($SELECT_OLD_DOMAIN)){

		//check in users_notifications table 
		$LAST_FAIL = mysql_fetch_array($SELECT_OLD_DOMAIN);
		
		$days_suspended = round((time() - $LAST_FAIL['time']) / 86400);
		if ($days_suspended > $CONF['DOMAIN_REP_FAILED_VAL_DIS_PER']){
	
			//DELETE DOMAIN
			
			mysql_query("DELETE FROM `records` WHERE `name`= '".$DOMAINS['name']."' AND type = 'NS' AND user_id = '".$DOMAINS['user_id']."' ",$db);
			mysql_query("DELETE FROM `records` WHERE `name` LIKE '%.".$DOMAINS['name']."' AND type = 'A' AND user_id = '".$DOMAINS['user_id']."' ",$db);
			mysql_query("DELETE FROM `users_notifications` WHERE `domain`= '".$DOMAINS['name']."' AND user_id = '".$DOMAINS['user_id']."' ",$db);			
						
			//Send email notification that the account just got suspended
			echo "Domain " . $DOMAINS['name'] . " was suspended in for over " . ($CONF['DOMAIN_REP_FAILED_VAL_DIS_PER'] + $CONF['DOMAIN_REP_FAILED_VAL']) . " days. DELETING. Sending email.\n";	
		
		}
		
	}

}


// Check for suspended domains that havent been activated and are near permanent deletion!
$SELECT_DOMAINS = mysql_query("SELECT * FROM records WHERE type = 'NS' AND user_id > '0' AND disabled = '1' GROUP BY name ", $db);
while ($DOMAINS = mysql_fetch_array($SELECT_DOMAINS)){
                                                                    
	//Check if domain is disabled due to periodic validation failed checks
	$SELECT_OLD_DOMAIN = mysql_query("SELECT time FROM users_notifications WHERE user_id = '".$DOMAINS['user_id']."' AND domain = '".$DOMAINS['name']."' AND type = 'DOMAIN_REP_FAILED_VAL' ORDER BY time DESC", $db);
	if (mysql_num_rows($SELECT_OLD_DOMAIN)){
		
		//check in users_notifications table 
		$LAST_FAIL = mysql_fetch_array($SELECT_OLD_DOMAIN);
		
		$suspend_days =  round(( time() - $LAST_FAIL['time'] ) / 86400 );
	    $days = $CONF['DOMAIN_REP_FAILED_VAL_DIS_PER'] - $suspend_days;
        echo "Has been suspended for " . $suspend_days . " days\n";
        echo "Days left until delete:" . $days . "\n";
		//now check if domain user has already had an email sent in the last month.
		
		//check in users_notifications table 
		$SELECT_PAST_NOTIF = mysql_query("SELECT * FROM users_notifications WHERE user_id = '".$DOMAINS['user_id']."'AND domain = '".$DOMAINS['name']."' AND type = 'DOMAIN_REP_FAILED_VAL_DIS_PER' ORDER BY time DESC", $db);
		$PAST_NOTIF = mysql_fetch_array($SELECT_PAST_NOTIF);
		$TOTAL_SENT_NOTIF = mysql_num_rows($SELECT_PAST_NOTIF);
		
		if ($TOTAL_SENT_NOTIF > 0){
			$TOTAL_NOTIF = round ($CONF['DOMAIN_REP_FAILED_VAL_DIS_PER'] / $CONF['DOMAIN_REP_FAILED_VAL_DIS_PER_ALERT_INTERVAL']);
		}else{
			$TOTAL_NOTIF = 1; 		
		}
		
		$last_sent_notif =  round(( time() - $PAST_NOTIF['time'] ) / 86400 );
		echo "Last notification sent: " .$last_sent_notif. " days ago\n";
		
		if ( ( $PAST_NOTIF['time'] < (time() - ($CONF['DOMAIN_REP_FAILED_VAL_DIS_PER_ALERT_INTERVAL'] * 86400) ) && $TOTAL_SENT_NOTIF < $TOTAL_NOTIF ) || $days <= '1' && $last_sent_notif >= '1' ){
	        
	        //SEND EMAIL NOTIFICATION
			mysql_query("INSERT INTO users_notifications (`user_id`, `domain`, `type`, `time`) VALUES ('".$DOMAINS['user_id']."', '".$DOMAINS['name']."', 'DOMAIN_REP_FAILED_VAL_DIS_PER' , UNIX_TIMESTAMP() ) ", $db);
			echo "Domain " . $DOMAINS['name'] . " is supended! User has ".$days." days before domain delete. Sending email.\n";	
					
		}else{
			
			//DONT SEND EMAIL - IT'S NOT TIME YET
			echo "Domain " . $DOMAINS['name'] . " was already informed. Last notification sent at: ". date("d-m-Y g:i a",$PAST_NOTIF['time']) .". User has ".$days." days before domain delete.\n";
		}		

	}

}


// cleanup old sessions to keep database small
mysql_query("DELETE FROM sessions WHERE access < ".(time()-86400)." ", $db);

?>
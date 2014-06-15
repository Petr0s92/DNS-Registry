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

require("includes/config.php");
require("includes/functions.php");
require_once 'Net/DNS.php';

// Protect page from anonymous users
admin_auth();

$maintitle_title = "Validate Zone";

if ($_SESSION['admin_level'] == 'user'){
	$user_id = " AND user_id = '".$_SESSION['admin_id']."' ";
}else{
    $user_id = " AND user_id > '0' ";		
}

if ($_GET['domain']){
	$d = mysql_real_escape_string($_GET['domain'], $db);
}else{
	header ("Location: ./index.php");
    exit();
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?=$CONF['APP_NAME'];?> | <?=$maintitle_title;?></title>
<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />

<!-- INCLUDE STYLES & JAVASCRIPTS -->
<link href="./includes/css.php" rel="stylesheet" type="text/css"  media="screen" />
<script type="text/javascript" src="./includes/js.php"></script> 
<!-- INCLUDE STYLES & JAVASCRIPTS END -->

</head>

<body id="login" style="height: auto;">

    <!-- NO JAVASCRIPT NOTIFICATION START -->
    <noscript>
        <div class="maintitle_nojs">This site needs Javascript enabled to function properly!</div>
    </noscript>
    <!-- NO JAVASCRIPT NOTIFICATION END -->
    
    <h2 id="validate_title">Validating Domain <strong class='blue'><?=$d;?></strong></h2>
    <br />        
    <table align="center">
    	<tr>
    		<td>
    		<div id='validate_form'>
    		<?
   			$resolver = new Net_DNS_Resolver();
			//Set resolver options
			$resolver->debug = 0; // Turn on debugging output to show the query
			$resolver->usevc = 0; // Force the use of TCP instead of UDP
			$resolver->port = 53; // DNS Server port
			$resolver->recurse = 1; // Disable recursion
			$resolver->retry = 1; // How long to wait for answer
			$resolver->retrans = 1; // How many times to retry for answer

    		$SELECT_DOMAIN_NS = mysql_query("SELECT content FROM records WHERE name = '".$d."' AND type = 'NS' ".$user_id." ORDER BY content ASC", $db);
    		$r=0;
			$all_domain_errors = array();
    		while($DOMAIN_NS = mysql_fetch_array($SELECT_DOMAIN_NS)){
    			$r++;
    			$domain_errors = array();
				
			    $SELECT_NS_IP = mysql_query("SELECT content FROM records WHERE name = '".$DOMAIN_NS['content']."' AND type = 'A' ", $db);
				$NS_IP = mysql_fetch_array($SELECT_NS_IP);
					
			    echo "<h4><img src='images/ico_info.png' align='absmiddle'> &nbsp;Checking Nameserver: <strong class='blue'>" .$DOMAIN_NS['content'] ." (".$NS_IP['content'].")</strong></h4>\n";
				echo "<div style='margin-left: 40px;'>\n";
								
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
									echo "<img src='images/ico_valid.png' align='absmiddle' > <strong class='blue'>SOA</strong> record check <strong class='green'>[OK]</strong><br /><br />\n";
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
																				
											echo "<img src='images/ico_valid.png' align='absmiddle' > <strong class='blue'>NS</strong> records check <strong class='green'>[OK]</strong><br /><br />\n";
											
											$glue_errors = array(); 
							                
											$SELECT_DOMAIN_NS_GLUES = mysql_query("SELECT content FROM records WHERE name = '".$d."' AND type = 'NS' ORDER BY content ASC", $db);
    										while($DOMAIN_NS_GLUES = mysql_fetch_array($SELECT_DOMAIN_NS_GLUES)){
    				                    
												$local_ns_found = false;
								                
								                //Check if db NS record is part of the domain (so that we need to check it's A/Glue records)
												$dbns_parts = explode(".", $DOMAIN_NS_GLUES['content']);
												$dbns_parts = array_reverse($dbns_parts);
												$ns_parent_domain = $dbns_parts[1] . ".". $dbns_parts[0];					
												
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
														echo "<img src='images/ico_valid.png' align='absmiddle' > Glue <strong class='blue'>".$NS_GLUE['content']."</strong> for A record <strong class='blue'>".$DOMAIN_NS_GLUES['content']."</strong>  check <strong class='green'>[OK]</strong><br />\n";
													}else{
														echo "<img src='images/ico_invalid.png' align='absmiddle' > Glue response: <strong class='red'>".$response->answer[0]->address."</strong> for A record <strong class='blue'>".$DOMAIN_NS_GLUES['content']."</strong> does not match the glue record in registry (<strong class='blue'>".$NS_GLUE['content']."</strong>)<br />\n";
														$glue_errors[] = true;
													}
												}else{
													echo "<img src='images/ico_invalid.png' align='absmiddle' > Glue <strong class='blue'>".$NS_GLUE['content']."</strong> for A record <strong class='blue'>".$DOMAIN_NS_GLUES['content']."</strong> does not exist (<strong class='red'>NXDOMAIN</strong>)<br />\n";
													$glue_errors[] = true;
												}
												
												if ($RESOLVER_IP != $NS_IP['content']){
												    echo "<span class='small' style='margin-left: 20px;'>(Authoritative NS for ".$DOMAIN_NS_GLUES['content']." > ".$RESOLVER_IP." - ".$RESOLVER_NAME.")</span><br /><br />\n";
												}else{
													echo "<br />\n";
												}
											}
											
										}elseif($NS_TOTAL > count($NS)-2){
											echo "\n<img src='images/ico_invalid.png' align='absmiddle' > Nameserver <strong class='blue'>".$NS_IP['content']." (".$DOMAIN_NS['content'].")</strong> responded with less NS Records than configured in Registry's Database.<br /><br />\n";
											$domain_errors[] = true;
										}else{
											echo "\n<img src='images/ico_invalid.png' align='absmiddle' > Nameserver <strong class='blue'>".$NS_IP['content']." (".$DOMAIN_NS['content'].")</strong> responded with more NS Records than configured in Registry's Database.<br /><br />\n";
											$domain_errors[] = true;
										} 					
									
									}else{
										echo "\n<img src='images/ico_invalid.png' align='absmiddle' > Nameserver <strong class='blue'>".$NS_IP['content']." (".$DOMAIN_NS['content'].")</strong> responded with NS Records not configured in Registry's Database.<br /><br />\n";
										$domain_errors[] = true;
									}					
								
								}else{
									echo "\n<img src='images/ico_invalid.png' align='absmiddle' > Nameserver <strong class='blue'>".$NS_IP['content']." (".$DOMAIN_NS['content'].")</strong> did not reply (<strong class='red'>NO ANSWER</strong>).<br /><br />\n";
									$domain_errors[] = true;
								}

							}else{
								echo "\n<img src='images/ico_invalid.png' align='absmiddle' > Nameserver <strong class='blue'>".$NS_IP['content']." (".$DOMAIN_NS['content'].")</strong> is <strong class='red'>not authoritative</strong> for this domain.<br /><br />\n";
								$domain_errors[] = true;
							}
                        					
						}else{
							echo "\n<img src='images/ico_invalid.png' align='absmiddle' > Nameserver <strong class='blue'>".$NS_IP['content']." (".$DOMAIN_NS['content'].")</strong> did not answer properly (<strong class='red'>SERVFAIL</strong>).<br /><br />\n";
							$domain_errors[] = true;
						}
					
					}else{
						echo "\n<img src='images/ico_invalid.png' align='absmiddle' > Nameserver <strong class='blue'>".$NS_IP['content']." (".$DOMAIN_NS['content'].")</strong> did not respond (<strong class='red'>TIMEOUT</strong>).<br /><br />\n";
						$domain_errors[] = true;
					}
				
				}else{
					echo "\n<img src='images/ico_invalid.png' align='absmiddle' > You haven't configured any nameservers for your domain.<br /><br />\n";
					$domain_errors[] = true;
				}
				
				if (count($glue_errors)  == false && count($domain_errors) == 0){
					echo "\n<h3><img src='images/ico_valid_medium.png' align='absmiddle' > Domain <strong class='blue'>".$d."</strong> passed validation on NS: <strong class='blue'>".$DOMAIN_NS['content']." (".$NS_IP['content'].")</strong></h3>\n";
				}else{
					echo "\n<h3><img src='images/ico_invalid_medium.png' align='absmiddle' > Domain <strong class='blue'>".$d."</strong> failed validation on NS: <strong class='red'>".$DOMAIN_NS['content']." (".$NS_IP['content'].")</strong></h3>\n";
					$all_domain_errors[] = true;
				}
								
				echo "</div>\n";
				echo "\n\n<hr height='1' width='90%' align='left'><br />\n\n\n";
			
			}
    		
    		echo "<div style='margin-left:40px;'>\n";
			if (count($all_domain_errors) || $r==0){
				echo "<h2><img src='images/ico_invalid_big.png' align='absmiddle' > Domain <strong class='blue'>".$d."</strong> <strong class='red'>failed</strong> the validation checks. </h2>\n";
				echo "<h3>Please check your nameserver(s) configuration and try again.</h3>\n";
    		}else{
				echo "<h2><img src='images/ico_valid_big.png' align='absmiddle' > Domain <strong class='blue'>".$d."</strong> <strong class='green'>passed</strong> the validation checks. </h2>\n";
				
			    $id = addslashes($_POST['id']);
			    $option = addslashes($_POST['option']);
			    
			    $SELECT_DOMAIN = mysql_query("SELECT name, domain_id, disabled FROM records WHERE name = '".$d."' AND type = 'NS' ". $user_id, $db);
			    $DOMAIN = mysql_fetch_array($SELECT_DOMAIN);

			    if ($DOMAIN['disabled'] == '1'){
				    $UPDATE = mysql_query("UPDATE records SET `disabled` = '0' WHERE `name` = '".$DOMAIN['name']."' ".$user_id,$db);
					$UPDATE2 = mysql_query("UPDATE records SET `disabled` = '0' WHERE `name` LIKE '%.".$DOMAIN['name']."' ".$user_id,$db);
					
					$soa_update = update_soa_serial_byid($DOMAIN['domain_id']);
				
					if ($UPDATE && $UPDATE2 && $soa_update){
						echo "<h3>Your domain is now enabled.</h3>\n";
					}else{
						echo "<h3>(!) Your domain could not be enabled. Please contact support.</h3>\n";					
					}			
				}
			}
    		echo "</div>\n";
	        ?>
    		</div>
    		</td>
    	</tr>
		<tr>
			<td>
				<div id="validate_credits">
					<a href="http://www.code.ath/public" target="_blank">Domain Registry Control Panel</a>
    			</div>
            </td>
		</tr>    
    </table>
    <br />
    <br />

</body>
</html>
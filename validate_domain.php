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
<title><?=$maintitle_title;?> - <?=$CONF['APP_NAME'];?></title>
<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />

<!-- INCLUDE STYLES & JAVASCRIPTS -->
<link href="./includes/css.php" rel="stylesheet" type="text/css"  media="screen" />
<script type="text/javascript" src="./includes/js.php?login=1"></script> 
<!-- INCLUDE STYLES & JAVASCRIPTS END -->

</head>

<body id="login" style="height: auto;">

    <!-- NO JAVASCRIPT NOTIFICATION START -->
    <noscript>
        <div class="maintitle_nojs">This site needs Javascript enabled to function properly!</div>
    </noscript>
    <!-- NO JAVASCRIPT NOTIFICATION END -->
    
    <h1 id="login_title">VALIDATING ZONE <?=$d;?></h1>
            
    <table align="center">
    	<tr>
    		<td>
    		<?
   			echo "<pre>\n";
   
   			$resolver = new Net_DNS_Resolver();
			//Set resolver options
			$resolver->debug = 0; // Turn on debugging output to show the query
			$resolver->usevc = 0; // Force the use of TCP instead of UDP
			$resolver->port = 53; // DNS Server port
			$resolver->recurse = 1; // Disable recursion
			$resolver->retry = 1; // How long to wait for answer
			$resolver->retrans = 1; // How many times to retry for answer

    		$SELECT_DOMAIN_NS = mysql_query("SELECT content FROM records WHERE name = '".$d."' AND type = 'NS' ".$user_id." ", $db);
    		$r=0;
    		while($DOMAIN_NS = mysql_fetch_array($SELECT_DOMAIN_NS)){
    			$r++;
				
			    echo ">>> STARTING VALIDATION ON NAMESERVER " .$DOMAIN_NS['content'] ."\n";
				
				$SELECT_NS_IP = mysql_query("SELECT content FROM records WHERE name = '".$DOMAIN_NS['content']."' AND type = 'A' ", $db);
				$NS_IP = mysql_fetch_array($SELECT_NS_IP);
				
				//Set resolver nameserver IP to use for lookup
				$resolver->nameservers = array($NS_IP['content']);

		                           
		        // GET SOA RECORD
				$response = $resolver->rawQuery($d, 'SOA');
				
				$aa = $response->header->aa;
				//$qr = $response->header->qr;
				//$tc = $response->header->tc;
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
								echo "--- SOA RECORD CHECK [OK] \n";
								//print_r($NS);
								
								//Compare received NS to registry NS
								$ns_error = false;					
								for ($i = 0; $i <= count($NS)-2; $i++) {
									$NS_AR = (array) $NS[$i];
									$NS_IN_DB = mysql_num_rows(mysql_query("SELECT 1 FROM records WHERE name = '".$d."' AND content = '".mysql_real_escape_string($NS_AR['nsdname'])."' ", $db));
									if (!$NS_IN_DB){
										//echo "\nSELECT 1 FROM records WHERE name = ".$d." AND content = ".mysql_real_escape_string($NS_AR['nsdname'])." \n";
										$ns_error = true;
									}						
								}
								
								
								//Validate NS records and move to further checks					
								if ($ns_error == false){
									
									echo "--- NS RECORDS CHECK [OK]\n";
									
									//Check if db NS record is part of the domain (so that we need to check it's A/Glue records)
									$dbns_parts = explode(".", $DOMAIN_NS['content']);
									$dbns_parts = array_reverse($dbns_parts);
									$ns_parent_domain = $dbns_parts[1] . ".". $dbns_parts[0];					
				                    
				                    $glue_errors = false;      
									if ($ns_parent_domain == $d){
										
										$SELECT_DOMAIN_NS_GLUES = mysql_query("SELECT content FROM records WHERE name = '".$d."' AND type = 'NS' ".$user_id." ", $db);
    									$r=0;
    									while($DOMAIN_NS_GLUES = mysql_fetch_array($SELECT_DOMAIN_NS_GLUES)){
    		                            
    		                            	echo "------ GETTING A GLUE RECORD FOR A RECORD ".$DOMAIN_NS_GLUES['content']."\n";
										
											$response = $resolver->rawQuery($DOMAIN_NS_GLUES['content'], 'A');
	
											//print_r($response);
	                                        $SELECT_NS_GLUE = mysql_query("SELECT content FROM records WHERE name = '".$DOMAIN_NS_GLUES['content']."' AND type = 'A' ", $db);
											$NS_GLUE = mysql_fetch_array($SELECT_NS_GLUE);							
											
											if ($response->header->rcode != 'NXDOMAIN'){
												
												if ($response->answer[0]->address == $NS_GLUE['content']){
													echo "------>GOT GLUE RECORD ".$NS_GLUE['content']." FOR A RECORD ".$DOMAIN_NS_GLUES['content']."\n";
												}else{
													echo "------ (!) GLUE RECORD ".$NS_GLUE['content']." FOR A RECORD ".$DOMAIN_NS_GLUES['content']." DOES NOT MATCH THE GLUE RECORD IN REGISTRY (got: ".$response->answer[0]->address.")\n";
													$glue_errors = true;
												}
												
											}else{
												echo "------ (!) GLUE RECORD ".$NS_GLUE['content']." FOR A RECORD ".$DOMAIN_NS_GLUES['content']." DOES NOT EXIST (NXDOMAIN)\n";
												$glue_errors = true;
											}
											
										}
										
										
									}
									
									if ($glue_errors  == false){
										echo "\n--> DOMAIN ".$d." PASSED VALIDATION ON NS: ".$NS_IP['content']." (".$DOMAIN_NS['content'].")!\n";
									}else{
										echo "\n--> (!) DOMAIN ".$d." FAILED VALIDATION ON NS: ".$NS_IP['content']." (".$DOMAIN_NS['content'].")!\n";
										$domain_errors[] = true;
									}
									
								}else{
									echo "\n---\n(!) NAMESERVER ".$NS_IP['content']." (".$DOMAIN_NS['content'].") RESPONDED WITH NS RECORDS NOT CONFIGURED IN REGISTRY\n";
									$domain_errors[] = true;
								}					
							
							}else{
								echo "\n---\n(!) NAMESERVER ".$NS_IP['content']." (".$DOMAIN_NS['content'].") DID NOT REPLY (NO ANSWER)\n";
								$domain_errors[] = true;
							}

						}else{
							echo "\n---\n(!) NAMESERVER ".$NS_IP['content']." (".$DOMAIN_NS['content'].") IS NOT AUTHORITATIVE FOR THIS DOMAIN\n";
							$domain_errors[] = true;
						}
                        				
					}else{
						echo "\n---\n(!) NAMESERVER ".$NS_IP['content']." (".$DOMAIN_NS['content'].") DID NOT ANSWER PROPERLY (SERVFAIL)\n";
						$domain_errors[] = true;
					}
				
				}else{
					echo "\n---\n(!) NAMESERVER ".$NS_IP['content']." (".$DOMAIN_NS['content'].") DID NOT RESPOND (TIMEOUT)\n";
					$domain_errors[] = true;
				}
				echo "\n\n---------------------------------------------------------------------------------------\n\n\n";
				
				
    		}
    		
    		if (count($domain_errors) || $r==0){
				
				echo "\n\n!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n";
				echo "# DOMAIN ".$d." FAILED VALIDATION :( \n";
				echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n\n";
			
    		}else{
				
				echo "\n\n!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n";
				echo "# DOMAIN ".$d." PASSED VALIDATION :D \n";
				echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n\n";
			
    		}
    		
				echo "</pre>\n";		
	        
    		?>
    		</td>
    	</tr>
    
    </table>
      
    
</body>
</html>
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

// Protect page from anonymous users
admin_auth();

// include dns validation functions
require ("./includes/dns.php");


//Define current page data
$mysql_table = 'records';
$sorting_array = array("id", "name", "content", "change_date", "created");

// ----------------------------------------------------------------------

$action_title = "All My Domain Names"; 
    
$search_vars = "";
    
$q = mysql_real_escape_string($_GET['q'], $db);
if ($q) { 
    $search_vars .= "&q=".$q; 
    $action_title = "Search: " . $q;
}

if ($_SESSION['admin_default_ttl_domains']){
	$CONF['RECORDS_TTL'] = $_SESSION['admin_default_ttl_domains'];
}

if ($_SESSION['admin_level'] == 'user'){
	$user_id = " AND user_id = '".$_SESSION['admin_id']."' ";
}else{

	$qu = mysql_real_escape_string($_GET['search_user_id'], $db);
    if ($qu) { 
        $search_vars .= "&search_user_id=".$qu;
    
        $user_id = " AND user_id = '".$qu."' ";
     
    }else{

		if ($_GET['show_system_domains'] == '1'){
			$user_id = " ";
		}elseif ($_GET['show_system_domains'] == '2'){
			$user_id = " AND user_id = '0' AND domain_id ";
		}else{
			$user_id = " AND user_id > '0' ";
		}								
		
		$search_vars .= "&show_system_domains=".mysql_real_escape_string($_GET['show_system_domains'], $db);;		
				
    }

}

$search_query = "WHERE (".$mysql_table.".name LIKE '%".$q."%' OR ".$mysql_table.".content LIKE '%".$q."%' ) AND type = 'NS' ". $user_id . " GROUP BY `name` ";

  
// Sorting
if (isset($_GET['sort'])){
    if (in_array($_GET['sort'], $sorting_array)) {
        if ($_GET['by'] !== "desc" && $_GET['by'] !== "asc") {
            $_GET['by'] = "desc";
        }
        $order = "ORDER BY `". mysql_escape_string($_GET['sort']) ."` ". mysql_escape_string($_GET['by']) . " ";
    }
} else {
    $order = "ORDER BY `created` DESC ";
    $_GET['sort'] = "created";
    $_GET['by'] = "desc";
}
$sort_vars = "&sort=".$_GET['sort']."&by=".$_GET['by'];


// Paging
$count = mysql_query("SELECT id FROM ".$mysql_table." ".$search_query,$db);
$items_number  = mysql_num_rows($count);
if ($_GET['items_per_page'] && is_numeric($_GET['items_per_page'])){
    $_SESSION['items_per_page'] = $_GET['items_per_page'];
}
if ($_POST['items_per_page'] && is_numeric($_POST['items_per_page'])){
    $_SESSION['items_per_page'] = $_POST['items_per_page'];
}
if (isset($_SESSION['items_per_page']) && is_numeric($_SESSION['items_per_page'])){
    $num = $_SESSION['items_per_page'];
} else { 
    $_SESSION['items_per_page'] = $CONF['ADMIN_ITEMS_PER_PAGE'];
    $num = $CONF['ADMIN_ITEMS_PER_PAGE'];     
}
$e = $num;
$pages = $items_number/$num;
if (!$_GET['pageno']){
    $pageno = 0; 
}else{
    $pageno = $_GET['pageno'];
}
if (isset($_POST['goto'])) {
    if ($_POST['goto'] <= $pages + 1) {
        $pageno = $num * ($_POST['goto'] - 1);
    } else {
        $pageno = 0;
    }
}
$current_page = 0;
for($i=0;$i<$pages;$i++){
    $y=$i+1;
    $page=$i*$num;
    if ($page == $pageno){
        $current_page = $y;
    }
} 
$total_pages=$i; // sinolo selidon

//Final Query for records listing
$SELECT_RESULTS  = mysql_query("SELECT `".$mysql_table."`.* FROM `".$mysql_table."` ".$search_query." ".$order . " LIMIT ".$pageno.", ".$e ,$db);

$url_vars = "action=".$_GET['action'] . $sort_vars . $search_vars;


//ADD NEW RECORD
if ($_POST['action'] == "add" ) {
    
    $errors = array();
    
    //Check if user has reached daily limit of new domain registrations
    if ($_SESSION['admin_level'] == "user"){
	    $SELECT_DOMAINS_TODAY = mysql_query("SELECT created FROM records WHERE type = 'NS' ". $user_id . " AND created >= '".(time() - 86400 )."'  GROUP BY `name` ORDER BY created DESC", $db);
	    $TOTAL_DOMAINS_TODAY = mysql_num_rows($SELECT_DOMAINS_TODAY);
	    $DOMAINS_TODAY = mysql_fetch_array($SELECT_DOMAINS_TODAY);
	    
	    if ($TOTAL_DOMAINS_TODAY >= $CONF['NEW_DOMAINS_PER_DAY'] && $DOMAINS_TODAY['created'] > (time() - 86400 )){
			$errors['domains_per_day'] = "You have reached the daily limit (".$CONF['NEW_DOMAINS_PER_DAY'].") of new domain registrations. Please try again tomorrow.";
	    }    
	} 
    
    if ($_POST['tld'] < 1) {
        $errors['tld'] = "Please choose a TLD.";
        $tld = "";
    } else {
        
        $SELECT_TLD = mysql_query("SELECT name, id FROM `tlds` WHERE `id` = '".mysql_escape_string($_POST['tld'])."' ",$db);
        $TLD = mysql_fetch_array($SELECT_TLD);
        
        $SELECT_TLD_ID = mysql_query("SELECT domain_id, name FROM `".$mysql_table."` WHERE name = '".$TLD['name']."' AND type = 'SOA' ",$db);
        $TLDID = mysql_fetch_array($SELECT_TLD_ID);
        
        if (!$TLDID['name'] && !$TLDID['domain_id']){
        	$errors['tld'] = "Please choose a TLD.";	
		}else{
			$tld = ".".$TLD['name'];
        }
        
    }
    
    $_POST['name'] = trim($_POST['name']);
    
    $hostname_labels = explode('.', $_POST['name'] . "." . $TLD['name']);
    $label_count = count($hostname_labels);    
    
    //If reverse domain validate differently
    if ($hostname_labels[$label_count - 1] == "arpa" ) {
    	$lookup_domain = $_POST['name'] . "." . $TLD['name'];
    	if ($validate = is_valid_hostname_fqdn($lookup_domain, 0) ){
			$errors['name'] = $validate;
		}
	//If forward domain do our validation
	}elseif (!preg_match("/^(?!-)[a-z0-9-]{1,63}(?<!-)$/", $_POST['name'])) {
        $errors['name'] = "Please choose a valid domain name. Only lowercase alphanumeric characters are allowed and a dash (-). Domain cannot start or end with a dash.";
	}else{
    	if (strlen($_POST['name']) > 62){
			$errors['name'] = "Please choose a shorter domain name.";	
    	}elseif (strlen($_POST['name']) < 2){
			$errors['name'] = "Please choose a bigger domain name.";	
    	}else{
	        if (mysql_num_rows(mysql_query("SELECT 1 FROM `".$mysql_table."` WHERE `name` = '".mysql_escape_string($_POST['name'].$tld)."' ",$db))){
	            $errors['name'] = "This domain name is already registered." ;
	        } 
    	}
    }
    
    if ($_POST['hosted'] == "nohosted"){ 
    
	    //CHECK NAMESERVERS
	    for ($i = 0; $i <= count($_POST['nameserver'])-1; $i++) {
    		$ns = trim($_POST['nameserver'][$i]);	
    		$glue = trim($_POST['glue'][$i]);	
    		$n = $i+1;
			if (!$ns){
    			$errors['namesever'.$i] = "Please enter a valid Nameserver ".$n.".";						
    		}else{
    			//check nameserver name
			    if (mysql_num_rows(mysql_query("SELECT 1 FROM `".$mysql_table."` WHERE `name` = '".mysql_escape_string($ns)."' AND type = 'A' AND user_id > 0 ",$db)) || !getTLD(mysql_escape_string($ns)) ){
				    //NS exists! We use this one!			    
				    $nameserver[$i]['name'] = trim($ns);	
				}else{

					//Check if the nameserver to be created is under a domain the user owns or under the newly created domain
					$new_domain = ".".$_POST['name'] . $tld;
					//echo $new_domain;
					$ns_domain_parts = explode(".", $ns);
					//print_r($ns_domain_parts);
					$ns_domain_parts[0] = false;
					$ns_domain = implode(".", $ns_domain_parts);
					$ns_domain = substr($ns_domain, 1);												
					//$ns_domain_parts = array_reverse($ns_domain_parts);
					//$ns_domain = $ns_domain_parts[1] . "." . $ns_domain_parts[0] . $tld;
					//echo $ns_domain;				
					if ( stristr($ns. $tld, $new_domain ) || 
						mysql_num_rows(mysql_query("SELECT 1 FROM `".$mysql_table."` WHERE `name` = '".mysql_escape_string($ns_domain)."' AND type = 'NS' " . $user_id ,$db))
					) {
						//NS does not exist - so we check the A record to add them later on
						if ($glue){
    						//check nameserver ip/glue
						    if(filter_var($glue, FILTER_VALIDATE_IP)){
								if ($CONF['NAMESERVERS_IP_RANGE'] == 'any' || netMatch($CONF['NAMESERVERS_IP_RANGE'], $glue)){
									//IP VALIDATED! We prepare arrays for the new nameserver/glue record insert
									$nameserver[$i]['name'] = trim($ns);	
									$nameserver[$i]['glue'] = trim($glue);
																						
								}else{
									$errors['glue'.$i] = "The Nameserver ".$n." IP you entered is not within permitted range: ".$CONF['NAMESERVERS_IP_RANGE'].".";	
								}	
							}else{
								$errors['glue'.$i] = "The Nameserver ".$n." IP you entered is not valid.";	
							}        
    					}else{
							$n = $i+1;
							$errors['glue'.$i] = "Please enter a valid Nameserver ".$n." IP.";						
						}				
					}else{
						$n = $i+1;
						$errors['namesever'.$i] = "Nameserver ".$n." parent domain is not owned by you. Cannot create Glue Record";						
    				}
				}    	    		
    		}

		}
		
		//echo "<pre>";	
		//print_r($nameserver);	    
		//echo "</pre>";	    
	    
	}elseif ($_POST['hosted'] != 'hosted'){
		
		$errors['hosted'] = "Please select a Domain Hosting Method";		
		
	}
    
    
    if (!$_POST['user_id']) {
        if ($_SESSION['admin_level'] != 'admin'){
        	$_POST['user_id'] = $_SESSION['admin_id'];
		}else{
			$errors['user_id'] = "Please choose an owner for the domain.";			
		}
    }elseif ($_POST['user_id'] == 'system'){
		$_POST['user_id'] = '0';
    }
    
    if (count($errors) == 0) {
        
        $insert_errors = array();
        $new_domain_time = time();
		
        //INSERT DOMAIN FOR SELF HOSTING
		if ($_POST['hosted'] == 'nohosted'){
        	
	        for ($i = 0; $i <= count($nameserver)-1; $i++) {
	        
		        $INSERT = mysql_query("INSERT INTO `".$mysql_table."` (name, user_id, domain_id, type, content, ttl, prio, change_date, disabled, auth, created) VALUES (      
		            '" . mysql_escape_string($_POST['name'].$tld) . "',
		            '" . mysql_escape_string($_POST['user_id']) . "',
		            '".$TLDID['domain_id']."',
		            'NS',
		            '".mysql_escape_string($nameserver[$i]['name'])."',
		            '".$CONF['RECORDS_TTL']."',
		            '0',
		            '".$new_domain_time."',
		            '1',
		            NULL,
		            '".$new_domain_time."'
		        )", $db);
		        
		        if (!$INSERT){
					$insert_errors[] = true;
		        }
		        
		        if ($nameserver[$i]['glue']){

			        $INSERT = mysql_query("INSERT INTO `".$mysql_table."` (name, content, type, domain_id, ttl, prio, change_date, created, user_id, auth, disabled ) VALUES (      
			            '" . mysql_escape_string($nameserver[$i]['name']) . "',
			            '" . mysql_escape_string($nameserver[$i]['glue']) . "',
			            'A',
			            '".$TLDID['domain_id']."',
			            '".$CONF['RECORDS_TTL']."',
			            '0',
			            '".$new_domain_time."',
			            '".$new_domain_time."',
			            '".mysql_escape_string($_POST['user_id'])."',
			            NULL,
			            '0'
			        )", $db);
					
					if (!$INSERT){
						$insert_errors[] = true;
	        		}		
		        }
    			
    			//$soa_update = update_soa_serial($tld);
    		
			}
    		
		}elseif ($_POST['hosted'] == 'hosted'){
		//INSERT DOMAIN FOR MANAGED HOSTING
			
			//Insert Domain record
			$INSERT_DOMAIN = mysql_query("INSERT INTO domains (name, type, notified_serial) VALUES ('". mysql_escape_string($_POST['name'].$tld)."', 'MASTER', '".get_soa_serial($CONF['DEFAULT_SOA'])."' ) ", $db);
			
			$new_domain_id = mysql_insert_id($db);			
			
			if (!$INSERT_DOMAIN || !$new_domain_id){
				$insert_errors[] = true;
		    }			
			
			
			//Insert SOA record
			$INSERT_SOA = mysql_query("INSERT INTO `records` (`domain_id`, `name`, `type`, `content`, `ttl`, `prio`, `change_date`, `ordername`, `auth`, `disabled`, `created`, `user_id`) VALUES (
						'".$new_domain_id."', 
						'".mysql_escape_string($_POST['name'].$tld)."', 
						'SOA',
						'".$CONF['DEFAULT_SOA']."',
						'".$CONF['RECORDS_TTL']."',
						'0',
						'".$new_domain_time."',
						NULL,
						NULL,
						'1',
						'".$new_domain_time."',
						'".mysql_escape_string($_POST['user_id'])."'
			)", $db);
			
			if (!$INSERT_SOA){
				$insert_errors[] = true;
		    }			
			
			//Insert Nameservers for new Domain
			$SELECT_ROOT_NS = mysql_query("SELECT `name`, `ip`, `id` FROM `root_ns` WHERE `active` = '1' ORDER BY `name` ASC ", $db);
			while($ROOT_NS = mysql_fetch_array($SELECT_ROOT_NS)){
				
				$INSERT_NS = mysql_query("INSERT INTO `records` (`domain_id`, `name`, `type`, `content`, `ttl`, `prio`, `change_date`, `ordername`, `auth`, `disabled`, `created`, `user_id`) VALUES (
							'".$new_domain_id."', 
							'".mysql_escape_string($_POST['name'].$tld)."', 
							'NS',
							'".$ROOT_NS['name']."',
							'".$CONF['RECORDS_TTL']."',
							'0',
							'".$new_domain_time."',
							NULL,
							NULL,
							'1',
							'".$new_domain_time."',
							'".mysql_escape_string($_POST['user_id'])."'
				)", $db);

				if (!$INSERT_NS){
					$insert_errors[] = true;
		        }
		        
		    	//Insert the NS TSIG records for AXFR to slaves				
				$INSERT_TSIG = mysql_query("INSERT INTO `domainmetadata` (`domain_id`, `kind`, `content` ) VALUES (
							'".$new_domain_id."', 
							'TSIG-ALLOW-AXFR',
							'".$ROOT_NS['name']."'
							
				)", $db);
				
   				//Insert the ALSO-NOTIFY records with Unicast IPs to notify meta-slaves for automatic provision of the new zone on the slaves. 				
				$SELECT_UNICAST_NS = mysql_query("SELECT `ip` FROM root_ns_unicast WHERE parent_id = '".$ROOT_NS['id']."' ", $db);
				while ($UNICAST_NS = mysql_fetch_array($SELECT_UNICAST_NS)){
					mysql_query("INSERT INTO `domainmetadata` (`domain_id`, `kind`, `content` ) VALUES (
								'".$new_domain_id."', 
								'ALSO-NOTIFY',
								'".addslashes($UNICAST_NS['ip'])."'
								
					)", $db);
					mysql_query("INSERT INTO `domainmetadata` (`domain_id`, `kind`, `content` ) VALUES (
								'".$new_domain_id."', 
								'ALSO-NOTIFY',
								'".addslashes($UNICAST_NS['ip']).":".$CONF['META_SLAVE_PORT']."'
								
					)", $db);
				}				
				
				
				if (!$INSERT_TSIG){
					$insert_errors[] = true;
		        }
		        
		    			
			
			}
			
			$soa_update = update_soa_serial($tld);
			$soa_update = update_soa_serial(mysql_escape_string($_POST['name'].$tld));
			
			
		}
		
		
        if (count($insert_errors) == 0){
            header("Location: index.php?section=".$SECTION."&saved_success=1");
            exit();
        }else{
            $error_occured = TRUE;
        }
        
    }
        
}


// DELETE RECORD
if ($_GET['action'] == "delete" && $_POST['id']){
    $id = mysql_real_escape_string(str_replace ("tr-", "", $_POST['id']), $db);

	if ($_SESSION['admin_level'] == 'user'){
		$user_id = " AND user_id = '".$_SESSION['admin_id'] . "' ";  	
	}else{
		$user_id = '';
	}
    
    $SELECT_DOMAIN = mysql_query("SELECT name FROM `".$mysql_table."` WHERE id = '".$id."' ". $user_id, $db);
    $DOMAIN = mysql_fetch_array($SELECT_DOMAIN);
    
	$SELECT_ISHOSTED = mysql_query("SELECT id FROM domains WHERE name = '".$DOMAIN['name']."' ", $db);
	$HOSTEDID = mysql_fetch_array($SELECT_ISHOSTED);
	$ISHOSTED = mysql_num_rows($SELECT_ISHOSTED);
					      

    if (mysql_num_rows($SELECT_DOMAIN)){
		$DELETE = mysql_query("DELETE FROM `".$mysql_table."` WHERE `name`= '".$DOMAIN['name']."' AND type = 'NS' ". $user_id ,$db);
		$DELETE = mysql_query("DELETE FROM `".$mysql_table."` WHERE `name` LIKE '%.".$DOMAIN['name']."' AND type = 'A' ". $user_id ,$db);
		$DELETE = mysql_query("DELETE FROM `users_notifications` WHERE `name`= '".$DOMAIN['name']."' ". $user_id ,$db);
		
		if ($ISHOSTED){
			$DELETE = mysql_query("DELETE FROM `domains` WHERE `id`= '".$HOSTEDID['id']."' ",$db);
			$DELETE = mysql_query("DELETE FROM `domainmetadata` WHERE `domain_id`= '".$HOSTEDID['id']."' ",$db);
			$DELETE = mysql_query("DELETE FROM `".$mysql_table."` WHERE `domain_id`= '".$HOSTEDID['id']."' ". $user_id ,$db);
			$soa_update = true;
		}
	    
	    $soa_update = update_soa_serial($DOMAIN['name'], true);
	    
	    if ($DELETE && $soa_update){
	        ob_end_clean();
	        echo "ok";
	    } else {
	        ob_end_clean();
	        echo "An error has occured.";
	    }
	}
	
    exit();
} 

/*
// ENABLE/DISABLE RECORD
if ($_GET['action'] == "toggle_active" && $_POST['id'] && isset($_POST['option'])){
    $id = mysql_real_escape_string($_POST['id'], $db);
    $option = mysql_real_escape_string($_POST['option'], $db);
    
    if ($_SESSION['admin_level'] == 'user'){
		$user_id = " AND user_id = '".$_SESSION['admin_id'] . "' ";  	
	}else{
		$user_id = '';
	}
    
    $SELECT_DOMAIN = mysql_query("SELECT name, domain_id FROM `".$mysql_table."` WHERE id = '".$id."' ". $user_id, $db);

    if (mysql_num_rows($SELECT_DOMAIN)){

        $DOMAIN = mysql_fetch_array($SELECT_DOMAIN);

	    $UPDATE = mysql_query("UPDATE `".$mysql_table."` SET `disabled` = '".$option."' WHERE `name` = '".$DOMAIN['name']."' ".$user_id,$db);
	    $UPDATE = mysql_query("UPDATE `".$mysql_table."` SET `disabled` = '".$option."' WHERE `name` LIKE '%.".$DOMAIN['name']."' ".$user_id,$db);
		
		$soa_update = update_soa_serial_byid($DOMAIN['domain_id']);
		
		if ($UPDATE && $soa_update) {
	        //print_r($_GET);
	        ob_clean();
	        echo "ok";
	    } else {
	        ob_clean();
	        echo "An error has occured.";
	    }
    
	}
    exit();
}
*/

// FIND NAMESERVER GLUE
if ($_GET['action'] == "fetch_glue" && $_POST['nameserver']){
    $nameserver = addslashes($_POST['nameserver']);

    //Check if nameserver TLD belongs to us or a 3rd Party DNS Service
    if (!getTLD($nameserver)){
		ob_clean();
	    echo "3rd Party TLD";
	    exit();
	}
	    
    $SELECT_GLUE = mysql_query("SELECT content FROM `".$mysql_table."` WHERE name = '".$nameserver."' AND type = 'A' AND user_id > 0", $db);
    $GLUE = mysql_fetch_array($SELECT_GLUE);

    if ($GLUE['content']){
    	ob_clean();
	    echo $GLUE['content'];
	} else {
		ob_clean();
	    echo "Enter IP";
	}
    exit();
}

?>

                <script>
                
                $(document).bind('cbox_closed', function(){
    				location.reload();
				});
                
                $(function() {
                	
                	
                	$(".validate_domain").colorbox({iframe:true, width:"85%", height:"90%", fastIframe:false, current: "Domain {current} of {total}" });
                
                	
                	// most effect types need no options passed by default
                    var options = {};    
                    
                    // Hide/Show the ADD Form
                    $( "#button" ).click(function() {
                        $( "#toggler" ).toggle( "blind", options, 500, function (){
                        	$('#name').focus();
    				    } );
                        return false;
                    });

                    // Hide/Show the RESULTS Table
                    $( "#button2" ).click(function() {
                        $( "#toggler2" ).toggle( "blind", options, 500, function (){
                            
                            //if ( $('#toggle_state').val('1') )
                            $('#toggle_state').val('1');

                            
                            
                        } );
                        return false;
                    });
                    
                    //Init
                    <?if ($_POST['action'] || $_GET['action'] == 'add'){?>
                        $( "#toggler" ).show();
                    <?}else{?>
                        $( "#toggler" ).hide();
                    <?}?>
                    $( "#toggler2" ).show();
                    
                    
                    <?if (staff_help()){?>
                    //TIPSY for the ADD Form
                    $('#name').tipsy({trigger: 'focus', gravity: 'n', fade: true});
                    $('#tld').tipsy({trigger: 'focus', gravity: 'w', fade: true});
                    $('#hosted').tipsy({trigger: 'focus', gravity: 'w', fade: true});
                    $('#user_id').tipsy({trigger: 'focus', gravity: 'w', fade: true});
                    $('#nameserver').tipsy({ gravity: 'e', fade: true, live: true, html: true });
                    $('#glue').tipsy({ gravity: 'w', fade: true, live: true, html: true });
                    <?}?>
                    

                    //DELETE RECORD
                    $('a.delete').click(function () {
                        var record_id = $(this).attr('rel');
                        if(confirm('Are you sure you want to delete this domain?\n\rThis action cannot be undone!')){
                            $.post("index.php?section=<?=$SECTION;?>&action=delete", {
                                    id: record_id
                                }, function(response){
                                    if (response == "ok"){
                                        $('#'+record_id).hide();
                                        $("#notification_success_response").html('Record deleted successfully.');
                                        $('.notification_success').show();
                                        var total_records = $('span#total_records').html();
                                         total_records--;
                                         $('span#total_records').html(total_records);
                                    } else {
                                        $("#notification_fail_response").html('An error occured.' );
                                        $('.notification_fail').show();
                                        //alert(response);
                                    }
                                });
                            return false;
                        }
                    });

                    <?/*
                    //ENABLE/DISABLE
                    $('a.toggle_active').click(function () {
                    	var dochange = '0';
	                    if ($(this).hasClass('activated')){
	                        if(confirm('Are you sure you want disable this domain?')){    
	                            var option = '1';
	                            var dochange = '1';
							}
	                    } else if ($(this).hasClass('deactivated')){
	                        var option = '0';
	                        var dochange = '1';
	                    }
	                    if (dochange == '1'){
		                    var myItem = $(this);
		                    var record_id = $(this).attr('rel');
		                    $.post("index.php?section=<?=$SECTION;?>&action=toggle_active", {
                          		id: record_id,
		                        option: option
		                    }, function(response){
		                        if (response == "ok"){
		                            $(myItem).toggleClass('activated');
		                            $(myItem).toggleClass('deactivated');
		                        } else {
		                            $("#notification_fail_response").html('An error occured.' );
		                            $('.notification_fail').show();
		                            //alert(response);
		                        }
		                    });
		                    
						}
	                    return false;
	            	});
	            	*/?>
    
    
                //CLOSE THE NOTIFICATION BAR
                $("a.close_notification").click(function() {
                    var bar_class = $(this).attr('rel');
                    //alert(bar_class);
                    $('.'+bar_class).hide();
                    return false;
                });
                
                
                //Get new domain name as being typed
                //$("#name").live('keyup', function() {
        		//	var userdomain = this.value;
        		//	$("#nameserver").val( this.value );
        		//});
                
                
				// Add Nameserver fields to add form                
				var MaxInputs       = 9; //maximum input boxes allowed
				var InputsWrapper   = $("#InputsWrapper"); //Input boxes wrapper ID
				var AddButton       = $("#AddMoreFileBox"); //Add button ID

				<?
				if (count($_POST['nameserver'])){
					echo "var FieldCount=".count($_POST['nameserver']).";";
					echo "var x=FieldCount;";
				}else{
					echo "var FieldCount=1;";
					echo "var x=FieldCount;";
				}
				?>

				$(AddButton).click(function (e)  //on add input button click
				{
				        if(x <= MaxInputs) //max input box allowed
				        {      
				        	x++; //text box increment
				            FieldCount++; //text box added increment
				            //add input box
				            var content = '<div>'+
				            				'<label for="nameserver" class="required">Nameserver '+ FieldCount +'</label>'+
				            				'<input type="text" name="nameserver[]" id="nameserver" title="Enter nameserver name.<br />Eg: ns'+ FieldCount +'.domain.tld" value="ns'+ FieldCount +'.domain.tld"/> '+
				            				' &nbsp; IP: <input type="text" name="glue[]" class="glue" id="glue" value="Enter IP"/> '+
				                          	'<a href="javascript:void(0)" class="removeclass" title="Click here to remove this nameserver field"><img src="images/ico_remove.png" align="absmiddle"></a>'+
				                          '<br /><br /></div>';
				            
				            $(InputsWrapper).append(content);
				        }
				return false;
				});

				$("body").on("click",".removeclass", function(e){ //user click on remove text
				        if( x > 1 ) {
				                $(this).parent('div').remove(); //remove text box
				                x--; //decrement textbox
				                FieldCount--; //text box decrement				            
				        }
				return false;
				});

				//Auto clear input NS fields
				$("#nameserver").live('focus', function() {
					if( this.value.indexOf( "domain.tld" ) != -1 ){
					$(this).val('');
					}
				});				                 
				
				//Auto clear input GLUE fields
				$("#glue").live('focus', function() {
					if( this.value.indexOf( "10.x.x." ) != -1 || this.value.indexOf( "Enter IP" ) != -1 ){
					$(this).val('');
					}
				});				                 
				
				//Find Nameserver Glue and add it to the field
                $('#nameserver').live('keyup', function () {
                    var nameserver = this.value;
                    var field = $(this).next();
                    if (nameserver.length > 3){
	                    $.post("index.php?section=<?=$SECTION;?>&action=fetch_glue", {
	                        nameserver: nameserver
	                    }, function(response){
	                        if (response){
	                            $(field).val(response);
	                            if( response ==  "3rd Party TLD" ){
	                            	$(field).addClass('input_disabled');									
	                            	$(field).attr("disabled", true);									
								}else if( response.indexOf( "Enter IP" ) != -1 ){
	                            	$(field).removeClass('input_disabled');									
	                            	$(field).attr("disabled", false);									
								}else {
									$(field).addClass('input_disabled');
									$(field).attr("disabled", true);									
								}
	                        }
	                    });
					}
					return false;
                });
                
                
                
                //SHOW/HIDE INPUT FIELDS BASED ON DROPDOWN MENU SELECTION
                <?if (!$_POST['hosted'] || $_POST['hosted'] == 'hosted') {?>
                $('#Hosted').show();
                $('#NoHosted').hide();
                <?}elseif ($_POST['hosted'] == 'nohosted'){?>
                $('#Hosted').hide();
                $('#NoHosted').show();
                <?}?>
                
                $('#hosted').live('change', function(){
                    var myval = $('option:selected',this).val();
                    if (myval == 'hosted') { 
                        $('#NoHosted').hide();
                        $('#Hosted').show();
                    }else if(myval == 'nohosted') {
                        $('#NoHosted').show();
                        $('#Hosted').hide();
                    }
                });                

				
				//end                                
                });				                 
				                                

                </script>
                
                <!-- DOMAINS SECTION START -->
                
                <div id="main_content">
                
                <div class="mainsubtitle_bg">
                    <div class="mainsubtitle"><a href="javascript: void(0)" id="button2">List all my Domain Names</a> | <?if ($_GET['action'] == 'edit'){?><a href="index.php?section=<?=$SECTION;?>&action=add" class="add"><span>Register new Domain Name</span></a> | <a href="index.php?section=<?=$SECTION;?>">Back to My Domains List</a><?}else{?><a href="javascript: void(0)" id="button" class="add"><span>Register new Domain Name</span></a><?}?></div>
                </div> 
                            
                <br />
                    
                    <? if ($_GET['saved_success']) { ?>
                        <p class="success"><span style="float: right;"><a href="javascript:void(0)" style="margin:0 auto" class="<?if (staff_help()){?>tip_east<?}?> close_notification" rel="success" title="Close notification bar"><span>Close Notification Bar</span></a></span>
                        Record saved successfully. </p>
                    <? } ?>
                    <? if ($error_occured) { ?>
                        <p class="error"><span style="float: right;"><a href="javascript:void(0)" style="margin:0 auto" class="<?if (staff_help()){?>tip_east<?}?> close_notification" rel="error" title="Close notification bar"><span>Close Notification Bar</span></a></span>An error occured.</p>
                    <? } ?>
                    
                    <p class="notification_success"><span style="float: right;"><a href="javascript:void(0)" style="margin:0 auto" class="<?if (staff_help()){?>tip_east<?}?> close_notification" rel="notification_success" title="Close notification bar"><span>Close Notification Bar</span></a></span><span id="notification_success_response"></span></p>
                    <p class="notification_fail"><span style="float: right;"><a href="javascript:void(0)" style="margin:0 auto" class="<?if (staff_help()){?>tip_east<?}?> close_notification" rel="notification_fail" title="Close notification bar"><span>Close Notification Bar</span></a></span><span id="notification_fail_response"></span></p>
                        
                    <div id="toggler">
                    
                        <!-- ADD DOMAIN START -->
                        <? if (count($errors) > 0) { ?>
                            <div id="errors">
                                <p>Please check:</p>
                                <ul>
                                    <? foreach ($errors as $key => $value) { echo "<li>" . $value . "</li>"; }?> 
                                </ul>
                            </div>
                        <? } ?>                        
                        
                        <form id="form" method="post" action="index.php?section=<?=$SECTION;?>&action=add">
                        
                            
                            <fieldset>
                                
                                <legend>&raquo; Register Domain Name</legend>
                        
                                     <div class="columns">
                                        <div class="colx2-left">
                                        
                                            <p>
                                                <label for="name" class="required">Domain Name</label>
                                                <input type="text" name="name" id="name" title="Enter the Domain Name" value="<?if($_POST['name']){ echo $_POST['name']; } ?>">
                                                <select name="tld" id="tld" title="Select TLD" >
                                                    <option value="" selected="selected">--Select--</option>
													<?
													$SELECT_TLDs = mysql_query("SELECT name, `default`, `id` FROM tlds WHERE active ='1' ORDER BY name ASC", $db);
													while ($TLDs = mysql_fetch_array($SELECT_TLDs)){
														$SELECT_DOMAIN_ID = mysql_query("SELECT id FROM domains WHERE name = '".$TLDs['name']."' ", $db);
														$DOMAIN_ID = mysql_fetch_array($SELECT_DOMAIN_ID);
														
														//Check if domain is reverse or forward
														$hostname_labels = explode('.', $TLDs['name']);
														$label_count = count($hostname_labels);    
														if ($hostname_labels[$label_count - 1] == "arpa" && ( $CONF['ALLOW_USERS_REVERSE'] == 'yes' || $_SESSION['admin_level'] == 'admin' )) {
													?>                                                    
                                                    <option value="<?=$TLDs['id'];?>"   <? if ($DOMAIN_ID['id'] && $_POST['tld'] == $TLDs['id']){ echo "selected=\"selected\""; }elseif ($TLDs['default'] == '1' && !$_POST['tld']){echo "selected=\"selected\"";}?> >.<?=$TLDs['name'];?></option>
													<?}elseif($hostname_labels[$label_count - 1] != "arpa" ){?>
													<option value="<?=$TLDs['id'];?>"   <? if ($DOMAIN_ID['id'] && $_POST['tld'] == $TLDs['id']){ echo "selected=\"selected\""; }elseif ($TLDs['default'] == '1' && !$_POST['tld']){echo "selected=\"selected\"";}?> >.<?=$TLDs['name'];?></option>
													<?}}?>                                                    
                                                    
                                                </select>
                                                
                                            </p>
                                            
                                            <p>
                                                <label for="hosted" class="required">Domain Hosting Method</label>
                                                <select name="hosted" id="hosted" title="Select the domain hosting method" >
                                                    <option value="hosted"   <?if (!$_POST['hosted'] || $_POST['hosted'] == 'hosted'){   echo "selected=\"selected\"";}?> >Managed Hosting</option>
                                                    <option value="nohosted" <?if ($_POST['hosted'] == 'nohosted'){ echo "selected=\"selected\"";}?> >Self Hosted</option>
                                                </select>
                                            </p>                                            

											
                                        	<div id="Hosted"><strong>This domain will be hosted on <span class="red">our</span> nameservers.</strong></div>
                                            
                                            <div id="NoHosted">
                                            	<strong>This domain will be hosted on <span class="red">your</span> nameservers</strong>
                                            	<br />												
                                            	<br />												
												<label class="required">Nameserver 1</label>                                            
                                            	<div id="InputsWrapper">
												
													<div>
														<input type="text" name="nameserver[]" id="nameserver" title="Enter nameserver name<br />Eg: ns1.domain.tld" value="<?if($_POST['nameserver'][0]){ echo $_POST['nameserver'][0]; }else{?>ns1.domain.tld<?}?>" autocomplete="off" />
														&nbsp; 
														<?
														//echo "<pre>";
														//print_r($_POST);
														//echo "</pre>";
														if ($_POST){	
															$SELECT_GLUE = mysql_query("SELECT content FROM records WHERE name = '".mysql_real_escape_string($_POST['nameserver'][0])."' AND user_id > 0", $db);
															$GLUE = mysql_fetch_array($SELECT_GLUE);
															if ($GLUE['content']){
																$glue = $GLUE['content'];
																$disabled = ' class="input_disabled" ';
															}elseif ($_POST['glue'][0]){
																$glue = $_POST['glue'][0];
																$disabled = '';
															}else{
																$glue = 'Enter IP';
																$disabled = '';
															}
														}else{
															$glue = 'Enter IP';															
															$disabled = '';
														}															
														?>
														IP: <input type="text" name="glue[]" id="glue" <?=$disabled;?> value="<?=$glue;?>"/><br /><br />
													</div>
													
													<?
													if ($_POST){
														for ($i = 1; $i <= count($_POST['nameserver'])-1; $i++) {
															$SELECT_GLUE = mysql_query("SELECT content FROM records WHERE name = '".mysql_real_escape_string($_POST['nameserver'][$i])."' AND user_id > 0", $db);
															$GLUE = mysql_fetch_array($SELECT_GLUE);
															if ($GLUE['content']){
																$glue = $GLUE['content'];
																$disabled = ' class="input_disabled" ';
															}elseif ($_POST['glue'][$i]){
																$glue = $_POST['glue'][$i];
																$disabled = '';
															}else{
																$glue = 'Enter IP';
																$disabled = '';
															}
														
													?>
													<div>
                                            			<label class="required">Nameserver <?=$i+1;?></label>
                                            			<input type="text" name="nameserver[]" id="nameserver" title="Enter nameserver name<br />Eg: ns<?=$i;?>.domain.tld" value="<?if($_POST['nameserver'][$i]){ echo $_POST['nameserver'][$i]; }else{?>ns<?=$i;?>.domain.tld<?}?>"/>
														&nbsp; 
														IP: <input type="text" name="glue[]" id="glue" <?=$disabled;?> value="<?=$glue;?>"/>
														<a href="javascript:void(0)" class="removeclass" title="Click here to remove this nameserver field"><img src="images/ico_remove.png" align="absmiddle"></a>
														<br /><br />
														
													</div>
													<?}}?> 
													
												</div>
												<a href="javascript:void(0)" id="AddMoreFileBox">Add another Nameserver <img src="images/ico_add.png" align="absmiddle"></a>
												<br />
                                        	    
                                        	</div>
                                        	
                                        </div>
                                        <div class="colx2-right">
                                        
                                        	
                                            <? if ($_SESSION['admin_level'] == 'admin'){?>
                                            <p>
                                                <label for="user_id" class="required">Domain Name Owner</label>
                                                <select name="user_id" id="user_id" title="Select an owner" >
                                                    <option value="" selected="selected">--Select--</option>
												    <? 
													$SELECT_USERS = mysql_query("SELECT id, username, fullname FROM users WHERE active ='1' ORDER BY username ASC", $db);
													while ($USERS = mysql_fetch_array($SELECT_USERS)){
													?>                                                    
                                                    <option value="<?=$USERS['id'];?>"   <? if ($_POST['user_id'] == $USERS['id']){ echo "selected=\"selected\""; }elseif ($_SESSION['admin_id'] == $USERS['id']){ echo "selected=\"selected\"";}?> ><?=$USERS['username'];?> <?if ($USERS['fullname']){?>(<?=$USERS['fullname'];?> )<?}?></option>
													<?}?>
													<?if ($_SESSION['admin_level'] == 'admin'){?>
                                                    <option value="system"   <? if ($_POST['user_id'] == "system"){ echo "selected=\"selected\""; }?> >----System Zone----</option>
													<?}?>													
                                                </select>
                                            </p>
                                            <?}?>
											
                                        </div>
                                        
                                     </div>
                        
                           </fieldset>

                           <fieldset>
                                <legend>&raquo; Action</legend>
                                <button type="submit"  >Save</button>&nbsp; &nbsp;
                                <button type="reset"  id="button">Cancel</button>
                                <input  type="hidden" name="action" id="action" value="add" />
                           </fieldset>
                        </form>                    
                        
                        <!-- ADD DOMAIN END -->
                        
                        <br />
                        
                    </div>
                        
                    <div id="toggler2">
                      
                    <!-- LIST DOMAINS START -->
                      
                      <fieldset>
                                
                          <legend>&raquo; My Domains List</legend>
                        
                      <form name="search_form" action="index.php?section=<?=$SECTION;?>" method="get" class="search_form">
                        <input type="hidden" name="section" value="<?=$SECTION;?>" />
                        <table border="0" cellspacing="0" cellpadding="4">
                            <tr>
                                <td>Domain:</td>
                                <td><input type="text" name="q" id="search_field_q" class="input_field" value="<?=$q?>" /></td>
                                
								<?if ($_SESSION['admin_level'] == 'admin'){?>                                
                    			<td>Owner:</td>
                                <td>
                                    <select name="search_user_id" class="select_box">
                                        <option value="">All Owners</option> 
                                        											<? 
										$SELECT_USERS = mysql_query("SELECT id, username, fullname FROM users WHERE active ='1' ORDER BY username ASC", $db);
										while ($USERS = mysql_fetch_array($SELECT_USERS)){
										?>                                                    
                                        <option value="<?=$USERS['id'];?>"   <? if ($_GET['search_user_id'] == $USERS['id']){ echo "selected=\"selected\""; }?> ><?=$USERS['username'];?> <?if ($USERS['fullname']){?>(<?=$USERS['fullname'];?>)<?}?></option>
										<?}?>  
                                        
                                    </select>
                                </td>
                                <td>Show System Domains:</td>
                                <td>
                                    <select name="show_system_domains" class="select_box">
                                        <option value="0" <? if ($_GET['show_system_domains'] != '1' && $_GET['show_system_domains'] != '2'){ echo "selected=\"selected\""; }?> >No</option> 
                                        <option value="1" <? if ($_GET['show_system_domains'] == '1'){ echo "selected=\"selected\""; }?> >Yes</option> 
                                        <option value="2" <? if ($_GET['show_system_domains'] == '2'){ echo "selected=\"selected\""; }?> >Only</option> 
                                    </select>
                                </td>
                                <?}?>                                
                                
                                <td><button type="submit"  >Search</button></td>
                            </tr>
                        </table> 
                      </form>

                      <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:15px; margin-top: 15px;">
                        <tr>
                            <td width="36%" height="30">
                                <h3 style="margin:0"><?=$action_title;?> <? if ($q) { ?><span style="font-size:12px"> (<a href="index.php?section=<?=$SECTION;?>">x</a>)</span><? } ?></h3> 
                            </td>
                            <td width="28%" align="center">
                                <? if ($items_number) { ?>
                                    Total Records: <span id="total_records"><?=$items_number?></span>
                                <? } ?>
                            </td>
                            <td width="36%"><? if ($items_number) { include "includes/paging.php"; } ?></td>
                        </tr>
                      </table>                            
                        
                        
                  
                      <table width="100%" border="0" cellspacing="2" cellpadding="5">
                      <tr>
                        <th><?=create_sort_link("name","Domain Name");?></th>
                        <th>Nameservers</th>
                        <th>Total Records</th>
                        <th><?=create_sort_link("created","Registered");?> / <?=create_sort_link("change_date","Updated");?></th>
                        <th><?=create_sort_link("disabled","Domain Status");?></th>
                        <?if ($_SESSION['admin_level'] == 'admin'){?>
                        <th><a href="javascript:void(0)" <?if (staff_help()){?>class="tip_south"<?}?> title="Domain Owner">Owner</a></th>
                        <?}?>
                        <th><a href="javascript:void(0)" <?if (staff_help()){?>class="tip_south"<?}?> title="Use the icons bellow manage your Domain.">Actions</a></th>
                      </tr>
                      <!-- RESULTS START -->
                      <?
                      $i=-1;
                      while($LISTING = mysql_fetch_array($SELECT_RESULTS)){
                      $i++;
                      
					  if ($_SESSION['admin_level'] == 'admin'){
					  	  if ($LISTING['user_id'] == 0){
						  	$DOMAIN_USER['username'] = 'System';
					  	  }else{
					  	  	$SELECT_DOMAIN_USER = mysql_query("SELECT username, id FROM users WHERE id = '".$LISTING['user_id']."' ", $db);
					  	  	$DOMAIN_USER = mysql_fetch_array($SELECT_DOMAIN_USER);
						  }
					  }
					  
					  $SELECT_ISHOSTED = mysql_query("SELECT id FROM domains WHERE name = '".$LISTING['name']."' ", $db);
					  $HOSTEDID = mysql_fetch_array($SELECT_ISHOSTED);
					  $ISHOSTED = mysql_num_rows($SELECT_ISHOSTED);					  
					  
					  $SELECT_ISTLD = mysql_query("SELECT id FROM tlds WHERE name = '".$LISTING['name']."' ", $db);
					  $TLDID = mysql_fetch_array($SELECT_ISTLD);
					  $ISTLD = mysql_num_rows($SELECT_ISTLD);
					  
					  $SELECT_LAST_UPDATED  = mysql_query("SELECT `change_date` FROM `".$mysql_table."` WHERE name LIKE '%".$LISTING['name']."' ORDER BY change_date DESC LIMIT 0, 1",$db);
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
					  					  
					  if ($_SESSION['admin_level'] == 'user'){
							$search_query = "WHERE (".$mysql_table.".name LIKE '%".$q."%' OR ".$mysql_table.".content LIKE '%".$q."%' OR ".$mysql_table.".type LIKE '%".$q."%' OR ".$mysql_table.".ttl LIKE '%".$q."%') AND domain_id = '".$DOMAIN['id']."' AND type != 'SOA' AND content NOT IN (".$ns.")". $user_id . "  ";
					  		$SELECT_DOMAIN_RECORDS = mysql_query("SELECT 1 FROM records WHERE domain_id = '".$HOSTEDID['id']."'  AND type != 'SOA' AND content NOT IN (".$ns.") " . $user_id, $db);
                      }elseif($_SESSION['admin_level'] == 'admin'){
					  		$SELECT_DOMAIN_RECORDS = mysql_query("SELECT 1 FROM records WHERE domain_id = '".$HOSTEDID['id']."' " . $user_id, $db);
                      }
					  $DOMAIN_RECORDS = mysql_num_rows($SELECT_DOMAIN_RECORDS);
                      
                      if ($LISTING['name'] != 'meta.meta'){
					  ?>     
                      <tr onmouseover="this.className='on' " onmouseout="this.className='off' " id="tr-<?=$LISTING['id'];?>">
                        <td align="left" nowrap>
	                        <h4>
                        		&nbsp; 
                        		<?if ($ISTLD){?>
                        			<a href="index.php?section=domain&amp;domain_id=<?=$HOSTEDID['id'];?>" <?if (staff_help()){?>class="tip_south"<?}?> title="Managed Domain Records" ><img src="images/nav_tlds.png" border="0" align="absmiddle"/></a>
                        		<?}elseif ($ISHOSTED && $LISTING['user_id'] == '0'){?>
                        			<a href="index.php?section=domain&amp;domain_id=<?=$HOSTEDID['id'];?>" <?if (staff_help()){?>class="tip_south"<?}?> title="Managed Domain Records" ><img src="images/nav_domains.png" border="0" align="absmiddle"/></a>
                        		<?}else{?>
                        			<a href="http://<?=$LISTING['name'];?>" target="_blank" <?if (staff_help()){?>class="tip_south"<?}?> title="Visit web site" ><img src="images/ico_link.png" border="0" align="absmiddle"/></a>
                        		<?}?> 
                        		&nbsp;<a href="index.php?section=<?if ($ISHOSTED){?>domain&amp;domain_id=<?=$HOSTEDID['id'];?><?}else{?>domain_ns&domain=<?=$LISTING['name'];?><?}?>" <?if (staff_help()){?>class="tip_south"<?}?> title="<?if ($ISHOSTED){?>Manage Domain Records<?}else{?>Set Domain Nameservers<?}?>" ><?=$LISTING['name'];?></a>
	                        </h4>
                        </td>
                        <td>
                            <table>
                            <?if ($ISHOSTED){?>
                            	<tr>
                            		<?if ($DOMAIN_RECORDS >= 1){?>
                            		<td nowrap="nowrap" align="right" width="33">
                            			<a href="index.php?section=domain&amp;domain_id=<?=$HOSTEDID['id'];?>" <?if (staff_help()){?>class="tip_south"<?}?> title="Manage Domain Records" ><img src="images/ico_edit_ns.png" align="absmiddle"></a>
                            		</td>
                            		<?}?>
                            		<td nowrap="nowrap">
                            			<?if ($DOMAIN_RECORDS >= 1){?>
                            			<span class="<?if ($ISTLD){?>red<?}elseif ($LISTING['user_id'] == '0'){?>blue<?}else{?>green<?}?>"><strong style="font-family: monospace"><?if ($ISTLD){?>--System TLD--<?}elseif ($LISTING['user_id'] == '0'){?>--System Zone--<?}else{?>--Hosted Domain--<?}?></strong></span>
										<?}else{?>                            			
                            			<span class="red alert_ico"><strong style="font-family: monospace"><a href="index.php?section=domain&domain_id=<?=$HOSTEDID['id'];?>&action=add" title="Add some records to enable this domain" <?if (staff_help()){?>class="tip_south"<?}?> >No Records yet</a></strong></span>
                            			<?}?>
                            		</td>
                            	</tr>
                            <?}else{?>
                            	<?
								$r=0;					  
					  			$SELECT_NAMESERVERS = mysql_query("SELECT content, id FROM `".$mysql_table."` WHERE name = '".$LISTING['name']."' AND type = 'NS' ", $db);
					  			while ($NAMESERVERS = mysql_fetch_array($SELECT_NAMESERVERS)){
					  				$SELECT_GLUE = mysql_query("SELECT id, user_id, content FROM `".$mysql_table."` WHERE name = '".$NAMESERVERS['content']."' AND type = 'A'", $db);
					  				$GLUE = mysql_fetch_array($SELECT_GLUE);
					  	  		$r++;
						  		?>
						  		<tr>
                        			<?if ($NAMESERVERS['content']!='unconfigured' ){?>
                        	    	<td nowrap="nowrap" align="right" width="60">
                        	    		<?if ( ( $GLUE['user_id'] == $_SESSION['admin_id'] || $_SESSION['admin_level'] == 'admin') && getTLD($NAMESERVERS['content']) ){?>
                        	    		<a href="index.php?section=nameservers&action=edit&id=<?=$GLUE['id'];?>" <?if (staff_help()){?>class="tip_south"<?}?> title="Edit this nameserver's Glue/A Record" ><img src="images/ico_edit_ns.png" align="absmiddle"></a> 
                        	    		<?}elseif (!getTLD($NAMESERVERS['content'])){?>
                        	    		<a href="javascript:void(0)" <?if (staff_help()){?>class="tip_south"<?}?> title="3rd Party TLD" ><img src="images/ico_arrow_up_left.png" align="absmiddle"></a>
                        	    		<?}?> 
                        	    		<strong>ns<?=$r?>:</strong></td>
                        	    	<?}?>
                        	    	<td nowrap="nowrap">
										<?if ($NAMESERVERS['content']=='unconfigured' && getTLD($NAMESERVERS['content'])){?>
										<span class="red alert_ico"><strong style="font-family: monospace"><a href="index.php?section=domain_ns&domain=<?=$LISTING['name'];?>&action=edit&id=<?=$NAMESERVERS['id'];?>" title="Configure this Domain's Nameserver" <?if (staff_help()){?>class="tip_south"<?}?> ><?=$NAMESERVERS['content'];?></a></strong></span>
										<?}else{?>
										<span class="<?if ($NAMESERVERS['content']=='unconfigured'){echo "red alert_ico";}else{ echo "blue";} ?>"><strong style="font-family: monospace"><?=$NAMESERVERS['content'];?></strong></span> <span class="small" style="font-family: monospace">(<?if (getTLD($NAMESERVERS['content'])){ echo $GLUE['content'];}else{ echo "3rd Party TLD";}?>)</span>
										<?}?> 
									</td>
                        	    </tr>
                        		<?}?>
                        	<?}?>
                        	</table>                        	                        
                        </td>
                        <td align="center" nowrap><?if ($ISHOSTED){ echo $DOMAIN_RECORDS; }else{ echo "-"; } ?></td>
                        <td align="center" nowrap><?if ($_GET['sort']=='created'){?><strong><?}?>R <?=date("d-m-Y g:i a", $LISTING['created']);?><?if ($_GET['sort']=='created'){?></strong><?}?><br /><?if ($_GET['sort']=='change_date'){?><strong><?}?>U <?=date("d-m-Y g:i a", $LAST_UPDATED['change_date']);?><?if ($_GET['sort']=='change_date'){?></strong><?}?></td>
                        <td align="center" >   
                        <?
                        if (!$ISTLD){
                        	
                        //if ($_SESSION['admin_level'] == 'admin'){
                        //	$status_title = 'Enable/Disable';
                        //	$toggle = true;
						//}else{
							if ($LISTING['disabled'] != '1') {
								$status_title = 'Active';	
							}else{
								$status_title = 'Inactive';
							}
							$toggle = false;							 
						//}   
                        ?>
                        <a href="javascript:void(0)" class="<?if (staff_help()){?>tip_south<?}?> <?if ($toggle){?>toggle_active<?}?> <? if ($LISTING['disabled'] != '1') { ?>activated<? } else { ?>deactivated<? } ?>" <?if ($toggle){?>rel="<?=$LISTING['id']?>"<?}?> title="<?=$status_title;?>"><span><?=$status_title;?></span></a>
                        <?}?>
                        </td>
                        <?if ($_SESSION['admin_level'] == 'admin'){?>
                        <td align="center" nowrap><?if ($LISTING['user_id'] > 0){?><a href="index.php?section=users&action=edit&id=<?=$LISTING['user_id'];?>" <?if (staff_help()){?>class="tip_south"<?}?> title="View User details"><?}?><?=$DOMAIN_USER['username'];?><?if ($LISTING['user_id'] > 0){?></a><?}?></td>
                        <?}?>
                        <td align="center" nowrap="nowrap">
	                        <?if (!$ISHOSTED){?>
	                        <a href="validate_domain.php?domain=<?=$LISTING['name'];?>" rel="validate_group" title="Validate your DNS Server configuration to enable domain <?=$LISTING['name'];?>" class="<?if (staff_help()){?>tip_south<?}?> validate validate_domain"><span>Validate Domain</span></a> &nbsp;
	                        <a href="index.php?section=domain_ns&amp;domain=<?=$LISTING['name'];?>" title="Configure Domain Nameserver" class="<?if (staff_help()){?>tip_south<?}?> edit"><span>Set Nameserver</span></a> &nbsp;
	                        <?}else{?> 
	                        <a href="index.php?section=domain&amp;domain_id=<?=$HOSTEDID['id'];?>" title="Manage Domain Records" class="<?if (staff_help()){?>tip_south<?}?> edit"><span>Manage Domain Records</span></a> &nbsp;
	                        <?}?> 
	                        <?if (!$ISTLD){?>
	                        <a href="javascript:void(0)" rel="tr-<?=$LISTING['id']?>" title="Delete" class="<?if (staff_help()){?>tip_south<?}?> delete"><span>Delete</span></a>
	                        <?}?>
                        </td>
                      </tr>
                      <?}}?>

                      <!-- RESULTS END -->
                    </table>
                    
                    <? if (!$items_number) { ?>
                        <div class="no_records">No records found</div>
                    <? } ?>
            

                    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin:10px 0">
                        <tr>
                            <td width="36%" height="30">
                            <? include "includes/items_per_page.php"; ?>
                            </td>
                            <td width="28%">&nbsp;</td>
                            <td width="36%"> 
                                <? if ($items_number) { include "includes/paging.php"; } ?>
                            </td>
                        </tr>
                    </table>
                    
                    </fieldset>
                    
                    <!-- LIST DOMAINS END -->
                    
                    </div>
                        
                </div>    
                
                <!-- DOMAINS SECTION END --> 
                
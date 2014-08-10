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

if ($_SESSION['admin_level'] == 'user'){
	$user_id = " AND user_id = '".$_SESSION['admin_id']."' ";
}else{

	$qu = mysql_real_escape_string($_GET['search_user_id'], $db);
    if ($qu) { 
        $search_vars .= "&search_user_id=".$qu;
    
        $user_id = " AND user_id = '".$qu."' ";
     
    }else{
		$user_id = " AND user_id > '0' ";		
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
    
    if ($_POST['tld'] < 1) {
        $errors['tld'] = "Please choose a TLD.";
        $tld = "";
    } else {
        
        $SELECT_TLD = mysql_query("SELECT name, id FROM `tlds` WHERE `id` = '".mysql_escape_string($_POST['tld'])."' ",$db);
        $TLD = mysql_fetch_array($SELECT_TLD);
        if (!$TLD['name']){
        	$errors['tld'] = "Please choose a TLD.";	
		}else{
			$tld = ".".$TLD['name'];
        }
        
    }
    
    $_POST['name'] = trim($_POST['name']);
    if (!preg_match("/^(?!-)[a-z0-9-]{1,63}(?<!-)$/", $_POST['name'])) {
        $errors['name'] = "Please choose a valid domain name. Only lowercase alphanumeric characters are allowed and a dash (-). Domain cannot start or end with a dash.";
    } else {
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
    
    //CHECK NAMESERVERS
    for ($i = 0; $i <= count($_POST['nameserver'])-1; $i++) {
    	$ns = trim($_POST['nameserver'][$i]);	
    	$glue = trim($_POST['glue'][$i]);	
    	$n = $i+1;
		if (!$ns){
    		$errors['namesever'.$i] = "Please enter a valid Nameserver ".$n.".";						
    	}else{
    		//check nameserver name
		    if (mysql_num_rows(mysql_query("SELECT 1 FROM `".$mysql_table."` WHERE `name` = '".mysql_escape_string($ns)."' AND type = 'A' AND user_id > 0 ",$db))){
			    //NS exists! We use this one!			    
			    $nameserver[$i]['name'] = trim($ns);	
			}else{

				//Check if the nameserver to be created is under a domain the user owns or under the newly created domain
				$new_domain = ".".$_POST['name'] . $tld;
				$ns_domain_parts = explode(".", $ns);
				$ns_domain_parts = array_reverse($ns_domain_parts);
				$ns_domain = $ns_domain_parts[1] . "." . $ns_domain_parts[0] . $tld;				
				if ( stristr($ns. $tld, $new_domain ) || 
					mysql_num_rows(mysql_query("SELECT 1 FROM `".$mysql_table."` WHERE `name` = '".mysql_escape_string($ns_domain)."' AND type = 'NS' " . $user_id ,$db))
				) {
					//NS does not exist - so we check the A record to add them later on
					if ($glue){
    					//check nameserver ip/glue
					    if(filter_var($glue, FILTER_VALIDATE_IP)){
							if ( ip2long($glue) <= ip2long("10.255.255.255") && ip2long("10.0.0.0") <=  ip2long($glue) )  {
								//IP VALIDATED! We prepare arrays for the new nameserver/glue record insert
								$nameserver[$i]['name'] = trim($ns);	
								$nameserver[$i]['glue'] = trim($glue);
																					
							}else{
								$errors['glue'.$i] = "The Nameserver ".$n." IP you entered is not valid.";	
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
    
    if (!$_POST['user_id']) {
        if ($_SESSION['admin_level'] != 'admin'){
        	$_POST['user_id'] = $_SESSION['admin_id'];
		}else{
			$errors['user_id'] = "Please choose an owner for the domain.";			
		}
    }
    
    if (count($errors) == 0) {
        
        $insert_errors = array();
        for ($i = 0; $i <= count($nameserver)-1; $i++) {
        
	        $INSERT = mysql_query("INSERT INTO `".$mysql_table."` (name, user_id, domain_id, type, content, ttl, prio, change_date, disabled, auth, created) VALUES (      
	            '" . mysql_escape_string($_POST['name'].$tld) . "',
	            '" . mysql_escape_string($_POST['user_id']) . "',
	            '".$TLD['id']."',
	            'NS',
	            '".mysql_escape_string($nameserver[$i]['name'])."',
	            '".$CONF['RECORDS_TTL']."',
	            '0',
	            UNIX_TIMESTAMP(),
	            '1',
	            '0',
	            UNIX_TIMESTAMP()
	        )", $db);
	        
	        if (!$INSERT){
				$insert_errors[] = true;
	        }
	        
	        if ($nameserver[$i]['glue']){

		        $INSERT = mysql_query("INSERT INTO `".$mysql_table."` (name, content, type, domain_id, ttl, prio, change_date, created, user_id, auth, disabled ) VALUES (      
		            '" . mysql_escape_string($nameserver[$i]['name']) . "',
		            '" . mysql_escape_string($nameserver[$i]['glue']) . "',
		            'A',
		            '".$TLD['id']."',
		            '".$CONF['RECORDS_TTL']."',
		            '0',
		            UNIX_TIMESTAMP(),
		            UNIX_TIMESTAMP(),
		            '".$_SESSION['admin_id']."',
		            '0',
		            '1'
		        )", $db);
				
				if (!$INSERT){
					$insert_errors[] = true;
	        	}		
	        }
    		
    		//$soa_update = update_soa_serial($tld);
    	
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
    $id = addslashes(str_replace ("tr-", "", $_POST['id']));

	if ($_SESSION['admin_level'] == 'user'){
		$user_id = " AND user_id = '".$_SESSION['admin_id'] . "' ";  	
	}else{
		$user_id = '';
	}
    
    $SELECT_DOMAIN = mysql_query("SELECT name FROM `".$mysql_table."` WHERE id = '".$id."' ". $user_id, $db);
    $DOMAIN = mysql_fetch_array($SELECT_DOMAIN);

    if (mysql_num_rows($SELECT_DOMAIN)){
		$DELETE = mysql_query("DELETE FROM `".$mysql_table."` WHERE `name`= '".$DOMAIN['name']."' AND type = 'NS' ". $user_id ,$db);
		$DELETE = mysql_query("DELETE FROM `".$mysql_table."` WHERE `name` LIKE '%.".$DOMAIN['name']."' AND type = 'A' ". $user_id ,$db);
	    
	    $soa_update = update_soa_serial($DOMAIN['name']);
	    
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

// ENABLE/DISABLE RECORD
if ($_GET['action'] == "toggle_active" && $_POST['id'] && isset($_POST['option'])){
    $id = addslashes($_POST['id']);
    $option = addslashes($_POST['option']);
    
    if ($_SESSION['admin_level'] == 'user'){
		$user_id = " AND user_id = '".$_SESSION['admin_id'] . "' ";  	
	}else{
		$user_id = '';
	}
    
    $SELECT_DOMAIN = mysql_query("SELECT name, domain_id FROM `".$mysql_table."` WHERE id = '".$id."' ". $user_id, $db);
    $DOMAIN = mysql_fetch_array($SELECT_DOMAIN);

    if (mysql_num_rows($SELECT_DOMAIN)){

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


// FIND NAMESERVER GLUE
if ($_GET['action'] == "fetch_glue" && $_POST['nameserver']){
    $nameserver = addslashes($_POST['nameserver']);
    
    $SELECT_GLUE = mysql_query("SELECT content FROM `".$mysql_table."` WHERE name = '".$nameserver."' AND user_id > 0", $db);
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
	                            if( response.indexOf( "Enter IP" ) != -1 ){
	                            	$(field).removeClass('input_disabled');									
	                            	//$(field).attr("disabled", false);									
								}else{
									$(field).addClass('input_disabled');
									//$(field).attr("disabled", true);									
								}
	                        }
	                    });
					}
					return false;
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
													$SELECT_TLDs = mysql_query("SELECT name, `default` AS def FROM tlds WHERE active ='1' ORDER BY name ASC", $db);
													while ($TLDs = mysql_fetch_array($SELECT_TLDs)){
														$SELECT_DOMAIN_ID = mysql_query("SELECT id FROM domains WHERE name = '".$TLDs['name']."' ", $db);
														$DOMAIN_ID = mysql_fetch_array($SELECT_DOMAIN_ID);
													?>                                                    
                                                    <option value="<?=$DOMAIN_ID['id'];?>"   <? if ($_POST['tld'] == $DOMAIN_ID['id']){ echo "selected=\"selected\""; }elseif ($TLDs['def'] == '1'){echo "selected=\"selected\"";}?> >.<?=$TLDs['name'];?></option>
													<?}?>                                                    
                                                    
                                                </select>
                                                
                                            </p>
												
												<label class="required">Nameserver 1</label>                                            
                                            	<div id="InputsWrapper">
												
													<div>
														<input type="text" name="nameserver[]" id="nameserver" title="Enter nameserver name<br />Eg: ns1.domain.tld" value="<?if($_POST['nameserver'][0]){ echo $_POST['nameserver'][0]; }else{?>ns1.domain.tld<?}?>"/>
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
                                                    <option value="<?=$USERS['id'];?>"   <? if ($_POST['user_id'] == $USERS['id']){ echo "selected=\"selected\""; }?> ><?=$USERS['username'];?> <?if ($USERS['fullname']){?>(<?=$USERS['fullname'];?> )<?}?></option>
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
					  	$SELECT_DOMAIN_USER = mysql_query("SELECT username, id FROM users WHERE id = '".$LISTING['user_id']."' ", $db);
					  	$DOMAIN_USER = mysql_fetch_array($SELECT_DOMAIN_USER);
					  }

					  ?>     
                      <tr onmouseover="this.className='on' " onmouseout="this.className='off' " id="tr-<?=$LISTING['id'];?>">
                        <td align="left" nowrap><h4> &nbsp; <a href="http://<?=$LISTING['name'];?>" target="_blank" <?if (staff_help()){?>class="tip_south"<?}?> title="Visit web site" ><img src="images/ico_link.png" border="0" align="absmiddle"/></a> &nbsp;<a href="index.php?section=domain_ns&domain=<?=$LISTING['name'];?>" <?if (staff_help()){?>class="tip_south"<?}?> title="Set Domain Nameservers" ><?=$LISTING['name'];?></a></h4></td>
                        <td>
                            <table>
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
                        	    	<td nowrap="nowrap" align="right" width="60"><?if ($GLUE['user_id'] == $_SESSION['admin_id'] || $_SESSION['admin_level'] == 'admin'){?><a href="index.php?section=nameservers&action=edit&id=<?=$GLUE['id'];?>" <?if (staff_help()){?>class="tip_south"<?}?> title="Edit this nameserver's Glue/A Record" ><img src="images/ico_edit_ns.png" align="absmiddle"></a> <?}?><strong>ns<?=$r?>:</strong></td>
                        	    	<?}?>
                        	    	<td nowrap="nowrap">
										<?if ($NAMESERVERS['content']=='unconfigured'){?>
										<span class="red alert_ico"><strong style="font-family: monospace"><a href="index.php?section=domain_ns&domain=<?=$LISTING['name'];?>&action=edit&id=<?=$NAMESERVERS['id'];?>" title="Configure this Domain's Nameserver" <?if (staff_help()){?>class="tip_south"<?}?> ><?=$NAMESERVERS['content'];?></a></strong></span>
										<?}else{?>
										<span class="<?if ($NAMESERVERS['content']=='unconfigured'){echo "red alert_ico";}else{ echo "blue";} ?>"><strong style="font-family: monospace"><?=$NAMESERVERS['content'];?></strong></span> <span class="small" style="font-family: monospace">(<?=$GLUE['content'];?>)</span>
										<?}?> 
									</td>
                        	    </tr>
                        		<?}?>
                        	</table>                        
                        </td>
                        <td align="center" nowrap><?if ($_GET['sort']=='created'){?><strong><?}?>C <?=date("d-m-Y g:i a", $LISTING['created']);?><?if ($_GET['sort']=='created'){?></strong><?}?><br /><?if ($_GET['sort']=='change_date'){?><strong><?}?>U <?=date("d-m-Y g:i a", $LISTING['change_date']);?><?if ($_GET['sort']=='change_date'){?></strong><?}?></td>
                        <td align="center" >   
                        <?
                        if ($_SESSION['admin_level'] == 'admin'){
                        	$status_title = 'Enable/Disable';
                        	$toggle = true;
						}else{
							if ($LISTING['disabled'] != '1') {
								$status_title = 'Active';	
							}else{
								$status_title = 'Inactive';
							}
							$toggle = false;							 
						}   
                        ?>
                        <a href="javascript:void(0)" class="<?if (staff_help()){?>tip_south<?}?> <?if ($toggle){?>toggle_active<?}?> <? if ($LISTING['disabled'] != '1') { ?>activated<? } else { ?>deactivated<? } ?>" <?if ($toggle){?>rel="<?=$LISTING['id']?>"<?}?> title="<?=$status_title;?>"><span><?=$status_title;?></span></a>
                        </td>
                        <?if ($_SESSION['admin_level'] == 'admin'){?>
                        <td align="center" nowrap><a href="index.php?section=users&action=edit&id=<?=$LISTING['user_id'];?>" <?if (staff_help()){?>class="tip_south"<?}?> title="View User details"><?=$DOMAIN_USER['username'];?></a></td>
                        <?}?>
                        <td align="center" nowrap="nowrap">
                            <a href="validate_domain.php?domain=<?=$LISTING['name'];?>" rel="validate_group" title="Validate your DNS Server configuration to enable domain <?=$LISTING['name'];?>" class="<?if (staff_help()){?>tip_south<?}?> validate validate_domain"><span>Validate Domain</span></a> &nbsp; 
                            <a href="index.php?section=domain_ns&amp;domain=<?=$LISTING['name'];?>" title="Configure Domain Nameserver" class="<?if (staff_help()){?>tip_south<?}?> edit"><span>Set Nameserver</span></a> &nbsp; 
                            <a href="javascript:void(0)" rel="tr-<?=$LISTING['id']?>" title="Delete" class="<?if (staff_help()){?>tip_south<?}?> delete"><span>Delete</span></a>
                        </td>
                      </tr>
                      <?}?>

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
                
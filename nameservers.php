<?php
/*-----------------------------------------------------------------------------
* Domain Registry Control Panel                                               *
*                                                                             *
* Developed by Vaggelis Koutroumpas - vaggelis@koutroumpas.gr                 *
* www.koutroumpas.gr  (c)2014                                                 * 
*-----------------------------------------------------------------------------*/

// Protect page from anonymous users
admin_auth();


//Define current page data
$mysql_table = 'records';
$sorting_array = array("id", "name", "content", "change_date", "created");

// ----------------------------------------------------------------------

$action_title = "All My Nameservers"; 
    
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

$search_query = "WHERE (".$mysql_table.".name LIKE '%".$q."%' OR ".$mysql_table.".content LIKE '%".$q."%' ) AND type = 'A' ". $user_id ;

  
// Sorting
if (isset($_GET['sort'])){
    if (in_array($_GET['sort'], $sorting_array)) {
        if ($_GET['by'] !== "desc" && $_GET['by'] !== "asc") {
            $_GET['by'] = "desc";
        }
        $order = "ORDER BY `". mysql_escape_string($_GET['sort']) ."` ". mysql_escape_string($_GET['by']) . " ";
    }
} else {
    $order = "ORDER BY `name` ASC ";
    $_GET['sort'] = "name";
    $_GET['by'] = "asc";
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

   



//SELECT RECORD FOR EDIT
if ( $_GET['action'] == "edit" && $_GET['id'] ) {
    $SELECT = mysql_query("SELECT * FROM `".$mysql_table."` WHERE `id`='".addslashes($_GET['id'])."'",$db);
    $RESULT = mysql_fetch_array($SELECT);
}

//ADD NEW RECORD
if ($_POST['action'] == "add" ) {
    
    $errors = array();
    
    if (!$_POST['domain']) {
        $errors['domain'] = "Please choose a Domain.";
        $domain = "";
    } else {
        
        $SELECT_DOMAIN = mysql_query("SELECT name, user_id, disabled FROM `".$mysql_table."` WHERE `name` = '".mysql_escape_string($_POST['domain'])."' AND type = 'NS' " . $user_id . " GROUP BY name",$db);
        $DOMAIN = mysql_fetch_array($SELECT_DOMAIN);
        if (!$DOMAIN['name']){
        	$errors['domain'] = "Please choose a Domain.";	
		}else{
			$domain = ".".$DOMAIN['name'];
			
			$DOMAIN_parts = explode("." ,$DOMAIN['name']);
			$TLD = $DOMAIN_parts[1];
        }
        
    }
    
    $_POST['name'] = trim($_POST['name']);
    if (!ctype_alnum($_POST['name'])) {
        $errors['name'] = "Please choose valid Nameserver name. Only Alphanumeric characters are allowed. Eg: ns1";
    } else {
    	if (strlen($_POST['name']) > 30){
			$errors['name'] = "Please choose a shorter nameserver name.";	
    	}else{
	        if (mysql_num_rows(mysql_query("SELECT 1 FROM `".$mysql_table."` WHERE `name` = '".mysql_escape_string($_POST['name'].$domain)."' AND type = 'A' ",$db))){
	            $errors['name'] = "This nameserver is already registered." ;
	        } 
    	}
    }    

	    
    $_POST['content'] = trim($_POST['content']);
    if (!$_POST['content']){
		$errors['content'] = "Please enter the IP Address of the nameserver.";
    } else {
		if(filter_var($_POST['content'], FILTER_VALIDATE_IP)){
			if ( ip2long($_POST['content']) <= ip2long("10.255.255.255") && ip2long("10.0.0.0") <=  ip2long($_POST['content']) )  {
												
			}else{
				$errors['content'] = "The IP you entered is not valid.";	
			}	
		}else{
			$errors['content'] = "The IP you entered is not valid.";	
		}        
         
    }
    
    $SELECT_TLD = mysql_query("SELECT id FROM domains WHERE name = '".$TLD."' ", $db);
    $TLD = mysql_fetch_array($SELECT_TLD);
    if (!$TLD['id']){
		 $errors['tld'] = "Invalid TLD.";
    }
            
    if (count($errors) == 0) {
        
        $INSERT = mysql_query("INSERT INTO `".$mysql_table."` (name, content, type, domain_id, ttl, prio, change_date, created, user_id, auth, disabled ) VALUES (      
            '" . addslashes($_POST['name'].$domain) . "',
            '" . addslashes($_POST['content']) . "',
            'A',
            '".$TLD['id']."',
            '".$CONF['RECORDS_TTL']."',
            '0',
            UNIX_TIMESTAMP(),
            UNIX_TIMESTAMP(),
            '".$DOMAIN['user_id']."',
            '0',
            '".$DOMAIN['disabled']."'
        )", $db);
        
        $soa_update = update_soa_serial_byid($TLD['id']);
        
        if ($INSERT && $soa_update){
            header("Location: index.php?section=".$SECTION."&saved_success=1".$sort_vars.$search_vars);
            exit();
        }else{
            $error_occured = TRUE;
        }
        
    }
        
}


if ($_POST['action'] == "edit" && $_POST['id']) {
    
    $id = $_POST['id'] = (int)$_POST['id'];
    
    $errors = array();
    
    if (!$_POST['domain']) {
        $errors['domain'] = "Please choose a Domain.";
        $domain = "";
    } else {
        
        $SELECT_DOMAIN = mysql_query("SELECT name FROM `".$mysql_table."` WHERE `name` = '".mysql_escape_string($_POST['domain'])."' AND type = 'NS' " . $user_id . " GROUP BY name",$db);
        $DOMAIN = mysql_fetch_array($SELECT_DOMAIN);
        if (!$DOMAIN['name']){
        	$errors['domain'] = "Please choose a Domain.";	
		}else{
			$domain = ".".$DOMAIN['name'];
			
			$DOMAIN_parts = explode("." ,$DOMAIN['name']);
			$TLD = $DOMAIN_parts[1];
        }
        
    }
    
    $_POST['name'] = trim($_POST['name']);
    if (!ctype_alnum($_POST['name'])) {
        $errors['name'] = "Please choose valid Nameserver name. Only Alphanumeric characters are allowed. Eg: ns1";
    } else {
    	if (strlen($_POST['name']) > 30){
			$errors['name'] = "Please choose a shorter nameserver name.";	
    	}else{
	        if (mysql_num_rows(mysql_query("SELECT 1 FROM `".$mysql_table."` WHERE `name` = '".mysql_escape_string($_POST['name'].$domain)."' AND type = 'A' AND id !='".$id."'  " ,$db))){
	            $errors['name'] = "This nameserver is already registered." ;
	        } 
    	}
    }    

	    
    $_POST['content'] = trim($_POST['content']);
    if (!$_POST['content']){
		$errors['content'] = "Please enter the IP Address of the nameserver.";
    } else {
		if(filter_var($_POST['content'], FILTER_VALIDATE_IP)){
			if ( ip2long($_POST['content']) <= ip2long("10.255.255.255") && ip2long("10.0.0.0") <=  ip2long($_POST['content']) )  {
												
			}else{
				$errors['content'] = "The IP you entered is not valid.";	
			}	
		}else{
			$errors['content'] = "The IP you entered is not valid.";	
		}        
         
    }
    
    $SELECT_TLD = mysql_query("SELECT id FROM domains WHERE name = '".$TLD."' ", $db);
    $TLD = mysql_fetch_array($SELECT_TLD);
    if (!$TLD['id']){
		 $errors['tld'] = "Invalid TLD.";
    }
        
    if (count($errors) == 0) {
        
        $UPDATE = mysql_query("UPDATE `".$mysql_table."` SET
            name = '" . addslashes($_POST['name'].$domain) . "',
            content = '" . addslashes($_POST['content']) . "',
            change_date = UNIX_TIMESTAMP()
            
            WHERE id= '" . $_POST['id'] . "'",$db);
            
            $soa_update = update_soa_serial_byid($TLD['id']);
                       
        if ($UPDATE && $soa_update){
            $_SESSION['admin_help'] = $_POST['Help'];
            header("Location: index.php?section=".$SECTION."&saved_success=1".$sort_vars.$search_vars);
            exit();
        }else{
            $error_occured = TRUE;
        }
        
    }
    
}


// DELETE RECORD
if ($_GET['action'] == "delete" && $_POST['id']){
    $id = addslashes(str_replace ("tr-", "", $_POST['id']));
    
    $SELECT_TLD = mysql_query("SELECT domain_id FROM `".$mysql_table."` WHERE id = '".$id."' ", $db);
    $TLD = mysql_fetch_array($SELECT_TLD);
    
    $DELETE = mysql_query("DELETE FROM `".$mysql_table."` WHERE `id`= '".$id."' " ,$db);
    
    $soa_update = update_soa_serial_byid($TLD['domain_id']);
    
    if ($DELETE){
        ob_end_clean();
        echo "ok";
    } else {
        ob_end_clean();
        echo "An error has occured.";
    }
    exit();
} 

// ENABLE/DISABLE RECORD
if ($_GET['action'] == "toggle_active" && $_POST['id'] && isset($_POST['option'])){
    $id = addslashes($_POST['id']);
    $option = addslashes($_POST['option']);
    
    $SELECT_TLD = mysql_query("SELECT domain_id FROM `".$mysql_table."` WHERE id = '".$id."' ", $db);
    $TLD = mysql_fetch_array($SELECT_TLD);
    
    $UPDATE = mysql_query("UPDATE `".$mysql_table."` SET `disabled` = '".$option."' WHERE `id`= '".$id."'",$db);
    
    $soa_update = update_soa_serial_byid($TLD['domain_id']);
    
    if ($UPDATE && $soa_update) {
        //print_r($_GET);
        ob_clean();
        echo "ok";
    } else {
        ob_clean();
        echo "An error has occured.";
    }
    exit();
}


?>
<? if (!1) { ?>
<link href="includes/style.css" rel="stylesheet" type="text/css" />
<? } ?>

                <script>
                $(function() {
                	
                	// most effect types need no options passed by default
                    var options = {};    
                    
                    // Hide/Show the ADD Form
                    $( "#button" ).click(function() {
                        $( "#toggler" ).toggle( "blind", options, 500, function (){
                            
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
                    <?if ($_POST['action'] || $_GET['action'] == 'edit' || $_GET['action'] == 'add'){?>
                        $( "#toggler" ).show();
                    <?}else{?>
                        $( "#toggler" ).hide();
                    <?}?>
                    $( "#toggler2" ).show();
                    
                    
                    <?if (staff_help()){?>
                    //TIPSY for the ADD Form
                    $('#name').tipsy({trigger: 'focus', gravity: 'n', fade: true});
                    $('#domain').tipsy({trigger: 'focus', gravity: 'w', fade: true});
                    $('#content').tipsy({trigger: 'focus', gravity: 'w', fade: true});
                    <?}?>
                    

                    //DELETE RECORD
                    $('a.delete').click(function () {
                        var record_id = $(this).attr('rel');
                        if(confirm('Are you sure you want to delete this nameserver?\n\rAll Domains using this nameserver will stop working! \n\rThis action cannot be undone!')){
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
	                        if(confirm('Are you sure you want disable this nameserver?\r\nDomains using this nameserver will stop working!')){    
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

                
                });
                

                </script>
                
                <!-- NAMESERVERS SECTION START -->
                
                <div id="main_content">
                
                <div class="mainsubtitle_bg">
                    <div class="mainsubtitle"><a href="javascript: void(0)" id="button2">List all Nameservers</a> | <?if ($_GET['action'] == 'edit'){?><a href="index.php?section=<?=$SECTION;?>&action=add">Add New Nameserver</a><?}else{?><a href="javascript: void(0)" id="button">Add New Nameserver</a><?}?></div>
                </div> 
                            
                <br />
                    
                    <? if ($_GET['saved_success']) { ?>
                        <p class="success"><span style="float: right;"><a href="javascript:void(0)" style="margin:0 auto" class="<?if (staff_help()){?>tip_east<?}?> close_notification" rel="success" title="Close notification bar"><span>Close Notification Bar</span></a></span>
                        Record saved successfully. <? if ($_GET['change_pass']) echo " Password changed."; ?></p>
                    <? } ?>
                    <? if ($error_occured) { ?>
                        <p class="error"><span style="float: right;"><a href="javascript:void(0)" style="margin:0 auto" class="<?if (staff_help()){?>tip_east<?}?> close_notification" rel="error" title="Close notification bar"><span>Close Notification Bar</span></a></span>An error occured.</p>
                    <? } ?>
                    
                    <p class="notification_success"><span style="float: right;"><a href="javascript:void(0)" style="margin:0 auto" class="<?if (staff_help()){?>tip_east<?}?> close_notification" rel="notification_success" title="Close notification bar"><span>Close Notification Bar</span></a></span><span id="notification_success_response"></span></p>
                    <p class="notification_fail"><span style="float: right;"><a href="javascript:void(0)" style="margin:0 auto" class="<?if (staff_help()){?>tip_east<?}?> close_notification" rel="notification_fail" title="Close notification bar"><span>Close Notification Bar</span></a></span><span id="notification_fail_response"></span></p>
                        
                    <div id="toggler">
                    
                        <!-- ADD/EDIT NAMESERVERS START -->
                        <? if (!empty($errors)) { ?>
                            <div id="errors">
                                <p>Please check:</p>
                                <ul>
                                    <? foreach ($errors as $key => $value) { echo "<li>" . $value . "</li>"; }?> 
                                </ul>
                            </div>
                        <? } ?>                        
                        
                        <form id="form" method="post" action="index.php?section=<?=$SECTION;?>&action=<?if ($_GET['action'] == 'edit'){ echo 'edit&id='.$_GET['id'];}else{ echo 'add'; } ?><?=$sort_vars;?><?=$search_vars;?>">
                        
                            
                            <fieldset>
                                
                                <legend>&raquo; <?if ($_GET['action'] == 'edit'){?>Edit Nameserver<?}else{?>New Nameserver<?}?></legend>
                        
                                     <div class="columns">
                                        <div class="colx2-left">
                                        
                                            <p>
                                            
                                            	<?
                                            	$nameserver_parts = explode(".", $RESULT['name']);
                                            	
                                            	$NS_NAME = $nameserver_parts[0];
                                            	$NS_DOMAIN = $nameserver_parts[1] . "." . $nameserver_parts[2];
                                            		
                                            	?>
                                                <label for="name" class="required">Nameserver Name</label>
                                                <input type="text" name="name" size="4" id="name" title="Enter name for your nameserver. Eg: ns1" value="<? if ($_GET['action'] == "edit"){ echo stripslashes($NS_NAME);}elseif($_POST['name']){ echo $_POST['name']; } ?>">
                                                <?if ($_GET['action'] == 'edit'){?>
                                                <input type="hidden" name="domain" id="domain" value="<?=$NS_DOMAIN;?>" />
                                                <?}?>
                                                <select name="domain" id="domain" title="Select domain" <?if ($_GET['action'] == 'edit'){?>disabled<?}?>>
                                                    <option value="" selected="selected">--Select--</option>
													<?
													$SELECT_DOMAINS = mysql_query("SELECT name FROM `".$mysql_table."` WHERE type = 'NS' ". $user_id . " GROUP BY `name` ORDER BY name ASC", $db);
													while ($DOMAIN = mysql_fetch_array($SELECT_DOMAINS)){
													?>                                                    
                                                    <option value="<?=$DOMAIN['name'];?>"   <? if ($_POST['domain'] == $DOMAIN['name']){ echo "selected=\"selected\""; }elseif ($_GET['action'] == "edit" && $DOMAIN['name'] == $NS_DOMAIN) { echo "selected=\"selected\""; }?> ><?=$DOMAIN['name'];?></option>
													<?}?>                                                    
                                                    
                                                </select>
                                                
                                            </p>
                                            
                                        </div>
                                        <div class="colx2-right">
                                            
                                            <p>
                                                <label for="content" class="required">IP Address</label>
                                                <input type="text" name="content" id="content" title="Enter Nameserver IP Address (Glue Record)" value="<? if($_POST['content']){ echo $_POST['content']; }elseif ($_GET['action'] == "edit"){ echo stripslashes($RESULT['content']);} ?>">
                                            </p>
                                          
                                                                                        
                                               
                                        </div>
                                        
                                     </div>
                        
                           </fieldset>

                           <fieldset>
                                <legend>&raquo; Action</legend>
                                <button type="submit"  >Save</button>&nbsp; &nbsp;
                                <button type="reset"  id="button">Cancel</button>
                                <input  type="hidden" name="action" id="action" value="<?if ($_GET['action'] == 'edit'){ echo 'edit';}else{ echo 'add'; } ?>" />
                                <?if ($_GET['action'] == 'edit'){?><input  type="hidden" name="id" id="id" value="<?=$RESULT['id'];?>" /><?}?>
                           </fieldset>
                        </form>                    
                        
                        <!-- ADD/EDIT NAMESERVERS END -->
                        
                        <br />
                        
                    </div>
                        
                    <div id="toggler2">
                      
                    <!-- LIST NAMESERVERS START -->
                      
                      <fieldset>
                                
                          <legend>&raquo; Nameservers List</legend>
                        
                      <form name="search_form" action="index.php?section=<?=$SECTION;?>" method="get" class="search_form">
                        <input type="hidden" name="section" value="<?=$SECTION;?>" />
                        <table border="0" cellspacing="0" cellpadding="4">
                            <tr>
                                <td>Nameserver or IP:</td>
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
                        <th><?=create_sort_link("name","Nameserver Name");?></th>
                        <th><?=create_sort_link("content", "IP Address (Glue)");?></th>
                        <?if ($_SESSION['admin_level'] == 'admin'){?>
                        <th><?=create_sort_link("disabled", "Active");?></th>
                        <?}?>
                        <th>Actions</th>
                      </tr>
                      <!-- RESULTS START -->
                      <?
                      $i=-1;
                      while($LISTING = mysql_fetch_array($SELECT_RESULTS)){
                      $i++;
                      ?>      
                      <tr onmouseover="this.className='on' " onmouseout="this.className='off' " id="tr-<?=$LISTING['id'];?>">
                        <td nowrap><a href="index.php?section=<?=$SECTION;?>&action=edit&id=<?=$LISTING['id'];?><?=$sort_vars;?><?=$search_vars;?>" title="Edit Nameserver" class="<?if (staff_help()){?>tip_south<?}?>"><?=$LISTING['name'];?></a></td>
                        <td align="center" ><?=$LISTING['content'];?></td>
                        <?if ($_SESSION['admin_level'] == 'admin'){?>
                        <td align="center" >
                            <a href="javascript:void(0)" style="margin:0 auto" class="<?if (staff_help()){?>tip_south<?}?> toggle_active <? if ($LISTING['disabled'] != '1') { ?>activated<? } else { ?>deactivated<? } ?>" rel="<?=$LISTING['id']?>" title="Enable/Disable"><span>Enable/Disable</span></a>
                        </td>
                        <?}?>
                        <td align="center" nowrap="nowrap">
                            <a href="index.php?section=<?=$SECTION;?>&amp;action=edit&amp;id=<?=$LISTING['id'];?><?=$sort_vars;?><?=$search_vars;?>" title="Edit" class="<?if (staff_help()){?>tip_south<?}?> edit"><span>Edit</span></a>
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
                    
                    <!-- LIST NAMESERVERS END -->
                    
                    </div>
                        
                </div>    
                
                <!-- NAMESERVERS SECTION END --> 
                
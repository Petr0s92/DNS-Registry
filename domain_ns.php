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

if ($_GET['domain']){
	$d = mysql_real_escape_string($_GET['domain'], $db);
	$d_vars = "&domain=".$d	;
}else{
	header ("Location: ./index.php?section=domains");
	exit();
}

   
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


$SELECT_DOMAIN = mysql_query("SELECT name FROM `".$mysql_table."` WHERE name = '".$d."' AND type = 'NS' " . $user_id, $db);
if (!mysql_num_rows($SELECT_DOMAIN)){
	header ("Location: ./index.php?section=domains");
    exit();
}
$DOMAIN = mysql_fetch_array($SELECT_DOMAIN);

if ($q) { 
$action_title = $DOMAIN['name']." nameservers | " . $action_title ; 
}else{
	$action_title = $DOMAIN['name']." nameservers";	
}

$search_query = "WHERE ( ".$mysql_table.".content LIKE '%".$q."%' ) AND type = 'NS' ". $user_id . " AND name = '".$d."'";

  
// Sorting
if (isset($_GET['sort'])){
    if (in_array($_GET['sort'], $sorting_array)) {
        if ($_GET['by'] !== "desc" && $_GET['by'] !== "asc") {
            $_GET['by'] = "desc";
        }
        $order = "ORDER BY `". mysql_escape_string($_GET['sort']) ."` ". mysql_escape_string($_GET['by']) . " ";
    }
} else {
    $order = "ORDER BY `content` ASC ";
    $_GET['sort'] = "content";
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
    
    if (!$_GET['domain']) {
        $errors['domain'] = "Missing domain";
        $domain = "";
    } else {
        $SELECT_DOMAIN = mysql_query("SELECT name, created, disabled, user_id FROM `".$mysql_table."` WHERE `name` = '".mysql_escape_string($_GET['domain'])."' AND type = 'NS' " . $user_id ,$db);
        $DOMAIN = mysql_fetch_array($SELECT_DOMAIN);
        if (!$DOMAIN['name']){
        	$errors['domain'] = "Missing domain";	
		}else{
			$DOMAIN_parts = explode("." ,$DOMAIN['name']);
			
			$DOMAIN_parts[0] = false;
			$TLD = implode(".", $DOMAIN_parts);
			$TLD =  substr($TLD, 1);			
			
			//$TLD = $DOMAIN_parts[1];
		}
    }
    
    $_POST['content'] = trim($_POST['content']);
	if (!mysql_num_rows(mysql_query("SELECT 1 FROM `".$mysql_table."` WHERE `name` = '".mysql_escape_string($_POST['content'])."' AND type = 'A' AND user_id > 0 ",$db))){
	    if ($DOMAIN['user_id'] == $_SESSION['admin_id']){
	    	
	    	$ns_part = explode(".", $_POST['content']);
	    	$ns = $ns_part[0];
	    	
			$create_link = " <a href='index.php?section=nameservers&action=add&domain=".mysql_escape_string($_GET['domain'])."&ns=".$ns."&domain_id=".$_GET['id']."' >Click here to register it now</a>";
	    }
	    $errors['content'] = "The nameserver you entered is not registered with this system." . $create_link;
	} 
    if (mysql_num_rows(mysql_query("SELECT 1 FROM `".$mysql_table."` WHERE `content` = '".mysql_escape_string($_POST['content'])."' AND type = 'NS' AND `name` = '".mysql_escape_string($_GET['domain']). "' ".$user_id,$db))){
	    $errors['content'] = "The nameserver you entered is already configured for this domain" ;
	}
	
	
    $SELECT_DOM_ID = mysql_query("SELECT id, name FROM domains WHERE name = '".$TLD."' ", $db);
    $DOM_ID = mysql_fetch_array($SELECT_DOM_ID);
    if (!$DOM_ID['id']){
		 $errors['domid'] = "Invalid Domain ID.";
    }
        
    if (count($errors) == 0) {
        
        $INSERT = mysql_query("INSERT INTO `".$mysql_table."` (name, content, type, domain_id, ttl, prio, change_date, created, user_id, auth, disabled ) VALUES (      
            '" . addslashes($DOMAIN['name']) . "',
            '" . addslashes($_POST['content']) . "',
            'NS',
            '".$DOM_ID['id']."',
            '".$CONF['RECORDS_TTL']."',
            '0',
            UNIX_TIMESTAMP(),
            '".$DOMAIN['created']."',
            '".$DOMAIN['user_id']."',
            '0',
            '".$DOMAIN['disabled']."'            
        )", $db);

		$soa_update = update_soa_serial($TLD);
		               
        if ($INSERT && $soa_update){
            header("Location: index.php?section=".$SECTION."&saved_success=1".$sort_vars.$search_vars.$d_vars);
            exit();
        }else{
            $error_occured = TRUE;
        }
        
    }
        
}


if ($_POST['action'] == "edit" && $_POST['id']) {
    
    $id = $_POST['id'] = (int)$_POST['id'];
    
    $errors = array();
    
    
    if (!$_GET['domain']) {
        $errors['domain'] = "Missing domain";
        $domain = "";
    } else {
        $SELECT_DOMAIN = mysql_query("SELECT name, created, user_id FROM `".$mysql_table."` WHERE `name` = '".mysql_escape_string($_GET['domain'])."' AND type = 'NS' AND user_id > 0 " . $user_id ,$db);
        $DOMAIN = mysql_fetch_array($SELECT_DOMAIN);
        if (!$DOMAIN['name']){
        	$errors['domain'] = "Missing domain";	
		}else{
			$DOMAIN_parts = explode("." ,$DOMAIN['name']);
			
			$DOMAIN_parts[0] = false;
			$TLD = implode(".", $DOMAIN_parts);
			$TLD =  substr($TLD, 1);			
			
			//$TLD = $DOMAIN_parts[1];
		} 
    }
    
    $_POST['content'] = trim($_POST['content']);
	if (!mysql_num_rows(mysql_query("SELECT 1 FROM `".$mysql_table."` WHERE `name` = '".mysql_escape_string($_POST['content'])."' AND type = 'A' AND user_id > 0 ",$db))){
	    if ($DOMAIN['user_id'] == $_SESSION['admin_id']){
	    	
	    	$ns_part = explode(".", $_POST['content']);
	    	$ns = $ns_part[0];
	    	
			$create_link = " <a href='index.php?section=nameservers&action=add&domain=".mysql_escape_string($_GET['domain'])."&ns=".$ns."&domain_id=".$_GET['id']."' >Click here to register it now</a>";
	    }
	    $errors['content'] = "The nameserver you entered is not registered with this system." . $create_link;
	}
	if (mysql_num_rows(mysql_query("SELECT 1 FROM `".$mysql_table."` WHERE `content` = '".mysql_escape_string($_POST['content'])."' AND type = 'NS' AND `name` = '".mysql_escape_string($_GET['domain']). "' ".$user_id,$db))){
	    $errors['content'] = "The nameserver you entered is already configured for this domain" ;
	}
	

	
    $SELECT_DOM_ID = mysql_query("SELECT id FROM domains WHERE name = '".$TLD."' ", $db);
    $DOM_ID = mysql_fetch_array($SELECT_DOM_ID);
    if (!$DOM_ID['id']){
		 $errors['domid'] = "Invalid Domain ID.";
    }
    
        
    if (count($errors) == 0) {
        
        $UPDATE = mysql_query("UPDATE `".$mysql_table."` SET
            content = '" . addslashes($_POST['content']) . "',
            change_date = UNIX_TIMESTAMP()
            
            WHERE id= '" . $_POST['id'] . "'",$db);
            
            $soa_update = update_soa_serial($TLD);
            
        if ($UPDATE && $soa_update){
            header("Location: index.php?section=".$SECTION."&saved_success=1".$sort_vars.$search_vars.$d_vars);
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
    
    if ($DELETE && $soa_update){
        ob_end_clean();
        echo "ok";
    } else {
        ob_end_clean();
        echo "An error has occured.";
    }
    exit();
} 



?>

                <script>
                $(function() {
                	
                	// most effect types need no options passed by default
                    var options = {};    
                    
                    // Hide/Show the ADD Form
                    $( "#button" ).click(function() {
                        $( "#toggler" ).toggle( "blind", options, 500, function (){
                        	$('#content').focus();
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
                        $('#content').focus();
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
                        if(confirm('Are you sure you want to delete this nameserver?\n\rThe Domain may stop functioning.\n\rThis action cannot be undone!')){
                            $.post("index.php?section=<?=$SECTION;?>&action=delete<?=$d_vars;?>", {
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

    
    
                //CLOSE THE NOTIFICATION BAR
                $("a.close_notification").click(function() {
                    var bar_class = $(this).attr('rel');
                    //alert(bar_class);
                    $('.'+bar_class).hide();
                    return false;
                });

                
                });
                

                </script>
                
                <!-- DOMAIN NAMESERVERS SECTION START -->
                
                <div id="main_content">
                
                <div class="mainsubtitle_bg">
                    <div class="mainsubtitle"><a href="javascript: void(0)" id="button2">List all Nameservers</a> | <?if ($_GET['action'] == 'edit'){?><a href="index.php?section=<?=$SECTION;?>&action=add<?=$d_vars;?>" class="add"><span>Add Nameserver to Domain</span></a><?}else{?><a href="javascript: void(0)" id="button" class="add">Add Nameserver to Domain</a><?}?> | <a href="index.php?section=domains" class="back"><span>Back to My Domains</span></a></div>
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
                    
                        <!-- ADD/EDIT DOMAIN NAMESERVERS START -->
                        <? if (count($errors) > 0) { ?>
                            <div id="errors">
                                <p>Please check:</p>
                                <ul>
                                    <? foreach ($errors as $key => $value) { echo "<li>" . $value . "</li>"; }?> 
                                </ul>
                            </div>
                        <? } ?>                        
                        
                        <form id="form" method="post" action="index.php?section=<?=$SECTION;?>&action=<?if ($_GET['action'] == 'edit'){ echo 'edit&id='.$_GET['id'];}else{ echo 'add'; } ?><?=$sort_vars;?><?=$search_vars;?><?=$d_vars;?>">
                        
                            
                            <fieldset>
                                
                                <legend>&raquo; <?if ($_GET['action'] == 'edit'){?>Edit Nameserver<?}else{?>Add Nameserver<?}?></legend>
                        
                                     <div class="columns">
                                        <div class="colx2-left">
                                        
                                            <p>
                                                <label for="content" class="required">Nameserver Name</label>
                                                <input type="text" name="content" id="content" title="Enter the Nameserver Name. Eg: ns1.domain.ath" value="<? if($_POST['content']){ echo $_POST['content']; }elseif ($_GET['action'] == "edit"){ echo stripslashes($RESULT['content']);} ?>">
                                            </p>
                                          
                                        </div>
                                        <div class="colx2-right">
                                            
                                            
                                                                                        
                                               
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
                        
                        <!-- ADD/EDIT DOMAIN NAMESERVERS END -->
                        
                        <br />
                        
                    </div>
                        
                    <div id="toggler2">
                      
                    <!-- LIST DOMAIN NAMESERVERS START -->
                      
                      <fieldset>
                                
                          <legend>&raquo; Nameservers List</legend>
                        
                      <?/*
                      <form name="search_form" action="index.php?section=<?=$SECTION;?><?=$d_vars;?>" method="get" class="search_form">
                        <input type="hidden" name="section" value="<?=$SECTION;?>" />
                        <input type="hidden" name="domain" value="<?=$d;?>" />
                        <table border="0" cellspacing="0" cellpadding="4">
                            <tr>
                                <td>Nameserver Name:</td>
                                <td><input type="text" name="q" id="search_field_q" class="input_field" value="<?=$q?>" /></td>
                                
                                <td><button type="submit"  >Search</button></td>
                            </tr>
                        </table> 
                      </form>

                      <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:15px; margin-top: 15px;">
                        <tr>
                            <td width="36%" height="30">
                                <h3 style="margin:0"><?=$action_title;?> <? if ($q) { ?><span style="font-size:12px"> (<a href="index.php?section=<?=$SECTION;?><?=$d_vars;?>">x</a>)</span><? } ?></h3> 
                            </td>
                            <td width="28%" align="center">
                                <? if ($items_number) { ?>
                                    Total Records: <span id="total_records"><?=$items_number?></span>
                                <? } ?>
                            </td>
                            <td width="36%"><? if ($items_number) { include "includes/paging.php"; } ?></td>
                        </tr>
                      </table>                            
                      */?>  
                        
                  
                      <table width="100%" border="0" cellspacing="2" cellpadding="5">
                      <tr>
                        <th><?=create_sort_link("content", "Nameserver");?></th>
                        <th>IP Address (Glue)</th>
                        <th>Actions</th>
                      </tr>
                      <!-- RESULTS START -->
                      <?
                      $i=-1;
                      while($LISTING = mysql_fetch_array($SELECT_RESULTS)){
                      $i++;
                      ?>      
                      <tr onmouseover="this.className='on' " onmouseout="this.className='off' " id="tr-<?=$LISTING['id'];?>">
                        <td align="center" nowrap><a href="index.php?section=<?=$SECTION;?>&action=edit&id=<?=$LISTING['id'];?><?=$sort_vars;?><?=$search_vars;?><?=$d_vars;?>" title="Edit Nameserver" class="<?if (staff_help()){?>tip_south<?}?>"><?=$LISTING['content'];?></a></td>
                        <td align="center" nowrap><?
                        $SELECT_GLUE = mysql_query("SELECT id, user_id, content FROM `".$mysql_table."` WHERE name = '".$LISTING['content']."' AND type = 'A'", $db);
					  	$GLUE = mysql_fetch_array($SELECT_GLUE);
					  	  		
                        ?>
                        <?if ($GLUE['content']){?>
                        <?if ($GLUE['user_id'] == $_SESSION['admin_id'] || $_SESSION['admin_level'] == 'admin'){?><a href="index.php?section=nameservers&action=edit&id=<?=$GLUE['id'];?>" <?if (staff_help()){?>class="tip_south"<?}?> title="Edit this nameserver's Glue/A Record" ><img src="images/ico_edit_ns.png" align="absmiddle"></a> <?}?>
                        <strong class="blue" style="font-family: monospace">(<?=$GLUE['content'];?>)</strong>
                        <?}else{?>
                        <span class="red alert_ico"><strong>No Glue record found</strong></span>
                        <?}?>
                        <td align="center" nowrap="nowrap">
                            <a href="index.php?section=<?=$SECTION;?>&amp;action=edit&amp;id=<?=$LISTING['id'];?><?=$sort_vars;?><?=$search_vars;?><?=$d_vars;?>" title="Edit" class="<?if (staff_help()){?>tip_south<?}?> edit"><span>Edit</span></a>
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
                    
                    <!-- LIST DOMAIN NAMESERVERS END -->
                    
                    </div>
                        
                </div>    
                
                <!-- DOMAIN NAMESERVERS SECTION END --> 
                
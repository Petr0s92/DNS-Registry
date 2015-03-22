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
$sorting_array = array("id", "name", "content", "type", "ttl", "prio", "domain_id");

// ----------------------------------------------------------------------

if ($_SESSION['admin_default_ttl_records']){
	$CONF['RECORDS_TTL'] = $_SESSION['admin_default_ttl_records'];
}

if ($_SESSION['admin_level'] == 'user'){
	$user_id = " AND user_id = '".$_SESSION['admin_id']."' ";
}else{
    $user_id = "";		
}

$did = mysql_real_escape_string($_GET['domain_id'], $db);

$SELECT_DOMAIN = mysql_query("SELECT name, id FROM domains WHERE id = '".$did."' ", $db);
$DOMAIN = mysql_fetch_array($SELECT_DOMAIN);

$SELECT_DOMAIN_USER = mysql_query("SELECT user_id FROM records WHERE domain_id = '".$DOMAIN['id']."' " . $user_id, $db);
$DOMAIN_USER = mysql_fetch_array($SELECT_DOMAIN_USER);

//If domain_id is invalid redirect to my domains page
if (!mysql_num_rows($SELECT_DOMAIN) || !$DOMAIN['id'] || !mysql_num_rows($SELECT_DOMAIN_USER) ){
	Header ("Location: index.php?section=domains");
	exit();
}

//Check if domain is reverse or forward
$hostname_labels = explode('.', $DOMAIN['name']);
$label_count = count($hostname_labels);    
if ($hostname_labels[$label_count - 1] == "arpa" ) {
	$ISREVERSE = true;
	$mgmt_title = "hosted Reverse Zone";
}else{
	$ISREVERSE = false;
	$mgmt_title = "hosted Domain";
}

$action_title = "Manage ".$mgmt_title.": " . $DOMAIN['name']; 
    
$search_vars = "&domain_id=".$DOMAIN['id'];
    
$q = mysql_real_escape_string($_GET['q'], $db);
if ($q) { 
    $search_vars .= "&q=".$q; 
    $action_title = "Search: " . $q;
}

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
}elseif($_SESSION['admin_level'] == 'admin'){
	$search_query = "WHERE (".$mysql_table.".name LIKE '%".$q."%' OR ".$mysql_table.".content LIKE '%".$q."%' OR ".$mysql_table.".type LIKE '%".$q."%' OR ".$mysql_table.".ttl LIKE '%".$q."%') AND domain_id = '".$DOMAIN['id']."' ";
	//$search_query = "WHERE (".$mysql_table.".name LIKE '%".$q."%' OR ".$mysql_table.".content LIKE '%".$q."%' ) AND domain_id = '".$DOMAIN['id']."'  ". $user_id . "  ";
}

  
// Sorting
if (isset($_GET['sort'])){
    if (in_array($_GET['sort'], $sorting_array)) {
        if ($_GET['by'] !== "desc" && $_GET['by'] !== "asc") {
            $_GET['by'] = "desc";
        }
        $order = "ORDER BY `". mysql_escape_string($_GET['sort']) ."` ". mysql_escape_string($_GET['by']) . " ";
    }
} else {
    $order = "ORDER BY `name` ASC, `content` ASC";
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
if ($_POST['action'] == "add" && $_POST['domain_id']) {
    
    $errors = array();
    
    $_POST['name'] = strtolower($_POST['name']); // powerdns only searches for lower case records

    if ($ISREVERSE && ! is_numeric($_POST['name'])){
		$errors['name'] = "The record name must be an IP octet.";		
	}
      
    if ($ISREVERSE && filter_var($_POST['content'], FILTER_VALIDATE_IP) ){
		$errors['content'] = "The content must be a dns name.";		
	}
    
    if (!$_POST['content']){
		$errors['content'] = "Please fill in the record content.";		
	}
      
    if (!$_POST['ttl'] || !is_numeric($_POST['ttl'])){
		$errors['content'] = "Please fill in the TTL.";		
	}
      
    if ($validate = validate_input(-1, $DOMAIN['id'], $_POST['type'], $_POST['content'], $_POST['name'], $_POST['priority'], $_POST['ttl'])){
		$errors['validate'] = $validate;	
    }
	
    if (count($errors) == 0) {
        
        //Check if domain is new and inactive and prepare to activate all its records

        //first fetch the active root ns
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
        
        $SELECT_DOMAIN_INACTIVE = mysql_query("SELECT 1 FROM `".$mysql_table."` WHERE domain_id = '".$DOMAIN['id']."'  AND type != 'SOA' AND content NOT IN (".$ns.") ", $db);
        $DOMAIN_INACTIVE = mysql_num_rows($SELECT_DOMAIN_INACTIVE);
        
        $new_record_time = time();
        
        //format name
        if ($_POST['name'] == ''){
			$NAME = $DOMAIN['name'];
        }else{
			$NAME = $_POST['name'] . "." . $DOMAIN['name'];
        }
			
        $INSERT = mysql_query("INSERT INTO `".$mysql_table."` (name, content, type, domain_id, ttl, prio, change_date, created, user_id, auth, disabled ) VALUES (      
            '" . $NAME . "',
            '" . addslashes($_POST['content']) . "',
            '" . addslashes($_POST['type']) . "',
            '".$DOMAIN['id']."',
            '" . addslashes($_POST['ttl']) . "',
            '" . addslashes($_POST['priority']) . "',
            '".$new_record_time."',
            '".$new_record_time."',
            '".$DOMAIN_USER['user_id']."',
            '0',
            '0'            
        )", $db);
        
        //Enable domain
        if ($DOMAIN_INACTIVE==0){
        	mysql_query("UPDATE `".$mysql_table."` SET disabled='0' WHERE type = 'SOA' OR content IN (".$ns.") ", $db);
        
        	//Delete any user_notifications about this domain
			mysql_query("DELETE FROM users_notifications WHERE domain = '".$DOMAIN['name']."' ".$user_id, $db);
		}

		$soa_update = update_soa_serial_byid($DOMAIN['id']);
		               
        if ($INSERT && $soa_update){
        	if ($_POST['formaction'] == 'add'){
				$add = "&action=add";
        	}
            header("Location: index.php?section=".$SECTION."&saved_success=1".$add.$sort_vars.$search_vars);
            exit();
        }else{
            $error_occured = TRUE;
        }
        
    }
        
}


if ($_POST['action'] == "edit" && $_POST['id']) {
    
    $id = $_POST['id'] = (int)$_POST['id'];
    
    $errors = array();
      
    if ($validate = validate_input($id, $DOMAIN['id'], $_POST['type'], $_POST['content'], $_POST['name'], $_POST['priority'], $_POST['ttl'])){
		$errors['validate'] = $validate;	
    }
        
    if (count($errors) == 0) {

        //format name
        if ($_POST['name'] == ''){
			$NAME = $DOMAIN['name'];
        }else{
			$NAME = $_POST['name'] . "." . $DOMAIN['name'];
        }
                
        $UPDATE = mysql_query("UPDATE `".$mysql_table."` SET
            content = '" . addslashes($_POST['content']) . "',
            name = '" . $NAME . "',
            prio = '" . addslashes($_POST['prio']) . "',
            ttl = '" . addslashes($_POST['ttl']) . "',
            type = '" . addslashes($_POST['type']) . "',
            change_date = '".time()."'
            
            WHERE id= '" . $_POST['id'] . "'",$db);
            
                               
            $soa_update = update_soa_serial_byid($DOMAIN['id']);
            
        if ($UPDATE && $soa_update){
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

	if ($_SESSION['admin_level'] == 'user'){
		$user_id = " AND user_id = '".$_SESSION['admin_id'] . "' ";  	
	}else{
		$user_id = '';
	}
    
    $SELECT_DOMAIN = mysql_query("SELECT domain_id FROM `".$mysql_table."` WHERE id = '".$id."' ". $user_id, $db);
    $DOMAIN = mysql_fetch_array($SELECT_DOMAIN);

    if (mysql_num_rows($SELECT_DOMAIN)){
	
		$DELETE = mysql_query("DELETE FROM `".$mysql_table."` WHERE `id`= '".$id."' ". $user_id ,$db);
		
	    $soa_update = update_soa_serial_byid($DOMAIN['domain_id']);
	    
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
    $id = addslashes($_POST['id']);
    $option = addslashes($_POST['option']);
    
    if ($_SESSION['admin_level'] == 'user'){
		$user_id = " AND user_id = '".$_SESSION['admin_id'] . "' ";  	
	}else{
		$user_id = '';
	}
    
    $SELECT_DOMAIN = mysql_query("SELECT domain_id FROM `".$mysql_table."` WHERE id = '".$id."' ". $user_id, $db);

    if (mysql_num_rows($SELECT_DOMAIN)){

        $DOMAIN = mysql_fetch_array($SELECT_DOMAIN);

	    $UPDATE = mysql_query("UPDATE `".$mysql_table."` SET `disabled` = '".$option."' WHERE `id` = '".$id."' ".$user_id,$db);
	    
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

?>

                <script>
                $(function() {
                	
                	
                	// most effect types need no options passed by default
                    var options = {};    
                    
                    // Hide/Show the ADD/EDIT Form
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
                    <?if ($_POST['action'] || $_GET['action'] == 'edit' || $_GET['action'] == 'add' || $items_number == 0){?>
                        $( "#toggler" ).show();
                       	$('#name').focus();
    				<?}else{?>
                        $( "#toggler" ).hide();
                    <?}?>
                    $( "#toggler2" ).show();
                    
                    
                    
                    <?if (staff_help()){?>
                    //TIPSY for the ADD Form
                    $('#name').tipsy({trigger: 'focus', gravity: 'n', fade: true});
                    $('#type').tipsy({trigger: 'focus', gravity: 'w', fade: true});
                    $('#content').tipsy({trigger: 'focus', gravity: 'w', fade: true});
                    $('#priority').tipsy({trigger: 'focus', gravity: 'w', fade: true});
                    $('#ttl').tipsy({trigger: 'focus', gravity: 'w', fade: true});
                    $('#action').tipsy({trigger: 'focus', gravity: 'w', fade: true});
                    <?}?>
                    

                    //DELETE RECORD
                    $('a.delete').click(function () {
                        var record_id = $(this).attr('rel');
                        if(confirm('Are you sure you want to delete this record?\n\rThis action cannot be undone!')){
                            $.post("index.php?section=<?=$SECTION;?>&action=delete&domain_id=<?=$DOMAIN['id'];?>", {
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
	                        if(confirm('Are you sure you want disable this record?')){    
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
		                    $.post("index.php?section=<?=$SECTION;?>&action=toggle_active&domain_id=<?=$DOMAIN['id'];?>", {
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
                
                
				//end                                
                });				                 
				
                </script>
                
                <!-- DOMAIN RECORDS START -->
                
                <div id="main_content">
                
                <div class="mainsubtitle_bg">
                    <div class="mainsubtitle"><a href="javascript: void(0)" id="button2">List all Records</a> | <?if ($_GET['action'] == 'edit'){?><a href="index.php?section=<?=$SECTION;?>&action=add&domain_id=<?=$DOMAIN['id'];?>" class="add"><span>Add New Record</span></a><?}else{?><a href="javascript: void(0)" id="button" class="add"><span>Add New Record</span></a><?}?> | <a href="index.php?section=<?=$SECTION;?>" class="back">Back to My Domains List</a></div>
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
                    
                        <!-- ADD RECORD START -->
                        <? if (count($errors) > 0) { ?>
                            <div id="errors">
                                <p>Please check:</p>
                                <ul>
                                    <? foreach ($errors as $key => $value) { echo "<li>" . $value . "</li>"; }?> 
                                </ul>
                            </div>
                        <? } ?>                        
                        
                        <form id="form" method="post" action="index.php?section=<?=$SECTION;?>&action=<?if ($_GET['action'] == 'edit'){ echo 'edit';}else{ echo 'add'; } ?><?=$sort_vars;?><?=$search_vars;?>">
                        
                            
                            <fieldset>
                                
                                <legend>&raquo; <?if ($_GET['action'] == 'edit'){?>Edit Record<?}else{?>Add Record<?}?></legend>
                        
                                     <div class="columns">
                                        <div class="colx2-left">
                                        
                                            <p>
                                                <label for="name" class="required">Record Name</label>
                                                <input type="text" name="name" id="name" title="Enter the Record Name" value="<?if($_POST['name']){ echo $_POST['name']; }elseif ($_GET['action'] == "edit"){ echo stripslashes(str_replace(".".$DOMAIN['name'], "", $RESULT['name']));} ?>"><strong>.<?=$DOMAIN['name'];?></strong>
                                            </p>
												
                                            <p>
                                                <label for="type" class="required">Record Type</label>
                                                <select name="type" id="type" title="Select Record Type" >
                                                    <option value="A"     <? if ($_POST['type'] == 'A'){     echo "selected=\"selected\""; }elseif ($_GET['action'] == "edit" && $RESULT['type'] == 'A'){     echo "selected=\"selected\""; }?> >A</option>
													<option value="MX"    <? if ($_POST['type'] == 'MX'){    echo "selected=\"selected\""; }elseif ($_GET['action'] == "edit" && $RESULT['type'] == 'MX'){    echo "selected=\"selected\""; }?> >MX</option>
													<option value="CNAME" <? if ($_POST['type'] == 'CNAME'){ echo "selected=\"selected\""; }elseif ($_GET['action'] == "edit" && $RESULT['type'] == 'CNAME'){ echo "selected=\"selected\""; }?> >CNAME</option>
													<option value="TXT"   <? if ($_POST['type'] == 'TXT'){   echo "selected=\"selected\""; }elseif ($_GET['action'] == "edit" && $RESULT['type'] == 'TXT'){   echo "selected=\"selected\""; }?> >TXT</option>
													<option value="SPF"   <? if ($_POST['type'] == 'SPF'){   echo "selected=\"selected\""; }elseif ($_GET['action'] == "edit" && $RESULT['type'] == 'SPF'){   echo "selected=\"selected\""; }?> >SPF</option>
													<option value="NS"    <? if ($_POST['type'] == 'NS'){    echo "selected=\"selected\""; }elseif ($_GET['action'] == "edit" && $RESULT['type'] == 'NS'){    echo "selected=\"selected\""; }?> >NS</option>
													<option value="SRV"   <? if ($_POST['type'] == 'SRV'){   echo "selected=\"selected\""; }elseif ($_GET['action'] == "edit" && $RESULT['type'] == 'SRV'){   echo "selected=\"selected\""; }?> >SRV</option>
													<option value="PTR"   <? if ($_POST['type'] == 'PTR'){   echo "selected=\"selected\""; }elseif ($_GET['action'] == "edit" && $RESULT['type'] == 'PTR'){   echo "selected=\"selected\""; }elseif ($ISREVERSE == true && !$_POST && $_GET['action'] != 'edit'){ echo "selected=\"selected\""; }?> >PTR</option>
                                                </select>
                                            </p>
                                            
                                            <p>
                                                <label for="content" class="required">Content</label>
                                                <input type="text" name="content" id="content" title="Enter the Content for this Record" value="<?if($_POST['content']){ echo $_POST['content']; }elseif ($_GET['action'] == "edit"){ echo stripslashes($RESULT['content']);} ?>">
                                            </p>
												
                                            
                                        </div>
                                        <div class="colx2-right">
                                        
                                        	
                                            <p>
                                                <label for="priority">Priority</label>
                                                <input type="text" name="priority" id="priority" title="Enter the Priority (for SRV,MX,etc. or leave 0 for A, CNAME, etc)" value="<?if($_POST['priority']){ echo $_POST['priority']; }elseif ($_GET['action'] == "edit"){ echo stripslashes($RESULT['prio']);}else{ echo 0; } ?>">
                                            </p>
												
                                            <p>
                                                <label for="ttl" class="required">TTL</label>
                                                <input type="text" name="ttl" id="ttl" title="Enter the Record TTL (Time To Live)" value="<?if($_POST['ttl']){ echo $_POST['ttl']; }elseif ($_GET['action'] == "edit"){ echo stripslashes($RESULT['ttl']);}else{ echo $CONF['RECORDS_TTL']; } ?>">
                                            </p>
                                            
                                            <?if ($_GET['action'] != 'edit'){?>
                                            <p>
                                                <label for="formaction">Add another record after submit</label>
                                                <input type="checkbox" name="formaction" id="formaction" style="width:12px; margin:7px;" title="Check to add another record after submit" value="add" checked="checked" />
                                            </p>
                                            <?}?>                                                                                        
											
                                        </div>
                                        
                                     </div>
                        
                           </fieldset>

                           <fieldset>
                                <legend>&raquo; Action</legend>
                                <button type="submit"  >Save</button>&nbsp; &nbsp;
                                <button type="reset"  id="button">Cancel</button>
                                <input  type="hidden" name="action" id="action" value="<?if ($_GET['action'] == 'edit'){ echo 'edit';}else{ echo 'add'; } ?>" />
                                <input  type="hidden" name="domain_id" id="domain_id" value="<?=$DOMAIN['id'];?>" />
                                <?if ($_GET['action'] == 'edit'){?><input  type="hidden" name="id" id="id" value="<?=$RESULT['id'];?>" /><?}?>
                           </fieldset>
                        </form>                    
                        
                        <!-- ADD RECORD END -->
                        
                        <br />
                        
                    </div>
                        
                    <div id="toggler2">
                      
                    <!-- LIST RECORDS START -->
                      
                      <fieldset>
                                
                          <legend>&raquo; DNS Records List</legend>
                        
                      <form name="search_form" action="index.php?section=<?=$SECTION;?>" method="get" class="search_form">
                        <input type="hidden" name="section" value="<?=$SECTION;?>" />
                        <input  type="hidden" name="domain_id" id="domain_id" value="<?=$DOMAIN['id'];?>" />
                        <table border="0" cellspacing="0" cellpadding="4">
                            <tr>
                                <td>Record:</td>
                                <td><input type="text" name="q" id="search_field_q" class="input_field" value="<?=$q?>" /></td>
                                <td><button type="submit"  >Search</button></td>
                            </tr>
                        </table> 
                      </form>

                      <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:15px; margin-top: 15px;">
                        <tr>
                            <td width="36%" height="30">
                                <h3 style="margin:0"><?=$action_title;?> <? if ($q) { ?><span style="font-size:12px"> (<a href="index.php?section=<?=$SECTION;?>&domain_id=<?=$DOMAIN['id'];?>">x</a>)</span><? } ?></h3> 
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
                        <th><?=create_sort_link("name","Name");?></th>
                        <th><?=create_sort_link("type","Type");?></th>
                        <th><?=create_sort_link("content","Content");?></th>
                        <th><?=create_sort_link("prio","Priority");?></th>
                        <th><?=create_sort_link("ttl","TTL");?></th>
                        <?/*<th><?=create_sort_link("disabled","Enabled");?></th>*/?>
                        <th><a href="javascript:void(0)" <?if (staff_help()){?>class="tip_south"<?}?> title="Use the icons bellow manage your Domain.">Actions</a></th>
                      </tr>
                      <!-- RESULTS START -->
                      <?
                      $i=-1;
                      while($LISTING = mysql_fetch_array($SELECT_RESULTS)){
                      $i++;
                      ?>     
                      <tr onmouseover="this.className='on' " onmouseout="this.className='off' " id="tr-<?=$LISTING['id'];?>">
                        <td align="left" nowrap><a href="index.php?section=domain&action=edit&id=<?=$LISTING['id'];?><?=$search_vars;?><?=$sort_vars;?>" <?if (staff_help()){?>class="tip_south"<?}?> title="Edit Record" ><?=$LISTING['name'];?></a></td>
                        <td align="center" nowrap><?=$LISTING['type'];?></td>
                        <td align="left" nowrap><?=$LISTING['content'];?></td>
                        <td align="center" nowrap><?=$LISTING['prio'];?></td>
                        <td align="center" nowrap><?=$LISTING['ttl'];?></td>
                        <?/*<td align="center" nowrap><a href="javascript:void(0)" class="<?if (staff_help()){?>tip_south<?}?> toggle_active <? if ($LISTING['disabled'] != '1') { ?>activated<? } else { ?>deactivated<? } ?>" rel="<?=$LISTING['id']?>" title="Enable/Disable"><span>Enable/Disable</span></a></td>*/?>
                        <td align="center" nowrap="nowrap">
                            <a href="index.php?section=domain&action=edit&id=<?=$LISTING['id'];?><?=$search_vars;?><?=$sort_vars;?>" title="Edit Record" class="<?if (staff_help()){?>tip_south<?}?> edit"><span>Edit Record</span></a> &nbsp; 
                            <a href="javascript:void(0)" rel="tr-<?=$LISTING['id']?>" title="Delete Record" class="<?if (staff_help()){?>tip_south<?}?> delete"><span>Delete Record</span></a>
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
                    
                    <!-- LIST RECORDS END -->
                    
                    </div>
                        
                </div>    
                
                <!-- DOMAIN RECORDS END --> 
                
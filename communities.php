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

if ($_SESSION['admin_level'] != 'admin'){
	header ("Location: index.php");
    exit();
}

//Define current page data
$mysql_table = 'communities';
$sorting_array = array("id", "name", "region", "description", "email", "active");

// ----------------------------------------------------------------------

$action_title = "All Wireless Communities"; 

//if ($action == "list") {
    
    $search_vars = "";
        
    $q = mysql_real_escape_string($_GET['q'], $db);
    if ($q) { 
        $search_vars .= "&q=$q"; 
        $action_title = "Search: " . $q;
    }
    $search_query = "WHERE ($mysql_table.name LIKE '%$q%' OR $mysql_table.region LIKE '%$q%' OR $mysql_table.description LIKE '%$q%' OR $mysql_table.email LIKE '%$q%')";
    
    
    // Sorting
    if (isset($_GET['sort'])){
        if (in_array($_GET['sort'], $sorting_array)) {
            if ($_GET['by'] !== "desc" && $_GET['by'] !== "asc") {
                $_GET['by'] = "desc";
            }
            $order = "ORDER BY `". addslashes($_GET['sort']) ."` ". addslashes($_GET['by']) . " ";
        }
    } else {
        $order = "ORDER BY `name` ASC ";
        $_GET['sort'] = "name";
        $_GET['by'] = "asc";
    }
    $sort_vars = "&sort=".$_GET['sort']."&by=".$_GET['by'];


    // Paging
    $count = mysql_query("SELECT id FROM $mysql_table $search_query",$db);
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
    $SELECT_RESULTS  = mysql_query("SELECT `".$mysql_table."`.* FROM `".$mysql_table."` ".$search_query." ".$order." LIMIT ".$pageno.", ".$e ,$db);
    $url_vars = "action=".$_GET['action'] . $sort_vars . $search_vars;
    



//SELECT RECORD FOR EDIT
if ( $_GET['action'] == "edit" && $_GET['id'] ) {
    $SELECT = mysql_query("SELECT * FROM `".$mysql_table."` WHERE `id`='".addslashes($_GET['id'])."'",$db);
    $RESULT = mysql_fetch_array($SELECT);
}

//ADD NEW RECORD
if ($_POST['action'] == "add" ) {
    
    $errors = array();
    
    $_POST['name'] = trim($_POST['name']);
    if (!$_POST['name']) {
        $errors['name'] = "Please enter a Wireless Community Name. eg: AWMN";
    } else {
        
        if (mysql_num_rows(mysql_query("SELECT id FROM `".$mysql_table."` WHERE `name` = '".addslashes($_POST['name'])."' ",$db))){
            $errors['name'] = "Wireless Community is already in registered on this system." ;
        } 
    }
    
    $_POST['region'] = trim($_POST['region']);
    if (!$_POST['region']) {
        $errors['region'] = "Please enter community region" ;
    }
    
    
    $_POST['email'] = trim($_POST['email']);
    if ($_POST['email']){
        if (!preg_match("/^([a-zA-Z0-9]+([\.+_-][a-zA-Z0-9]+)*)@(([a-zA-Z0-9]+((\.|[-]{1,2})[a-zA-Z0-9]+)*)\.[a-zA-Z]{2,7})$/", $_POST['email'])) {
            $errors['email'] = "The Email address you gave is not valid" ;
        }
    }
    
    if (count($errors) == 0) {
        
        $INSERT = mysql_query("INSERT INTO `".$mysql_table."` (name, region, description, email, active) VALUES (      
            '" . addslashes($_POST['name']) . "',
            '" . addslashes($_POST['region']) . "',
            '" . addslashes($_POST['description']) . "',
            '" . addslashes($_POST['email']) . "',
            '1'
        )", $db);

        if ($INSERT){
            header("Location: index.php?section=".$SECTION."&saved_success=1");
            exit();
        }else{
            $error_occured = TRUE;
        }
        
    }
        
}


if ($_POST['action'] == "edit" && $_POST['id']) {
    
    $id = $_POST['id'] = (int)$_POST['id'];
    
    $errors = array();
    
    $_POST['name'] = trim($_POST['name']);
    if (!$_POST['name']) {
        $errors['name'] = "Please enter a Wireless Community Name. eg: AWMN";
    } else {
        
        if (mysql_num_rows(mysql_query("SELECT id FROM `".$mysql_table."` WHERE `name` = '".addslashes($_POST['name'])."' AND id != '".addslashes($id)."' ",$db))){
            $errors['name'] = "Wireless Community is already in registered on this system." ;
        } 
    }
    
    $_POST['region'] = trim($_POST['region']);
    if (!$_POST['region']) {
        $errors['region'] = "Please enter community region" ;
    }
    
    
    $_POST['email'] = trim($_POST['email']);
    if ($_POST['email']){
        if (!preg_match("/^([a-zA-Z0-9]+([\.+_-][a-zA-Z0-9]+)*)@(([a-zA-Z0-9]+((\.|[-]{1,2})[a-zA-Z0-9]+)*)\.[a-zA-Z]{2,7})$/", $_POST['email'])) {
            $errors['email'] = "The Email address you gave is not valid" ;
        }
    }
        
    if (count($errors) == 0) {
        
        $UPDATE = mysql_query("UPDATE `".$mysql_table."` SET
            name = '" . addslashes($_POST['name']) . "',
            region = '" . addslashes($_POST['region']) . "',
            description = '" . addslashes($_POST['description']) . "',
            email = '" . addslashes($_POST['email']) . "'
            
            WHERE id= '" . $_POST['id'] . "'",$db);
        
        if ($change_pass) {
            $UPDATE_PASS = mysql_query("UPDATE `".$mysql_table."` SET password = '" . sha1($_POST['password']) . "' WHERE id= '" . $_POST['id'] . "'",$db);
        }
        
        if ($UPDATE){
            $_SESSION['admin_help'] = $_POST['Help'];
            header("Location: index.php?section=".$SECTION."&saved_success=1&change_pass=".$change_pass);
            exit();
        }else{
            $error_occured = TRUE;
        }
        
    }
    
}




// DELETE RECORD
if ($_GET['action'] == "delete" && $_POST['id']){
    $id = addslashes(str_replace ("tr-", "", $_POST['id']));
    
	//Delete user notifications
	$DELETE = mysql_query("DELETE FROM communities WHERE id = '".$id."' ", $db);
	
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
    
    $UPDATE = mysql_query("UPDATE `".$mysql_table."` SET `active` = '".$option."' WHERE `id`= '".$id."'",$db);
    
    if ($UPDATE) {
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

                <script>
                $(function() {
                    
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
                            $('#toggle_state').val('1');
                        } );
                        return false;
                    });
                    
                    //Init
                    <?if ($_POST['action'] || $_GET['action'] == 'edit' || $_GET['action'] == 'add'){?>
                        $( "#toggler" ).show();
                        $('#name').focus();
                    <?}else{?>
                        $( "#toggler" ).hide();
                    <?}?>
                    $( "#toggler2" ).show();
                    
                    <?if (staff_help()){?>
                    //TIPSY for the ADD Form
                    $('#name').tipsy({trigger: 'focus', gravity: 'w', fade: true});
                    $('#region').tipsy({trigger: 'focus', gravity: 'w', fade: true});
                    $('#description').tipsy({trigger: 'focus', gravity: 'w', fade: true});
                    $('#email').tipsy({trigger: 'focus', gravity: 'w', fade: true});
                    <?}?>
                    

                    //DELETE RECORD
                    $('a.delete').click(function () {
                        var record_id = $(this).attr('rel');
                        if(confirm('Are you sure you want to delete this record?\n\rThis action cannot be undone!')){
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
    
    
                    //SET ACTIVE FLAG
                    $('a.toggle_active').click(function () {
                        if ($(this).hasClass('activated')){    
                            var option = '0';
                        } else if ($(this).hasClass('deactivated')){
                            var option = '1';
                        }
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
                
                <!-- COMMUNITIES SECTION START -->

                <div id="main_content">
                
                <div class="mainsubtitle_bg">
                    <div class="mainsubtitle"><a href="javascript: void(0)" id="button2">List Communities</a> | <?if ($_GET['action'] == 'edit'){?><a href="index.php?section=<?=$SECTION;?>&action=add" class="add"><span>Add New Community</a><?}else{?><a href="javascript: void(0)" id="button" class="add"><span>Add New Community</a><?}?></div>
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
                    
                        <!-- ADD/EDIT COMMUNITIES START -->
                        <? if (count($errors) > 0) { ?>
                            <div id="errors">
                                <p>Please check:</p>
                                <ul>
                                    <? foreach ($errors as $key => $value) { echo "<li>" . $value . "</li>"; }?> 
                                </ul>
                            </div>
                        <? } ?>                        
                        
                        <form id="form" method="post" action="index.php?section=<?=$SECTION;?>&action=<?if ($_GET['action'] == 'edit'){ echo 'edit&id='.$_GET['id'];}else{ echo 'add'; } ?>">
                        
                            
                            <fieldset>
                                
                                <legend>&raquo; <?if ($_GET['action'] == 'edit'){?>Edit Community<?}else{?>New Community<?}?></legend>
                        
                                     <div class="columns">
                                        <div class="colx2-left">
                                        
                                            <p>
                                                <label for="name" class="required">Name</label>
                                                <input type="text" name="name" id="name" title="Enter the Wireless Community Name" value="<? if($_POST['name']){ echo $_POST['name']; }elseif ($_GET['action'] == "edit"){ echo stripslashes($RESULT['name']);} ?>">
                                            </p>
                                            
                                            <p>
                                                <label for="region" class="required">Region</label>
                                                <input type="text" name="region" id="region" title="Enter the Wireless Community Region" value="<? if($_POST['region']){ echo $_POST['region']; }elseif ($_GET['action'] == "edit"){ echo stripslashes($RESULT['region']);} ?>">
                                            </p>
                                            											                                           
                                        </div>
                                        <div class="colx2-right">
                                                                                        
                                            <p>
                                                <label for="description">Description</label>
                                                <input type="text" name="description" id="description" title="Enter a Description" value="<? if($_POST['description']){ echo $_POST['description']; }elseif ($_GET['action'] == "edit"){ echo stripslashes($RESULT['description']);} ?>">
                                            </p>

                                            <p>
                                                <label for="email">E-Mail</label>
                                                <input type="text" name="email" id="email" title="Enter a contact Email for this Community if there is one." value="<?if($_POST['email']){ echo $_POST['email']; }elseif ($_GET['action'] == "edit"){ echo stripslashes($RESULT['email']);} ?>">
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
                        
                        <!-- ADD/EDIT COMMUNITIES END -->
                        
                        <br />
                        
                    </div>
                        
                    <div id="toggler2">
                      
                    <!-- LIST COMMUNITIES START -->
                      
                      <fieldset>
                                
                          <legend>&raquo; Communities List</legend>
                        
                      <form name="search_form" action="index.php?section=<?=$SECTION;?>" method="get" class="search_form">
                        <input type="hidden" name="section" value="<?=$SECTION;?>" />
                        <table border="0" cellspacing="0" cellpadding="4">
                            <tr>
                                <td>Keywords:</td>
                                <td><input type="text" name="q" id="search_field_q" class="input_field" value="<?=$q?>" /></td>
                    
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
                        <th><?=create_sort_link("username","Name");?></th>
                        <th><?=create_sort_link("region","Region");?></th>
                        <th><?=create_sort_link("description", "Description");?></th>
                        <th><?=create_sort_link("email", "Email");?></th>
                        <th><?=create_sort_link("active", "Active");?></th>
                        <th>Actions</th>
                      </tr>
                      <!-- RESULTS START -->
                      <?
                      $i=-1;
                      while($LISTING = mysql_fetch_array($SELECT_RESULTS)){
                      $i++;  
                      ?>      
                      <tr onmouseover="this.className='on' " onmouseout="this.className='off' " id="tr-<?=$LISTING['id'];?>">
                        <td nowrap><a href="index.php?section=<?=$SECTION;?>&action=edit&id=<?=$LISTING['id'];?>" title="Edit community" class="<?if (staff_help()){?>tip_south<?}?>"><?=$LISTING['name'];?></a></td>
                        <td ><?=$LISTING['region'];?></td>
                        <td ><?=$LISTING['description'];?></td>
                        <td align="center"><a href="mailto:<?=$LISTING['email'];?>" <?if (staff_help()){?>class="tip_south"<?}?> title="Community Contact"><?=$LISTING['email'];?></a></td>
                        <td align="center" >
                            <a href="javascript:void(0)" style="margin:0 auto" class="<?if (staff_help()){?>tip_south<?}?> toggle_active <? if ($LISTING['active'] == '1') { ?>activated<? } else { ?>deactivated<? } ?>" rel="<?=$LISTING['id']?>" title="Enable/Disable"><span>Enable/Disable</span></a>
                        </td>
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
                    
                    <!-- LIST COMMUNITIES END -->
                    
                    </div>
                        
                </div>    
                
                <!-- COMMUNITIES SECTION END --> 
                
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

// Access is only allowed for admin users
if ($_SESSION['admin_level'] != 'admin'){
	header ("Location: index.php");
    exit();
}


if ($_GET['parent_id']){
	$pid = mysql_real_escape_string($_GET['parent_id'], $db);
	$pid_vars = "&parent_id=".$pid	;
}else{
	header ("Location: ./index.php?section=root_ns");
	exit();
}

   
$search_vars = "&parent_id=".$pid;


//Define current page data
$mysql_table = 'root_ns_unicast';
$sorting_array = array("id", "name", "ip", "active");

//Select parent root ns to show the name
$SELECT_ROOT_NS = mysql_query("SELECT name, ip FROM root_ns WHERE id = '".$pid."' ", $db);
$ROOT_NS = mysql_fetch_array($SELECT_ROOT_NS);

$action_title = "Manage Unicast NOTIFY IPs for Root Nameserver: " . $ROOT_NS['name']; 
    
$search_vars = "";
    
$q = mysql_real_escape_string($_GET['q'], $db);
if ($q) { 
	$search_vars .= "&q=$q"; 
	$action_title = "Search: " . $q;
}
$search_query = "WHERE ($mysql_table.name LIKE '%$q%' OR $mysql_table.ip LIKE '%$q%' OR $mysql_table.active LIKE '%$q%' ) AND parent_id = '".$pid."' ";


// Sorting
if (isset($_GET['sort'])){
    if (in_array($_GET['sort'], $sorting_array)) {
    	if ($_GET['by'] !== "desc" && $_GET['by'] !== "asc") {
        	$_GET['by'] = "desc";
    	}
    	$order = "ORDER BY `". addslashes($_GET['sort']) ."` ". addslashes($_GET['by']) . " ";
	}
}else{
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
}else{ 
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
	}else{
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
// Total Pages 
$total_pages=$i;

//Final Query for records listing
$SELECT_RESULTS  = mysql_query("SELECT `".$mysql_table."`.* FROM `".$mysql_table."` ".$search_query." ".$order." LIMIT ".$pageno.", ".$e ,$db);
$url_vars = "action=".$_GET['action'] . $sort_vars . $search_vars;




//ADD NEW RECORD
if ($_POST['action'] == "add" && $_POST['parent_id']) {

    $errors = array();
    
    $_POST['name'] = trim($_POST['name']);
    if (!preg_match("/^(?!-)^(?!\.)[a-z0-9-\.]{1,63}(?<!-)(?<!\.)$/", $_POST['name'])) {
        $errors['name'] = "Please choose a Unicast IP Name with 2 to 10 latin lowercase characters without numbers, spaces and symbols.";
    }else{
        if (mysql_num_rows(mysql_query("SELECT id FROM `".$mysql_table."` WHERE `name` = '".addslashes($_POST['name'])."' ",$db))){
            $errors['name'] = "This name is already registered on this system." ;
        } 
    }
    
    $_POST['ip'] = trim($_POST['ip']);
    if(!filter_var($_POST['ip'], FILTER_VALIDATE_IP)){
		$errors['ip'] = "Please enter a valid Unicast IP Address.";	
	}else{
		if (mysql_num_rows(mysql_query("SELECT id FROM `".$mysql_table."` WHERE `ip` = '".addslashes($_POST['ip'])."' ",$db))){
            $errors['ip'] = "This IP is already registered on this system." ;
        }		
	}
	
	
	if ($_POST['Provision'] == '1'){
		
	    $_POST['real_ip'] = trim($_POST['real_ip']);
	    if(!filter_var($_POST['real_ip'], FILTER_VALIDATE_IP)){
			$errors['real_ip'] = "Please enter a valid Real IP Address.";	
		}
		
	    $_POST['cache_ip'] = trim($_POST['cache_ip']);
	    if(!filter_var($_POST['cache_ip'], FILTER_VALIDATE_IP)){
			$errors['cache_ip'] = "Please enter a valid Anycast Cache IP Address.";	
		}

	    $_POST['owner_ssh_key'] = trim($_POST['owner_ssh_key']);    
	    if (strlen($_POST['owner_ssh_key']) > 1 && preg_match("/^(ssh-rsa|ssh-dss) AAAA[0-9A-Za-z+\\/]+[=]{0,3}/", $_POST['owner_ssh_key']) != 1 ){ 
    		$errors['owner_ssh_key'] = "Enter a valid SSH Public Key" ; 
	    }
	    
	    $SELECT_TSIG = mysql_query("SELECT secret FROM tsigkeys WHERE name = '".$ROOT_NS['name']."' ", $db);
	    if (mysql_num_rows($SELECT_TSIG)){
			$TSIG = mysql_fetch_array($SELECT_TSIG);	
	    }else{
			$errors['tsig'] = "Could not fetch TSIG key for " . $ROOT_NS['name'];
	    }
	    
	    //Looking good, proceed with connecting to the new root ns to provision the configuration
		if (count($errors) == 0) {
		
			// run ssh here
			$ssh_command = "/usr/local/bin/provision_root_ns.php '" . $ROOT_NS['ip'] ."' '"  . $_POST['ip'] . "' '" . $_POST['cache_ip'] . "' '" . $ROOT_NS['name'] . "' '" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . "' '" . $TSIG['secret'] . "' '" . $_POST['owner_ssh_key'] . "' ";
			
			if ( ssh_client2($_POST['real_ip'], $ssh_command) == false){
				$errors['provision'] = "Automatic Provisioning Failed :( Please configure the Root NS manually.";
			}		
		}    
	    
		
	}							
    
    
    if (count($errors) == 0) {
        
        $INSERT = mysql_query("INSERT INTO `".$mysql_table."` (parent_id, name, ip, active) VALUES (      
            '" . addslashes($_POST['parent_id']) . "',
            '" . addslashes($_POST['name']) . "',
            '" . addslashes($_POST['ip']) . "',
            '1'
        )", $db);

        if ($INSERT){
        	
        	//Insert new ALSO-NOTIFY IP to all existing domains
			$SELECT_DOMAINS = mysql_query("SELECT id, name, type FROM domains", $db);
			while ($DOMAINS = mysql_fetch_array($SELECT_DOMAINS)){
				
				//Insert the ALSO-NOTIFY record to notify the root NS on its UNICAST IP. 				
				mysql_query("INSERT INTO `domainmetadata` (`domain_id`, `kind`, `content` ) VALUES (
							'".$DOMAINS['id']."', 
							'ALSO-NOTIFY',
							'".addslashes($_POST['ip'])."'
							
				)", $db);
				
				//Insert the ALSO-NOTIFY records to notify meta-slaves for automatic provision of the new zone on the slaves. 				
				mysql_query("INSERT INTO `domainmetadata` (`domain_id`, `kind`, `content` ) VALUES (
							'".$DOMAINS['id']."', 
							'ALSO-NOTIFY',
							'".addslashes($_POST['ip']).":".$CONF['META_SLAVE_PORT']."'
							
				)", $db);
				
				if ($DOMAINS['id'] != '1'&& $DOMAINS['type'] != 'SLAVE'){
					$soa_update = update_soa_serial_byid($DOMAINS['id']);
				}
						
			}        	
        	
        	
        	header("Location: index.php?section=".$SECTION."&saved_success=1".$pid_vars);
            exit();
        }else{
            $error_occured = TRUE;
        }
        
    }
        
}


// DELETE RECORD
if ($_GET['action'] == "delete" && $_POST['id']){
    $id = mysql_real_escape_string(str_replace ("tr-", "", $_POST['id']), $db);
    
    $SELECT_ROOT_NS = mysql_query("SELECT `ip` FROM `".$mysql_table."`  WHERE id = '".$id."' ");
    $ROOT_NS = mysql_fetch_array($SELECT_ROOT_NS);
    $DELETE = mysql_query("DELETE FROM `".$mysql_table."` WHERE `id`= '".$id."' " ,$db);
    $DELETE = mysql_query("DELETE FROM `domainmetadata` WHERE `content`= '".$ROOT_NS['ip']."' " ,$db);
    $DELETE = mysql_query("DELETE FROM `domainmetadata` WHERE `content`= '".$ROOT_NS['ip'].":".$CONF['META_SLAVE_PORT']."' " ,$db);
    
    #Update SOA on all domains
    $SELECT_DOMAINS = mysql_query("SELECT id, name FROM domains WHERE id != '1' ", $db);
	while ($DOMAINS = mysql_fetch_array($SELECT_DOMAINS)){
			$soa_update = update_soa_serial_byid($DOMAINS['id']);
    }
    
    if ($DELETE){
        ob_end_clean();
        echo "ok";
    }else{
        ob_end_clean();
        echo "An error has occured.";
    }
    exit();
} 

/*
// ENABLE/DISABLE RECORD
if ($_GET['action'] == "toggle_active" && $_POST['id'] && isset($_POST['option'])){
    $id = addslashes($_POST['id']);
    $option = addslashes($_POST['option']);
    
    $UPDATE = mysql_query("UPDATE `".$mysql_table."` SET `active` = '".$option."' WHERE `id`= '".$id."'",$db);
    
    if ($UPDATE) {
        //print_r($_GET);
        ob_clean();
        echo "ok";
    }else{
        ob_clean();
        echo "An error has occured.";
    }
    exit();
}
*/

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
                    $('#ip').tipsy({trigger: 'focus', gravity: 'w', fade: true});
                    $('#tsig_key').tipsy({trigger: 'focus', gravity: 'w', fade: true});
                    $('#Provision').tipsy({trigger: 'focus', gravity: 'w', fade: true});
                    $('#real_ip').tipsy({trigger: 'focus', gravity: 'w', fade: true});
                    $('#cache_ip').tipsy({trigger: 'focus', gravity: 'w', fade: true});
                    $('#owner_ssh_key').tipsy({trigger: 'focus', gravity: 'w', fade: true});
                    <?}?>
                    

                    //DELETE RECORD
                    $('a.delete').click(function () {
                        var record_id = $(this).attr('rel');
                        if(confirm('Are you sure you want to delete this record?\n\rThis action cannot be undone!')){
                            $.post("index.php?section=<?=$SECTION;?>&action=delete<?=$pid_vars;?>", {
                                id: record_id
                            }, function(response){
                                if (response == "ok"){
                                    $('#'+record_id).hide();
                                    $("#notification_success_response").html('Record deleted successfully.');
                                    $('.notification_success').show();
                                    var total_records = $('span#total_records').html();
                                     total_records--;
                                     $('span#total_records').html(total_records);
                                }else{
                                    $("#notification_fail_response").html('An error occured.' );
                                    $('.notification_fail').show();
                                    //alert(response);
                                }
                            });
                            return false;
                        }
                    });

                    <?/*
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
	                        }else{
	                            $("#notification_fail_response").html('An error occured.' );
	                            $('.notification_fail').show();
	                            //alert(response);
	                        }
                        });
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
                
                
                //SHOW/HIDE INPUT FIELDS BASED ON DROPDOWN MENU SELECTION
                <?if ($_POST['Provision'] == '1') {?>
                $('#Provision_form').show();
                <?}else{?>
                $('#Provision_form').hide();
                <?}?>
                
                $('#Provision').click(function(){
                    //var myval = $('value',this).val();
                    if( $(this).is(':checked')) {
                        $('#Provision_form').show();
                    }else{
                        $('#Provision_form').hide();
                    }
                });                  

                
                });
                

                </script>
                
                <!-- UNICAST IPs SECTION START -->

                <div id="main_content">
                
                <div class="mainsubtitle_bg">
                    <div class="mainsubtitle"><a href="javascript: void(0)" id="button2">List Unicast NOTIFY IPs</a> | <a href="javascript: void(0)" id="button" class="add"><span>Add New Unicast NOTIFY IP</a> | <a href="index.php?section=root_ns" class="back"><span>Back to Root NS</span></a></div>
                </div> 
                
                <br />
                            
                    
                    <? if ($_GET['saved_success']) { ?>
                        <p class="success"><span style="float: right;"><a href="javascript:void(0)" style="margin:0 auto" class="<?if (staff_help()){?>tip_east<?}?> close_notification" rel="success" title="Close notification bar"><span>Close Notification Bar</span></a></span>
                        Record saved successfully.</p>
                    <? } ?>
                    <? if ($error_occured) { ?>
                        <p class="error"><span style="float: right;"><a href="javascript:void(0)" style="margin:0 auto" class="<?if (staff_help()){?>tip_east<?}?> close_notification" rel="error" title="Close notification bar"><span>Close Notification Bar</span></a></span>An error occured.</p>
                    <? } ?>
                    
                    <p class="notification_success"><span style="float: right;"><a href="javascript:void(0)" style="margin:0 auto" class="<?if (staff_help()){?>tip_east<?}?> close_notification" rel="notification_success" title="Close notification bar"><span>Close Notification Bar</span></a></span><span id="notification_success_response"></span></p>
                    <p class="notification_fail"><span style="float: right;"><a href="javascript:void(0)" style="margin:0 auto" class="<?if (staff_help()){?>tip_east<?}?> close_notification" rel="notification_fail" title="Close notification bar"><span>Close Notification Bar</span></a></span><span id="notification_fail_response"></span></p>
                        
                    <div id="toggler">
                    
                        <!-- ADD UNICAST IP START -->
                        <? if (count($errors) > 0) { ?>
                            <div id="errors">
                                <p>Please check:</p>
                                <ul>
                                    <? foreach ($errors as $key => $value) { echo "<li>" . $value . "</li>"; }?> 
                                </ul>
                            </div>
                        <? } ?>                        
                        
                        <form id="form" method="post" action="index.php?section=<?=$SECTION;?>&action=add&parent_id=<?=$pid;?>">
                            <fieldset>
                                <legend>&raquo; New Secondary NOTIFY IP</legend>
                                     <div class="columns">
                                        <div class="colx2-left">
                                            <p>
                                                <label for="name" class="required">Unicast IP Name</label>
                                                <input type="text" name="name" id="name" title="Enter a name to identify this IP. eg: ns1-loc1.tld" value="<? if($_POST['name']){ echo $_POST['name']; } ?>">
                                            </p>
                                        
                                            <p>
                                                <label for="ip" class="required">Unicast NOTIFY IP Address</label>
                                                <input type="text" name="ip" id="ip" title="Enter the Unicast NOTIFY IP Address" value="<? if($_POST['ip']){ echo $_POST['ip']; } ?>">
                                            </p>
                                            
                                            <p>
                                                <label for="Provision">Provision new Root NS Image</label>
                                                <input type="checkbox" name="Provision" id="Provision" style="width:12px; margin:7px;" title="Automatic Provision of new Root NS installation. Use with caution." value="1" <? if ($_POST['Provision'] == '1'){ echo " checked=\"checked\""; }?> />
                                            </p>
                                            
                                            
                                        </div>
                                        
                                        <div class="colx2-right">
                                            
                                            <div id="Provision_form">
                                            
	                                            <p>
	                                                <label for="real_ip" class="required">Root NS Real IP</label>
	                                                <input type="text" name="real_ip" id="real_ip" title="Enter the real root NS IP Address (the one assigned by DHCP)" value="<? if($_POST['real_ip']){ echo $_POST['real_ip']; } ?>">
	                                            </p>

	                                            <p>
	                                                <label for="cache_ip" class="required">Anycast Cache IP Address</label>
	                                                <input type="text" name="cache_ip" id="cache_ip" title="Enter the Anycast Cache IP Address (for BIND)" value="<? if($_POST['cache_ip']){ echo $_POST['cache_ip']; } ?>">
	                                            </p>
	                                            
	                                            
                                            	<p>
	                                                <label for="owner_ssh_key" class="required">Owner SSH Public Key</label>
	                                                <input type="text" name="owner_ssh_key" id="owner_ssh_key" title="Enter the Root NS owner SSH Public Key for read only access" value="<? if($_POST['owner_ssh_key']){ echo $_POST['owner_ssh_key']; } ?>">
	                                            </p>
	                                            
	                                            
                                            </div>
                                                                                    
                                        </div>
                                     </div>
                           </fieldset>
                           <fieldset>
                                <legend>&raquo; Action</legend>
                                <button type="submit"  >Save</button>&nbsp; &nbsp;
                                <button type="reset"  id="button">Cancel</button>
                                <input  type="hidden" name="action" id="action" value="add" />                                
                                <input  type="hidden" name="parent_id" id="parent_id" value="<?=$pid;?>" />                                
                           </fieldset>
                        </form>                    
                        
                        <!-- ADD UNICAST IP END -->
                        
                        <br />
                        
                    </div>
                        
                    <div id="toggler2">
                      
                    <!-- LIST UNICAST IPs START -->
                      
                      <fieldset>
                                
                          <legend>&raquo; Unicast NOTIFY IPs List</legend>
                        
                      <form name="search_form" action="index.php?section=<?=$SECTION;?>" method="get" class="search_form">
                        <input type="hidden" name="section" value="<?=$SECTION;?>" />
                        <table border="0" cellspacing="0" cellpadding="4">
                            <tr>
                                <td>Search:</td>
                                <td><input type="text" name="q" id="search_field_q" class="input_field" value="<?=$q?>" /></td>
                                <td><button type="submit"  >Search</button></td>
                            </tr>
                        </table> 
                      </form>

                      <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:15px; margin-top: 15px;">
                        <tr>
                            <td width="36%" height="30">
                                <h3 style="margin:0"><?=$action_title;?> <? if ($q) { ?><span style="font-size:12px"> (<a href="index.php?section=<?=$SECTION;?><?=$pid_vars;?>">x</a>)</span><? } ?></h3> 
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
                        <th><?=create_sort_link("ip","Unicast NOTIFY IP");?></th>
                        <?/*<th><?=create_sort_link("active", "Active");?></th>*/?>
                        <th>Actions</th>
                      </tr>
                      <!-- RESULTS START -->
                      <?
                      while($LISTING = mysql_fetch_array($SELECT_RESULTS)){
                      ?>      
                      <tr onmouseover="this.className='on' " onmouseout="this.className='off' " id="tr-<?=$LISTING['id'];?>">
                        <td nowrap align="center"><?=$LISTING['name'];?></td>
                        <td nowrap align="center"><?=$LISTING['ip'];?></td>
                        <?/*<td align="center" >
                            <a href="javascript:void(0)" style="margin:0 auto" class="<?if (staff_help()){?>tip_south<?}?> toggle_active <? if ($LISTING['active'] == '1') { ?>activated<? }else{ ?>deactivated<? } ?>" rel="<?=$LISTING['id']?>" title="Enable/Disable"><span>Enable/Disable</span></a>
                        </td>*/?>
                        <td align="center" nowrap="nowrap">
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
                    
                    <!-- LIST UNICST IPs END -->
                    
                    </div>
                        
                </div>    
                
                <!-- UNICAST IPs SECTION END --> 
                
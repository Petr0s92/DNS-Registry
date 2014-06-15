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

admin_auth();


//Define current page data
$mysql_table = 'users';

//SELECT RECORD FOR EDIT
$SELECT = mysql_query("SELECT * FROM `".$mysql_table."` WHERE `id`='".addslashes($_SESSION['admin_id'])."'",$db);
if (!mysql_num_rows($SELECT)){Header ("Location: index.php"); exit(); }
$RESULT = mysql_fetch_array($SELECT);


//EDIT RECORD
if ($_POST['action'] == "edit" && $_SESSION['admin_id']) {
    
    $id = $_SESSION['admin_id'] = (int)$_SESSION['admin_id'];
    $change_pass = 0;    
    
    $errors = array();
    
    $_POST['username'] = trim($_POST['username']);
    if (!preg_match("/^[a-zA-Z0-9][a-zA-Z0-9_-]{2,29}$/", $_POST['username'])) {
        $errors['username'] = "Please choose a username with 3 to 30 latin characters &amp; numbers without spaces and symbols. Hyphens '-' and underscores '_' are allowed but the username cannot begin with either of those 2 characters.";
    } else {
        
        if (mysql_num_rows(mysql_query("SELECT id FROM `".$mysql_table."` WHERE `Username` = '".addslashes($_POST['username'])."'  AND id != '".addslashes($id)."' ",$db))){
            $errors['username'] = "Username is already in use." ;
        } 
    }
    
    if ($_POST['password'] && $_POST['password2']) {
        if ($_POST['password'] != $_POST['password2']) {
            $errors['password'] = "The passwords do not match. Leave blank if you do not wish to change the password";        
        } else {
            $change_pass = 1;
        }
    }
    
    $_POST['nodeid'] = (int)$_POST['nodeid'];    
    if ($_POST['nodeid'] <1){ 
    	$errors['admin_level'] = "Please enter your NodeID #" ; 
    }
        
    $_POST['email'] = trim($_POST['email']);
    if ($_POST['email']){
        if (!preg_match("/^([a-zA-Z0-9]+([\.+_-][a-zA-Z0-9]+)*)@(([a-zA-Z0-9]+((\.|[-]{1,2})[a-zA-Z0-9]+)*)\.[a-zA-Z]{2,7})$/", $_POST['email'])) {
            $errors['email'] = "The Email address you gave is not valid" ;
        }
    }elseif(!$_POST['email']){
        $errors['email'] = "Please enter the Email address";
    }
        
    if (count($errors) == 0) {
        
        $UPDATE = mysql_query("UPDATE `".$mysql_table."` SET
            username  = '" . mysql_escape_string($_POST['username'])  . "',
            email     = '" . mysql_escape_string($_POST['email'])     . "',
            fullname = '" . mysql_escape_string($_POST['fullname']) . "',
            description  = '" . mysql_escape_string($_POST['description'])  . "',
            nodeid  = '" . mysql_escape_string($_POST['nodeid'])  . "'
            
            WHERE id= '" . addslashes($id) . "'",$db);
        
        if ($change_pass) {
            $UPDATE_PASS = mysql_query("UPDATE `".$mysql_table."` SET password = '" . md5($_POST['password']) . "' WHERE id= '" . addslashes($id) . "'",$db);
        }
        
        if ($UPDATE){
            session_unset();
            setcookie ($CONF['COOKIE_NAME'], "",time()-60*60*24*15, "/");
            header("Location: login.php?saved_success=1");
            exit();
        }else{
            $error_occured = TRUE;
        }
        
    }
    
}


?>
<? if (!1) { ?>
<link href="includes/style.css" rel="stylesheet" type="text/css" />
<? } ?>

                <script>
                $(function() {

                    
                    <?if (staff_help()){?>
                    //TIPSY for the EDIT Form
                    $('#username').tipsy({trigger: 'focus', gravity: 'w', fade: true});
                    $('#nodeid').tipsy({trigger: 'focus', gravity: 'w', fade: true});
                    $('#fullname').tipsy({trigger: 'focus', gravity: 'w', fade: true});
                    $('#description').tipsy({trigger: 'focus', gravity: 'w', fade: true});
                    $('#email').tipsy({trigger: 'focus', gravity: 'w', fade: true});
                    $('#password').tipsy({trigger: 'focus', gravity: 'w', fade: true});
                    $('#password2').tipsy({trigger: 'focus', gravity: 'w', fade: true});
                    <?}?>
                    
    
    
                //CLOSE THE NOTIFICATION BAR
                $("a.close_notification").click(function() {
                    var bar_class = $(this).attr('rel');
                    //alert(bar_class);
                    $('.'+bar_class).hide();
                    return false;
                });

                
                });
                

                </script>
                
                <!-- ACCOUNT EDIT SECTION START -->

                <div id="main_content">
            
                    <? if ($error_occured) { ?>
                        <p class="error"><span style="float: right;"><a href="javascript:void(0)" style="margin:0 auto" class="<?if (staff_help()){?>tip_east<?}?> close_notification" rel="error" title="Close notification bar"><span>Close Notification Bar</span></a></span>An error occured.</p>
                    <? } ?>
                    
                    <p class="notification_success"><span style="float: right;"><a href="javascript:void(0)" style="margin:0 auto" class="<?if (staff_help()){?>tip_east<?}?> close_notification" rel="notification_success" title="Close notification bar"><span>Close Notification Bar</span></a></span><span id="notification_success_response"></span></p>
                    <p class="notification_fail"><span style="float: right;"><a href="javascript:void(0)" style="margin:0 auto" class="<?if (staff_help()){?>tip_east<?}?> close_notification" rel="notification_fail" title="Close notification bar"><span>Close Notification Bar</span></a></span><span id="notification_fail_response"></span></p>
                        
                    <div id="toggler">
                    
                        <!-- EDIT ACCOUNT START -->
                        <? if (count($errors) > 0) { ?>
                            <div id="errors">
                                <p>Please check:</p>
                                <ul>
                                    <? foreach ($errors as $key => $value) { echo "<li>" . $value . "</li>"; }?> 
                                </ul>
                            </div>
                        <? } ?>                        
                        
                        <form id="form" method="post" action="index.php?section=<?=$SECTION;?>&action=edit">
                        
                            
                            <fieldset>
                                
                                <legend>&raquo; Edit account</legend>
                        
                                     <div class="columns">
                                        <div class="colx2-left">
                                        
                                            <p>
                                                <label for="username" class="required">Username</label>
                                                <input type="text" name="username" id="username" title="Enter the Username" value="<? if($_POST['username']){ echo $_POST['username']; }else{ echo stripslashes($RESULT['username']);} ?>">
                                            </p>
                                            
                                            <p>
                                                <label for="nodeid" class="required">NodeID #</label>
                                                <input type="text" name="nodeid" id="nodeid" title="Enter your NodeID #" value="<? if($_POST['nodeid']){ echo $_POST['nodeid']; }else{ echo stripslashes($RESULT['nodeid']);} ?>">
                                            </p>
                                            
                                            <p>
                                                <label for="fullname">Fullname</label>
                                                <input type="text" name="fullname" id="fullname" title="Enter the Fullname" value="<? if($_POST['fullname']){ echo $_POST['fullname']; }else{ echo stripslashes($RESULT['fullname']);} ?>">
                                            </p>
                                            
                                            <p>
                                                <label for="description">Description</label>
                                                <input type="text" name="description" id="description" title="Enter an account description" value="<? if($_POST['description']){ echo $_POST['description']; }else{ echo stripslashes($RESULT['description']);} ?>">
                                            </p>
                                            
											                                           
                                        </div>
                                        <div class="colx2-right">
                                            
                                            <p>
                                                <label for="password" class="required">Password</label>
                                                <input type="text" name="password" id="password" title="Enter the Password" >
                                                <br />Enter password twice if you wish to change it.<br />Leave empty to keep the current password.</strong>
                                            </p>
                                            
                                            <p>
                                                <label for="password2">Password (repeat)</label>
                                                <input type="text" name="password2" id="password2" title="Re-enter the Password for validation">
                                            </p>
                                            
                                            <p>
                                                <label for="email" class="required">E-Mail</label>
                                                <input type="text" name="email" id="email" title="Enter the Email" value="<? if($_POST['email']){ echo $_POST['email']; }else{ echo stripslashes($RESULT['email']);} ?>">
                                            </p>
                                            

                                            
                                        </div>
                                        
                                     </div>
                        
                           </fieldset>

                           <fieldset>
                                <legend>&raquo; Action</legend>
                                <button type="submit"  >Save</button>&nbsp; &nbsp;
                                <button type="reset"  id="button">Cancel</button>
                                <input  type="hidden" name="action" id="action" value="edit" />
                                &nbsp;&nbsp;&nbsp;After changing your account details you will be logged out from the system automatically for the changes to take effect.
                           </fieldset>
                        </form>                    
                        
                        <!-- EDIT ACCOUNT END -->
                        
                        <br />
                        
                    </div>
                    
                        
                </div>    
                
                <!-- ACCOUNT EDIT SECTION END --> 
                
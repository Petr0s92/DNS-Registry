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


if (admin_logged()) {
    header("Location: index.php");
    exit();
} 

if (isset ($_GET['action']) && $_GET['action'] == "register") {
	
    $errors = array();
    
    $_POST['username'] = trim($_POST['username']);
    if (!preg_match("/^[a-zA-Z0-9][a-zA-Z0-9_-]{2,29}$/", $_POST['username'])) {
        $errors['username'] = "Please choose a username with 3 to 30 latin characters &amp; numbers without spaces and symbols. Hyphens '-' and underscores '_' are allowed but the username cannot begin with either of those 2 characters.";
    } else {
        
        if (mysql_num_rows(mysql_query("SELECT id FROM `users` WHERE `username` = '".addslashes($_POST['username'])."' ",$db))){
            $errors['username'] = "Username is already in use." ;
        } 
    }
    
    if (!$_POST['password']) {
        $errors['password'] = "Please enter the Password" ;
    } else {
        if ($_POST['password'] != $_POST['password2']) {
            $errors['password'] = "Passwords do not match";
        }    
    }
    
    $_POST['email'] = trim($_POST['email']);
    if ($_POST['email']){
        if (!preg_match("/^([a-zA-Z0-9]+([\.+_-][a-zA-Z0-9]+)*)@(([a-zA-Z0-9]+((\.|[-]{1,2})[a-zA-Z0-9]+)*)\.[a-zA-Z]{2,7})$/", $_POST['email'])) {
            $errors['email'] = "The Email address you gave is not valid" ;
        }
    }elseif(!$_POST['Email']){
        $errors['email'] = "Please enter the Email address";
    } else {
        
        if (mysql_num_rows(mysql_query("SELECT id FROM `users` WHERE `email` = '".addslashes($_POST['email'])."' ",$db))){
            $errors['email'] = "Email is already in use." ;
        } 
    }
    
    if (count($errors) == 0) {
        
        $INSERT = mysql_query("INSERT INTO `users` (username, password, email, Admin_level, Help, active) VALUES (      
            '" . addslashes($_POST['username']) . "',
            '" . md5($_POST['password']) . "',
            '" . addslashes($_POST['email']) . "',
            'user',
            '1',
            '1'
        )", $db);

        if ($INSERT){
        	admin_login($_POST['username'], $_POST['password'], '1');
        	header ("Location: index.php");
        	exit();
        }else{
            $error_occured = TRUE;
        }
        
    }	
    
}

$maintitle_title = "Register";

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

<script type="text/javascript">
$(function() {
    $('#username').focus();
});
</script>

</head>

<body id="login" style="height: auto;">

    <!-- NO JAVASCRIPT NOTIFICATION START -->
    <noscript>
        <div class="maintitle_nojs">This site needs Javascript enabled to function properly!</div>
    </noscript>
    <!-- NO JAVASCRIPT NOTIFICATION END -->

    <h1 id="login_title"><a href="index.php"><img src="images/logo.png" alt="<?=$CONF['APP_NAME'];?>" /></a></h1>
	
    <form id="login_form" name="login_form" method="POST" action="<?=$CONF['APP_URL'];?>/register.php?action=register">
        <h1>Register new account</h1>
		
		<? if (isset ($error_occured)) { ?><p id="login_message">An error occured!</p><? } ?>

        <? if (!empty($errors)) { ?>
            <div id="errors">
                <p>Please check:</p>
                <ul>
                    <? foreach ($errors as $key => $value) { echo "<li>" . $value . "</li>"; }?> 
                </ul>
            </div>
        <? } ?> 		
		
        <label for="username">Username:</label>
        <input name="username" id="username" type="text" size="20" maxlength="20" class="input_field" value="<?=$_POST['username']?>" />
        <label for="password">Password: </label>
        <input name="password" id="password" type="password" size="20" maxlength="20" class="input_field" />
        <label for="password2">Password (repeat): </label>
        <input name="password2" id="password2" type="password" size="20" maxlength="20" class="input_field" />
        <label for="email">Email:</label>
        <input name="email" id="email" type="text" size="20" maxlength="255" class="input_field" value="<?=$_POST['email']?>" />
        
        <div class="clr">&nbsp;</div>

        <input type="submit" name="go" id="go" value="Register" class="button_primary" />
        
        <a href="login.php" id="login_message" style="padding-left:10px">Click here to login</a>
        <div class="clr">&nbsp;</div>

    </form>
    <div id="login_credits">
        <a href="http://www.code.ath/public" target="_blank">Domain Registry Control Panel</a>
    </div>
    
    <div id="forgot_dialog" title="Did you forget your password?" style="display:none">
        <p>If you lost your password contact the Administrator on this email: <br /><br /><a href="mailto:<?=$CONF['MAIL_SUPPORT']?>"><?=$CONF['MAIL_SUPPORT']?></a>.</p>
    </div>
    
</body>
</html>
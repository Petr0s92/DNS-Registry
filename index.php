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

// Protect page from anonymous users
admin_auth();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?=$maintitle_title;?> - <?=$CONF['APP_NAME'];?> - <?=$_SERVER['HTTP_HOST'];?></title>
<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />
<!-- INCLUDE STYLES & JAVASCRIPTS -->
<link href="./includes/css.php" rel="stylesheet" type="text/css"  media="screen" />
<script type="text/javascript" src="./includes/js.php"></script> 
<!-- INCLUDE STYLES & JAVASCRIPTS END -->
</head>

<body>

	<!-- NO JAVASCRIPT NOTIFICATION START -->
	<noscript>
		<div class="maintitle_nojs">This site needs Javascript enabled to function properly!</div>
	</noscript>
	<!-- NO JAVASCRIPT NOTIFICATION END -->

	<div id="wrapper">

		<!-- HEADER START -->
		<div id="header">
			<div id="logo">
				<a href="index.php"><span><?=$CONF['APP_NAME'];?></span></a>
			</div>
		</div>

		<!-- MENU START -->
		<div id="navigation">

			<!-- MAIN MENU START -->
			<ul>
			<li class="menu_home"><a href="index.php" <? if ($SECTION=='' || !$SECTION) echo " class=\"selected\""; ?>><span>Dashboard</span></a></li>
			<li class="menu_domains"><a href="index.php?section=domains" title="Manage your Domain Names" <? if ($SECTION=='domains' && staff_help() ){?>class="tip_south selected"<?}elseif($SECTION=='domains' && !staff_help() ){?>class="selected"<?}elseif($SECTION!='domains' && staff_help()){?>class="tip_south"<?}?> ><span>My Domain Names</span></a></li>
			<li class="menu_nameservers"><a href="index.php?section=nameservers" title="Manage your Nameservers & Glue records" <? if ($SECTION=='nameservers' && staff_help() ){?>class="tip_south selected"<?}elseif($SECTION=='nameservers' && !staff_help() ){?>class="selected"<?}elseif($SECTION!='nameservers' && staff_help()){?>class="tip_south"<?}?> ><span>My Nameservers</span></a></li>
			<? 
			if ($_SESSION['admin_level'] == 'admin'){
			?><li class="menu_tlds"><a href="index.php?section=tlds" title="Managed allowed TLDs" <? if ($SECTION=='tlds' && staff_help() ){?>class="tip_south selected"<?}elseif($SECTION=='tlds' && !staff_help() ){?>class="selected"<?}elseif($SECTION!='tlds' && staff_help()){?>class="tip_south"<?}?> ><span>TLDs</span></a></li>
			<li class="menu_users"><a href="index.php?section=users" title="Manage Users" <? if ($SECTION=='users' && staff_help() ){?>class="tip_south selected"<?}elseif($SECTION=='users' && !staff_help() ){?>class="selected"<?}elseif($SECTION!='users' && staff_help()){?>class="tip_south"<?}?> ><span>Users</span></a></li>
			<li class="menu_settings"><a href="index.php?section=settings" title="Manage system settings" <? if ($SECTION=='settings' && staff_help() ){?>class="tip_south selected"<?}elseif($SECTION=='settings' && !staff_help() ){?>class="selected"<?}elseif($SECTION!='settings' && staff_help()){?>class="tip_south"<?}?> ><span>Settings</span></a></li>
			<?
			}
			?><li class="menu_portal"><a href="http://www.ath/" title="Go back to www.ath portal for more information" <?if (staff_help()){?>class='tip_south'<?}?> target="_blank" ><span>Portal</span></a></li>
			</ul>
			<!-- MAIN MENU END -->

			<!-- USER LOGOUT START -->
			<div id="user_panel">
			User: <a href="index.php?section=staff&action=edit&id=<?=$_SESSION['admin_id'];?>" <?if (staff_help()){?>class="tip_south"<?}?> title="Edit account"><strong><?=$_SESSION['admin_username'];?></strong></a>
			<a href="login.php?action=logout" class="logout <?if (staff_help()){?>tip_east<?}?>" title="Logout of the system">Logout</a>
			</div>
			<!-- USER LOGOUT END -->           

		</div>
		<!-- MENU END -->

		<div class="clr">&nbsp;</div>
		<!-- HEADER END -->

		<!-- MAIN START -->
		<br />
		<table width="100%" border="0" cellspacing="0" cellpadding="0">
			<tr>
			<!-- SIDEBAR START -->
				<td valign="top" id="sidebar">

					<h2 class="sidebar_title">Registry Stats</h2>
					<table width="100%" border="0" cellspacing="2" cellpadding="2">
					<tr>
					<td align="right" nowrap="nowrap" height="25" class="smalltahoma">Total Domains</td>
					<td class="smalltahoma"><strong><?=mysql_num_rows(mysql_query("SELECT 1 FROM records WHERE type = 'NS' AND user_id > 0 GROUP BY name", $db));?></strong></td>
					</tr>
					<tr>
					<td align="right" nowrap="nowrap" height="25" class="smalltahoma">Total Nameservers</td>
					<td class="smalltahoma"><strong><?=mysql_num_rows(mysql_query("SELECT 1 FROM records WHERE type = 'A' AND user_id > '0' ", $db));?></strong></td>
					</tr>
					<tr>
					<td align="right" nowrap="nowrap" height="25" class="smalltahoma">Total Users</td>
					<td class="smalltahoma" nowrap="nowrap" ><strong><?=mysql_num_rows(mysql_query("SELECT 1 FROM users WHERE 1", $db));?></strong></td>
					</tr>                  
					</table>
					<br />				

					<h2 class="sidebar_title">Account Stats</h2>
					<table width="100%" border="0" cellspacing="2" cellpadding="2" >
					<tr>
					<td align="right" nowrap="nowrap" height="25" class="smalltahoma">My Domains Total</td>
					<td class="smalltahoma"><strong><?=mysql_num_rows(mysql_query("SELECT 1 FROM records WHERE type = 'NS' AND user_id = '".$_SESSION['admin_id']."' GROUP BY name", $db));?></strong></td>
					</tr>
					<tr>
					<td align="right" nowrap="nowrap" height="25" class="smalltahoma">My Nameservers Total</td>
					<td class="smalltahoma"><strong><?=mysql_num_rows(mysql_query("SELECT 1 FROM records WHERE type = 'A' AND user_id = '".$_SESSION['admin_id']."' ", $db));?></strong></td>
					</tr>
					</table>

				</td>
				<!-- SIDEBAR END -->
				<td class="main_content_spacer"></td>
			
				<td valign="top" id="main">

				<div class="maintitle_bg">
					<div class="<?=$maintitle_class;?>"><a href="index.php?section=<?=$SECTION;?><?if ($_GET['domain']) { echo "&domain=".$_GET['domain'];}?><?if ($_GET['id']) { echo "&id=".$_GET['id'];}?>"><?=$maintitle_title;?></a></div>
				</div>    

					<?
					// LOAD THE APPROPRIATE SECTION 
					if (!$SECTION){
						include "dashboard.php";
					}

					if ($SECTION && preg_match('!^[\w @.-]*$!', $SECTION)) {
						if (file_exists($SECTION.'.php')) {
							include $SECTION.'.php';    
						}
					}
					?>        
				</td>
			</tr>
		</table>
		<!-- MAIN END -->

	</div>

	<!-- FOOTER START -->
	<div id="footer">
		<span style="float:right">Domain Registry Control Panel &copy; <?=date("Y")?>.</span>
		<?=$CONF['APP_NAME'];?>
	
	</div>
	<!-- FOOTER END -->

</body>
</html>
<? 
$buffer = ob_get_clean(); 
ob_start("ob_gzhandler"); 
echo $buffer;
?>
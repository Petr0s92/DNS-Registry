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
<title><?=$CONF['APP_NAME'];?> | <?=$maintitle_title;?> | <?=$_SERVER['HTTP_HOST'];?></title>
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

	<!-- LOW RESOLUTION NOTIFICATION START -->
		<div class="maintitle_lowres">For your best user experience please use a screen resolution greater than 1280 x 720!</div>
	<!-- LOW RESOLUTION NOTIFICATION END -->

	<div id="wrapper">

		<!-- HEADER START -->
		<div id="header">
			
			<div id="logo<?if (is_file("./images/logo.custom.png")){?>_custom<?}?>">
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
			<li class="menu_transfers"><a href="index.php?section=transfers" title="Manage your Domain Transfer Requests" <? if ($SECTION=='transfers' && staff_help() ){?>class="tip_south selected"<?}elseif($SECTION=='transfers' && !staff_help() ){?>class="selected"<?}elseif($SECTION!='transfers' && staff_help()){?>class="tip_south"<?}?> ><span>Domain Transfers</span></a></li>
			<li class="menu_whois"><a href="index.php?section=whois" title="WHOIS Lookup" <? if ($SECTION=='whois' && staff_help() ){?>class="tip_south selected"<?}elseif($SECTION=='whois' && !staff_help() ){?>class="selected"<?}elseif($SECTION!='whois' && staff_help()){?>class="tip_south"<?}?> ><span>Web Whois</span></a></li>
            <? 
			if ($_SESSION['admin_level'] == 'admin'){
			?><li class="menu_properties"><a id="anchor_properties" href="javascript: void(0)" title="Administrator Options" <? if ( ($SECTION=='tlds' || $SECTION=='root_ns' || $SECTION=='root_ns_unicast' || $SECTION=='slave_zones' || $SECTION=='communities' || $SECTION=='users' || $SECTION=='settings' ) && staff_help() ){?>class="tip_south selected"<?}elseif( ($SECTION=='tlds' || $SECTION=='root_ns' || $SECTION=='root_ns_unicast' || $SECTION=='slave_zones' || $SECTION=='communities' || $SECTION=='users' || $SECTION=='settings' )  && !staff_help() ){?>class="selected"<?}elseif(staff_help()){?>class="tip_south"<?}?> ><span>Administrative Settings <img src="images/arrow_down.gif" align="absmiddle" border="0" /></span></a></li>
			<?
			}
			if ($CONF['PORTAL_URL']){
			?><li class="menu_portal"><a href="<?=$CONF['PORTAL_URL'];?>" title="Go back to <?=$CONF['PORTAL_URL'];?> portal for more information" <?if (staff_help()){?>class='tip_south'<?}?> target="_blank" ><span>Portal</span></a></li>
			<?}?>
			</ul>
			<!-- MAIN MENU END -->

            <?if ($_SESSION['admin_level'] == 'admin'){?>
			<!-- SUB MENUS START -->
            <script type="text/javascript">
            jkmegamenu.definemenu("anchor_properties", "menu_properties", "mouseover"); 
            </script>    
            <div id="menu_properties" class="megamenu">
                <ul>
            		<li class="menu_tlds"><a href="index.php?section=tlds" title="Managed allowed TLDs" <? if (staff_help() ){?>class="tip_east"<?}?> ><span>TLDs</span></a></li>
					<li class="menu_root_ns"><a href="index.php?section=root_ns" title="Manage Root Nameservers" <? if (staff_help() ){?>class="tip_east"<?}?> ><span>Root Nameservers</span></a></li>
					<li class="menu_slave_zones"><a href="index.php?section=slave_zones" title="Manage Slave DNS Zones" <? if (staff_help() ){?>class="tip_east"<?}?> ><span>Slave Zones</span></a></li>
					<li class="menu_communities"><a href="index.php?section=communities" title="Manage Wireless Communities" <? if (staff_help() ){?>class="tip_east"<?}?> ><span>Communities</span></a></li>
					<li class="menu_users"><a href="index.php?section=users" title="Manage Users" <? if (staff_help() ){?>class="tip_east"<?}?> ><span>Users Management</span></a></li>
			        <li class="menu_settings"><a href="index.php?section=settings" title="Manage system settings" <? if (staff_help() ){?>class="tip_east"<?}?> ><span>System Settings</span></a></li>
                    <li class="menu_soa_update"><a href="index.php?force_soa_update=1&return=<?=urlencode($_SERVER['REQUEST_URI']);?>" title="This will force a SOA Serial Update to all Domains on the system to force slaves to sync their zones. Use only when nessescary." <? if (staff_help() ){?>class="tip_east"<?}?> ><span>Force SOA Serial Update</span></a></li>
                    <li class="menu_cache_flush"><a href="index.php?cache_flush=1&return=<?=urlencode($_SERVER['REQUEST_URI']);?>" title="This will flush the DNS cache on all BIND servers. Use only when nessescary." <? if (staff_help() ){?>class="tip_east"<?}?> ><span>Flush DNS Cache</span></a></li>
                </ul>
            </div>
            <!-- SUB MENUS END -->
            <?}?>
            			
			<!-- USER MENU START -->
			<div id="user_panel">
			<?if ($_SESSION['admin_level'] == 'admin' || $_SESSION['admin_orig']){?>
			<select name="switch_user" id="switch_user" title="Switch to user" class="tip_south" >
                <option value="" selected="selected">--Select--</option>
				<? 
				$SELECT_USERS = mysql_query("SELECT username, id FROM users WHERE active ='1' ORDER BY username ASC", $db);
				while ($USERS = mysql_fetch_array($SELECT_USERS)){
				?>                                                    
                <option value="<?=$USERS['id'];?>"   <? /*if ($_SESSION['admin_id'] == $USERS['id']){ echo "selected=\"selected\""; }*/?> ><?=$USERS['username'];?></option>
				<?}?>                                                    
            </select>
            &nbsp;
            Current User: <a href="index.php?section=user&action=edit&id=<?=$_SESSION['admin_id'];?>" <?if (staff_help()){?>class="tip_south"<?}?> title="Edit account"><strong><?=$_SESSION['admin_username'];?></strong></a>
			<?}else{?>
			Welcome, <a href="index.php?section=user&action=edit" <?if (staff_help()){?>class="tip_south"<?}?> title="Edit account"><strong><?=$_SESSION['admin_username'];?></strong></a>
			<?}?>
			<a href="login.php?action=logout" class="logout <?if (staff_help()){?>tip_east<?}?>" title="Logout of the system">Logout</a>
			</div>
			<!-- USER MENU END -->           

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

					<?
					$o=0;
					$tlds=false;
					$SELECT_TLDS = mysql_query("SELECT domains.id FROM tlds LEFT JOIN domains on tlds.name=domains.name ", $db);
					$TLDS_TOTAL = mysql_num_rows($SELECT_TLDS);
					while ($TLDS = mysql_fetch_array($SELECT_TLDS)){
						$o++;
						$tlds .= "'".$TLDS['id']."'";
						if ($o < $TLDS_TOTAL){
							$tlds .=", ";
						}
					}
					if ($tlds){
						$tldsq = " AND domain_id IN (".$tlds.") ";
					}
					?>
					
					<h2 class="sidebar_title">Registry Stats</h2>
					<table width="100%" border="0" cellspacing="2" cellpadding="2">
					<tr>
					<td align="right" nowrap="nowrap" height="25" class="smalltahoma">Total TLDs</td>
					<td class="smalltahoma"><strong><?=mysql_num_rows(mysql_query("SELECT 1 FROM tlds WHERE active ='1' ", $db));?></strong></td>
					</tr>
					<?if ($_SESSION['admin_level'] == 'admin'){?>
					<tr>
					<td align="right" nowrap="nowrap" height="25" class="smalltahoma">Total Slave Zones</td>
					<td class="smalltahoma"><strong><?=mysql_num_rows(mysql_query("SELECT 1 FROM slave_zones WHERE active ='1' ", $db));?></strong></td>
					</tr>
					<tr>
					<td align="right" nowrap="nowrap" height="25" class="smalltahoma">Total Root Nameservers</td>
					<td class="smalltahoma"><strong><?=mysql_num_rows(mysql_query("SELECT 1 FROM root_ns WHERE active = '1' ", $db));?></strong></td>
					</tr>
					<tr>
					<td align="right" nowrap="nowrap" height="25" class="smalltahoma">Total Unicast Nameservers</td>
					<td class="smalltahoma"><strong><?=mysql_num_rows(mysql_query("SELECT 1 FROM root_ns_unicast WHERE active = '1' ", $db));?></strong></td>
					</tr>
					<?}?>
					<tr>
					<td align="right" nowrap="nowrap" height="25" class="smalltahoma">Total Domains</td>
					<td class="smalltahoma"><strong><?=mysql_num_rows(mysql_query("SELECT 1 FROM records WHERE type = 'NS' AND user_id > 0 GROUP BY name", $db));?></strong></td>
					</tr>
					<tr>
					<td align="right" nowrap="nowrap" height="25" class="smalltahoma">Total Hosted Domains</td>
					<td class="smalltahoma"><strong><?=mysql_num_rows(mysql_query("SELECT 1 FROM records WHERE type = 'SOA' AND user_id > 0 ", $db));?></strong></td>
					</tr>
					<tr>
					<td align="right" nowrap="nowrap" height="25" class="smalltahoma">Total Nameservers</td>
					<td class="smalltahoma"><strong><?=mysql_num_rows(mysql_query("SELECT 1 FROM records WHERE type = 'A' AND user_id > '0' " . $tldsq, $db));?></strong></td>
					</tr>
					<tr>
					<td align="right" nowrap="nowrap" height="25" class="smalltahoma">Wireless Communities</td>
					<td class="smalltahoma" nowrap="nowrap" ><strong><?=mysql_num_rows(mysql_query("SELECT 1 FROM communities WHERE 1", $db));?></strong></td>
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
					<td align="right" nowrap="nowrap" height="25" class="smalltahoma">My Hosted Domains Total</td>
					<td class="smalltahoma"><strong><?=mysql_num_rows(mysql_query("SELECT 1 FROM records WHERE type = 'SOA' AND user_id = ".$_SESSION['admin_id']." ", $db));?></strong></td>
					</tr>
					<tr>
					<td align="right" nowrap="nowrap" height="25" class="smalltahoma">My Nameservers Total</td>
					<td class="smalltahoma"><strong><?=mysql_num_rows(mysql_query("SELECT 1 FROM records WHERE type = 'A' AND user_id = '".$_SESSION['admin_id']."' " . $tldsq, $db));?></strong></td>
					</tr>
					</table>
					<br />
					
					<?if ($CONF['TERMS_URL'] || $CONF['SUPPORT_URL'] || $CONF['WHOIS_SERVER']){?>
						<h2 class="sidebar_title"></h2>
					<?}?>
					<?if ($CONF['WHOIS_SERVER']){?>
						WHOIS Server: <strong><?=$CONF['WHOIS_SERVER'];?></strong>
						<br />
						<br />
					<?}?>
					<?if ($CONF['TERMS_URL']){?>
						<a href="<?=$CONF['TERMS_URL'];?>" target="_blank">Terms and Conditions</a>
						<br />
						<br />
					<?}?>
					<?if ($CONF['SUPPORT_URL']){?>
						<a href="<?=$CONF['SUPPORT_URL'];?>" target="_blank">Get Support</a>
					<?}?>

				</td>
				<!-- SIDEBAR END -->
				<td class="main_content_spacer"></td>
			
				<td valign="top" id="main">

				<?if ($_GET['soa_updated'] == '1' && $_SESSION['admin_level'] == 'admin'){?>
				<script>
                $(function() {    
	                //CLOSE THE SOA NOTIFICATION BAR
	                $("a.close_notification").click(function() {
	                    var bar_class = $(this).attr('rel');
	                    //alert(bar_class);
	                    $('.'+bar_class).hide();
	                    return false;
	                });
				});
                </script>
                				
				<div class="maintitle_bg">
					<p class="success"><span style="float: right;"><a href="javascript:void(0)" style="margin:0 auto" class="<?if (staff_help()){?>tip_east<?}?> close_notification" rel="success" title="Close notification bar"><span>Close Notification Bar</span></a></span>
                        SOA Update was successful.</p>
                </div>
				<?}?>    

				<?if ($_GET['cache_flushed'] == '1' && $_SESSION['admin_level'] == 'admin'){?>
				<script>
                $(function() {    
	                //CLOSE THE SOA NOTIFICATION BAR
	                $("a.close_notification").click(function() {
	                    var bar_class = $(this).attr('rel');
	                    //alert(bar_class);
	                    $('.'+bar_class).hide();
	                    return false;
	                });
				});
                </script>
                				
				<div class="maintitle_bg">
					<p class="success"><span style="float: right;"><a href="javascript:void(0)" style="margin:0 auto" class="<?if (staff_help()){?>tip_east<?}?> close_notification" rel="success" title="Close notification bar"><span>Close Notification Bar</span></a></span>
                        Cache Flush was successful.</p>
                </div>
				<?}?>    

				<div class="maintitle_bg">
					<div class="<?=$maintitle_class;?>"><a href="index.php?section=<?=$SECTION;?><?if ($_GET['domain']) { echo "&domain=".$_GET['domain'];}?><?if ($_GET['id']) { echo "&id=".(int)$_GET['id'];}?><?if ($_GET['domain_id']) { echo "&domain_id=".(int)$_GET['domain_id'];}?><?if ($_GET['parent_id']) { echo "&parent_id=".$_GET['parent_id'];}?>"><?=$maintitle_title;?></a></div>
				</div>    

					<?
					// LOAD THE APPROPRIATE SECTION 
					if (!$SECTION){
						if (file_exists('dashboard.php')) {
							include "dashboard.php";
						}else{
							include "dashboard.php.dist";
						}
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
		<span style="float:right"><?=$CONF['CREDITS'];?></span>
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
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

?>

<div id="main_content">
	<center>
		<div style="width:500px; text-align:left">

			<center><h2><?=$CONF['APP_NAME'];?> Web Whois</h2></center>
			<center><b>Enter the Domain Name you are trying to lookup.</b></center>
			<br />
			<center>
				<form method='get' action='index.php'>
					<input type='hidden' name='section' value='whois'>
					<input type='text' name='domain' class='input_login' value='<?=htmlentities(trim($_GET['domain']));?>'>&nbsp;
					<input type='submit' value='Whois' class='input_login'>
				</form>
			</center>
			<?
			if($_GET['domain']){

				$_input = htmlentities(trim($_GET['domain']));

				require_once "Net/Whois.php";
				$whois = new Net_Whois;
				$data = $whois->query($_input, $CONF['WHOIS_ADDRESS']);
				echo "<br />";
				echo "<pre>";
				if ($data == "Unable to open socket"){
					echo $CONF['WHOIS_ADDRESS'] . "whois server is down.";
				}else{
					echo $data;
				}
				echo "</pre>";

			}
            ?>
		</div>
	</center>
</div>
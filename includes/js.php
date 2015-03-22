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

ob_start("ob_gzhandler");
header('Content-type: application/javascript');

header("Expires: Sat, 26 Jul ".(date("Y")+1)." 05:00:00 GMT"); // Date in the future
    
$js_files = array(
	'./jquery/jquery-1.9.1.min.js',
	'./jquery/jquery-migrate-1.1.1.js',
	'./jquery/jquery-ui-1.8.7.custom.min.js',
	'./jquery/tipsy/javascripts/jquery.tipsy.js',
	'./jquery/colorbox/jquery.colorbox-min.js',
    './jquery/jquery.easing.1.2.js',
    './jquery/jkmegamenu/jkmegamenu.js'
);


foreach($js_files AS $key => $file) {
	include($file);
	echo "\n\n";
}

?>

$(function() {
    $('.tip_north').tipsy({gravity: 'n', fade: true});
    $('.tip_south').tipsy({gravity: 's', fade: true});
    $('.tip_southwest').tipsy({gravity: 'sw', fade: true});
    $('.tip_northeast').tipsy({gravity: 'se', fade: true});
    $('.tip_east').tipsy({gravity: 'e', fade: true});
    $('.tip_west').tipsy({gravity: 'w', fade: true});
     
    $('.tip').tipsy();
    
	$('#switch_user').change(function() {
		//alert( "Handler for .change() called." );
		
		var user_id = $(this).find(":selected").val();
		$.post("index.php?section=<?=$SECTION;?>&action=switch_user", {
            user_id: user_id		    
		}, function(response){
			location.reload();
		});		
		
	});
	
	
	//Show alert on low screen resolution 
	var width = $(window).width();
    
    if (width < 1200){
		$('.maintitle_lowres').show();
	}else{
		$('.maintitle_lowres').hide();
	}
		
	//show warning on window resize
	$(window).resize(function() {
	    var width = $(window).width();
		if (width < 1200){
			$('.maintitle_lowres').show();
		}else{
			$('.maintitle_lowres').hide();
		}
  		
	});	    
    
        
});   

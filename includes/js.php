<?php
/*-----------------------------------------------------------------------------
* Domain Registry Control Panel                                               *
*                                                                             *
* Developed by Vaggelis Koutroumpas - vaggelis@koutroumpas.gr                 *
* www.koutroumpas.gr  (c)2014                                                 * 
*-----------------------------------------------------------------------------*/

require ("config.php");
require ("functions.php");


ob_start("ob_gzhandler");
header('Content-type: application/javascript');

header("Expires: Sat, 26 Jul ".(date("Y")+1)." 05:00:00 GMT"); // Date in the future
    //'./jquery/jquery-1.4.4.min.js',
	
$js_files = array(
	'./jquery/jquery-1.9.1.min.js',
	'./jquery/jquery-migrate-1.1.1.js',
	'./jquery/jquery-ui-1.8.7.custom.min.js',
	'./jquery/tipsy/javascripts/jquery.tipsy.js',
    './jquery/jquery.easing.1.2.js'
);


foreach($js_files AS $key => $file) {
	include($file);
	echo "\n\n";
}

?>

<?if (!isset($_GET['login'])){?>


$(function() {
    $('.tip_north').tipsy({gravity: 'n', fade: true});
    $('.tip_south').tipsy({gravity: 's', fade: true});
    $('.tip_southwest').tipsy({gravity: 'sw', fade: true});
    $('.tip_northeast').tipsy({gravity: 'se', fade: true});
    $('.tip_east').tipsy({gravity: 'e', fade: true});
    $('.tip_west').tipsy({gravity: 'w', fade: true});
     
    $('.tip').tipsy();
        
});   

<?}?>
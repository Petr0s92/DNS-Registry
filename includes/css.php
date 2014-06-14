<?php
/*-----------------------------------------------------------------------------
* Domain Registry Control Panel                                               *
*                                                                             *
* Developed by Vaggelis Koutroumpas - vaggelis@koutroumpas.gr                 *
* www.koutroumpas.gr  (c)2014                                                 * 
*-----------------------------------------------------------------------------*/

ob_start("ob_gzhandler");
header('Content-type: text/css');

header("Expires: Sat, 26 Jul ".(date("Y")+1)." 05:00:00 GMT"); // Date in the future


$js_files = array(
    './jquery/css/custom-theme/jquery-ui-1.8.7.custom.css',
	'./jquery/tipsy/stylesheets/tipsy.css',
    './style.css'
);

foreach($js_files AS $key => $file) {
	include($file);
	echo "\n\n";
}
?>
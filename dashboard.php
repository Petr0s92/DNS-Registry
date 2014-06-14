<?php
/*-----------------------------------------------------------------------------
* Domain Registry Control Panel                                               *
*                                                                             *
* Developed by Vaggelis Koutroumpas - vaggelis@koutroumpas.gr                 *
* www.koutroumpas.gr  (c)2014                                                 * 
*-----------------------------------------------------------------------------*/

admin_auth();

?>
       <?/*
      <div>
<script type="text/javascript">
$(function() {
    $('#dashboard-1').tabs({ fxAutoHeight: true });
    $('#dashboard-2').tabs({ fxAutoHeight: true });
});
</script>

      <br />
      <br />
      <table border="0" cellpadding="0" cellspacing="0" align="center" >
        <tr>
          <td width="65" align="left" valign="top"><img src="images/dashboard_clients.png" alt="cat" width="48" height="48" class="image" /></td>
          <td width="165" align="left" valign="top"><h2 style="margin:0px;">Contacts</h2>
              <p style="margin:0px;">
                <a href="index.php?section=users">Contacts</a><br />
                <?/*<a href="index.php?section=users_traffic">Clients Traffic</a><br />
                <a href="index.php?section=users_bandwidth">Clients Bandwidth</a><br />* /?>
              </p>
          </td>
              
          <td width="65" align="left" valign="top"><img src="images/dashboard_acls.png" alt="cat" width="48" height="48" class="image" /></td>
          <td width="170" align="left" valign="top"><h2 style="margin:0px;">Access Lists</h2>
              <p style="margin:0px;">
                <a href="index.php?section=acls&mode=allow">Allow List</a><br />
                <a href="index.php?section=acls&mode=block">Block List</a><br />
              </p>
          </td>
		    <td width="65" align="left" valign="top"><img src="images/dashboard_channels.png" alt="cat" width="48" height="48" class="image" /></td>
          <td width="170" align="left" valign="top"><h2 style="margin:0px;">Channels</h2>
              <p style="margin:0px;">
                <a href="index.php?section=channels">Manage all Channels</a><br />
              </p>
          </td>
          <td width="200"></td>
          <td width="65" align="left" valign="top"><img src="images/dashboard_settings.png" alt="cat" width="48" height="48" class="image" /></td>
          <td width="170" align="left" valign="top"><h2 style="margin:0px;">Settings</h2>
              <p style="margin:0px;">
                <a href="index.php?section=staff">Staff Management</a><br />
                <a href="index.php?section=settings">AMS Settings</a><br />
              </p>
          </td>
        </tr>
        
      </table>
      <br />
             
      

<?if ($CONF['gmap_enable']){?>                    
<!-- SET SOME GMAP SETTINGS -->
<?
$SELECT_MAX_LAT = mysql_query("SELECT MAX(Lat) AS LAT FROM routers_mikrotik WHERE 1 ", $db);
$MAX_LAT = mysql_fetch_array($SELECT_MAX_LAT);
//print_r ($MAX_LAT);

$SELECT_MAX_LON = mysql_query("SELECT MAX(Lon) AS LON FROM routers_mikrotik WHERE 1 ", $db);
$MAX_LON = mysql_fetch_array($SELECT_MAX_LON);
//print_r ($MAX_LON);
    
$SELECT_MIN_LAT = mysql_query("SELECT MIN(Lat) AS LAT FROM routers_mikrotik WHERE Lon > 0 ", $db);
$MIN_LAT = mysql_fetch_array($SELECT_MIN_LAT);
//print_r ($MIN_LAT);
    
$SELECT_MIN_LON = mysql_query("SELECT MIN(Lon) AS LON FROM routers_mikrotik WHERE Lon > 0 ", $db);
$MIN_LON = mysql_fetch_array($SELECT_MIN_LON);
//print_r ($MIN_LON);

$CENTER_LAT = ( $MAX_LAT['LAT'] + $MIN_LAT['LAT'])/2;
$CENTER_LON = ( $MAX_LON['LON'] + $MIN_LON['LON'])/2;

    
?>
                <script>
                
                


    $(function(){

      $('#test1').gmap3(
        {
            action: 'init', 
            center:{
                lat:<?=$CENTER_LAT;?>, 
                lng:<?=$CENTER_LON;?>
            }, 
            zoom:<?=$CONF['gmap_default_zoom'];?>,
            mapTypeId: google.maps.MapTypeId.HYBRID,
            navigationControl: true,
            scrollwheel: true,
            streetViewControl: true

        }
        <?
        $SELECT_ROUTERS = mysql_query("SELECT Lat, Lon, Router_name, Ip, Status, Router_type FROM `routers_mikrotik` WHERE Lat != '0' AND Lon != '0' AND Status = '1' ", $db);
        if (mysql_num_rows($SELECT_ROUTERS)){?>
        ,
        {  // PRINT UP ROUTERS
            action: 'addMarkers',
            markers:[
             <?
             $routers_qnt = mysql_num_rows($SELECT_ROUTERS);
             $i=0;
             while ($ROUTERS = mysql_fetch_array($SELECT_ROUTERS)){
                $i++;
                
                echo "\t\t{lat:".$ROUTERS['Lat'].", lng:".$ROUTERS['Lon'].", data:'".$ROUTERS['Router_name']."<br />".$ROUTERS['Ip']."<br /><br />Status: <span class=\"green\">Up!</span>' ";
                if ($ROUTERS['Router_type'] == 'ap'){
                echo ", options:{ draggable: false, icon: 'images/gmap/mm_50_orange.png' }";
                }
                echo " }";
                
                if ($i < $routers_qnt){
                    echo ",\n";
                }else{ 
                    echo "\n";
                }
                
             }
             ?>
            ],
            marker:{
              options:{
                draggable: false,
                icon: 'images/gmap/mm_50_green.png'
              },
              events:{
                click: function(marker, event, data){
                  var map = $(this).gmap3('get'),
                      infowindow = $(this).gmap3({action:'get', name:'infowindow'});
                  if (infowindow){
                    infowindow.open(map, marker);
                    infowindow.setContent(data);
                  } else {
                    $(this).gmap3({action:'addinfowindow', anchor:marker, options:{content: data}});
                  }
                },
                
                focusout: function(){
                  var infowindow = $(this).gmap3({action:'get', name:'infowindow'});
                  if (infowindow){
                    infowindow.close();
                  }
                }
              }
            }

        }
        <?}?>
        <?
        $SELECT_ROUTERS = mysql_query("SELECT Lat, Lon, Router_name, Ip FROM `routers_mikrotik` WHERE Lat != '0' AND Lon != '0' AND Status = '0' ", $db);
        if (mysql_num_rows($SELECT_ROUTERS)){?>
        ,{ //PRINT DOWN ROUTERS
            action: 'addMarkers',
            markers:[
             <?
             $routers_qnt = mysql_num_rows($SELECT_ROUTERS);
             $i=0;
             while ($ROUTERS = mysql_fetch_array($SELECT_ROUTERS)){
                $i++;
                
                echo "\t\t{lat:".$ROUTERS['Lat'].", lng:".$ROUTERS['Lon'].", data:'".$ROUTERS['Router_name']."<br />".$ROUTERS['Ip']."<br /><br />Status: <span class=\"red\">Down!</span>'}";
                
                if ($i < $routers_qnt){
                    echo ",\n";
                }else{ 
                    echo "\n";
                }
             }
             ?>
            ],
            marker:{
              options:{
                draggable: false,
                icon: 'images/gmap/mm_50_red.png' 
              },
              events:{
                click: function(marker, event, data){
                  var map = $(this).gmap3('get'),
                      infowindow = $(this).gmap3({action:'get', name:'infowindow'});
                  if (infowindow){
                    infowindow.open(map, marker);
                    infowindow.setContent(data);
                  } else {
                    $(this).gmap3({action:'addinfowindow', anchor:marker, options:{content: data}});
                  }
                },
                
                focusout: function(){
                  var infowindow = $(this).gmap3({action:'get', name:'infowindow'});
                  if (infowindow){
                    infowindow.close();
                  }
                }
              }
            }
        }
<?}?>
<?
        $SELECT_ROUTERS = mysql_query("SELECT Lat, Lon, id FROM `routers_mikrotik` WHERE Lat != '0' AND Lon != '0' AND Parent_router = '0' ", $db);
        $routers_qnt = mysql_num_rows($SELECT_ROUTERS);
        if ($routers_qnt){
            echo ", ";
        }
        $i=0;
        while ($ROUTERS = mysql_fetch_array($SELECT_ROUTERS)){ 
            $i++;
            
            $SELECT_CHILD_ROUTER = mysql_query("SELECT Lat, Lon FROM `routers_mikrotik` WHERE Lat != '0' AND Lon != '0' AND Parent_router = '".$ROUTERS['id']."' ", $db);
            
            while ($CHILD_ROUTERS = mysql_fetch_array($SELECT_CHILD_ROUTER)){
            
                
                echo "        { action: 'addPolyline',\n";
                echo "        options:{\n";
                echo "        \tstrokeColor: \"#00ff00\",\n";
                echo "        \tstrokeOpacity: 0.7,\n";
                echo "        \tstrokeWeight: 3\n";
                echo "        },\n";
                echo "        path:[\n";
                    
                echo "        \t[".$ROUTERS['Lat'].", ".$ROUTERS['Lon']."],\n";
                echo "        \t[".$CHILD_ROUTERS['Lat'].", ".$CHILD_ROUTERS['Lon']."]\n";
            
                
                echo "        ] \n";
                    
                //if ($i < $routers_qnt){ 
                    echo "        },\n";
                //}else{
                //    echo "        }\n";
                //}
                
            }
            

        }
        ?>
        "autofit"
      );
    });
   
   </script>
                
    <style>
      .gmap3{
        margin: 0px auto;
        border: 1px #C0C0C0;
        width: 100%;
        height: 390px;
        margin-bottom:4px;
      }
    </style>                    
                    
<!-- END GMAP SETTINGS -->                                    
<?}?>                    
                   
                    
                    <div class="columns">
                        <div class="colx2-left">
                       <?if ($CONF['gmap_enable']){?>
                      <fieldset>
                                
                          <legend>&raquo; Live Map</legend>
                        
                            <div id="test1" class="gmap3"></div>
                            <div id="map_legend" align="center">
                            
                                <img src="images/gmap/mm_20_green.png"> Sentry Router - Up &nbsp;  &nbsp; <img src="images/gmap/mm_20_orange.png"> AP Router - Up &nbsp;  &nbsp; <img src="images/gmap/mm_20_red.png"> Router Down<br/>
                            </div>                                               
                    
                    </fieldset> 
                    <!-- GMAP END -->
                    <?}?>
                     </div>
                     
                     * /?>
                     
                     <div class="colx2-right">
                     
                     <fieldset>
                                
                          <legend>&raquo; Quick Notifications</legend>
                     
                                        
      <div id="dashboard-2" style="width:100%;">
            <ul>
              <li><a href="#latest-alerts"><span>Notifications</span></a></li>
              <li><a href="#latest-orders"><span>Routers</span></a></li>
              <?/*<li><a href="#latest-customers"><span>Newest Customers</span></a></li>* /?>
            </ul>
            <div id="latest-alerts" class="open_tab">
            <table width="100%" border="0" cellspacing="0"  cellpadding="5" class="smarttable">
                <?/*
                $SELECT_NEW_CONTACTS = mysql_query("SELECT Firstname, Lastname, SSID_id, Plan_id, Email, id FROM users WHERE SSID_id = '0' OR Plan_id = '0' ORDER BY Last_copied DESC LIMIT 0, 10");
                while ($NEW_CONTACTS = mysql_fetch_array($SELECT_NEW_CONTACTS)){
                    if ($NEW_CONTACTS['SSID_id'] == '0'){
                        $id_missing = "SSID";
                    }
                    if ($NEW_CONTACTS['Plan_id'] == '0'){
                        $id_missing = "account plan";
                    }
                    if (!$NEW_CONTACTS['Firstname'] && !$NEW_CONTACTS['Lastname']){
                        $new_user = $NEW_CONTACTS['Email'];
                    }else{
                        $new_user = $NEW_CONTACTS['Lastname'] . " " . $NEW_CONTACTS['Firstname'];
                    }* /
                ?>
                <tr class="border">
                    <td width="20"><img src="images/ico_warning.png" alt="warning" width="16" height="16" /></td>
                    <td><span class="info_date">Newly added contact <strong><?=$new_user;?></strong>  does not have any <?=$id_missing;?> applied.</td>
                    <td width="10"><a href="index.php?section=users&action=edit&id=<?=$NEW_CONTACTS['id'];?>" class="tip_east edit" title="Click to apply <?=$id_missing;?> on this contact"><span>edit</span></a></td>
                </tr>
                <?//}
                ?>
                
                <tr>
                  <td colspan="5" height="5"></td>
                </tr>
                <tr class="smalltahoma">
                  <td colspan="5"><div align="right"><strong><a href="index.php?section=users">Manage Contacts »</a></strong></div></td>
                </tr>
                
            </table>

            </div>
            
            <div id="latest-orders" class="open_tab">
              <table width="100%" border="0" cellspacing="3" cellpadding="3" class="smarttable">
                <tr>
                  <td colspan="5" height="5"></td>
                </tr>
                <?  /*
                $SELECT_DOWN_ROUTERS = mysql_query("SELECT id, Router_name FROM routers_mikrotik WHERE Status = '0' ORDER BY Router_name ASC LIMIT 0, 10 ", $db);
                while ($DOWN_ROUTERS = mysql_fetch_array ($SELECT_DOWN_ROUTERS)){* /
                ?>
                <tr class="border">
                  <td width="20"><img src="images/ico_disabled.png" alt="down" width="16" height="16" /></td>
                  <td>Router <a href="index.php?section=routers_mikrotik&action=edit&id=<?=$DOWN_ROUTERS['id'];?>"><strong><?=$DOWN_ROUTERS['Router_name'];?></strong></a> is down!</td>
                </tr>
                <?//}
                ?>
                
                <tr>
                  <td colspan="5" height="5"></td>
                </tr>
                <tr class="smalltahoma">
                  <td colspan="5"><div align="right"><strong><a href="index.php?section=routers_mikrotik">Manage Routers »</a></strong></div></td>
                </tr>
              </table>
            </div>
              
      </div>                                        
      </fieldset>                          
                    
                    
                    
             </div>
             
             </div>       
                    
      <br />
             */?>
            
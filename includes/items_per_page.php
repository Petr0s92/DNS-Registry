<?php
/*-----------------------------------------------------------------------------
* Domain Registry Control Panel                                               *
*                                                                             *
* Developed by Vaggelis Koutroumpas - vaggelis@koutroumpas.gr                 *
* www.koutroumpas.gr  (c)2014                                                 * 
*-----------------------------------------------------------------------------*/

?>
				Records per page: 
                  <span style="color:#ccc">
                  <a href="index.php?section=<?=$SECTION;?>&<?=$url_vars?>&items_per_page=20"<? if ($num == 20) { ?> style="font-weight:bold;"<? } ?>>20</a> | 
                  <a href="index.php?section=<?=$SECTION;?>&<?=$url_vars?>&items_per_page=50"<? if ($num == 50) { ?> style="font-weight:bold;"<? } ?>>50</a> | 
                  <a href="index.php?section=<?=$SECTION;?>&<?=$url_vars?>&items_per_page=100"<? if ($num == 100) { ?> style="font-weight:bold;"<? } ?>>100</a> |
                  <a href="index.php?section=<?=$SECTION;?>&<?=$url_vars?>&items_per_page=200"<? if ($num == 200) { ?> style="font-weight:bold;"<? } ?>>200</a> |
                  <a href="index.php?section=<?=$SECTION;?>&<?=$url_vars?>&items_per_page=999999"<? if ($num == 999999) { ?> style="font-weight:bold;"<? } ?>>All</a> 
                  
                  </span>
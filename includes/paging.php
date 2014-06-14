<?php
/*-----------------------------------------------------------------------------
* Domain Registry Control Panel                                               *
*                                                                             *
* Developed by Vaggelis Koutroumpas - vaggelis@koutroumpas.gr                 *
* www.koutroumpas.gr  (c)2014                                                 * 
*-----------------------------------------------------------------------------*/

?>
                                            <table border="0" align="right" cellpadding="4" cellspacing="0" class="form_paging">
                                              <tr>
                                                <td><?
                                                    if ($pageno >= $num) {
                                                        $page_prev = $pageno - $num;
                                                    ?>
                                                    <a href="index.php?section=<?=$SECTION;?>&<?=$url_vars?>&pageno=<?=$page_prev?>" class="previous_page" title="Previous page"><span>Previous Page</span></a>
                                                    <? } else { ?>
                                                    <span class="previous_page_inactive"><span>Previous page</span></span>
                                                    <? } ?>
                                                </td>
                                                <td>Page</td>
                                                <td><form action="index.php?section=<?=$SECTION;?>&<?=$url_vars?>" method="post" name="paging" id="paging" style="margin:0">
                                                    <input name="goto" type="text" value="<?=$current_page?>" size="3" maxlength="3" class="paging_field" onblur="if(this.value=='') this.value='<?=$current_page?>';" onFocus="if(this.value=='<?=$current_page?>') this.value='';" />
                                                </form></td>
                                                <td>of <?=$total_pages?></td>
                                                <td><?
                                                    if ($pageno < ($items_number - $num)) {
                                                        $page_next = $pageno + $num;
                                                    ?>
                                                    <a href="index.php?section=<?=$SECTION;?>&<?=$url_vars?>&amp;pageno=<?=$page_next?>" class="next_page" title="Next page"><span>Next Page</span></a>
                                                    <? } else { ?>
                                                    <span class="next_page_inactive"><span>Next page</span></span>
                                                    <? } ?>
                                                </td>
                                              </tr>
                                            </table>
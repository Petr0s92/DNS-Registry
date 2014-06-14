<?php
/*-----------------------------------------------------------------------------
* Domain Registry Control Panel                                               *
*                                                                             *
* Developed by Vaggelis Koutroumpas - vaggelis@koutroumpas.gr                 *
* www.koutroumpas.gr  (c)2014                                                 * 
*-----------------------------------------------------------------------------*/

/*

CREATE TABLE sessions
(
    id varchar(32) NOT NULL,
    access int(10) unsigned,
    data text,
    PRIMARY KEY (id)
);

+--------+------------------+------+-----+---------+-------+
| Field  | Type             | Null | Key | Default | Extra |
+--------+------------------+------+-----+---------+-------+
| id     | varchar(32)      |      | PRI |         |       |
| access | int(10) unsigned | YES  |     | NULL    |       |
| data   | text             | YES  |     | NULL    |       |
+--------+------------------+------+-----+---------+-------+

*/

session_set_save_handler('_open',
                         '_close',
                         '_read',
                         '_write',
                         '_destroy',
                         '_clean');

function _open(){
    global $db;
    return $db;
}

function _close(){
    global $db;
    return true;
    //return mysql_close($db);
}

function _read($id){
    global $db;

    $id = mysql_real_escape_string($id);

    $sql = "SELECT `data` FROM `sessions` WHERE `id` = '$id'";

    if ($result = mysql_query($sql, $db))
    {
        if (mysql_num_rows($result))
        {
            $record = mysql_fetch_assoc($result);

            return $record['data'];
        }
    }  echo mysql_error();

    return '';
}

function _write($id, $data){   
    global $db;

    $access = time();

    $id = mysql_real_escape_string($id);
    $access = mysql_real_escape_string($access);
    $data = mysql_real_escape_string($data);

    $sql = "REPLACE INTO `sessions` VALUES  ('$id', '$access', '$data')";

    return mysql_query($sql, $db);
}

function _destroy($id){
    global $db;
    
    $id = mysql_real_escape_string($id);

    $sql = "DELETE FROM `sessions` WHERE `id` = '$id'";

    return mysql_query($sql, $db);
}

function _clean($max){
    global $db;
    
    $old = time() - $max;
    $old = mysql_real_escape_string($old);

    $sql = "DELETE FROM `sessions` WHERE `access` < '$old'";

    return mysql_query($sql, $db);
}

?>
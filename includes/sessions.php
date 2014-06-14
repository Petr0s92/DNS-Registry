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
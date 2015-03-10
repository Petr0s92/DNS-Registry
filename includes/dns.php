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

// Borrowed almost verbatim from poweradmin project https://github.com/poweradmin/poweradmin

// row_id (if edit), domain_id, type, content, name, priority, ttl
function validate_input($rid, $zid, $type, &$content, $name, &$prio, &$ttl) {

	
    $zone = get_zone_name_from_id($zid);    // TODO check for return
        
    if (!(preg_match("/$zone$/i", $name))) {
        if (isset($name) && $name != "") {
            $name = $name . "." . $zone;
        } else {
            $name = $zone;
        }
    }
    
       
    switch ($type) {

        case "A":
			if ($return = is_valid_ipv4($content)) {
                return $return;
            }
            if ($return = is_valid_rr_cname_exists($name, $rid)) {
                return $return;
            }
            if ($return = is_valid_hostname_fqdn($name, 1)) {
                return $return;
            }
            break;

/*
        case "AAAA":
            if (!is_valid_ipv6($content)) {
                return false;
            }
            if (!is_valid_rr_cname_exists($name, $rid)) {
                return false;
            }
            if (!is_valid_hostname_fqdn($name, 1)) {
                return false;
            }
            break;

        case "AFSDB": // TODO: implement validation.
            break;

        case "CERT": // TODO: implement validation.
            break;

*/
        case "CNAME":
            if ($return = is_valid_rr_cname_name($name)) {
                return $return;
            }
            if ($return = is_valid_rr_cname_unique($name, $rid)) {
                return $return;
            }
            if ($return = is_valid_hostname_fqdn($name, 1)) {
                return $return;
            }
            if ($return = is_valid_hostname_fqdn($content, 0)) {
                return $return;
            }
            if ($return = is_not_empty_cname_rr($name, $zone)) {
                return $return;
            }
            break;

/*
        case 'DHCID': // TODO: implement validation
            break;

        case 'DLV': // TODO: implement validation
            break;

        case 'DNSKEY': // TODO: implement validation
            break;

        case 'DS': // TODO: implement validation
            break;

        case 'EUI48': // TODO: implement validation
            break;

        case 'EUI64': // TODO: implement validation
            break;

        case "HINFO":
            if (!is_valid_rr_hinfo_content($content)) {
                return false;
            }
            if (!is_valid_hostname_fqdn($name, 1)) {
                return false;
            }
            break;

        case 'IPSECKEY': // TODO: implement validation
            break;

        case 'KEY': // TODO: implement validation
            break;

        case 'KX': // TODO: implement validation
            break;

        case "LOC":
            if (!is_valid_loc($content)) {
                return false;
            }
            if (!is_valid_hostname_fqdn($name, 1)) {
                return false;
            }
            break;

        case 'MINFO': // TODO: implement validation
            break;

        case 'MR': // TODO: implement validation
            break;
*/
        case "MX":
            if ($return = is_valid_hostname_fqdn($content, 0)) {
                return $return;
            }
            if ($return = is_valid_hostname_fqdn($name, 1)) {
                return $return;
            }
            if ($return = is_valid_non_alias_target($content)) {
                return $return;
            }
            if (is_valid_ipv4($content) == false) {
                return "You cannot enter IP address in MX record.";
            }
            break;
/*
        case 'NAPTR': // TODO: implement validation
            break;
*/
        case "NS":
            if ($return = is_valid_hostname_fqdn($content, 0)) {
                return $return;
            }
            if ($return = is_valid_hostname_fqdn($name, 1)) {
                return $return;
            }
            if ($return = is_valid_non_alias_target($content)) {
                return $return;
            }
            break;
/*
        case 'NSEC': // TODO: implement validation
            break;

        case 'NSEC3': // TODO: implement validation
            break;

        case 'NSEC3PARAM': // TODO: implement validation
            break;

        case 'OPT': // TODO: implement validation
            break;
*/
        case "PTR":
            if ($return = is_valid_hostname_fqdn($content, 0)) {
                return $return;
            }
            if ($return = is_valid_hostname_fqdn($name, 1)) {
                return $return;
            }
            break;
/*
        case 'RKEY': // TODO: implement validation
            break;

        case 'RP': // TODO: implement validation
            break;

        case 'RRSIG': // TODO: implement validation
            break;

        case "SOA":
            if (!is_valid_rr_soa_name($name, $zone)) {
                return false;
            }
            if (!is_valid_hostname_fqdn($name, 1)) {
                return false;
            }
            if (!is_valid_rr_soa_content($content)) {
                error(ERR_DNS_CONTENT);
                return false;
            }
            break;

*/

        case "SPF":
            if ($return = is_valid_spf($content)) {
                return $return;
            }
            break;

        case "SRV":
            if ($return = is_valid_rr_srv_name($name)) {
                return $return;
            }
            if ($return = is_valid_rr_srv_content($content)) {
                return $return;
            }
            break;

/*
        case 'SSHFP': // TODO: implement validation
            break;

        case 'TLSA': // TODO: implement validation
            break;

        case 'TSIG': // TODO: implement validation
            break;
*/
        case "TXT":
            if ($return = is_valid_printable($name)) {
                return $return;
            }
            if ($return = is_valid_printable($content)) {
                return $return;
            }
            break;
/*
        case 'WKS': // TODO: implement validation
            break;

        case "CURL":
        case "MBOXFW":
        case "URL":
            // TODO: implement validation?
            // Fancy types are not supported anymore in PowerDNS
            break;
*/
        default:
            //error(ERR_DNS_RR_TYPE);
            return false;
    }

    if ($return = is_valid_rr_prio($prio, $type)) {
        return $return;
    }
    if ($return = is_valid_rr_ttl($ttl)) {
        return $return;
    }

    return false;
}

/** Get Zone Name from Zone ID
 *
 * @param int $zid Zone ID
 *
 * @return string Domain name
 */
function get_zone_name_from_id($zid) {
    global $db;

    if (is_numeric($zid)) {
    	$select = mysql_query("SELECT name FROM domains WHERE id = '".$zid."' ", $db);
    	$result = mysql_fetch_array($select); 
        if ($result) {
            return $result["name"];
        } else {
            //error(sprintf("Zone does not exist."));
            return false;
        }
    } else {
        //error(sprintf(ERR_INV_ARGC, "get_zone_name_from_id", "Not a valid domainid: $zid"));
        return false;
    }
}


/** Test if IPv4 address is valid
 *
 * @param string $ipv4 IPv4 address string
 * @param boolean $answer print error if true
 * [default=true]
 *
 * @return boolean true if valid, false otherwise
 */
function is_valid_ipv4($ipv4, $answer = true) {

// 20080424/RZ: The current code may be replaced by the following if()
// statement, but it will raise the required PHP version to ">= 5.2.0".
// Not sure if we want that now.
//
//	if(filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === FALSE) {
//		error(ERR_DNS_IPV4); return false;
//	}

    if (!preg_match("/^[0-9\.]{7,15}$/", $ipv4)) {
        if ($answer) {
            //error(ERR_DNS_IPV4);
        }
        return "This is not a valid IPv4 address.";
    }

    $quads = explode('.', $ipv4);
    $numquads = count($quads);

    if ($numquads != 4) {
        if ($answer) {
            //error(ERR_DNS_IPV4);
        }
        return "This is not a valid IPv4 address.";
    }

    for ($i = 0; $i < 4; $i++) {
        if ($quads[$i] > 255) {
            if ($answer) {
                //error(ERR_DNS_IPV4);
            }
            return "This is not a valid IPv4 address.";
        }
    }

    return false;
}


/** Check if CNAME already exists
 *
 * @param string $name CNAME
 * @param int $rid Record ID
 *
 * @return boolean true if non-existant, false if exists
 */
function is_valid_rr_cname_exists($name, $rid) {
    global $db;

    $where = ($rid > 0 ? " AND id != " . $rid : '');
    $select = mysql_query("SELECT id FROM records WHERE name = '' ".$where." AND type = 'CNAME' ", $db);
    $response = mysql_fetch_array($select);
                        
    if ($response) {
        //error(ERR_DNS_CNAME_EXISTS);
        return "This is not a valid record. There is already exists a CNAME with this name.";
    }
    return false;
}


/** Test if hostname is valid FQDN
 *
 * @param mixed $hostname Hostname string
 * @param string $wildcard Hostname includes wildcard '*'
 *
 * @return boolean true if valid, false otherwise
 */
function is_valid_hostname_fqdn(&$hostname, $wildcard) {
//    global $dns_top_level_tld_check;
//    global $dns_strict_tld_check;
//    global $valid_tlds;

    $hostname = preg_replace("/\.$/", "", $hostname);

    # The full domain name may not exceed a total length of 253 characters.
    if (strlen($hostname) > 253) {
        //error(ERR_DNS_HN_TOO_LONG);
        return "The hostname is too long.";
    }

    $hostname_labels = explode('.', $hostname);
    $label_count = count($hostname_labels);

//    if ($dns_top_level_tld_check && $label_count == 1) {
//        return false;
//    }
        
    foreach ($hostname_labels as $hostname_label) {
        if ($wildcard == 1 && !isset($first)) {
        	if (!preg_match('/^(\*|[\w-\/]+)$/', $hostname_label)) {
                //error(ERR_DNS_HN_INV_CHARS);
                return "You have invalid characters in your hostname";
            }
            $first = 1;
        } else {
            if (!preg_match('/^[\w-\/]+$/', $hostname_label)) {
                //error(ERR_DNS_HN_INV_CHARS);
                return "You have invalid characters in your hostname";
            }  
        }
        if (substr($hostname_label, 0, 1) == "-") {
            //error(ERR_DNS_HN_DASH);
            return "A hostname can not start or end with a dash";
        }
        if (substr($hostname_label, -1, 1) == "-") {
            //error(ERR_DNS_HN_DASH);
            return "A hostname can not start or end with a dash";
        }
        if (strlen($hostname_label) < 1 || strlen($hostname_label) > 63) {
            //error(ERR_DNS_HN_LENGTH);
            return "Given hostname or one of the labels is too short or too long";
        }
    }

    if ($hostname_labels[$label_count - 1] == "arpa" && (substr_count($hostname_labels[0], "/") == 1 XOR substr_count($hostname_labels[1], "/") == 1)) {
        if (substr_count($hostname_labels[0], "/") == 1) {
            $array = explode("/", $hostname_labels[0]);
        } else {
            $array = explode("/", $hostname_labels[1]);
        }
        if (count($array) != 2) {
            //error(ERR_DNS_HOSTNAME);
            return "Invalid Reverse Name";
        }
        if (!is_numeric($array[0]) || $array[0] < 0 || $array[0] > 255) {
            //error(ERR_DNS_HOSTNAME);
            return "Invalid Reverse Name";
        }
        if (!is_numeric($array[1]) || $array[1] < 25 || $array[1] > 31) {
            //error(ERR_DNS_HOSTNAME);
            return "Invalid Reverse Name";
        }
    } else {
        if (substr_count($hostname, "/") > 0) {
            //error(ERR_DNS_HN_SLASH);
            return "Given hostname has too many slashes";
        }
    }

//    if ($dns_strict_tld_check && !in_array(strtolower($hostname_labels[$label_count - 1]), $valid_tlds)) {
//        error(ERR_DNS_INV_TLD);
//        return false;
//    }

    return false;
}



/** Test if CNAME is valid
 *
 * Check if any MX or NS entries exist which invalidated CNAME
 *
 * @param string $name CNAME to lookup
 *
 * @return boolean true if valid, false otherwise
 */
function is_valid_rr_cname_name($name) {
    global $db;

    $select = mysql_query("SELECT id FROM records WHERE content = '".$name."' AND (type = 'MX' OR type = 'NS') ", $db);
    $response = mysql_fetch_array($select);
    
    if (!empty($response)) {
        //error(ERR_DNS_CNAME);
        return "This is not a valid CNAME. Did you assign an MX or NS record to the record?";
    }

    return false;
}


/** Check if CNAME is unique (doesn't overlap A/AAAA)
 *
 * @param string $name CNAME
 * @param string $rid Record ID
 *
 * @return boolean true if unique, false if duplicate
 */
function is_valid_rr_cname_unique($name, $rid) {
    global $db;

    $where = ($rid > 0 ? " AND id != " . $rid : '');
    $select = mysql_query("SELECT id FROM records WHERE name = '".$name."' ".$where." AND type IN ('A', 'AAAA', 'CNAME')", $db);
    $response = mysql_fetch_array($select);
    
    if ($response) {
        //error(ERR_DNS_CNAME_UNIQUE);
        return "This is not a valid CNAME. There is already an A or CNAME with this name";
    }
    return false;
}


/**
 * Check that the zone does not have a empty CNAME RR
 *
 * @param string $name
 * @param string $zone
 */
function is_not_empty_cname_rr($name, $zone) {

    if ($name == $zone) {
        //error(ERR_DNS_CNAME_EMPTY);
        return "Empty CNAME records are not allowed";
    }
    return false;
}


/** Check if target is not a CNAME
 *
 * @param string $target target to check
 *
 * @return boolean true if not alias, false if CNAME exists
 */
function is_valid_non_alias_target($target) {
    global $db;
    
    $select = mysql_query("SELECT id FROM records WHERE name = '".$target."' AND type = 'CNAME' ", $db);
    $response = mysql_fetch_array($select);
    
    if ($response) {
        //error(ERR_DNS_NON_ALIAS_TARGET);
        return "You can not point a NS or MX record to a CNAME record. Remove or rame the CNAME record first, or take another name";
    }
    return false;
}


/** Check if SPF content is valid
 *
 * @param string $content SPF content
 *
 * @return boolean true if valid, false otherwise
 */
function is_valid_spf($content) {
    //Regex from http://www.schlitt.net/spf/tests/spf_record_regexp-03.txt
    $regex = "^[Vv]=[Ss][Pp][Ff]1( +([-+?~]?([Aa][Ll][Ll]|[Ii][Nn][Cc][Ll][Uu][Dd][Ee]:(%\{[CDHILOPR-Tcdhilopr-t]([1-9][0-9]?|10[0-9]|11[0-9]|12[0-8])?[Rr]?[+-/=_]*\}|%%|%_|%-|[!-$&-~])*(\.([A-Za-z]|[A-Za-z]([-0-9A-Za-z]?)*[0-9A-Za-z])|%\{[CDHILOPR-Tcdhilopr-t]([1-9][0-9]?|10[0-9]|11[0-9]|12[0-8])?[Rr]?[+-/=_]*\})|[Aa](:(%\{[CDHILOPR-Tcdhilopr-t]([1-9][0-9]?|10[0-9]|11[0-9]|12[0-8])?[Rr]?[+-/=_]*\}|%%|%_|%-|[!-$&-~])*(\.([A-Za-z]|[A-Za-z]([-0-9A-Za-z]?)*[0-9A-Za-z])|%\{[CDHILOPR-Tcdhilopr-t]([1-9][0-9]?|10[0-9]|11[0-9]|12[0-8])?[Rr]?[+-/=_]*\}))?((/([1-9]|1[0-9]|2[0-9]|3[0-2]))?(//([1-9][0-9]?|10[0-9]|11[0-9]|12[0-8]))?)?|[Mm][Xx](:(%\{[CDHILOPR-Tcdhilopr-t]([1-9][0-9]?|10[0-9]|11[0-9]|12[0-8])?[Rr]?[+-/=_]*\}|%%|%_|%-|[!-$&-~])*(\.([A-Za-z]|[A-Za-z]([-0-9A-Za-z]?)*[0-9A-Za-z])|%\{[CDHILOPR-Tcdhilopr-t]([1-9][0-9]?|10[0-9]|11[0-9]|12[0-8])?[Rr]?[+-/=_]*\}))?((/([1-9]|1[0-9]|2[0-9]|3[0-2]))?(//([1-9][0-9]?|10[0-9]|11[0-9]|12[0-8]))?)?|[Pp][Tt][Rr](:(%\{[CDHILOPR-Tcdhilopr-t]([1-9][0-9]?|10[0-9]|11[0-9]|12[0-8])?[Rr]?[+-/=_]*\}|%%|%_|%-|[!-$&-~])*(\.([A-Za-z]|[A-Za-z]([-0-9A-Za-z]?)*[0-9A-Za-z])|%\{[CDHILOPR-Tcdhilopr-t]([1-9][0-9]?|10[0-9]|11[0-9]|12[0-8])?[Rr]?[+-/=_]*\}))?|[Ii][Pp]4:([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])(/([1-9]|1[0-9]|2[0-9]|3[0-2]))?|[Ii][Pp]6:(::|([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4}|([0-9A-Fa-f]{1,4}:){1,8}:|([0-9A-Fa-f]{1,4}:){7}:[0-9A-Fa-f]{1,4}|([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}){1,2}|([0-9A-Fa-f]{1,4}:){5}(:[0-9A-Fa-f]{1,4}){1,3}|([0-9A-Fa-f]{1,4}:){4}(:[0-9A-Fa-f]{1,4}){1,4}|([0-9A-Fa-f]{1,4}:){3}(:[0-9A-Fa-f]{1,4}){1,5}|([0-9A-Fa-f]{1,4}:){2}(:[0-9A-Fa-f]{1,4}){1,6}|[0-9A-Fa-f]{1,4}:(:[0-9A-Fa-f]{1,4}){1,7}|:(:[0-9A-Fa-f]{1,4}){1,8}|([0-9A-Fa-f]{1,4}:){6}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])|([0-9A-Fa-f]{1,4}:){6}:([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])|([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])|([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])|([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])|([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])|[0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])|::([0-9A-Fa-f]{1,4}:){0,6}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]))(/([1-9][0-9]?|10[0-9]|11[0-9]|12[0-8]))?|[Ee][Xx][Ii][Ss][Tt][Ss]:(%\{[CDHILOPR-Tcdhilopr-t]([1-9][0-9]?|10[0-9]|11[0-9]|12[0-8])?[Rr]?[+-/=_]*\}|%%|%_|%-|[!-$&-~])*(\.([A-Za-z]|[A-Za-z]([-0-9A-Za-z]?)*[0-9A-Za-z])|%\{[CDHILOPR-Tcdhilopr-t]([1-9][0-9]?|10[0-9]|11[0-9]|12[0-8])?[Rr]?[+-/=_]*\}))|[Rr][Ee][Dd][Ii][Rr][Ee][Cc][Tt]=(%\{[CDHILOPR-Tcdhilopr-t]([1-9][0-9]?|10[0-9]|11[0-9]|12[0-8])?[Rr]?[+-/=_]*\}|%%|%_|%-|[!-$&-~])*(\.([A-Za-z]|[A-Za-z]([-0-9A-Za-z]?)*[0-9A-Za-z])|%\{[CDHILOPR-Tcdhilopr-t]([1-9][0-9]?|10[0-9]|11[0-9]|12[0-8])?[Rr]?[+-/=_]*\})|[Ee][Xx][Pp]=(%\{[CDHILOPR-Tcdhilopr-t]([1-9][0-9]?|10[0-9]|11[0-9]|12[0-8])?[Rr]?[+-/=_]*\}|%%|%_|%-|[!-$&-~])*(\.([A-Za-z]|[A-Za-z]([-0-9A-Za-z]?)*[0-9A-Za-z])|%\{[CDHILOPR-Tcdhilopr-t]([1-9][0-9]?|10[0-9]|11[0-9]|12[0-8])?[Rr]?[+-/=_]*\})|[A-Za-z][-.0-9A-Z_a-z]*=(%\{[CDHILOPR-Tcdhilopr-t]([1-9][0-9]?|10[0-9]|11[0-9]|12[0-8])?[Rr]?[+-/=_]*\}|%%|%_|%-|[!-$&-~])*))* *$^";
    if (!preg_match($regex, $content)) {
        return "Invalid SPF record";
    } else {
        return false;
    }
}


/** Check if SRV name is valid
 *
 * @param mixed $name SRV name
 *
 * @return boolean true if valid, false otherwise
 */
function is_valid_rr_srv_name(&$name) {

    if (strlen($name) > 255) {
        //error(ERR_DNS_HN_TOO_LONG);
        return "The hostname is too long";
    }

    $fields = explode('.', $name, 3);
    if (!preg_match('/^_[\w-]+$/i', $fields[0])) {
        //error(ERR_DNS_SRV_NAME);
        return "Invalid value for name field of SRV record";
    }
    if (!preg_match('/^_[\w]+$/i', $fields[1])) {
        //error(ERR_DNS_SRV_NAME);
        return "Invalid value for name field of SRV record";
    }
    if (!is_valid_hostname_fqdn($fields[2], 0)) {
        //error(ERR_DNS_SRV_NAME);
        return "Invalid value for name field of SRV record";
    }
    $name = join('.', $fields);
    return false;
}


/** Check if SRV content is valid
 *
 * @param mixed $content SRV content
 *
 * @return boolean true if valid, false otherwise
 */
function is_valid_rr_srv_content(&$content) {
    $fields = preg_split("/\s+/", trim($content), 3);
    if (!is_numeric($fields[0]) || $fields[0] < 0 || $fields[0] > 65535) {
        //error(ERR_DNS_SRV_WGHT);
        return "Invalid value for the priority field of the SRV record";
    }
    if (!is_numeric($fields[1]) || $fields[1] < 0 || $fields[1] > 65535) {
        //error(ERR_DNS_SRV_PORT);
        return "Invalid value for the weight field of the SRV record";
    }
    if ($fields[2] == "" || ($fields[2] != "." && !is_valid_hostname_fqdn($fields[2], 0))) {
        //error(ERR_DNS_SRV_TRGT);
        return "Invalid SRV target";
    }
    $content = join(' ', $fields);
    return false;
}


/** Test if string is printable
 *
 * @param string $string string
 *
 * @return boolean true if valid, false otherwise
 */
function is_valid_printable($string) {
    if (!preg_match('/^[[:print:]]+$/', trim($string))) {
        //error(ERR_DNS_PRINTABLE);
        return "Invalid characters have been used in this record";
    }
    return false;
}

/** Check if Priority is valid
 *
 * Check if MX or SRV priority is within range, otherwise set to 0
 *
 * @param mixed $prio Priority
 * @param string $type Record type
 *
 * @return boolean true if valid, false otherwise
 */
function is_valid_rr_prio(&$prio, $type) {
    if ($type == "MX" || $type == "SRV") {
        if (!is_numeric($prio) || $prio < 0 || $prio > 65535) {
            //error(ERR_DNS_INV_PRIO);
            return "Invalid value for prio field. It should be numeric.";
        }
    } else {
        $prio = 0;
    }

    return false;
}


/** Check if TTL is valid and within range
 *
 * @param int $ttl TTL
 *
 * @return boolean true if valid,false otherwise
 */
function is_valid_rr_ttl(&$ttl) {
    
    if (!is_numeric($ttl) || $ttl < 0 || $ttl > 2147483647) {
        //error(ERR_DNS_INV_TTL);
        return "Invalid value for TTL field. It should be numeric";
    }

    return false;
}

/** Check if SOA name is valid
 *
 * Checks if SOA name = zone name
 *
 * @param string $name SOA name
 * @param string $zone Zone name
 *
 * @return boolean true if valid, false otherwise
 */
function is_valid_rr_soa_name($name, $zone) {
    if ($name != $zone) {
        //error(ERR_DNS_SOA_NAME);
        return "Invalid value for name field of SOA record. It should be the name of the zone";
    }
    return false;
}


?>
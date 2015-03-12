DROP TABLE IF EXISTS `cryptokeys`;
CREATE TABLE IF NOT EXISTS `cryptokeys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain_id` int(11) NOT NULL,
  `flags` int(11) NOT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `content` text,
  PRIMARY KEY (`id`),
  KEY `domainidindex` (`domain_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;


DROP TABLE IF EXISTS `domainmetadata`;
CREATE TABLE IF NOT EXISTS `domainmetadata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain_id` int(11) NOT NULL,
  `kind` varchar(16) DEFAULT NULL,
  `content` text,
  PRIMARY KEY (`id`),
  KEY `domainmetadata_idx` (`domain_id`, `kind`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;


DROP TABLE IF EXISTS `domains`;
CREATE TABLE IF NOT EXISTS `domains` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `master` varchar(20) DEFAULT NULL,
  `last_check` int(11) DEFAULT NULL,
  `type` varchar(6) NOT NULL,
  `notified_serial` int(11) DEFAULT NULL,
  `account` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_index` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;


DROP TABLE IF EXISTS `records`;
CREATE TABLE IF NOT EXISTS `records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `type` varchar(10) DEFAULT NULL,
  `content` varchar(255) DEFAULT NULL,
  `ttl` int(11) DEFAULT NULL,
  `prio` int(11) DEFAULT NULL,
  `change_date` int(11) DEFAULT NULL,
  `ordername` varchar(255) DEFAULT NULL,
  `auth` tinyint(1) DEFAULT NULL,
  `disabled` tinyint(1) NOT NULL DEFAULT '0',
  `created` int(10) NOT NULL,
  `user_id` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `rec_name_index` (`name`),
  KEY `nametype_index` (`name`,`type`),
  KEY `domain_id` (`domain_id`),
  KEY `recordorder` (`domain_id`, `ordername`),
  KEY `user_id` (`created`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

ALTER TABLE `records` ADD CONSTRAINT `records_ibfk_1` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE CASCADE;
ALTER TABLE `domainmetadata` ADD CONSTRAINT `records_ibfk_2` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE CASCADE;

DROP TABLE IF EXISTS `root_ns`;
CREATE TABLE IF NOT EXISTS `root_ns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `active` enum('1','0') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_index` (`name`),
  KEY `active` (`active`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `root_ns_unicast` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `active` enum('1','0') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_index` (`name`),
  KEY `active` (`active`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;



DROP TABLE IF EXISTS `sessions`;
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(32) NOT NULL,
  `access` int(10) unsigned DEFAULT NULL,
  `data` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;


DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `Name` varchar(100) NOT NULL,
  `Value` varchar(255) NOT NULL,
  `Description` varchar(100) NOT NULL,
  `Type` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=12 ;


INSERT INTO `settings` (`id`, `Name`, `Value`, `Description`, `Type`) VALUES
(1, 'MAIL_SUPPORT', 'contact@your-domain.tld', 'Support Mail Address', 'general'),
(2, 'ADMIN_ITEMS_PER_PAGE', '100', 'Records per page', 'panel'),
(3, 'APP_NAME', 'My Wireless Domains', 'Application Name (custom branding)', 'general'),
(4, 'APP_URL', 'https://www.your-domain.tld/registry', 'Full URL to Control Panel (without trailing slash)', 'panel'),
(5, 'COOKIE_NAME', 'mydomains', 'Cookie name for panel', 'panel'),
(6, 'REG_ALLOWED_IPS', '10.0.0.0/8', 'Allowed IPs for open registration. Type ''any'' to allow all IPs.', 'panel'),
(7, 'RECORDS_TTL', '86400', 'TTL Used for NS & A (glue) records in TLD zone.', 'general'),
(8, 'DNS_VALIDATE_WAIT', '2', 'How long the domain validator should wait for a reply from the nameserver (seconds)', 'panel'),
(9, 'DNS_VALIDATE_RETRY', '2', 'How many times the domain validator should try to get a reply from a nameserver', 'panel'),
(10, 'PORTAL_URL', 'https://www.your-domain.tld', 'Full url to web portal', 'panel'),
(11, 'DEFAULT_SOA', 'ns1.your-domain.tld hostmaster.your-domain.tld 2014122201 21600 3600 3600000 120', 'Default SOA record for all new TLDs', 'panel'),
(12, 'WHOIS_USER', 'nobody', 'User under which the WHOIS Server will run.', 'whois'),
(13, 'WHOIS_URL', 'https://www.your-domain.tld', 'Webbased Whois URL.', 'whois'),
(14, 'WHOIS_PORT', '43', 'Whois server port. Default: 43', 'whois'),
(15, 'WHOIS_ADDRESS', '0', 'Whois server address. Set to 0 to listen to all system addresses.', 'whois'),
(16, 'WHOIS_MAXLENGTH', '256', 'Maximum number of characters the whois server will accept.', 'whois'),
(17, 'WHOIS_LISTENQ', '10', 'Whois server listen queue.', 'whois'),
(18, 'WHOIS_MAXCONN', '100', 'Whois server maximum concurrent connections.', 'whois'),
(19, 'META_SLAVE_PORT', '5353', 'Port for Meta-Slave', 'panel'),
(20, 'NAMESERVERS_IP_RANGE', '10.0.0.0/8', 'Allowed IPs for Nameservers registration. Type ''any'' to allow all IPs.', 'panel'),
(21, 'TERMS_URL', 'http://www.your-domain.tld/terms', 'Terms And Conditions URL for users to accept during registration. Leave empty to disable.', 'panel'),
(22, 'SUPPORT_URL', 'http://www.your-domain.tld/forum', 'Support URL. Will be shown on the left menu. Leave empty to disable', 'panel'),
(23, 'ALLOW_USERS_REVERSE', 'yes', 'Allow users to create zones under .in-addr.arpa TLDs. Use yes/no to enable/disable.', 'panel'),
(24, 'PDNS_CONTROL_KEY', 'PUT_YOUR_SECRET_HERE', 'Secret Key to pdns_control', 'panel'),
(25, 'PDNS_CONTROL_PATH', '/usr/bin/pdns_control', 'Full path to pdns_control executable', 'panel'),
(26, 'PDNS_CONTROL_IP', '127.0.0.1', 'IP for pdns_control', 'panel'),
(27, 'PDNS_CONTROL_PORT', '53000', 'Port for pdns_control', 'panel'),
(28, 'WHOIS_SERVER', 'whois.tld', 'Whois server. It will appear on the left menu. Leave empty to disable', 'panel');


DROP TABLE IF EXISTS `supermasters`;
CREATE TABLE IF NOT EXISTS `supermasters` (
  `ip` varchar(25) NOT NULL,
  `nameserver` varchar(255) NOT NULL,
  `account` varchar(40) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `tlds`;
CREATE TABLE IF NOT EXISTS `tlds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('auth','hosted') NOT NULL,
  `active` enum('1','0') NOT NULL,
  `default` enum('1','0') NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_index` (`name`),
  KEY `active` (`active`),
  KEY `type` (`type`),
  KEY `default` (`default`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;


DROP TABLE IF EXISTS `tsigkeys`;
CREATE TABLE IF NOT EXISTS `tsigkeys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `algorithm` varchar(255) DEFAULT NULL,
  `secret` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `namealgoindex` (`name`,`algorithm`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;


DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL,
  `password` varchar(128) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `description` varchar(1024) NOT NULL,
  `perm_templ` int(1) NOT NULL,
  `active` int(1) NOT NULL,
  `use_ldap` int(1) NOT NULL,
  `Admin_level` enum('admin','user') NOT NULL DEFAULT 'user',
  `Help` enum('1','0') NOT NULL DEFAULT '1',
  `registered` int(10) NOT NULL,
  `last_login` int(10) NOT NULL,
  `last_ip` varchar(15) NOT NULL,
  `nodeid` int(10) NOT NULL,
  `wireless_community` VARCHAR(100) NOT NULL,
  `default_ttl_domains` int(10) NOT NULL DEFAULT '86400',
  `default_ttl_records` int(10) NOT NULL DEFAULT '86400',
  PRIMARY KEY (`id`),
  KEY `Admin_level` (`Admin_level`,`Help`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- Default admin user
INSERT INTO `users` (`id`, `username`, `password`, `fullname`, `email`, `description`, `perm_templ`, `active`, `use_ldap`, `Admin_level`, `Help`, `registered`, `last_login`, `last_ip`, `nodeid`) VALUES
(1, 'admin', 'd033e22ae348aeb5660fc2140aec35850c4da997', 'Administrator - TLD Owner', 'admin@your-domain.tld', 'Administrator with full rights', 1, 1, 0, 'admin', '1', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '0.0.0.0', 1);

-- Create meta-zone for slaves automatic zones delete
INSERT INTO `domains` (`id`, `name`, `master`, `last_check`, `type`, `notified_serial`, `account` ) VALUES (1, 'meta.meta', NULL, NULL, 'MASTER', NULL, NULL);
INSERT INTO `records` (`domain_id`, `name`, `type`, `content`, `ttl`, `prio`, `change_date`, `ordername`, `auth`, `disabled`, `created`, `user_id`) VALUES 
(1, 'meta.meta', 'SOA', 'meta.meta root.meta 2015030701 10800 3600 3600 120', '86400', 0, UNIX_TIMESTAMP(), NULL, NULL, 0, 0, 0);


-- Create meta-zone TXT record for slaves delete on domain delete
DELIMITER $$$

DROP TRIGGER IF EXISTS domains_del;

CREATE TRIGGER `domains_del` AFTER DELETE ON `domains`
FOR EACH ROW 
BEGIN

    -- Add a record to the records table for the "metazone"
    -- indicating that a zone is being deleted.

    SET @metazone = 'meta.meta';
    SET @d_id = OLD.id;
    SET @d_name = OLD.name;
    SET @txt = CONCAT('"', 'd=', @d_id, ' ', UTC_TIMESTAMP(), '"');
    
    -- Remove all records for this zone.
    -- no need - we handle this already via PHP
    -- DELETE FROM records WHERE domain_id = @d_id;

    -- Add a NEW record to the metazone detailing which zone we've
    -- just deleted.

    INSERT INTO records (domain_id, name, ttl, type, content)
        SELECT id, CONCAT(@d_name, '.', @metazone), 86400, 'TXT', @txt
        FROM domains WHERE name = @metazone;

    -- Update SOA record in metazone.

    UPDATE records
        SET content = CONCAT(@metazone, ' root.meta ', UNIX_TIMESTAMP(NOW()), ' 10800 3600 3600 120')
        WHERE domain_id = (SELECT id FROM domains WHERE name = @metazone)
              AND type = 'SOA';
END $$$

DELIMITER ;


-- Cleanup meta-zone on new domains
DELIMITER $$$

DROP TRIGGER IF EXISTS domains_add;

CREATE TRIGGER `domains_add` AFTER INSERT ON `domains`
FOR EACH ROW BEGIN

    -- Remove a possibly previously 'deleted' zone by deleting its
    -- record from the "metazone" zone.

    SET @metazone = 'meta.meta';
    SET @d_name = NEW.name;

    DELETE FROM records
        WHERE domain_id = (SELECT id FROM domains WHERE name = @metazone)
             AND name = CONCAT(@d_name, '.', @metazone);

    -- Update SOA record in metazone.

    UPDATE records
        SET content = CONCAT(@metazone, ' root.meta ', UNIX_TIMESTAMP(NOW()), ' 10800 3600 3600 120')
        WHERE domain_id = (SELECT id FROM domains WHERE name = @metazone)
              AND type = 'SOA';

END $$$
DELIMITER ;

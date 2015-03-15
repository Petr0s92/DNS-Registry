# Requirements:

* Linux (CentOS/Fedora or Debian/Ubuntu)
* Apache 2+
* PHP 5.2+
* PHP cli
* MySQL 5.1+
* PEAR Net_DNS 1.0.7
* PEAR Net_WHOIS 1.0.5
* phpseclib
* PEAR System_Daemon 1.0.0

# Installation:

This guide comprises of 4 distinct parts.

* The DNS Registry Control Panel
* The Hidden Master DNS Server based on PowerDNS
* The MySQL Database that the Control Panel and PowerDNS use
* The Whois Server

Typically you would have a server (VM or Physical) that runs the Control Panel along with the PowerDNS Hidden Master and the Whois Server and on separate servers you would have the NSD slaves with the Super Slave daemon to automatically provision new zones to the slaves.

The amount of slave servers depends on your size so you could run only two (for redanduncy).

The whole system *could* run on a single server if PowerDNS NSD and BIND are configured to listen on specific IPs/Ports so they wont interfere with each other but this is not a *supported* configuration. Try it at your own risk.

Wherever you need to generate a key you can use the following command

`dd if=/dev/random of=/dev/stdout count=1 bs=32 | base64`

The last line of the command above will give you a unique random key to use bellow when configuring the services.

**DNS Registry Panel**

First create a database and credentials to be used by PowerDNS and the DNS Registry Control Panel.

Import the db_schema.sql schema into the newly created database.

*do not import db_schema.sql via phpMyAdmin as it cannot import MySQL Triggers properly. Import it using mysql cli. eg: `mysql -u root -p < db_schema.sql`*

Copy all files to a web accessible directory. Eg: /dns

Edit `includes/config.php.sample` and add the MySQL Credentials.

Save as `config.php`


*Optional:*

Edit `dashboard.php.dist` to put your own 'welcome content' on the Dashboard page and save as `dashboard.php`

Upload your custom logo in `./images/logo.custom.png` to replace the default logo.
 

**Master (Hidden) Server Configuration (PowerDNS)**

Now you need to configure powerdns

Here's an example of the powerdns mysql backend configuration. The rest of the configuration is pretty much the defaults.

You will need to generate a CONTROL KEY for PowerDNS. This is for automatic provisioning of Slave zones on PowerDNS.
The same KEY will be used on the Control Panel settings later.


```
tcp-control-address=127.0.0.1
tcp-control-port=53000
tcp-control-range=127.0.0.1
tcp-control-secret=--YOUR--PDNS_CONTROL_KEY--HERE--

# Launch gmysql backend
launch=gmysql

# gmysql parameters
gmysql-host=localhost
gmysql-port=
gmysql-dbname=pdns
gmysql-user=pdns
gmysql-password=password
gmysql-dnssec=yes
# gmysql-socket=

gmysql-list-query-auth=select content,ttl,prio,type,domain_id,name, auth from records where domain_id='%d' and disabled != '1' order by name, type
``` 

**WHOIS Server Installation**

Make `whois-server.php` executable. eg: `chmod +x whois-server.php`

Copy init file to `/etc/init.d/whoisd`. There are two versions for CentOS or Debian/Ubuntu. Choose what suits your needs.

Edit `/etc/init.d/whoisd` and set the paths to your installation.

Set whois daemon to start on boot. 

For CentOS: `chkconfig whoisd on`

For Debian/Ubuntu: `update-rc whoisd defaults`

Start the daemon: `/etc/init.d/whoisd start`

Now you can do whois lookups with `whois -h your_whois_ip_or_host your.domain.tld`


**DNS Registry Panel initial configuration**

```
Visit http://your_domain/dns
Login with:
Username: admin
Password: admin
```

After logging in you should go to 'Settings' section and edit all settings according to your needs.

Don't forget to set PDNS_CONTROL_KEY as configured on PowerDNS.

Then you need to add the 'Root Nameservers' with their TSIG Secret Keys and IPs.
You will use the TSIG keys used above.

Then you need to add Unicast IPs on those Root Nameservers.

And finally you can add the TLDs that those Root Nameservers will serve.

If the root nameservers are part of a TLD that this system will manage you need to go to My Domain Names and edit the records that TLD.

Then add a new A record with the name of the nameserver and its IP. Repeat for as many nameservers you have.

You are now ready to create new domains and records.
# Requirements:

* Linux (CentOS or Debian/Ubuntu)
* Apache 2+
* PHP 5.2+
* PHP cli
* MySQL 5.5
* PEAR Net_DNS 1.0.7
* PEAR System_Daemon 1.0.0
* PERL Net::DNS
* PERL Net::DNS::Nameserver
* screen
* crontab

# Installation:

This system comprises of 6 distinct parts.

* The DNS Registry Control Panel
* The Hidden Master DNS Server based on PowerDNS
* The MySQL Database that the Control Panel and PowerDNS use
* The Slave DNS Server(s) based on NSD
* The Super Slave daemon to automatically provision new zones on the slave(s)  
* The Whois Server

Typically you would have a server (VM or Physical) that runs the Control Panel along with the PowerDNS Hidden Master and the Whois Server and on separate servers you would have the NSD slaves with the Super Slave daemon to automatically provision new zones to the slaves.

The amount of slave servers depends on your size so you could run only two (for redanduncy).

The whole system *could* run on a single server if PowerDNS and NSD are configured to listen on specific IPs/Ports so they wont interfere with each other but this is not a *supported* configuration. Try it at your own risk.

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

**DNS Registry Panel initial configuration**

```
Visit http://your_domain/dns
Login with:
Username: admin
Password: admin
```

After logging in you should go to 'Settings' section and edit all settings according to your needs.


Then you need to add the 'Root Nameservers' with their TSIG Secret Keys and IPs.

An easy way to generate a unique TSIG Key for each Root Nameserver is by running the following command on a linux terminal

`dd if=/dev/random of=/dev/stdout count=1 bs=32 | base64`

You then copy the last line of the output of the command above and use it as a TSIG Key.


And finally you can add the TLDs that those Root Nameservers will serve.

If the root nameservers are part of a TLD that this system will manage you need to go to My Domain Names and edit the records that TLD.

Then add a new A record with the name of the nameserver and its IP. Repeat for as many nameservers you have.

**Master (Hidden) Server Configuration (PowerDNS)**

Now you need to configure powerdns

Here's an example of the powerdns mysql backend configuration. The rest of the configuration is pretty much the defaults.


```
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


**Slave DNS Server Configuration (NSD)**

Then you need to configure your root nameservers (slaves) with the appropriate TSIG Keys.

Here's an example of NSD configuration for a Slave using TSIG.

```
# First set the TSIG Key for AXFR zone transfers
key:
		# The name of the TSIG Key is the name of the nameserver as registed on the control panel
        name: ns1.your-tld
        algorithm: hmac-md5
        secret: "--YOUR--TSIG--SECRET--KEY-HERE--"

# Pattern for automatic provisioning of new slave zones
pattern:
        # name by which the pattern is referred to
        name: "superslave"
        zonefile: "%s.zone"

        allow-notify: --YOUR--MASTER--PDNS-IP--HERE-- NOKEY
        request-xfr: --YOUR--MASTER--PDNS-IP--HERE-- ns1.your-tld
        allow-axfr-fallback: yes
        provide-xfr: --THIS--SLAVE--NSD--IP--HERE-- NOKEY

        outgoing-interface: --THIS--SLAVE--NSD--IP--HERE--

```

**Meta-Slave Server Installation**

Meta-slave is a small perl script that listens for NOTIFYs from the Master and issues commands to NSD to create new zones automatically.

On the scripts folder you will find 2 perl files. `nsd_superslave.pl nsd_cleanup_zones.pl` copy them to each NSD slave server on a folder you prefer (eg `/usr/local/bin`).

Make `nsd_superslave.pl` and `nsd_cleanup_zones.pl` executable with `chmod +x nsd_superslave.pl nsd_cleanup_zone.pl`

Edit both and set the appropriate parameters according to your installation.

Set nsd_superslave to start on boot:

Open `/etc/rc.local` and before `exit 0` add the following: 

`screen -dmS nsd_superslave /usr/local/bin/nsd_superslave.pl`

Start the daemon: `screen -dmS nsd_superslave /usr/local/bin/nsd_superslave.pl`

To check the output during operation run `screen -r -d nsd_superslave`

**Slave automatic zone cleanup**

create a new crontab as root to run the cleanup script every 10minutes (or however ofter you feel best) `crontab -e`  

And enter the following to run every 10 minutes

`*/10 * * * * /usr/local/bin/nsd_cleanup_zones.pl`

**WHOIS Server Installation**

Make `whois-server.php` executable. eg: `chmod +x whois-server.php`

Copy init file to `/etc/init.d/whoisd`. There are two versions for CentOS or Debian/Ubuntu. Choose what suits your needs.

Edit `/etc/init.d/whoisd` and set the paths to your installation.

Set whois daemon to start on boot. 

For CentOS: `chkconfig whoisd on`

For Debian/Ubuntu: `update-rc whoisd defaults`

Start the daemon: `/etc/init.d/whoisd start`

Now you can do whois lookups with `whois -h your_whois_ip_or_host your.domain.tld`




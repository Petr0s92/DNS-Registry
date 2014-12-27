# Requirements:

* Apache 2+
* PHP 5.2+
* MySQL 5+
* PEAR Net_DNS 1.0.7

# Installation:

First create a database and credentials to be used by PowerDNS and the DNS Registry Control Panel.

Import the db_schema.sql schema into the newly created database.


Copy all files to a web accessible directory. Eg: /dns

Edit `includes/config.php.sample` and add the MySQL Credentials.

Save as `config.php`


*Optional:*

Edit `dashboard.php.dist` to put your own 'welcome content' on the Dashboard page and save as `dashboard.php`

Upload your custom logo in `./images/logo.custom.png` to replace the default logo. 


```
Visit http://your_domain/dns
Login with:
Username: admin
Password: admin
```

After logging in you should go to 'Settings' section and edit all settings according to your needs.


Then you need to add the 'Root Nameservers' with their TSIG Secret Keys.

An easy way to generate a unique TSIG Key for each Root Nameserver is by running the following command on a linux terminal

`dd if=/dev/random of=/dev/stdout count=1 bs=32 | base64`

You then copy the last line of the output of the command above and use it as a TSIG Key.


And finally you can add the TLDs that those Root Nameservers will serve.

Now you need to configure powerdns

Here's an example of the powerdns mysql backend configuration


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


Then you need to configure your root nameservers (slaves) with the appropriate TSIG Keys.

Here's an example of NSD configuration for a Slave zone using TSIG.

```
# First set the TSIG Key for AXFR zone transfers
key:
		# The name of the TSIG Key is the name of the nameserver as registed on the control panel
        name: ns1.your-tld
        algorithm: hmac-md5
        secret: "--YOUR--TSIG--SECRET--KEY-HERE--"

# The slave zone - TLD
zone:
        # Set the TLD Name as registed on the control panel
        name: "tld-name"
        zonefile: "tld-name.zone"

        # The the PowerDNS Master IP and the TSIG Key 
        allow-notify: 10.1.1.25 NOKEY
        request-xfr: 10.1.1.25 ns1.your-tld

        allow-axfr-fallback: "yes"

        # Set the Outgoing IP for AXFR transfers
        outgoing-interface: 10.1.1.11
```

This control panel only abstracts the PowerDNS database into users and delegated domains for easier management of a private TLD.



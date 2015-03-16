# Requirements/Prequisities:

* Raspberry Pi Model B or Model B+
* 2GB+ SD Card/MicroSD Card (depending on r-pi model) Class 4 or better 
* 1GB USB Flash Stick
* DHCP Server on the LAN where the R-Pi will be connected. Static lease -MUST- be assigned for R-Pi's MAC Address.

# Installation:
Download latest (*2015-02-18 as of this writing*) **Minibian** distro image from here https://minibianpi.wordpress.com/

Install image to your SD Card. Instructions here: https://minibianpi.wordpress.com/setup/

Once the r-pi has booted and gotten an IP from the DHCP server login with SSH with user `root` and pass `raspberry`.

First configure raspberry-pi initial settings.

```
apt-get update
apt-get upgrade
apt-get install raspi-config
```

Run `raspi-config` and select the first option to expand the filesystem to use the whole SD card.

**Networking Configuration**

Edit `pico /etc/network/interfaces` and add the following:

```
# ROOT NS ANYCAST IP
auto lo:1
iface lo:1 inet static
        address 10.1.1.11
        netmask 255.255.255.255

# ROOT NS UNICAST IP
auto lo:2
iface lo:2 inet static
        address 10.1.1.211
        netmask 255.255.255.255

# ROOT NS CACHING NS ANYCAST IP
auto lo:3
iface lo:3 inet static
        address 10.1.1.10
        netmask 255.255.255.255
```

Change the IPs according to what your root NS should have.

Set device hostname on file `/etc/hostname`

**Prepare USB Flash Stick**

Use `fdisk /dev/sda` to delete any previous partition on the USB Flash stick and create a single Linux partition.

Format partition with `mkfs.ext4 /dev/sda1`

Add the following on `/etc/fstab`

`/dev/sda1 /data ext4 errors=remount-ro,noatime 0 0`

Run `mkdir /data`

Mount flash stick: `mount /data`

Now we are ready to install and configure our services.
  
**Install required packages**

```
apt-get install nano build-essential bind9 bind9utils bind9-host bash-completion dnsutils lrzsz telnet \
iotop iptraf htop curl traceroute mtr-tiny nano whois libevent-dev libssl-dev screen rsync libnet-dns-perl \
php5-cli php5-curl php5-sqlite php5-dev php-pear sudo
```

`pear install Net_DNS`

**Disable crond logging**

Edit `/etc/rsyslog.conf`

Find line `*.*;auth,authpriv.none             -/var/log/syslog` 

and change to

`*.*;auth,authpriv,cron.none             -/var/log/syslog`

Restart syslog to apply changes: `/etc/init.d/rsyslog restart`

**NSD Installation & Configuration**

Download, extract and compile latest version of NSD Nameserver

```
cd /root
wget https://www.nlnetlabs.nl/downloads/nsd/nsd-4.1.1.tar.gz
tar -zxf nsd-4.1.1.tar.gz
cd nsd-4.1.1
./configure
make
make install
```
Compilation will take a considerable amount of time due to Raspberry-Pi's slow CPU. Please be patient.

Once compilation is successfuly complete create NSD configuration file

`pico /etc/nsd/nsd.conf`

And add the following and change where needed:

```
# options for the nsd server
server:
        # Number of NSD servers to fork.  Put the number of CPUs to use here.
        server-count: 1

        # uncomment to specify specific interfaces to bind (default are the
        # wildcard interfaces 0.0.0.0 and ::0).
        ip-address: --YOUR--ROOT-NS--ANYCAST--IP--HERE--
        ip-address: --YOUR--ROOT-NS--UNICAST--IP--HERE--

        # debug-mode: yes

        do-ip6: no

        # Verbosity level.
        # verbosity: 10

        # The directory for zonefile: files.  The daemon chdirs here.
        zonesdir: "/data/nsd/var/db/nsd"

        # the list of dynamically added zones.
        zonelistfile: "/data/nsd/var/db/nsd/zone.list"

        # the database to use
        database: "/data/nsd/var/db/nsd/nsd.db"

        # log messages to file. Default to stderr and syslog (with
        # facility LOG_DAEMON).  stderr disappears when daemon goes to bg.
        logfile: "/data/nsd/var/log/nsd.log"

        # File to store pid for nsd in.
        pidfile: "/var/run/nsd.pid"

        # The file where secondary zone refresh and expire timeouts are kept.
        # If you delete this file, all secondary zones are forced to be 
        # 'refreshing' (as if nsd got a notify).
        xfrdfile: "/data/nsd/var/db/nsd/xfrd.state"

        # The directory where zone transfers are stored, in a subdir of it.
        xfrdir: "/data/tmp"

        # don't answer VERSION.BIND and VERSION.SERVER CHAOS class queries
        hide-version: yes

        # identify the server (CH TXT ID.SERVER entry).
        identity: "--YOUR--ROOT--NS--NAME--"

# Remote control config section. 
remote-control:
        # Enable remote control with nsd-control(8) here.
        # set up the keys and certificates with nsd-control-setup.
        control-enable: yes

        # what interfaces are listened to for control, default is on localhost.
        control-interface: 127.0.0.1
        # control-interface: ::1

        # port number for remote control operations (uses TLS over TCP).
        control-port: 8952

        # nsd server key file for remote control.
        server-key-file: "/etc/nsd/nsd_server.key"

        # nsd server certificate file for remote control.
        server-cert-file: "/etc/nsd/nsd_server.pem"

        # nsd-control key file.
        control-key-file: "/etc/nsd/nsd_control.key"

        # nsd-control certificate file.
        control-cert-file: "/etc/nsd/nsd_control.pem"

# Secret keys for TSIGs that secure zone transfers.
key:
        name: --YOUR--TSIG--KEY--NAME--HERE--
        algorithm: hmac-md5
        secret: "--YOUR--TSIG--KEY--HERE--"

# Pattern for automatic provisioning of new slave zones
pattern:
        # name by which the pattern is referred to
        name: "superslave"
        zonefile: "%s.zone"

        allow-notify: --YOUR--MASTER--PDNS--IP--HERE-- NOKEY
        request-xfr: --YOUR--MASTER--PDNS--IP--HERE-- --YOUR--TSIG--KEY--NAME--HERE--
        allow-axfr-fallback: yes
        provide-xfr: --YOUR--ROOT-NS--UNICAST--IP--HERE-- NOKEY

        outgoing-interface: --YOUR--ROOT-NS--UNICAST--IP--HERE--
```

Create `nsd` user/group

```
groupadd nsd
useradd -d /var/db/nsd -s /bin/false -g nsd nsd
```

Create NSD directories

```
mkdir /data/nsd
mkdir /data/nsd/var
mkdir /data/nsd/var/db
mkdir /data/nsd/var/db/nsd
mkdir /data/nsd/var/log
mkdir /data/tmp
mkdir /data/tmp/zones
chown nsd.nsd -R /data/nsd
chmod 777 -R /data/tmp
```

Create NSD control keys

`nsd-control-setup`

Startup configuration:

Copy init file `cp /root/nsd-4.1.1/contrib/nsd.init /etc/init.d/nsd`

Edit init file and set `configfile="/etc/nsd/nsd.conf"`

Also replace first line `#!/bin/sh` with the following:

```

#!/bin/sh
#
### BEGIN INIT INFO
# Provides:             nsd
# Required-Start:       $remote_fs $syslog
# Required-Stop:        $remote_fs $syslog
# Default-Start:        2 3 4 5
# Default-Stop:         0 1 6
# Short-Description:    NSD Authoritative Nameserver
### END INIT INFO
```

Make executable `chmod +x /etc/init.d/nsd`

Start on boot `update-rc nsd defaults`

Start NSD daemon `/etc/init.d/nsd start`

Create symbolic link to log file for easy access

`ln -s /data/nsd/var/log/nsd.log /var/log/nsd.log`

**BIND9 Configuration**

Edit `/etc/bind/named.conf.options` delete everything in it and set:

````
options {
        directory "/data/bind/var/cache/bind";
        auth-nxdomain no;
        dnssec-validation no;
        dnssec-enable no;
        listen-on { --YOUR--ROOT-NS--CACHING--ANYCAST--IP--HERE--; };
        recursion yes;
        allow-recursion { 10.0.0.0/8; 127.0.0.1; };
        allow-recursion-on { 10.0.0.0/8; 127.0.0.1; };
        allow-new-zones yes;
        max-cache-size 128m;
        query-source address --YOUR--ROOT-NS--CACHING--UNICAST--IP--HERE--;
        allow-transfer { };
};

logging{
        channel default_log {
                file "/data/bind/var/log/bind.log" versions 3 size 5m;
                severity info;
                print-time yes;
                print-severity yes;
                print-category yes;
        };

        category default      { default_log; };
        category general      { default_log; };
        category database     { default_log; };
        category security     { default_log; };
        category config       { default_log; };
        category resolver     { default_log; };
        category xfer-in      { default_log; };
        category xfer-out     { default_log; };
        category notify       { default_log; };
        category client       { default_log; };
        category unmatched    { default_log; };
        category queries      { default_log; };
        category network      { default_log; };
        category update       { default_log; };
        category dispatch     { default_log; };
        category dnssec       { default_log; };
        category lame-servers { default_log; };
};
````

Change `listen-on` IP to whatever your root NS Caching Anycast IP address is (as configured previously on `/etc/network/interfaces`)

Edit `/etc/default/bind9` and modify last line from:

`OPTIONS="-u bind"` 

to:

`OPTIONS="-u bind -4"`

Create BIND directories

```
mkdir /data/bind
mkdir /data/bind/var
mkdir /data/bind/var/cache
mkdir /data/bind/var/cache/bind
mkdir /data/bind/var/log 
chown bind.bind -R /data/bind
```

Restart BIND9 to apply changes `/etc/init.d/bind9 restart`

Create symbolic link to log file for easy access

`ln -s /data/bind/var/log/bind.log /var/log/bind.log`



**Metaslave Installation & Configuration**

Meta-slave is a small perl script that listens for NOTIFYs from the Master and issues commands to NSD to create new zones automatically.

On the scripts folder of DNS-Registry panel you will find 1 perl 1 .sh and 2 PHP files. `nsd_superslave.pl nsd_cleanup_zones.php bind_sync_zones.php bind_add_stub.sh` 

Upload them to the Raspberry-Pi on `/usr/local/bin`

Make them executable with `chmod +x nsd_superslave.pl nsd_cleanup_zone.php bind_sync_zones.php bind_add_stub.sh`

Edit `nsd_superslave.pl` and set `my $rootns = '--YOUR--ROOT-NS--UNICAST--IP--HERE--';` to your Root NS Unicast IP (as configured previously on `/etc/network/interfaces`).

Set nsd_superslave to start on boot:

Open `/etc/rc.local` and before `exit 0` add the following: 

`screen -dmS nsd_superslave /usr/local/bin/nsd_superslave.pl`

Start the daemon: `screen -dmS nsd_superslave /usr/local/bin/nsd_superslave.pl`

To check the daemon's output during operation run `screen -r -d nsd_superslave`

**Slave automatic zone cleanup**

Edit `nsd_cleanup_zones.php` to match your installation.

Set `$CONF['unicast_ip'] = '--YOUR--ROOT-NS--UNICAST--IP--HERE--';` to your Root NS Unicast IP (as configured previously on `/etc/network/interfaces`

Create cleanup script text db file with:

`touch /data/tmp/deleted_zones.txt`

Create a new crontab as root to run the cleanup script every 10minutes (or however ofter you feel best) `crontab -e`  

And enter the following to run every 10 minutes

`*/10 * * * * /usr/local/bin/nsd_cleanup_zones.php > /dev/null 2>&1`

**BIND caching NS automatic slave & stub zones sync**

Edit `bind_sync_zones.php` to match your installation.

Set `$CONF['unicast_ip'] = '--YOUR--ROOT-NS--UNICAST--IP--HERE--';` to your Root NS Unicast IP (as configured previously on `/etc/network/interfaces`

create a new crontab as root to run the cleanup script every 10minutes (or however ofter you feel best) `crontab -e`  

And enter the following to run every 10 minutes

`*/10 * * * * /usr/local/bin/bind_sync_zones.php > /dev/null 2>&1`

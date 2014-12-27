# DNS Registry Control Panel

A control panel to allow users to register domains on private TLDs.

Currenty this system is only a 'DNS Registry' providing only delegations to nameservers for each domain registered.
It is NOT an alternative to Poweradmin. And the db schema is not compatible with Poweradmin at the moment.


The zones are written on a MySQL Database and served via PowerDNS with it's MySQL Backend.
PowerDNS acts as a hidden master pushing each zone change to its configured Slaves which can be PowerDNS or NSD.


See CREDITS for other software used in this system.

### Warning: This software is still under heavy development. You should not use this in production just yet.
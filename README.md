# DNS Registry Control Panel

A control panel to allow users to register domains on private TLDs.
This system is primarily designed for use in Wireless Community Networks such as AWMN.net in order to provide the network with a centralized DNS registry with private TLDs for the users to register domains.

There are two modes of registering domains. Self Hosting where you define your own nameservers as you would on any internet TLD or Hosted Domains where the domain is registered and hosted on the same infrastructure.

**DNS Registry Control Panel Features**

* Configure private TLDs
* Configure Root Nameservers that will server the private TLDs and any domains registered and hosted under them.
* Register Domains under configured TLDs.
* Register Nameservers and Glue (IP) under registered Domains.
* Set any number of nameservers on registered domains.
* Allow use of 3rd party nameservers (which aren't handled by this system in any way).
* Or Host registered Domains on the same system (DNS Hosting with domain records management).
* User system with open registration (with allowed IP range, eg: 10.0.0.0/8).
* PowerDNS MySQL Backend Compatible Database Schema.
* Domain Validation before activation to keep the TLD public records clean.
* Whois Server for the users to lookup Domain Names.
* Domain transfers between users without the need for admins to approve the transfers. (todo)
* Mail notifications on various events. (todo)
* Automatic domain cleanup after configured times when domains are down or users no longer active. (todo) 

The zones are written on a MySQL Database and served via PowerDNS with it's MySQL Backend.
PowerDNS acts as a hidden master pushing each zone (change) to its configured Slaves which are running NSD.

For a rough installation guide check docs/

See CREDITS.md for other software used in this system.

### Warning: This software is still under heavy development. You should not use this in production just yet (or even testing :-P).
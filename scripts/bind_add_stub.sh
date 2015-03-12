#!/bin/bash

#USAGE bind_add_stub.sh DOMAIN MASTER_IP

# give it time for the slave to fetch the zone from master
# before we add the stub zone to bind

sleep 10

# Add stub zone to BIND
/usr/sbin/rndc addzone $1 in "
{
	type stub;
	file \"${1}.stub\";
	masters {
		$2;
	};
};"

/usr/sbin/rndc flush

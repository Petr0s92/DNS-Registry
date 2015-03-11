#!/bin/bash

#USAGE bind_add_stub.sh DOMAIN MASTER_IP

/usr/sbin/rndc addzone $1 in "
{
        type stub;
        file \"${1}.stub\";
        masters {
                $2;
        };
};"

# give it time for the slave to fetch the zone from master
# before we reload bind to make the new stub zone work

sleep 60
rndc reload

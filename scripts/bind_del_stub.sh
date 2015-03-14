#!/bin/bash

#USAGE bind_sel_stub.sh DOMAIN

/usr/sbin/rndc delzone $1
sleep 1
/usr/sbin/rndc reload
/usr/sbin/rndc flush
rm -rf /data/bind/var/cache/bind/$1.stub
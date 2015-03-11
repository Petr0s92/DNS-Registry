#!/bin/bash

#USAGE bind_sel_stub.sh DOMAIN

rndc delzone $1
sleep 1
rndc reload
rm -rf /var/cache/bind/$1.stub
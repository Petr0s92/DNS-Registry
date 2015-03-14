#!/usr/bin/perl

use strict;
use warnings;
use Net::DNS;

# Metazone name. Leave default unless you use another name for the metazone.
my $metazone = 'meta.meta';  

# Full Path to nsd-control 
my $nsdcontrol = '/usr/local/sbin/nsd-control';

# This Root NS Unicast (secondary) IP (where NSD listens to for AXFR requests)
my $rootns = '10.1.1.211'; 

# TMP Folder where nsd_superslave writes incoming zones (without trailing slash)
my $depot = '/data/tmp/zones';


my $res = Net::DNS::Resolver->new;

# Perform a zone transfer from the local slave server
$res->nameservers($rootns);

my @zone = $res->axfr($metazone);

foreach my $rr (@zone) {
    # Skip records we know can't be meant for us
    next unless $rr->type eq 'TXT';
    next unless $rr->name =~ /\.${metazone}$/;

    # Strip meta zone name from origin
    my $name = $rr->name;
    $name =~ s/\.${metazone}$//;

    # Perform sanity check on name
    # Skip if zone $name doesn't exist on this slave.

    # Remove zone from slave server:
    #   if BIND:
    #     issue [ rndc delzone ] 
    #   if NSD:
    #     issue [ nsd-control delzone ]
    #   if PowerDNS slave:
    #    delete from back-end database
    #   etc.
    
    # Determine if zone file on disk; if so, move it into
    #    backup area.
     
    if (defined($name)) {
        my $command = "${nsdcontrol} delzone ${name} ";
        system($command);
        my $command2 = "rm -rf ". $depot ."/". $name ;
        system($command2);

		if ($metazone ne $name){
        	# clean stub zone from BIND
        	system("/usr/local/bin/bind_del_stub.sh ".$name );
		}

    }


    print $name, "\n";
}
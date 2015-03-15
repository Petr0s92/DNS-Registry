#!/usr/bin/perl

use strict;
use warnings;
use Net::DNS::Nameserver;


# Metazone name. Leave default unless you use another name for the metazone.
my $metazone = 'meta.meta';  

# Full Path to nsd-control 
my $nsdcontrol = '/usr/local/sbin/nsd-control'; 

# This Root NS Unicast (secondary) IP (where NSD listens to for AXFR requests)
my $rootns = '10.1.1.211'; 

# TMP Folder to save received zone NOTIFIES (make sure the folder exists) (without trailing slash) 
my $depot = '/data/tmp/zones'; 

# TMP Folder to save received zone NOTIFIES (make sure the folder exists) (without trailing slash) 
my $cleanupdb = '/data/tmp/deleted_zones.txt'; 


sub notify_handler {
    my ($qname, $qclass, $qtype, $peerhost,$query,$conn) = @_;
    my ($rcode, @ans, @auth, @add, $path);

    $path = $depot."/".$qname;
    $rcode = undef;

    print "Received NOTIFY query from $peerhost for " . $qname ."\n";

    # Slight sanity check (don't accept slashes)
    # FIXME: lowercase and do more checks.

    $rcode = 'SERVFAIL' if ($qname =~ /\//);

    # Check whether the zone already exists on this slave. Implemented here
    # as a file on a filesystem. What I'd probably do is use Redis (SET)
    # or SQLite3, whereby the former could be used to 'report back' to a
    # monitoring station via PUB/SUB, etc.

    # Actually we can check existence of the zone on the file system 
    # instead of using a separate database...

    # As Marc suggests, we can also simply query the paired DNS server (i.e.
    # the server this script is catering to) using Net::DNS to see if the zone
    # has been defined.

    $rcode = 'NOERROR'  if (-f $path);

    if (defined($rcode)) {
        return ($rcode, [], [], [],
                { aa => 1, opcode => 'NS_NOTIFY_OP'} );
    }

    # NSD: addzone ${qname} groupname
    # BIND: rndc addzone ${qname} IN '......;'

#    my $command = "rndc addzone ${qname} in '{type slave; file \"${qname}\"; masters { 172.16.153.102;};};'";
    my $command = "${nsdcontrol} addzone ${qname} superslave &";
    
    
    if (open(DB, "> $path")) {
        print DB $command, "\n";
        close(DB);
    }
    
	# Clear cleanup script db in case the domain was previously deleted
    open( FILE, "<$cleanupdb" ); 
    my @LINES = <FILE>; 
    close( FILE ); 
    open( FILE, ">$cleanupdb" ); 
    foreach my $LINE ( @LINES ) { 
        print FILE $LINE unless ( $LINE =~ m/$qname/ ); 
    } 
    close( FILE );   
    

    # FIXME: too "heavy". Ensure non-blocking, maybe as a kind of queue?
    # FIXME: maybe unreliable, but attempt to obtain return code of 'addzone'
    #        and maybe SERVFAIL (not that it's of any use)

    system($command);
    
    if ($metazone ne $qname){
		# also add each new zone to BIND caching NS as Stub Zone
    	system("/usr/local/bin/bind_add_stub.sh ${qname} ${rootns} &");
	}    

    
    $rcode = "NOERROR";
    return ($rcode, [], [], [],
            { aa => 1, opcode => 'NS_NOTIFY_OP'} );
}

sub reply_handler {
    my ($qname, $qclass, $qtype, $peerhost,$query,$conn) = @_;
    my (@ans, @auth, @add);

    return ('SERVFAIL', \@ans, \@auth, \@add);
}

my $ns = Net::DNS::Nameserver->new(
    LocalAddr     => [$rootns],
    LocalPort     => 5353,
    ReplyHandler  => \&reply_handler,
    NotifyHandler => \&notify_handler,
    Verbose       => 0,
    Debug         => 0,
) || die "couldn't create nameserver object\n";

$ns->main_loop;
<?php

//require("phpdns-1.01/dns.inc.php");

//$query=new DNSQuery($_GET['server'],53,1000,1,0);
//$result=$query->Query($_GET['domain'],'NS', true);
//$result2=$query->Query($_GET['domain'],'NS', false);

//echo $result."\n";

require_once 'Net/DNS.php';

$resolver = new Net_DNS_Resolver();

$resolver->nameservers = array(              // Set the IP addresses
                           $_GET['ns']     // of the nameservers
                           );
                           
$resolver->debug = 0; // Turn on debugging output to show the query
$resolver->usevc = 0; // Force the use of TCP instead of UDP
$resolver->port = 53; // DNS Server port
$resolver->recurse = 1; // Disable recursion
$resolver->retry = 1; // How long to wait for answer
$resolver->retrans = 2; // How many times to retry for answer

                           
$response = $resolver->rawQuery($_GET['domain'], 'SOA');

$aa = $response->header->aa;
$qr = $response->header->qr;
$tc = $response->header->tc;
$rd = $response->header->rd;
$ra = $response->header->ra;

echo "<pre>\n";
if ($aa == 1 && $qr == 1 && $tc == 0 ){
	
	echo "\nDOMAIN ".$_GET['domain']." LOOKS VALID\n\n";
	
}else{
	echo "\nDOMAIN ".$_GET['domain']." DOES NOT LOOK VALID\n\n";
	
}

if ($_GET['debug']){

echo "--------------------------------------------------------------\n";
echo "RAW DNS FLAGS\n\n";	
echo "Is Authoritative: aa = $aa\n";
echo "Question=0 Response=1: qr = $qr\n";
echo "Reply is Truncated: tc = $tc\n";
echo "No Recursion requested: tc = $rd\n";
echo "Recursion available: tc = $ra\n";
echo "--------------------------------------------------------------\n\n";
}


if ($_GET['debug'] == '1'){

echo "\n\n--------------------------------------------------------------\n";
echo "RAW REQUEST/RESPONSE DATA\n";	
print_r($response);

if (! $response) {
  echo "\n";
  echo "ANCOUNT is 0, therefore the query() 'failed'\n";
  echo "See Net_DNS_Resolver::rawQuery() to receive this packet\n";
}
echo "--------------------------------------------------------------\n";

}
echo "</pre>\n";


?>

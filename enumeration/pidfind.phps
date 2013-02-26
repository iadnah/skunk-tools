<?php
/*
(c) 2007 Iadnah

simple code to search for hidden processes

Basically, tries to run through all valid pid numbers on a linux
system and then checks if they exist in proc. If they do not (like if
hidden by a rootkit) then this spits out the pid of the hidden process.

*/

if (!function_exists('posix_kill') || !function_exists('posix_access')) {
	die("ERROR: Your php installation does not support the posix family of functions.\n");
}

if (posix_access('/proc/sys/kernel/pid_max', POSIX_R_OK)) {
	$maxpid = (int)file_get_contents('/proc/sys/kernel/pid_max');
} else {
	$maxpid = 32768; //reasonable default
}

echo "Max pid: $maxpid\n";
for ($x = 1; $x < $maxpid; $x++) {
	if (posix_kill($x,0)) {
		if (!is_dir('/proc/'. $x)) {
			echo $x. "\n";
		}
	}
}

?>

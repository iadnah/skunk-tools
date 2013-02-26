#!/usr/bin/env python
# passgen.py
# iadnah@iadnah.net
#
# Generates all possible combinations of characters in the given keyspace
# and outputs to stdout
#
# keyspace = [32,33,36,42,43] + range(48, 58) + range(65, 91) + range(97, 123)

import sys
import getopt

lowercase = range(97, 122)
uppercase = range(65, 90)
numeric = range(48, 57)
symbols = range(32, 47) + range(58, 64) + range(91, 96) + range(123, 126)

def recurse(width, pos, baseString):
	for char in keyspace:
		if (pos < width - 1):
			recurse(width, pos + 1, baseString + "%c" % char)
			
		print baseString + "%c" % char

def usage():
	print """
	Passgen.py -- iadnah@iadnah.net 2011
	Usage: ./passgen.py -m <length> -k <keyspaces>

	-m	Sets the max length of passwords to generate
	-k	Selects one of the specified keyspaces below:
		lalpha	- all lowercase latters
		ualpha	- all uppercase letters
		num	- all numbers
		symbols	- include symbols
		
		-k can be specified multiple times
		
	-h	Help. You're looking at it
	
	Example: ./passgen.py -m 5 -k lalpha -k num
		- will generate 5 character passwords composted of
		  letters and numbers
	"""

try:
	opts, args = getopt.getopt(sys.argv[1:], "k:m:h")
except getopt.GetoptError, err:
	usage()
	exit()	

min = 0

keyspace = []

for opt, arg in opts:
	if opt == "-m":
		min = int(arg)
	elif opt == "-k":
		if arg == "num":
			keyspace += numeric
		elif arg == "lalpha":
			keyspace += lowercase
		elif arg == "ualpha":
			keyspace += uppercase
		elif arg == "symbols":
			keyspace += symbols
		else:
			print "Error!"
	else:
		usage()
		exit()
else:
	if min == 0 or len(keyspace) < 1:
		usage()
		exit()

recurse(min, 0, "")

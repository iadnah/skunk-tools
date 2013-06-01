#!/bin/bash
# sshkey_crack.sh 
# By iadnah@uplinklounge.com
# Last Edit: June 1, 2013
#
# Very basic dictionary cracker for ssh private key files
# currently supports only RSA keys
#
# Usage:
#  Edit WORDLIST below to point to a good word list
#  ./sshkey_cracker.sh <private key file>

OPENSSL=`which openssl`
KEYFILE=$1
WORDLIST="test.dic"


exec 5<> "${WORDLIST}"

while [ 1 ]; do
	read -u 5 PASS

	if [ "${PASS}x" = "x" ]; then
		LOOP=1
		break
	fi

	result=$(${OPENSSL} rsa -text -in "${KEYFILE}" -passin "pass:${PASS}" 2>&1)
	if [ $? = 0 ]; then
		echo "SUCCESS: $PASS"
		break
	fi

done

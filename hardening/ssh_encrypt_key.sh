#!/bin/bash
# ssh_encrypt_key.sh
# By: iadnah@uplinklounge.com
# Last Edit: June 5, 2013
#
# WARNING: Keys encoded this way may not work with ssh clients
#	other than openssh !!!
#
# openssh private keys are encrypted using AES with a MD5 digest
# which are relatively quick to compute
#
# This tool re-encodes a private key file using PKCS#8
#  (see:
#   http://tools.ietf.org/html/rfc2898#section-5.2
#   http://en.wikipedia.org/wiki/PBKDF2
#   http://tools.ietf.org/html/rfc5208
#  )
#
# This generates a new id_rsa encoded with PCKCS#8 and a more
# CPU-intensive crypto algorithm. This works transparently
# because openssh uses openssl
#
# For more information see http://martin.kleppmann.com/2013/05/24/improving-security-of-ssh-private-keys.html

usage() {
cat <<EOT
ssh_encrypt_key by iadnah@uplinklounge (06/01/2013)

Standard openssh private keys are encrypted with AES-128 using an MD5 digest,
both of which are relatively fast to compute. This tool generates a new private
key file using PKCS#8 and more CPU-intensive algorithms. OpenSSH can transparently
read the new (PKCS#8 encoded) keyfile because it uses openssl as a backend.

For more info read the source or visit:
http://martin.kleppmann.com/2013/05/24/improving-security-of-ssh-private-keys.html


Usage: $0 <original private key> <new private key>
 - Edit this script to change the algorithm used. des3 is used by default.
 - Replace your old private key file with the new one (make sure to test first)

EOT

}


OPENSSL=`which openssl`

# What algorithm to use. Any algorithm supported by openssl should work
# You may want to run openssl speed <algo1 algo2 algo3 ...> and choose the SLOWEST
ALGO='des3'

OLDKEY=$1
NEWKEY=$2

if [ "${OLDKEY}x" = "x" ] || [ "${NEWKEY}x" = "x" ]; then
	usage
	exit
fi


touch "${NEWKEY}"
chmod 0600 "${NEWKEY}"

${OPENSSL} pkcs8 -topk8 -v2 ${ALGO} -in "${OLDKEY}" -out "${NEWKEY}"
if [ $? = 0 ]; then
	echo "New private key written to ${NEWKEY}. Replace ${OLDKEY} with ${NEWKEY} after testing that it works (ssh -identity ${NEWKEY})"
else
	echo "ERROR: Something went wrong. Maybe you should learn how to type..."
	usage
	exit
fi

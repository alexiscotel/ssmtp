#!/bin/sh
apt-get install -y ssmtp

# edit ssmtp.conf with file ssmtp/ssmtp.conf

# on host machine (VPS)
	## add iptables rule (TODO: change ip to match with all docker containers)
	iptables -A DOCKER -s 172.0.0.0/8 -p tcp --dport 25 -j ACCEPT

# in container
	## change www-data real name
	chfn -f "header mail Name" www-data
	
	## test command
	echo "content of mail from container 1" | mail -s "subject of mail from container 1" [mailAddress]
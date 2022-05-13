# PHP + Apache + sSMTP Docker config (with on-board Traefik config)

ssmtp configuration for docker environment. Worked with pre-configured [traefik template](https://github.com/alexiscotel/traefik)

## :warning: Warning
This `ssmtp` config only works on containers mounted from a remote server where `postfix` (or other MTA) has been configured.

This means that if you try to run it on a local machine, `ssmtp` exit with a non-zero status

## Summary
1. [Configuration](#globalConfig)
    1. [On host machine (VPS)](#hostConfig)
    1. [Inside project (ssmtp configuration)](#ssmtpConfig)
        1. [ssmtp.conf](#ssmtpConf)
        1. [revaliases](#revaliases)
1. [Tests](#tests)
1. [Troubles](#troubles)

  
## <a name="globalConfig">Configuration</a>

### <a name="hostConfig">On host machine (VPS)</a>

* get docker0 host interface ip
```
ifconfig docker0
docker0: flags=4099<UP,BROADCAST,MULTICAST>  mtu 1500
        inet 172.17.0.1  netmask 255.255.0.0  broadcast 172.17.255.255
        ...
```
* check how docker generate ip for containers
```
iptables -L
Chain DOCKER (x references)
target     prot opt source               destination
ACCEPT     tcp  --  anywhere             172.19.0.2           tcp dpt:http-alt
ACCEPT     tcp  --  anywhere             172.20.0.2           tcp dpt:http
...
```
* add iptables rules
```
iptables -A DOCKER -s 172.0.0.0/8 -p tcp --dport 25 -j ACCEPT
```
* Be sure of your `postfix` <a name="postfixConfig">configuration</a> (`/etc/postfix/main.cf`), ...
```
myhostname = [VPS-COMPLETE-NAME]
mydestination = $myhostname, [VPS-COMPLETE-NAME], localhost.[VPS-NAME], , localhost
inet_interfaces = all // TODO: check if it can be replaced by loopback-only
```
... then add to `mynetworks` line the docker's ip mask 
```
mynetworks = ..., 172.0.0.0/8
```


### <a name="ssmtpConfig">Inside project (ssmtp configuration)</a>

* Create a copy of `php/ssmtp/ssmtp.conf` with name `php/ssmtp/ssmtp.conf.local` (`docker-compose.yml` reference).
* Do the same for the `revaliases` file (`docker-compose.yml` reference)

#### <a name="ssmtpConf">Inside `php/ssmtp/ssmtp.conf.local`</a>

change `[docker0-IP]`, `[DOMAIN.EXT]` and `[VPS-NAME]`
```
mailhub=[docker0-IP]
rewriteDomain=[DOMAIN.EXT]
hostname=[VPS-COMPLETE-NAME]
```
Example
```
mailhub=172.17.0.1
rewriteDomain=toto.net
hostname=[vps-id].vps.ovh.net // replace [vps-id] by your own
```


#### <a name="revaliases">Inside `php/ssmtp/revaliases`</a>

* change `[DOMAIN.EXT]` according with `rewriteDomain` for user `www-data`, and choose the name used for the service name of email address (`[SERVICE-NAME]`)
```
www-data:[SERVICE-NAME]@[DOMAIN.EXT]
```
Example
```
www-data:noreply@toto.net
```


* according to this, change the real name of user `www-data` :
```
chfn -f "John Doe" www-data
```
but it's already set in `Dockerfile`
```
RUN chfn -f "John Doe" www-data
```
to get the actual real name, use the command 
```
getent passwd "www-data" | cut -d ':' -f 5 // => John Doe,,,
```

## <a name="tests">Tests</a>

* First of all, to see if the mail is correctly transmitted to the host machine, run in another remote server terminal
```
tail -F /var/log/mail.log
```
* After editing files (there is no default config, do the previous steps first !), start with
```
docker-compose -f docker-compose.yml up -d
```
* access the newly created container
```
docker exec -it www_php_ssmtp bash
```
* inside container, test sending mail with this command (of course, replace `[mailAddress]` by a email address that you can access). To see the SERVICE-NAME change with this command, don't forget to add line for root in container `/etc/ssmtp/revaliases`
```
echo "content of mail from container" | mail -s "subject of mail from container" [mailAddress]
```
Example
```
echo "content of mail from container" | mail -s "subject of mail from container" fake@mail.net
```
you'll see a first output like this
```
May 13 12:53:02 [vps-id] postfix/smtpd[824438]: connect from unknown[172.20.0.2]
May 13 12:53:02 [vps-id] postfix/smtpd[824438]: D6C0841A8E: client=unknown[172.20.0.2]
May 13 12:53:03 [vps-id] postfix/cleanup[824441]: D6C0841A8E: message-id=<>
May 13 12:53:03 [vps-id] postfix/qmgr[814242]: D6C0841A8E: from=<noreply@toto.net>, size=438, nrcpt=1 (queue active)
May 13 12:53:03 [vps-id] postfix/smtpd[824438]: disconnect from unknown[172.20.0.2] helo=1 mail=1 rcpt=1 data=1 quit=1 commands=5
```
if the mail is correctly transmitted, you'll see a second outlike like this
```
May 13 12:53:09 [vps-id] postfix/smtp[824442]: D6C0841A8E: to=<fake@mail.net>, relay=xx.mail.xxx.net[xxx.xxx.xxx.xxx]:25, delay=6.3, delays=1/0/5.2/0.13, dsn=2.0.0, status=sent (250 2.0.0 Ok: queued as 4L07q90jqFz1fqnrw)
May 13 12:53:09 [vps-id] postfix/qmgr[814242]: D6C0841A8E: removed
```
where `xx.mail.xxx.net[xxx.xxx.xxx.xxx]:25` is the remote server relay and his IP address


## <a name="troubles">Troubles</a>
if there trouble to transfert mail, check :
* the output log : `/var/log/mail.log`
* your `ssmtp` [configuration](#ssmtpConfig)
* your `postfix` [configuration](#postfixConfig)

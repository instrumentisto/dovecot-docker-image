<?php
$var = getopt('', ['dockerfile:']);
$isAlpineImage = (end(explode('/', $var['dockerfile'])) === 'alpine');

$DovecotVer = '2.2.27';
$DovecotSha512Sum = 'faab441bb2afa1e6de3e6ec6207c92a333773941bbc10c4761483ef6ccc193d3a4983de1acc73325122c22b197ea25c1e54886cccfb6b060ede90936a69b71f2';
$AlpineRepoCommit = '5dac5caa8e9bba5534fd9d4010ddc8955eddb194';
$DebianRepoCommit = 'fc8f5ddc49b39ee56eb57a082ee34dbd58a30c1d';
?>
# AUTOMATICALLY GENERATED
# DO NOT EDIT THIS FILE DIRECTLY, USE /Dockerfile.tmpl.php

<? if ($isAlpineImage) { ?>
# https://hub.docker.com/_/alpine
FROM alpine:3.6
<? } else { ?>
# https://hub.docker.com/_/debian
FROM debian:stretch-slim
<? } ?>

MAINTAINER Instrumentisto Team <developer@instrumentisto.com>


# Build and install Dovecot
<? if ($isAlpineImage) { ?>
# https://git.alpinelinux.org/cgit/aports/tree/main/dovecot/APKBUILD?h=<?= $AlpineRepoCommit."\n"; ?>
RUN apk update \
 && apk upgrade \
 && apk add --no-cache \
        ca-certificates \
<? } else { ?>
# https://anonscm.debian.org/git/collab-maint/dovecot.git/tree/debian/rules?id=<?= $DebianRepoCommit."\n"; ?>
RUN apt-get update \
 && apt-get upgrade -y \
 && apt-get install -y --no-install-recommends --no-install-suggests \
            ca-certificates \
<? } ?>
 && update-ca-certificates \

 # Install Dovecot dependencies
<? if ($isAlpineImage) { ?>
 && apk add --no-cache \
        libressl libressl2.5-libcrypto libressl2.5-libssl \
        libbz2 lz4-libs xz-libs zlib \
        libcap \
        libpq mariadb-client-libs sqlite-libs \
        libldap \
        heimdal-libs \
<? } else { ?>
 && apt-get install -y --no-install-recommends --no-install-suggests \
            libssl1.1 \
            libbz2-1.0 liblz4-1 liblzma5 zlib1g \
            libcap2 \
            libpq5 libmariadbclient18 libsqlite3-0 \
            libldap-2.4 \
            libgssapi-krb5-2 libk5crypto3 libkrb5-3 \
<? } ?>

 # Install tools for building
<? if ($isAlpineImage) { ?>
 && apk add --no-cache --virtual .tool-deps \
        curl coreutils autoconf g++ libtool make \
<? } else { ?>
 && toolDeps=" \
        curl make gcc g++ libc-dev \
    " \
 && apt-get install -y --no-install-recommends --no-install-suggests \
            $toolDeps \
<? } ?>

 # Install Dovecot build dependencies
<? if ($isAlpineImage) { ?>
 && apk add --no-cache --virtual .build-deps \
        libressl-dev \
        bzip2-dev lz4-dev xz-dev zlib-dev \
        libcap-dev \
        postgresql-dev mariadb-dev sqlite-dev \
        openldap-dev \
        heimdal-dev \
        linux-headers \
<? } else { ?>
 && buildDeps=" \
        libssl-dev \
        libbz2-dev liblz4-dev liblzma-dev zlib1g-dev \
        libcap-dev \
        libpq-dev libmariadbclient-dev-compat libsqlite3-dev \
        libldap2-dev \
        krb5-multidev \
    " \
 && apt-get install -y --no-install-recommends --no-install-suggests \
            $buildDeps \
<? } ?>

 # Download and prepare Dovecot sources
 && curl -fL -o /tmp/dovecot.tar.gz \
         https://www.dovecot.org/releases/<?= implode('.', array_slice(explode('.', $DovecotVer), 0, 2)); ?>/dovecot-<?= $DovecotVer; ?>.tar.gz \
 && (echo "<?= $DovecotSha512Sum; ?>  /tmp/dovecot.tar.gz" \
         | sha512sum -c -) \
 && tar -xzf /tmp/dovecot.tar.gz -C /tmp/ \
 && cd /tmp/dovecot-* \
<? if ($isAlpineImage) { ?>
 && curl -fL -o ./libressl.patch \
         https://git.alpinelinux.org/cgit/aports/plain/main/dovecot/libressl.patch?h=<?= $AlpineRepoCommit; ?> \
 && patch -p1 -i ./libressl.patch \
<? } ?>

 # Build Dovecot from sources
<? if ($isAlpineImage) { ?>
 && ./configure \
<? } else { ?>
 && KRB5CONFIG=krb5-config.mit \
    ./configure \
<? } ?>
        --prefix=/usr \
        --with-ssl=openssl --with-ssldir=/etc/ssl/dovecot \
        --with-lz4 --with-lzma \
        --with-libcap \
        --with-sql=plugin --with-pgsql --with-mysql --with-sqlite \
        --with-ldap=plugin \
        --with-gssapi=plugin \
        --with-rundir=/run/dovecot \
        --localstatedir=/var \
        --sysconfdir=/etc \
        # No documentation included to keep image size smaller
        --mandir=/tmp/man \
        --docdir=/tmp/doc \
        --infodir=/tmp/info \
 && make \

 # Create Dovecot user and groups
<? if ($isAlpineImage) { ?>
 && addgroup -S -g 91 dovecot \
 && adduser -S -u 90 -D -s /sbin/nologin \
            -H -h /dev/null \
            -G dovecot -g dovecot \
            dovecot \
 && addgroup -S -g 93 dovenull \
 && adduser -S -u 92 -D -s /sbin/nologin \
            -H -h /dev/null \
            -G dovenull -g dovenull \
            dovenull \
<? } else { ?>
 && addgroup --system --gid 91 dovecot \
 && adduser --system --uid 90 --disabled-password --shell /sbin/nologin \
            --no-create-home --home /dev/null \
            --ingroup dovecot --gecos dovecot \
            dovecot \
 && addgroup --system --gid 93 dovenull \
 && adduser --system --uid 92 --disabled-password --shell /sbin/nologin \
            --no-create-home --home /dev/null \
            --ingroup dovenull --gecos dovenull \
            dovenull \
<? } ?>

 # Install and configure Dovecot
 && make install \
 && rm -rf /etc/dovecot/* \
 && mv /tmp/doc/example-config/dovecot* \
       /tmp/doc/example-config/conf.d \
       /tmp/doc/dovecot-openssl.cnf \
       /etc/dovecot/ \
 # Set logging to STDOUT/STDERR
 && sed -i -e 's,#log_path = syslog,log_path = /dev/stderr,' \
           -e 's,#info_log_path =,info_log_path = /dev/stdout,' \
           -e 's,#debug_log_path =,debug_log_path = /dev/stdout,' \
        /etc/dovecot/conf.d/10-logging.conf \
 # Set default passdb to passwd and create appropriate 'users' file
 && sed -i -e 's,!include auth-system.conf.ext,!include auth-passwdfile.conf.ext,' \
           -e 's,#!include auth-passwdfile.conf.ext,#!include auth-system.conf.ext,' \
        /etc/dovecot/conf.d/10-auth.conf \
 && install -m 640 -o dovecot -g mail /dev/null \
            /etc/dovecot/users \
 # Change TLS/SSL dirs in default config and generate default certs
 && sed -i -e 's,^ssl_cert =.*,ssl_cert = </etc/ssl/dovecot/server.pem,' \
           -e 's,^ssl_key =.*,ssl_key = </etc/ssl/dovecot/server.key,' \
        /etc/dovecot/conf.d/10-ssl.conf \
 && install -d /etc/ssl/dovecot \
 && openssl req -new -x509 -nodes -days 365 \
                -config /etc/dovecot/dovecot-openssl.cnf \
                -out /etc/ssl/dovecot/server.pem \
                -keyout /etc/ssl/dovecot/server.key \
 && chmod 0600 /etc/ssl/dovecot/server.key \
 # Tweak TLS/SSL settings to achieve A grade
 && sed -i -e 's,^#ssl_protocols =.*,ssl_protocols = !SSLv2 !SSLv3,' \
           -e 's,^#ssl_cipher_list =.*,ssl_cipher_list = ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA:ECDHE-RSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-RSA-AES256-SHA256:DHE-RSA-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:AES128-SHA256:AES256-SHA256:AES128-SHA:AES256-SHA:!DSS,' \
           -e 's,^#ssl_prefer_server_ciphers =.*,ssl_prefer_server_ciphers = yes,' \
           -e 's,^#ssl_dh_parameters_length =.*,ssl_dh_parameters_length = 2048,' \
        /etc/dovecot/conf.d/10-ssl.conf \

 # Cleanup unnecessary stuff
<? if ($isAlpineImage) { ?>
 && apk del .tool-deps .build-deps \
 && rm -rf /var/cache/apk/* \
<? } else { ?>
 && apt-get purge -y --auto-remove \
                  -o APT::AutoRemove::RecommendsImportant=false \
            $toolDeps $buildDeps \
 && rm -rf /var/lib/apt/lists/* \
<? } ?>
           /tmp/*


EXPOSE 110 143 993 995

CMD ["/usr/sbin/dovecot", "-F"]

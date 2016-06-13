#!/bin/sh

export ORACLE_HOME=/u01/app/oracle/product/11.2.0/xe/
export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:$ORACLE_HOME/lib:$ORACLE_HOME/network/lib
export TERM=dumb

php /var/www/html/owncloud/console.php files:scan pointec > /dev/null

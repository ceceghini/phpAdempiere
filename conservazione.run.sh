#!/bin/sh

export ORACLE_HOME=/u01/app/oracle/product/11.2.0/xe/
export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:$ORACLE_HOME/lib:$ORACLE_HOME/network/lib
export GS_LIB=/usr/share/fonts

php ./Conservazione.php

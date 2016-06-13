#!/bin/sh

# Cambio proprietario dei file
find /opt/owncloud/pointec/files -user root -exec chown apache.apache {} +

# Scand e reindicizzazione
sudo -u apache /opt/phpAdempiere/script/scanfile.sh

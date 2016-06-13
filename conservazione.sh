#!/bin/sh

sudo -u apache ./conservazione.run.sh

/opt/phpAdempiere/script/finalize_file.sh

chmod 777 /opt/owncloud/pointec/files/conservazione

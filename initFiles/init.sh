#!/bin/bash
sed -i -e "s/+WAZO-FAX-USER+/$WAZO_FAX_USER/g" /var/www/html/index.php
sed -i -e "s/+WAZO-FAX-NAME+/$WAZO_FAX_NAME/g" /var/www/html/index.php
sed -i -e "s/+WAZO-FAX-PASSWD+/$WAZO_FAX_PASSWD/g" /var/www/html/index.php
sed -i -e "s/+CALLER-ID+/$CALLER_ID/g" /var/www/html/index.php
/etc/init.d/php7.3-fpm start
nginx -g 'daemon off;'

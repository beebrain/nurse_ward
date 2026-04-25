#!/bin/bash
echo " Starting Migration...\
docker exec php-fpm php /var/www/html/nurse_ward/spark migrate
if [ \True -eq 0 ]; then echo \SUCCESS\; else echo \FAILED\; exit 1; fi

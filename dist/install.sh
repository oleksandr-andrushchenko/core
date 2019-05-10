#!/usr/bin/env bash

# Before run:
# 1) echo '{
#  "name": "snowgirl-app",
#  "autoload": {
#    "psr-4": {
#      "APP\\": "src"
#    }
#  },
#  "authors": [
#    {
#      "name": "alex.snowgirl",
#      "email": "alex.snowgirl@gmail.com"
#    }
#  ],
#  "prefer-stable": true,
#  "minimum-stability": "dev",
#  "require": {
#    "php": "^7.2",
#    "snowgirl/core": "dev-master"
#  }
#}' >> ./composer.json

# 2) composer update

# 3) cp ./dist/install.sh ./install.sh

# 4) sh dist/install.sh

echo ""
echo "...structure"
echo ""

mkdir src
mkdir css
echo "/*xs*/
/*mb*/
@media (min-width: 480px) {}
/*sm*/
@media (min-width: 768px) {}
/*md*/
@media (min-width: 992px) {}
/*lg*/
@media (min-width: 1200px) {}" > ./css/core.css
mkdir js
echo "" > ./css/core.js
mkdir locale
mkdir view

mkdir var
mkdir var/tmp
mkdir var/log
mkdir var/cache
echo "" > ./var/log/access.log
echo "" > ./var/log/admin.log
echo "" > ./var/log/hit.page.log
echo "" > ./var/log/open-door.log
echo "" > ./var/log/server.log
echo "" > ./var/log/web.log

mkdir public
cp ./vendor/snowgirl/core/dist/index.php ./public/index.php

mkdir public/css
ln -s ../../vendor/snowgirl/core/css public/css/core
ln -s ../css public/css/app

mkdir public/js
ln -s ../../vendor/snowgirl/core/js public/js/core
ln -s ../js public/js/app

mkdir -p public/img/0/0

mkdir bin
cp ./vendor/snowgirl/core/dist/console.php ./bin/console
cp ./vendor/snowgirl/core/dist/config.ini ./config.ini
echo "" > ./info.txt


echo ""
echo "...config"
echo ""

read -p 'site: ' site
perl -pi -w -e "s/{site}/${site}/g;" ./config.ini

read -p 'domain: ' domain
perl -pi -w -e "s/{domain}/${domain}/g;" ./config.ini

read -p 'memcache_prefix: ' memcache_prefix
perl -pi -w -e "s/{memcache_prefix}/${memcache_prefix}/g;" ./config.ini

read -p 'elastic_prefix: ' elastic_prefix
perl -pi -w -e "s/{elastic_prefix}/${elastic_prefix}/g;" ./config.ini

echo ""
echo "...database"
echo ""

read -p 'db_root_user: ' db_root_user
read -p 'db_root_pass: ' db_root_pass

echo 'db_schema: '$domain
echo ""
db_schema=$domain
perl -pi -w -e "s/{db_schema}/${db_schema}/g;" ./config.ini
echo 'db_user: '$domain
echo ""
db_user=$domain
perl -pi -w -e "s/{db_user}/${db_user}/g;" ./config.ini
read -p 'db_pass: ' db_pass
perl -pi -w -e "s/{db_pass}/${db_pass}/g;" ./config.ini

query="CREATE DATABASE \`${db_schema}\`;"
echo "..."$query
mysql -u${db_root_user} -p${db_root_pass} -e "${query}"

query="CREATE USER \`${db_user}\`@localhost IDENTIFIED BY '${db_pass}';"
echo "..."$query
mysql -u${db_root_user} -p${db_root_pass} -e "${query}"

query="GRANT ALL PRIVILEGES ON \`${db_schema}\`.* TO \`${db_user}\`@'localhost';"
echo "..."$query
mysql -u${db_root_user} -p${db_root_pass} -e "${query}"

query="FLUSH PRIVILEGES;"
echo "..."$query
mysql -u${db_root_user} -p${db_root_pass} -e "${query}"

mysql -u${db_root_user} -p${db_root_pass} ${db_schema} < ./dist/dump.sql

sudo chown -R www-data:www-data ./ && sudo chmod -R g+w ./
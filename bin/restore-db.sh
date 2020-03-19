#!/usr/bin/env bash

file=$1
if [ -z "$file" ]; then
    echo "USAGE: restore-db <filename>"
    exit 1;
fi

# Restore database to db container
cmd='exec mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"'
#docker exec -i $(docker-compose ps -q db) sh -c "$cmd" < $file
docker exec -i woocommerce_db_1 sh -c "$cmd" < $file

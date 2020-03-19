#!/usr/bin/env bash

file=$1
if [ -z "$file" ]; then
    echo "USAGE: restore-db <filename>"
    exit 1;
fi

# Restore database to db container
cmd='exec mysql -udb_user -pdb_pass woo_db'
docker exec -i $(docker-compose ps -q db) sh -c "$cmd" < $file
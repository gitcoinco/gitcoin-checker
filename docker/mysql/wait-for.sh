#!/bin/bash

set -e

host="$1"
shift
cmd="$@"

until mysql -h"$host" -P3306 -uroot -ppassword -e 'SELECT 1'; do
  >&2 echo "MySQL is unavailable - sleeping"
  sleep 5
done

>&2 echo "MySQL is up - executing command"
exec $cmd

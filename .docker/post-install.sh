#!/bin/sh
set -e

# deployment
echo "Deployement stage..."
composer app:deploy

# start process
echo "start main process : $@"
exec "$@"

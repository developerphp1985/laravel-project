#!/bin/bash 
ENV=dev
if [ $# -eq 1 ]
then
    ENV=$1
fi
NAME=ico-web-${ENV}

IMAGE=${NAME}
echo -n "Stopping docker image ${NAME} ... "
docker stop ${NAME}  2>&1 > /dev/null && docker rm ${NAME}  2>&1 > /dev/null
echo "Done"


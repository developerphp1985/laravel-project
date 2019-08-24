#!/bin/bash 
ENV=dev
if [ $# -eq 1 ]
then
    ENV=$1
fi

NAME=ico-web-${ENV}
echo "Launching docker image ${NAME} ..."

IMAGE=${NAME}
OPTIONS="-p 80:8181 "

docker stop ${NAME}  2>&1 > /dev/null
docker rm ${NAME}  2>&1 > /dev/null

docker run -d -it --name ${NAME} \
	${OPTIONS} \
	${IMAGE}

docker ps -a

docker logs -f $(docker ps | grep ${IMAGE} | awk '{print $1}')
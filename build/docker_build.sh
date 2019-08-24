#!/bin/bash 
ENV=dev
if [ $# -ge 1 ]
then
    ENV=$1
fi

echo "$ENV Environment Selected"

if [ -e build/${ENV}/.env ]
then
    echo "Image Creation start"

    docker build --build-arg ENV_FILE=build/${ENV}/.env -t ico-web-${ENV} .

    docker tag ico-web-${ENV}:latest 757662348773.dkr.ecr.eu-west-2.amazonaws.com/ico-web-${ENV}:latest

    bash -c 'eval $(aws ecr get-login --no-include-email --region eu-west-2)'

    docker push 757662348773.dkr.ecr.eu-west-2.amazonaws.com/ico-web-${ENV}:latest

    echo "Image Creation Complete"

else
    echo "Missing env file in ${ENV}/ folder"
fi


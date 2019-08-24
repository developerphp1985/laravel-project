#!/usr/bin/env bash

if [ -e build/prod/.env ]

then
    echo "Deploy start";

    docker build --build-arg ENV_FILE=build/prod/.env -t ico-web-prod .

    docker tag ico-web-prod:latest 757662348773.dkr.ecr.eu-west-2.amazonaws.com/ico-web-prod:latest

    bash -c 'eval $(aws ecr get-login --no-include-email --region eu-west-2)'

    docker push 757662348773.dkr.ecr.eu-west-2.amazonaws.com/ico-web-prod:latest

    echo "Deploy Complete";

else
    echo "Missing env file in prod/ folder"
fi
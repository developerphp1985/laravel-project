#!/usr/bin/env bash

if [ -e buld/dev/.env ]
then
    echo "Deploy start";

    docker build -t lendo-dev .

    docker cp  build/prod/.env lendo-dev:/app

    docker tag lendo-dev:latest 757662348773.dkr.ecr.us-east-1.amazonaws.com/lendo-dev:latest

    bash -c 'eval $(aws ecr get-login --no-include-email --region us-east-1)'

    docker push 757662348773.dkr.ecr.us-east-1.amazonaws.com/lendo-dev:latest

    echo "Deploy Complete";
else
    echo "Missing env file in dev/ folder"
fi

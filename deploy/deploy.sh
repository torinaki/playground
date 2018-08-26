#!/usr/bin/env bash

set -o errexit
set -o pipefail
set -o nounset

export GIT_COMMIT_HASH=$(git log -1 --format="%H")
export TASK_FAMILY=phpstan-playground
export CLUSTER='arn:aws:ecs:eu-west-1:928192134594:cluster/phpstan-spot2'

export FPM_IMAGE_NAME=phpstan-playground-fpm
export NGINX_IMAGE_NAME=phpstan-playground-nginx
export FPM_DOCKER_TAG=928192134594.dkr.ecr.eu-west-1.amazonaws.com/${FPM_IMAGE_NAME}:${GIT_COMMIT_HASH}
export NGINX_DOCKER_TAG=928192134594.dkr.ecr.eu-west-1.amazonaws.com/${NGINX_IMAGE_NAME}:${GIT_COMMIT_HASH}

# ------------------------

echo Building FPM image:

docker build --build-arg GIT_COMMIT_HASH=${GIT_COMMIT_HASH} -t ${FPM_IMAGE_NAME}:${GIT_COMMIT_HASH} -f docker/fpm/Dockerfile .
docker tag ${FPM_IMAGE_NAME}:${GIT_COMMIT_HASH} ${FPM_IMAGE_NAME}:latest
docker tag ${FPM_IMAGE_NAME}:${GIT_COMMIT_HASH} ${FPM_DOCKER_TAG}

echo Building nginx image:

docker build -t ${NGINX_IMAGE_NAME}:${GIT_COMMIT_HASH} -f docker/nginx/Dockerfile .
docker tag ${NGINX_IMAGE_NAME}:${GIT_COMMIT_HASH} ${NGINX_IMAGE_NAME}:latest
docker tag ${NGINX_IMAGE_NAME}:${GIT_COMMIT_HASH} ${NGINX_DOCKER_TAG}

echo Pushing images:

$(aws ecr get-login --no-include-email --region eu-west-1)
docker push ${FPM_DOCKER_TAG}
docker push ${NGINX_DOCKER_TAG}

echo Updating task and cluster definitions:

CONTAINER_DEFINITIONS=$(php deploy/containerDefinitions.php)
TASK_REVISION=$(aws ecs register-task-definition --family ${TASK_FAMILY} --container-definitions ${CONTAINER_DEFINITIONS} --requires-compatibilities EC2 | php -r "echo json_decode(file_get_contents('php://stdin'))->taskDefinition->revision;")

aws ecs update-service --cluster ${CLUSTER} --service ${TASK_FAMILY} --task-definition ${TASK_FAMILY}:${TASK_REVISION} > /dev/null

echo Success! Deployed ${TASK_FAMILY}:${TASK_REVISION} \(${GIT_COMMIT_HASH}\)

#!/usr/bin/env bash

set -o errexit
set -o pipefail
set -o nounset

export GIT_COMMIT_HASH=$(git log -1 --format="%H")
export TASK_FAMILY_SERVICE=phpstan-playground
export TASK_FAMILY_TASK=phpstan-playground-cli
export CLUSTER='arn:aws:ecs:eu-west-1:928192134594:cluster/phpstan'

export FPM_IMAGE_NAME=phpstan-playground-fpm
export NGINX_IMAGE_NAME=phpstan-playground-nginx
export FPM_DOCKER_TAG=928192134594.dkr.ecr.eu-west-1.amazonaws.com/${FPM_IMAGE_NAME}:${GIT_COMMIT_HASH}
export NGINX_DOCKER_TAG=928192134594.dkr.ecr.eu-west-1.amazonaws.com/${NGINX_IMAGE_NAME}:${GIT_COMMIT_HASH}

# ------------------------

echo Building FPM image:

docker build --build-arg GIT_COMMIT_HASH=${GIT_COMMIT_HASH} -t ${FPM_IMAGE_NAME}:${GIT_COMMIT_HASH} --target production -f docker/fpm/Dockerfile .
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

SERVICE_CONTAINER_DEFINITIONS=$(php deploy/serviceContainerDefinitions.php)
VOLUME_DEFINITIONS=$(php deploy/volumeDefinitions.php)
SERVICE_TASK_REVISION=$(aws ecs register-task-definition --region eu-west-1 --family ${TASK_FAMILY_SERVICE} --container-definitions ${SERVICE_CONTAINER_DEFINITIONS} --volumes ${VOLUME_DEFINITIONS} --requires-compatibilities EC2 | php -r "echo json_decode(file_get_contents('php://stdin'))->taskDefinition->revision;")

aws ecs update-service --cluster ${CLUSTER} --region eu-west-1 --service ${TASK_FAMILY_SERVICE} --task-definition ${TASK_FAMILY_SERVICE}:${SERVICE_TASK_REVISION} > /dev/null

TASK_CONTAINER_DEFINITIONS=$(php deploy/taskContainerDefinitions.php)
aws ecs register-task-definition --region eu-west-1 --family ${TASK_FAMILY_TASK} --container-definitions ${TASK_CONTAINER_DEFINITIONS} --volumes ${VOLUME_DEFINITIONS} --requires-compatibilities EC2  > /dev/null

echo Success! Deployed ${TASK_FAMILY_SERVICE}:${SERVICE_TASK_REVISION} \(${GIT_COMMIT_HASH}\)

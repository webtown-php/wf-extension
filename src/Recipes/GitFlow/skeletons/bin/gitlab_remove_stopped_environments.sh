#!/bin/bash
# Debug mode:
# set -x
[[ ${WF_DEBUG:-0} -ge 2 ]] && set -x

# DIRECTORIES
SOURCE="${BASH_SOURCE[0]}"
while [ -h "$SOURCE" ]; do # resolve $SOURCE until the file is no longer a symlink
  DIR="$( cd -P "$( dirname "$SOURCE" )" && pwd )"
  SOURCE="$(readlink "$SOURCE")"
  [[ $SOURCE != /* ]] && SOURCE="$DIR/$SOURCE" # if $SOURCE was a relative symlink, we need to resolve it relative to the path where the symlink file was located
done
DIR="$( cd -P "$( dirname "$SOURCE" )" && pwd )"

source ${DIR}'/_gitlab_init.sh'
source ${DIR}'/_css.sh'

DEAD_ENVS=$(curl -s --header "PRIVATE-TOKEN: ${PRIVATE_TOKEN}" "${URL}/projects/${PROJECT_ID}/environments" | jq '.[] | select(.external_url==null) .id')
for DEAD_ENV in $(echo "$DEAD_ENVS"); do
  curl -s --request DELETE --header "PRIVATE-TOKEN: ${PRIVATE_TOKEN}" "${URL}/projects/${PROJECT_ID}/environments/${DEAD_ENV}"
  echo -e "DELETED: ${DEAD_ENV}"
done

PROJECT_URL=$(echo $PROJECT_DATA | jq -r .web_url)
echo -e "\nEnvironments page: \033[1;35m${PROJECT_URL}/environments\033[0m"

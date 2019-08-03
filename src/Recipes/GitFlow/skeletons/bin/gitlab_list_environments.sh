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

printf "%-30s %s\n" "NAME" "URL"
printf "%-30s %s\n" "----" "---"
curl -s --header "PRIVATE-TOKEN: ${PRIVATE_TOKEN}" "${URL}/projects/${PROJECT_ID}/environments" \
    | jq -r '.[] | "\(.name) \(.external_url)"' \
    | awk '{ printf "\033[1m%-30s\033[0m \033[1;34m%s\033[0m\n", $1, $2 }'

PROJECT_URL=$(echo $PROJECT_DATA | jq -r .web_url)
echo -e "\nEnvironments page: \033[1;35m${PROJECT_URL}/environments\033[0m"

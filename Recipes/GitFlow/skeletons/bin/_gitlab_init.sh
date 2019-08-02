#!/bin/bash
# Debug mode:
# set -x
[[ ${WF_DEBUG:-0} -ge 2 ]] && set -x

API_PATH="/api/v4"
HOST=$(git config --get remote.origin.url | egrep -o '(//|@)[^:/]+' | grep -o '[^@\]*') || exit 1
PROJECT_PATH=$(git config --get remote.origin.url | egrep -o '[:/][^/]+/[^/]+\.' | egrep -o '[^:/]+/[^/\.]+') || exit 1
PROJECT_NAME=$(echo $PROJECT_PATH | egrep -o '[^/]+$') || exit 1
URL="https://${HOST}${API_PATH}"
TOKEN_CACHE="${HOME}/.gitlab-token"
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD) || exit 1

echo "Detect and check token..."
if [ ! -f $TOKEN_CACHE ]; then
    touch $TOKEN_CACHE || exit 1
    chmod 600 $TOKEN_CACHE || exit 1
fi

PRIVATE_TOKEN=$(cat $TOKEN_CACHE)
if [ -z "$PRIVATE_TOKEN" ]; then
    TOKEN_CHECK=302
else
    TOKEN_CHECK=$(curl -s -o /dev/null -I -w "%{http_code}" --header "PRIVATE-TOKEN: ${PRIVATE_TOKEN}" "${URL}/user") || exit 1
fi

source ${DIR}'/_css.sh'

if [ "200" != $TOKEN_CHECK ]; then
    echo "You don't have valid token, you have to login."
    echo -n "${LBLUE}Username${RESTORE}: "
    read username
    echo -n "${LBLUE}Password${RESTORE}: "
    read -s password
    echo ""
    echo "Connecting..."
    PRIVATE_TOKEN=$(curl -s --request POST "${URL}/session?login=${username}&password=${password}" | jq -r .private_token)
    if [ "$PRIVATE_TOKEN" == "null" ]; then
        echo_fail "Invalid username or password!";
        exit 1
    fi
    echo $PRIVATE_TOKEN > $TOKEN_CACHE
fi

echo_pass "You have been logged in"

PROJECT_DATA=$(curl -s --header "PRIVATE-TOKEN: ${PRIVATE_TOKEN}" "${URL}/projects?search=${PROJECT_NAME}" | jq '.[] | select(.path_with_namespace=="'"${PROJECT_PATH}"'")')
PROJECT_ID=$(echo $PROJECT_DATA | jq .id)

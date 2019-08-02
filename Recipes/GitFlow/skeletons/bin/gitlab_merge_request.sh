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

# Does it create MR to master branch too?
MR_TO_MASTER_TOO=0

if [ -z "$DEVELOP_BRANCH_NAME" ]; then
    DEVELOP_BRANCH_NAME="develop"
fi

if [ -z "$HOTFIX_BRANCH_PREFIX" ]; then
    HOTFIX_BRANCH_PREFIX="hotfix"
fi

if [ "$CURRENT_BRANCH" == "master" ]; then
    echo_block "97;43m" " Now you are on 'master' branch. You can't create MR from master branch. Please checkout to other branch!"
    exit 0
fi

echo "Origin repository URL: ${GREEN}${URL}${RESTORE}"
echo "Project path:          ${GREEN}${PROJECT_PATH}${RESTORE}"
echo "Current branch:        ${GREEN}${CURRENT_BRANCH}${RESTORE}"
echo ""
echo "Update repositories..."
git fetch -u origin master:master || exit 1
echo_pass "origin/master"
git fetch -u origin $DEVELOP_BRANCH_NAME:$DEVELOP_BRANCH_NAME || exit 1
echo_pass "origin/${DEVELOP_BRANCH_NAME}"

# Is there any diff with master?
if [[ $CURRENT_BRANCH == ${HOTFIX_BRANCH_PREFIX}* ]]; then
    MASTER_DIFF=$(git log origin/master..HEAD)
    if [ ! -z "$MASTER_DIFF" ]; then
        MR_TO_MASTER_TOO=1
    fi
fi

# Is there any diff with develop?
DEVELOP_DIFF=$(git log origin/${DEVELOP_BRANCH_NAME}..HEAD)
if [ -z "$DEVELOP_DIFF" ] && [ "$MR_TO_MASTER_TOO" == "0" ]; then
    echo_block "97;43m" " There are no differents! No changes or perheps the branch has already merged!"
    exit 0
fi

CREATOR_ID=$(echo $PROJECT_DATA | jq .creator_id)

# Develop MR
MR_DATA=$(curl -s --header "PRIVATE-TOKEN: ${PRIVATE_TOKEN}" "${URL}/projects/${PROJECT_ID}/merge_requests" | \
            jq '.[] | select(.target_branch=="'"${DEVELOP_BRANCH_NAME}"'" and .source_branch=="'"${CURRENT_BRANCH}"'" and .state=="opened")' \
)
if [ -z "$MR_DATA" ]; then
    DEVELOP_MR_DATA=$(curl -s --header "PRIVATE-TOKEN: ${PRIVATE_TOKEN}" --request POST \
        -F "source_branch=${CURRENT_BRANCH}" \
        -F "target_branch=${DEVELOP_BRANCH_NAME}" \
        -F "assignee_id=${CREATOR_ID}" \
        -F "title=Merge request: ${CURRENT_BRANCH} --> ${DEVELOP_BRANCH_NAME}" \
        -F "remove_source_branch=true" \
        "${URL}/projects/${PROJECT_ID}/merge_requests" \
    )
    echo_pass "The '$(echo $DEVELOP_MR_DATA | jq -r .title)' MR has been created! URL: ${LBLUE}$(echo $DEVELOP_MR_DATA | jq -r .web_url)"
else
    echo "The '$(echo $MR_DATA | jq -r .title)' MR exists! URL: ${LBLUE}$(echo $MR_DATA | jq -r .web_url)${RESTORE}"
fi

# Master MR
if [ "$MR_TO_MASTER_TOO" == "1" ]; then
    MR_DATA=$(curl -s --header "PRIVATE-TOKEN: ${PRIVATE_TOKEN}" "${URL}/projects/${PROJECT_ID}/merge_requests" | \
                jq '.[] | select(.target_branch=="master" and .source_branch=="'"${CURRENT_BRANCH}"'" and .state=="opened")' \
    )
    if [ -z "$MR_DATA" ]; then
        DEVELOP_MR_DATA=$(curl -s --header "PRIVATE-TOKEN: ${PRIVATE_TOKEN}" --request POST \
            -F "source_branch=${CURRENT_BRANCH}" \
            -F "target_branch=master" \
            -F "assignee_id=${CREATOR_ID}" \
            -F "title=Merge request: ${CURRENT_BRANCH} --> master" \
            -F "remove_source_branch=true" \
            "${URL}/projects/${PROJECT_ID}/merge_requests" \
        )
        echo_pass "The '$(echo $DEVELOP_MR_DATA | jq -r .title)' MR has been created! URL: ${LBLUE}$(echo $DEVELOP_MR_DATA | jq -r .web_url)"
    else
        echo "The '$(echo $MR_DATA | jq -r .title)' MR exists! URL: ${LBLUE}$(echo $MR_DATA | jq -r .web_url)${RESTORE}"
    fi
fi

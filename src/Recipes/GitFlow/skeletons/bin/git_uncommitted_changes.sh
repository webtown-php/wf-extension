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

source ${DIR}'/_css.sh'

if [ "$(git status -s | wc -l)" != "0" ];
then
    echo "${YELLOW}There are uncommitted git changes:${RESTORE}"
    git status -s
    echo ""
    echo "${YELLOW}What can you do?${RESTORE}"
    echo "  1. You can make ${GREEN}stash${RESTORE}:"
    echo "     > ${BOLD}${WHITE}git stash save --include-untracked --all${RESTORE}"
    echo "     > ${BOLD}${WHITE}[... change branch ...]${RESTORE}"
    echo "     > ${BOLD}${WHITE}git stash apply${RESTORE}"
    echo ""
    echo "  2. You can make a ${GREEN}commit${RESTORE}:"
    echo "     > ${BOLD}${WHITE}git add . && git commit -m \"...\" && wf push${RESTORE}"
    echo ""
    exit 1
fi

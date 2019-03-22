#!/usr/bin/env bash

stars=$(printf "%-30s" "*")

export CI_SCRIPT_OPTIONS="ci_script_options"
export RTBOT_WORKSPACE="/home/rtbot/github-workspace"
hosts_file="$GITHUB_WORKSPACE/.github/hosts.yml"

rsync -a "$GITHUB_WORKSPACE/" "$RTBOT_WORKSPACE"
rsync -a /root/vip-go-ci-tools/ /home/rtbot/vip-go-ci-tools
chown -R rtbot:rtbot /home/rtbot/

GITHUB_REPO_NAME=${GITHUB_REPOSITORY##*/}
GITHUB_REPO_OWNER=${GITHUB_REPOSITORY%%/*}

phpcs_standard=''
VIP="false"
if [[ -f "$hosts_file" ]]; then
    VIP=$(cat "$hosts_file" | shyaml get-value "$CI_SCRIPT_OPTIONS.vip" | tr '[:upper:]' '[:lower:]')
fi

if [[ -f "$RTBOT_WORKSPACE/phpcs.xml" ]]; then
    phpcs_standard="--phpcs-standard=$RTBOT_WORKSPACE/phpcs.xml"
elif [[ "$VIP" = "true" ]]; then
    phpcs_standard="--phpcs-standard=WordPress-VIP-Go --phpcs-severity=1"
else
    phpcs_standard="--phpcs-standard=WordPress-Core,WordPress-Docs"
fi

[[ -n "$1" ]] && user_phpcs_standard="--phpcs-standard=$1"
# user_phpcs_standard contains
phpcs_standard="${user_phpcs_standard:-$phpcs_standard}"

gosu rtbot bash -c "/home/rtbot/vip-go-ci-tools/vip-go-ci/vip-go-ci.php --repo-owner=$GITHUB_REPO_OWNER --repo-name=$GITHUB_REPO_NAME --commit=$GITHUB_SHA --token=$USER_GITHUB_TOKEN --phpcs-path=/home/rtbot/vip-go-ci-tools/phpcs/bin/phpcs --local-git-repo=/home/rtbot/github-workspace --phpcs=true $phpcs_standard --lint=true"

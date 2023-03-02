#!/usr/bin/env bash

## Logging functions
# Arguments: Message.
error_message() {
  echo -en "\033[31mERROR\033[0m: $1"
}

warning_message() {
  echo -en "\033[33mWARNING\033[0m: $1"
}

info_message() {
  echo -en "\033[32mINFO\033[0m: $1"
}

if [[ "$GITHUB_EVENT_NAME" != "pull_request" ]]; then
  echo $( warning_message "This action only runs on pull_request events." )
  echo $( info_message "Refer https://docs.github.com/en/actions/using-workflows/events-that-trigger-workflows#pull_request for more information." )
  echo $( info_message "Exiting..." )

  exit 0
fi

if [[ $(cat "$GITHUB_EVENT_PATH" | jq -r .pull_request.body) == *"[do-not-scan]"* ]]; then
  echo $( info_message "[do-not-scan] found in PR description. Skipping PHPCS scan." )

  exit 0
fi

GITHUB_REPOSITORY_NAME=${GITHUB_REPOSITORY##*/}
GITHUB_REPOSITORY_OWNER=${GITHUB_REPOSITORY%%/*}
COMMIT_ID=$(cat $GITHUB_EVENT_PATH | jq -r '.pull_request.head.sha')

echo $( info_message "COMMIT_ID: $COMMIT_ID" )
echo $( info_message "GITHUB_REPOSITORY_NAME: $GITHUB_REPOSITORY_NAME" )
echo $( info_message "GITHUB_REPOSITORY_OWNER: $GITHUB_REPOSITORY_OWNER" )

if [[ -z "$GITHUB_REPOSITORY_NAME" ]] || [[ -z "$GITHUB_REPOSITORY_OWNER" ]] || [[ -z "$COMMIT_ID" ]]; then
  echo $( error_message "One or more of the following variables are not set: GITHUB_REPOSITORY_NAME, GITHUB_REPOSITORY_OWNER, COMMIT_ID" )

  exit 1
fi

if [[ -n "$VAULT_TOKEN" ]]; then
  GH_BOT_TOKEN=$(vault read -field=token secret/rtBot-token)

    echo "::warning ::Support for HashiCorp Vault will be discontinued in the future. Please use GitHub Action Secrets to store the secrets. Refer https://docs.github.com/en/actions/security-guides/encrypted-secrets#creating-encrypted-secrets-for-a-repository to know more about GitHub Action Secrets."
fi

# Remove trailing and leading whitespaces. At times copying token can give leading space.
GH_BOT_TOKEN=${GH_BOT_TOKEN//[[:blank:]]/}

if [[ -z "$GH_BOT_TOKEN" ]]; then
  echo $( error_message "GH_BOT_TOKEN is not set." )

  exit 1
fi

# VIP Go CI tools directory.
VIP_GO_CI_TOOLS_DIR="$ACTION_WORKDIR/vip-go-ci-tools"

# Setup GitHub workspace inside Docker container.
DOCKER_GITHUB_WORKSPACE="$ACTION_WORKDIR/workspace"

# Sync GitHub workspace to Docker GitHub workspace.
rsync -a "$GITHUB_WORKSPACE/" "$DOCKER_GITHUB_WORKSPACE"

echo $( info_message "DOCKER_GITHUB_WORKSPACE: $DOCKER_GITHUB_WORKSPACE" )

if [[ ! -d "$VIP_GO_CI_TOOLS_DIR" ]] || [[ ! -d "$DOCKER_GITHUB_WORKSPACE" ]]; then
  echo $( error_message "One or more of the following directories are not present: VIP_GO_CI_TOOLS_DIR, DOCKER_GITHUB_WORKSPACE" )

  exit 1
fi

################################################################################
#                    Configure options for vip-go-ci                           #
#                                                                              #
#   Refer https://github.com/Automattic/vip-go-ci#readme for more information  #
################################################################################

################################################################################
#                             General Configuration                            #
################################################################################

#######################################
# Set the --lint and --phpcs
# Default: true
# Options: BOOLEAN
#######################################
CMD=( "--lint=false" "--phpcs=true" )

#######################################
# Set the --skip-draft-prs
# Default: false
# Options: BOOLEAN
#######################################
if [[ "$SKIP_DRAFT_PRS" == "true" ]]; then
  CMD+=( "--skip-draft-prs=true" )
fi

#######################################
# Set the --local-git-repo
# Options: STRING (Path to local git repo)
#######################################
CMD+=( "--local-git-repo=$DOCKER_GITHUB_WORKSPACE" )

#######################################
# Set the --name-to-use
# Default: 'action-phpcs-code-review'
# Options: STRING (Name to use for the bot)
#######################################
if [[ -n "$NAME_TO_USE" ]]; then
  CMD+=( "--name-to-use=$NAME_TO_USE" )
else
  CMD+=( "--name-to-use=[action-phpcs-code-review](https://github.com/rtCamp/action-phpcs-code-review/)")
fi

################################################################################
#                      Environmental & repo configuration                      #
################################################################################

#######################################
# Set the --repo-options
# Default: If .vipgoci_options file is present in the repo, then true.
#######################################
if [[ -f "$DOCKER_GITHUB_WORKSPACE/.vipgoci_options" ]]; then
  CMD+=( "--repo-options=true" )
fi

################################################################################
#                             GitHub configuration                             #
################################################################################

#######################################
# Set the --repo-owner
# Default: $GITHUB_REPOSITORY_OWNER
#######################################
CMD+=( "--repo-owner=$GITHUB_REPOSITORY_OWNER" )

#######################################
# Set the --repo-name
# Default: $GITHUB_REPOSITORY_NAME
#######################################
CMD+=( "--repo-name=$GITHUB_REPOSITORY_NAME" )

#######################################
# Set the --commit
# Default: $GITHUB_SHA
#######################################
CMD+=( "--commit=$COMMIT_ID" )

#######################################
# Set the --token
# Default: $GH_BOT_TOKEN
# Options: STRING (GitHub token)
#######################################
CMD+=( "--token=$GH_BOT_TOKEN" )

################################################################################
#                            PHPCS configuration                               #
################################################################################

#######################################
# Set the --phpcs-php-path
# Default: PHP in $PATH
# Options: FILE (Path to php executable)
#######################################
if [[ -n "$PHPCS_PHP_VERSION" ]]; then
  if [[ -z "$( command -v php$PHPCS_PHP_VERSION )" ]]; then
    echo $( warning_message "php$PHPCS_PHP_VERSION is not available. Using default php runtime...." )

    phpcs_php_path=$( command -v php )
  else
    phpcs_php_path=$( command -v php$PHPCS_PHP_VERSION )
  fi

  CMD+=( "--phpcs-php-path=$PHPCS_PHP_VERSION" )
fi

#######################################
# Set the --phpcs-path
# Default: $VIP_GO_CI_TOOLS_DIR/phpcs/bin/phpcs
# Options: FILE (Path to phpcs executable)
#######################################
phpcs_path="$VIP_GO_CI_TOOLS_DIR/phpcs/bin/phpcs"

if [[ -n "$PHPCS_FILE_PATH" ]]; then
  if [[ -f "$DOCKER_GITHUB_WORKSPACE/$PHPCS_FILE_PATH" ]]; then
    phpcs_path="$DOCKER_GITHUB_WORKSPACE/$PHPCS_FILE_PATH"
  else
    echo $( warning_message "$DOCKER_GITHUB_WORKSPACE/$PHPCS_FILE_PATH does not exist. Using default path...." )
  fi
fi

CMD+=( "--phpcs-path=$phpcs_path" )

#######################################
# Set the --phpcs-standard
# Default: WordPress
# Options: STRING (Comma separated list of standards to check against)
#
#  1. Either a comma separated list of standards to check against.
#  2. Or a path to a custom ruleset.
#######################################
phpcs_standard=''

defaultFiles=(
  '.phpcs.xml'
  'phpcs.xml'
  '.phpcs.xml.dist'
  'phpcs.xml.dist'
)

phpcsfilefound=1

for phpcsfile in "${defaultFiles[@]}"; do
  if [[ -f "$DOCKER_GITHUB_WORKSPACE/$phpcsfile" ]]; then
      phpcs_standard="$DOCKER_GITHUB_WORKSPACE/$phpcsfile"
      phpcsfilefound=0
  fi
done

if [[ $phpcsfilefound -ne 0 ]]; then
    if [[ -n "$1" ]]; then
      phpcs_standard="$1"
    else
      phpcs_standard="WordPress"
    fi
fi

if [[ -n "$PHPCS_STANDARD_FILE_NAME" ]] && [[ -f "$DOCKER_GITHUB_WORKSPACE/$PHPCS_STANDARD_FILE_NAME" ]]; then
  phpcs_standard="$DOCKER_GITHUB_WORKSPACE/$PHPCS_STANDARD_FILE_NAME"
fi;

CMD+=( "--phpcs-standard=$phpcs_standard" )

#######################################
# Set the --phpcs-standards-to-ignore
# Default: PHPCSUtils
# Options:String (Comma separated list of standards to ignore)
#######################################
if [[ -n "$PHPCS_STANDARDS_TO_IGNORE" ]]; then
  CMD+=( "--phpcs-standards-to-ignore=$PHPCS_STANDARDS_TO_IGNORE" )
else
  CMD+=( "--phpcs-standards-to-ignore=PHPCSUtils" )
fi

#######################################
# Set the --phpcs-skip-scanning-via-labels-allowed
# Default: true
# Options: BOOLEAN
#######################################
CMD+=( "--phpcs-skip-scanning-via-labels-allowed=true" )

#######################################
# Set the --phpcs-skip-folders
# Options: STRING (Comma separated list of folders to skip)
#######################################
if [[ -n "$SKIP_FOLDERS" ]]; then
  CMD+=( "--phpcs-skip-folders=$SKIP_FOLDERS" )
fi

#######################################
# Set the --phpcs-sniffs-exclude
# Default: ''
# Options: STRING (Comma separated list of sniffs to exclude)
#######################################
if [[ -n "$PHPCS_SNIFFS_EXCLUDE" ]]; then
  CMD+=( "--phpcs-sniffs-exclude=$PHPCS_SNIFFS_EXCLUDE" )
fi

#######################################
# Set the --phpcs-skip-folders-in-repo-options-file
# Default: If .vipgoci_phpcs_skip_folders file exists in the repo, then true.
#######################################
if [[ -f "$DOCKER_GITHUB_WORKSPACE/.vipgoci_phpcs_skip_folders" ]]; then
  CMD+=( "--phpcs-skip-folders-in-repo-options-file=true" )
fi

################################################################################
#                GitHub reviews & generic comments configuration               #
################################################################################

#######################################
# Set the --report-no-issues-found
# Default: false
#######################################
CMD+=( "--report-no-issues-found=false" )

#######################################
# Set the --informational-msg
# Default: Powered by rtCamp's [GitHub Actions Library](https://github.com/rtCamp/github-actions-library/)
# Options: STRING (Message to be included in the comment)
#######################################
if [[ -z "$INFORMATIONAL_MSG" ]]; then
  informational_msg="Powered by rtCamp's [GitHub Actions Library](https://github.com/rtCamp/github-actions-library)"
else
  informational_msg="$INFORMATIONAL_MSG"
fi

CMD+=( "--informational-msg=$informational_msg" )

#######################################
# Set the --scan-details-msg-include
# Default: false
#######################################
CMD+=( "--scan-details-msg-include=false" )

#######################################
# Set the --dismiss-stale-reviews
# Default: true
#######################################
CMD+=( "--dismiss-stale-reviews=true" )

################################################################################
#                Start Code Review and set GH build status                     #
################################################################################

echo $( info_message "Running PHPCS inspection..." )
echo $( info_message "Command: $VIP_GO_CI_TOOLS_DIR/vip-go-ci/vip-go-ci.php ${CMD[*]}" )

PHPCS_CMD=( php "$VIP_GO_CI_TOOLS_DIR/vip-go-ci/vip-go-ci.php" "${CMD[@]}" )

if [[ "$ENABLE_STATUS_CHECKS" == "true" ]]; then
  php $VIP_GO_CI_TOOLS_DIR/vip-go-ci/github-commit-status.php --repo-owner="$GITHUB_REPOSITORY_OWNER" --repo-name="$GITHUB_REPOSITORY_NAME" --github-token="$GH_BOT_TOKEN" --github-commit="$COMMIT_ID" --build-context='PHPCS Code Review by rtCamp' --build-description="PR review in progress" --build-state="pending"

  "${PHPCS_CMD[@]}"

  export VIPGOCI_EXIT_CODE="$?"

  export BUILD_STATE="failure"
  export BUILD_DESCRIPTION="Unknown error"

  case "$VIPGOCI_EXIT_CODE" in
    "0")
      export BUILD_STATE="success"
      export BUILD_DESCRIPTION="No PHPCS errors found"
      ;;
    "230")
      export BUILD_DESCRIPTION="Pull request not found for commit"
      ;;
    "248")
      export BUILD_DESCRIPTION="Commit not latest in PR"
      ;;
    "249")
      export BUILD_DESCRIPTION="Inspection timed out, PR may be too large"
      ;;
    "250")
      export BUILD_DESCRIPTION="Found PHPCS errors"
      ;;
    "251")
      export BUILD_DESCRIPTION="Action is not configured properly"
      ;;
    "252")
      export BUILD_DESCRIPTION="GitHub communication error. Please retry"
      ;;
    "253")
      export BUILD_DESCRIPTION="Wrong options passed to action"
      ;;
  esac


  php $VIP_GO_CI_TOOLS_DIR/vip-go-ci/github-commit-status.php --repo-owner="$GITHUB_REPOSITORY_OWNER" --repo-name="$GITHUB_REPOSITORY_NAME" --github-token="$GH_BOT_TOKEN" --github-commit="$commit" --build-context='PHPCS Code Review by rtCamp' --build-description="$BUILD_DESCRIPTION" --build-state="$BUILD_STATE"
else
  "${PHPCS_CMD[@]}"
fi

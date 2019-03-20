# PHPCS Inspections - GitHub Action

A [GitHub Action](https://github.com/features/actions) for running code inspections. It is based on https://github.com/Automattic/vip-go-ci/

You can use this action to work on latest commits pushed to Pull-Requests on GitHub, looking for problems in the code using PHP lint and PHPCS, and posting back to GitHub comments and reviews, detailing the issues found.

* This action by default respects standards specified in [phpcs.xml](https://github.com/rtCamp/github-actions-wordpress-skeleton/blob/master/phpcs.xml) file. 

* If no `phpcs.xml` file is found in the root of the repository then by default inspection is carried out using: `WordPress-Core and WordPress-Docs` standards.

This action is a part of [GitHub action library](https://github.com/rtCamp/github-actions-library/) created by [rtCamp](https://github.com/rtCamp/).

## Installation

> Note: To use this GitHub Action, you must have access to GitHub Actions. GitHub Actions are currently only available in public beta (you must [apply for access](https://github.com/features/actions)).

Here is an example setup of this action:

1. Create a `.github/main.workflow` in your GitHub repo.
2. Add the following code to the `main.workflow` file and commit it to the repo's `master` branch.
3. Define `USER_GITHUB_TOKEN` as a [GitHub Actions Secret](https://developer.github.com/actions/creating-workflows/storing-secrets). (You can add secrets using the visual workflow editor or the repository settings.)
[Read here](#environment-variables) for more info on how to setup this variable.

```bash
workflow "Run Inspections" {
  resolves = ["PHPCS Inspections"]
  on = "pull_request"
}

action "PHPCS Inspections" {
  uses = "rtCamp/action-vip-go-ci@master"
  secrets = ["USER_GITHUB_TOKEN"]
}
```

4. Whenever you create a pull request or commit on an existing pull request, this action will run.

## Environment Variables

`USER_GITHUB_TOKEN`: [GitHub token](https://github.com/settings/tokens), that will be used to post review comments on opened pull requests if any issue is found during the inspections run. 

1. It is recommended to create this token from a bot user account.
2. Permissions required for this token differ according to which type of repo this workflow has been setup for.
    1. Private Repo: Complete `repo` as well as `write:discussion` permission.
    2. Public Repo: Only `public_repo` permission.

## License

[MIT](LICENSE) © 2019 rtCamp

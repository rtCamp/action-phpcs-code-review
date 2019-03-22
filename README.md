# PHPCS Code Review - GitHub Action

A [GitHub Action](https://github.com/features/actions) for running PHPCS code review. It is based on https://github.com/Automattic/vip-go-ci/

You can use this action to review pull requests using PHPCS on GitHub. It will then flag all the problems it found on the pull request by creating a [pull request review](https://help.github.com/en/articles/about-pull-request-reviews).

![PHPCS Code Review Demo](https://user-images.githubusercontent.com/8456197/54820322-c55cb900-4cc4-11e9-8ba7-7ed2b2f3c189.png)

**Note:**
1. This action runs only for PRs. It even runs on new commits pushed after a PR is created.
2. This action doesn't run on code in the repository added before this action.
3. This action doesn't run for code committed directly to a branch.

This action is a part of [GitHub action library](https://github.com/rtCamp/github-actions-library/) created by [rtCamp](https://github.com/rtCamp/).

## Installation

> Note: To use this GitHub Action, you must have access to GitHub Actions. GitHub Actions are currently only available in public beta (you must [apply for access](https://github.com/features/actions)).

* This action by default respects standards specified in [phpcs.xml](https://github.com/rtCamp/github-actions-wordpress-skeleton/blob/master/phpcs.xml) file at the root of your repository.

* If `phpcs.xml` is not present, and `args` are defined, the standards mentioned in the args will will be respected. If both `args` are defined and phpcs.xml exists, then `phpcs.xml` will be used. See [available standards](#available-standards) for the list of standards available in this action.
```
action "PHPCS Inspections" {
  uses = "rtCamp/action-phpcs-code-review@master"
  secrets = ["USER_GITHUB_TOKEN"]
  args = ["WordPress-VIP-Go"]
}
```

* If no `phpcs.xml` file is found in the root of the repository then by default inspection is carried out using: `WordPress, WordPress-Core and WordPress-Docs` standards.

Here is an example setup of this action:

1. Create a `.github/main.workflow` in your GitHub repo.
2. Add the following code to the `main.workflow` file and commit it to the repo's `master` branch.
3. Define `USER_GITHUB_TOKEN` as a [GitHub Actions Secret](https://developer.github.com/actions/creating-workflows/storing-secrets). (You can add secrets using the visual workflow editor or the repository settings.)
[Read here](#environment-variables) for more info on how to setup this variable.

```bash
workflow "Run Code Review" {
  resolves = ["PHPCS Code Review"]
  on = "pull_request"
}

action "PHPCS Code Review" {
  uses = "rtCamp/action-vip-go-ci@master"
  secrets = ["USER_GITHUB_TOKEN"]
}
```

4. Whenever you create a pull request or commit on an existing pull request, this action will run.

## Environment Variables

`USER_GITHUB_TOKEN`: [GitHub token](https://github.com/settings/tokens), that will be used to post review comments on opened pull requests if any issue is found during the code review.

1. It is recommended to create this token from a [bot user account](https://stackoverflow.com/a/29177936/4108721). In a large team, if you use your human account token, you may get flooded with unncessary Github notifications.
2. Permissions required for this token differ according to which type of repo this workflow has been setup for.
    1. Private Repo: Complete `repo` as well as `write:discussion` permission. [TODO: Add screenshot]
    2. Public Repo: Only `public_repo` permission. [TODO: Add screenshot]

## Available Standards

You can pass more than one standard at a time by comma separated value. By default, `WordPress-Core,WordPress-Docs` value is passed.

* MySource
* PEAR
* PHPCompatibility
* PHPCompatibilityParagonieRandomCompat
* PHPCompatibilityParagonieSodiumCompat
* PHPCompatibilityWP
* PSR1
* PSR12
* PSR2
* Squiz
* WordPress _(default)_
* WordPress-Core _(default)_
* WordPress-Docs _(default)_
* WordPress-Extra
* WordPress-VIP
* WordPress-VIP-Go
* WordPressVIPMinimum
* Zend

## License

[MIT](LICENSE) © 2019 rtCamp

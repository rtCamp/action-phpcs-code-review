This action is a part of [GitHub Actions Library](https://github.com/rtCamp/github-actions-library/) created by [rtCamp](https://github.com/rtCamp/).

# PHPCS Code Review - GitHub Action

[![Project Status: Active – The project has reached a stable, usable state and is being actively developed.](https://www.repostatus.org/badges/latest/active.svg)](https://www.repostatus.org/#active)


A [GitHub Action](https://github.com/features/actions) to perform automated [pull request review](https://help.github.com/en/articles/about-pull-request-reviews). It is based on https://github.com/Automattic/vip-go-ci/ but can be used for any WordPress or even PHP projects.

The code review is performed using [PHPCS](https://github.com/squizlabs/PHP_CodeSniffer).

Please note that, this action performs pull request review *only*. If you have an existing project, and you want entire project's code to be reviwed, you may need to do it manually.

## Usage

1. Create a `.github/workflows/phpcs.yml` in your GitHub repo, if one doesn't exist already.
2. Add the following code to the `phpcs.yml` file.

```yaml
on: pull_request

name: Inspections
jobs:
  runPHPCSInspection:
    name: Run PHPCS inspection
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v2
      with:
        ref: ${{ github.event.pull_request.head.sha }}
    - name: Run PHPCS inspection
      uses: rtCamp/action-phpcs-code-review@v2
      env:
        GH_BOT_TOKEN: ${{ secrets.GH_BOT_TOKEN }}
        SKIP_FOLDERS: "tests,.github"
        PHPCS_SNIFFS_EXCLUDE: "WordPress.Files.FileName"
      with:
        args: "WordPress,WordPress-Core,WordPress-Docs"
```

3. Define `GH_BOT_TOKEN` using [GitHub Action's Secret](https://developer.github.com/actions/creating-workflows/storing-secrets). See [GitHub Token Creation](#github-token-creation) section for more details.

Now, next time you create a pull request or commit on an existing pull request, this action will run.

By default, pull request will be reviwed using WordPress coding and documentation standards. You can change the default by passing different [PHPCS Coding Standard(s)](#phpcs-coding-standards) in line `args = ["WordPress-Core,WordPress-Docs"]`.

4. In case you want to skip PHPCS scanning in any pull request, add `[do-not-scan]` in the PR description. You can add it anywhere in the description and it will skip the action run for that pull request.

5. In case you want to skip linting all files on every pull request, set `PHP_LINT` to `false`.

## GitHub Token Creation

You can create [GitHub Token from here](https://github.com/settings/tokens).

It is necessary that you create this token from a [bot user account](https://stackoverflow.com/a/29177936/4108721). Please note that the bot account should have access to the repo in which action is being run, in case it is a private repo. It is compulsory to use a bot account because in GitHub it is forbidden to request changes on your own Pull Request by your own user.
Additional benefit of using a bot account is that in a large team, if you use your human account token, you may get flooded with unncessary Github notifications.

Permissions required for this token differ according to which type of repo this workflow has been setup for.

Repo Type | Permissions Required                                | Screenshots
----------|-----------------------------------------------------|-------------------------------------------------------------
Public    | Under `Repo` section, only `public_repo` permission | [Screenshot Public Repo](https://user-images.githubusercontent.com/4115/54978322-01926100-4fc6-11e9-8da5-1e088fa52b34.png)
Private   | Complete `repo` and `write:discussion` permissions  | [Screenshot Private Repo](https://user-images.githubusercontent.com/4115/54978180-86c94600-4fc5-11e9-846e-7d3fd1dfb7e0.png)

## Environment Variables

Variable       | Default | Possible  Values            | Purpose
---------------|---------|-----------------------------|----------------------------------------------------
`SKIP_FOLDERS` | -       | `tests`,`tests,.github` (Any other comma seprated top level directories in the repo)     | If any specific folders should be ignored when scanning, then a comma seprated list of values should be added to this env variable.
`PHPCS_SNIFFS_EXCLUDE` | -       | `WordPress.Files.FileName` (Any other comma seprated list of valid sniffs) | Single sniff or comma seprated list of sniffs to be excluded from the phpcs scan.
`PHP_LINT`     | `true`  | `true` or `false`, *case insensitive* (Any unknown value is the same as passing `true`)  | If the default automatic linting of all PHP files should be deactivated, then this env variable should be set to `false`.
`PHPCS_STANDARD_FILE_NAME`     |  -  | phpcs ruleset file from project root dir. i.e phpcs.ruleset.xml | PHP_CodeSniffer ruleset filename. Default filename available: '.phpcs.xml', 'phpcs.xml', '.phpcs.xml.dist', 'phpcs.xml.dist'
`PHPCS_FILE_PATH`     |  -  | Custom phpcs execution file path from project. i.e Composer phpcs path. 'vendor/bin/phpcs' | This is useful in case of needed to use any custom coding standards apart from pre-defined in VIP/WP Coding Standards. [Wiki](https://github.com/rtCamp/action-phpcs-code-review/wiki/How-to%3F#use-custom-coding-standards)

## Modifying the bot’s behavior

You can change the bot’s behavior by placing a configuration file named `.vipgoci_options` at the root of the relevant repository. This file must contain a valid JSON string for this to work; if the file is not parsable, it will be ignored. This file is where you can add code to turn off support messages as well as adjust PHPCS severity levels.

i.e: You can update phpcs severity:
```json
{
  "phpcs-severity": 5
}
```

Allowed options:
- `"skip-execution"`
- `"skip-draft-prs"`
- `"results-comments-sort"`
- `"review-comments-include-severity"`
- `"phpcs"`
- `"phpcs-severity"`
- `"post-generic-pr-support-comments"`
- `"phpcs-sniffs-include"`
- `"phpcs-sniffs-exclude"`
- `"hashes-api"`
- `"svg-checks"`
- `"autoapprove"`
- `"autoapprove-php-nonfunctional-changes`

For more details please check the documentation for [all options here](https://github.com/automattic/vip-go-ci#configuration-via-repository-config-file).

## Skipping PHPCS scanning for specific folders

You can add files to the root of the repository indicating folders that should not be scanned. For PHPCS, the file should be named `.vipgoci_phpcs_skip_folders`. For PHP Linting the file should be named `.vipgoci_lint_skip_folders`. Please ensure both files are located in the root of the repository.

This can be used as an alternate to `SKIP_FOLDERS` env variable.

**Please note** that the folders exlcuded in the PHPCS xml file do not work in this action, you can check the reason [here](https://github.com/rtCamp/action-phpcs-code-review/issues/29#issuecomment-623933663). Instead you should add all the folders to be excluded in either `SKIP_FOLDERS` env or `.vipgoci_phpcs_skip_folders` file.

List each folder to be skipped on individual lines within those files.

i.e:
```
foo
tests/bar
vendor
node_modules
```

For more details, please check the documentation [here](https://github.com/automattic/vip-go-ci#skipping-certain-folders).

## PHPCS Coding Standards

Below is list of PHPCS sniffs available at runtime. You can pass more than one standard at a time by comma separated value.

By default, `WordPress-Core,WordPress-Docs` value is passed.

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

### Custom Sniffs

Default filename supported:
- `.phpcs.xml`
- `phpcs.xml`
- `.phpcs.xml.dist`
- `phpcs.xml.dist`

If your git repo has a file named `phpcs.xml` in the root of the repository, then that will take precedence. In that case, value passed to args such as `args = ["WordPress,WordPress-Core,WordPress-Docs"]` will be ignored.

If your git repo doesn't have `phpcs.xml` and you do not specify `args` in `main.workflow` PHPCS action, then this actions will fallback to default.

If your git repo has phpcs ruleset file other than default filename list, use `PHPCS_STANDARD_FILE_NAME` environment var to provide filename.

Here is a sample [phpcs.xml](https://github.com/rtCamp/github-actions-wordpress-skeleton/blob/master/phpcs.xml) you can use in case you want to use custom sniffs.

### Custom Coding Standards

If you have custom coding standards from your git repository, you can use composer and use `phpcs` from execution from your repository phpcs file with the help of `PHPCS_FILE_PATH` environment variable. Please refer this [wiki page](https://github.com/rtCamp/action-phpcs-code-review/wiki/How-to%3F#use-custom-coding-standards) for more information.

## Screenshot

**Automated Code Review in action**

<img width="770" alt="Automated PHPCS Code Review" src="https://user-images.githubusercontent.com/4115/55004924-20621900-5001-11e9-9363-fd6f9a99170e.png">


## Limitations

Please note...

1. This action runs only for PRs. It even runs on new commits pushed after a PR is created.
2. This action doesn't run on code in the repository added before this action.
3. This action doesn't run for code committed directly to a branch. We highly recommend that you disable direct commits to your main/master branch.

## License

[MIT](LICENSE) © 2019 rtCamp

## Does this interest you?

<a href="https://rtcamp.com/"><img src="https://rtcamp.com/wp-content/uploads/2019/04/github-banner@2x.png" alt="Join us at rtCamp, we specialize in providing high performance enterprise WordPress solutions"></a>

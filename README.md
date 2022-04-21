# The OpenEuropa Whitelabel theme

Sub-theme of [OpenEuropa Bootstrap base theme](https://github.com/openeuropa/bootstrap-component-library), with integration for many of the Drupal modules from the OpenEuropa ecosystem.

## Features

### Paragraphs

The paragraphs below are not yet themed therefore not recommended for usage:

- Contextual navigation
- Document

Some paragraphs are considered "internal", and only meant to be used inside other paragraphs:

- Listing item: To be used as item paragraph within 'Listing item block'.
- Fact: To be used as item paragraph within 'Facts and figures'.

## Usage as a dependency in another project

Website projects can use `oe_whitelabel` either as an active theme, or they can create a custom theme using `oe_whitelabel` as a base theme.

### Requirements

The package is meant for Drupal projects that manage their dependencies with [Composer](https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies#managing-contributed).

Ideally this project should be managed with [Docker](https://www.docker.com/get-docker) and [Docker Compose](https://docs.docker.com/compose/), but this is not a hard requirement.

Check the [composer.json](composer.json) for required PHP version and other dependencies.

### Add the composer package

Add this manually in composer.json, or combine with existing entries:

```
    "extra": {
        "artifacts": {
            "openeuropa/oe_bootstrap_theme": {
                "dist": {
                    "url": "https://github.com/{name}/releases/download/{pretty-version}/{project-name}-{pretty-version}.zip",
                    "type": "zip"
                }
            },
            "openeuropa/oe_whitelabel": {
                "dist": {
                    "url": "https://github.com/{name}/releases/download/{pretty-version}/{project-name}-{pretty-version}.zip",
                    "type": "zip"
                }
            }
        }
    }
```

If your project is still in development, you may modify composer.json to allow alpha versions, while this package and some of its dependencies are still in their `alpha` phase.

Review this again when the site goes into production.

```
    "minimum-stability": "alpha",
```

Require with composer:

```bash
composer require openeuropa/oe_whitelabel
```

If composer.json does not allow alpha versions, a version string needs to be appended to the package name in the require command, and other dependencies with alpha versions also need to be required with explicit version strings. The version string could be `:^1.0@alpha`, or a specific version like `:^1.0.0-alpha7`.

```bash
composer require openeuropa/oe_whitelabel:^1.0@alpha openeuropa/oe_bootstrap_theme:^1.0@alpha
```

Review the installed package versions. Make sure that oe_whitelabel and oe_bootstrap theme are using 1.x or higher.

```bash
composer show -i | grep oe_
```

### Enable and configure

Enable required and optional submodules:

```bash
# Always required.
./vendor/bin/drush en oe_whitelabel_helper

# Required, if you use oe_paragraphs module, or if you copied any paragraph
# types from that module.
./vendor/bin/drush en oe_whitelabel_paragraphs

# Other submodules are optional - check the /modules/ folder.
./vendor/bin/drush en <modulename>
```

Enable the theme and set as default:

```bash
./vendor/bin/drush config-set system.theme default oe_bootstrap_theme
```

## Development setup

### Using LAMP stack or similar

This is not officially supported. You are on your own.

### Using Docker Compose

Alternatively, you can build a development site using [Docker](https://www.docker.com/get-docker) and
[Docker Compose](https://docs.docker.com/compose/) with the provided configuration.

Docker provides the necessary services and tools such as a web server and a database server to get the site running,
regardless of your local host configuration.

#### Requirements

- [Docker](https://www.docker.com/get-docker)
- [Docker Compose](https://docs.docker.com/compose/)

#### Configuration

By default, Docker Compose reads two files, a `docker-compose.yml` and an optional `docker-compose.override.yml` file.
By convention, the `docker-compose.yml` contains your base configuration and it's provided by default.
The override file, as its name implies, can contain configuration overrides for existing services or entirely new
services.
If a service is defined in both files, Docker Compose merges the configurations.

Find more information on Docker Compose extension mechanism on [the official Docker Compose documentation](https://docs.docker.com/compose/extends/).

#### Start the container

If you have other (daemonized) containers running, you might want to stop them first:

```bash
docker stop $(docker ps -q)
```

To start, run:

```bash
docker-compose up
```

It's advised to not daemonize `docker-compose` so you can turn it off (`CTRL+C`) quickly when you're done working.
However, if you'd like to daemonize it, you have to add the flag `-d`:

```bash
docker-compose up -d
```

#### Optionally purge existing installation

If you already had the package installed, and want a clean start:

```bash
docker-compose exec web rm composer.lock
docker-compose exec web rm -rf vendor/
docker-compose exec web rm -rf build/
```

#### Install the package

```bash
docker-compose exec -u node node npm install
docker-compose exec -u node node npm run build
docker-compose exec web composer install
docker-compose exec web ./vendor/bin/run drupal:site-install
```

Using default configuration, the development site files should be available in the `build` directory and the development site should be available at: [http://127.0.0.1:8080/build](http://127.0.0.1:8080/build) or [http://web:8080/build](http://web:8080/build).

#### Run the tests

To run the grumphp checks:

```bash
docker-compose exec web ./vendor/bin/grumphp run
```

To run the phpunit tests:

```bash
docker-compose exec web ./vendor/bin/phpunit
```

## Upgrade from older versions

### Upgrade to 1.0.0-alpha7

#### Paragraphs migration

Paragraphs-related theming and functionality has been moved from the [OpenEuropa Bootstrap base theme](https://github.com/openeuropa/oe_bootstrap_theme) to [OpenEuropa Whitelabel](https://github.com/openeuropa/oe_whitelabel).

Special paragraphs fields that were introduced in `oe_bootstrap_theme_paragraphs` are being renamed in `oe_whitelabel_paragraphs`.

If you have the `oe_paragraphs` module enabled, you should create a `hook_post_update_NAME()` in your code, to enable the `oe_whitelabel_paragraphs` module during deployment.

```php
function EXAMPLE_post_update_00001(): void {
  \Drupal::service('module_installer')->install(['oe_whitelabel_paragraphs']);
}
```

This is needed to make sure that the install hook for `oe_whitelabel_paragraphs` runs _before_ config-import during a deployment.

Note that `drush updb` will also trigger update hooks in `oe_bootstrap_theme_helper`, which will uninstall the legacy module `oe_bootstrap_theme_paragraphs`.

### Upgrade to 1.0.0-alpha6

This release contains some bugs, please move directly to alpha7.

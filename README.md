# The OpenEuropa Whitelabel theme

## Paragraphs

The paragraphs below are not yet themed therefore not recommended for usage:

- Contextual navigation
- Document

Some paragraphs are considered "internal", and only meant to be used inside other paragraphs:

- Listing item: To be used as item paragraph within 'Listing item block'.
- Fact: To be used as item paragraph within 'Facts and figures'.

## Usage as a dependency in another project

Website projects can use `oe_whitelabel` either as an active theme, or they can create a custom theme using `oe_whitelabel` as a base theme.

### Requirements

The package is meant for projects that manage their dependencies via [Composer](https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies#managing-contributed).

Ideally this project should be managed with [Docker](https://www.docker.com/get-docker) and
[Docker Compose](https://docs.docker.com/compose/), but this is not a hard requirement.

Check the [composer.json](composer.json) for required PHP version and other dependencies.


### Installation (composer)

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

Require with composer:

```bash
composer require openeuropa/oe_whitelabel
```

### Enable and configure

Enable the theme and set as default:

```bash
./vendor/bin/drush config-set system.theme default oe_bootstrap_theme
```

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

## Development setup

### Using LAMP stack or similar

This is not officially supported. You are on your own.

### Using Docker Compose

Alternatively, you can build a development site using [Docker](https://www.docker.com/get-docker) and
[Docker Compose](https://docs.docker.com/compose/) with the provided configuration.

Docker provides the necessary services and tools such as a web server and a database server to get the site running,
regardless of your local host configuration.

#### Requirements:

- [Docker](https://www.docker.com/get-docker)
- [Docker Compose](https://docs.docker.com/compose/)

#### Configuration

By default, Docker Compose reads two files, a `docker-compose.yml` and an optional `docker-compose.override.yml` file.
By convention, the `docker-compose.yml` contains your base configuration and it's provided by default.
The override file, as its name implies, can contain configuration overrides for existing services or entirely new
services.
If a service is defined in both files, Docker Compose merges the configurations.

Find more information on Docker Compose extension mechanism on [the official Docker Compose documentation](https://docs.docker.com/compose/extends/).

#### Usage

To start, run:

```bash
docker-compose up
```

It's advised to not daemonize `docker-compose` so you can turn it off (`CTRL+C`) quickly when you're done working.
However, if you'd like to daemonize it, you have to add the flag `-d`:

```bash
docker-compose up -d
```

Then:

```bash
docker-compose exec -u node node npm install
docker-compose exec -u node node npm run build
docker-compose exec web composer install
docker-compose exec web ./vendor/bin/run drupal:site-install
```

Using default configuration, the development site files should be available in the `build` directory and the development site should be available at: [http://127.0.0.1:8080/build](http://127.0.0.1:8080/build).

#### Running the tests

To run the grumphp checks:

```bash
docker-compose exec web ./vendor/bin/grumphp run
```

To run the phpunit tests:

```bash
docker-compose exec web ./vendor/bin/phpunit
```

## Upgrade from older versions

### Upgrade from 1.0.0-alpha5 and earlier

Target release: You can upgrade directly to the version that contains this README.md.

#### Paragraphs migration

Paragraphs-related theming and functionality has been moved from `oe_bootstrap_theme` to `oe_whitelabel`.

If you have the `oe_paragraphs` module enabled, you should:
- Create a `hook_update_N()` or `hook_post_update_NAME()` in a custom module, to enable the `oe_whitelabel_paragraphs` module.
- Run `drush updb` in your local installation.
- Run `drush config:export`, to export the changes.
- For deployment, make sure that `drush updb` runs _before_ `drush config:import`.

If you did _not_ have `oe_paragraphs` enabled, but you want to do so now, you should:
- Enable the new `oe_whitelabel_paragraphs` module in your local installation, ideally with `drush en`.
- Export configuration, e.g. via `drush cex`. This should add the module to `core.extension.yml`.
- For deployment, `drush config:import` will do the job. No update hook needed.

Note that `drush updb` will also trigger update hooks in `oe_bootstrap_theme_helper`, which will uninstall the legacy module `oe_bootstrap_theme_paragraphs`.

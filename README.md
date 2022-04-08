# The OpenEuropa Whitelabel theme

## Paragraphs

The paragraphs below are not yet themed therefore not recommended for usage:

- Contextual navigation
- Document

Some paragraphs are considered "internal", and only meant to be used inside other paragraphs:

- Listing item: To be used as item paragraph within 'Listing item block'.
- Fact: To be used as item paragraph within 'Facts and figures'.

## Requirements

This depends on the following software:

* [PHP 7.3](http://php.net/)

## Installation

The recommended way of installing the OpenEuropa Whitelabel Theme is via [Composer](https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies#managing-contributed).

```bash
composer require openeuropa/oe_whitelabel
```

### Enable the theme

In order to enable the theme in your project perform the following steps:

- Enable the OpenEuropa Whitelabel Theme and set it as default ```./vendor/bin/drush config-set system.theme default oe_whitelabel_theme```

### Integration with oe_paragraphs

In order to have full working integration with paragraphs in your project, you must enable oe_whitelabel_paragraphs module:

```./vendor/bin/drush en oe_whitelabel_paragraphs```

## Development setup

OpenEuropa Whitelabel theme uses [Webpack](https://webpack.js.org) to compile and bundle SASS and JS.

Make sure you have Node and npm installed.

You can read a guide on how to install node here: https://docs.npmjs.com/getting-started/installing-node

If you prefer to use [Yarn](https://yarnpkg.com) instead of npm, install Yarn by following the guide [here](https://yarnpkg.com/docs/install).

To install required Node.js dependencies run:


`npm install` or `yarn install`.

To build the final artifacts run:

```bash
npm run build
```

This will compile all SASS and JavaScript files into self-contained assets that are exposed as [Drupal libraries][11].

In order to download all required PHP code run:

```bash
composer install
```

This will build a fully functional Drupal site in the `./build` directory that can be used to develop and showcase the
theme.

Before setting up and installing the site make sure to customize default configuration values by copying [runner.yml.dist](runner.yml.dist)
to `./runner.yml` and override relevant properties.

To set up the project run:

```bash
./vendor/bin/run drupal:site-setup
```

A post command hook (`drupal:site-setup`) is triggered automatically after `composer install`.
It will make sure that the necessary symlinks are properly setup in the development site.
It will also perform token substitution in development configuration files such as `phpunit.xml.dist`.

The development site web root should be available in the `build` directory.

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

### Upgrade to 1.0.0-alpha6

Upgrade directly to 1.0.0-alpha7.

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

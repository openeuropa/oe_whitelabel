# OpenEuropa Whitelabel Helper

This module offers some additional functionality that might come in useful when
theming an OpenEuropa website.

Here is an overview of the features it offers:

### Corporate Logo Blocks

Two blocks, one for EC one for EU displaying the European Commission and European Union logos linked.

### Enables OE Authentication

Enables the [OpenEuropa Authentication](https://github.com/openeuropa/oe_authentication) module so the themed login block can be picked up by Drupal.

### Enables OE Multilingual

Enables the [OpenEuropa Multilingual](https://github.com/openeuropa/oe_multilingual) module.
The language switcher block is themed out of the box.

## Requirements

To be able to enable this module you will have to provide the dependent modules in your projects composer.json

```
composer require openeuropa/oe_authentication
composer require openeuropa/oe_multilingual
```

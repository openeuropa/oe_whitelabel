# OpenEuropa Whitelabel Modal Page

This module provides integration with the [Modal Page](https://www.drupal.org/project/modal_page) contrib module, enabling modal dialogs and toast notifications styled with the Bootstrap Component Library (BCL).

## Features

- **Bootstrap Component Library integration**: Template overrides that render modals and toasts using BCL components (implemented in the theme, outside of this module, see **Template overrides** section below)
- **Permission-based form simplification**: Hides advanced modal configuration fields from users without the "administer advanced modal page configuration" permission
- **Library conflict prevention**: Prevents modal_page from loading its own Bootstrap libraries to avoid conflicts with BCL (implemented in the theme, outside of this module)

## Requirements

To use this module, you need to require the Modal Page contrib module in your project's composer.json:

```
composer require drupal/modal_page:^6.0@beta
```

## Template overrides

This feature provides the following template overrides for the oe_whitelabel theme:

- `modal-page-modal.html.twig`: Renders modal dialogs using the BCL modal component
- `modal-page-toast.html.twig`: Renders toast notifications using the BCL toasts component

Both templates support all configuration options provided by the Modal Page module, including header/footer customization, buttons, video embeds, and display behavior settings.

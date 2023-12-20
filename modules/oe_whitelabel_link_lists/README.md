# OpenEuropa Whitelabel link lists

This module is a theming companion module to the [OpenEuropa Link Lists](https://github.com/openeuropa/oe_link_lists) component.

It provides a link list display plugin base class that can be extended to implement new plugins for the view modes
present in the website.\
Two methods need to be implemented when extending the base class:
* `protected function getEntityViewDisplayId(): string;`: returns the entity view mode ID to use for rendering an entity attached to the link.
* `protected function buildLinkWithFallback(LinkInterface $link): array;`: handles fallback rendering when the link has no entity attached,
or the entity being rendered doesn't support the given view mode.

This module ships with a plugin implementation that renders links using the `Teaser` view mode, as it's the only
additional view mode supported out of the box.

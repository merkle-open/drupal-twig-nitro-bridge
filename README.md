# Drupal Twig nitro bridge

Drupal module which acts as a bridge for [twig-nitro-library](https://github.com/namics/twig-nitro-library).

## Installation

```bash
$ composer require namics/drupal-twig-nitro-bridge
```

## Config
Update the path to the frontend directory at _/admin/config/twig-nitro-bridge/settings_

or use drush
```bash
$ drush cset twig_nitro_bridge.settings frontend_dir ../frontend/
```

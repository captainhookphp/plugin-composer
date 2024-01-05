# Composer-Plugin for [CaptainHook](https://github.com/captainhookphp/captainhook)

## ⚠️ This package is deprecated ⚠️

Please use the [hook-installer](https://github.com/captainhookphp/hook-installer) package instead.
The only difference to this package is that it does NOT require `CaptainHook` by itself.
That gives you the opportunity to choose your installation method. You can either install the
PHAR or the source code version with all its dependencies.

```json
{
  "require-dev": {
    "captainhook/captainhook-phar": "^5.0",
    "captainhook/hook-installer": "^1.0"
  }  
}
```

or

```json
{
  "require-dev": {
    "captainhook/captainhook": "^5.0",
    "captainhook/hook-installer": "^1.0"
  }  
}
```

or even use PHIVE to install the captain and just install the `hook-installer` plugin with composer.

```json
{
  "require-dev": {
    "captainhook/hook-installer": "^1.0"
  },
  "extra": {
    "captainhook": {
      "exec": "tools/captainhook.phar"
    }
  }
}
```

[![Latest Stable Version](https://poser.pugx.org/captainhook/plugin-composer/v/stable.svg?v=1)](https://packagist.org/packages/captainhook/plugin-composer)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.1-8892BF.svg)](https://php.net/)
[![Downloads](https://img.shields.io/packagist/dt/captainhook/plugin-composer.svg?v1)](https://packagist.org/packages/captainhook/plugin-composer)
[![License](https://poser.pugx.org/captainhook/plugin-composer/license.svg?v=1)](https://packagist.org/packages/captainhook/plugin-composer)

This is a composer-plugin that makes sure your teammates install the git hooks. For more information visit its [Website](https://github.com/captainhookphp/captainhook).

## Installation:

As this is a composer-plugin the preferred method is to use composer for installation.
 
```bash
$ composer require --dev captainhook/plugin-composer
```

Everything else will happen automagically.

## Customize

You can set a custom name for your hook configuration.
If you want to use the PHAR release of `CaptainHook` you can configure the path to the PHAR file.
All extra config settings are optional and if you are using the default settings you do not have to 
configure anything to make it work.
 
```json
{
  "extra": {
    "captainhook": {
      "config": "hooks.json",
      "exec": "tools/captainhook.phar",
      "disable-plugin": false
    }    
  }  
}
```

## A word of warning

It is still possible to commit without invoking the hooks. 
So make sure you run appropriate backend-sanity checks on your code!

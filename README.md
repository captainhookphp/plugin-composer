# Composer-Plugin for [CaptainHook](https://github.com/captainhookphp/captainhook)

This is a composer-plugin that installs CaptainHook and the corresponding git hooks. For more information visit its [Website](https://github.com/captainhookphp/captainhook).

[![Latest Stable Version](https://poser.pugx.org/captainhook/plugin-composer/v/stable.svg?v=1)](https://packagist.org/packages/captainhook/plugin-composer)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.1-8892BF.svg)](https://php.net/)
[![Downloads](https://img.shields.io/packagist/dt/captainhook/plugin-composer.svg?v1)](https://packagist.org/packages/captainhook/plugin-composer)
[![License](https://poser.pugx.org/captainhook/plugin-composer/license.svg?v=1)](https://packagist.org/packages/captainhook/plugin-composer)

## Installation:

As this is a composer-plugin the preferred method is to use composer for installation.
 
```bash
$ composer require --dev captainhook/plugin-composer
```

Everything else will happen automagically.

## Setup

The plugin will install CaptainHook and make sure the git-hooks are installed. The configuration though is still 
done using CaptainHook.

So after first installation you should run `vendor/bin/captainhook  configure -e` and then commit the file 
`captainhook.json` to version control. Then everyone using your project will also have the configured hooks installed.

## A word of warning

It is still possible to commit without invoking the hooks. 
So make sure you run appropriate backend-sanity checks on 
your code!

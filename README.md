# Castor PHP Quality Assurance Tools

This repository contains functions to run PHP quality assurance tools on Castor projects.

It does not add command to your castor project, but rather provide a set of functions that should
work in any environment without having PHP installed only castor is required.

## Installation

To install the package, you can use the following command:

```bash
castor composer require castor-php/php-qa
```

## Usage

Each tool is provided as a function that you can call in your castor project.

```php
<?php

use Castor\Attribute\AsTask;
use function Castor\PHPQa\phpstan;

#[AsTask('phpstan', namespace: 'qa')]
function qa_phpstan()
{
    phpstan(['analyze', __DIR__ . '/src']);
}
```

### Using a specific version

By default the latest version of the tool is used. However it is recommended to use a specific version
so you don't have different version depending the environment.

You can pass the version as the second argument of the function.

```php
<?php

use Castor\Attribute\AsTask;
use function Castor\PHPQa\phpstan;

#[AsTask('phpstan', namespace: 'qa')]
function qa_phpstan()
{
    phpstan(['analyze', __DIR__ . '/src'], version: '1.11.0');
}
```

## Provided tools

* [PHPStan](https://phpstan.org/): `Castor\PHPQa\phpstan()` function
* [Psalm](https://psalm.dev/): `Castor\PHPQa\psalm()` function
* [PHP CS Fixer](https://cs.symfony.com/): `Castor\PHPQa\php_cs_fixer()` function


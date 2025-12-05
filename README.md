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
    phpstan();
}
```

### Using a specific version

By default the latest version of the tool is used. However it is recommended to use a specific version
so you don't have different version depending the environment.

You can pass the version as an argument of the function.

```php
<?php

use Castor\Attribute\AsTask;
use function Castor\PHPQa\phpstan;

#[AsTask('phpstan', namespace: 'qa')]
function qa_phpstan()
{
    phpstan(version: '1.11.0');
}
```

### Extra dependencies

Some tools may require extra dependencies to be installed. You can pass them as an argument of the function.

```php
<?php

use Castor\Attribute\AsTask;
use function Castor\PHPQa\php_cs_fixer;

#[AsTask('php_cs_fixer', namespace: 'qa')]
function qa_php_cs_fixer()
{
    php_cs_fixer(extraDependencies: [
        'kubawerlos/php-cs-fixer-custom-fixers' => '^3.21',
    ]);
}
```

## Provided tools

* [PHPStan](https://phpstan.org/): `Castor\PHPQa\phpstan()` function
* [PHP CS Fixer](https://cs.symfony.com/): `Castor\PHPQa\php_cs_fixer()` function
* [Rector](https://getrector.org/): `Castor\PHPQa\rector()` function
* [Twig Cs Fixer](https://twigcsfixer.github.io/): `Castor\PHPQa\twig_cs_fixer()` function

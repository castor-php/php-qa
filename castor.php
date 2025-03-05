<?php

namespace Castor\PHPQa\Castor;

use Castor\Attribute\AsTask;

use function Castor\PHPQa\php_cs_fixer;
use function Castor\PHPQa\phpstan;
use function Castor\PHPQa\psalm;

require 'src/functions.php';

#[AsTask('phpstan', namespace: 'qa')]
function qa_phpstan()
{
    phpstan();
}

#[AsTask('php-cs-fixer', namespace: 'qa')]
function qa_php_cs_fixer()
{
    php_cs_fixer([]);
}

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
    phpstan(['analyze', __DIR__ . '/src'], version: '2.1.6');
}

#[AsTask('php-cs-fixer', namespace: 'qa')]
function qa_php_cs_fixer()
{
    php_cs_fixer(['fix', __DIR__]);
}

#[AsTask('psalm', namespace: 'qa')]
function qa_psalm()
{
    psalm(['fix', __DIR__]);
}

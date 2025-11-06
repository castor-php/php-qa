<?php

namespace Castor\PHPQa\Castor;

use Castor\Attribute\AsRawTokens;
use Castor\Attribute\AsTask;

use function Castor\PHPQa\php_cs_fixer;
use function Castor\PHPQa\phpstan;

#[AsTask('phpstan', namespace: 'qa')]
function qa_phpstan(string $phpstanVersion = '*', #[AsRawTokens] array $rawTokens = [])
{
    $args = empty($rawTokens) ? ['analyze', 'src'] : $rawTokens;
    phpstan($args, version: $phpstanVersion);
}

#[AsTask('php-cs-fixer', namespace: 'qa')]
function qa_php_cs_fixer(string $csFixerVersion = '*', #[AsRawTokens] array $rawTokens = [])
{
    $args = empty($rawTokens) ? ['fix', 'src'] : $rawTokens;
    php_cs_fixer($args, version: $csFixerVersion);
}

<?php

namespace Castor\PHPQa\Castor;

use Castor\Attribute\AsRawTokens;
use Castor\Attribute\AsTask;

use function Castor\guard_min_version;
use function Castor\PHPQa\php_cs_fixer;
use function Castor\PHPQa\phpstan;
use function Castor\PHPQa\rector;
use function Castor\PHPQa\twig_cs_fixer;

guard_min_version('1.1.0');

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

#[AsTask('rector', namespace: 'qa')]
function qa_rector(string $rectorVersion = '*', #[AsRawTokens] array $rawTokens = [])
{
    $args = empty($rawTokens) ? null : $rawTokens;
    rector($args, version: $rectorVersion);
}

#[AsTask('twig-cs-fixer', namespace: 'qa')]
function qa_twig_cs_fixer(string $csFixerVersion = '*', #[AsRawTokens] array $rawTokens = [])
{
    $args = empty($rawTokens) ? ['fix', 'src'] : $rawTokens;
    twig_cs_fixer($args, version: $csFixerVersion);
}

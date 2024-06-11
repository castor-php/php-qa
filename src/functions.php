<?php

namespace Castor\PHPQa;

use Psr\Cache\CacheItemInterface;

use function Castor\context;
use function Castor\http_request;
use function Castor\cache;
use function Castor\run_phar;

function download_phar_from_github(string $name, string $repo, string $version = 'latest'): string
{
    if ($version === 'latest') {
        // get latest release from github
        $version = cache($name . '-latest-version', function (CacheItemInterface $item) use ($repo) {
            $item->expiresAfter(3600);

            $response = http_request('GET', "https://api.github.com/repos/{$repo}/releases/latest", [
                'headers' => [
                    'Accept' => 'application/vnd.github.v3+json',
                ],
            ]);

            $content = $response->toArray();

            return $content['tag_name'];
        });
    }

    // Check if phar exists
    $phar = sprintf($name . '-%s.phar', $version);
    $pharPath = context()->workingDirectory . '/.castor/bin/' . $phar;

    if (!file_exists($pharPath)) {
        // Download phar
        $pharContent = file_get_contents("https://github.com/{$repo}/releases/download/{$version}/{$name}.phar");

        if (!is_dir(dirname($pharPath))) {
            mkdir(dirname($pharPath), 0755, true);
        }

        file_put_contents($pharPath, $pharContent);
    }

    return $pharPath;
}

function phpstan(array $arguments, string $version = 'latest')
{
    $pharPath = download_phar_from_github('phpstan', 'phpstan/phpstan', $version);

    return run_phar($pharPath, $arguments);
}

function php_cs_fixer(array $arguments, string $version = 'latest')
{
    $pharPath = download_phar_from_github('php-cs-fixer', 'PHP-CS-Fixer/PHP-CS-Fixer', $version);

    return run_phar($pharPath, $arguments);
}

function psalm(array $arguments, string $version = 'latest')
{
    $pharPath = download_phar_from_github('psalm', 'vimeo/psalm', $version);

    return run_phar($pharPath, $arguments);
}

<?php

namespace Castor\PHPQa;

use Castor\Import\Remote\ComposerApplication;
use http\Exception\RuntimeException;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Console\Input\ArgvInput;

use function Castor\context;
use function Castor\fingerprint;
use function Castor\hasher;
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

function create_tools(string $name, array $dependencies = [])
{
    $toolsDirectory = context()->workingDirectory . '/.castor/tools/' . $name;
    $composerFile = $toolsDirectory . '/composer.json';

    if (!is_dir($toolsDirectory)) {
        mkdir($toolsDirectory, 0755, true);
    }

    // create composer json
    $composerJson = json_encode([
        'name' => 'tools/' . $name,
        'require' => $dependencies
    ]);

    fingerprint(
        callback: function () use ($composerFile, $composerJson) {
            file_put_contents($composerFile, $composerJson);

            composer(['update'], $composerFile);
        },
        id: 'tools-' . $name,
        fingerprint: hasher()->write($composerJson)->write(file_exists($composerFile))->finish(),
    );

    return $toolsDirectory;
}

function phpstan(array $arguments, string $version = '*', array $extraDependencies = [])
{
    $phpstanDirectory = create_tools('phpstan', [
        'phpstan/phpstan' => $version,
        ...$extraDependencies,
    ]);

    $binaryPath = $phpstanDirectory . '/vendor/bin/phpstan';

    return run_phar($binaryPath, $arguments);
}

function php_cs_fixer(array $arguments, string $version = '*', array $extraDependencies = [])
{
    $phpstanDirectory = create_tools('php-cs-fixer', [
        'friendsofphp/php-cs-fixer' => $version,
        ...$extraDependencies,
    ]);

    $binaryPath = $phpstanDirectory . '/vendor/bin/php-cs-fixer';

    return run_phar($binaryPath, $arguments);
}

function psalm(array $arguments, string $version = 'latest')
{
    $pharPath = download_phar_from_github('psalm', 'vimeo/psalm', $version);

    return run_phar($pharPath, $arguments);
}

function composer(array $arguments, $composerJsonFilePath)
{
    $output = \Castor\output();
    $args[] = '--working-dir';
    $args[] = \dirname($composerJsonFilePath);
    $args[] = '--no-interaction';

    putenv('COMPOSER=' . $composerJsonFilePath);
    $_ENV['COMPOSER'] = $composerJsonFilePath;
    $_SERVER['COMPOSER'] = $composerJsonFilePath;
    $argvInput = new ArgvInput(['composer', ...$args, ...$arguments]);

    $composerApplication = new ComposerApplication();
    $composerApplication->setAutoExit(false);
    $exitCode = $composerApplication->run($argvInput, $output);

    if (0 !== $exitCode) {
        throw new RuntimeException('The Composer process failed');
    }

    putenv('COMPOSER=');
    unset($_ENV['COMPOSER'], $_SERVER['COMPOSER']);
}

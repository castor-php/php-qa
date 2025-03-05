<?php

namespace Castor\PHPQa;

use Castor\Import\Remote\ComposerApplication;
use http\Exception\RuntimeException;
use Symfony\Component\Console\Input\ArgvInput;

use function Castor\context;
use function Castor\fingerprint;
use function Castor\hasher;
use function Castor\output;
use function Castor\run_php;

function create_tools(string $name, array $dependencies = [])
{
    $toolsDirectory = context()->workingDirectory . '/.castor/vendor/.tools/' . $name;
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

function phpstan(?array $arguments = null, string $version = '*', array $extraDependencies = [])
{
    if (!$arguments) {
        $arguments = ['analyze', context()->workingDirectory];
    }

    $phpstanDirectory = create_tools('phpstan', [
        'phpstan/phpstan' => $version,
        ...$extraDependencies,
    ]);

    $binaryPath = $phpstanDirectory . '/vendor/bin/phpstan';

    return run_php($binaryPath, $arguments);
}

function php_cs_fixer(?array $arguments = null, string $version = '*', array $extraDependencies = [], bool $dryRun = false, bool $diff = false)
{
    if (null === $arguments) {
        $arguments = ['fix', context()->workingDirectory . '/src'];

        if ($dryRun) {
            $arguments[] = '--dry-run';
        }

        if ($diff) {
            $arguments[] = '--diff';
        }
    }

    $phpstanDirectory = create_tools('php-cs-fixer', [
        'friendsofphp/php-cs-fixer' => $version,
        ...$extraDependencies,
    ]);

    $binaryPath = $phpstanDirectory . '/vendor/bin/php-cs-fixer';

    return run_php($binaryPath, $arguments);
}

function composer(array $arguments, $composerJsonFilePath)
{
    $output = output();
    $args[] = '--working-dir';
    $args[] = \dirname($composerJsonFilePath);
    $args[] = '--no-interaction';

    $argvInput = new ArgvInput(['composer', ...$args, ...$arguments]);

    $composerApplication = new ComposerApplication();
    $composerApplication->setAutoExit(false);
    $exitCode = $composerApplication->run($argvInput, $output);

    if (0 !== $exitCode) {
        throw new RuntimeException('The Composer process failed');
    }
}

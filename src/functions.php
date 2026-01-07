<?php

namespace Castor\PHPQa;

use Castor\Console\Output\VerbosityLevel;
use Castor\Import\Remote\ComposerApplication;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

use function Castor\context;
use function Castor\fingerprint;
use function Castor\hasher;
use function Castor\output;
use function Castor\run_php;

/**
 * @param list<string> $arguments
 * @param array<string, string> $extraDependencies
 */
function phpstan(?array $arguments = null, string $version = '*', array $extraDependencies = []): Process
{
    if (null === $arguments) {
        $arguments = ['analyze', context()->workingDirectory];
    }

    $directory = create_tools('phpstan', [
        'phpstan/phpstan' => $version,
        ...$extraDependencies,
    ]);

    $binaryPath = $directory . '/vendor/bin/phpstan';

    return run_php($binaryPath, $arguments);
}

/**
 * @param list<string> $arguments
 * @param array<string, string> $extraDependencies
 */
function php_cs_fixer(?array $arguments = null, string $version = '*', array $extraDependencies = [], bool $dryRun = false, bool $diff = false): Process
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

    $directory = create_tools('php-cs-fixer', [
        'friendsofphp/php-cs-fixer' => $version,
        ...$extraDependencies,
    ]);

    $binaryPath = $directory . '/vendor/bin/php-cs-fixer';

    return run_php($binaryPath, $arguments);
}

/**
 * @param list<string> $arguments
 * @param array<string, string> $extraDependencies
 */
function rector(?array $arguments = null, string $version = '*', array $extraDependencies = [], bool $dryRun = false): Process
{
    if (null === $arguments) {
        $arguments = [context()->workingDirectory . '/src'];

        if ($dryRun) {
            $arguments[] = '--dry-run';
        }
    }

    $directory = create_tools('rector', [
        'rector/rector' => $version,
        ...$extraDependencies,
    ]);

    $binaryPath = $directory . '/vendor/bin/rector';

    return run_php($binaryPath, $arguments);
}

/**
 * @param list<string> $arguments
 * @param array<string, string> $extraDependencies
 */
function twig_cs_fixer(?array $arguments = null, string $version = '*', array $extraDependencies = []): Process
{
    if (null === $arguments) {
        $arguments = ['fix', context()->workingDirectory . '/src'];
    }

    $directory = create_tools('twig-cs-fixer', [
        'vincentlanglet/twig-cs-fixer' => $version,
        ...$extraDependencies,
    ]);

    $binaryPath = $directory . '/vendor/bin/twig-cs-fixer';

    return run_php($binaryPath, $arguments);
}

/**
 * @param array<string, string> $dependencies A list of composer dependencies to require
 * @return string
 */
function create_tools(string $name, array $dependencies = []): string
{
    $toolsDirectory = context()->workingDirectory . '/.castor/vendor/.tools/' . $name;
    $composerFile = $toolsDirectory . '/composer.json';

    if (!is_dir($toolsDirectory)) {
        mkdir($toolsDirectory, 0755, true);
    }

    $allowPlugins = [];

    foreach ($dependencies as $package => $version) {
        $allowPlugins[$package] = true;
    }

    // create composer json
    $composerJson = json_encode([
        'name' => 'tools/' . $name,
        'require' => $dependencies,
        'config' => [
            'allow-plugins' => $allowPlugins,
        ],
    ], JSON_THROW_ON_ERROR);

    fingerprint(
        callback: function () use ($name, $composerFile, $composerJson): void {
            file_put_contents($composerFile, $composerJson);

            composer($name, ['update'], $composerFile);
        },
        id: 'tools-' . $name,
        fingerprint: hasher()->write((string) $composerJson)->finish(),
        force: !file_exists($composerFile),
    );

    return $toolsDirectory;
}

/**
 * @param list<string> $arguments
 */
function composer(string $name, array $arguments, string $composerJsonFilePath): void
{
    $currentOutput = output();
    $progressIndicator = null;

    if (context()->verbosityLevel->value <= VerbosityLevel::NORMAL->value) {
        $progressIndicator = new ProgressIndicator($currentOutput, null, 100, ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇']);
        $progressIndicator->start('<comment>Installing/Updating ' . $name . '...</comment>');

        $output = new class ($progressIndicator) extends Output {
            public function __construct(
                private readonly ProgressIndicator $progressIndicator,
            ) {
                parent::__construct(OutputInterface::VERBOSITY_VERY_VERBOSE);
            }

            public function doWrite(string $message, bool $newline): void
            {
                $this->progressIndicator->advance();
            }
        };
    } else {
        $output = $currentOutput;
    }

    $args[] = '--working-dir';
    $args[] = \dirname($composerJsonFilePath);
    $args[] = '--no-interaction';

    $_SERVER['COMPOSER_VENDOR_DIR'] = \dirname($composerJsonFilePath) . '/vendor';

    $argvInput = new ArgvInput(['composer', ...$args, ...$arguments]);

    $composerApplication = new ComposerApplication();
    $composerApplication->setAutoExit(false);
    $exitCode = $composerApplication->run($argvInput, $output);

    unset($_SERVER['COMPOSER_VENDOR_DIR']);

    if (0 !== $exitCode) {
        throw new \RuntimeException('The Composer process failed');
    }

    $progressIndicator?->finish('<comment>' . $name . ' installed.</comment>');

}

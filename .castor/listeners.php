<?php

declare(strict_types=1);

use Castor\Attribute\AsListener;
use Castor\Event\AfterExecuteTaskEvent;
use Castor\Event\BeforeExecuteTaskEvent;
use Symfony\Component\Process\ExecutableFinder;

use function Castor\context;
use function Castor\finder;
use function Castor\fingerprint_exists;
use function Castor\fs;
use function Castor\io;
use function Castor\request;
use function TheoD02\Castor\Docker\docker;

#[AsListener(BeforeExecuteTaskEvent::class, priority: 1000)]
function check_tool_deps(BeforeExecuteTaskEvent $event): void
{
    if ((new ExecutableFinder())->find('docker') === null) {
        io()->error(['Docker is required for running this application', 'Check documentation: https://docs.docker.com/engine/install']);
        exit(1);
    }
}

#[AsListener(BeforeExecuteTaskEvent::class, priority: 900)]
function check_docker_is_running(BeforeExecuteTaskEvent $event): void
{
    if (in_array($event->task->getName(), ['start', 'stop', 'restart'], true)) {
        return;
    }

    $context = context()->withQuiet();
    if (str_contains(docker($context)->compose()->ps()->getOutput(), 'franken-base-app-1') === false) {
        io()->note('Docker containers are not running. Starting them.');
        start();
    }
}

#[AsListener(BeforeExecuteTaskEvent::class, priority: 850)]
#[AsListener(AfterExecuteTaskEvent::class, priority: 800)]
function check_symfony_installation(BeforeExecuteTaskEvent|AfterExecuteTaskEvent $event): void
{
    if ($event instanceof BeforeExecuteTaskEvent && in_array($event->task->getName(), ['start'], true)) {
        return;
    }

    $response = request('GET', 'https://symfony.com/releases.json')->toArray();
    $versions = [
        substr($response['symfony_versions']['stable'], 0, 3) => 'Latest Stable',
        substr($response['symfony_versions']['lts'], 0, 3) => 'Latest LTS',
        substr($response['symfony_versions']['next'], 0, 3) => 'Next',
    ];
    $mapping = [
        substr($response['symfony_versions']['stable'], 0, 3) => substr($response['symfony_versions']['stable'], 0, 3) . '.*',
        substr($response['symfony_versions']['lts'], 0, 3) => substr($response['symfony_versions']['lts'], 0, 3) . '.*',
        substr($response['symfony_versions']['next'], 0, 3) => substr($response['symfony_versions']['next'], 0, 3) . '.*-dev',
    ];

    $diff = array_diff($response['maintained_versions'], array_keys($versions));

    foreach ($diff as $version) {
        $versions[$version] = "{$version} Maintained";
        $mapping[$version] = $version . '.*';
    }

    ksort($versions);

    $destination = default_context()->workingDirectory . '/app';
    if (is_file("{$destination}/composer.json") === false) {
        io()->newLine();
        io()->warning('Symfony seems not to be installed.');

        if (io()->confirm('Do you want to install it now?') === false) {
            return;
        }

        $version = io()->choice('Choose Symfony version', $versions, 'Latest Stable');
        $version = $mapping[$version];
        composer()->add('create-project', "symfony/skeleton:{$version} /app/sf-temp")->runCommand();

        $tempDestination = "{$destination}/sf-temp";
        io()->newLine();
        io()->note('Copying files to the destination directory.');
        fs()->mirror($tempDestination, $destination);

        io()->note('Removing temporary directory.');
        fs()->remove($tempDestination);
    }
}

#[AsListener(BeforeExecuteTaskEvent::class, priority: 800)]
#[AsListener(AfterExecuteTaskEvent::class, priority: 800)]
function check_projects_deps(BeforeExecuteTaskEvent|AfterExecuteTaskEvent $event): void
{
    if ($event instanceof BeforeExecuteTaskEvent && in_array($event->task->getName(), ['start', 'stop', 'restart', 'install'], true)) {
        return;
    }

    if ($event->task->getName() === 'install') {
        return;
    }

    $deps = [];

    if (is_file(default_context()->workingDirectory . '/composer.json')) {
        $deps['Composer'] = default_context()->workingDirectory . '/vendor';
    }

    if (
        is_file(default_context()->workingDirectory . '/package.json')
        || is_file(default_context()->workingDirectory . '/yarn.lock')
    ) {
        $deps['Node Modules'] = default_context()->workingDirectory . '/node_modules';
    }

    $qaToolsDirectories = finder()
        ->directories()
        ->in(qa_context()->workingDirectory)
        ->depth(0)
        ->notName(['bin', 'k6'])
        ->sortByName()
    ;

    foreach ($qaToolsDirectories as $directory) {
        $deps["QA - {$directory->getFilename()}"] = $directory->getPathname() . '/vendor';
    }

    $missingDeps = [];

    foreach ($deps as $depName => $path) {
        if (is_dir($path) === false) {
            $missingDeps[] = $depName;
        }
    }

    if ($missingDeps !== []) {
        io()->newLine();
        io()->error('Some dependencies are missing:');
        io()->listing($missingDeps);

        if (io()->confirm('Do you want to install them now?') === false) {
            io()->note('Run `castor install` to install them.');
            exit(1);
        }

        install();
    }

    // Check if deps is latest
    $outdatedDeps = [];

    if (fingerprint_exists(fgp()->npm()) === false) {
        $outdatedDeps[] = 'Node Modules';
    }

    if (fingerprint_exists(fgp()->composer()) === false) {
        $outdatedDeps[] = 'Composer';
    }

    if ($outdatedDeps !== []) {
        io()->newLine();
        io()->error('Some dependencies are outdated:');
        io()->listing($outdatedDeps);

        if (io()->confirm('Do you want to install them now?') === false) {
            io()->note('Run `castor install` to install them.');
            exit(1);
        }

        install();
    }
}

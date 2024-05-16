<?php

declare(strict_types=1);

use function Castor\finder;
use function Castor\hasher;
use function utils\path;

class Fingerprint
{
    public function php_docker(): string
    {
        return hasher()
            ->writeWithFinder(finder()->files()->in(path('.docker/php')))
            ->finish()
        ;
    }

    public function composer(): string
    {
        return hasher()
            ->writeWithFinder(finder()->files()->in(path('app'))->name(['composer.json', 'composer.lock', 'symfony.lock']))
            ->finish()
        ;
    }

    public function npm(): string
    {
        return hasher()
            ->writeWithFinder(finder()->files()->in(path('app'))->name(['package.json']))
            ->finish()
        ;
    }
}

function fgp(): Fingerprint
{
    return new Fingerprint();
}

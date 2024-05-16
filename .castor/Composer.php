<?php

declare(strict_types=1);

use Castor\Context;
use Symfony\Component\Process\Process;
use TheoD02\Castor\Classes\AsTaskClass;
use TheoD02\Castor\Classes\AsTaskMethod;
use TheoD02\Castor\Docker\RunnerTrait;

use function Castor\context;

#[AsTaskClass]
class Composer
{
    use RunnerTrait {
        RunnerTrait::__construct as private __runnerConstruct;
    }

    public function __construct(?Context $context = null)
    {
        $this->__runnerConstruct($context ?? context());
    }

    protected function getBaseCommand(): ?string
    {
        return 'composer';
    }

    protected function allowRunningUsingDocker(): bool
    {
        return true;
    }

    #[AsTaskMethod]
    public function install(): Process
    {
        return $this->add('install')->runCommand();
    }

    #[AsTaskMethod]
    public function update(): Process
    {
        return $this->add('update')->runCommand();
    }
}

function composer(?Context $context = null): Composer
{
    return new Composer($context);
}

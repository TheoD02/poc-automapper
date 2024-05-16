<?php

declare(strict_types=1);

use Castor\Attribute\AsOption;
use Symfony\Component\Process\Process;
use TheoD02\Castor\Classes\AsTaskClass;
use TheoD02\Castor\Classes\AsTaskMethod;
use TheoD02\Castor\Docker\CastorDockerContext;
use TheoD02\Castor\Docker\RunnerTrait;

use function Castor\context;
use function Castor\finder;
use function Castor\fingerprint;
use function Castor\fs;
use function Castor\hasher;
use function Castor\io;

#[AsTaskClass]
class Qa
{
    use RunnerTrait {
        __construct as private __runnerTraitConstruct;
    }

    private static bool $runOnce = false;

    public function __construct()
    {
        $this->__runnerTraitConstruct(qa_context());
    }

    protected function allowRunningUsingDocker(): bool
    {
        return true;
    }

    private function preRunCommand(): void
    {
        $this->install();
    }

    #[AsTaskMethod(aliases: ['qa:update'])]
    public function install(): void
    {
        if (self::$runOnce) {
            return;
        }

        $tools = finder()
            ->directories()
            ->in(qa_context()->workingDirectory)
            ->notName(['bin', 'k6'])
            ->depth(0)
        ;

        io()->writeln('Checking tools installation');
        foreach ($tools as $tool) {
            $toolDirectory = $tool->getPathname();
            io()->write("{$toolDirectory}...");
            if (! fs()->exists("{$toolDirectory}/composer.json")) {
                io()->error("The tool {$toolDirectory} does not contain a composer.json file");
                exit(1);
            }

            $needForceInstall = fs()->exists("{$toolDirectory}/vendor") === false;

            /** @var CastorDockerContext $composerDockerContext */
            $composerDockerContext = context()->data['docker']['composer'] ?? context()->data['docker']['default'];
            $composerDockerContext->workdir = '/tools/' . $tool->getFilename();
            $context = qa_context()
                ->withQuiet()
                ->withData([
                    'docker' => [
                        'composer' => $composerDockerContext,
                    ],
                ])
            ;

            fingerprint(
                callback: static function () use ($context) {
                    io()->write(' Installing...');
                    composer($context)->install();
                },
                fingerprint: hasher()
                    ->writeFile("{$toolDirectory}/composer.json")
                    ->writeFile("{$toolDirectory}/composer.lock")
                    ->finish(),
                force: $needForceInstall
            );
            io()->writeln(' <info>OK</info>');
        }
        io()->newLine();

        self::$runOnce = true;
    }

    #[AsTaskMethod(aliases: ['qa:update'])]
    public function update(string $tool = '', bool $all = false): void
    {
        if ($tool === '' && ! $all) {
            io()->error('You must specify a tool to update or use the --all option');
            exit(1);
        }

        if ($all) {
            $tools = finder()
                ->directories()
                ->in(qa_context()->workingDirectory)
                ->notName(['bin', 'k6'])
                ->depth(0)
            ;
        } else {
            $tools = finder()
                ->directories()
                ->in(qa_context()->workingDirectory)
                ->name($tool)
                ->depth(0)
            ;
        }

        foreach ($tools as $tool) {
            $toolDirectory = $tool->getPathname();
            io()->write("{$toolDirectory}...");
            if (! fs()->exists("{$toolDirectory}/composer.json")) {
                io()->error("The tool {$toolDirectory} does not contain a composer.json file");
                exit(1);
            }

            /** @var CastorDockerContext $composerDockerContext */
            $composerDockerContext = context()->data['docker']['composer'] ?? context()->data['docker']['default'];
            $composerDockerContext->workdir = '/tools/' . $tool->getFilename();
            $context = qa_context()
                ->withQuiet()
                ->withData([
                    'docker' => [
                        'composer' => $composerDockerContext,
                    ],
                ])
            ;

            composer($context)->update();
            io()->writeln(' <info>OK</info>');
        }
    }

    #[AsTaskMethod]
    public function ecs(#[AsOption(description: 'Fix the issues')] bool $fix = false): Process
    {
        $this->add('ecs', 'check', '--clear-cache', '--ansi', '--config', '/tools/ecs.php');

        $this->addIf($fix, '--fix');

        return $this->runCommand();
    }

    #[AsTaskMethod]
    public function phpstan(): Process
    {
        $this->add('phpstan', 'clear-result-cache')->runCommand();

        return $this
            ->add('phpstan', 'analyse', '--level=8', '--configuration', '/tools/phpstan.neon', '--memory-limit=1G')
            ->runCommand()
        ;
    }

    #[AsTaskMethod]
    public function rector(#[AsOption(description: 'Fix the issues')] bool $fix = false): Process
    {
        $this->add('rector', 'process', '--clear-cache', '--config', '/tools/rector.php');

        $this->addIf(! $fix, '--dry-run');

        return $this->runCommand();
    }

    #[AsTaskMethod(aliases: ['qa:arki'])]
    public function phparkitect(): Process
    {
        return $this
            ->add('phparkitect', 'check', '--ansi', '--config', '/tools/phparkitect.php')
            ->runCommand()
        ;
    }

    #[AsTaskMethod(aliases: ['qa:phpmd'])]
    public function phpmd(): Process
    {
        $process = $this
            ->add('phpmd', '/app/src', 'text', 'codesize')
            ->runCommand()
        ;

        io()->success('PHPMD has been executed successfully');

        return $process;
    }

    #[AsTaskMethod]
    public function preCommit(): void
    {
        io()->title('Running QA tools - Pre-commit hook');

        io()->section('Running ECS');
        $this->ecs(fix: true);

        io()->section('Running Rector');
        $this->rector(fix: true);

        io()->section('Running PHPStan');
        $this->phpstan();

        io()->section('Running PHParkitect');
        $this->phparkitect();

        io()->section('Running PHPMD');
        $this->phpmd();
    }
}

function qa(): Qa
{
    return new Qa();
}

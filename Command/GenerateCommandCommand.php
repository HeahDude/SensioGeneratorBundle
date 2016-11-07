<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sensio\Bundle\GeneratorBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Sensio\Bundle\GeneratorBundle\Generator\CommandGenerator;

/**
 * Generates commands.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class GenerateCommandCommand extends GeneratorCommand
{
    /**
     * @see Command
     */
    public function configure()
    {
        $this
            ->setName('generate:command')
            ->setDescription('Generates a console command')
            ->setDefinition(array(
                new InputArgument('bundle', InputArgument::OPTIONAL, 'The bundle where the command is generated'),
                new InputArgument('name', InputArgument::OPTIONAL, 'The command\'s name (e.g. app:my-command)'),
            ))
            ->setHelp(<<<EOT
The <info>generate:command</info> command helps you generate new commands
inside bundles. Provide the bundle name as the first argument and the command
name as the second argument:

<info>php app/console generate:command App blog:publish-posts</info>

If any of the arguments is missing, the command will ask for their values interactively. 
To deactivate the interaction mode, simply use the <comment>--no-interaction</comment> option or its
alias <comment>-n</comment>, without forgetting to pass all needed options:

Every generated file is based on a template. There are default templates but they can
be overridden by placing custom templates in one of the following locations, by order of priority:

<info>BUNDLE_PATH/Resources/SensioGeneratorBundle/skeleton/command
APP_PATH/Resources/SensioGeneratorBundle/skeleton/command</info>

You can check https://github.com/sensio/SensioGeneratorBundle/tree/master/Resources/skeleton
in order to know the file structure of the skeleton.
EOT
            )
        ;
    }

    public function interact(InputInterface $input, OutputInterface $output)
    {
        $io = $this->getStyle($input, $output);
        $bundle = $this->addBundleSuffix($input->getArgument('bundle'));
        $name = $input->getArgument('name');

        $io->title('Welcome to the Symfony command generator');

        // bundle
        if (!empty($bundle)) {
            $io->text(sprintf('Bundle name: <info>%s</info>', $bundle));
        } else {
            $io->text(array(
                'First, you need to give the name of the bundle where the command will be generated',
                '(e.g. <comment>App</comment>), it will be suffixed by <comment>Bundle</comment> automatically.',
                '',
            ));

            $bundleNames = array_map(function ($b) {
                return substr($b, 0, -6);
            }, array_keys($this->getKernel()->getBundles()));
            $bundle = $io->askWithCompletion('Bundle name', $bundleNames, 'App', false);
        }
        $input->setArgument('bundle', $this->addBundleSuffix($bundle));

        // command name
        if (null !== $name) {
            $io->text(sprintf('Command name: <info>%s</info>', $name));
        } else {
            $io->text(array(
                'Now, provide the name of the command as you type it in the console',
                '(e.g. <comment>app:my-command</comment>)',
                '',
            ));

            $name = $io->ask('Command name', $name, function ($answer) {
                if (empty($answer)) {
                    throw new \RuntimeException('The command name cannot be empty.');
                }

                return $answer;
            });
            $input->setArgument('name', $name);
        }

        if (!$io->confirm(sprintf('You are going to generate a <info>%s</info> command inside <info>%s</info> bundle.', $name, $bundle))) {
            $io->error('Command aborted');

            return 1;
        }
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = $this->getStyle($input, $output);
        $bundle = $this->addBundleSuffix($input->getArgument('bundle'));
        $name = $input->getArgument('name');

        try {
            $bundle = $this->getKernel()->getBundle($bundle);
        } catch (\Exception $e) {
            $io->error(sprintf('Bundle "%s" does not exist.', $bundle));

            return 1;
        }

        /** @var CommandGenerator $generator */
        $generator = $this->getGenerator($bundle);
        $generator->generate($bundle, $name);

        $io->text(sprintf('Generated the <info>%s</info> command in <info>%s</info>', $name, $bundle->getName()));
        $io->summary();
    }

    protected function createGenerator()
    {
        return new CommandGenerator();
    }
}

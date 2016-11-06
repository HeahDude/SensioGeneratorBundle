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

use Sensio\Bundle\GeneratorBundle\Manipulator\ConfigurationManipulator;
use Sensio\Bundle\GeneratorBundle\Model\Bundle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Sensio\Bundle\GeneratorBundle\Generator\BundleGenerator;
use Sensio\Bundle\GeneratorBundle\Manipulator\KernelManipulator;
use Sensio\Bundle\GeneratorBundle\Manipulator\RoutingManipulator;

/**
 * Generates bundles.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jules Pietri <jules@heahprod.com>
 */
class GenerateBundleCommand extends GeneratorCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('generate:bundle')
            ->setDescription('Generates a bundle')
            ->setDefinition(array(
                new InputArgument('namespace', InputArgument::OPTIONAL, 'The namespace of the bundle to create'),
                new InputOption('namespace', '', InputOption::VALUE_OPTIONAL, 'The namespace of the bundle to create'),
                new InputOption('dir', '', InputOption::VALUE_OPTIONAL, 'The directory where to create the bundle', 'src'),
                new InputOption('bundle-name', '', InputOption::VALUE_OPTIONAL, 'The optional bundle name'),
                new InputOption('format', '', InputOption::VALUE_OPTIONAL, 'Use the format for configuration files (php, xml, yml, or annotation)'),
                new InputOption('shared', '', InputOption::VALUE_NONE, 'Are you planning on sharing this bundle across multiple applications?'),
            ))
            ->setHelp(<<<EOT
The <info>%command.name%</info> command helps you generates new bundles.

By default, the command interacts with the developer to tweak the generation.
Any passed option will be used as a default value for the interaction
(<comment>namespace</comment> argument is the only one needed if you follow the conventions):

<info>php %command.full_name% Acme/Blog</info>

Note that you can use <comment>/</comment> instead of <comment>\\\\</comment> for the namespace
delimiter to avoid any problems.

To deactivate the interaction mode, simply use the <comment>--no-interaction</comment> option or its
alias <comment>-n</comment>, without forgetting to pass the required argument:

<info>php %command.full_name% -n Acme/Blog</info>

Note that the bundle namespace will be suffixed with "Bundle" and the generated bundle
will be "src/Acme/BlogBundle/AcmeBlogBundle".
EOT
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bc = __CLASS__ !== get_class($this);
        $io = $this->getStyle($input, $output);
        $kernel = $this->getKernel();
        $rootDir = $this->getKernelRootDir();
        $bundle = $this->createBundleObject($input);

        $io->section('Bundle generation');

        /** @var BundleGenerator $generator */
        $generator = $this->getGenerator();

        $io->text(sprintf('> Generating a sample bundle skeleton into <info>%s</info>', $this->makePathRelative($bundle->getTargetDirectory())));

        $generator->generateBundle($bundle);

        $errors = array();
        if ($bc) {
            $this->checkAutoloader($output, $bundle);
        } else {
            $io->test('> Checking that the bundle is autoloaded', $this->tryAutoload($bundle), $errors);
        }

        // register the bundle in the Kernel class
        if ($bc) {
            $this->updateKernel($output, $kernel, $bundle);
        } else {
            $kernelManipulator = new KernelManipulator($kernel);
            $io->test(
                sprintf('> Enabling the bundle inside <info>%s</info>', $this->makePathRelative($kernelManipulator->getFilename())),
                $this->tryUpdateKernel($kernelManipulator, $kernel, $bundle),
                $errors
            );
        }

        // routing importing
        if ($bc) {
            $this->updateRouting($output, $bundle);
        } else {
            $targetRoutingPath = $rootDir.'/config/routing.yml';
            $io->test(
                sprintf('> Importing the bundle\'s routes from the <info>%s</info> file', $this->makePathRelative($targetRoutingPath)),
                $this->tryUpdateRouting($targetRoutingPath, $bundle),
                $errors
            );
        }

        if (!$bundle->shouldGenerateDependencyInjectionDirectory()) {
            $targetConfigurationPath = $rootDir.'/config/config.yml';
            // we need to import their services.yml manually!
            if ($bc) {
                $this->updateConfiguration($output, $bundle);
            } else {
                $io->test(
                    sprintf(
                        '> Importing the bundle\'s %s from the <info>%s</info> file',
                        $bundle->getServicesConfigurationFilename(),
                        $this->makePathRelative($targetConfigurationPath)
                    ),
                    $this->tryUpdateConfiguration($targetConfigurationPath, $bundle),
                    $errors
                );
            }
        }

        $io->summary($errors);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = $this->getStyle($input, $output);

        $io->title('Welcome to the Symfony bundle generator');

        /*
         * shared option
         */
        $shared = $io->confirm('Are you planning on sharing this bundle across multiple applications?', $input->getOption('shared'));
        $input->setOption('shared', $shared);

        /*
         * namespace option
         */
        $io->text(array(
            'Your application code must be written in <comment>bundles</comment>. This command helps you generate them easily.',
            '',
        ));

        $askForBundleName = true;
        if ($input->getOption('namespace')) {
            @trigger_error("Using the namespace option is deprecated since version 3.2 and will be removed in 4.0. Pass the namespace as first argument instead.", E_USER_DEPRECATED);
            $input->setArgument('namespace', $input->getOption('namespace'));
            $input->setOption('namespace', null); // prevent another notice
        }
        $namespace = $this->addBundleSuffix($input->getArgument('namespace'));
        $that = $this;
        if ($shared) {
            // a shared bundle, so it should probably have a vendor namespace
            $io->text(array(
                'Each bundle is hosted under a namespace (like <comment>Acme/Blog</comment>).',
                '',
                'The namespace should begin with a "vendor" name like your company name, your project name,',
                'or your client name, followed by one or more optional category sub-namespaces, and it should end with',
                'the bundle name itself (which will be suffixed with <comment>Bundle</comment>).',
                'If you omit it the suffix will be added automatically.',
                '',
                'See http://symfony.com/doc/current/cookbook/bundles/best_practices.html#bundle-name for more details',
                'on bundle naming conventions.',
                '',
                'Use <comment>/</comment> instead of <comment>\\\\</comment> for the namespace delimiter to avoid any problem.',
            ));

            $namespace = $io->ask('Bundle namespace', $namespace, function ($inputNamespace) use ($that) {
                return Validators::validateBundleNamespace($that->addBundleSuffix($inputNamespace));
            });
        } else {
            // a simple application bundle
            $io->text('Give your bundle a descriptive name, like <comment>Blog</comment> that will be suffixed with <comment>Bundle</comment>.');

            $namespace = $io->ask('Bundle name', $namespace, function ($inputNamespace) use ($that) {
                return Validators::validateBundleNamespace($that->addBundleSuffix($inputNamespace), false);
            });
            if (strpos($namespace, '\\') === false) {
                // this is a bundle name (FooBundle) not a namespace (Acme\FooBundle)
                // so this is the bundle name (and it is also the namespace)
                $input->setOption('bundle-name', $this->addBundleSuffix($namespace));
                $askForBundleName = false;
            }
        }
        $input->setArgument('namespace', $this->addBundleSuffix($namespace));

        /*
         * bundle-name option
         */
        if ($askForBundleName) {
            $bundle = $input->getOption('bundle-name');
            // no bundle yet? Get a default from the namespace
            if (!$bundle) {
                $bundle = strtr($namespace, array('\\Bundle\\' => '', '\\' => ''));
            }

            $io->text(array(
                'In your code, a bundle is often referenced by its name. It can be the concatenation of all namespace',
                'parts but it\'s really up to you to come up with a unique name (a good practice is to start with the vendor name).',
                'Based on the namespace, we suggest <comment>'.$bundle.'</comment>.',
            ));

            $bundle = $io->ask('Bundle name', $bundle, function ($inputName) use ($that) {
                return Validators::validateBundleName($that->addBundleSuffix($inputName));
            });
            $input->setOption('bundle-name', $this->addBundleSuffix($bundle));
        }

        /*
         * dir option
         */
        // defaults to src in the option
        $io->text(array(
            'Bundles are usually generated into the <info>src/</info> directory. Unless you\'re doing something custom,',
            'hit enter to keep this default!',
        ));

        $dir = $io->ask('Target Directory', $input->getOption('dir'));
        $input->setOption('dir', $dir);

        /*
         * format option
         */
        $format = $input->getOption('format');
        if (!$format) {
            $format = $shared ? 'xml' : 'annotation';
        }

        $format = $io->askWithCompletion(
            'What format do you want to use for generated configuration? ("annotation", "yml", "xml", "php")',
            array('annotation', 'yml', 'xml', 'php'),
            $format
            // Validate from choices
        );
        $input->setOption('format', $format);
    }

    /**
     * @deprecated since 3.2, to be removed in 4.0. Use "tryAutoload()" instead.
     */
    protected function checkAutoloader(OutputInterface $output, Bundle $bundle)
    {
        $output->writeln('> Checking that the bundle is autoloaded');

        return $this->tryAutoload($bundle);
    }

    /**
     * @return string[]|null An array of errors, nothing otherwise
     */
    protected function tryAutoload(Bundle $bundle)
    {
        if (!class_exists($bundle->getBundleClassName())) {
            return array(
                '- Edit the <comment>composer.json</comment> file and register the bundle namespace in the "autoload" section:',
            );
        }
    }

    /**
     * @deprecated since 3.2, to be removed in 4.0. Use "tryUpdateKernel()' instead.
     */
    protected function updateKernel(OutputInterface $output, KernelInterface $kernel, Bundle $bundle)
    {
        $kernelManipulator = new KernelManipulator($kernel);

        $output->writeln(sprintf(
            '> Enabling the bundle inside <info>%s</info>',
            $this->makePathRelative($kernelManipulator->getFilename())
        ));

        $this->tryUpdateKernel($kernelManipulator, $kernel, $bundle);
    }

    /**
     * @return string[]|null An array of errors, nothing otherwise
     */
    protected function tryUpdateKernel(KernelManipulator $kernelManipulator, KernelInterface $kernel, Bundle $bundle)
    {
        try {
            $ret = $kernelManipulator->addBundle($bundle->getBundleClassName());

            if (!$ret) {
                $reflected = new \ReflectionObject($kernel);

                return array(
                    sprintf('- Edit <comment>%s</comment>', $reflected->getFilename()),
                    '  and add the following bundle in the <comment>AppKernel::registerBundles()</comment> method:',
                    '',
                    sprintf('    <comment>new %s(),</comment>', $bundle->getBundleClassName()),
                    '',
                );
            }
        } catch (\RuntimeException $e) {
            return array(
                sprintf('Bundle <comment>%s</comment> is already defined in <comment>AppKernel::registerBundles()</comment>.', $bundle->getBundleClassName()),
                '',
            );
        }
    }

    /**
     * @deprecated since 3.2, to be removed in 4.0. Use "tryUpdateRouting()" instead.
     */
    protected function updateRouting(OutputInterface $output, Bundle $bundle)
    {
        $targetRoutingPath = $this->getContainer()->getParameter('kernel.root_dir').'/config/routing.yml';
        $output->writeln(sprintf(
            '> Importing the bundle\'s routes from the <info>%s</info> file',
            $this->makePathRelative($targetRoutingPath)
        ));

        return $this->tryUpdateRouting($targetRoutingPath, $bundle);
    }

    /**
     * @return string[]|null An array of errors, nothing otherwise
     */
    protected function tryUpdateRouting($targetRoutingPath, Bundle $bundle)
    {
        $routing = new RoutingManipulator($targetRoutingPath);
        try {
            $ret = $routing->addResource($bundle->getName(), $bundle->getConfigurationFormat());
            if (!$ret) {
                if ('annotation' === $bundle->getConfigurationFormat()) {
                    $help = sprintf("        <comment>resource: \"@%s/Controller/\"</comment>\n        <comment>type:     annotation</comment>\n", $bundle->getName());
                } else {
                    $help = sprintf("        <comment>resource: \"@%s/Resources/config/routing.%s\"</comment>\n", $bundle->getName(), $bundle->getConfigurationFormat());
                }
                $help .= "        <comment>prefix:   /</comment>\n";

                return array(
                    '- Import the bundle\'s routing resource in the app\'s main routing file:',
                    '',
                    sprintf('    <comment>%s:</comment>', $bundle->getName()),
                    $help,
                    '',
                );
            }
        } catch (\RuntimeException $e) {
            return array(
                sprintf('Bundle <comment>%s</comment> is already imported.', $bundle->getName()),
                '',
            );
        }
    }

    /**
     * @deprecated since 3.2, to be removed in 4.0. Use "tryUpdateConfiguration()" instead.
     */
    protected function updateConfiguration(OutputInterface $output, Bundle $bundle)
    {
        $targetConfigurationPath = $this->getContainer()->getParameter('kernel.root_dir').'/config/config.yml';
        $output->writeln(sprintf(
            '> Importing the bundle\'s %s from the <info>%s</info> file',
            $bundle->getServicesConfigurationFilename(),
            $this->makePathRelative($targetConfigurationPath)
        ));

        return $this->tryUpdateConfiguration($targetConfigurationPath, $bundle);
    }

    /**
     * @return string[]|null Ann array of errors, nothing otherwise
     */
    protected function tryUpdateConfiguration($targetConfigurationPath, Bundle $bundle)
    {
        $manipulator = new ConfigurationManipulator($targetConfigurationPath);
        try {
            $manipulator->addResource($bundle);
        } catch (\RuntimeException $e) {
            return array(
                sprintf('- Import the bundle\'s "%s" resource in the app\'s main configuration file:', $bundle->getServicesConfigurationFilename()),
                '',
                $manipulator->getImportCode($bundle),
                '',
            );
        }
    }

    /**
     * Creates the Bundle object based on the user's (non-interactive) input.
     *
     * @param InputInterface $input
     *
     * @return Bundle
     */
    protected function createBundleObject(InputInterface $input)
    {
        if ($input->getOption('namespace')) {
            @trigger_error("Using the namespace option is deprecated since version 3.2 and will be removed in 4.0. Pass the namespace as first argument instead.", E_USER_DEPRECATED);
            $input->setArgument('namespace', $input->getOption('namespace'));
        }
        if (!$dir = $input->getOption('dir')) {
            throw new \RuntimeException('The "--dir" option must be provided.');
        }
        if (!$namespace = $input->getArgument('namespace')) {
            throw new \RuntimeException('The "namespace" is required as argument or option.');
        }
        $shared = $input->getOption('shared');
        $namespace = Validators::validateBundleNamespace($this->addBundleSuffix($namespace), $shared);

        if (!$bundleName = $input->getOption('bundle-name')) {
            $bundleName = strtr($namespace, array('\\' => ''));
        } else {
            $bundleName = $this->addBundleSuffix($bundleName);
        }
        $bundleName = Validators::validateBundleName($bundleName);

        if (!$format = $input->getOption('format')) {
            $format = $shared ? 'xml' : 'annotation';
        }
        $format = Validators::validateFormat($format);

        // an assumption that the kernel root dir is in a directory (like app/)
        $projectRootDirectory = $this->getKernelRootDir().'/..';

        if (!$this->getContainer()->get('filesystem')->isAbsolutePath($dir)) {
            $dir = $projectRootDirectory.'/'.$dir;
        }
        // add trailing / if necessary
        $dir = '/' === substr($dir, -1, 1) ? $dir : $dir.'/';

        $bundle = new Bundle($namespace, $bundleName, $dir, $format, $shared);

        // not shared - put the tests in the root
        if (!$shared) {
            $testsDir = $projectRootDirectory.'/tests/'.$bundleName;
            $bundle->setTestsDirectory($testsDir);
        }

        return $bundle;
    }

    protected function createGenerator()
    {
        return new BundleGenerator();
    }
}

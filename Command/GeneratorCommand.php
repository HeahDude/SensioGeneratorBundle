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

use Sensio\Bundle\GeneratorBundle\Command\Style\SensioGeneratorStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Sensio\Bundle\GeneratorBundle\Generator\Generator;
use Sensio\Bundle\GeneratorBundle\Command\Helper\QuestionHelper;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Base class for generator commands.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class GeneratorCommand extends ContainerAwareCommand
{
    /**
     * @var Generator
     */
    private $generator;

    /**
     * @var StyleInterface
     */
    private $style;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var string
     */
    private $kernelRootDir;

    // only useful for unit tests
    public function setGenerator(Generator $generator)
    {
        $this->generator = $generator;
    }
    public function setStyle($style)
    {
        $this->style = $style;
    }

    abstract protected function createGenerator();

    protected function getGenerator(BundleInterface $bundle = null)
    {
        if (null === $this->generator) {
            $this->generator = $this->createGenerator();
            $this->generator->setSkeletonDirs($this->getSkeletonDirs($bundle));
        }

        return $this->generator;
    }

    protected function getSkeletonDirs(BundleInterface $bundle = null)
    {
        $skeletonDirs = array();

        if (isset($bundle) && is_dir($dir = $bundle->getPath().'/Resources/SensioGeneratorBundle/skeleton')) {
            $skeletonDirs[] = $dir;
        }

        if (is_dir($dir = $this->getContainer()->get('kernel')->getRootdir().'/Resources/SensioGeneratorBundle/skeleton')) {
            $skeletonDirs[] = $dir;
        }

        $skeletonDirs[] = __DIR__.'/../Resources/skeleton';
        $skeletonDirs[] = __DIR__.'/../Resources';

        return $skeletonDirs;
    }

    protected function getQuestionHelper()
    {
        $question = $this->getHelperSet()->get('question');
        if (!$question || get_class($question) !== 'Sensio\Bundle\GeneratorBundle\Command\Helper\QuestionHelper') {
            $this->getHelperSet()->set($question = new QuestionHelper());
        }

        return $question;
    }

    protected function getStyle(InputInterface $input = null, OutputInterface $output = null)
    {
        if (null === $this->style) {
            $this->style = new SensioGeneratorStyle($input, $output);
        }

        return $this->style;
    }

    /**
     * Tries to make a path relative to the project, which prints nicer.
     *
     * @param string $absolutePath
     *
     * @return string
     */
    protected function makePathRelative($absolutePath)
    {
        $projectRootDir = dirname($this->getKernelRootDir());

        return str_replace($projectRootDir.'/', '', realpath($absolutePath) ?: $absolutePath);
    }

    protected function addBundleSuffix($bundleName)
    {
        if (empty($bundleName)) {
            // Don't suffix empty string
            return '';
        }

        return 'Bundle' === substr($bundleName, -6) ? $bundleName : $bundleName.'Bundle';
    }

    protected function getKernel()
    {
        if (null === $this->kernel) {
            $this->kernel = $this->getContainer()->get('kernel');
        }

        return $this->kernel;
    }

    protected function getKernelRootDir()
    {
        if (null === $this->kernelRootDir) {
            $this->kernelRootDir = $this->getContainer()->getParameter('kernel.root_dir');
        }

        return $this->kernelRootDir;
    }
}

<?php

namespace Kit\GeneratorBundle\Generator;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Generator is the base class for all generators.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Generator
{
    private $skeletonDirs;
    private static $output;

    /**
     * Sets an array of directories to look for templates.
     *
     * The directories must be sorted from the most specific to the most
     * directory.
     *
     * @param array $skeletonDirs An array of skeleton dirs
     */
    public function setSkeletonDirs($skeletonDirs)
    {
        $this->skeletonDirs = is_array($skeletonDirs) ? $skeletonDirs : array($skeletonDirs);
    }

    protected function render($template, $parameters)
    {
        $twig = $this->getTwigEnvironment();

        return $twig->render($template, $parameters);
    }

    /**
     * Gets the twig environment that will render skeletons.
     *
     * @return \Twig_Environment
     */
    protected function getTwigEnvironment()
    {
        return new \Twig_Environment(new \Twig_Loader_Filesystem($this->skeletonDirs), array(
            'debug' => true,
            'cache' => false,
            'strict_variables' => true,
            'autoescape' => false,
        ));
    }

    protected function renderFile($template, $target, $parameters)
    {
        self::mkdir(dirname($target));

        return self::dump($target, $this->render($template, $parameters));
    }

    /**
     * @internal
     */
    public static function mkdir($dir, $mode = 0777, $recursive = true)
    {
        if (!is_dir($dir)) {
            mkdir($dir, $mode, $recursive);
            self::writeln(sprintf('  <fg=green>created</> %s', self::relativizePath($dir)));
        }
    }

    /**
     * @internal
     */
    public static function dump($filename, $content)
    {
        if (file_exists($filename)) {
            self::writeln(sprintf('  <fg=yellow>updated</> %s', self::relativizePath($filename)));
        } else {
            self::writeln(sprintf('  <fg=green>created</> %s', self::relativizePath($filename)));
        }

        return file_put_contents($filename, $content);
    }

    public static function dumpError($fileName, $reason)
    {
        self::writeln(sprintf('  <fg=red>failure</> \'%s\' [%s]', self::relativizePath($fileName), $reason));
    }

    private static function writeln($message)
    {
        if (null === self::$output) {
            self::$output = new ConsoleOutput();
        }

        self::$output->writeln($message);
    }

    private static function relativizePath($absolutePath)
    {
        $relativePath = str_replace(getcwd(), '.', $absolutePath);

        return is_dir($absolutePath) ? rtrim($relativePath, '/').'/' : $relativePath;
    }

    protected function getEntityPrivateField(BundleInterface $bundle, $entity, $fields)
    {
        $privateFieldArr = [];

        if (!class_exists($entity)) {
            $entityClassCompName = $bundle->getNamespace().'\\Entity\\'.$entity;
        } else {
            $entityClassCompName = $entity;
        }
        foreach ($fields as $field) {
            $_fun = 'set'.ucfirst($field['fieldName']);
            if (class_exists($entityClassCompName) && !is_callable([$entityClassCompName, $_fun])) {
                $privateFieldArr[] = $field['fieldName'];
            }
        }
        return $privateFieldArr;
    }
}

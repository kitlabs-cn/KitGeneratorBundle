<?php

namespace Kit\GeneratorBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Sensio\Bundle\GeneratorBundle\Command\AutoComplete\EntitiesAutoCompleter;
use Sensio\Bundle\GeneratorBundle\Command\Helper\QuestionHelper;
use Kit\GeneratorBundle\Generator\DoctrineCrudThemeGenerator;
use Kit\GeneratorBundle\Generator\DoctrineFormGenerator;
use Sensio\Bundle\GeneratorBundle\Manipulator\RoutingManipulator;
use Kit\GeneratorBundle\Command\GenerateDoctrineCommand;
use Sensio\Bundle\GeneratorBundle\Generator\Generator;
use Sensio\Bundle\GeneratorBundle\Command\Validators;

/**
 * Generates a CRUD for a Doctrine entity.
 *
 * @author lcp0578@gmail.com
 */
class GenerateDoctrineCrudThemeCommand extends GenerateDoctrineCommand
{
    private $formGenerator;

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('kit:doctrine:generate:crud:theme')
            ->setAliases(array('kit:generate:doctrine:crud:theme'))
            ->setDescription('Generates a CRUD based on a Doctrine entity')
            ->addArgument('entity', InputArgument::OPTIONAL, 'The entity class name to initialize (shortcut notation)')
            ->addOption('entity', null, InputOption::VALUE_OPTIONAL, 'The entity class name to initialize (shortcut notation)')
            ->addOption('theme', null, InputOption::VALUE_OPTIONAL, 'The views theme name')
            ->addOption('route-prefix', null, InputOption::VALUE_REQUIRED, 'The route prefix')
            ->addOption('with-write', null, InputOption::VALUE_NONE, 'Whether or not to generate create, new and delete actions')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'The format used for configuration files (php, xml, yml, or annotation)', 'yml')
            ->addOption('overwrite', null, InputOption::VALUE_NONE, 'Overwrite any existing controller or form class when generating the CRUD contents')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command generates a CRUD based on a Doctrine entity.

The default command only generates the list and show actions.

<info>php %command.full_name% AcmeBlogBundle:Post --route-prefix=post_admin</info>

Using the --with-write option allows to generate the new, edit and delete actions.

<info>php %command.full_name% AcmeBlogBundle:Post --route-prefix=post_admin --with-write</info>

Every generated file is based on a template. There are default templates but they can be overridden by placing custom templates in one of the following locations, by order of priority:

<info>BUNDLE_PATH/Resources/SensioGeneratorBundle/skeleton/crud
APP_PATH/Resources/SensioGeneratorBundle/skeleton/crud</info>

And

<info>__bundle_path__/Resources/SensioGeneratorBundle/skeleton/form
__project_root__/app/Resources/SensioGeneratorBundle/skeleton/form</info>

You can check https://github.com/sensio/SensioGeneratorBundle/tree/master/Resources/skeleton
in order to know the file structure of the skeleton
EOT
            )
        ;
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();

        if ($input->isInteractive()) {
            $question = new ConfirmationQuestion($questionHelper->getQuestion('Do you confirm generation', 'yes', '?'), true);
            if (!$questionHelper->ask($input, $output, $question)) {
                $output->writeln('<error>Command aborted</error>');

                return 1;
            }
        } else {
            // BC to be removed in 4.0
            if ($input->hasOption('entity') && $entityOption = $input->getOption('entity')) {
                @trigger_error('Using the "--entity" option has been deprecated since version 3.0 and will be removed in 4.0. Pass it as argument instead.', E_USER_DEPRECATED);

                $input->setArgument('entity', $entityOption);
            }
        }

        $entity = Validators::validateEntityName($input->getArgument('entity'));
        list($bundle, $entity) = $this->parseShortcutNotation($entity);

        $format = Validators::validateFormat($input->getOption('format'));
        $prefix = $this->getRoutePrefix($input, $entity);
        $withWrite = $input->getOption('with-write');
        $forceOverwrite = $input->getOption('overwrite');
        $theme = $input->getOption('theme');
        if(!in_array($theme, $this->getThemes())){
            $output->writeln('<error>--theme option value error.Theme name list: [Default, Blove, Blue, Pintuer]</error>');
            
            return 1;
        }

        $questionHelper->writeSection($output, 'CRUD generation');

        try {
            $entityClass = $this->getContainer()->get('doctrine')->getAliasNamespace($bundle).'\\'.$entity;
            $metadata = $this->getEntityMetadata($entityClass);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Entity "%s" does not exist in the "%s" bundle. Create it with the "doctrine:generate:entity" command and then execute this command again.', $entity, $bundle));
        }

        $bundle = $this->getContainer()->get('kernel')->getBundle($bundle);

        $generator = $this->getGenerator($bundle);
        $generator->generate($bundle, $entity, $metadata[0], $format, $prefix, $withWrite, $forceOverwrite, $theme);

        $output->writeln('Generating the CRUD code: <info>OK</info>');

        $errors = array();
        $questionHelper->getRunner($output, $errors);
        $runner = $questionHelper->getRunner($output, $errors);

        // form
         if ($withWrite) {
             $this->generateForm($bundle, $entity, $metadata, $forceOverwrite);
             $output->writeln('Generating the Form code: <info>OK</info>');
         }

        // routing
         $output->write('Updating the routing: ');
         if ('annotation' != $format) {
             $runner($this->updateRouting($questionHelper, $input, $output, $bundle, $format, $entity, $prefix));
         } else {
             $runner($this->updateAnnotationRouting($bundle, $entity, $prefix));
         }

        $questionHelper->writeGeneratorSummary($output, $errors);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();
        $questionHelper->writeSection($output, 'Welcome to the Doctrine2 CRUD generator');

        // namespace
        $output->writeln(array(
            '',
            'This command helps you generate CRUD controllers and templates.',
            '',
            'First, give the name of the existing entity for which you want to generate a CRUD',
            '(use the shortcut notation like <comment>AcmeBlogBundle:Post</comment>)',
            '',
        ));

        if ($input->hasOption('entity') && $entityOption = $input->getOption('entity')) {
            @trigger_error('Using the "--entity" option has been deprecated since version 3.0 and will be removed in 4.0. Pass it as argument instead.', E_USER_DEPRECATED);

            $input->setArgument('entity', $entityOption);
        }

        $question = new Question($questionHelper->getQuestion('The Entity shortcut name', $input->getArgument('entity')), $input->getArgument('entity'));
        $question->setValidator(array('Sensio\Bundle\GeneratorBundle\Command\Validators', 'validateEntityName'));

        $autocompleter = new EntitiesAutoCompleter($this->getContainer()->get('doctrine')->getManager());
        $autocompleteEntities = $autocompleter->getSuggestions();
        $question->setAutocompleterValues($autocompleteEntities);
        $entity = $questionHelper->ask($input, $output, $question);

        $input->setArgument('entity', $entity);
        list($bundle, $entity) = $this->parseShortcutNotation($entity);

        try {
            $entityClass = $this->getContainer()->get('doctrine')->getAliasNamespace($bundle).'\\'.$entity;
            $metadata = $this->getEntityMetadata($entityClass);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Entity "%s" does not exist in the "%s" bundle. You may have mistyped the bundle name or maybe the entity doesn\'t exist yet (create it first with the "doctrine:generate:entity" command).', $entity, $bundle));
        }

        // write?
        $withWrite = $input->getOption('with-write') ?: true;
        $output->writeln(array(
            '',
            'By default, the generator creates two actions: list and show.',
            'You can also ask it to generate "write" actions: new, update, and delete.',
            '',
        ));
        $question = new ConfirmationQuestion($questionHelper->getQuestion('Do you want to generate the "write" actions', $withWrite ? 'yes' : 'no', '?', $withWrite), $withWrite);

        $withWrite = $questionHelper->ask($input, $output, $question);
        $input->setOption('with-write', $withWrite);

        // format
        $format = $input->getOption('format');
        $output->writeln(array(
            '',
            'Determine the format to use for the generated CRUD.',
            '',
        ));
        $question = new Question($questionHelper->getQuestion('Configuration format (yml, xml, php, or annotation)', $format), $format);
        $question->setValidator(array('Sensio\Bundle\GeneratorBundle\Command\Validators', 'validateFormat'));
        $format = $questionHelper->ask($input, $output, $question);
        $input->setOption('format', $format);

        // route prefix
        $prefix = $this->getRoutePrefix($input, $entity);
        $output->writeln(array(
            '',
            'Determine the routes prefix (all the routes will be "mounted" under this',
            'prefix: /prefix/, /prefix/new, ...).',
            '',
        ));
        $prefix = $questionHelper->ask($input, $output, new Question($questionHelper->getQuestion('Routes prefix', '/'.$prefix), '/'.$prefix));
        $input->setOption('route-prefix', $prefix);

        // summary
        $questionHelper->writeSection($output, 'Summary before generation');
        $output->writeln(array(
            sprintf('You are going to generate a CRUD controller for "<info>%s:%s</info>"', $bundle, $entity),
            sprintf('using the "<info>%s</info>" format.', $format),
            '',
        ));
    }

    /**
     * Tries to generate forms if they don't exist yet and if we need write operations on entities.
     */
    protected function generateForm($bundle, $entity, $metadata, $forceOverwrite = false)
    {
        $this->getFormGenerator($bundle)->generate($bundle, $entity, $metadata[0], $forceOverwrite);
    }

    protected function updateRouting(QuestionHelper $questionHelper, InputInterface $input, OutputInterface $output, BundleInterface $bundle, $format, $entity, $prefix)
    {
        $auto = true;
        if ($input->isInteractive()) {
            $question = new ConfirmationQuestion($questionHelper->getQuestion('Confirm automatic update of the Routing', 'yes', '?'), true);
            $auto = $questionHelper->ask($input, $output, $question);
        }

        $output->write('Importing the CRUD routes: ');
        $this->getContainer()->get('filesystem')->mkdir($bundle->getPath().'/Resources/config/');

        // first, import the routing file from the bundle's main routing.yml file
        $routing = new RoutingManipulator($bundle->getPath().'/Resources/config/routing.yml');
        try {
            $ret = $auto ? $this->addResourceEntity($routing, $bundle->getName(), $format, '/'.$prefix, 'routing/'.strtolower(str_replace('\\', '_', $entity)), $entity) : false;
        } catch (\RuntimeException $exc) {
            $ret = false;
        }

        if (!$ret) {
            $help = sprintf("        <comment>resource: \"@%s/Resources/config/routing/%s.%s\"</comment>\n", $bundle->getName(), strtolower(str_replace('\\', '_', $entity)), $format);
            $help .= sprintf("        <comment>prefix:   /%s</comment>\n", $prefix);

            return array(
                '- Import the bundle\'s routing resource in the bundle routing file',
                sprintf('  (%s).', $bundle->getPath().'/Resources/config/routing.yml'),
                '',
                sprintf('    <comment>%s:</comment>', $routing->getImportedResourceYamlKey($bundle->getName(), $prefix)),
                $help,
                '',
            );
        }

        // second, import the bundle's routing.yml file from the application's routing.yml file
        $routing = new RoutingManipulator($this->getContainer()->getParameter('kernel.root_dir').'/config/routing.yml');
        try {
            $ret = $auto ? $routing->addResource($bundle->getName(), 'yml') : false;
        } catch (\RuntimeException $e) {
            // the bundle is already imported form app's routing.yml file
            $errorMessage = sprintf(
                "\n\n[ERROR] The bundle's \"Resources/config/routing.yml\" file cannot be imported\n".
                "from \"app/config/routing.yml\" because the \"%s\" bundle is\n".
                "already imported. Make sure you are not using two different\n".
                "configuration/routing formats in the same bundle because it won't work.\n",
                $bundle->getName()
            );
            $output->write($errorMessage);
            $ret = true;
        } catch (\Exception $e) {
            $ret = false;
        }

        if (!$ret) {
            return array(
                '- Import the bundle\'s routing.yml file in the application routing.yml file',
                sprintf('# app/config/routing.yml'),
                sprintf('%s:', $bundle->getName()),
                sprintf('    <comment>resource: "@%s/Resources/config/routing.yml"</comment>', $bundle->getName()),
                '',
                '# ...',
                '',
            );
        }
    }

    /**
     * @modifier yaoyanan<904381790@qq.com>
     * Adds a routing resource at the top of the existing ones.
     *
     * @param string $bundle
     * @param string $format
     * @param string $prefix
     * @param string $path
     *
     * @return bool Whether the operation succeeded
     *
     * @throws \RuntimeException If bundle is already imported
     *
     * @see \Sensio\Bundle\GeneratorBundle\Manipulator\RoutingManipulator::addResource
     */
    public function addResourceEntity($routing, $bundle, $format, $prefix = '/', $path = 'routing', $entity = '')
    {
        $current = '';
        $code = sprintf("%s:\n", $routing->getImportedResourceYamlKey($bundle, $prefix));

        $getFileFun = function(){return $this->file;};
        $file = $getFileFun->call($routing);
        if (file_exists($file)) {
            $current = file_get_contents($file);

            if ($entity && false !== strpos($current, strtolower($bundle).'_'.strtolower($entity).':')) {
                throw new \RuntimeException(sprintf('Entity "%s" is already imported.', $bundle));
            }
        } elseif (!is_dir($dir = dirname($file))) {
            Generator::mkdir($dir);
        }

        if ('annotation' == $format) {
            $code .= sprintf("    resource: \"@%s/Controller/\"\n    type:     annotation\n", $bundle);
        } else {
            $code .= sprintf("    resource: \"@%s/Resources/config/%s.%s\"\n", $bundle, $path, $format);
        }
        $code .= sprintf("    prefix: %s\n", $prefix);

        $code = $current."\n\n".$code;

        if (false === Generator::dump($file, $code)) {
            return false;
        }

        return true;
    }

    protected function updateAnnotationRouting(BundleInterface $bundle, $entity, $prefix)
    {
        $rootDir = $this->getContainer()->getParameter('kernel.root_dir');

        $routing = new RoutingManipulator($rootDir.'/config/routing.yml');

        if (!$routing->hasResourceInAnnotation($bundle->getName())) {
            $parts = explode('\\', $entity);
            $controller = array_pop($parts);

            $ret = $routing->addAnnotationController($bundle->getName(), $controller);
        }
    }

    protected function getRoutePrefix(InputInterface $input, $entity)
    {
        $prefix = $input->getOption('route-prefix') ?: strtolower(str_replace(array('\\', '/'), '_', $entity));

        if ($prefix && '/' === $prefix[0]) {
            $prefix = substr($prefix, 1);
        }

        return $prefix;
    }

    protected function createGenerator($bundle = null)
    {
        return new DoctrineCrudThemeGenerator(
            $this->getContainer()->get('filesystem'),
            $this->getContainer()->getParameter('kernel.root_dir')
        );
    }

    protected function getFormGenerator($bundle = null)
    {
        if (null === $this->formGenerator) {
            $this->formGenerator = new DoctrineFormGenerator($this->getContainer()->get('filesystem'));
            $this->formGenerator->setSkeletonDirs($this->getSkeletonDirs($bundle));
        }

        return $this->formGenerator;
    }

    public function setFormGenerator(DoctrineFormGenerator $formGenerator)
    {
        $this->formGenerator = $formGenerator;
    }
    
    private function getThemes()
    {
        return [
            'Default',
            'Blove',
            'Blue',
            'Pintuer',
        ];
    }
}

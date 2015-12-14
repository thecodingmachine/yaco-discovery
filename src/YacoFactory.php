<?php

namespace TheCodingMachine\Yaco\Discovery;


use Interop\Container\ContainerInterface;
use Interop\Container\Factory\ContainerFactoryInterface;
use Puli\Discovery\Api\Discovery;
use Puli\Discovery\Binding\ClassBinding;
use Symfony\Component\Filesystem\Filesystem;
use TheCodingMachine\Yaco\Compiler;

/**
 * A class in charge of instantiating the default Yaco container.
 */
class YacoFactory implements ContainerFactoryInterface
{

    /**
     * Creates a container.
     *
     * @param ContainerInterface $rootContainer
     * @param Discovery $discovery
     *
     * @return ContainerInterface
     */
    public static function buildContainer(ContainerInterface $rootContainer, Discovery $discovery)
    {
        $containerFile = self::getContainerFilePath();
        if (!file_exists($containerFile)) {
            self::compileContainer($discovery);
        }
        if (!is_readable($containerFile)) {
            throw new YacoFactoryNoContainerException('Unable to read file ".yaco/Container.php" at project root.');
        }
        require_once $containerFile;

        return new \TheCodingMachine\Yaco\Container($rootContainer);
    }

    /**
     * Creates the container file from the discovered providers.
     *
     * @param Discovery $discovery
     */
    public static function compileContainer(Discovery $discovery) {
        $containerFile = self::getContainerFilePath();

        $compiler = new Compiler();

        $bindings = $discovery->findBindings('container-interop/DefinitionProviderFactories');

        $definitionProviders = [];
        $priorities = [];

        foreach ($bindings as $binding) {
            /* @var $binding ClassBinding */
            $definitionProviderFactoryClassName = $binding->getClassName();

            // From the factory class name, let's call the buildDefinitionProvider static method to get the definitionProvider.
            $definitionProviders[] = call_user_func([ $definitionProviderFactoryClassName, 'buildDefinitionProvider' ], $discovery);
            $priorities[] = $binding->getParameterValue('priority');
        }

        // Sort definition providers according to their priorities.
        array_multisort($priorities, $definitionProviders);

        foreach ($definitionProviders as $provider) {
            $compiler->register($provider);
        }

        $containerFileContent = $compiler->compile('\\TheCodingMachine\\Yaco\\Container');

        $filesystem = new Filesystem();
        if (!$filesystem->exists(dirname($containerFile))) {
            $filesystem->mkdir(dirname($containerFile));
        }
        $filesystem->dumpFile($containerFile, $containerFileContent);
        $filesystem->chmod($containerFile, 0664);
    }

    /**
     * @return string
     */
    public static function getContainerFilePath() {
        return __DIR__.'/../../../../.yaco/Container.php';
    }
}

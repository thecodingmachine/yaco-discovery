<?php
namespace TheCodingMachine\Yaco\Discovery;


use Puli\Manager\Api\Event\PuliEvents;
use Puli\Manager\Api\Puli;
use Puli\Manager\Api\PuliPlugin;
use Symfony\Component\Filesystem\Filesystem;

class YacoPlugin implements PuliPlugin
{

    /**
     * Activates the plugin.
     *
     * @param Puli $puli The {@link Puli} instance.
     */
    public function activate(Puli $puli)
    {
        $puli->getEventDispatcher()->addListener(PuliEvents::GENERATE_FACTORY, function() use ($puli) {
            // Function called just before the factory class is written.
            // Idea: let's delete the compiled container file. And let's recreate it only when needed!
            $filesystem = new Filesystem();
            $filesystem->remove(YacoFactory::getContainerFilePath());

            $logger = $puli->getLogger();
            if ($logger) {
                $puli->getLogger()->info("Bindings updated. Removing outdated container.");
            }
        });
    }
}

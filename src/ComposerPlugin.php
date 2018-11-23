<?php

declare(strict_types=1);

/**
 * Copyright Andrea Heigl <andreas@heigl.org>
 *
 * Licenses under the MIT-license. For details see the included file LICENSE.md
 */

namespace CaptainHook\Plugin\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

class ComposerPlugin implements PluginInterface, EventSubscriberInterface
{
    /** @var Installer */
    private $installer;

    public function activate(Composer $composer, IOInterface $io) : void
    {
        $this->installer = new Installer($io, $composer->getConfig());
    }

    public static function getSubscribedEvents() : array
    {
        return [
            ScriptEvents::POST_AUTOLOAD_DUMP => 'runPostInstallScript'
         ];
    }

    public function runPostInstallScript(Event $event) : void
    {
        ($this->installer)();
    }
}
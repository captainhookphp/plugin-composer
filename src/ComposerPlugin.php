<?php

declare(strict_types=1);

/**
 * Copyright Andrea Heigl <andreas@heigl.org>
 *
 * Licenses under the MIT-license. For details see the included file LICENSE.md
 */

namespace CaptainHook\Plugin\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PluginEvents;

class ComposerPlugin implements PluginInterface
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
            PluginEvents::COMMAND => 'runAtCommandRun',
        ];
    }

    public function runAtCommandRun(CommandEvent $event) : void
    {
        if (! in_array($event->getCommandName(), [
            'install',
            'update',
        ])) {
            return;
        }

        ($this->installer)();
    }
}
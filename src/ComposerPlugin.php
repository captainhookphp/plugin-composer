<?php
/**
 * This file is part of CaptainHook.
 *
 * (c) Sebastian Feldmann <sf@sebastian.feldmann.info>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace CaptainHook\Plugin\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

/**
 * Class ComposerPlugin
 *
 * @package CaptainHook\Plugin
 * @author  Andrea Heigl <andreas@heigl.org>
 * @link    https://github.com/captainhookphp/captainhook
 */
class ComposerPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var Installer
     */
    private $installer;

    /**
     * Activate the plugin by setting up the installer
     *
     * @param  \Composer\Composer       $composer
     * @param  \Composer\IO\IOInterface $io
     * @return void
     */
    public function activate(Composer $composer, IOInterface $io) : void
    {
        $this->installer = new Installer($io, $composer->getPackage());
    }

    /**
     * Make sure the installer is executed after the autoloader is created
     *
     * @return array
     */
    public static function getSubscribedEvents() : array
    {
        return [
            ScriptEvents::POST_AUTOLOAD_DUMP => 'runPostInstallScript'
         ];
    }

    /**
     * Run the installer
     *
     * @param  \Composer\Script\Event $event
     * @return void
     */
    public function runPostInstallScript(Event $event) : void
    {
        if (!$this->isCaptainHookInstalled()) {
            // reload the autoloader to make sure CaptainHook is available
            $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
            require $vendorDir . '/autoload.php';
        }

        // if it's still not available end the plugin execution
        if (!$this->isCaptainHookInstalled()) {
            $event->getIO()->write('CaptainHook not properly installed try to run composer update');
            return;
        }
        // otherwise run the installer
        ($this->installer)();
    }

    /**
     * Checks if CaptainHook is installed properly
     *
     * @return bool
     */
    private function isCaptainHookInstalled() : bool
    {
        return class_exists('\\CaptainHook\\App\\CH');
    }
}

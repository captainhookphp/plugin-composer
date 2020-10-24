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

use CaptainHook\App\Composer\Cmd;
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
 * @link    https://github.com/captainhookphp/plugin-composer
 */
class ComposerPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * Activate the plugin
     *
     * @param  \Composer\Composer       $composer
     * @param  \Composer\IO\IOInterface $io
     * @return void
     */
    public function activate(Composer $composer, IOInterface $io) : void
    {
        // nothing to do here
    }

    /**
     * Remove any hooks from Composer
     *
     * This will be called when a plugin is deactivated before being
     * uninstalled, but also before it gets upgraded to a new version
     * so the old one can be deactivated and the new one activated.
     *
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
        // Do nothing currently
    }

    /**
     * Prepare the plugin to be uninstalled
     *
     * This will be called after deactivate.
     *
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
        // Do nothing currently
    }



    /**
     * Make sure the installer is executed after the autoloader is created
     *
     * @return array
     */
    public static function getSubscribedEvents() : array
    {
        return [
            ScriptEvents::POST_AUTOLOAD_DUMP => 'installHooks'
         ];
    }

    /**
     * Run the installer
     *
     * @param  \Composer\Script\Event $event
     * @return void
     * @throws \Exception
     */
    public function installHooks(Event $event) : void
    {
        $event->getIO()->write('CaptainHook Composer Plugin');
        if (!$this->isCaptainHookInstalled()) {
            // reload the autoloader to make sure CaptainHook is available
            $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
            require $vendorDir . '/autoload.php';
        }

        if (!$this->isCaptainHookInstalled()) {
            // if CaptainHook is still not available end the plugin execution
            // normally this only happens if CaptainHook gets uninstalled
            $event->getIO()->write(
                '  <info>CaptainHook not properly installed try to run composer update</info>' . PHP_EOL .
                PHP_EOL .
                'If you are uninstalling CaptainHook, we are sad seeing you go, ' .
                'but we would appreciate your feedback on your experience.' . PHP_EOL .
                'Just go to https://github.com/CaptainHookPhp/captainhook/issues to leave your feedback' . PHP_EOL .
                PHP_EOL .
                '<comment>WARNING: Don\'t forget to deactivate the hooks in your .git/hooks directory.</comment>'
            );
            return;
        }
        Cmd::setup($event);
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

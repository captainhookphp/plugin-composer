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

use CaptainHook\App\CH;
use Composer\IO\IOInterface;
use Composer\Package\Package;
use CaptainHook\App\Composer\Application;
use CaptainHook\App\Config\Factory;
use CaptainHook\App\Console\Command\Install;
use CaptainHook\App\Storage\File\Json;
use SplFileInfo;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Class Installer
 *
 * @package CaptainHook\Plugin
 * @author  Andrea Heigl <andreas@heigl.org>
 * @link    https://github.com/captainhookphp/captainhook
 */
class Installer
{
    /**
     * @var \Composer\IO\IOInterface
     */
    private $io;

    /**
     * @var \Composer\Package\Package
     */
    private $package;

    /**
     * Installer constructor
     *
     * @param \Composer\IO\IOInterface  $io
     * @param \Composer\Package\Package $package
     */
    public function __construct(IOInterface $io, Package $package)
    {
        $this->io      = $io;
        $this->package = $package;
    }

    /**
     * @throws \Exception
     */
    public function __invoke() : void
    {
        $app     = $this->createApplication();
        $config  = $app->getConfigFile();
        $install = new Install();
        $install->setIO($app->getIO());

        $this->assertConfigFile(new SplFileInfo($config));
        $input = new ArrayInput(['command' => 'install', '--configuration' => $app->getConfigFile(), '-f' => '-f']);
        $app->add($install);
        $app->run($input);
    }

    /**
     * Create a CaptainHook Composer application
     *
     * @return \CaptainHook\App\Composer\Application
     */
    private function createApplication() : Application
    {
        $app = new Application();
        $app->setAutoExit(false);
        $app->setConfigFile($this->getConfigFile());
        $app->setProxyIO($this->io);

        return $app;
    }

    /**
     * Return the configured captainhook config file empty string if not set
     *
     * @return string
     */
    private function getConfigFile() : string
    {
        $extra = $this->package->getExtra();
        if ($extra === null || ! isset($extra[CH::CONFIG_COMPOSER])) {
            return '';
        }

        return $extra[CH::CONFIG_COMPOSER];
    }

    /**
     * Make sure the config file exists on disk
     *
     * @param  \SplFileInfo $configFile
     * @return void
     */
    private function assertConfigFile(SplFileInfo $configFile) : void
    {
        if ($configFile->isFile()) {
            $this->io->write('  <info>Using CaptainHook config: ' . $configFile->getFilename() . '</info>');
            return;
        }

        $this->io->write('  <info>Creating CaptainHook config: ' . $configFile->getFilename() . '</info>');

        $file = new Json($configFile->getPathname());
        $file->write(Factory::create($configFile->getPathname())->getJsonData());
    }
}

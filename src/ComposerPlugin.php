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

use CaptainHook\Plugin\Composer\Asset\Service\ConvertGithubReleaseListService;
use CaptainHook\Plugin\Composer\Asset\Service\GithubService;
use CaptainHook\Plugin\Composer\Asset\Service\VersionService;
use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Semver\Comparator;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\StreamHandler;
use RuntimeException;
use function chmod;
use function dirname;
use function file_exists;
use function fputcsv;
use function fputs;
use function fread;
use function is_writeable;
use function mkdir;
use function stream_context_create;
use function stream_context_set_option;
use const PHP_EOL;

/**
 * Class ComposerPlugin
 *
 * @package CaptainHook\Plugin
 * @author  Andrea Heigl <andreas@heigl.org>
 * @link    https://github.com/captainhookphp/plugin-composer
 */
class ComposerPlugin implements PluginInterface, EventSubscriberInterface
{
    private const COMMAND_CONFIGURE = 'configure';
    private const COMMAND_INSTALL   = 'install';

    /**
     * Composer instance
     *
     * @var \Composer\Composer
     */
    private $composer;

    /**
     * Composer IO instance
     *
     * @var \Composer\IO\IOInterface
     */
    private $io;

    /**
     * Path to the captainhook executable
     *
     * @var string
     */
    private $executable;

    /**
     * Path to the captainhook configuration file
     *
     * @var string
     */
    private $configuration;

    /**
     * Path to the .git directory
     *
     * @var string
     */
    private $gitDirectory;

    /**
     * Activate the plugin
     *
     * @param  \Composer\Composer       $composer
     * @param  \Composer\IO\IOInterface $io
     * @return void
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io       = $io;
    }

    /**
     * Make sure the installer is executed after the autoloader is created
     *
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => [
                'installCaptn',
                'installHooks',
            ],
            ScriptEvents::POST_UPDATE_CMD  => [
                'installCaptn',
                'installHooks',
            ],
            PackageEvents::POST_PACKAGE_UNINSTALL => [
                'uninstallHooks',
                'uninstallCaptn'
            ]
         ];
    }

    /**
     * Run the installer
     *
     * @param  \Composer\Script\Event $event
     * @return void
     * @throws \Exception
     */
    public function installHooks(Event $event): void
    {
        $this->io->write('<info>CaptainHook Composer Plugin</info>');

        if ($this->isPluginDisabled()) {
            $this->io->write('  <comment>plugin is disabled</comment>');
            return;
        }

        $this->detectConfiguration();
        $this->detectGitDir();
        $this->detectCaptainExecutable();

        if (!file_exists($this->executable)) {
            $this->io->write(
                '<comment>CaptainHook executable not found</comment>' . PHP_EOL .
                PHP_EOL .
                'Make sure you have installed the captainhook/captainhook package.' . PHP_EOL .
                'If you are using the PHAR you have to configure the path to your CaptainHook executable' . PHP_EOL .
                'using Composers \'extra\' config. e.g.' . PHP_EOL .
                PHP_EOL . '<comment>' .
                '    "extra": {' . PHP_EOL .
                '        "captainhook": {' . PHP_EOL .
                '            "exec": "tools/captainhook.phar' . PHP_EOL .
                '        }' . PHP_EOL .
                '    }' . PHP_EOL .
                '</comment>' . PHP_EOL .
                'If you are uninstalling CaptainHook, we are sad seeing you go, ' .
                'but we would appreciate your feedback on your experience.' . PHP_EOL .
                'Just go to https://github.com/CaptainHookPhp/captainhook/issues to leave your feedback' . PHP_EOL .
                '<comment>WARNING: Don\'t forget to deactivate the hooks in your .git/hooks directory.</comment>' .
                PHP_EOL
            );
            return;
        }

        $this->configure();
        $this->install();
    }

    public function installCaptn(Event $event): void
    {
        $this->detectCaptainExecutable();

        if ($this->canNotCreateExecutable()) {
            $this->io->write(sprintf(
                '<comment>CaptainHook executable at "%s" can not be updated!</comment>' . PHP_EOL .
                PHP_EOL .
                'Make sure to update The Cap\'n manually to the latest version' . PHP_EOL,
                $this->executable
            ));
            return;
        }

        $context = stream_context_create();

        $service = new GithubService($context, new VersionService(), new ConvertGithubReleaseListService());
        $url = $service('CaptainHookPhp', 'captainhook', 'captainhook.phar', '^5.0');

        if (Comparator::greaterThan($this->getCurrentCaptainVersion(), $url['version'])) {
            return;
        }

        stream_context_set_option($context, 'http', 'method', 'GET');
        $target = fopen($this->executable, 'w+');
        $source = fopen((string) $url['url'], 'r', false, $context);
        while (! feof($source)) {
            fputs($target, fread($source, 1024));
        }
        fclose($target);
        fclose($source);
        chmod($this->executable, 0755);
        // Possibly add a signature-check here as well!
    }

    public function uninstallCaptn(PackageEvent $event): void
    {
        unlink($this->executable);
    }

    public function uninstallHooks(PackageEvent $event): void
    {
        var_dump('foo');
    }

    /**
     *
     * Create captainhook.json file if it does not exist
     */
    private function configure(): void
    {
        if (file_exists($this->configuration)) {
            $this->io->write(('  <comment>Using CaptainHook config: ' . $this->configuration . '</comment>'));
            return;
        }

        $this->runCaptainCommand(self::COMMAND_CONFIGURE);
    }

    /**
     * Install hooks to your .git/hooks directory
     */
    private function install(): void
    {
        $this->runCaptainCommand(self::COMMAND_INSTALL);
    }

    /**
     * Executes CaptainHook in a sub process
     *
     * @param string $command
     */
    private function runCaptainCommand(string $command): void
    {
        // Respect composer CLI settings
        $ansi        = $this->io->isDecorated() ? ' --ansi' : ' --no-ansi';
        $interaction = $this->io->isInteractive() ? '' : ' --no-interaction';

        // captainhook config and repository settings
        $configuration  = ' -c ' . escapeshellarg($this->configuration);
        $repository     = $command === self::COMMAND_INSTALL ? ' -g ' . escapeshellarg($this->gitDirectory) : '';
        $skip           = $command === self::COMMAND_INSTALL ? ' -s' : '';
        $executable     = str_replace(' ', '\\ ', $this->executable);

        // sub process settings
        $cmd   = $executable . ' ' . $command . $ansi . $interaction . $skip . $configuration . $repository;
        $pipes = [];
        $spec  = [
            0 => ['file', 'php://stdin', 'r'],
            1 => ['file', 'php://stdout', 'w'],
            2 => ['file', 'php://stderr', 'w'],
        ];

        $process = @proc_open($cmd, $spec, $pipes);

        if ($this->io->isVerbose()) {
            $this->io->write('Running process : ' . $cmd);
        }
        if (!is_resource($process)) {
            throw new RuntimeException($this->pluginErrorMessage('no-process'));
        }

        // Loop on process until it exits normally.
        do {
            $status = proc_get_status($process);
        } while ($status && $status['running']);
        $exitCode = $status['exitcode'] ?? -1;
        proc_close($process);
        if ($exitCode !== 0) {
            $this->io->writeError($this->pluginErrorMessage('installation process failed'));
        }
    }

    /**
     * Return path to the CaptainHook configuration file
     *
     * @return void
     */
    private function detectConfiguration(): void
    {
        $extra               = $this->composer->getPackage()->getExtra();
        $this->configuration = getcwd() . '/' . ($extra['captainhook']['config'] ?? 'captainhook.json');
    }

    /**
     * Search for the git repository to store the hooks in

     * @return void
     * @throws \RuntimeException
     */
    private function detectGitDir(): void
    {
        $path = getcwd();

        while (file_exists($path)) {
            $possibleGitDir = $path . '/.git';
            if (is_dir($possibleGitDir)) {
                $this->gitDirectory = $possibleGitDir;
                return;
            }

            // if we checked the root directory already, break to prevent endless loop
            if ($path === dirname($path)) {
                break;
            }

            $path = \dirname($path);
        }
        throw new RuntimeException($this->pluginErrorMessage('git directory not found'));
    }

    /**
     * Creates a nice formatted error message
     *
     * @param  string $reason
     * @return string
     */
    private function pluginErrorMessage(string $reason): string
    {
        return 'Shiver me timbers! CaptainHook could not install yer git hooks! (' . $reason . ')';
    }

    /**
     *
     */
    private function detectCaptainExecutable(): void
    {
        $extra = $this->composer->getPackage()->getExtra();
        if (isset($extra['captainhook']['exec'])) {
            $this->executable = (string) $extra['captainhook']['exec'];
            return;
        }

        $this->executable = (string) $this->composer->getConfig()->get('bin-dir') . '/captainhook';
    }

    /**
     * Check if the plugin is disabled
     *
     * @return bool
     */
    private function isPluginDisabled(): bool
    {
        $extra = $this->composer->getPackage()->getExtra();
        return (bool) ($extra['captainhook']['disable-plugin'] ?? false);
    }

    private function canNotCreateExecutable()
    {
        if (! file_exists(dirname($this->executable))) {
            mkdir(dirname($this->executable), 0777, true);
        }
        return ! is_writeable(dirname($this->executable));
    }

    private function getCurrentCaptainVersion(): string
    {
        if (! preg_match('/ \d+\.\d+\.\d+ /', 'foob', $result)) {
            return '1.0.0';
        };

        return $result[1];
    }
}

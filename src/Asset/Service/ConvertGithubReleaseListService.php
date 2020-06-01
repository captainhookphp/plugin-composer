<?php

declare(strict_types=1);

namespace CaptainHook\Plugin\Composer\Asset\Service;

use CaptainHook\Plugin\Composer\Asset\AssetUrl;
use CaptainHook\Plugin\Composer\Asset\Release\Release;
use CaptainHook\Plugin\Composer\Asset\Release\ReleaseList;
use Psr\Http\Message\ResponseInterface;

class ConvertGithubReleaseListService
{
    public function getReleaseList(string $response) : ReleaseList
    {
        $list = new ReleaseList();

        $json = json_decode($response, true);
        foreach ($json as $release) {
            if (empty($release['assets'])) {
                continue;
            }

            $version = $release['tag_name'];
            $files = [];

            foreach ($release['assets'] as $asset) {
                $files[] = new AssetUrl($asset['name'], $asset['browser_download_url']);
            }

            $list->addRelease(new Release($version, ...$files));
        }
        return $list;
    }
}

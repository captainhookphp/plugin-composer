<?php

declare(strict_types=1);

namespace CaptainHook\Plugin\Composer\Asset\Service;

use CaptainHook\Plugin\Composer\Asset\Exception\NoAssetMatchingConstraintFound;
use CaptainHook\Plugin\Composer\Asset\Exception\TroubleWithGithubApiAccess;
use Exception;
use function file_get_contents;
use function stream_context_set_option;

class GithubService
{
    private $client;

    private $versionService;

    private $converterService;

    public function __construct(
        $context,
        VersionService $versionService,
        ConvertGithubReleaseListService $converterService
    ) {
        $this->context = $context;
        $this->versionService = $versionService;
        $this->converterService = $converterService;

        stream_context_set_option($this->context, 'http', 'method', 'GET');
        stream_context_set_option($this->context, 'http', 'header', 'Accept: application/vnd.github.v3+json');
        stream_context_set_option($this->context, 'http', 'user_agent', 'AssetFetcher');
    }

    /**
     * @throws TroubleWithGithubApiAccess
     * @throws NoAssetMatchingConstraintFound
     */
    public function __invoke(
        string $user,
        string $project,
        string $file,
        string $constraint = null
    ) : array {
        try {
            $result = file_get_contents(sprintf(
                '%3$s/repos/%1$s/%2$s/releases',
                $user,
                $project,
                'https://api.github.com'
            ), false, $this->context);
        } catch (Exception $e) {
            throw new TroubleWithGithubApiAccess(
                'Something went south while accessing the Github-API',
                400,
                $e
            );
        }

        $result = $this->converterService->getReleaseList($result);

        $asset = $this->versionService->getLatestAssetForConstraintFromResult(
            $result,
            $constraint
        );

        return [
            'version' => $asset->getVersion(),
            'url' => $asset->getAssetUrl($file)->getAssetUrl(),
        ];
    }
}

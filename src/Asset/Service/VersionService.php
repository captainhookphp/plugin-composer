<?php

declare(strict_types=1);

namespace CaptainHook\Plugin\Composer\Asset\Service;

use CaptainHook\Plugin\Composer\Asset\Exception\NoAssetMatchingConstraintFound;
use CaptainHook\Plugin\Composer\Asset\Release\Release;
use CaptainHook\Plugin\Composer\Asset\Release\ReleaseList;
use Composer\Semver\Comparator;
use Composer\Semver\Semver;

class VersionService
{
    public function getLatestAssetForConstraintFromResult(ReleaseList $list, string $versionConstraint = null) : Release
    {
        $newList = [];
        /** @var Release $item */
        foreach ($list as $item) {
            if (null !== $versionConstraint &&  ! Semver::satisfies($item->getVersion(), $versionConstraint)) {
                continue;
            }

            $newList[] = $item;
        }

        if (count($newList) < 1) {
            throw new NoAssetMatchingConstraintFound(sprintf(
                'Could not match Constraint %s',
                $versionConstraint
            ));
        }

        usort($newList, function (Release $a, Release $b) {
            if (Comparator::greaterThan($a->getVersion(), $b->getVersion())) {
                return -1;
            }
            if (Comparator::lessThan($a->getVersion(), $b->getVersion())) {
                return 1;
            }

            return 0;
        });

        return $newList[0];
    }
}

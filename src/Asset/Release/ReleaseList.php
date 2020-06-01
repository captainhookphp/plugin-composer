<?php

declare(strict_types=1);

namespace CaptainHook\Plugin\Composer\Asset\Release;

use Iterator;
use Org_Heigl\IteratorTrait\IteratorTrait;

class ReleaseList implements Iterator
{
    use IteratorTrait;

    private $releases;

    public function addRelease(Release $release) : void
    {
        $this->releases[] = $release;
    }

    /**
     * Get the array the iterator shall iterate over.
     *
     * @return array
     */
    protected function & getIterableElement()
    {
        return $this->releases;
    }
}

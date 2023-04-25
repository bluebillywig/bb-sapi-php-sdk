<?php

namespace BlueBillywig\Helpers;

use BlueBillywig\Helper;

/**
 * @property-read \Bluebillywig\Entities\Thumbnail $entity
 */
class ThumbnailHelper extends Helper
{
    /**
     * Parse a relative image path to an absolute image path on the OVP.
     *
     * @param string $relativeImagePath The relative image path to be parsed to an absolute one.
     * @param int $width The width the image should have when retrieved through the absolute URL.
     * @param int $height The height the image should have when retrieved through the absolute URL.
     *
     * @throws \ValueError
     */
    public function getAbsoluteImagePath(
        string $relativeImagePath,
        int $width = 0,
        int $height = 0
    ): string {
        if ($width < 0) {
            throw new \ValueError('Given width is lower than 0.');
        } elseif ($height < 0) {
            throw new \ValueError('Given height is lower than 0.');
        }
        $relativeImagePath = ltrim($relativeImagePath, '/');
        $baseUri = $this->sdk->getBaseUri();
        return "$baseUri/image/$width/$height/$relativeImagePath";
    }
}

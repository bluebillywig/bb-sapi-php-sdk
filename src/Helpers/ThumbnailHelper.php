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

    /**
     * Absolute URL of a media clip's poster image.
     *
     * Use this rather than building a URL from the clip payload. A clip's `src`
     * is its SOURCE MEDIA file, so `defaultMediaAssetPath . $clip['src']` yields
     * a link to a .mov — the service says as much, answering
     * "Invalid src mime type: video/quicktime". That mistake is easy to make and
     * shows up as a grid full of broken images.
     *
     * `default` is accepted for either dimension and lets the service choose.
     *
     * A draft (unpublished) clip's poster is not public. Pass an RPC token —
     * minted from the READ-ONLY key, never the write key, because this URL ends
     * up in page source — to see those.
     *
     * @param int|string $mediaClipId
     * @param int|string $width  Pixels, or 'default'.
     * @param int|string $height Pixels, or 'default'.
     * @param string|null $rpcToken Read-only RPC token, for draft clips.
     */
    public function getMediaClipPosterPath(
        int|string $mediaClipId,
        int|string $width = 'default',
        int|string $height = 'default',
        ?string $rpcToken = null
    ): string {
        $dimension = static function (int|string $value): string {
            $value = (string) $value;
            return preg_match('/^\d{1,5}$/', $value) === 1 ? $value : 'default';
        };

        $baseUri = $this->sdk->getBaseUri();
        $url = sprintf(
            '%s/mediaclip/%s/spthumbnail/%s/%s.webp',
            $baseUri,
            rawurlencode((string) $mediaClipId),
            $dimension($width),
            $dimension($height)
        );

        if ($rpcToken !== null && $rpcToken !== '') {
            $url .= '?useSession=true&rpctoken=' . rawurlencode($rpcToken);
        }

        return $url;
    }

}

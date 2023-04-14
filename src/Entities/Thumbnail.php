<?php

namespace BlueBillywig\Entities;

use BlueBillywig\Entity;

/**
 * Representation of the Thumbnail resource on the Blue Billywig SAPI
 *
 * @property-read \BlueBillywig\Helpers\ThumbnailHelper $helper
 */
class Thumbnail extends Entity
{
    protected static string $helperCls = \BlueBillywig\Helpers\ThumbnailHelper::class;
}

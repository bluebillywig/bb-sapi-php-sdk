<?php

namespace BlueBillywig\Tests\Unit\Helpers;

use BlueBillywig\Authentication\EmptyAuthenticator;
use BlueBillywig\Entities\Thumbnail;
use BlueBillywig\Sdk;

class ThumbnailHelperTest extends \Codeception\Test\Unit
{
    use \Codeception\AssertThrows;

    public function testGetAbsoluteImagePath()
    {
        $thumbnail = new Thumbnail(
            new Sdk('my-publication', new EmptyAuthenticator())
        );
        $relativePath = '/some/path/to/an/image';
        $width = 0;
        $height = 200;
        $absolutePath = $thumbnail->helper->getAbsoluteImagePath(
            $relativePath,
            $width,
            $height
        );
        $this->assertEquals(
            "https://my-publication.bbvms.com/image/$width/$height$relativePath",
            $absolutePath
        );
    }

    public function testGetAbsoluteImagePathNonTrailingSlash()
    {
        $thumbnail = new Thumbnail(
            new Sdk('my-publication', new EmptyAuthenticator())
        );
        $relativePath = 'some/path/to/an/image';
        $width = 300;
        $height = 0;
        $absolutePath = $thumbnail->helper->getAbsoluteImagePath(
            $relativePath,
            $width,
            $height
        );
        $this->assertEquals(
            "https://my-publication.bbvms.com/image/$width/$height/$relativePath",
            $absolutePath
        );
    }

    public function testGetAbsoluteImagePathWidthBelowZero()
    {
        $thumbnail = new Thumbnail(
            new Sdk('my-publication', new EmptyAuthenticator())
        );

        $this->assertThrowsWithMessage(
            \ValueError::class,
            'Given width is lower than 0.',
            function () use ($thumbnail) {
                $thumbnail->helper->getAbsoluteImagePath(
                    'some/path/to/an/image',
                    -1,
                    0
                );
            }
        );
    }

    public function testGetAbsoluteImagePathHeightBelowZero()
    {
        $thumbnail = new Thumbnail(
            new Sdk('my-publication', new EmptyAuthenticator())
        );

        $this->assertThrowsWithMessage(
            \ValueError::class,
            'Given height is lower than 0.',
            function () use ($thumbnail) {
                $thumbnail->helper->getAbsoluteImagePath(
                    'some/path/to/an/image',
                    0,
                    -1
                );
            }
        );
    }
}

<?php

namespace BlueBillywig\Tests\Unit\Helpers;

use BlueBillywig\Authentication\EmptyAuthenticator;
use BlueBillywig\Sdk;

class ThumbnailHelperTest extends \Codeception\Test\Unit
{
    use \Codeception\AssertThrows;

    public function testGetAbsoluteImagePath()
    {
        $sdk = new Sdk('my-publication', new EmptyAuthenticator());
        $relativePath = '/some/path/to/an/image';
        $width = 0;
        $height = 200;
        $absolutePath = $sdk->thumbnail->helper->getAbsoluteImagePath(
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
        $sdk = new Sdk('my-publication', new EmptyAuthenticator());
        $relativePath = 'some/path/to/an/image';
        $width = 300;
        $height = 0;
        $absolutePath = $sdk->thumbnail->helper->getAbsoluteImagePath(
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
        $sdk = new Sdk('my-publication', new EmptyAuthenticator());

        $this->assertThrowsWithMessage(
            \ValueError::class,
            'Given width is lower than 0.',
            function () use ($sdk) {
                $sdk->thumbnail->helper->getAbsoluteImagePath(
                    'some/path/to/an/image',
                    -1,
                    0
                );
            }
        );
    }

    public function testGetAbsoluteImagePathHeightBelowZero()
    {
        $sdk = new Sdk('my-publication', new EmptyAuthenticator());

        $this->assertThrowsWithMessage(
            \ValueError::class,
            'Given height is lower than 0.',
            function () use ($sdk) {
                $sdk->thumbnail->helper->getAbsoluteImagePath(
                    'some/path/to/an/image',
                    0,
                    -1
                );
            }
        );
    }

    public function testGetMediaClipPosterPath()
    {
        $sdk = new Sdk('my-publication', new EmptyAuthenticator());

        $this->assertEquals(
            'https://my-publication.bbvms.com/mediaclip/1234/spthumbnail/320/180.webp',
            $sdk->thumbnail->helper->getMediaClipPosterPath(1234, 320, 180)
        );
    }

    public function testGetMediaClipPosterPathDefaultsToTheServiceChoice()
    {
        $sdk = new Sdk('my-publication', new EmptyAuthenticator());

        $this->assertEquals(
            'https://my-publication.bbvms.com/mediaclip/1234/spthumbnail/default/default.webp',
            $sdk->thumbnail->helper->getMediaClipPosterPath(1234)
        );
    }

    public function testGetMediaClipPosterPathRejectsNonNumericDimensions()
    {
        // A dimension is part of the path, so anything that is not a plain number
        // falls back to 'default' rather than entering the URL.
        $sdk = new Sdk('my-publication', new EmptyAuthenticator());

        $this->assertEquals(
            'https://my-publication.bbvms.com/mediaclip/1234/spthumbnail/default/default.webp',
            $sdk->thumbnail->helper->getMediaClipPosterPath(1234, '320/../../etc', 'auto')
        );
    }

    public function testGetMediaClipPosterPathCarriesAnRpcTokenForDraftClips()
    {
        $sdk = new Sdk('my-publication', new EmptyAuthenticator());

        $this->assertEquals(
            'https://my-publication.bbvms.com/mediaclip/1234/spthumbnail/default/default.webp'
                . '?useSession=true&rpctoken=12-345678',
            $sdk->thumbnail->helper->getMediaClipPosterPath(1234, 'default', 'default', '12-345678')
        );
    }

}

<?php

namespace BlueBillywig\Tests\Unit;

use BlueBillywig\Authentication\EmptyAuthenticator;
use BlueBillywig\Entity;
use BlueBillywig\Helper;
use BlueBillywig\Sdk;

class HelperTest extends \Codeception\Test\Unit
{
    use \Codeception\AssertThrows;

    public function testGetEntity()
    {
        $sdk = new Sdk("my-publication", new EmptyAuthenticator());
        $entity = new class($sdk) extends Entity
        {
        };
        $helper = new class($entity) extends Helper
        {
        };
        $this->assertInstanceOf($entity::class, $helper->entity);
    }

    public function testGetSdk()
    {
        $sdk = new Sdk("my-publication", new EmptyAuthenticator());
        $entity = new class($sdk) extends Entity
        {
        };
        $helper = new class($entity) extends Helper
        {
        };
        $this->assertInstanceOf($sdk::class, $helper->sdk);
    }

    public function testIncorrectPropertyCall()
    {
        $sdk = new Sdk("my-publication", new EmptyAuthenticator());
        $entity = new class($sdk) extends Entity
        {
        };
        $helper = new class($entity) extends Helper
        {
        };
        $this->assertThrowsWithMessage(
            \InvalidArgumentException::class,
            "Cannot get inaccessible or undefined property someUndefinedProperty.",
            function () use ($helper) {
                $helper->someUndefinedProperty;
            }
        );
    }
}

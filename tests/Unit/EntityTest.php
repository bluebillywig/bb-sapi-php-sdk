<?php

namespace BlueBillywig\Tests\Unit;

use BlueBillywig\Authentication\EmptyAuthenticator;
use BlueBillywig\Entity;
use BlueBillywig\Helper;
use BlueBillywig\Sdk;

class MyEntityTestHelperClass extends Helper
{
}

class EntityTest extends \Codeception\Test\Unit
{
    use \Codeception\AssertThrows;

    public function testGetSdk()
    {
        $sdk = new Sdk("my-publication", new EmptyAuthenticator());
        $entity = new class($sdk) extends Entity
        {
        };
        $this->assertInstanceOf(Sdk::class, $entity->sdk);
    }

    public function testGetNestedSdk()
    {
        $sdk = new Sdk("my-publication", new EmptyAuthenticator());
        $parent = new class($sdk) extends Entity
        {
        };
        $entity = new class($parent) extends Entity
        {
        };
        $this->assertInstanceOf(Sdk::class, $entity->sdk);
    }

    public function testGetHelper()
    {
        $sdk = new Sdk("my-publication", new EmptyAuthenticator());
        $entity = new class($sdk) extends Entity
        {
            protected static string $helperCls = MyEntityTestHelperClass::class;
        };
        $this->assertInstanceOf(MyEntityTestHelperClass::class, $entity->helper);
        $helperInstance1 = $entity->helper;
        $helperInstance2 = $entity->helper;
        $this->assertEquals(spl_object_id($helperInstance1), spl_object_id($helperInstance2));
    }

    public function testGetUndefinedHelper()
    {
        $sdk = new Sdk("my-publication", new EmptyAuthenticator());
        $entity = new class($sdk) extends Entity
        {
        };
        $this->assertThrowsWithMessage(
            \Exception::class,
            $entity::class . ' does not have a helper defined.',
            function () use ($entity) {
                $entity->helper;
            }
        );
    }

    public function testGetUndefinedProperty()
    {
        $sdk = new Sdk("my-publication", new EmptyAuthenticator());
        $entity = new class($sdk) extends Entity
        {
        };
        $this->assertThrowsWithMessage(
            \InvalidArgumentException::class,
            "Cannot get inaccessible or undefined property someUndefinedProperty.",
            function () use ($entity) {
                $entity->someUndefinedProperty;
            }
        );
    }
}

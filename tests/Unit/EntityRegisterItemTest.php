<?php

namespace BlueBillywig\Tests\Unit;

use BlueBillywig\Authentication\EmptyAuthenticator;
use BlueBillywig\Entity;
use BlueBillywig\EntityRegisterItem;
use BlueBillywig\Sdk;
use Codeception\Stub\Expected;

class MyEntityRegisterItemTestEntity extends Entity
{
    public $myProperty = "some value";

    public function myMethod()
    {
    }
}

class MyEntityRegisterItemTestEntityParent extends Entity
{
    public int $factoryCalled = 0;
}

class EntityRegisterItemTest extends \Codeception\Test\Unit
{
    public function testCreateEntityFromFactory()
    {
        $sdk = new Sdk("my-publication", new EmptyAuthenticator());

        $entityParent = new MyEntityRegisterItemTestEntityParent($sdk);

        $entityRegisterItem = $this->construct(
            EntityRegisterItem::class,
            [
                MyEntityRegisterItemTestEntity::class,
                $entityParent,
                function ($parent) {
                    $parent->factoryCalled++;
                    return new MyEntityRegisterItemTestEntity($parent);
                }
            ]
        );
        $entityRegisterItem->getEntityClass();
        $entityRegisterItem->getEntityClass();
        $this->assertEquals(1, $entityParent->factoryCalled);
    }

    public function testCallEntityMethod()
    {
        $entityRegisterItem = $this->make(
            EntityRegisterItem::class,
            [
                'instance' => $this->construct(
                    MyEntityRegisterItemTestEntity::class,
                    [new Sdk("my-publication", new EmptyAuthenticator())],
                    ['myMethod' => Expected::once()]
                )
            ]
        );
        $entityRegisterItem->myMethod();
    }

    public function testGetEntityProperty()
    {
        $sdk = new Sdk("my-publication", new EmptyAuthenticator());
        $entityRegisterItem = new EntityRegisterItem(MyEntityRegisterItemTestEntity::class, $sdk);
        $this->assertEquals('some value', $entityRegisterItem->myProperty);
    }

    public function testSetEntityProperty()
    {
        $sdk = new Sdk("my-publication", new EmptyAuthenticator());
        $entityRegisterItem = new EntityRegisterItem(MyEntityRegisterItemTestEntity::class, $sdk);
        $this->assertEquals('some value', $entityRegisterItem->myProperty);
        $entityRegisterItem->myProperty = 'some other value';
        $this->assertEquals('some other value', $entityRegisterItem->myProperty);
    }
}

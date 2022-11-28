<?php

namespace BlueBillywig\Tests\Unit;

use BlueBillywig\Authentication\EmptyAuthenticator;
use BlueBillywig\Entity;
use BlueBillywig\EntityRegister;
use BlueBillywig\Sdk;

class MyEntityRegisterTestNestedEntity extends Entity
{
}

class MyEntityRegisterTestEntity extends Entity
{
    protected static array $entitiesCls = [
        [MyEntityRegisterTestNestedEntity::class]
    ];
}

class MyEntityRegisterTestSdk extends Sdk
{
    protected static array $entitiesCls = [
        ["myDifferentEntityName" => MyEntityRegisterTestEntity::class]
    ];
}

class MyEntityRegisterTestIncorrectEntity
{
}

class EntityRegisterTest extends \Codeception\Test\Unit
{
    use \Codeception\AssertThrows;

    public function testGetNestedEntity()
    {
        $sdk = new MyEntityRegisterTestSdk("my-publication", new EmptyAuthenticator());
        $this->assertEquals(MyEntityRegisterTestNestedEntity::class, $sdk->myDifferentEntityName->myEntityRegisterTestNestedEntity->getEntityClass());
    }

    public function testRegisterEntityIncorrectType()
    {
        $this->assertThrowsWithMessage(
            \TypeError::class,
            'Given entity ' . MyEntityRegisterTestIncorrectEntity::class . ' is not a subclass of ' . Entity::class . '.',
            function () {
                new class() extends EntityRegister
                {
                    protected static array $entitiesCls = [
                        [MyEntityRegisterTestEntity::class],
                        [MyEntityRegisterTestIncorrectEntity::class]
                    ];

                    protected function getSdk(): Sdk
                    {
                        return new Sdk("my-publication", new EmptyAuthenticator());
                    }
                };
            }
        );
    }

    public function testRegisterEmptyMapping()
    {
        $this->assertThrowsWithMessage(
            \UnexpectedValueException::class,
            "Empty entity mapping given.",
            function () {
                new class() extends EntityRegister
                {
                    protected static array $entitiesCls = [
                        [MyEntityRegisterTestEntity::class],
                        []
                    ];

                    protected function getSdk(): Sdk
                    {
                        return new Sdk("my-publication", new EmptyAuthenticator());
                    }
                };
            }
        );
    }
}

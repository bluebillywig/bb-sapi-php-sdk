<?php

namespace BlueBillywig\Tests\Unit;

use BlueBillywig\AutoAsyncToSyncCaller;
use Codeception\Stub\Expected;
use GuzzleHttp\Promise\Promise;

class MyAutoAsyncToSyncClass
{
    use AutoAsyncToSyncCaller;

    public function methodThatIsAsync()
    {
    }

    public function method2ThatIsAsync()
    {
    }

    public function method2ThatIs()
    {
    }
}

class AutoAsyncToSyncCallerTest extends \Codeception\Test\Unit
{
    use \Codeception\AssertThrows;

    public function testAutoAsyncToSyncCall()
    {
        $mock = $this->make(MyAutoAsyncToSyncClass::class, [
            'methodThatIsAsync' => Expected::once(new Promise),
            'method2ThatIsAsync' => Expected::never(new Promise),
            'method2ThatIs' => Expected::once()
        ]);

        $this->assertDoesNotThrow(\Error::class, [$mock, 'methodThatIs']);
        $this->assertDoesNotThrow(\Error::class, [$mock, 'method2ThatIs']);
    }
}

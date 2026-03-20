<?php

namespace BlueBillywig;

use BlueBillywig\Sdk;

/**
 * Base Entity class.
 * An Entity represents a resource on the Blue Billywig SAPI.
 * Entity class methods should only call one API method and should always return a Response object.
 *
 * @property-read \BlueBillywig\Helper $helper
 * @property-read \BlueBillywig\Sdk $sdk
 */
abstract class Entity extends EntityRegister
{
    use AutoAsyncToSyncCaller;

    private Sdk $sdk;
    private Helper $helper;

    protected static string $helperCls;

    public function __construct(
        private readonly EntityRegister $parent
    ) {
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        if ($name === 'sdk') {
            return $this->getSdk();
        } elseif ($name === 'helper') {
            if (!isset(static::$helperCls)) {
                throw new \Exception(static::class . ' does not have a helper defined.');
            } elseif (!isset($this->helper)) {
                $this->helper = new (static::$helperCls)($this);
            }
            return $this->helper;
        }
        return parent::__get($name);
    }

    /**
     * Retrieve the Sdk instance to which this Entity is linked.
     */
    protected function getSdk(): Sdk
    {
        if (!isset($this->sdk)) {
            $this->sdk = $this->parent->getSdk();
        }
        return $this->sdk;
    }
}

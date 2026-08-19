<?php

namespace BlueBillywig\Tests\Unit\Search;

use BlueBillywig\Search\FieldMap;

class FieldMapTest extends \Codeception\Test\Unit
{
    public function testKnownFieldsAreTranslated()
    {
        $this->assertEquals('statusSort', FieldMap::resolve('status'));
        $this->assertEquals('mediatypeSort', FieldMap::resolve('mediatype'));
        $this->assertEquals('views_int', FieldMap::resolve('views'));
    }

    public function testTitleIsRewrittenToTheCaseInsensitiveField()
    {
        // The one field made explicitly case-insensitive, so that "koert" finds
        // "Koert Live". formatengine applies this rewrite to titleSort only.
        $this->assertEquals('title_cistr', FieldMap::resolve('title'));
    }

    public function testTheWholeMapIsAvailableForIntrospection()
    {
        $map = FieldMap::all();

        $this->assertArrayHasKey('status', $map);
        $this->assertEquals('statusSort', $map['status']);
    }

    public function testAnUnknownFieldIsPassedThroughUntouched()
    {
        // Lets a caller use a raw Solr field name the map does not cover.
        $this->assertEquals('some_custom_field', FieldMap::resolve('some_custom_field'));
    }
}

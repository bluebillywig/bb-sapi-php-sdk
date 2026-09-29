<?php

namespace BlueBillywig\Tests\Unit\Search;

use BlueBillywig\Search\Filter;
use BlueBillywig\Search\FilterOperator;
use BlueBillywig\Search\FilterSet;

/**
 * The filterset wire format.
 *
 * A filterset is sent to SAPI as JSON and compiled there, by the same
 * SearchRequestHelper that serves the OVP. These tests pin the SHAPE, because
 * the shape is the contract; the semantics are the server's to own.
 *
 * The equivalences below were checked against a live publication of 5777 clips:
 * `filterset` and a hand-compiled `fq` return identical counts (published 4361,
 * title contains "koert" 1, hasInteractivity 868, published AND video 3679).
 */
class FilterSetTest extends \Codeception\Test\Unit
{
    public function testAFilterBecomesAGroupOfOne()
    {
        $filterSet = FilterSet::create()->where('status', FilterOperator::Is, 'published');

        $this->assertEquals(
            [['filters' => [['field' => 'status', 'operator' => 'is', 'value' => 'published']]]],
            $filterSet->toArray()
        );
    }

    public function testEachWhereAddsItsOwnGroupSoTheyAreAnded()
    {
        $filterSet = FilterSet::create()
            ->where('status', FilterOperator::Is, 'published')
            ->where('mediatype', FilterOperator::Is, 'video');

        $this->assertCount(2, $filterSet->toArray());
    }

    public function testFiltersInOneGroupAreKeptTogetherSoTheyAreOred()
    {
        $filterSet = FilterSet::create()->andGroup(
            new Filter('status', FilterOperator::Is, 'published'),
            new Filter('status', FilterOperator::Is, 'draft')
        );

        $groups = $filterSet->toArray();

        $this->assertCount(1, $groups);
        $this->assertCount(2, $groups[0]['filters']);
    }

    public function testAnEntityTypeIsCarriedButOmittedWhenAbsent()
    {
        $withType = FilterSet::create()
            ->where('status', FilterOperator::Is, 'published', 'mediaclip')
            ->toArray();
        $withoutType = FilterSet::create()
            ->where('status', FilterOperator::Is, 'published')
            ->toArray();

        $this->assertEquals('mediaclip', $withType[0]['filters'][0]['type']);
        $this->assertArrayNotHasKey('type', $withoutType[0]['filters'][0]);
    }

    public function testSeveralValuesAreCarriedAsAList()
    {
        $filterSet = FilterSet::create()
            ->where('status', FilterOperator::IsAnyOf, ['published', 'draft']);

        $this->assertEquals(['published', 'draft'], $filterSet->toArray()[0]['filters'][0]['value']);
    }

    public function testAValuelessFilterIsDropped()
    {
        $this->assertEquals([], FilterSet::create()->where('status', FilterOperator::Is, '')->toArray());
    }

    public function testAGroupLeftWithNoFiltersIsDropped()
    {
        $filterSet = FilterSet::create()
            ->andGroup(new Filter('status', FilterOperator::Is, ''))
            ->where('mediatype', FilterOperator::Is, 'video');

        $groups = $filterSet->toArray();

        $this->assertCount(1, $groups);
        $this->assertEquals('mediatype', $groups[0]['filters'][0]['field']);
    }

    public function testPresenceOperatorsCarryThePlaceholderTheBackendRequires()
    {
        // The backend's compiler skips ANY filter whose value is empty —
        // presence tests included — so a bare isEmpty silently never fires
        // (verified live: it returned the full unfiltered publication). OVP6
        // sends the placeholder '*', with the comment "backend needs a value to
        // work"; the SDK must do the same.
        $filterSet = FilterSet::create()->where('author', FilterOperator::IsEmpty);

        $this->assertFalse($filterSet->isEmpty());
        $this->assertSame(
            [['filters' => [['field' => 'author', 'operator' => 'isEmpty', 'value' => '*']]]],
            $filterSet->toArray()
        );
    }

    public function testThePlaceholderOverridesWhateverValueACallerSupplied()
    {
        // '*' is the canonical wire value for presence tests; a caller-supplied
        // value would only vary the bytes without changing the semantics.
        $filterSet = FilterSet::create()->where('author', FilterOperator::IsNotEmpty, 'anything');

        $this->assertSame('*', $filterSet->toArray()[0]['filters'][0]['value']);
    }

    public function testNumbersAndBooleansAreNormalisedToTheStringsTheBackendUnderstands()
    {
        // Verified live: a JSON number works, but a JSON boolean gets mangled
        // into "1" by the backend and matches NOTHING (hasInteractivity true as
        // a boolean returned 0; as the string 'true', 868). Normalising here is
        // what keeps an ingested OVP/Automations filterset working.
        $this->assertSame(
            '100',
            FilterSet::create()->where('views', FilterOperator::IsGreaterThan, 100)->toArray()[0]['filters'][0]['value']
        );
        $this->assertSame(
            'true',
            FilterSet::create()->where('hasInteractivity', FilterOperator::Is, true)->toArray()[0]['filters'][0]['value']
        );
        $this->assertSame(
            'false',
            FilterSet::create()->where('isImported', FilterOperator::Is, false)->toArray()[0]['filters'][0]['value']
        );
        $this->assertSame(
            '2.5',
            FilterSet::create()->where('views', FilterOperator::IsGreaterThan, 2.5)->toArray()[0]['filters'][0]['value']
        );
        $this->assertSame(
            ['1', '2.5', 'true'],
            FilterSet::create()->where('views', FilterOperator::IsAnyOf, [1, 2.5, true])->toArray()[0]['filters'][0]['value']
        );
    }

    public function testNonScalarArrayMembersAreDroppedNotStringified()
    {
        // strval() on an array yields the literal string "Array" (plus a
        // warning); junk members are dropped instead.
        $filterSet = FilterSet::fromArray([
            ['filters' => [['field' => 'status', 'operator' => 'is', 'value' => ['published', ['nested']]]]],
        ]);

        $this->assertSame(['published'], $filterSet->toArray()[0]['filters'][0]['value']);
    }

    public function testAnUnknownOperatorThrowsADescriptiveException()
    {
        // PHP is the strict SDK: the operator is a real enum, so an OVP
        // filterset using an operator this enum does not know yet cannot
        // round-trip. Fail with a message that says what to do about it,
        // instead of the bare ValueError enums throw by default.
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown filter operator "sneaky"');

        FilterSet::fromArray([
            ['filters' => [['field' => 'status', 'operator' => 'sneaky', 'value' => 'x']]],
        ]);
    }

    public function testAMissingOperatorThrowsTheSameException()
    {
        $this->expectException(\InvalidArgumentException::class);

        FilterSet::fromArray([['filters' => [['field' => 'status', 'value' => 'x']]]]);
    }

    public function testMalformedGroupsAndFiltersAreSkippedNotFatal()
    {
        // fromArray ingests external data (stored filtersets, request bodies);
        // one junk entry must not take the whole filterset down.
        $filterSet = FilterSet::fromArray([
            'not-a-group',
            ['filters' => 'not-a-list'],
            ['filters' => ['not-a-filter', ['field' => 'status', 'operator' => 'is', 'value' => 'published']]],
        ]);

        $this->assertSame(
            [['filters' => [['field' => 'status', 'operator' => 'is', 'value' => 'published']]]],
            $filterSet->toArray()
        );
    }

    public function testJunkValuesAreTreatedAsNoValue()
    {
        $filterSet = FilterSet::fromArray([
            ['filters' => [['field' => 'status', 'operator' => 'is', 'value' => ['x' => ['nested' => true]]]]],
        ]);

        // The nested-array member is dropped, leaving nothing to match on.
        $this->assertTrue($filterSet->isEmpty());

        // A non-scalar, non-array value (an object) is "no value", not garbage.
        $objectValued = FilterSet::fromArray([
            ['filters' => [['field' => 'status', 'operator' => 'is', 'value' => new \stdClass()]]],
        ]);
        $this->assertTrue($objectValued->isEmpty());
    }

    public function testAnEmptyFilterSetIsEmpty()
    {
        $this->assertTrue(FilterSet::create()->isEmpty());
        $this->assertEquals([], FilterSet::create()->toArray());
    }

    public function testItSerialisesToTheJsonSapiExpects()
    {
        $json = FilterSet::create()->where('status', FilterOperator::Is, 'published')->toJson();

        $this->assertEquals(
            '[{"filters":[{"field":"status","operator":"is","value":"published"}]}]',
            $json
        );
    }

    public function testItRoundTripsFromTheOvpEnvelope()
    {
        $filterSet = FilterSet::fromArray([
            'type' => 'SearchRequest',
            'filterSet' => [
                ['filters' => [
                    ['field' => 'status', 'operator' => 'is', 'value' => 'published', 'type' => 'mediaclip'],
                ]],
            ],
        ]);

        $this->assertEquals(
            [['filters' => [[
                'field' => 'status',
                'operator' => 'is',
                'value' => 'published',
                'type' => 'mediaclip',
            ]]]],
            $filterSet->toArray()
        );
    }

    public function testItRoundTripsFromABareListOfGroups()
    {
        $filterSet = FilterSet::fromArray([
            ['filters' => [['field' => 'status', 'operator' => 'is', 'value' => 'published']]],
        ]);

        $this->assertFalse($filterSet->isEmpty());
    }

    public function testAFilterBuiltFromAnArrayKeepsItsParts()
    {
        $filter = Filter::fromArray([
            'field' => 'status',
            'operator' => 'is',
            'value' => 'published',
            'type' => 'mediaclip',
        ]);

        $this->assertEquals('status', $filter->field);
        $this->assertEquals(FilterOperator::Is, $filter->operator);
        $this->assertEquals(['published'], $filter->values());
        $this->assertEquals('mediaclip', $filter->type);
        $this->assertFalse($filter->isEmpty());
    }

    public function testAFilterBuiltFromAnArrayWithoutValueOrTypeIsEmpty()
    {
        $filter = Filter::fromArray(['field' => 'status', 'operator' => 'is', 'type' => '']);

        $this->assertNull($filter->type);
        $this->assertTrue($filter->isEmpty());
        $this->assertEquals([''], $filter->values());
    }

    public function testAFilterWhoseValuesAreAllBlankIsEmpty()
    {
        $this->assertTrue((new Filter('status', FilterOperator::Is, ['', '']))->isEmpty());
        $this->assertFalse((new Filter('status', FilterOperator::Is, ['', 'draft']))->isEmpty());
    }

    public function testPresenceOperatorsAreNeverConsideredEmpty()
    {
        $this->assertTrue(FilterOperator::IsEmpty->ignoresValue());
        $this->assertTrue(FilterOperator::IsNotEmpty->ignoresValue());
        $this->assertFalse(FilterOperator::Is->ignoresValue());
    }
}

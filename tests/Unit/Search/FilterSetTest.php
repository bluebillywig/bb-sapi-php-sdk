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

    public function testPresenceOperatorsSurviveWithoutAValue()
    {
        // `isEmpty` / `isNotEmpty` test presence, so an absent value is correct
        // and must not cause the filter to be dropped.
        $filterSet = FilterSet::create()->where('author', FilterOperator::IsEmpty);

        $this->assertCount(1, $filterSet->toArray());
        $this->assertFalse($filterSet->isEmpty());
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

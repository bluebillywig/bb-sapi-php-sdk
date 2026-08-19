<?php

namespace BlueBillywig\Tests\Unit\Search;

use BlueBillywig\Search\FilterOperator;
use BlueBillywig\Search\FilterSet;
use BlueBillywig\Search\SearchQuery;

/**
 * How a filtered search is encoded onto the URL.
 *
 * This is the failure that is hardest to notice: SAPI ignores the wrong shape
 * and still answers HTTP 200, with a body carrying neither `numfound` nor
 * `items` — indistinguishable from an empty library. So the encoding is pinned.
 */
class SearchQueryTest extends \Codeception\Test\Unit
{
    public function testFiltersAreEncodedAsIndexedParameters()
    {
        $query = (new SearchQuery(
            'mediaclip',
            FilterSet::create()->where('status', FilterOperator::Is, 'published')
        ))->toPath();

        // Accepted by SAPI.
        $this->assertStringContainsString('fq%5B0%5D=', $query);
        // The shape http_build_query(['fq[]' => [...]]) produces, which is ignored.
        $this->assertStringNotContainsString('fq%5B%5D%5B0%5D=', $query);
    }

    public function testTheCompiledFilterSurvivesTheRoundTrip()
    {
        $query = (new SearchQuery(
            'mediaclip',
            FilterSet::create()->where('status', FilterOperator::Is, 'published')
        ))->toPath();

        parse_str((string)parse_url($query, PHP_URL_QUERY), $parsed);

        $this->assertEquals(['(statusSort:"published")'], $parsed['fq']);
    }

    public function testAnUnfilteredSearchSendsNoFilterParameter()
    {
        $query = (new SearchQuery('mediaclip'))->toPath();

        $this->assertStringNotContainsString('fq', $query);
        $this->assertStringStartsWith('/sapi/mediaclip?', $query);
    }

    public function testAnEmptyFilterSetSendsNoFilterParameter()
    {
        $query = (new SearchQuery('mediaclip', FilterSet::create()))->toPath();

        $this->assertStringNotContainsString('fq', $query);
    }

    public function testTheEntityTypeSelectsTheEndpoint()
    {
        $this->assertStringStartsWith('/sapi/channel?', (new SearchQuery('channel'))->toPath());
    }

    public function testPagingAndSortingArePassedThrough()
    {
        $query = (new SearchQuery('mediaclip', null, 50, 100, 'title asc'))->toPath();
        parse_str((string)parse_url($query, PHP_URL_QUERY), $parsed);

        $this->assertEquals('50', $parsed['limit']);
        $this->assertEquals('100', $parsed['offset']);
        $this->assertEquals('title asc', $parsed['sort']);
    }

    public function testItStringifiesToItsPath()
    {
        $query = new SearchQuery('mediaclip');

        $this->assertEquals($query->toPath(), (string)$query);
    }

    public function testAFreeTextQueryIsPassedThrough()
    {
        $query = (new SearchQuery('mediaclip', null, 15, 0, null, 'holiday'))->toPath();
        parse_str((string)parse_url($query, PHP_URL_QUERY), $parsed);

        $this->assertEquals('holiday', $parsed['q']);
        $this->assertArrayNotHasKey('sort', $parsed);
    }

}

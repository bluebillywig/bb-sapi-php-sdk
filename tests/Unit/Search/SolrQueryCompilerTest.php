<?php

namespace BlueBillywig\Tests\Unit\Search;

use BlueBillywig\Search\Filter;
use BlueBillywig\Search\FilterOperator;
use BlueBillywig\Search\FilterSet;

/**
 * The filterset compiler, pinned against formatengine's SearchRequestHelper.
 *
 * Every expectation here was checked against the live SAPI while porting; the
 * counts are in the PR description. The reason these belong in the SDK is that a
 * wrong filter does not fail — SAPI answers 200 with an empty envelope, so a
 * broken search is indistinguishable from an empty library.
 */
class SolrQueryCompilerTest extends \Codeception\Test\Unit
{
    public function testGroupsAreAndedAndFiltersWithinAGroupAreOred()
    {
        $filterSet = (FilterSet::create())
            ->andGroup(
                new Filter('status', FilterOperator::Is, 'published'),
                new Filter('status', FilterOperator::Is, 'draft')
            )
            ->where('mediatype', FilterOperator::Is, 'video');

        $this->assertEquals(
            '((statusSort:"published") OR (statusSort:"draft")) AND (mediatypeSort:"video")',
            $filterSet->toSolrQuery()
        );
    }

    public function testFieldNamesAreTranslatedToTheirSolrEquivalent()
    {
        $this->assertEquals(
            '(sourcetypeSort:"on_demand")',
            FilterSet::create()->where('sourcetype', FilterOperator::Is, 'on_demand')->toSolrQuery()
        );
    }

    public function testTitleIsMadeCaseInsensitiveAndMatchesSubstrings()
    {
        // titleSort is rewritten to title_cistr, and `contains` adds the wildcard
        // form: an exact phrase alone never matches "Koert Live" for "koert".
        $this->assertEquals(
            '(title_cistr:"koert" OR title_cistr:*koert*)',
            FilterSet::create()->where('title', FilterOperator::Contains, 'koert')->toSolrQuery()
        );
    }

    public function testASpaceInAWildcardTermIsEscapedRatherThanQuoted()
    {
        // Quoting would turn the term back into a phrase and disable the wildcards.
        $this->assertEquals(
            '(title_cistr:"Koert Live" OR title_cistr:*Koert\\ Live*)',
            FilterSet::create()->where('title', FilterOperator::Contains, 'Koert Live')->toSolrQuery()
        );
    }

    public function testMultiValuedFieldsTestCardinalityNotEquality()
    {
        // The cardinality branch keys off the `_txtmulti` / `_strmulti` suffixes,
        // exactly as formatengine does. Note that no field in FieldMap carries
        // one — `timeline_multistring` matches neither — so this only fires for a
        // caller passing a raw Solr field name. Faithful to formatengine, which
        // means `timeline` gets plain term semantics there too; worth raising
        // upstream rather than diverging here.
        $this->assertEquals(
            '(tags_strmulti:[1 TO *])',
            FilterSet::create()->where('tags_strmulti', FilterOperator::Is, 'true')->toSolrQuery()
        );
        $this->assertEquals(
            '(-tags_strmulti:[1 TO *])',
            FilterSet::create()->where('tags_strmulti', FilterOperator::Is, 'false')->toSolrQuery()
        );
    }

    public function testTimelineGetsPlainTermSemanticsAsInFormatengine()
    {
        // Pinned deliberately: `timeline_multistring` contains neither suffix, so
        // it is a term match. Use the `hasInteractivity` field for presence.
        $this->assertEquals(
            '(timeline_multistring:"true")',
            FilterSet::create()->where('timeline', FilterOperator::Is, 'true')->toSolrQuery()
        );
    }

    public function testBooleanFalseIsExpressedAsTheAbsenceOfTrue()
    {
        $this->assertEquals(
            '(isImported_boolean:true)',
            FilterSet::create()->where('isImported', FilterOperator::Is, 'true')->toSolrQuery()
        );
        $this->assertEquals(
            '(*:* AND -isImported_boolean:true)',
            FilterSet::create()->where('isImported', FilterOperator::Is, 'false')->toSolrQuery()
        );
    }

    public function testABooleanAskedForBothValuesFiltersNothing()
    {
        $this->assertEquals(
            '',
            FilterSet::create()
                ->where('isImported', FilterOperator::Is, ['true', 'false'])
                ->toSolrQuery()
        );
    }

    public function testInteractivityIsPresenceOfATimeline()
    {
        $this->assertEquals(
            '((timeline_multistring:[* TO *]))',
            FilterSet::create()->where('hasInteractivity', FilterOperator::Is, 'true')->toSolrQuery()
        );
        $this->assertEquals(
            '((*:* AND -timeline_multistring:[* TO *]))',
            FilterSet::create()->where('hasInteractivity', FilterOperator::Is, 'false')->toSolrQuery()
        );
    }

    public function testFulltextCarriesNoFieldName()
    {
        $this->assertEquals(
            '("holiday" OR *holiday*)',
            FilterSet::create()->where('fulltext', FilterOperator::Contains, 'holiday')->toSolrQuery()
        );
    }

    public function testAnyOfJoinsWithOrAndAllOfJoinsWithAnd()
    {
        $this->assertEquals(
            '(statusSort:"published" OR statusSort:"draft")',
            FilterSet::create()->where('status', FilterOperator::IsAnyOf, ['published', 'draft'])->toSolrQuery()
        );
        $this->assertEquals(
            '(catSort:"news" AND catSort:"sport")',
            FilterSet::create()->where('cat', FilterOperator::ContainsAllOf, ['news', 'sport'])->toSolrQuery()
        );
    }

    public function testPresenceOperatorsSurviveAnEmptyValue()
    {
        // The outer parentheses are the group wrapper.
        $this->assertEquals(
            '(( *:* -authorSort:*))',
            FilterSet::create()->where('author', FilterOperator::IsEmpty)->toSolrQuery()
        );
        $this->assertEquals(
            '(( authorSort:["" TO *]))',
            FilterSet::create()->where('author', FilterOperator::IsNotEmpty)->toSolrQuery()
        );
    }

    public function testAFilterWithNoValueIsDropped()
    {
        $this->assertEquals(
            '',
            FilterSet::create()->where('status', FilterOperator::Is, '')->toSolrQuery()
        );
    }

    public function testRelativeAndAbsoluteRanges()
    {
        $this->assertEquals(
            '(createddate:[NOW-7DAY TO NOW])',
            FilterSet::create()->where('createddate', FilterOperator::IsInTheLast, '7DAY')->toSolrQuery()
        );
        $this->assertEquals(
            '(createddate:[2026-01-01 TO *])',
            FilterSet::create()->where('createddate', FilterOperator::IsAfter, '2026-01-01')->toSolrQuery()
        );
    }

    public function testARangeValueCannotBreakOutOfTheBracket()
    {
        // Anything outside the date/number/NOW vocabulary is dropped, so a
        // caller-supplied value cannot close the bracket and continue the query.
        $query = FilterSet::create()
            ->where('createddate', FilterOperator::IsAfter, '2026-01-01]) OR evil:*')
            ->toSolrQuery();

        // The `*` that remains is the range's own open end, not the caller's.
        $this->assertEquals('(createddate:[2026-01-01ORevil: TO *])', $query);
        $this->assertStringNotContainsString(']) OR', $query);
        $this->assertEquals(1, substr_count($query, ']'));
    }

    public function testAQuoteInAValueCannotCloseTheTermEarly()
    {
        $this->assertEquals(
            '(statusSort:"published")',
            FilterSet::create()->where('status', FilterOperator::Is, 'pub"lished')->toSolrQuery()
        );
    }

    public function testAnEntityTypeConstrainsTheStatement()
    {
        $this->assertEquals(
            '((typeSort:MediaClip AND (statusSort:"published")))',
            FilterSet::create()
                ->where('status', FilterOperator::Is, 'published', 'mediaclip')
                ->toSolrQuery()
        );
    }

    public function testAnEmptyFilterSetCompilesToNothing()
    {
        $this->assertEquals('', FilterSet::create()->toSolrQuery());
        $this->assertTrue(FilterSet::create()->isEmpty());
    }

    public function testAFilterSetRoundTripsFromTheOvpEnvelope()
    {
        $filterSet = FilterSet::fromArray([
            'type' => 'SearchRequest',
            'filterSet' => [
                ['filters' => [
                    ['field' => 'status', 'operator' => 'is', 'value' => 'published'],
                ]],
            ],
        ]);

        $this->assertEquals('(statusSort:"published")', $filterSet->toSolrQuery());
    }

    public function testNegatedEqualityOperators()
    {
        $this->assertEquals(
            '(( *:* -statusSort:"published"))',
            FilterSet::create()->where('status', FilterOperator::IsNot, 'published')->toSolrQuery()
        );
        $this->assertEquals(
            '(( *:* -statusSort:"published") OR ( *:* -statusSort:"draft"))',
            FilterSet::create()->where('status', FilterOperator::IsNotAnyOf, ['published', 'draft'])->toSolrQuery()
        );
    }

    public function testContainsAnyOfIsAnExactMatchPerValue()
    {
        // Unlike `contains`, it adds no wildcard form.
        $this->assertEquals(
            '(catSort:"news" OR catSort:"sport")',
            FilterSet::create()->where('cat', FilterOperator::ContainsAnyOf, ['news', 'sport'])->toSolrQuery()
        );
    }

    public function testDoesNotContainExcludesASubstringOnASingleValuedField()
    {
        $this->assertEquals(
            '(( *:* -title_cistr:*holiday*))',
            FilterSet::create()->where('title', FilterOperator::DoesNotContain, 'holiday')->toSolrQuery()
        );
    }

    public function testDoesNotContainExcludesAnExactValueOnAMultiValuedField()
    {
        // Multi-valued fields hold discrete terms, so a substring test is wrong.
        $this->assertEquals(
            '(( *:* -tags_strmulti:"news"))',
            FilterSet::create()->where('tags_strmulti', FilterOperator::DoesNotContain, 'news')->toSolrQuery()
        );
    }

    public function testDoesNotContainAnyOfRequiresEveryExclusionToHold()
    {
        $this->assertEquals(
            '(( *:* -title_cistr:*a*) AND ( *:* -title_cistr:*b*))',
            FilterSet::create()->where('title', FilterOperator::DoesNotContainAnyOf, ['a', 'b'])->toSolrQuery()
        );
    }

    public function testUpperBoundRanges()
    {
        $this->assertEquals(
            '(createddate:[ * TO 2026-01-01])',
            FilterSet::create()->where('createddate', FilterOperator::IsBefore, '2026-01-01')->toSolrQuery()
        );
        $this->assertEquals(
            '(views_int:[ * TO 100])',
            FilterSet::create()->where('views', FilterOperator::IsSmallerThan, '100')->toSolrQuery()
        );
    }

    public function testLowerBoundRange()
    {
        $this->assertEquals(
            '(views_int:[100 TO *])',
            FilterSet::create()->where('views', FilterOperator::IsGreaterThan, '100')->toSolrQuery()
        );
    }

    public function testNegatedRelativeRange()
    {
        $this->assertEquals(
            '((*:* -createddate:[NOW-7DAY TO NOW]))',
            FilterSet::create()->where('createddate', FilterOperator::IsNotInTheLast, '7DAY')->toSolrQuery()
        );
    }

    public function testAValueThatAlreadyCarriesRangeSyntaxIsPassedThroughUnquoted()
    {
        $this->assertEquals(
            '(views_int:[1 TO 10])',
            FilterSet::create()->where('views', FilterOperator::Is, '[1 TO 10]')->toSolrQuery()
        );
    }

    public function testAPlusIsTreatedAsASpaceRatherThanAnOperator()
    {
        $this->assertEquals(
            '(statusSort:"a b")',
            FilterSet::create()->where('status', FilterOperator::Is, 'a+b')->toSolrQuery()
        );
    }

    public function testSeveralEntityTypesAreOredTogether()
    {
        $this->assertEquals(
            '(((typeSort:Project OR typeSort:MediaClip OR typeSort:MediaClipList)'
                . ' AND (statusSort:"published")))',
            FilterSet::create()
                ->where('status', FilterOperator::Is, 'published', 'search')
                ->toSolrQuery()
        );
    }

    public function testAnUnknownEntityTypeIsUsedVerbatim()
    {
        $this->assertEquals(
            '((typeSort:Shorts AND (statusSort:"published")))',
            FilterSet::create()
                ->where('status', FilterOperator::Is, 'published', 'Shorts')
                ->toSolrQuery()
        );
    }

    public function testAGroupWithOnlyEmptyFiltersIsDropped()
    {
        $filterSet = FilterSet::create()
            ->andGroup(new Filter('status', FilterOperator::Is, ''))
            ->where('mediatype', FilterOperator::Is, 'video');

        $this->assertEquals('(mediatypeSort:"video")', $filterSet->toSolrQuery());
    }

    public function testAFilterSetCanBeBuiltFromABareListOfGroups()
    {
        $filterSet = FilterSet::fromArray([
            ['filters' => [['field' => 'status', 'operator' => 'is', 'value' => 'published']]],
        ]);

        $this->assertEquals('(statusSort:"published")', $filterSet->toSolrQuery());
    }

    public function testAFilterFromArrayKeepsItsEntityType()
    {
        $filter = Filter::fromArray([
            'field' => 'status',
            'operator' => 'is',
            'value' => 'published',
            'type' => 'mediaclip',
        ]);

        $this->assertEquals('mediaclip', $filter->type);
        $this->assertEquals(['published'], $filter->values());
        $this->assertFalse($filter->isEmpty());
    }

    public function testAFilterFromArrayWithoutAValueOrTypeIsEmpty()
    {
        $filter = Filter::fromArray(['field' => 'status', 'operator' => 'is', 'type' => '']);

        $this->assertNull($filter->type);
        $this->assertTrue($filter->isEmpty());
    }

}

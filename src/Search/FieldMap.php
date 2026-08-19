<?php

namespace BlueBillywig\Search;

/**
 * Maps OVP field names to the Solr fields the search index actually uses.
 *
 * Ported from `MediaStore::getFieldMapping()` in formatengine, which is the
 * authority. Callers write filters in OVP terms (`title`, `status`, `views`) and
 * the compiler translates; a caller that already holds a Solr field name can
 * pass it through untouched, because unmapped names are returned as-is.
 *
 * Keep this in step with formatengine. A silent divergence here does not fail —
 * it just filters on a field that does not exist, and Solr answers "no results",
 * which is indistinguishable from an empty library.
 */
final class FieldMap
{
    /**
     * @var array<string, string>
     */
    private const MAP = [
        'author' => 'authorSort',
        'cat' => 'catSort',
        'copyright' => 'copyrightSort',
        'embargoFrom' => 'embargofrom_date',
        'embargoTo' => 'embargoto_date',
        'createddate' => 'createddate',
        'updateddate' => 'modifieddate',
        'exportchannelid' => 'exportchannelid_multistring',
        'exportsystemid' => 'exportsystemid_multistring',
        'location' => 'location_loc',
        'mediatype' => 'mediatypeSort',
        'originalfilename' => 'originalfilenameSort',
        'parentid' => 'parentid',
        'parentpublicationid' => 'parentpublicationid',
        'publication' => 'publicationSort',
        'publisher' => 'publisherSort',
        'publisheddate' => 'published_date',
        'sourcetype' => 'sourcetypeSort',
        'sourceid' => 'sourceid_string',
        'src' => 'src_string',
        'mainthumbnail' => 'mainthumbnail_string',
        'ratingcount' => 'ratingcount_int',
        'ratingvalue' => 'ratingvalue_int',
        'status' => 'statusSort',
        'title' => 'titleSort',
        'type' => 'typeSort',
        'length' => 'length_int',
        'views' => 'views_int',
        'totalviews' => 'totalviews_int',
        'hasInteractivity' => '{!key=hasInteractivity}timeline_multistring',
        'timeline' => 'timeline_multistring',
        'transcodingfinished' => 'transcodingFinished_string',
        'isImported' => 'isImported_boolean',
        'hasfailedjobs' => 'hasFailedJobs_string',
        'hasjobs' => 'hasJobs_string',
        'hasnewjobs' => 'hasNewJobs_string',
        'deeplink' => 'deeplink_string',
        'gendeeplink' => 'gendeeplink_string',
        'transcodingFinished' => 'transcodingFinished_string',
        'transcodingFailed' => 'transcodingFailed_string',
        'usetype' => 'usetypeSort',
        'cubemapVideo' => 'cubemapVideo_boolean',
        'isThreeSixtyVideo' => 'isThreeSixtyVideo_boolean',
        'YouTube_status' => 'YouTube_status_string',
        'YouTube_manual' => 'YouTube_manual_boolean',
        'Facebook_status' => 'Facebook_status_string',
        'listtype' => 'listtype_string',
        'shortTitle' => 'shortTitle_string',
    ];

    /**
     * Translate an OVP field name to its Solr equivalent.
     *
     * `titleSort` is rewritten to `title_cistr`: it is the one field made
     * explicitly case-insensitive, so that searching for "koert" finds
     * "Koert Live". formatengine does the same rewrite, and only for this field.
     */
    public static function resolve(string $field): string
    {
        $resolved = self::MAP[$field] ?? $field;

        return $resolved === 'titleSort' ? 'title_cistr' : $resolved;
    }

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return self::MAP;
    }
}

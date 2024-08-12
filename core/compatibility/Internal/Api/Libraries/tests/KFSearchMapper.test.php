<?

use RightNow\Internal\Api\Libraries\KFSearchMapper;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class KFSearchMapperLibraryTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Api\Libraries\KFSearchMapper';

    function testToSearchResultForNoResults () {
        $result = KFSearchMapper::toSearchResults((object) array());
        $this->assertSame('', $result->query);
        $this->assertSame(0, $result->size);
        $this->assertSame(0, $result->total);
        $this->assertSame(0, $result->offset);
        $this->assertIdentical(array(), $result->filters);
        $this->assertIdentical(array(), $result->results);
    }

    function testToSearchResults () {
        $input = (object) array(
            'TotalResults' => 10,
            'SummaryContents' => array(
                (object) array(
                    'ID' => 64,
                    'LookupName' => '',
                    'CreatedTime' => NULL,
                    'UpdatedTime' => 1269411000,
                    'SecurityOptions' => NULL,
                    'Excerpt' => 'Things you should know about charging your battery.',
                    'Title' => 'DROID - Tips for extending battery life',
                    'URL' => '',
                    'ContentOrigin' => (object) array(
                       'ID' => 1,
                       'LookupName' => 'HTML Answer',
                    ),
                ),
                (object) array(
                    'ID' => 65,
                    'LookupName' => '',
                    'CreatedTime' => NULL,
                    'UpdatedTime' => 1269411321,
                    'SecurityOptions' => NULL,
                    'Excerpt' => 'Things you should know about the KFAPI.',
                    'Title' => "It currently doesn't return data for some fields.",
                    'URL' => '',
                    'ContentOrigin' => (object) array(
                       'ID' => 1,
                       'LookupName' => 'HTML Answer',
                    ),
                ),
                (object) array(
                    'ID' => 66,
                    'LookupName' => '',
                    'CreatedTime' => NULL,
                    'UpdatedTime' => 1269411322,
                    'SecurityOptions' => NULL,
                    'Excerpt' => 'We should escape html in the title',
                    'Title' => "Ensure we do not render <br>html</br>.",
                    'URL' => '',
                    'ContentOrigin' => (object) array(
                       'ID' => 1,
                       'LookupName' => 'HTML Answer',
                    ),
                ),
                (object) array(
                    'ID' => 67,
                    'LookupName' => '',
                    'CreatedTime' => NULL,
                    'UpdatedTime' => 1269411322,
                    'SecurityOptions' => NULL,
                    'Excerpt' => 'Check out this cool file attachment',
                    'Title' => "Ensure we do not render <br>html</br>.",
                    'URL' => 'Stuff.pdf',
                    'ContentOrigin' => (object) array(
                       'ID' => 3,
                       'LookupName' => 'Attachment Answer',
                    ),
                ),
                (object) array(
                    'ID' => 68,
                    'LookupName' => '',
                    'CreatedTime' => NULL,
                    'UpdatedTime' => 1269411322,
                    'SecurityOptions' => NULL,
                    'Excerpt' => 'Check out this cool external url',
                    'Title' => "Ensure we do not render <br>html</br>.",
                    'URL' => 'https://www.google.com',
                    'ContentOrigin' => (object) array(
                       'ID' => 2,
                       'LookupName' => 'Attachment Answer',
                    ),
                ),
            ),
        );

        $results = KFSearchMapper::toSearchResults($input);

        $this->assertSame(count($input->SummaryContents), $results->size);
        $this->assertSame($input->TotalResults, $results->total);
        $this->assertSame(count($input->SummaryContents), count($results->results));

        foreach ($results->results as $index => $result) {
            $original = $input->SummaryContents[$index];
            $this->assertSame(KFSearchMapper::$type, $result->type);
            if($original->ContentOrigin->ID === 2)
                $this->assertSame($result->url, $original->URL);
            else
                $this->assertBeginsWith($result->url, '/app/answers/detail/a_id/');
            $this->assertSame(\RightNow\Utils\Text::escapeHtml($original->Title), $result->text);
            $this->assertSame($original->Excerpt, $result->summary);
            $this->assertSame($original->ID, $result->KFSearch->id);
            $this->assertSame($original->CreatedTime, $result->created);
            $this->assertSame($original->UpdatedTime, $result->updated);
        }
    }

    function testToSearchResultsWithFilters () {
        $input = (object) array( 'SummaryContents' => array() );
        $filters = array(
            'offset' => array('value' => 'to me'),
            'query'  => array('value' => 'living'),
        );

        $results = KFSearchMapper::toSearchResults($input, $filters);
        $this->assertSame($filters['offset']['value'], $results->offset);
        $this->assertSame($filters, $results->filters);
    }
}

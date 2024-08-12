<?

use RightNow\Libraries\SearchMappers\SocialSearchMapper;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class SocialSearchMapperTest extends CPTestCase {
    public $testingClass = 'RightNow\Libraries\SearchMappers\SocialSearchMapper';

    function __construct () {
        $this->apiResults = (object) array(
            'TotalResults' => 100,
            'GroupedSummaries' => array(
                (object) array(
                    'SummaryContents' => array(
                        (object) array(
                            'URL'                 => 'snow',
                            'Title'               => 'the living room',
                            'Excerpt'             => 'osipov state russian folk orchestra',
                            'ID'                  => 23,
                            'CreatedTime'         => 1,
                            'UpdatedTime'         => 2,
                            'NumberOfBestAnswers' => 12,
                            'NumberOfComments'    => 2,
                            'CreatedBySocialUser' => (object) array(
                                'ID' => 11301,
                                'DisplayName' => 'useractive1',
                                'AvatarURL' => '/one/b/two/c',
                                'StatusWithType' => 'Active',
                            ),
                        ),
                        (object) array(
                            'URL'                 => 'bells',
                            'Title'               => 'last train',
                            'Excerpt'             => 'anonymous',
                            'ID'                  => 26,
                            'CreatedTime'         => 12132340,
                            'UpdatedTime'         => 12132340,
                            'NumberOfBestAnswers' => 0,
                            'NumberOfComments'    => 12,
                            'CreatedBySocialUser' => (object) array(
                                'ID' => 11302,
                                'DisplayName' => 'useridle',
                                'AvatarURL' => '/one/a/two/zzz',
                                'StatusWithType' => 'Not so active',
                            ),
                        ),
                    ),
                ),
            ),
        );
    }

    function testToSearchResultForNoResults () {
        $result = SocialSearchMapper::toSearchResults(array());
        $this->assertSame('', $result->query);
        $this->assertSame(0, $result->size);
        $this->assertSame(0, $result->total);
        $this->assertSame(0, $result->offset);
        $this->assertIdentical(array(), $result->filters);
        $this->assertIdentical(array(), $result->results);
    }

    function testToSearchResults () {
        $input = $this->apiResults;
        $results = SocialSearchMapper::toSearchResults($input);
        $this->assertSame($input->TotalResults, $results->total);
        $this->assertSame(count($input->GroupedSummaries[0]->SummaryContents), $results->size);

        $input = $input->GroupedSummaries[0]->SummaryContents;
        foreach ($results->results as $index => $result) {
            $original = $input[$index];

            $this->assertSame(SocialSearchMapper::$type, $result->type);
            $this->assertSame($original->ID, $result->SocialSearch->id);
            $this->assertIsa($result->SocialSearch->author->ID, 'integer');
            $this->assertIsa($result->SocialSearch->author->DisplayName, 'string');
            $this->assertSame($original->NumberOfBestAnswers, $result->SocialSearch->bestAnswerCount);
            $this->assertSame($original->NumberOfComments, $result->SocialSearch->commentCount);
            $this->assertSame($original->CreatedTime, $result->created);
            $this->assertSame($original->UpdatedTime, $result->updated);
            $this->assertSame($original->Title, $result->text);
            $this->assertSame($original->Excerpt, $result->summary);
        }
    }

    function testSearchResult() {
        $input = $this->apiResults->GroupedSummaries[0]->SummaryContents[0];
        $method = $this->getMethod('searchResult', true);
        $results = $method($input);
        $this->assertSame($input->Title, $results->text);
        $this->assertSame($input->Excerpt, $results->summary);
        $this->assertSame($input->CreatedTime, $results->created);

        $socialSearch = $results->SocialSearch;
        $this->assertIsa($socialSearch->author->ID, 'integer');
        $this->assertIsa($socialSearch->author->DisplayName, 'string');
        $this->assertSame($input->NumberOfBestAnswers, $socialSearch->bestAnswerCount);
        $this->assertSame($input->NumberOfComments, $socialSearch->commentCount);
    }

    function testToSearchResultsWithFilters () {
        $input = (object) array( 'SummaryContents' => array() );
        $filters = array(
            'offset' => array('value' => 'to me'),
            'query'  => array('value' => 'living'),
        );

        $output = SocialSearchMapper::toSearchResults($input, $filters);
        $this->assertSame(0, $output->offset);
        $this->assertIdentical($filters, $output->filters);
    }
}

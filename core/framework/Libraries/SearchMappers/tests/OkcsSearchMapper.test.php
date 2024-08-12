<?

use RightNow\Libraries\SearchMappers\OkcsSearchMapper;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class OkcsSearchMapperTest extends CPTestCase {
    public $testingClass = 'RightNow\Libraries\SearchMappers\OkcsSearchMapper';
    
    function testToSearchResultForNoResults () {
        $result = OkcsSearchMapper::toSearchResults(array());
        $this->assertSame(1, $result->size);
        $this->assertSame(0, $result->total);
        $this->assertSame(0, $result->offset);
        $this->assertIdentical(array(), $result->filters);
        $this->assertIdentical(array(), $result->results);
    }
    
    function testToSearchCategories () {
        $categoryList = json_decode('[{"parent":{"recordID":"08202020d4436fb014a3e04d707007fe8","referenceKey":"COMPANIES","name":"Companies","externalID":3,"externalType":"PRODUCT"},"recordID":"08202020d4436fb014a3e04d707007fe6","referenceKey":"ORACLE","name":"Oracle","externalID":2,"externalType":"PRODUCT","dateAdded":1418380237000,"dateModified":1418380237000,"objectID":"002.001","sortOrder":1,"hasChildren":false},{"parent":{"recordID":"08202020d4436fb014a3e04d707007fe8","referenceKey":"COMPANIES","name":"Companies","externalID":3,"externalType":"PRODUCT"},"recordID":"08202020d4436fb014a3e04d707007fe4","referenceKey":"MICROSOFT","name":"MicroSoft","externalID":1,"externalType":"PRODUCT","dateAdded":1418380252000,"dateModified":1418380252000,"objectID":"002.002","sortOrder":2,"hasChildren":false}]');

        $result = OkcsSearchMapper::toSearchResults(array('category' => $categoryList));
        $this->assertSame(2, count($result->category));
        $this->assertIsA($result->category, 'Array');
    }

    function testGetAnswerTitle () {
        $answer = json_decode('{"type":"unstructured","fileType":"HTML","answerId":16777216,"docId":12582962,"score":0.76703590154648,"title":{"url":"http:\/\/slc04oqn.us.oracle.com\/Test\/testhtml.html","type":"STRING","snippets":[{"text":"This is a test Html with a very long long long long long long long long title","level":0}]},"clickThroughLink":"?ui_mode=answer&prior_transaction_id=601354951&iq_action=4&answer_id=16777216&turl=http%3A%2F%2Fslc04oqn.us.oracle.com%2FTest%2Ftesthtml.html","similarResponseLink":"?ui_mode=answer&prior_transaction_id=601354951&iq_action=12&answer_id=16777216&related_ids=","highlightedLink":"null?ui_mode=answer&prior_transaction_id=601354951&iq_action=5&answer_id=16777216&highlight_info=12582962,0,4&turl=http%3A%2F%2Fslc04oqn.us.oracle.com%2FTest%2Ftesthtml.html#__highlight"}');
        $truncateSize = 20;
        $title = OkcsSearchMapper::getAnswerTitle($answer, $truncateSize);
        $this->assertSame('This is a test Html...', $title);
    }
    
    function testGetAnswerExcerpts () {
        $answer = json_decode('{"type":"unstructured","fileType":"HTML","answerId":16777216,"docId":12582962,"score":0.76703590154648,"title":{"url":"http:\/\/slc04oqn.us.oracle.com\/Test\/testhtml.html","type":"STRING","snippets":[{"text":"This is a test Html","level":0}]},"clickThroughLink":"?ui_mode=answer&prior_transaction_id=601354951&iq_action=4&answer_id=16777216&turl=http%3A%2F%2Fslc04oqn.us.oracle.com%2FTest%2Ftesthtml.html","similarResponseLink":"?ui_mode=answer&prior_transaction_id=601354951&iq_action=12&answer_id=16777216&related_ids=","highlightedLink":"null?ui_mode=answer&prior_transaction_id=601354951&iq_action=5&answer_id=16777216&highlight_info=12582962,0,4&turl=http%3A%2F%2Fslc04oqn.us.oracle.com%2FTest%2Ftesthtml.html#__highlight"}');
        $excerpt = OkcsSearchMapper::getAnswerExcerpts($answer);
        $this->assertSame('', $excerpt);
    }
    
    function testGetAnswerUrl () {
        $answer = json_decode('{"type":"unstructured","fileType":"HTML","answerId":16777216,"docId":12582962,"score":0.76703590154648,"title":{"url":"http:\/\/slc04oqn.us.oracle.com\/Test\/testhtml.html","type":"STRING","snippets":[{"text":"This is a test Html","level":0}]},"link":"IM:FAQ:02014703d47b1440149512cc695007fd1:en_US:published:FA1:1000000:1.0","clickThroughLink":"?ui_mode=answer&prior_transaction_id=601354951&iq_action=4&answer_id=16777216&turl=http%3A%2F%2Fslc04oqn.us.oracle.com%2FTest%2Ftesthtml.html","similarResponseLink":"?ui_mode=answer&prior_transaction_id=601354951&iq_action=12&answer_id=16777216&related_ids=","highlightedLink":"null?ui_mode=answer&prior_transaction_id=601354951&iq_action=5&answer_id=16777216&highlight_info=12582962,0,4&turl=http%3A%2F%2Fslc04oqn.us.oracle.com%2FTest%2Ftesthtml.html#__highlight"}');
        $session = 'asdfgekh';
        $searchState = array('session' => $session, 'transactionID' => 1, 'priorTransactionID' => 1);
        $answerUrl = OkcsSearchMapper::getAnswerUrl($answer, $searchState, 'test document', $session);
        $this->assertNotNull($answer->clickThroughUrl);
        $linkData = explode(':', $answer->link);
        $this->assertSame('published', $linkData[4]);
        $this->assertStringContains($answerUrl, 'type/HTML');
        $this->assertStringContains($answerUrl, '/externalUrl/');
    }
    
    function testGetAnswerUrlWithAttachment () {
        $answer = json_decode('{"type":"unstructured","fileType":"HTML","answerId":16777216,"docId":12582962,"score":0.76703590154648,"title":{"type":"STRING","snippets":[{"text":"This is a test Html","level":0}]},"link":"IM:FAQ:02014703d47b1440149512cc695007fd1:en_US:published:FA1:1000000:1.0:#test.pdf","clickThroughLink":"?ui_mode=answer&prior_transaction_id=601354951&iq_action=4&answer_id=16777216&turl=http%3A%2F%2Fslc04oqn.us.oracle.com%2FTest%2Ftesthtml.html","similarResponseLink":"?ui_mode=answer&prior_transaction_id=601354951&iq_action=12&answer_id=16777216&related_ids=","highlightedLink":"null?ui_mode=answer&prior_transaction_id=601354951&iq_action=5&answer_id=16777216&highlight_info=12582962,0,4&turl=http%3A%2F%2Fslc04oqn.us.oracle.com%2FTest%2Ftesthtml.html#__highlight"}');
        $session = 'asdfgekh';
        $searchState = array('session' => $session, 'transactionID' => 1, 'priorTransactionID' => 1);
        $answerUrl = OkcsSearchMapper::getAnswerUrl($answer, $searchState, 'test document', $session);
        $this->assertSame($answerUrl, '1000000/file/');
    }
}

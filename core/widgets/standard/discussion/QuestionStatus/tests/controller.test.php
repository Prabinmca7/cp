<?

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class QuestionStatusTest extends WidgetTestCase {
    public $testingWidget = 'standard/discussion/QuestionStatus';

    private $questions = array(
        'activeLocked' => 2041,
        'activeUnlocked' => 2039,
        'suspendedLocked' => 2031,
        'suspendedUnlocked' => 2030,
        'pendingLocked' => 2043,
        'pendingUnlocked' => 2042,
    );

    function getData($questionID) {
        $this->addUrlParameters(array('qid' => $questionID));
        $instance = $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->restoreUrlParameters();
        return $data;
    }

    function getQuestion($questionID) {
        return $this->CI->model('CommunityQuestion')->get($questionID)->result;
    }

    function setUp() {
        $this->logIn();
    }

    function tearDown() {
        $this->logOut();
    }

    function testGetData() {
        // No question specified in url
        $data = $this->getData(null);
        $this->assertNull($data['question']);

        // unlocked active question
        $data = $this->getData($this->questions['activeUnlocked']);
        $this->assertNull($data['question']);

        // locked active question
        $data = $this->getData($this->questions['activeLocked']);
        $this->assertIsA($data['question'], 'RightNow\Connect\v1_4\CommunityQuestion');
        $state = $data['state'];
        $this->assertTrue($state['locked']);
        $this->assertEqual('locked', $state['status']);

        // pending question
        $data = $this->getData($this->questions['pendingUnlocked']);
        $this->assertNull($data['question']);
    }

    function testGetQuestionState() {
        $this->createWidgetInstance();
        $getQuestionState = $this->getWidgetMethod('getQuestionState');

        $actual = $getQuestionState($this->getQuestion($this->questions['activeLocked']));
        $expected = array('locked' => true, 'status' => 'active');
        $this->assertIdentical($expected, $actual);

        $actual = $getQuestionState($this->getQuestion($this->questions['pendingUnlocked']));
        $expected = array('locked' => false, 'status' => 'pending');
        $this->assertIdentical($expected, $actual);

        $actual = $getQuestionState($this->getQuestion($this->questions['pendingLocked']));
        $expected = array('locked' => true, 'status' => 'pending');
        $this->assertIdentical($expected, $actual);

        $actual = $getQuestionState($this->getQuestion($this->questions['suspendedLocked']));
        $expected = array('locked' => true, 'status' => 'suspended');
        $this->assertIdentical($expected, $actual);

        $actual = $getQuestionState($this->getQuestion($this->questions['suspendedUnlocked']));
        $expected = array('locked' => false, 'status' => 'suspended');
        $this->assertIdentical($expected, $actual);
    }
}

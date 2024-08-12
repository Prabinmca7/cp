<?php
use RightNow\UnitTest\Helper as TestHelper,
    RightNow\Utils\Text,
    RightNow\Utils\Config,
    RightNow\Utils\Framework;

TestHelper::loadTestedFile(__FILE__);

$reflectionClass = new ReflectionClass('RightNow\Models\Social');
$method = $reflectionClass->getMethod('makeRequestToCommunity');
$method->setAccessible(true);

class MockSocial extends RightNow\Models\Social
{
    public static function mockMakeRequestToCommunity($url, $postString, $isCacheable)
    {
        if(Text::stringContains($url, "Action=CommentAdd") && Text::stringContains($url, "doesnotmatter"))
            return '{"comment":{
                "id":145,
                "uri":"http://pjk2-12501-sql-127h-soc.dx.lan/api/comments/145",
                "created":"2012-06-25T17:15:04-06:00",  "createdBy":{
                    "hash":"66bc49394a",
                    "uri":"http://pjk2-12501-sql-127h-soc.dx.lan/api/users/66bc49394a",
                    "loginId":"66bc49394a",
                    "name":"James Watson",
                    "avatar":"http:\\/\\/pjk2-12501-sql-127h-soc.dx.lan\\/common\\/images\\/avatars\\/user\\/default.jpg"   },
                "lastEdited":"2012-06-25T17:15:04-06:00",   "lastEditedBy":{
                    "hash":"66bc49394a",
                    "uri":"http://pjk2-12501-sql-127h-soc.dx.lan/api/users/66bc49394a",
                    "loginId":"66bc49394a",
                    "name":"James Watson",
                    "avatar":"http:\\/\\/pjk2-12501-sql-127h-soc.dx.lan\\/common\\/images\\/avatars\\/user\\/default.jpg"   },
                "status":1,
                "hiveHash":"a93f4c7f51",
                "hiveName":"Medidata Rave",
                "postHash":"7baa4d52f9",
                "postName":"Merge reports",
                "value":"test comment (jvsw)"}}';
        else if(Text::stringContains($url, "Action=CommentAdd"))
            return '{"comment":{
                "id":145,
                "uri":"http://pjk2-12501-sql-127h-soc.dx.lan/api/comments/145",
                "created":"2012-06-25T17:15:04-06:00",  "createdBy":{
                    "hash":"66bc49394a",
                    "uri":"http://pjk2-12501-sql-127h-soc.dx.lan/api/users/66bc49394a",
                    "loginId":"66bc49394a",
                    "name":"James Watson",
                    "avatar":"http:\\/\\/pjk2-12501-sql-127h-soc.dx.lan\\/common\\/images\\/avatars\\/user\\/default.jpg"   },
                "lastEdited":"2012-06-25T17:15:04-06:00",   "lastEditedBy":{
                    "hash":"66bc49394a",
                    "uri":"http://pjk2-12501-sql-127h-soc.dx.lan/api/users/66bc49394a",
                    "loginId":"66bc49394a",
                    "name":"James Watson",
                    "avatar":"http:\\/\\/pjk2-12501-sql-127h-soc.dx.lan\\/common\\/images\\/avatars\\/user\\/default.jpg"   },
                "status":2,
                "hiveHash":"a93f4c7f51",
                "hiveName":"Medidata Rave",
                "postHash":"7baa4d52f9",
                "postName":"Merge reports",
                "value":"test comment (jvsw)"}}';
        else
            return '{"post":{
                "uri":"http://pjk2-121100-sql-25h-soc.dx.lan/api/posts/0515121073",
                "postType":{
                    "uri":"http://pjk2-121100-sql-25h-soc.dx.lan/api/hives/2049357ac7/types/5",
                    "name":"Topic"  },
              "created":"2009-09-13T22:08:41+00:00",
                "createdBy":{
                    "hash":"7c62355853",
                    "uri":"http://pjk2-121100-sql-25h-soc.dx.lan/api/users/7c62355853",
                    "loginId":"Camille",
                    "name":"Camille",
                    "avatar":"http://pjk2-121100-sql-25h-soc.dx.lan/files/d3bda422d7/me.jpg"    },
                   "lastEdited":"2012-06-21T15:44:59+00:00",    "lastEditedBy":{
                    "hash":"7c62355853",
                    "uri":"http://pjk2-121100-sql-25h-soc.dx.lan/api/users/7c62355853",
                    "loginId":"Camille",
                    "name":"Camille",
                    "avatar":"http://pjk2-121100-sql-25h-soc.dx.lan/files/d3bda422d7/me.jpg"    },
                "eventStart":"1970-01-01T00:00:00+00:00",
                "eventEnd":"1970-01-01T00:00:00+00:00",
                "eventTimeZone":"",
                    "title":"How to use a circular polarizer?",
                "status":1,
                "commentCount":16,
                "viewCount":31,
                "ratingCount":5,
                "ratingTotal":300,
                "flagCount":0,
                "tags":["hardware","help request"],
                    "ratedByRequestingUser":{
                        "created":"2012-06-26T16:26:08+00:00",
                        "ratingValue":100    },
                    "fields":[
                {
                    "id":99,
                    "postTypeField":{
                        "name":"Title",
                        "type":1
                    },
                    "value":"How to use a circular polarizer?"},{
                    "id":100,
                    "postTypeField":{
                        "name":"Summary",
                        "type":1
                    },
                    "value":"difficult to find the right position..."},{
                    "id":101,
                    "postTypeField":{
                        "name":"Content",
                        "type":4
                    },
                    "value":"<p>I bought a B+W MRC Circular Polarizer and I struggle to use it on a bright sunny day. I have used linear polarizers during film days, which was easier to use as the polarizer transitioned between bright colors and total darkness. With the circular polarizer, the transition is much less dramatic to the point where most of the times I can hardly tell the difference as I rotate it around. There are a few circumstances that really strike out, like when I zoom into a red car or on to a TV, but for most other situations (particularly with a lot of green in them which the main reason I bought the polarizer), it is very difficult to find the right position.<br \\/>Any tips?<br \\/>Equipment:<br \\/>Rebel XT<br \\/>Tamron 18-270mm, Tamron 18-50mm f\\/2.8, Sigma 30mm f\\/1.4,<br \\/>Canon 50mm f\\/1.8, Canon 85mm f\\/1.8<br \\/>77mm B+W MRC Circular Polarizer<br \\/><\\/p>"}    ]
                 }}
            ';
    }

    public function getSignatureParameters($arg1, $arg2) {
        static $timesEvoked = 0;
        $emptyVals = array(null, false, '');
        $returnValue = array(
            'p_cid' => $emptyVals[self::$timesEvoked]
        );
        self::$timesEvoked = self::$timesEvoked + 1;
        return $returnValue;
    }
}

class SocialModelTest extends CPTestCase
{
    public $testingClass = 'RightNow\Models\Social';
    protected $model;
    static $initialConfigValues = array();

    function __construct()
    {
        parent::__construct();
        $this->model = new RightNow\Models\Social();
        $this->communityBaseUrl = 'http://den01tpo.us.oracle.com';
    }

    function setUp() {
        self::$initialConfigValues["COMMUNITY_ENABLED"] = \Rnow::updateConfig("COMMUNITY_ENABLED", 1, true);
        self::$initialConfigValues["COMMUNITY_BASE_URL"] = \Rnow::updateConfig("COMMUNITY_BASE_URL", $this->communityBaseUrl, true);
        self::$initialConfigValues["COMMUNITY_PUBLIC_KEY"] = \Rnow::updateConfig("COMMUNITY_PUBLIC_KEY", '5JbzLXNAJ0Y3sxAn', true);
        self::$initialConfigValues["COMMUNITY_PRIVATE_KEY"] = \Rnow::updateConfig("COMMUNITY_PRIVATE_KEY", 'OXaX8ctYifSiwonq', true);
        parent::setUp();
    }

    function tearDown() {
        foreach (self::$initialConfigValues as $config => $value) {
            \Rnow::updateConfig($config, null, true);
        }
        parent::tearDown();
    }

    function testCommunityReachable() {
        $this->assertNotNull(file_get_contents($this->communityBaseUrl), "COMMUNITY_BASE_URL not reachable: {$this->communityBaseUrl}");
    }

    function testPerformSearch(){
        $invoke = $this->getMethod('performSearch');
        $response = $invoke();
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $socialResults = $response->result;
        $this->assertTrue(is_array($socialResults->searchResults));
        $this->assertSame(5, count($socialResults->searchResults));
        $this->assertTrue(is_int($socialResults->totalCount));

        $response = $invoke('', 15);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));
        $socialResults = $response->result;
        $this->assertTrue(is_array($socialResults->searchResults));
        $this->assertSame(15, count($socialResults->searchResults));
        $this->assertTrue(is_int($socialResults->totalCount));

        // Build up list of community result IDs for tests below
        $IDs = array();
        foreach($socialResults->searchResults as $result) {
            $IDs[] = $result->id;
        }

        // start index of null, 0 and 1 should all return the first item
        $response = $invoke('', 5, null, null, null, null);
        $this->assertSame($IDs[0], $response->result->searchResults[0]->id);

        $response = $invoke('', 5, null, null, null, 0);
        $this->assertSame($IDs[0], $response->result->searchResults[0]->id);

        $response = $invoke('', 5, null, null, null, 1);
        $this->assertSame($IDs[0], $response->result->searchResults[0]->id);

        // $start argument begins with 1 (not 0) as that is what the community API expects
        $response = $invoke('', 5, null, null, null, 5);
        $this->assertSame($IDs[4], $response->result->searchResults[0]->id);

        $response = $invoke('', 5, null, null, null, 10);
        $this->assertSame($IDs[9], $response->result->searchResults[0]->id);
    }

    function testFormatSearchResults(){
        $invoke = $this->getMethod('formatSearchResults');

        $fakeSearchResultData = array(
            (object)array(
                'webUrl' => "{$this->communityBaseUrl}/posts/a8dc4de3e0",
                'lastActivity' => 1316541282,
                'name' => 'John Doe',
                'preview' => "Marfa mixtape gluten-free, pitchfork lo-fi letterpress viral tattooed photo booth artisan aesthetic mcsweeney's.",
              ),
            (object)array(
                'webUrl' => "{$this->communityBaseUrl}/posts/afd11e3c9e",
                'lastActivity' => 1316456412,
                'name' => 'Hacker McGee %3C > < %3E',
                'preview' => "%3C > < %3E Raw denim jean shorts iphone, wolf single-origin coffee quinoa fanny pack mustache artisan 3 wolf moon tofu DIY high life. Fap biodiesel butcher, brooklyn mcsweeney's four loko PBR vinyl tattooed lomo cliche thundercats letterpress squid. Artisan scenester sustainable chambray, food truck single-origin coffee shoreditch.",
            )
        );

        $response = $invoke($fakeSearchResultData, 100, false);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $formattedResults = $response->result;
        $this->assertSame('Hacker McGee &lt; &gt; &lt; &gt;', $formattedResults[1]->name);
        $this->assertTrue(Text::beginsWith($formattedResults[1]->preview, '&lt; &gt; &lt; &gt;'));
        $this->assertSame(102, strlen($formattedResults[0]->preview));
        $this->assertSame(105, strlen($formattedResults[1]->preview));
        $this->assertFalse(is_int($formattedResults[0]->lastActivity));
        $this->assertFalse(is_int($formattedResults[1]->lastActivity));
        $this->assertSame($fakeSearchResultData[0]->preview, $formattedResults[0]->preview);
        $this->assertSame($fakeSearchResultData[0]->name, $formattedResults[0]->name);
        $this->assertSame($fakeSearchResultData[0]->webUrl, $formattedResults[0]->webUrl);
        $this->assertSame($fakeSearchResultData[1]->webUrl, $formattedResults[1]->webUrl);

        $response = $invoke($fakeSearchResultData, 50, true, 'denim', 'http://www.google.com');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $formattedResults = $response->result;
        $this->assertSame('Hacker McGee &lt; &gt; &lt; &gt;', $formattedResults[1]->name);
        $this->assertTrue(Text::beginsWith($formattedResults[1]->preview, '&lt; &gt; &lt; &gt;'));
        $this->assertTrue(Text::stringContains($formattedResults[1]->preview, "<em class='rn_Highlight'>denim</em>"));
        $this->assertSame(45, strlen($formattedResults[0]->preview));
        $this->assertSame(81, strlen($formattedResults[1]->preview));
        $this->assertFalse(is_int($formattedResults[0]->lastActivity));
        $this->assertFalse(is_int($formattedResults[1]->lastActivity));
        $this->assertSame($fakeSearchResultData[0]->preview, $formattedResults[0]->preview);
        $this->assertSame($fakeSearchResultData[0]->name, $formattedResults[0]->name);
        $this->assertSame('http://www.google.com/posts/a8dc4de3e0', $formattedResults[0]->webUrl);
        $this->assertSame('http://www.google.com/posts/afd11e3c9e', $formattedResults[1]->webUrl);
    }

    function testGetCommunityUser(){
        $invoke = $this->getMethod('getCommunityUser');

        $response = $invoke(array('userHash' => 'ef125a157a'));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $response = $invoke(array('contactID' => '32564353'));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertTrue(Text::stringContains($response->error, '32564353'));
        $this->assertSame(4, $response->error->errorCode);
        $this->assertSame(0, count($response->warnings));

        $response = $invoke(array('contactID' => ''));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $response = $invoke(array());
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertSame(0, count($response->warnings));
    }

    function testGetCommunityPost(){
        $invoke = $this->getMethod('getCommunityPost');

        $response = $invoke('088cf80079');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $response = $invoke('abc123');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertSame(4, $response->error->errorCode);
        $this->assertTrue(Text::stringContains($response->error, 'abc123'));
        $this->assertSame(0, count($response->warnings));

        $response = $invoke('');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertSame(0, count($response->warnings));
    }

    function testGetAnswerComments(){
        $invoke = $this->getMethod('getAnswerComments');

        $response = $invoke(1);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertSame(4, $response->error->errorCode);
        $this->assertTrue(Text::stringContains($response->error, '1'));
        $this->assertSame(0, count($response->warnings));

        $response = $invoke(0);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertTrue(Text::stringContains($response->error, '0'));
        $this->assertSame(0, count($response->warnings));
    }

    function testGetPostComments(){
        $invoke = $this->getMethod('getPostComments');

        $response = $invoke('088cf80079');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $response = $invoke('123abc');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertSame(4, $response->error->errorCode);
        $this->assertTrue(Text::stringContains($response->error, '123abc'));
        $this->assertSame(0, count($response->warnings));

        $response = $invoke('');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertSame(0, count($response->warnings));
    }

    function testGetPostTypeFields(){
        $invoke = $this->getMethod('getPostTypeFields');
        $response = $invoke(29);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_object($response->result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $response = $invoke(0);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertTrue(Text::stringContains($response->error, '0'));
        $this->assertSame(0, count($response->warnings));

        $response = $invoke(1);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertSame(4, $response->error->errorCode);
        $this->assertTrue(Text::stringContains($response->error, '1'));
        $this->assertSame(0, count($response->warnings));
    }

    function testPerformAnswerCommentAction(){
        $invoke = $this->getMethod('performAnswerCommentAction');

        $response = $invoke(null, 'fakeAction', (object)array('id' => 'fakeID'));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertSame('fakeAction', $response->result['action']);
        $this->assertSame('fakeID', $response->result['id']);
        $this->assertSame(1, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $response = $invoke(52, 'reply', (object)array('id' => 12));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertSame('reply', $response->result['action']);
        $this->assertSame(12, $response->result['id']);
        $this->assertSame(1, count($response->errors));
        $this->assertSame(0, count($response->warnings));
    }

    function testPerformAnswerCommentActionLoggedIn(){
        $invoke = $this->getMethod('performAnswerCommentAction');

        $this->logIn();

        $response = $invoke(52, 'rate', (object)array('id' => 12, 'rating' => 100));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame('rate', $response->result['action']);
        $this->assertSame(100, $response->result['rating']);
        $this->assertSame(12, $response->result['id']);

        $this->logOut();
    }

    function testPerformPostCommentActionNotLoggedIn(){
        $invoke = $this->getMethod('performPostCommentAction');

        $response = $invoke('fakeHash', 'fakeAction', null, 'fakeID');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertSame('fakeAction', $response->result['action']);
        $this->assertSame('fakeID', $response->result['id']);
        $this->assertSame(1, count($response->errors));
        $this->assertSame(Config::getMessage(LOGGED_PERFORM_ACTIONS_MSG), $response->errors[0]->externalMessage);
        $this->assertSame(0, count($response->warnings));

        $response = $invoke('a8dc4de3e0', 'reply', 1, 12);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertSame('reply', $response->result['action']);
        $this->assertSame(12, $response->result['id']);
        $this->assertSame(1, count($response->errors));
        $this->assertSame(Config::getMessage(LOGGED_PERFORM_ACTIONS_MSG), $response->errors[0]->externalMessage);
        $this->assertSame(0, count($response->warnings));
    }

    function testPerformPostCommentAction(){
        $invoke = $this->getMethod('performPostCommentAction');

        $this->logIn();

        $response = $invoke('fakeHash', 'fakeAction', null, 'fakeID');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertSame('fakeAction', $response->result['action']);
        $this->assertSame('fakeID', $response->result['id']);
        $this->assertSame(1, count($response->errors));
        $this->assertSame(Config::getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG), $response->errors[0]->externalMessage);
        $this->assertSame(0, count($response->warnings));

        $response = $invoke('a8dc4de3e0', 'reply', 1, 12);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertSame('reply', $response->result['action']);
        $this->assertSame(12, $response->result['id']);
        $this->assertSame(1, count($response->errors));
        $this->assertSame(Config::getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG), $response->errors[0]->externalMessage);
        $this->assertSame(0, count($response->warnings));

        $invoke = TestHelper::getMethodInvoker('MockSocial', 'performPostCommentAction');

        $response = $invoke('7baa4d52f9', 'reply', 'doesnotmatter', 1);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertSame('reply', $response->result['action']);
        $this->assertSame(1, $response->result['id']);
        $this->assertSame('test comment (jvsw)', $response->result['comment']->value);
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $response = $invoke('7baa4d52f9', 'reply', 'pending', 1);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertSame('reply', $response->result['action']);
        $this->assertSame(1, $response->result['id']);
        $this->assertSame('test comment (jvsw)', $response->result['comment']->value);
        $this->assertSame(Config::getMessage(COMMENT_COMMENT_UNDERGOING_MSG), $response->result['message']);
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $response = $invoke('7baa4d52f9', 'rate', 'doesnotmatter', 1);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertSame('rate', $response->result['action']);
        $this->assertSame(1, $response->result['id']);
        $this->assertSame(0, $response->result['rating']);
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $this->logOut();
    }

    function testSubmitPostNotLoggedIn(){
        $invoke = $this->getMethod('submitPost');

        $response = $invoke(100, 'a8dc4de3e0', 'Title of Post', 'Body of Post');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertSame(0, count($response->result));
        $this->assertSame(1, count($response->errors));
        $this->assertSame(-1, $response->error->errorCode);
        $this->assertSame(0, count($response->warnings));

        $_POST['token'] = \RightNow\Utils\Framework::createTokenWithExpiration(10);
        $response = $invoke(100, 'a8dc4de3e0', 'Title of Post', 'Body of Post');
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertSame(0, count($response->result));
        $this->assertSame(1, count($response->errors));
        $this->assertSame(0, count($response->warnings));
    }

    function testSubmitPost(){
        $invoke = TestHelper::getMethodInvoker('MockSocial', 'submitPost');

        $this->logIn();

        $title = (object)array('id' => 1, 'value' => 'Title of Post');
        $body = (object)array('id' => 2, 'value' => 'Body of Post');
        $response = $invoke(100, 'a8dc4de3e0', $title, $body);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertSame(0, count($response->result));
        $this->assertSame(1, count($response->errors));
        $this->assertSame(-1, $response->error->errorCode);
        $this->assertSame(0, count($response->warnings));

        $_POST['token'] = \RightNow\Utils\Framework::createTokenWithExpiration(10);
        $response = $invoke(100, 'a8dc4de3e0', $title, $body);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue(is_array($response->result));
        $this->assertIsA($response->result['created'], 'string');
        $this->assertIdentical(1, $response->result['status']);
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $this->logOut();
    }

    function testCreateUser(){
        $invoke = $this->getMethod('createUser');
        $response = $invoke(array());
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $response = $invoke(array('contactID' => 12));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $response = $invoke(array('contactID' => 12, 'name' => 'John Doe'));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $response = $invoke(array('name' => 'John Doe', 'email' => 'foo@example.com'));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $response = $invoke(array('contactID' => 12, 'email' => 'foo@example.com'));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertSame(0, count($response->warnings));
    }

    function testUpdateUser(){
        $invoke = $this->getMethod('updateUser');
        $response = $invoke(12, array());
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $response = $invoke(12, array('foo' => 'bar'));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertSame(0, count($response->warnings));
    }

    function testGenerateSsoToken(){
        // Default args - not logged in
        $invoke = $this->getMethod('generateSsoToken');
        $response = $invoke();
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertSame($response->errors[0]->externalMessage, 'User must be logged in to generate a SSO token.');
        $this->assertSame(0, count($response->warnings));

        $this->logIn('useractive2');

        // Default args
        $invoke = $this->getMethod('generateSsoToken');
        $response = $invoke();
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        // Version's 3 result is more complex - longer - than version 2, so save it to compare later.
        $version3Result = $response->result;
        $this->assertIsA($version3Result, 'string');
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        // Strict mode is true with an invalid signature param array
        \Rnow::updateConfig("COMMUNITY_PUBLIC_KEY", null, true);
        $invoke = TestHelper::getMethodInvoker('MockSocial', 'generateSsoToken');
        $response = $invoke(true);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($response->result);
        $this->assertSame(1, count($response->errors));
        $this->assertTrue(Text::stringContains($response->errors[0]->externalMessage, 'parameter does not have a value'));
        $this->assertSame(0, count($response->warnings));
        $response = $invoke(true);
        $this->assertTrue(Text::stringContains($response->errors[0]->externalMessage, 'parameter does not have a value'));
        $response = $invoke(true);
        $this->assertTrue(Text::stringContains($response->errors[0]->externalMessage, 'parameter does not have a value'));
        \Rnow::updateConfig("COMMUNITY_PUBLIC_KEY", '5JbzLXNAJ0Y3sxAn', true);

        // Explicitly using version 2
        $invoke = $this->getMethod('generateSsoToken');
        $response = $invoke(null, null, 2);
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $version2Result = $response->result;
        $this->assertIsA($version2Result, 'string');
        $this->assertTrue(strlen($version2Result) < strlen($version3Result));
        $this->assertSame(0, count($response->errors));
        $this->assertSame(0, count($response->warnings));

        $this->logOut();
    }

    function testGetSignatureParameters() {
        $invoke = $this->getMethod('getSignatureParameters');

        // Test default, version 3
        $expected = array(
            'ApiKey',
            'p_cid',
            'p_email.addr',
            'p_name.first',
            'p_name.last',
            'SignatureVersion',
            'p_sessionid',
            'p_timestamp',
        );
        $result = $invoke(array());
        $this->assertIdentical($expected, array_keys($result)); // The key order is important here
        $this->assertNull($result['p_cid']);
        $this->assertNull($result['p_email.addr']);
        $this->assertNull($result['p_name.first']);
        $this->assertNull($result['p_name.last']);
        $this->assertIdentical(3, $result['SignatureVersion']);
        $this->assertIsA($result['p_timestamp'], 'integer');
        $this->assertIsA($result['p_sessionid'], 'string');
        $this->assertTrue(strlen($result['p_sessionid']) > 1);
        $this->assertFalse(array_key_exists('redirectUrl', $result));

        // Test legacy, version 2
        $expected = array(
            'ApiKey',
            'p_cid',
            'p_email.addr',
            'p_name.first',
            'p_name.last',
            'SignatureVersion',
            'Signature',
            'p_sessionid',
            'p_timestamp',
        );
        $result = $invoke(array(), 2);
        $this->assertIdentical($expected, array_keys($result)); // The key order is important here
        $this->assertNull($result['p_cid']);
        $this->assertNull($result['p_email.addr']);
        $this->assertNull($result['p_name.first']);
        $this->assertNull($result['p_name.last']);
        $this->assertIdentical(2, $result['SignatureVersion']);
        $this->assertIsA($result['p_timestamp'], 'integer');
        $this->assertIsA($result['p_sessionid'], 'string');
        $this->assertTrue(strlen($result['p_sessionid']) > 1);
        $this->assertFalse(array_key_exists('redirectUrl', $result));

        // Send in profileData exactly the way it is done from generateSsoToken
        $redirect = 'redirect/here';
        $profileData = (array)$this->CI->session->getProfile(true);
        if ($redirect) {
            $profileData['redirectUrl'] = $redirect;
        }
        $result = $invoke($profileData);
        $this->assertIdentical($redirect, $result['redirectUrl']);

        // Test sending in specific values
        $profileData = array('contactID' => 1234546, 'firstName' => 'Bob&Doug', 'lastName' => 'Doug&Bob', 'email' => 'Bob&Doug@Doug&Bob.com');
        $result = $invoke($profileData);
        $this->assertIdentical(1234546, $result['p_cid']);
        $this->assertIdentical('Bob&Doug@Doug&Bob.com', $result['p_email.addr']);
        $this->assertIdentical('Bob&Doug', $result['p_name.first']);
        $this->assertIdentical('Doug&Bob', $result['p_name.last']);
    }

    function testEncryptToken(){
        $invoke = $this->getMethod('encryptToken');
        $response = $invoke('whoa there');
        $this->assertSame(count($response), 3);
        $this->assertIsA($response[2], 'string');
    }

    function testGenerateKey(){
        $invoke = $this->getMethod('generateKey');
        $response = $invoke('whoa there', 'aye');
        $this->assertIsA($response, 'string');

        $response = $invoke('hot potato', 'yum', 8);
        $this->assertSame(strlen($response), 8);
    }

    function testGetApiVersion(){
        $invoke = $this->getMethod('getApiVersion');
        $response = $invoke(12, array());
        $this->assertSame($response, COMMUNITY_NOV_10_API_VERSION);
    }

    function testCanMakeCommunityApiRequest(){
        $invoke = $this->getMethod('canMakeCommunityApiRequest');
        $this->assertTrue($invoke());
        \Rnow::updateConfig("COMMUNITY_ENABLED", 0, true);
        $this->assertFalse($invoke());
        \Rnow::updateConfig("COMMUNITY_ENABLED", 1, true);
        \Rnow::updateConfig("COMMUNITY_PUBLIC_KEY", '', true);
        $this->assertFalse($invoke());
        \Rnow::updateConfig("COMMUNITY_PUBLIC_KEY", '5JbzLXNAJ0Y3sxAn', true);
        \Rnow::updateConfig("COMMUNITY_PRIVATE_KEY", '', true);
        $this->assertFalse($invoke());
        \Rnow::updateConfig("COMMUNITY_PRIVATE_KEY", 'OXaX8ctYifSiwonq', true);
        $this->assertTrue($invoke());
    }

    function testParseCommunityResponse() {
        $parse = $this->getMethod('parseCommunityResponse');

        // Null community results. noValueErrorMessage returned
        $noValueErrorMessage = 'Nothing to see here folks';
        $result = $parse(null, $noValueErrorMessage);
        $this->assertIsA($result, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($result->errors, 'array');
        $this->assertIdentical($noValueErrorMessage, $result->errors[0]->externalMessage);
        $this->assertNull($result->result);

        $getCommunityResult = function($errorMessage = null, $errorCode = null) {
            $result = (object)array(
                'permissionsForRequestingUser' => (object)array(
                    'admin' => 0,
                    'tagCreate' => 0,
                    'tagApplyOwn' => 1,
                    'tagApplyAll' => 1,
                    'postCreate' => 1,
                    'postViewOwn' => 1,
                    'postViewAll' => 1,
                    'postEditOwn' => 1,
                    'postEditAll' => 1,
                    'commentCreate' => 1,
                    'commentViewOwn' => 1,
                    'commentViewAll' => 1,
                    'commentEditOwn' => 1,
                    'commentEditAll' => 1,
                    'flaggingEnabled' => 1,
                    'ratingEnabled' => 1,
                ),
                'comments' => array(),
                'error' => array(),
            );
            if ($errorMessage !== null || $errorCode !== null) {
                $result->error = (object)array(
                    'message' => $errorMessage,
                    'code' => $errorCode,
                );
            }
            return $result;
        };

        // No errors, community results returned
        $communityResult = $getCommunityResult();
        $result = $parse($communityResult, $noValueErrorMessage);
        $this->assertIsA($result, 'RightNow\Libraries\ResponseObject');
        $this->assertIdentical($communityResult, $result->result);
        $this->assertNull($result->error);
        $this->assertIdentical(array(), $result->errors);

        // Ignored error POST_RESOURCE_NOT_FOUND_ERROR [4]
        $communityResult = $getCommunityResult('Error getting comments for post Guid [4], post resource not found error.', '4');
        $result = $parse($communityResult, $noValueErrorMessage);
        $this->assertIsA($result, 'RightNow\Libraries\ResponseObject');
        $this->assertIdentical($communityResult, $result->result);
        $this->assertNull($result->error);
        $this->assertIdentical(array(), $result->errors);

        // Ignored error AUTHENTICATED_USER_ERROR [35]
        $communityResult = $getCommunityResult('Authenticated User Error..', '35');
        $result = $parse($communityResult, $noValueErrorMessage);
        $this->assertIsA($result, 'RightNow\Libraries\ResponseObject');
        $this->assertIdentical($communityResult, $result->result);
        $this->assertNull($result->error);
        $this->assertIdentical(array(), $result->errors);

        // Heeded error. Results should be null.
        $communityResult = $getCommunityResult('Some Nasty Error', '1001');
        $result = $parse($communityResult, $noValueErrorMessage);
        $this->assertIsA($result, 'RightNow\Libraries\ResponseObject');
        $this->assertNull($result->result);
        $this->assertIsA($result->errors, 'array');
        $this->assertIdentical('Some Nasty Error', $result->errors[0]->externalMessage);
        $this->assertIdentical('1001', $result->errors[0]->errorCode);
    }

    function testProcessCommunityResponse() {
        $method = $this->getMethod('processCommunityResponse');

        // No result
        $responseObj = new \RightNow\Libraries\ResponseObject;
        $method(null, null, $responseObj, null);
        $this->assertNull($responseObj->result);
        $this->assertIsA($responseObj->errors, 'array');
        $this->assertIdentical(Config::getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG), $responseObj->errors[0]->externalMessage);

        $responseObj = new \RightNow\Libraries\ResponseObject;
        $method('', null, $responseObj, null);
        $this->assertNull($responseObj->result);
        $this->assertIsA($responseObj->errors, 'array');
        $this->assertIdentical(Config::getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG), $responseObj->errors[0]->externalMessage);

        // Invalid JSON
        $responseObj = new \RightNow\Libraries\ResponseObject;
        $method('lasf:ja;slfj"lskfxcvh&e}w(', null, $responseObj, null);
        $this->assertNull($responseObj->result);
        $this->assertIsA($responseObj->errors, 'array');
        $this->assertIdentical(Config::getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG), $responseObj->errors[0]->externalMessage);

        // Community error
        $responseObj = new \RightNow\Libraries\ResponseObject;
        $method('{"error":{"code":"bananas"}}', null, $responseObj, null);
        $this->assertNull($responseObj->result);
        $this->assertIsA($responseObj->errors, 'array');
        $this->assertIdentical(Config::getMessage(THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG), $responseObj->errors[0]->externalMessage);
        $this->assertIdentical('bananas', $responseObj->errors[0]->errorCode);

        // Doesn't care about sub-object, so no args supplied to closure and return value is ignored
        $responseObj = new \RightNow\Libraries\ResponseObject;
        $givenArgs = true;
        $closure = function() use (&$givenArgs) {
            $givenArgs = func_num_args();
            return array('something' => 'nothing');
        };
        $method('{"bananas":{"hey":"yo"}}', null, $responseObj, $closure);
        $this->assertIdentical((object) array('bananas' => (object) array('hey' => 'yo')), $responseObj->result);
        $this->assertNull($responseObj->error);
        $this->assertIdentical(0, $givenArgs);

        // Wants a sub object, so sub object is given to closure and its return value is used
        $responseObj = new \RightNow\Libraries\ResponseObject;
        $givenArgs = true;
        $closure = function($bananas) use (&$givenArgs) {
            $givenArgs = func_num_args();
            $bananas->foo = 'bar';
            return $bananas;
        };
        $method('{"bananas":{"hey":"yo"}}', 'bananas', $responseObj, $closure);
        $this->assertIdentical((object) array('hey' => 'yo', 'foo' => 'bar'), $responseObj->result);
        $this->assertNull($responseObj->error);
        $this->assertIdentical(1, $givenArgs);
    }

    function testGenerateCommunityApiUrl(){
        $invoke = $this->getMethod('generateCommunityApiUrl');
        $expectedHost = Text::getSubstringAfter($this->communityBaseUrl, '//');
        $communityProtocol = \RightNow\Utils\Url::isRequestHttps() ? 'https' : 'http';
        $response = $invoke(array());
        $response = parse_url($response);
        $this->assertSame($communityProtocol, $response['scheme']);
        $this->assertSame($expectedHost, $response['host']);
        $this->assertSame('/api/endpoint', $response['path']);
        parse_str($response['query'], $queryParameters);
        $this->assertSame('json', $queryParameters['format']);
        $this->assertSame('Guest', $queryParameters['PermissionedAs']);
        $this->assertSame('2', $queryParameters['SignatureVersion']);
        $this->assertSame(COMMUNITY_NOV_10_API_VERSION, $queryParameters['version']);
        $this->assertSame('5JbzLXNAJ0Y3sxAn', $queryParameters['ApiKey']);
        $this->assertSame('WOiuFtTT7cGIpVrr/SruIqr8tQk=', $queryParameters['Signature']);

        $response = $invoke(array('PermissionedAs' => 'eturner', 'foo'=>'bar', 'random'=>'values'));
        $response = parse_url($response);
        $this->assertSame($communityProtocol, $response['scheme']);
        $this->assertSame($expectedHost, $response['host']);
        $this->assertSame('/api/endpoint', $response['path']);
        parse_str($response['query'], $queryParameters);
        $this->assertSame('json', $queryParameters['format']);
        $this->assertSame('eturner', $queryParameters['PermissionedAs']);
        $this->assertSame('2', $queryParameters['SignatureVersion']);
        $this->assertSame('bar', $queryParameters['foo']);
        $this->assertSame('values', $queryParameters['random']);
        $this->assertSame(COMMUNITY_NOV_10_API_VERSION, $queryParameters['version']);
        $this->assertSame('5JbzLXNAJ0Y3sxAn', $queryParameters['ApiKey']);
        $this->assertSame('zZHUKsWVadi8xuFRXyk+utUtzX4=', $queryParameters['Signature']);

        $response = $invoke(array(), array('foo' => 'bar'));
        $response = parse_url($response);
        $this->assertSame($communityProtocol, $response['scheme']);
        $this->assertSame($expectedHost, $response['host']);
        $this->assertSame('/api/endpoint', $response['path']);
        parse_str($response['query'], $queryParameters);
        $this->assertSame('Guest', $queryParameters['PermissionedAs']);
        $this->assertSame('2', $queryParameters['SignatureVersion']);
        $this->assertSame(COMMUNITY_NOV_10_API_VERSION, $queryParameters['version']);
        $this->assertSame('5JbzLXNAJ0Y3sxAn', $queryParameters['ApiKey']);
        $this->assertSame('WOiuFtTT7cGIpVrr/SruIqr8tQk=', $queryParameters['Signature']);

        $response = $invoke(array(), array('action' => 'apiAction'));
        $response = parse_url($response);
        $this->assertSame($communityProtocol, $response['scheme']);
        $this->assertSame($expectedHost, $response['host']);
        $this->assertSame('/api/apiAction', $response['path']);
        parse_str($response['query'], $queryParameters);
        $this->assertSame('Guest', $queryParameters['PermissionedAs']);
        $this->assertSame('2', $queryParameters['SignatureVersion']);
        $this->assertSame(COMMUNITY_NOV_10_API_VERSION, $queryParameters['version']);
        $this->assertSame('5JbzLXNAJ0Y3sxAn', $queryParameters['ApiKey']);
        $this->assertSame('WOiuFtTT7cGIpVrr/SruIqr8tQk=', $queryParameters['Signature']);

        $response = $invoke(array(), array('action' => 'apiAction', 'identifier' => 'apiIdentifier'));
        $response = parse_url($response);
        $this->assertSame($communityProtocol, $response['scheme']);
        $this->assertSame($expectedHost, $response['host']);
        $this->assertSame('/api/apiAction/apiIdentifier', $response['path']);
        parse_str($response['query'], $queryParameters);
        $this->assertSame('Guest', $queryParameters['PermissionedAs']);
        $this->assertSame('2', $queryParameters['SignatureVersion']);
        $this->assertSame(COMMUNITY_NOV_10_API_VERSION, $queryParameters['version']);
        $this->assertSame('5JbzLXNAJ0Y3sxAn', $queryParameters['ApiKey']);
        $this->assertSame('WOiuFtTT7cGIpVrr/SruIqr8tQk=', $queryParameters['Signature']);

        $originalRntSsl = $_SERVER['HTTP_RNT_SSL'] ?: null;
        $_SERVER['HTTP_RNT_SSL'] = 'yes';

        $invoke = $this->getMethod('generateCommunityApiUrl');
        $response = $invoke(array());
        $response = parse_url($response);
        $this->assertSame('https', $response['scheme']);
        $this->assertSame($expectedHost, $response['host']);
        $this->assertSame('/api/endpoint', $response['path']);
        parse_str($response['query'], $queryParameters);
        $this->assertSame('json', $queryParameters['format']);
        $this->assertSame('Guest', $queryParameters['PermissionedAs']);
        $this->assertSame('2', $queryParameters['SignatureVersion']);
        $this->assertSame(COMMUNITY_NOV_10_API_VERSION, $queryParameters['version']);
        $this->assertSame('5JbzLXNAJ0Y3sxAn', $queryParameters['ApiKey']);
        $this->assertSame('WOiuFtTT7cGIpVrr/SruIqr8tQk=', $queryParameters['Signature']);

        // update the COMMUNITY_BASE_URL to be https, make sure that is unchanged when request is using https
        \Rnow::updateConfig('COMMUNITY_BASE_URL', "https://den01tpo.us.oracle.com", true);
        $invoke = $this->getMethod('generateCommunityApiUrl');
        $response = $invoke(array());
        $response = parse_url($response);
        $this->assertSame('https', $response['scheme']);
        $this->assertSame($expectedHost, $response['host']);
        $this->assertSame('/api/endpoint', $response['path']);
        parse_str($response['query'], $queryParameters);
        $this->assertSame('json', $queryParameters['format']);
        $this->assertSame('Guest', $queryParameters['PermissionedAs']);
        $this->assertSame('2', $queryParameters['SignatureVersion']);
        $this->assertSame(COMMUNITY_NOV_10_API_VERSION, $queryParameters['version']);
        $this->assertSame('5JbzLXNAJ0Y3sxAn', $queryParameters['ApiKey']);
        $this->assertSame('WOiuFtTT7cGIpVrr/SruIqr8tQk=', $queryParameters['Signature']);

        if ($originalRntSsl === null) {
            unset($_SERVER['HTTP_RNT_SSL']);
        }
        else {
            $_SERVER['HTTP_RNT_SSL'] = $originalRntSsl;
        }

        // request should not be using https now, but COMMUNITY_BASE_URL is still using that protocol; URL should be modified to reflect that
        $invoke = $this->getMethod('generateCommunityApiUrl');
        $response = $invoke(array());
        $response = parse_url($response);
        $this->assertSame($communityProtocol, $response['scheme']);
        $this->assertSame($expectedHost, $response['host']);
        $this->assertSame('/api/endpoint', $response['path']);
        parse_str($response['query'], $queryParameters);
        $this->assertSame('json', $queryParameters['format']);
        $this->assertSame('Guest', $queryParameters['PermissionedAs']);
        $this->assertSame('2', $queryParameters['SignatureVersion']);
        $this->assertSame(COMMUNITY_NOV_10_API_VERSION, $queryParameters['version']);
        $this->assertSame('5JbzLXNAJ0Y3sxAn', $queryParameters['ApiKey']);
        $this->assertSame('WOiuFtTT7cGIpVrr/SruIqr8tQk=', $queryParameters['Signature']);
    }
}

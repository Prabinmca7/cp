<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class FakeModelClass extends RightNow\Api\Models\Base{
    public function getKnowledgeToken(){
        return $this->getKnowledgeApiSessionToken();
    }

    public function addSecurityFilters($contentObject, $contact=null){
        $this->addKnowledgeApiSecurityFilter($contentObject, $contact);
    }

    public function cache($key, $val) {
        return parent::cache($key, $val);
    }

    public function getCached($key) {
        return parent::getCached($key);
    }

    public function abuseCheck(){
        return $this->isAbuse();
    }
}

class ModelBaseTestClass extends CPTestCase {
    private $instance = null;
    private $cacheTests;

    function __construct() {
        $this->reflectionClass = new ReflectionClass('RightNow\Api\Models\Base');
        parent::__construct();
        $this->cacheTests = array(
            array('banana', 'freeway'),
            'string',
            126.3,
            (object) array('super' => 'global'),
        );
    }

    function testGetKnowledgeApiSessionToken(){
        $mockModel = new FakeModelClass();
        $token = $mockModel->getKnowledgeToken();
        $this->assertTrue(is_string($token));
        //Make sure it's cached
        $this->assertIdentical($token, $mockModel->getKnowledgeToken());
    }

    function testAddKnowledgeApiSecurityFilter(){
        $mockModel = new FakeModelClass();

        $content = new \RightNow\Connect\Knowledge\v1\SmartAssistantContentSearch();
        $mockModel->addSecurityFilters($content);
        $this->assertNull($content->SecurityOptions);

        $testContact = get_instance()->model('Contact')->get(1)->result;

        // order matters - test first without logging in and then with logging in (otherwise, you get cached SecurityOptions)
        // not logged in
        $mockModel->addSecurityFilters($content, $testContact);
        $this->assertNull($content->SecurityOptions);

        // logged in
        $this->logIn($testContact->Login);
        $mockModel->addSecurityFilters($content, $testContact);
        $this->assertIsA($content->SecurityOptions, KF_NAMESPACE_PREFIX . '\ContentSecurityOptions');
        $this->assertIdentical($content->SecurityOptions->Contact, $testContact);
        $this->logOut();
    }

    function testCache() {
        $mockModel = new FakeModelClass();

        foreach ($this->cacheTests as $key => $value) {
            $this->assertIdentical($value, $mockModel->cache("ModelBaseTest{$key}", $value));
        }
    }

    function testGetCached() {
        $mockModel = new FakeModelClass();

        foreach ($this->cacheTests as $key => $value) {
            $this->assertIdentical($value, $mockModel->getCached("ModelBaseTest{$key}", $value));
        }

        $this->assertFalse($mockModel->getCached("Banana not in cache"));
    }
}

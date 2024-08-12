<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class FakeModel extends RightNow\Models\Base{
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

class ModelBaseTest extends CPTestCase {
    private $instance = null;
    private $cacheTests;

    function __construct() {
        $this->reflectionClass = new ReflectionClass('RightNow\Models\Base');
        parent::__construct();
        $this->cacheTests = array(
            array('banana', 'freeway'),
            'string',
            126.3,
            (object) array('super' => 'global'),
        );
    }

    function setStaticProperty($propertyName, $propertyValue){
        $property = $this->reflectionClass->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($propertyValue);
    }

    function getStaticMethodInvoker($methodName) {
        return RightNow\UnitTest\Helper::getStaticMethodInvoker('RightNow\Models\Base', $methodName);
    }

    function testGetResponseObject(){
        $mockModel = new FakeModel();
        $response = $mockModel->getResponseObject(null);
        $this->assertNull($response->result);
        $this->assertIdentical('is_object', $response->validationFunction);
        $this->assertTrue(is_array($response->errors));
        $this->assertTrue(is_array($response->warnings));

        $response = $mockModel->getResponseObject(null, 'is_null');
        $this->assertNull($response->result);
        $this->assertIdentical('is_null', $response->validationFunction);
        $this->assertTrue(is_array($response->errors));
        $this->assertTrue(is_array($response->warnings));

        $response = $mockModel->getResponseObject(null, 'is_null', 'Fake error from testing');
        $this->assertNull($response->result);
        $this->assertIdentical('is_null', $response->validationFunction);
        $this->assertTrue(is_array($response->errors));
        $this->assertSame("Fake error from testing", $response->error . '');
        $this->assertTrue(is_array($response->warnings));

        $response = $mockModel->getResponseObject(null, 'is_null', array('Fake error from testing'));
        $this->assertNull($response->result);
        $this->assertIdentical('is_null', $response->validationFunction);
        $this->assertTrue(is_array($response->errors));
        $this->assertSame("Fake error from testing", $response->error . '');
        $this->assertTrue(is_array($response->warnings));

        $response = $mockModel->getResponseObject(null, 'is_null', null, 'Fake warning from testing');
        $this->assertNull($response->result);
        $this->assertIdentical('is_null', $response->validationFunction);
        $this->assertTrue(is_array($response->errors));
        $this->assertTrue(is_array($response->warnings));
        $this->assertSame("Fake warning from testing", $response->warning . '');

        $response = $mockModel->getResponseObject(null, 'is_null', null, array('Fake warning from testing'));
        $this->assertNull($response->result);
        $this->assertIdentical('is_null', $response->validationFunction);
        $this->assertTrue(is_array($response->errors));
        $this->assertTrue(is_array($response->warnings));
        $this->assertSame("Fake warning from testing", $response->warning . '');
    }

    function testGetKnowledgeApiSessionToken(){
        $mockModel = new FakeModel();
        $token = $mockModel->getKnowledgeToken();
        $this->assertTrue(is_string($token));
        //Make sure it's cached
        $this->assertIdentical($token, $mockModel->getKnowledgeToken());
        $this->assertIdentical($token, \RightNow\Models\Base::getKnowledgeApiSessionToken());
    }

    function testAddKnowledgeApiSecurityFilter(){
        $mockModel = new FakeModel();

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
        $mockModel = new FakeModel();

        foreach ($this->cacheTests as $key => $value) {
            $this->assertIdentical($value, $mockModel->cache("ModelBaseTest{$key}", $value));
        }
    }

    function testGetCached() {
        $mockModel = new FakeModel();

        foreach ($this->cacheTests as $key => $value) {
            $this->assertIdentical($value, $mockModel->getCached("ModelBaseTest{$key}", $value));
        }

        $this->assertFalse($mockModel->getCached("Banana not in cache"));
    }

    function testLoadModel(){
        $method = $this->getStaticMethodInvoker('loadModel');

        $this->assertIsA($method('Answer'), 'RightNow\Models\Answer');
        $this->assertIsA($method('standard/Contact'), 'RightNow\Models\Contact');
        $this->assertIsA($method('custom/Sample'), 'Custom\Models\Sample');
        $this->assertIsA($method('custom/sample'), 'Custom\Models\Sample');
        $this->assertIsA($method('custom/samPle'), 'Custom\Models\Sample');

        //Make sure we cache correctly
        $firstModel = $method('Incident');
        $secondModel = $method('standard/Incident');
        $thirdModel = $method('sTanDard/INCIdent');

        $this->assertTrue($firstModel === $secondModel);
        $this->assertTrue($firstModel === $thirdModel);
        $this->assertTrue($secondModel === $thirdModel);

        //Try invalid paths
        foreach(array('', array(), 'asdf', 'standard/asdf', 'custom/asdf') as $modelPath){
            try{
                $method($modelPath);
                $this->fail("Model path $modelPath is not valid and should throw an exception.");
            }
            catch(\Exception $e){}
        }
    }

    function testGetAbsoluteModelPathAndClassname() {
        $method = $this->getStaticMethodInvoker('getAbsoluteModelPathAndClassname');

        $response = $method("standard/Answer");
        $this->assertSame(CPCORE . 'Models/', $response[0]);
        $this->assertSame('', $response[1]);
        $this->assertSame('Answer', $response[2]);
        $this->assertSame('RightNow\Models\Answer', $response[3]);

        $response = $method("standard/SubDirectory/Answer");
        $this->assertSame(CPCORE . 'Models/', $response[0]);
        $this->assertSame('SubDirectory/', $response[1]);
        $this->assertSame('Answer', $response[2]);
        $this->assertSame('RightNow\Models\SubDirectory\Answer', $response[3]);

        $response = $method("standard/SubDirectory/Another/Answer");
        $this->assertSame(CPCORE . 'Models/', $response[0]);
        $this->assertSame('SubDirectory/Another/', $response[1]);
        $this->assertSame('Answer', $response[2]);
        $this->assertSame('RightNow\Models\SubDirectory\Another\Answer', $response[3]);

        $response = $method("custom/Sample");
        $this->assertSame(APPPATH . 'models/custom/', $response[0]);
        $this->assertSame('', $response[1]);
        $this->assertSame('Sample', $response[2]);
        $this->assertSame('Custom\Models\Sample', $response[3]);

        $response = $method("custom/SubDirectory/Sample");
        $this->assertSame(APPPATH . 'models/custom/', $response[0]);
        $this->assertSame('SubDirectory/', $response[1]);
        $this->assertSame('Sample', $response[2]);
        $this->assertSame('Custom\Models\SubDirectory\Sample', $response[3]);

        $response = $method("custom/SubDirectory/another/Sample");
        $this->assertSame(APPPATH . 'models/custom/', $response[0]);
        $this->assertSame('SubDirectory/another/', $response[1]);
        $this->assertSame('Sample', $response[2]);
        $this->assertSame('Custom\Models\SubDirectory\another\Sample', $response[3]);

        $response = $method("foobar/another/Sample");
        $this->assertSame(APPPATH . 'models/custom/', $response[0]);
        $this->assertSame('another/', $response[1]);
        $this->assertSame('Sample', $response[2]);
        $this->assertSame('Custom\Models\another\Sample', $response[3]);
    }

    function testGetExtendedModel(){
        $method = $this->getStaticMethodInvoker('getExtendedModel');

        $this->setStaticProperty('modelExtensionList', array(
                'Answer' => 'MyOwnAnswerModel',
                'Contact' => 'foo/bar/CustomContact',
                'SubDirectory/SubModel' => 'foo/bar/CustomContact',
                'DoesntExist' => 'AlsoDoesntExist',
                'Field' => 'custom/MyField',
                'chat' => 'MyChat',
                'Incident' => false,
                'Country' => '',
                'Account' => null
            )
        );

        $this->assertIdentical('custom/MyOwnAnswerModel', $method('Answer'));
        $this->assertIdentical('custom/foo/bar/CustomContact', $method('Contact'));
        $this->assertIdentical('custom/foo/bar/CustomContact', $method('SubDirectory/SubModel'));
        $this->assertIdentical('custom/AlsoDoesntExist', $method('DoesntExist'));
        $this->assertIdentical('custom/custom/MyField', $method('Field'));
        $this->assertNull($method('Chat'));
        $this->assertNull($method('Incident'));
        $this->assertNull($method('Country'));
        $this->assertNull($method('Account'));

        $this->setStaticProperty('modelExtensionList', array());
    }

    function testIsAbuse(){
        $mockModel = new FakeModel();
        $this->assertFalse($mockModel->abuseCheck());
        $this->setIsAbuse();
        $this->assertEqual(\RightNow\Utils\Config::getMessage(REQUEST_PERFORMED_SITE_ABUSIVE_MSG), $mockModel->abuseCheck());
        $this->clearIsAbuse();
    }

    function testLoadingOfCompatibilityModels(){
        $method = $this->getStaticMethodInvoker('loadModel');

        //Ensure that the test environment is clean
        try{
            $method('NonExistingModel');
            $this->fail('Model "NonExistingModel" should not exist');
        }
        catch(\Exception $e){}

        $modelCode = <<<'MODEL'
<?php
namespace RightNow\Models;

class NonExistingModel extends Base{
    public function test(){
        return "from compat layer";
    }
}
MODEL;

        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure(CORE_FILES . "compatibility/Models/NonExistingModel.php", $modelCode);

        $this->assertIsA($method('NonExistingModel'), 'RightNow\Models\NonExistingModel');
        $this->assertIdentical('from compat layer', $method('NonExistingModel')->test());

        $modelCode = <<<'MODEL'
<?php
namespace RightNow\Models\SubDir;

class NonExistingModel extends \RightNow\Models\Base{
    public function test(){
        return "from compat layer";
    }
}
MODEL;

        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure(CORE_FILES . "compatibility/Models/SubDir/NonExistingModel.php", $modelCode);
        $this->assertIsA($method('SubDir/NonExistingModel'), 'RightNow\Models\SubDir\NonExistingModel');
        $this->assertIdentical('from compat layer', $method('SubDir/NonExistingModel')->test());

        //Make sure custom models can't override models in the compat layer
        $this->setStaticProperty('modelExtensionList', array('NonExistingModel' => 'ExtendedSample'));

        $this->assertIsA($method('SubDir/NonExistingModel'), 'RightNow\Models\SubDir\NonExistingModel');
        $this->assertIdentical('from compat layer', $method('SubDir/NonExistingModel')->test());
        try{
            $method('ExtendedSample');
            $this->fail('Custom models should not be allowed to overwrite any model in the compatibility layer.');
        }
        catch(\Exception $e){}

            $this->setStaticProperty('modelExtensionList', array());
            \RightNow\Utils\FileSystem::removeDirectory(CORE_FILES . "compatibility/Models/SubDir", true);
            @unlink(CORE_FILES . "compatibility/Models/NonExistingModel.php");
    }
}

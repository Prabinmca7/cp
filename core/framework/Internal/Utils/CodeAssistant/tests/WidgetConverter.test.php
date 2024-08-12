<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

require_once(CPCORE . 'Internal/Utils/CodeAssistant.php');
class WidgetConverterTestCase extends CPTestCase {
    public $testingClass = '\RightNow\Internal\Utils\CodeAssistant\WidgetConverter';

    public function setUp() {
        \RightNow\Internal\Utils\FileSystem::copyDirectory(__DIR__ . '/fixtures/', CUSTOMER_FILES . 'widgets/custom/input/');
        parent::setUp();
    }
    public function tearDown() {
        \RightNow\Internal\Utils\FileSystem::removeDirectory(CUSTOMER_FILES . 'widgets/custom/input/', true);
        parent::tearDown();
    }

    public function testExecuteUnit() {
        //V2 widget extending a standard widget
        $operation = new \RightNow\Internal\Libraries\CodeAssistant\Conversion();
        $method = $this->getMethod('executeUnit', true);
        $method('custom/input/TestExtend', $operation);

        $this->assertIdentical(5, count($operation->getInstructions()));

        $testableOperations = array();
        foreach($operation->getInstructions() as $operation){
            $testableOperations[$operation['type']][] = $operation['source']['visiblePath'];
        }

        $this->assertIdentical($testableOperations['createDirectory'], array('custom/input/TestExtend/1.0'));
        $this->assertIdentical($testableOperations['createFile'], array(
            'custom/input/TestExtend/1.0/info.yml',
            'custom/input/TestExtend/1.0/view.php',
        ));
        $this->assertIdentical($testableOperations['moveFile'], array('custom/input/TestExtend/base.css'));
        $this->assertIdentical($testableOperations['deleteFile'], array('custom/input/TestExtend/view.php'));

        //Extend everything
        $operation = new \RightNow\Internal\Libraries\CodeAssistant\Conversion();
        $method = $this->getMethod('executeUnit', true);
        $method('custom/input/TestExtendCSS', $operation);

        $this->assertIdentical(4, count($operation->getInstructions()));

        $testableOperations = array();
        foreach($operation->getInstructions() as $operation){
            $testableOperations[$operation['type']][] = $operation['source']['visiblePath'];
        }
        $this->assertIdentical($testableOperations['createDirectory'], array('custom/input/TestExtendCSS/1.0'));
        $this->assertIdentical($testableOperations['createFile'], array(
            'custom/input/TestExtendCSS/1.0/info.yml',
            'custom/input/TestExtendCSS/1.0/view.php'
        ));
        $this->assertIdentical($testableOperations['deleteFile'], array('custom/input/TestExtendCSS/view.php'));

        //All custom v2 widget
        $operation = new \RightNow\Internal\Libraries\CodeAssistant\Conversion();
        $method = $this->getMethod('executeUnit', true);
        $method('custom/input/TextInput', $operation);
        $this->assertIdentical(10, count($operation->getInstructions()));

        $testableOperations = array();
        foreach($operation->getInstructions() as $operation){
            $testableOperations[$operation['type']][] = $operation['source']['visiblePath'];
        }

        $this->assertIdentical($testableOperations['modifyFile'], array('custom/input/TextInput/view.php'));
        $this->assertIdentical($testableOperations['createDirectory'], array(
            'custom/input/TextInput/1.0', 
        ));
        $this->assertIdentical($testableOperations['moveDirectory'], array(
            'custom/input/TextInput/preview',
        ));
        $this->assertIdentical($testableOperations['createFile'], array(
            'custom/input/TextInput/1.0/info.yml',
            'custom/input/TextInput/1.0/logic.js',
            'custom/input/TextInput/1.0/controller.php'
        ));
        $this->assertIdentical($testableOperations['moveFile'], array(
            'custom/input/TextInput/view.php',
            'custom/input/TextInput/base.css',
        ));
        $this->assertIdentical($testableOperations['deleteFile'], array(
            'custom/input/TextInput/controller.php',
            'custom/input/TextInput/logic.js'
        ));
    }

    public function testGetWidgetInformation() {
        $method = $this->getMethod('getWidgetInformation', true);

        //All Standard with 2 on the end
        $meta = array(
            'controller_path' => 'standard/reports/Multiline2',
            'js_path' => 'standard/reports/Multiline2',
            'base_css' => 'standard/reports/Multiline2',
            'presentation_css' => 'widgetCss/Multiline2.css',
        );

        $operation = new \RightNow\Internal\Libraries\CodeAssistant\Conversion();
        $results = $method($meta, 'custom/input/testInput', $operation);
        $this->assertTrue($results['extendCSS']);
        $this->assertIdentical(array(
            'view' => array('path' => 'custom/input/testInput/view.php', 'isStandard' => false)
        ), $results['components']);

        $this->assertIdentical('standard/reports/Multiline2', $results['parent']);
        $this->assertTrue(count($operation->getMessages()) === 1);

        //All custom
        $meta = array(
            'controller_path' => 'custom/input/testInput',
            'js_path' => 'custom/input/testInput',
            'presentation_css' => 'widgetCss/testInput.css',
            'base_css' => 'custom/input/testInput'
        );

        $operation = new \RightNow\Internal\Libraries\CodeAssistant\Conversion();
        $results = $method($meta, 'custom/input/testInput', $operation);

        $this->assertNull($results['extendCSS']);
        $this->assertIdentical(array(
            'php' => array('path' => 'custom/input/testInput/controller.php', 'isStandard' => false),
            'js' => array('path' => 'custom/input/testInput/logic.js', 'isStandard' => false),
            'css' => array('path' => 'custom/input/testInput/base.css', 'isStandard' => false)
        ), $results['components']);

        $this->assertIdentical(null, $results['parent']);
        $this->assertTrue(count($operation->getMessages()) === 0);

        //Extend PHP and JS
        $meta = array(
            'controller_path' => 'standard/input/TextInput',
            'js_path' => 'standard/input/TextInput',
            'presentation_css' => 'widgetCss/testInput.css',
            'base_css' => 'custom/input/testInput'
        );

        $operation = new \RightNow\Internal\Libraries\CodeAssistant\Conversion();
        $results = $method($meta, 'custom/input/testInput', $operation);
        $this->assertNull($results['extendCSS']);
        $this->assertIdentical(array(
            'css' => array('path' => 'custom/input/testInput/base.css', 'isStandard' => false),
            'view' => array('path' => 'custom/input/testInput/view.php', 'isStandard' => false)
        ), $results['components']);
        $this->assertIdentical('standard/input/TextInput', $results['parent']);
        $messages = $operation->getMessages();
        $this->assertTrue(count($messages) === 1);
        $this->assertStringContains($messages[0], 'widget uses components from standard widgets');

        //Extend just JS -- Requires extension of PHP because of the custom controller
        $meta = array(
            'controller_path' => 'custom/input/testInput',
            'js_path' => 'standard/input/TextInput',
            'presentation_css' => 'widgetCss/testInput.css',
            'base_css' => 'custom/input/testInput'
        );

        $operation = new \RightNow\Internal\Libraries\CodeAssistant\Conversion();
        $results = $method($meta, 'custom/input/testInput', $operation);
        $this->assertNull($results['extendCSS']);
        $this->assertIdentical(array(
            'php' => array('path' => 'custom/input/testInput/controller.php', 'isStandard' => false),
            'css' => array('path' => 'custom/input/testInput/base.css', 'isStandard' => false),
            'view' => array('path' => 'custom/input/testInput/view.php', 'isStandard' => false)
        ), $results['components']);
        $this->assertIdentical('standard/input/TextInput', $results['parent']);
        $messages = $operation->getMessages();
        $this->assertTrue(count($messages) === 1);
        $this->assertStringContains($messages[0], 'widget uses components from standard widgets');

        //Extend just PHP -- Requires extend JS because of custom logic
        $meta = array(
            'controller_path' => 'standard/input/TextInput',
            'js_path' => 'custom/input/testInput',
            'presentation_css' => 'widgetCss/testInput.css',
            'base_css' => 'custom/input/testInput'
        );

        $operation = new \RightNow\Internal\Libraries\CodeAssistant\Conversion();
        $results = $method($meta, 'custom/input/testInput', $operation);
        $this->assertNull($results['extendCSS']);
        $this->assertIdentical(array(
            'js' => array('path' => 'custom/input/testInput/logic.js', 'isStandard' => false),
            'css' => array('path' => 'custom/input/testInput/base.css', 'isStandard' => false),
            'view' => array('path' => 'custom/input/testInput/view.php', 'isStandard' => false)
        ), $results['components']);
        $this->assertIdentical('standard/input/TextInput', $results['parent']);
        $messages = $operation->getMessages();
        $this->assertTrue(count($messages) === 1);
        $this->assertStringContains($messages[0], 'widget uses components from standard widgets');

        //Use standard CSS Only - Don't extend, just let them know that they could move the styles over.
        $meta = array(
            'controller_path' => 'custom/input/testInput',
            'js_path' => 'custom/input/testInput',
            'presentation_css' => 'widgetCss/TextInput.css',
            'base_css' => 'standard/input/TextInput'
        );
        $operation = new \RightNow\Internal\Libraries\CodeAssistant\Conversion();
        $results = $method($meta, 'custom/input/testInput', $operation);
        $this->assertNull($results['extendCSS']);
        $this->assertIdentical(array(
            'php' => array('path' => 'custom/input/testInput/controller.php', 'isStandard' => false),
            'js' => array('path' => 'custom/input/testInput/logic.js', 'isStandard' => false)
        ), $results['components']);
        $this->assertIdentical(null, $results['parent']);
        $messages = $operation->getMessages();
        $this->assertTrue(count($messages) === 2);
        $this->assertStringContains($messages[0], "'base_css' path doesn't match");
        $this->assertStringContains($messages[1], "'presentation_css' path doesn't match");

        //Extend PHP and CSS -- Requires extension of all components
        $meta = array(
            'controller_path' => 'standard/input/TextInput',
            'js_path' => 'custom/input/testInput',
            'presentation_css' => 'widgetCss/TextInput.css',
            'base_css' => 'standard/input/TextInput'
        );
        $operation = new \RightNow\Internal\Libraries\CodeAssistant\Conversion();
        $results = $method($meta, 'custom/input/testInput', $operation);
        $this->assertTrue($results['extendCSS']);
        $this->assertIdentical(array(
            'js' => array('path' => 'custom/input/testInput/logic.js', 'isStandard' => false),
            'view' => array('path' => 'custom/input/testInput/view.php', 'isStandard' => false)
        ), $results['components']);
        $this->assertIdentical('standard/input/TextInput', $results['parent']);
        $messages = $operation->getMessages();
        $this->assertTrue(count($messages) === 1);
        $this->assertStringContains($messages[0], 'widget uses components from standard widgets');

        //Invalid custom controller path
        $operation = new \RightNow\Internal\Libraries\CodeAssistant\Conversion();
        $meta = array(
            'controller_path' => 'custom/input/AnotherTestWidget',
            'js_path' => 'custom/input/testInput',
        );
        try {
            $results = $method($meta, 'custom/input/testInput', $operation);
            $this->fail("Expected Exception");
        }
        catch(\Exception $e) {
            $this->assertStringContains($e->getMessage(), "'controller_path' for this widget is set to another custom widget");
        }

        //Invalid custom JS path
        $operation = new \RightNow\Internal\Libraries\CodeAssistant\Conversion();
        $meta = array(
            'controller_path' => 'custom/input/testInput',
            'js_path' => 'custom/input/AnotherTestWidget'
        );
        try {
            $results = $method($meta, 'custom/input/testInput', $operation);
            $this->fail("Expected Exception");
        }
        catch(\Exception $e) {
            $this->assertStringContains($e->getMessage(), "'js_path' for this widget is set to another custom widget");
        }

        //Valid controller extends, invalid JS extends
        $operation = new \RightNow\Internal\Libraries\CodeAssistant\Conversion();
        $meta = array(
            'controller_path' => 'standard/input/TextInput',
            'js_path' => 'custom/input/AnotherTestWidget'
        );
        try {
            $results = $method($meta, 'custom/input/testInput', $operation);
            $this->fail("Expected Exception");
        }
        catch(\Exception $e) {
            $this->assertStringContains($e->getMessage(), "'js_path' and 'controller_path' use content from two separate widgets");
        }

        //Extend PHP and presentation, but not base
        $operation = new \RightNow\Internal\Libraries\CodeAssistant\Conversion();
        $meta = array(
            'controller_path' => 'standard/input/TextInput',
            'js_path' => 'custom/input/testInput',
            'presentation_css' => 'widgetCss/TextInput.css'
        );
        $results = $method($meta, 'custom/input/testInput', $operation);
        $this->assertNull($results['extendCSS']);
        $this->assertIdentical(array(
            'js' => array('path' => 'custom/input/testInput/logic.js', 'isStandard' => false),
            'view' => array('path' => 'custom/input/testInput/view.php', 'isStandard' => false)
        ), $results['components']);
        $this->assertIdentical('standard/input/TextInput', $results['parent']);
        $messages = $operation->getMessages();
        $this->assertTrue(count($messages) === 2);
        $this->assertStringContains($messages[0], "'presentation_css' path references a standard widget");
        $this->assertStringContains($messages[1], 'widget uses components from standard widgets');

        //Extend PHP and base, but not presentation
        $operation = new \RightNow\Internal\Libraries\CodeAssistant\Conversion();
        $meta = array(
            'controller_path' => 'standard/input/TextInput',
            'js_path' => 'custom/input/testInput',
            'base_css' => 'standard/input/TextInput'
        );
        $results = $method($meta, 'custom/input/testInput', $operation);
        $this->assertNull($results['extendCSS']);
        $this->assertIdentical(array(
            'js' => array('path' => 'custom/input/testInput/logic.js', 'isStandard' => false),
            'view' => array('path' => 'custom/input/testInput/view.php', 'isStandard' => false)
        ), $results['components']);
        $this->assertIdentical('standard/input/TextInput', $results['parent']);
        $messages = $operation->getMessages();
        $this->assertTrue(count($messages) === 2);
        $this->assertStringContains($messages[0], "'base_css' path references a standard widget");
        $this->assertStringContains($messages[1], "widget uses components from standard widgets");

        //Extend PHP non-existent v3 widget, exists in v2
        $operation = new \RightNow\Internal\Libraries\CodeAssistant\Conversion();
        $meta = array(
            'controller_path' => 'standard/forms/FeedbackForm',
            'js_path' => 'custom/input/testInput',
        );
        $results = $method($meta, 'custom/input/testInput', $operation);
        $this->assertNull($results['extendCSS']);
        $this->assertIdentical(array(
            'php' => array('path' => 'standard/forms/FeedbackForm/controller.php', 'isStandard' => true),
            'js' => array('path' => 'custom/input/testInput/logic.js', 'isStandard' => false)
        ), $results['components']);
        $this->assertIdentical(null, $results['parent']);
        $this->assertTrue(count($operation->getMessages()) === 0);

        //Extend JS non-existent v3 widget, exists in v2
        $operation = new \RightNow\Internal\Libraries\CodeAssistant\Conversion();
        $meta = array(
            'controller_path' => 'custom/input/testInput',
            'js_path' => 'standard/forms/FeedbackForm',
        );
        $results = $method($meta, 'custom/input/testInput', $operation);
        $this->assertNull($results['extendCSS']);
        $this->assertIdentical(array(
            'php' => array('path' => 'custom/input/testInput/controller.php', 'isStandard' => false),
            'js' => array('path' => 'standard/forms/FeedbackForm/logic.js', 'isStandard' => true)
        ), $results['components']);
        $this->assertIdentical(null, $results['parent']);
        $this->assertTrue(count($operation->getMessages()) === 0);

        //Extend PHP non-existent standard widget and JS with existing v3 widget
        $operation = new \RightNow\Internal\Libraries\CodeAssistant\Conversion();
        $meta = array(
            'controller_path' => 'standard/forms/FeedbackForm',
            'js_path' => 'standard/input/TextInput',
        );
        $results = $method($meta, 'custom/input/testInput', $operation);
        $this->assertNull($results['extendCSS']);
        $this->assertIdentical(array(
            'php' => array('path' => 'standard/forms/FeedbackForm/controller.php', 'isStandard' => true),
            'view' => array('path' => 'custom/input/testInput/view.php', 'isStandard' => false)
        ), $results['components']);
        $this->assertIdentical('standard/input/TextInput', $results['parent']);
        $messages = $operation->getMessages();
        $this->assertTrue(count($messages) === 1);
        $this->assertStringContains($messages[0], 'widget uses components from standard widgets');

        //Extend PHP non-existent controller
        $operation = new \RightNow\Internal\Libraries\CodeAssistant\Conversion();
        $meta = array(
            'controller_path' => 'standard/input/IDontExist',
            'js_path' => 'custom/input/testInput',
        );
        try {
            $results = $method($meta, 'custom/input/testInput', $operation);
            $this->fail('Excepted Exception');
        }
        catch(\Exception $e) {
            $this->assertStringContains($e->getMessage(), "'controller_path' for this widget does not exist");
        }

        //Extend JS non-existent v2 or v3 widget
        $operation = new \RightNow\Internal\Libraries\CodeAssistant\Conversion();
        $meta = array(
            'controller_path' => 'custom/input/testInput',
            'js_path' => 'standard/input/IDontExist',
        );
        try {
            $results = $method($meta, 'custom/input/testInput', $operation);
            $this->fail('Excepted Exception');
        }
        catch(\Exception $e) {
            $this->assertStringContains($e->getMessage(), "'js_path' for this widget does not exist");
        }
    }
}

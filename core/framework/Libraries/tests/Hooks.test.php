<?php

use RightNow\Libraries\Hooks,
    RightNow\UnitTest\Helper,
    RightNow\Utils\Text;

class HooksTest extends CPTestCase
{
    public $testingClass = '\RightNow\Libraries\Hooks';
    function testValidateHook()
    {
        $value = Hooks::validateHook("pre_feedback_submit", array(
            "class"     => "SomeModel",
            "function"  => "preFeedbackSubmit",
            "filepath"  => "feedback",
        ));
        $this->assertTrue($value);

        $value = Hooks::validateHook("pre_feedback_submit", array(
            "class"     => "SomeModel",
            "function"  => "preFeedbackSubmit",
            "filepath"  => "../feedback",
        ));
        $this->assertTrue(is_string($value));

        $value = Hooks::validateHook("pre_feedback_submit", array(
            "class"     => "SomeModel",
            "filepath"  => "feedback",
        ));
        $this->assertTrue(is_string($value));

        $value = Hooks::validateHook("pre_feedback_submit", array(
            "function"  => "preFeedbackSubmit",
            "filepath"  => "feedback",
        ));
        $this->assertTrue(is_string($value));
    }

    function testCallHook()
    {
        // create a test model
        $modelFile = APPPATH . "models/custom/Hooks.php";
        $modelData = <<<EOF
<?php
namespace Custom\\Models;

class Hooks extends \\RightNow\\Models\\Base
{
    function __construct()
    {
        parent::__construct();
    }

    function preContactCreate(&\$hookData)
    {
        \$hookData['data']->Title = "Mr";
        \$hookData['data']->Name->First = "James";
        \$hookData['data']->Name->Last = "Bond";
    }
}
EOF;

        file_put_contents($modelFile, $modelData);

        // make up a hook
        $rnHooks = array(
            'pre_contact_create'    => array(
                array(
                    'class'         => 'Hooks',
                    'function'      => 'preContactCreate',
                    'filepath'      => '',
                ),
            ),
        );

        list ($class, $method, $hooks) = $this->reflect('method:callHook', 'hooks');
        $instance = $class->newInstance();
        $hooks->setValue($instance, $rnHooks);

        $CI = get_instance();
        $hookData = array('data' => $CI->model('Contact')->getBlank()->result);
        $instance->callHook("pre_contact_create", $hookData);

        $this->assertTrue(($hookData['data']->Title === "Mr"));
        $this->assertTrue(($hookData['data']->Name->First === "James"));
        $this->assertTrue(($hookData['data']->Name->Last === "Bond"));

        $hooks->setValue($instance, array());
        unlink($modelFile);
    }

    function testAddStandardHooks()
    {
        list($class, $method, $CI, $hooks) = $this->reflect('method:addStandardHooks', 'CI', 'hooks');

        $previousHooks = $hooks->getValue();
        $previousConfigs = Helper::getConfigValues(array('SIEBEL_EAI_HOST'));
        $previousOkcsConfigs = Helper::getConfigValues(array('OKCS_ENABLED'));

        $hooks->setValue(null, false);
        Helper::setConfigValues(array('SIEBEL_EAI_HOST' => ''));
        $method->invoke(null);
        $this->assertIdentical(false, $hooks->getValue());

        $hooks->setValue(null, array('monkeys' => 'reading'));
        $method->invoke(null);
        $this->assertIdentical(array('monkeys' => 'reading'), $hooks->getValue());

        $hooks->setValue(null, false);
        Helper::setConfigValues(array('SIEBEL_EAI_HOST' => 'bobby'));
        $method->invoke(null);
        $this->assertIdentical(array(
            'pre_incident_create_save' => array(array(
                'class' => 'Siebel',
                'function' => 'processRequest',
                'filepath' => '',
                'use_standard_model' => true,
            )),
            'pre_register_smart_assistant_resolution' => array(array(
                'class' => 'Siebel',
                'function' => 'registerSmartAssistantResolution',
                'filepath' => '',
                'use_standard_model' => true,
            ))
        ), $hooks->getValue());

        $hooks->setValue(null, array('monkeys' => 'reading'));
        $method->invoke(null);
        $this->assertIdentical(array(
            'monkeys' => 'reading',
            'pre_incident_create_save' => array(array(
                'class' => 'Siebel',
                'function' => 'processRequest',
                'filepath' => '',
                'use_standard_model' => true,
            )),
            'pre_register_smart_assistant_resolution' => array(array(
                'class' => 'Siebel',
                'function' => 'registerSmartAssistantResolution',
                'filepath' => '',
                'use_standard_model' => true,
            ))
        ), $hooks->getValue());

        $hooks->setValue(null, array('pre_incident_create_save' => array(array('monkeys' => 'reading')), 'pre_register_smart_assistant_resolution' => array(array('monkeys' => 'writing'))));
        $method->invoke(null);
        $this->assertIdentical(array(
            'pre_incident_create_save' => array(
                array('monkeys' => 'reading'),
                array(
                    'class' => 'Siebel',
                    'function' => 'processRequest',
                    'filepath' => '',
                    'use_standard_model' => true,
                )
            ),
            'pre_register_smart_assistant_resolution' => array(
                array('monkeys' => 'writing'),
                array(
                    'class' => 'Siebel',
                    'function' => 'registerSmartAssistantResolution',
                    'filepath' => '',
                    'use_standard_model' => true,
                )
            )
        ), $hooks->getValue());

        $hooks->setValue(null, $previousHooks);
        Helper::setConfigValues($previousConfigs);

        $hooks->setValue(null, false);
        Helper::setConfigValues(array('OKCS_ENABLED' => false));
        $method->invoke(null);
        $this->assertIdentical(false, $hooks->getValue());

        $hooks->setValue(null, false);
        Helper::setConfigValues(array('OKCS_ENABLED' => true));
        $method->invoke(null);
        $this->assertIdentical(array(
            'pre_retrieve_smart_assistant_answers' => array(array(
                'class' => 'Okcs',
                'function' => 'retrieveSmartAssistantRequest',
                'filepath' => '',
                'use_standard_model' => true,
            )),
            'okcs_site_map_answers' => array(array(
                'class' => 'Okcs',
                'function' => 'getArticlesForSiteMap',
                'filepath' => '',
                'use_standard_model' => true,
            ))
        ), $hooks->getValue());

        $hooks->setValue(null, $previousHooks);
        Helper::setConfigValues($previousOkcsConfigs);
    }

    function testRunHook()
    {
        $response = Helper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/" . urlencode(__FILE__) . "/" . __CLASS__ . "/runHook/1");
        $this->assertIdentical($response, "Hook Error: In hook blah, 'class' index is not set.");
        $response = Helper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/" . urlencode(__FILE__) . "/" . __CLASS__ . "/runHook/2");
        $this->assertIdentical($response, "Hook Error: In hook blah, 'function' index is not set.");
        $response = Helper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/" . urlencode(__FILE__) . "/" . __CLASS__ . "/runHook/3");
        $this->assertIdentical($response, "Hook Error: In hook blah, filepath may not contain ../");
        $response = Helper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/" . urlencode(__FILE__) . "/" . __CLASS__ . "/runHook/4");
        $this->assertIdentical($response, "Hook Error: In hook blah, function 'isItThere' does not exist in model Sample.");
        $response = Helper::makeRequest("/ci/unitTest/wgetRecipient/invokeTestMethod/" . urlencode(__FILE__) . "/" . __CLASS__ . "/runHook/5");
        $this->assertTrue(Text::stringContains($response, "Unable to locate the specified model: notExist."));

        $runHook = $this->getMethod('runHook', true);
        $response = $runHook('blah', array('class' => 'HooksTest', 'function' => 'littleHook', 'filepath' => 'Libraries/tests/Hooks.test.php'), array('dataForHook'));
        $this->assertIdentical($response, 'hey!');
        $this->assertIdentical(self::$hookData, array('dataForHook'));
    }

    function littleHook(&$hookData)
    {
        self::$hookData = $hookData;
        return 'hey!';
    }

    function runHook()
    {
        $type = Text::getSubstringAfter($this->CI->uri->uri_string(), 'runHook/');
        $runHook = $this->getMethod('runHook', true);
        if ($type === '1')
            $runHook('blah', array(), array());
        else if ($type === '2')
            $runHook('blah', array('class' => 'something'), array());
        else if ($type === '3')
            $runHook('blah', array('class' => 'something', 'function' => 'else', 'filepath' => 'tricky/../hacker'), array());
        else if ($type === '4')
            $runHook('blah', array('class' => 'Sample', 'function' => 'isItThere', 'filepath' => ''), array());
        else if ($type === '5')
            $runHook('blah', array('class' => 'notExist', 'function' => 'isItThere', 'filepath' => ''), array());
    }

    function testGetHookModelPath()
    {
        $method = $this->getMethod('getHookModelPath');

        $this->assertIdentical('custom//tables', $method(array('class' => 'tables', 'filepath' => '')));
        $this->assertIdentical('custom/bobby/tables', $method(array('class' => 'tables', 'filepath' => 'bobby')));
        $this->assertIdentical('custom//tables', $method(array('class' => 'tables', 'filepath' => '', 'use_standard_model' => 'a')));
        $this->assertIdentical('custom/bobby/tables', $method(array('class' => 'tables', 'filepath' => 'bobby', 'use_standard_model' => 'a')));
        $this->assertIdentical('tables', $method(array('class' => 'tables', 'filepath' => '', 'use_standard_model' => true)));
        $this->assertIdentical('tables', $method(array('class' => 'tables', 'filepath' => 'bobby', 'use_standard_model' => true)));
    }
    
    function testKAAddStandardHooks()
    {
        list($class, $method, $CI, $hooks) = $this->reflect('method:addKAStandardHooks', 'CI', 'hooks');

        $previousHooks = $hooks->getValue();
        $previousOkcsConfigs = Helper::getConfigValues(array('OKCS_ENABLED'));
        $hooks->setValue(null, $previousHooks);
        $hooks->setValue(null, false);
        Helper::setConfigValues(array('OKCS_ENABLED' => false));
        $method->invoke(null);
        $this->assertIdentical(false, $hooks->getValue());
        $hooks->setValue(null, false);
        Helper::setConfigValues(array('OKCS_ENABLED' => true));
        $method->invoke(null);
        $this->assertIdentical(array(
            'pre_retrieve_smart_assistant_answers' => array(array(
                'class' => 'Okcs',
                'function' => 'retrieveSmartAssistantRequest',
                'filepath' => '',
                'use_standard_model' => true,
            )),
            'okcs_site_map_answers' => array(array(
                'class' => 'Okcs',
                'function' => 'getArticlesForSiteMap',
                'filepath' => '',
                'use_standard_model' => true,
            ))
        ), $hooks->getValue());

        $hooks->setValue(null, $previousHooks);
        Helper::setConfigValues($previousOkcsConfigs);
    }
}

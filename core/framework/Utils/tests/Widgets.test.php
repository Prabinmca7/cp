<?php

use RightNow\Utils\Text,
    RightNow\Utils\Widgets,
    RightNow\Utils\Config as Config,
    RightNow\UnitTest\Helper as TestHelper,
    RightNow\Utils\FileSystem,
    RightNow\Internal\Utils\Version,
    RightNow\Internal\Libraries\Widget\Registry,
    RightNow\Internal\Libraries\ClientLoader;

TestHelper::loadTestedFile(__FILE__);
class WidgetTest extends CPTestCase {
    public $testingClass = '\RightNow\Utils\Widgets';

    function __construct() {
        $this->dir = sprintf("%s/unitTest/%s", get_cfg_var('upload_tmp_dir'), get_class($this));
    }

    function getWidgetInstancePathInfoMeta($widgetPath) {
        $widgetPathInfo = Registry::getWidgetPathInfo($widgetPath);
        $convert = $this->getStaticMethod('convertAttributeTagsToValues');
        $meta = $convert(Widgets::getWidgetInfo($widgetPathInfo), array('validate' => true, 'eval' => true));
        $controllerWidget = Registry::getWidgetPathInfo($meta['controller_path']);
        $controllerClass = $controllerWidget->namespacedClassName;
        \RightNow\Utils\Framework::installPathRestrictions();
        Widgets::requireWidgetControllerWithPathInfo($widgetPathInfo);
        $widgetInstance = new $controllerClass($meta['attributes']);
        return array($widgetInstance, $widgetPathInfo, $meta);
    }

    function testRnWidgetRenderCall() {
        $CI = get_instance();

        $CI->widgetCallsOnPage = array('standard/utils/OracleLogo' => array('view' => 'asdf'));
        $result = Widgets::rnWidgetRenderCall('standard/utils/OracleLogo');
        $this->assertIdentical('asdf', $result);

        $result = Widgets::rnWidgetRenderCall('utils/OracleLogo');
        $this->assertIdentical('asdf', $result);

        $CI->widgetCallsOnPage = null;
    }

    function testGetFullWidgetVersionDirectory() {
        $widgetPath = 'standard/utils/OracleLogo';
        $result = Widgets::getFullWidgetVersionDirectory($widgetPath);
        $this->assertIdentical($widgetPath . '/', $result);

        $widgetPath = 'utils/OracleLogo';
        $result = Widgets::getFullWidgetVersionDirectory($widgetPath);
        $this->assertIdentical($widgetPath . '/', $result);

        $widgetPath = 'Notstandard/utils/OracleLogo';
        $result = Widgets::getFullWidgetVersionDirectory($widgetPath);
        $this->assertIdentical($widgetPath . '/', $result);
    }

    function testPushAttributesOntoStack() {
        try {
            $null = null;
            Widgets::pushAttributesOntoStack($null);
            $this->fail();
        }
        catch (Exception $e) {
            $this->pass();
        }

        try {
            $notArrayToAdd = 'not an array';
            Widgets::pushAttributesOntoStack($notArrayToAdd);
            $this->fail();
        }
        catch (Exception $e) {
            $this->pass();
        }

        $arrayToAdd = array('this' => 'that');
        Widgets::pushAttributesOntoStack($arrayToAdd);

        list(, $widgetAttributeStack) = $this->reflect('widgetAttributeStack');

        $this->assertIdentical(array(array('this' => 'that')), $widgetAttributeStack->getValue());

        Widgets::popAttributesFromStack();
    }

    function testPopAttributesFromStack() {
        $arrayToAdd = array('this' => 'that');
        Widgets::pushAttributesOntoStack($arrayToAdd);

        $popArray = Widgets::popAttributesFromStack();
        $this->assertIdentical($arrayToAdd, $popArray);

        $popArray = Widgets::popAttributesFromStack();
        $this->assertIdentical(null, $popArray);

        $popArray = Widgets::popAttributesFromStack();
        $this->assertIdentical(null, $popArray);

        $popArray = Widgets::popAttributesFromStack();
        $this->assertIdentical(null, $popArray);
    }

    function testGetWidgetStatics() {
        $widgetInfo = array(
            'requires' => array(
                'framework' => array(3.2),
                'jsModule' => array('standard', 'mobile'),
                'yui' => array(
                    'overlay'
                ),
            ),
            'js_templates' => array(
                'label' => '<% if (label) { %>  <label for="rn_<%= instanceID %>_<%= fieldName %>" id="rn_<%= instanceID %>_Label" class="rn_Label"> <%= label %> <% if (required) { %>  <span class="rn_Required"> <%= requiredMarkLabel %></span><span class="rn_ScreenReaderOnly"><%= requiredLabel %></span>  <% } %> </label> <% } %>',
                'labelValidate' => '<label for="rn_<%= instanceID %>_<%= fieldName %>_Validate" id="rn_<%= instanceID %>_<%= fieldName %>_LabelValidate" class="rn_Label"><%= label %><% if (required) { %>  <span class="rn_Required"> <%= requiredMarkLabel %></span><span class="rn_ScreenReaderOnly"><%= requiredLabel %></span> <% } %></label>',
                'overlay' => '<div class="rn_Heading">  <div class="rn_Intro" aria-describedby="rn_<%= instanceID %>_Requirements"> <div class="rn_Text"><%= title %></div> <span class="rn_ScreenReaderOnly"><%= passwordRequirementsLabel %></span> </div>   <div class="rn_Strength rn_Hidden"> <div class="rn_Meter" aria-describedby="rn_<%= instanceID %>_MeterLabel"></div> <label id="rn_<%= instanceID %>_MeterLabel"></label> </div> </div><ul class="rn_Requirements" aria-live="assertive" id="rn_<%= instanceID %>_Requirements"> <% for (var i in validations) { %> <% if (!validations.hasOwnProperty(i)) continue; %> <li data-validate="<%= i %>"> <span class="rn_ScreenReaderOnly"></span> <%= validations[i].label %> </li> <% } %></ul>'
            ),
        );

        $expectedResult = array(
            'templates' => '{"label":"<% if (label) { %>  <label for=\"rn_<%= instanceID %>_<%= fieldName %>\" id=\"rn_<%= instanceID %>_Label\" class=\"rn_Label\"> <%= label %> <% if (required) { %>  <span class=\"rn_Required\"> <%= requiredMarkLabel %><\/span><span class=\"rn_ScreenReaderOnly\"><%= requiredLabel %><\/span>  <% } %> <\/label> <% } %>","labelValidate":"<label for=\"rn_<%= instanceID %>_<%= fieldName %>_Validate\" id=\"rn_<%= instanceID %>_<%= fieldName %>_LabelValidate\" class=\"rn_Label\"><%= label %><% if (required) { %>  <span class=\"rn_Required\"> <%= requiredMarkLabel %><\/span><span class=\"rn_ScreenReaderOnly\"><%= requiredLabel %><\/span> <% } %><\/label>","overlay":"<div class=\"rn_Heading\">  <div class=\"rn_Intro\" aria-describedby=\"rn_<%= instanceID %>_Requirements\"> <div class=\"rn_Text\"><%= title %><\/div> <span class=\"rn_ScreenReaderOnly\"><%= passwordRequirementsLabel %><\/span> <\/div>   <div class=\"rn_Strength rn_Hidden\"> <div class=\"rn_Meter\" aria-describedby=\"rn_<%= instanceID %>_MeterLabel\"><\/div> <label id=\"rn_<%= instanceID %>_MeterLabel\"><\/label> <\/div> <\/div><ul class=\"rn_Requirements\" aria-live=\"assertive\" id=\"rn_<%= instanceID %>_Requirements\"> <% for (var i in validations) { %> <% if (!validations.hasOwnProperty(i)) continue; %> <li data-validate=\"<%= i %>\"> <span class=\"rn_ScreenReaderOnly\"><\/span> <%= validations[i].label %> <\/li> <% } %><\/ul>"}',
            'requires' => '["overlay"]',
        );

        $clientLoader = new ClientLoader(new \RightNow\Internal\Libraries\DeployerClientLoaderOptions());
        $clientLoader->setJavaScriptModule('overlay');
        $result = Widgets::getWidgetStatics($widgetInfo, $clientLoader);

        $this->assertEqual($result, $expectedResult);
    }

    function testNormalizeSlashesInWidgetPath() {
        $relativeWidgetPath = 'standard/login/LoginDialog2';
        $absoluteWidgetPath = 'www/rnt/site/cgi-bin/interface.cfg/scripts/cp/customer/development/widgets/custom/sample/SampleWidget';
        $paths = array(
            array('//standard//login//LoginDialog2', 'standard/login/LoginDialog2', true),
            array('\standard\login\LoginDialog2', 'standard/login/LoginDialog2', true),
            array($relativeWidgetPath, $relativeWidgetPath, true),
            array("/$relativeWidgetPath", $relativeWidgetPath, true),
            array("/$relativeWidgetPath", "/$relativeWidgetPath", false),
            array("//$relativeWidgetPath", $relativeWidgetPath, true),
            array($absoluteWidgetPath, $absoluteWidgetPath, true),
            array("/$absoluteWidgetPath", $absoluteWidgetPath, true),
            array("/$absoluteWidgetPath", "/$absoluteWidgetPath", false),
            array("//$absoluteWidgetPath", $absoluteWidgetPath, true),
        );

        foreach ($paths as $args) {
            list($input, $expected, $removeLeadingSlash) = $args;
            $this->assertEqual($expected, Widgets::normalizeSlashesInWidgetPath($input, $removeLeadingSlash));
        }
    }

    function testGetWidgetInfoFromManifest() {
        umask(0000);
        $getWidgetInfoFromManifest = $this->getStaticMethod('getWidgetInfoFromManifest');

        $testWidgetPath = 'custom/sample/SampleWidget';
        $testFilePath = CUSTOMER_FILES . "widgets/" . $testWidgetPath;
        $testManifest = "$testFilePath/1.0/info.yml";
        $originalManifest = file_get_contents($testManifest);
        Registry::setSourceBasePath(CUSTOMER_FILES);
        $widget = Registry::getWidgetPathInfo($testWidgetPath);

        file_put_contents($testManifest, 'junk\:;;::;');
        $results = $getWidgetInfoFromManifest($widget->absolutePath, $widget->relativePath, array(), true);
        $this->assertTrue(is_string($results));

        $yaml = array('requires' => array('framework' => array('3.0', '3.1'), 'jsModule' => array('standard')), 'version' => "1.0");
        file_put_contents($testManifest, yaml_emit($yaml));

        $results = $getWidgetInfoFromManifest($widget->absolutePath, $widget->relativePath, array(), true);
        $this->assertTrue(is_array($results));
        $this->assertEqual(1, $results['version']);
        $this->assertTrue(is_array($results['requires']));
        $this->assertEqual(array('3.0', '3.1'), $results['requires']['framework']);

        // Invalid extends
        file_put_contents($testManifest, yaml_emit(array('extends' => array('widget' => 'custom/banana/IDontExist/'))));
        $results = $getWidgetInfoFromManifest($widget->absolutePath, $widget->relativePath, array(), true);
        $this->assertIsA($results, 'string');
        $this->assertTrue(Text::stringContains($results, 'invalid or deactivated parent'));

        // Can't extend itself
        file_put_contents($testManifest, yaml_emit(array('extends' => array('widget' => 'custom/sample/SampleWidget/'))));
        $results = $getWidgetInfoFromManifest($widget->absolutePath, $widget->relativePath, array(), true);
        $this->assertIsA($results, 'string');
        $this->assertTrue(Text::stringContains($results, 'cannot extend from itself'));

        file_put_contents($testManifest, $originalManifest);
    }

    function testMergeInheritedAttributes(){
        $mergeAttributes = $this->getStaticMethod('mergeInheritedAttributes');

        $this->assertIdentical(array(), $mergeAttributes(null, array()));
        $this->assertIdentical(array(), $mergeAttributes(false, array()));
        $this->assertIdentical(array(), $mergeAttributes(array(), array()));

        $this->assertIdentical(array('label' => array('name' => 'Label', 'default' => 'My Label')), $mergeAttributes(array('label' => array('name' => 'Label', 'default' => 'My Label')), array()));

        $this->assertIdentical(array('label' => array('name' => 'Label', 'default' => 'My Label'), 'custom_label' => array('name' => 'Custom Label', 'default' => 'My Custom Label', 'inherited' => true)),
            $mergeAttributes(array('label' => array('name' => 'Label', 'default' => 'My Label')), array('custom_label' => array('name' => 'Custom Label', 'default' => 'My Custom Label'))));

        $this->assertIdentical(array(), $mergeAttributes(array('label' => 'unset'), array('label' => array('name' => 'Custom Label', 'default' => 'My Custom Label'))));

        $this->assertIdentical(array('label' => array('name' => 'Label', 'default' => 'My Label')),
            $mergeAttributes(array('label' => array('name' => 'Label', 'default' => 'My Label')), array('custom_label' => 'unset')));
    }

    function testConvertAttributeTagsToValues(){
        $convertAttributes = $this->getStaticMethod('convertAttributeTagsToValues');

        //Array without attributes
        $this->assertTrue(is_array($convertAttributes(array(), array('validate' => false, 'eval' => true))));
        $this->assertTrue(is_array($convertAttributes(array('attributes' => null), array('validate' => false, 'eval' => true))));
        $this->assertTrue(is_array($convertAttributes(array('attributes' => 'empty'), array('validate' => false, 'eval' => true))));

        //Literal value non-conversion
        $parameters = array('attributes' => array('one' => array('name' => 'Answer ID', 'description' => 'AnswerID', 'default' => '4', 'optlistId' => 99)));

        $result = $convertAttributes($parameters, array('validate' => false, 'eval' => true));
        $shortened = $result['attributes']['one'];
        $this->assertTrue(is_object($shortened));
        $this->assertSame('Answer ID', $shortened->name);
        $this->assertSame('AnswerID', $shortened->description);
        $this->assertSame('AnswerID', $shortened->tooltip);
        $this->assertSame('4', $shortened->default);
        $this->assertSame(99, $shortened->optlistId);

        //Auto default value computation
        $result = $convertAttributes(array('attributes' => array('one' => array('type' => 'string'),
                                                                 'two' => array('type' => 'filepath'),
                                                                 'three' => array('type' => 'int'),
                                                                 'four' => array('type' => 'integer'),
                                                                 'five' => array('type' => 'bool'),
                                                                 'six' => array('type' => 'boolean'),
                                                                 'seven' => array('type' => 'option', 'options' => array(0)),
                                                                 'eight' => array('type' => 'multioption', 'options' => array(0)))), array('validate' => false, 'eval' => true));

        $this->assertSame('', $result['attributes']['one']->default);
        $this->assertSame('', $result['attributes']['two']->default);
        $this->assertNull($result['attributes']['three']->default);
        $this->assertNull($result['attributes']['four']->default);
        $this->assertFalse($result['attributes']['five']->default);
        $this->assertFalse($result['attributes']['six']->default);
        $this->assertNull($result['attributes']['seven']->default);
        $this->assertIsA($result['attributes']['eight']->default, 'array');

        //Code calculation return
        $result = $convertAttributes(array('attributes' => array('one' => array('type' => 'string'),
                                                                 'two' => array('type' => 'filepath'),
                                                                 'three' => array('type' => 'int'),
                                                                 'four' => array('type' => 'integer'),
                                                                 'five' => array('type' => 'bool'),
                                                                 'six' => array('type' => 'boolean'),
                                                                 'seven' => array('type' => 'option', 'options' => array(0)),
                                                                 'eight' => array('type' => 'multioption', 'options' => array(0)))), array('validate' => false, 'eval' => false));

        $this->assertSame("''", $result['attributes']['one']->default);
        $this->assertSame("''", $result['attributes']['two']->default);
        $this->assertSame("null", $result['attributes']['three']->default);
        $this->assertSame("null", $result['attributes']['four']->default);
        $this->assertSame("false", $result['attributes']['five']->default);
        $this->assertSame("false", $result['attributes']['six']->default);
        $this->assertSame("null", $result['attributes']['seven']->default);
        $this->assertSame("array()", $result['attributes']['eight']->default);

        // Omit certain properties
        $result = $convertAttributes(array('attributes' => array(
            'one' => array('type' => 'string', 'description' => 'blah'),
        )), array('validate' => false, 'omit' => array('description')));

        $this->assertNull($result['attributes']['one']->description);
        $this->assertNull($result['attributes']['one']->tooltip);
        $this->assertSame('string', $result['attributes']['one']->type);

        $result = $convertAttributes(array('attributes' => array(
            'one' => array('type' => 'string', 'description' => 'blah', 'default' => 'banana'),
        )), array('validate' => false, 'omit' => array('description', 'type')));

        $this->assertNull($result['attributes']['one']->description);
        $this->assertNull($result['attributes']['one']->tooltip);
        $this->assertNull($result['attributes']['one']->type);
        $this->assertSame('banana', $result['attributes']['one']->default);

        // Validation error is ignored
        $result = $convertAttributes(array('attributes' => array(
            'one' => array('type' => 'string', 'default' => 'blah'),
        )), array('validate' => true, 'omit' => array('description', 'name')));

        $this->assertIsA($result, 'array');
        $this->assertNull($result['attributes']['one']->description);
        $this->assertNull($result['attributes']['one']->tooltip);
        $this->assertNull($result['attributes']['one']->name);
        $this->assertSame('string', $result['attributes']['one']->type);
        $this->assertSame('blah', $result['attributes']['one']->default);

        // Validation error isn't ignored
        $result = $convertAttributes(array('attributes' => array(
            'one' => array('type' => 'string', 'default' => 'blah'),
        )), array('validate' => true, 'omit' => array('default')));

        $this->assertIsA($result, 'string');
        // Attribute's property values are not in an array
        // @@@  QA 130418-000090
        $result = $convertAttributes(array('attributes' => array(
            'one' => 'string'
        )), array('validate' => true, 'omit' => array('default')));
        $this->assertIsA($result, 'string');
    }

    function testConvertUrlParameterTagsToValues(){
        $convertParameters = $this->getStaticMethod('convertUrlParameterTagsToValues');

        //Array without URL parameters
        $this->assertTrue(is_array($convertParameters(array())));
        $this->assertTrue(is_array($convertParameters(array('info'))));
        $this->assertTrue(is_array($convertParameters(array('info' => array('urlParameters' => null)))));

        //Literal value non-conversion
        $parameters = array('info' => array('urlParameters' => array('one' => array('name' => 'Answer ID', 'description' => 'AnswerID', 'example' => 'a_id/4'),
                                                                     'two' => array('name' => 'Incident ID'),
                                                                     'three' => array('description' => 'Parameter description'),
                                                                     'four' => array('example' => 'key/value'),
                                                                     'five' => array('name' => null, 'description' => 12, 'example' => false),
                                                                     'six' => array('foo' => 'bar'))));
        $result = $convertParameters($parameters);
        $shortened = $result['info']['urlParameters'];
        $this->assertSame(array('name' => 'Answer ID', 'description' => 'AnswerID', 'example' => 'a_id/4'), $shortened['one']);
        $this->assertSame(array('name' => 'Incident ID'), $shortened['two']);
        $this->assertSame(array('description' => 'Parameter description'), $shortened['three']);
        $this->assertSame(array('example' => 'key/value'), $shortened['four']);
        $this->assertSame(array('name' => null, 'description' => 12, 'example' => false), $shortened['five']);
        $this->assertSame(array('foo' => 'bar'), $shortened['six']);

        //Tag conversion
        $parameters = array('info' => array('urlParameters' => array('one' => array('name' => 'rn:as' . 'tr:Answer ID', 'description' => 'rn:def:CP_NOV09_ANSWERS_DEFAULT', 'example' => 'rn:php:sprintf("foo %s", "bar")'))));
        $result = $convertParameters($parameters);
        $shortened = $result['info']['urlParameters'];
        $this->assertSame('Answer ID', $shortened['one']['name']);
        $this->assertSame(CP_NOV09_ANSWERS_DEFAULT, $shortened['one']['description']);
        $this->assertSame('foo bar', $shortened['one']['example']);
    }

    function testValidateWidgetManifestInfo() {
        $validateWidgetManifestInfo = $this->getStaticMethod('validateWidgetManifestInfo');

        // minimum requirements for a manifest
        $this->assertTrue(is_string($validateWidgetManifestInfo(false)));
        $this->assertTrue(is_string($validateWidgetManifestInfo(array('version' => 1))));
        $this->assertTrue(is_string($validateWidgetManifestInfo(array('requires' => 1))));
        $this->assertTrue(is_string($validateWidgetManifestInfo(array('requires' => 1, 'version' => 1))));
        $this->assertTrue(is_string($validateWidgetManifestInfo(array('requires' => array(), 'version' => 0))));
        $this->assertTrue(is_string($validateWidgetManifestInfo(array('requires' => array(), 'version' => 1))));
        $this->assertTrue(is_string($validateWidgetManifestInfo(array('requires' => array('framework'), 'version' => 1))));
        $this->assertTrue(is_string($validateWidgetManifestInfo(array('requires' => array('framework' => ''), 'version' => 1))));
        $minimumRequirements = array('requires' => array('framework' => array('3.0'), 'jsModule' => array()), 'version' => 1);
        $this->assertTrue(is_array($validateWidgetManifestInfo($minimumRequirements)));

        $verbose = false;
        $validate = function($attribute, $data) use ($validateWidgetManifestInfo, $minimumRequirements, $verbose) {
            $actual = $validateWidgetManifestInfo($minimumRequirements + array($attribute => $data));
            if ($verbose) {
                printf("<pre>%s</pre><br>", var_export($actual, true));
            }
            return $actual;
        };

        // EXTENDS
        $this->assertTrue(is_string($validate('extends', null)));
        $this->assertTrue(is_string($validate('extends', '')));
        $this->assertTrue(is_string($validate('extends', '  ')));
        $this->assertTrue(is_string($validate('extends', array())));
        $this->assertTrue(is_string($validate('extends', array('widget' => ''))));
        $this->assertTrue(is_string($validate('extends', '/standard/foo/Bar')));
        $this->assertTrue(is_string($validate('extends', 'standard/foo/Bar')));
        $this->assertTrue(is_string($validate('extends', array('widget' => 'standard/foo/Bar'))));
        $this->assertTrue(is_string($validate('extends', array('widget' => 'standard/foo/Bar', 'components' => ''))));
        $this->assertTrue(is_array($validate('extends', array('widget' => 'standard/foo/Bar', 'components' => '', 'overrideViewAndLogic' => true))));
        $this->assertTrue(is_string($validate('extends', array('widget' => 'standard/foo/Bar', 'components' => 'js,php'))));
        $this->assertTrue(is_string($validate('extends', array('widget' => 'standard/foo/Bar', 'components' => array()))));
        $this->assertTrue(is_array($validate('extends', array('widget' => 'standard/foo/Bar', 'components' => array(), 'overrideViewAndLogic' => true))));
        $this->assertTrue(is_string($validate('extends', array('widget' => 'standard/foo/Bar', 'components' => array('foo', 'bar')))));
        $this->assertTrue(is_array($validate('extends', array('widget' => 'standard/foo/Bar', 'components' => array('foo', 'bar'), 'overrideViewAndLogic' => true))));
        $this->assertTrue(is_string($validate('extends', array('widget' => 'standard/foo/Bar', 'components' => array('js' => true, 'php' => true)))));
        $this->assertTrue(is_string($validate('extends', array('widget' => '/standard/foo/Bar', 'components' => array('js')))));
        $this->assertTrue(is_string($validate('extends', array('widget' => 'standard/foo/Bar', 'components' => array('js'), 'versions' => 'banana'))));
        // version optional and converted to list when specified as a single.
        $this->assertTrue(is_array($validate('extends', array('widget' => 'standard/foo/Bar', 'components' => array('js')))));
        $expected = array('widget' => 'standard/foo/Bar', 'components' => array('js' => true), 'versions' => array('1.0'));
        $actual = $validate('extends', array('widget' => 'standard/foo/Bar', 'components' => array('js'), 'versions' => '1.0'));
        $this->assertIdentical($expected, $actual['extends']);
        $return = $validate('extends', array('widget' => 'standard/foo/Bar', 'components' => array('js'), 'versions' => '1.0'));
        $this->assertTrue(is_array($return));
        $this->assertSame('standard/foo/Bar', $return['extends']['widget']);
        $this->assertTrue($return['extends']['components']['js']);
        $this->assertSame(null, $return['extends']['components']['php']);
        $this->assertSame(null, $return['extends']['components']['view']);
        $return = $validate('extends', array('widget' => 'standard/foo/Bar', 'components' => array('js', 'php', 'foo', 'view'), 'versions' => '1.0'));
        $this->assertTrue(is_array($return));
        $this->assertSame('standard/foo/Bar', $return['extends']['widget']);
        $this->assertTrue($return['extends']['components']['js']);
        $this->assertTrue($return['extends']['components']['php']);
        $this->assertTrue($return['extends']['components']['view']);

        // CONTAINS
        $this->assertTrue(is_string($validate('contains', null)));
        $this->assertTrue(is_string($validate('contains', '')));
        $this->assertTrue(is_string($validate('contains', ' ')));
        $this->assertTrue(is_string($validate('contains', array(array()))));
        $this->assertTrue(is_string($validate('contains', array(array('widget' => 'standard/output/FieldDisplay', 'versions' => 'X.Y')))));
        $this->assertTrue(is_string($validate('contains', array(array('widget' => '', 'versions' => '1.0')))));
        $this->assertTrue(is_string($validate('contains', array(array('widget' => 'standard/output/FieldDisplay', 'versions' => '')))));
        $this->assertTrue(is_string($validate('contains', array(
            array('widget' => 'standard/output/FieldDisplay', 'versions' => '1.0'),
            array('widget' => 'standard/output/DataDisplay', 'versions' => 'X.Y'),
        ))));
        $this->assertTrue(is_array($validate('contains', array())));
        $this->assertTrue(is_array($validate('contains', array(array('widget' => 'standard/output/FieldDisplay', 'versions' => '1.0')))));
        $this->assertTrue(is_array($validate('contains', array(
            array('widget' => 'standard/output/FieldDisplay', 'versions' => '1.0'),
            array('widget' => 'standard/output/DataDisplay', 'versions' => '2.1'),
        ))));
        // version optional and converted to list when specified as a single.
        $this->assertTrue(is_array($validate('contains', array(array('widget' => 'standard/output/FieldDisplay')))));
        $expected = $minimumRequirements + array('contains' => array(array('widget' => 'standard/output/FieldDisplay', 'versions' => array('1.0'))));
        $this->assertIdentical($expected, $validate('contains', array(array('widget' => 'standard/output/FieldDisplay', 'versions' => '1.0'))));

        // unset
        $return = $validateWidgetManifestInfo($minimumRequirements + array('attributes' => array('nonono' => 'unset')));
        $this->assertSame('unset', $return['attributes']['nonono']);
    }

    function testValidateWidgetDependencies() {
        $validateWidgetDependencies = $this->getStaticMethod('validateWidgetDependencies');
        $verbose = false;
        $validate = function($entry, $relationship = 'extends') use ($validateWidgetDependencies, $verbose) {
            $results = $validateWidgetDependencies($entry, $relationship);
            if (is_string($results) && $verbose) {
                print("$results<br/>");
            }
            return $results;
        };

        // EXTENDS
        $this->assertTrue(is_string($validate(null)));
        $this->assertTrue(is_string($validate('')));
        $this->assertTrue(is_string($validate('  ')));
        $this->assertTrue(is_string($validate(array())));
        $this->assertTrue(is_string($validate(array('widget' => ''))));
        $this->assertTrue(is_string($validate('/standard/foo/Bar')));
        $this->assertTrue(is_string($validate('standard/foo/Bar')));
        $this->assertTrue(is_string($validate(array('widget' => 'standard/foo/Bar'))));
        $this->assertTrue(is_string($validate(array('widget' => 'standard/foo/Bar', 'components' => ''))));
        $this->assertTrue(is_string($validate(array('widget' => 'standard/foo/Bar', 'components' => 'js,php'))));
        $this->assertTrue(is_string($validate(array('widget' => 'standard/foo/Bar', 'components' => array()))));
        $this->assertTrue(is_string($validate(array('widget' => 'standard/foo/Bar', 'components' => array('foo', 'bar')))));
        $this->assertTrue(is_string($validate(array('widget' => 'standard/foo/Bar', 'components' => array('js' => true, 'php' => true)))));
        $this->assertTrue(is_string($validate(array('widget' => '/standard/foo/Bar', 'components' => array('js')))));
        $this->assertTrue(is_string($validate(array('widget' => 'standard/foo/Bar', 'components' => array('js'), 'versions' => 'banana'))));

        // overrideViewAndLogic
        $this->assertTrue(is_string($validate(array('widget' => 'standard/foo/Bar', 'overrideViewAndLogic' => true, components => array('view'), 'versions' => '1.0'))));
        $this->assertTrue(is_string($validate(array('widget' => 'standard/foo/Bar', 'overrideViewAndLogic' => true, components => array('js'), 'versions' => '1.0'))));
        $this->assertTrue(is_array($validate(array('widget' => 'standard/foo/Bar', 'overrideViewAndLogic' => true, components => array('php'), 'versions' => '1.0'))));

        // // version optional and converted to list when specified as a single.
        $this->assertTrue(is_array($validate(array('widget' => 'standard/foo/Bar', 'components' => array('js')))));

        $expected = array('widget' => 'standard/foo/Bar', 'components' => array('js' => true), 'versions' => array('1.0'));
        $actual = $validate(array('widget' => 'standard/foo/Bar', 'components' => array('js'), 'versions' => '1.0'));
        $this->assertIdentical($expected, $actual);

        $return = $validate(array('widget' => 'standard/foo/Bar', 'components' => array('js'), 'versions' => '1.0'));
        $this->assertTrue(is_array($return));
        $this->assertSame('standard/foo/Bar', $return['widget']);
        $this->assertTrue($return['components']['js']);
        $this->assertSame(null, $return['components']['php']);
        $this->assertSame(null, $return['components']['view']);

        $return = $validate(array('widget' => 'standard/foo/Bar', 'components' => array('js', 'php', 'foo', 'view'), 'versions' => '1.0'));
        $this->assertTrue(is_array($return));
        $this->assertSame('standard/foo/Bar', $return['widget']);
        $this->assertTrue($return['components']['js']);
        $this->assertTrue($return['components']['php']);
        $this->assertTrue($return['components']['view']);

        // CONTAINS
        $this->assertTrue(is_string($validate(null, 'contains')));
        $this->assertTrue(is_string($validate('', 'contains')));
        $this->assertTrue(is_string($validate(' ', 'contains')));
        $this->assertTrue(is_string($validate(array(array()), 'contains')));
        $this->assertTrue(is_string($validate(array(array('widget' => 'standard/output/FieldDisplay', 'versions' => 'X.Y')), 'contains')));
        $this->assertTrue(is_string($validate(array(array('widget' => '', 'versions' => '1.0')))));
        $this->assertTrue(is_string($validate(array(array('widget' => 'standard/output/FieldDisplay', 'versions' => '')))));
        $this->assertTrue(is_string($validate(array(
            array('widget' => 'standard/output/FieldDisplay', 'versions' => '1.0'),
            array('widget' => 'standard/output/DataDisplay', 'versions' => 'X.Y'),
        ), 'contains')));
        $this->assertTrue(is_array($validate(array(), 'contains')));
        $this->assertTrue(is_array($validate(array(array('widget' => 'standard/output/FieldDisplay', 'versions' => '1.0')), 'contains')));
        $this->assertTrue(is_array($validate(array(
            array('widget' => 'standard/output/FieldDisplay', 'versions' => '1.0'),
            array('widget' => 'standard/output/DataDisplay', 'versions' => '2.1'),
        ), 'contains')));
        // version optional and converted to list when specified as a single.
        $this->assertTrue(is_array($validate(array(array('widget' => 'standard/output/FieldDisplay')), 'contains')));
        $expected = array(array('widget' => 'standard/output/FieldDisplay', 'versions' => array('1.0')));
        $this->assertIdentical($expected, $validate(array(array('widget' => 'standard/output/FieldDisplay', 'versions' => '1.0')), 'contains'));
    }

    function testValidateWidgetAttributeStructure(){
        $validateWidgetManifestAttributes = $this->getStaticMethod('validateWidgetAttributeStructure');

        //Empty/unset attributes
        $this->assertNull($validateWidgetManifestAttributes(array()));
        $this->assertNull($validateWidgetManifestAttributes(array('attributes' => array())));
        $this->assertNull($validateWidgetManifestAttributes(array('attributes' => array('foo' => 'unset', 'bar' => 'unset'))));

        //Attributes with no name/description
        $result = $validateWidgetManifestAttributes(array('attributes' => array('nonono' => (object) array('name' => ''))));
        $this->assertIsA($result, 'string');
        $this->assertTrue(Text::stringContains($result, 'name'));
        $this->assertTrue(Text::stringContains($result, 'nonono'));

        $result = $validateWidgetManifestAttributes(array('attributes' => array('nonono' => (object)array('description' => '', 'name' => 'nonono'))));
        $this->assertIsA($result, 'string');
        $this->assertTrue(Text::stringContains($result, 'description'));
        $this->assertTrue(Text::stringContains($result, 'nonono'));

        $result = $validateWidgetManifestAttributes(array('attributes' => array('nonono' => (object)array('name' => '', 'description' => ''))));
        $this->assertIsA($result, 'string');
        $this->assertTrue(Text::stringContains($result, 'name'));

        //Attribute with invalid/missing type
        $result = $validateWidgetManifestAttributes(array('attributes' => array('nonono' => (object)array('name' => 'foo', 'description' => 'foo'))));
        $this->assertIsA($result, 'string');
        $this->assertTrue(Text::stringContains($result, 'nonono'));
        $this->assertTrue(Text::stringContains($result, 'type'));

        //List of attributes with no default
        $this->assertNull($validateWidgetManifestAttributes(array('attributes' => array('no' => (object)array('name' => 'foo', 'description' => 'foo', 'type' => 'string'),
                                                                                  'yes' => (object)array('name' => 'yes', 'description' => 'yes', 'type' => 'filepath'),
                                                                                  'maybe' => (object)array('name' => 'maybe', 'description' => 'maybe', 'type' => 'int'),
                                                                                  'kinda' => (object)array('name' => 'kinda', 'description' => 'kinda', 'type' => 'integer'),
                                                                                  'notreally' => (object)array('name' => 'notreally', 'description' => 'notreally', 'type' => 'bool'),
                                                                                  'almost' => (object)array('name' => 'almost', 'description' => 'almost', 'type' => 'boolean'),
                                                                                  'nope' => (object)array('name' => 'nope', 'description' => 'nope', 'type' => 'option', 'options'=>array('one'))))));

        //Invalid defaults
        $result = $validateWidgetManifestAttributes(array('attributes' => array('no' => (object)array('name' => 'foo', 'description' => 'foo', 'type' => 'string', 'default' => false))));
        $this->assertIsA($result, 'string');
        $this->assertTrue(Text::stringContains($result, 'no'));
        $this->assertTrue(Text::stringContains($result, 'default'));
        $this->assertTrue(Text::stringContains($result, 'string'));

        $result = $validateWidgetManifestAttributes(array('attributes' => array('no' => (object)array('name' => 'foo', 'description' => 'foo', 'type' => 'filepath', 'default' => true))));
        $this->assertIsA($result, 'string');
        $this->assertTrue(Text::stringContains($result, 'no'));
        $this->assertTrue(Text::stringContains($result, 'default'));
        $this->assertTrue(Text::stringContains($result, 'string'));

        $result = $validateWidgetManifestAttributes(array('attributes' => array('no' => (object)array('name' => 'foo', 'description' => 'foo', 'type' => 'filepath', 'default' => 27))));
        $this->assertIsA($result, 'string');
        $this->assertTrue(Text::stringContains($result, 'no'));
        $this->assertTrue(Text::stringContains($result, 'default'));
        $this->assertTrue(Text::stringContains($result, 'string'));

        $result = $validateWidgetManifestAttributes(array('attributes' => array('no' => (object)array('name' => 'foo', 'description' => 'foo', 'type' => 'ajax', 'default' => null))));
        $this->assertIsA($result, 'string');
        $this->assertTrue(Text::stringContains($result, 'no'));
        $this->assertTrue(Text::stringContains($result, 'default'));
        $this->assertTrue(Text::stringContains($result, 'specified'));

        $result = $validateWidgetManifestAttributes(array('attributes' => array('no' => (object)array('name' => 'foo', 'description' => 'foo', 'type' => 'ajax', 'default' => ''))));
        $this->assertIsA($result, 'string');
        $this->assertTrue(Text::stringContains($result, 'no'));
        $this->assertTrue(Text::stringContains($result, 'default'));
        $this->assertTrue(Text::stringContains($result, 'specified'));

        $result = $validateWidgetManifestAttributes(array('attributes' => array('no' => (object)array('name' => 'foo', 'description' => 'foo', 'type' => 'ajax', 'default' => 0))));
        $this->assertIsA($result, 'string');
        $this->assertTrue(Text::stringContains($result, 'no'));
        $this->assertTrue(Text::stringContains($result, 'default'));
        $this->assertTrue(Text::stringContains($result, 'specified'));

        $result = $validateWidgetManifestAttributes(array('attributes' => array('no' => (object)array('name' => 'foo', 'description' => 'foo', 'type' => 'ajax', 'default' => false))));
        $this->assertIsA($result, 'string');
        $this->assertTrue(Text::stringContains($result, 'no'));
        $this->assertTrue(Text::stringContains($result, 'default'));
        $this->assertTrue(Text::stringContains($result, 'specified'));

        $result = $validateWidgetManifestAttributes(array('attributes' => array('no' => (object)array('name' => 'foo', 'description' => 'foo', 'type' => 'int', 'default' => "not a number"))));
        $this->assertIsA($result, 'string');
        $this->assertTrue(Text::stringContains($result, 'no'));
        $this->assertTrue(Text::stringContains($result, 'default'));
        $this->assertTrue(Text::stringContains($result, 'integer'));

        $result = $validateWidgetManifestAttributes(array('attributes' => array('no' => (object)array('name' => 'foo', 'description' => 'foo', 'type' => 'integer', 'default' => "still not a number"))));
        $this->assertIsA($result, 'string');
        $this->assertTrue(Text::stringContains($result, 'no'));
        $this->assertTrue(Text::stringContains($result, 'default'));
        $this->assertTrue(Text::stringContains($result, 'integer'));

        $result = $validateWidgetManifestAttributes(array('attributes' => array('no' => (object)array('name' => 'foo', 'description' => 'foo', 'type' => 'bool', 'default' => 'yes'))));
        $this->assertIsA($result, 'string');
        $this->assertTrue(Text::stringContains($result, 'no'));
        $this->assertTrue(Text::stringContains($result, 'default'));
        $this->assertTrue(Text::stringContains($result, 'boolean'));
        $this->assertTrue(Text::stringContains($result, 'yes'));

        $result = $validateWidgetManifestAttributes(array('attributes' => array('no' => (object)array('name' => 'foo', 'description' => 'foo', 'type' => 'boolean', 'default' => 'no'))));
        $this->assertIsA($result, 'string');
        $this->assertTrue(Text::stringContains($result, 'no'));
        $this->assertTrue(Text::stringContains($result, 'default'));
        $this->assertTrue(Text::stringContains($result, 'boolean'));
        $this->assertTrue(Text::stringContains($result, 'no'));

        $result = $validateWidgetManifestAttributes(array('attributes' => array('no' => (object)array('name' => 'foo', 'description' => 'foo', 'type' => 'option', 'default' => 12))));
        $this->assertIsA($result, 'string');
        $this->assertTrue(Text::stringContains($result, 'no'));
        $this->assertTrue(Text::stringContains($result, 'options'));

        $result = $validateWidgetManifestAttributes(array('attributes' => array('no' => (object)array('name' => 'foo', 'description' => 'foo', 'type' => 'multioption', 'default' => 12))));
        $this->assertIsA($result, 'string');
        $this->assertTrue(Text::stringContains($result, 'no'));
        $this->assertTrue(Text::stringContains($result, 'options'));

        //Valid Defaults
        $this->assertNull($validateWidgetManifestAttributes(array('attributes' => array('no' => (object)array('name' => 'foo', 'description' => 'foo', 'type' => 'string', 'default' => "string")))));
        $this->assertNull($validateWidgetManifestAttributes(array('attributes' => array('no' => (object)array('name' => 'foo', 'description' => 'foo', 'type' => 'string', 'default' => "2")))));
        $this->assertNull($validateWidgetManifestAttributes(array('attributes' => array('no' => (object)array('name' => 'foo', 'description' => 'foo', 'type' => 'string', 'default' => 2)))));
        $this->assertNull($validateWidgetManifestAttributes(array('attributes' => array('no' => (object)array('name' => 'foo', 'description' => 'foo', 'type' => 'filepath', 'default' => "/foo/bar")))));
        $this->assertNull($validateWidgetManifestAttributes(array('attributes' => array('no' => (object)array('name' => 'foo', 'description' => 'foo', 'type' => 'int', 'default' => 0)))));
        $this->assertNull($validateWidgetManifestAttributes(array('attributes' => array('no' => (object)array('name' => 'foo', 'description' => 'foo', 'type' => 'int', 'default' => "23")))));
        $this->assertNull($validateWidgetManifestAttributes(array('attributes' => array('no' => (object)array('name' => 'foo', 'description' => 'foo', 'type' => 'int', 'default' => "23.2")))));
        $this->assertNull($validateWidgetManifestAttributes(array('attributes' => array('no' => (object)array('name' => 'foo', 'description' => 'foo', 'type' => 'int', 'default' => "1e4")))));
        $this->assertNull($validateWidgetManifestAttributes(array('attributes' => array('no' => (object)array('name' => 'foo', 'description' => 'foo', 'type' => 'bool', 'default' => 'true')))));
        $this->assertNull($validateWidgetManifestAttributes(array('attributes' => array('no' => (object)array('name' => 'foo', 'description' => 'foo', 'type' => 'bool', 'default' => 'false')))));
        $this->assertNull($validateWidgetManifestAttributes(array('attributes' => array('no' => (object)array('name' => 'foo', 'description' => 'foo', 'type' => 'bool', 'default' => true)))));
        $this->assertNull($validateWidgetManifestAttributes(array('attributes' => array('no' => (object)array('name' => 'foo', 'description' => 'foo', 'type' => 'bool', 'default' => false)))));
        $this->assertNull($validateWidgetManifestAttributes(array('attributes' => array('no' => (object)array('name' => 'foo', 'description' => 'foo', 'type' => 'option', 'default' => 'one', 'options'=> array('one', 'two', 'three'))))));

        // Ignore missing name
        $this->assertNull($validateWidgetManifestAttributes(array('attributes' => array('no' => (object) array('type' => 'string', 'default' => 'banana', 'description' => 'gnome'))), array('name')));
        // Ignore missing description
        $this->assertNull($validateWidgetManifestAttributes(array('attributes' => array('no' => (object) array('type' => 'string', 'default' => 'banana', 'name' => 'gnome'))), array('description')));
        // Ignore both
        $this->assertNull($validateWidgetManifestAttributes(array('attributes' => array('no' => (object) array('type' => 'string', 'default' => 'banana'))), array('description', 'name')));
    }

    function testUpdateDeclaredWidgetVersions() {
        # code...
    }

    function testGetDeclaredWidgetVersions() {
        # code...
    }

    function testUpdateAllWidgetVersions() {
        $results = Widgets::updateAllWidgetVersions(null, false);
        $this->assertFalse($results);

        $results = Widgets::updateAllWidgetVersions('10.4', false);
        $this->assertFalse($results);
    }

    function testUpdateWidgetVersion() {
        $results = Widgets::updateWidgetVersion('banana', 'banana');
        $this->assertFalse($results);

        $results = Widgets::updateWidgetVersion('standard/input/CustomAllInput', '1.3', false);
        $this->assertIdentical(array(
            'standard/input/CustomAllInput' => array('previousVersion' => 'current', 'newVersion' => '1.3'),
        ), $results);

        $results = Widgets::updateWidgetVersion('standard/input/CustomAllInput', '12.4', false);
        $this->assertFalse($results);

        $results = Widgets::updateWidgetVersion('standard/input/CustomAllInput', 'remove', false);
        $this->assertIdentical(array(
            'standard/input/CustomAllInput' => array('previousVersion' => 'current'),
        ), $results);
    }

    function testModifyWidgetVersions() {
        $method = $this->getStaticMethod('modifyWidgetVersions');

        $results = $method(array(), false);
        $this->assertSame(array(), $results);

        $results = $method(array(
            'standard/utils/SocialBookmarkLink' => 'yeah',
            'banana' => '2.0',
            'hurling' => 'remove',
        ), false);
        $this->assertIdentical(array(
            'standard/utils/SocialBookmarkLink' => array('previousVersion' => 'current', 'newVersion' => 'yeah'),
            'banana' => array('previousVersion' => null, 'newVersion' => '2.0'),
            'hurling' => array('previousVersion' => null),
        ), $results);
    }

    function testHasNameConflict() {
        $method = $this->getStaticMethod('hasNameConflict');

        $results = $method(null);
        $this->assertFalse($results);

        $results = $method('standard/input/FormInput');
        $this->assertFalse($results);

        $results = $method('custom/random/FormInput');
        $this->assertTrue($results);

        $results = $method('blah');
        $this->assertFalse($results);

        $results = $method('custom/sample/CrazySample');
        $this->assertFalse($results);

        FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . 'widgets/custom/delete/FormInput/1.0/info.yml',
            yaml_emit(array(
                'version' => '1.0',
                'requires' => array('framework' => array('3.0')))));
        Registry::getAllWidgets(true);

        $results = $method('standard/input/FormInput');
        $this->assertTrue($results);

        // inactive widget
        FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . 'widgets/custom/delete/WidgetTestA/1.0/info.yml',
            yaml_emit(array(
                'version' => '1.0',
                'requires' => array('framework' => array('3.0')))));
        Registry::getAllWidgets(true);

        $results = $method('custom/delete/WidgetTestA');
        $this->assertFalse($results);

        // active widget
        FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . 'widgets/custom/delete/WidgetTestA/1.0/info.yml',
            yaml_emit(array(
                'version' => '1.0',
                'requires' => array('framework' => array('3.0')))));
        $widgetVersions = Widgets::getDeclaredWidgetVersions(CUSTOMER_FILES);
        $widgetVersions['custom/delete/WidgetTestA'] = '1.0';
        Widgets::updateDeclaredWidgetVersions($widgetVersions);

        $results = $method('custom/delete/WidgetTestA');
        $this->assertFalse($results);

        // inactive widgets with overlapping names
        FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . 'widgets/custom/delete/WidgetTestA/1.0/info.yml',
            yaml_emit(array(
                'version' => '1.0',
                'requires' => array('framework' => array('3.0')))));
        FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . 'widgets/custom/delete2/WidgetTestA/1.0/info.yml',
            yaml_emit(array(
                'version' => '1.0',
                'requires' => array('framework' => array('3.0')))));
        Registry::getAllWidgets(true);

        $results = $method('custom/delete/WidgetTestA');
        $this->assertTrue($results);

        // active widgets with overlapping names
        $widgetVersions = Widgets::getDeclaredWidgetVersions(CUSTOMER_FILES);
        $widgetVersions['custom/delete/WidgetTestA'] = '1.0';
        $widgetVersions['custom/delete2/WidgetTestA'] = '1.0';
        Widgets::updateDeclaredWidgetVersions($widgetVersions);

        Registry::getAllWidgets(true);

        $results = $method('custom/delete/WidgetTestA');
        $this->assertTrue($results);

        $widgetVersions = Widgets::getDeclaredWidgetVersions(CUSTOMER_FILES);
        unset($widgetVersions['custom/delete/WidgetTestA']);
        unset($widgetVersions['custom/delete2/WidgetTestA']);
        Widgets::updateDeclaredWidgetVersions($widgetVersions);

        FileSystem::removeDirectoryRecursivelyOrThrowExceptionOnFailure(CUSTOMER_FILES . 'widgets/custom/delete', true);
        FileSystem::removeDirectoryRecursivelyOrThrowExceptionOnFailure(CUSTOMER_FILES . 'widgets/custom/delete2', true);
        Registry::getAllWidgets(true);
    }

    function testDeleteWidget() {
        $method = $this->getStaticMethod('deleteWidget');

        $results = $method(null);
        $this->assertSame(array('success' => false), $results);

        $results = $method('standard/input/FormInput');
        $this->assertSame(array('success' => false), $results);

        $results = $method('blah');
        $this->assertSame(array('success' => false), $results);

        $results = $method('custom/sample/CrazySample');
        $this->assertSame(array('success' => true, 'change' => array('custom/sample/CrazySample' => array('previousVersion' => NULL)), 'files' => array()), $results);

        // inactive widget with no CSS
        FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . 'widgets/custom/delete/WidgetTestA/1.0/info.yml',
            yaml_emit(array(
                'version' => '1.0',
                'requires' => array('framework' => array('3.0')))));

        $results = $method('custom/delete/WidgetTestA');
        $this->assertSame(array('success' => true, 'change' => array('custom/delete/WidgetTestA' => array('previousVersion' => NULL)),
                'files' => array(0 => '/customer/development/widgets/custom/delete/WidgetTestA/*')), $results);
        $widgetVersions = Widgets::getDeclaredWidgetVersions(CUSTOMER_FILES);
        $this->assertFalse(isset($widgetVersions['custom/delete/WidgetTestA']));
        $this->assertFalse(FileSystem::isReadableFile(CUSTOMER_FILES . "widgets/custom/delete/WidgetTestA/1.0/info.yml"));

        // active widget with CSS
        FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . 'widgets/custom/delete/WidgetTestA/1.0/info.yml',
            yaml_emit(array(
                'version' => '1.0',
                'requires' => array('framework' => array('3.0')))));
        FileSystem::filePutContentsOrThrowExceptionOnFailure(HTMLROOT . '/euf/assets/themes/standard/widgetCss/WidgetTestA.css', '/* */');
        $widgetVersions = Widgets::getDeclaredWidgetVersions(CUSTOMER_FILES);
        $widgetVersions['custom/delete/WidgetTestA'] = '1.0';
        Widgets::updateDeclaredWidgetVersions($widgetVersions);

        $results = $method('custom/delete/WidgetTestA');
        $this->assertSame(array('success' => true, 'change' => array('custom/delete/WidgetTestA' => array('previousVersion' => '1.0')),
                'files' => array(0 => '/customer/development/widgets/custom/delete/WidgetTestA/*', 1 => '/customer/assets/themes/standard/widgetCss/WidgetTestA.css')), $results);
        $widgetVersions = Widgets::getDeclaredWidgetVersions(CUSTOMER_FILES);
        $this->assertFalse(isset($widgetVersions['custom/delete/WidgetTestA']));
        $this->assertFalse(FileSystem::isReadableFile(CUSTOMER_FILES . 'widgets/custom/delete/WidgetTestA/1.0/info.yml'));
        $this->assertFalse(FileSystem::isReadableFile(HTMLROOT . '/euf/assets/themes/standard/widgetCss/WidgetTestA.css'));

        // inactive widgets with overlapping CSS
        FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . 'widgets/custom/delete/WidgetTestA/1.0/info.yml',
            yaml_emit(array(
                'version' => '1.0',
                'requires' => array('framework' => array('3.0')))));
        FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . 'widgets/custom/delete2/WidgetTestA/1.0/info.yml',
            yaml_emit(array(
                'version' => '1.0',
                'requires' => array('framework' => array('3.0')))));
        FileSystem::filePutContentsOrThrowExceptionOnFailure(HTMLROOT . '/euf/assets/themes/standard/widgetCss/WidgetTestA.css', '/* */');
        Registry::getAllWidgets(true);

        $results = $method('custom/delete/WidgetTestA');
        $this->assertSame(array('success' => true, 'change' => array('custom/delete/WidgetTestA' => array('previousVersion' => NULL)),
                'files' => array(0 => '/customer/development/widgets/custom/delete/WidgetTestA/*')), $results);
        $widgetVersions = Widgets::getDeclaredWidgetVersions(CUSTOMER_FILES);
        $this->assertFalse(isset($widgetVersions['custom/delete/WidgetTestA']));
        $this->assertFalse(FileSystem::isReadableFile(CUSTOMER_FILES . 'widgets/custom/delete/WidgetTestA/1.0/info.yml'));
        $this->assertTrue(FileSystem::isReadableFile(HTMLROOT . '/euf/assets/themes/standard/widgetCss/WidgetTestA.css'));

        $widgetVersions = Widgets::getDeclaredWidgetVersions(CUSTOMER_FILES);
        unset($widgetVersions['custom/delete/WidgetTestA']);
        Widgets::updateDeclaredWidgetVersions($widgetVersions);

        FileSystem::removeDirectoryRecursivelyOrThrowExceptionOnFailure(CUSTOMER_FILES . 'widgets/custom/delete', true);
        FileSystem::removeDirectoryRecursivelyOrThrowExceptionOnFailure(CUSTOMER_FILES . 'widgets/custom/delete2', true);
        @unlink(HTMLROOT . '/euf/assets/themes/standard/widgetCss/WidgetTestA.css');
        Registry::getAllWidgets(true);
        // restore versionAuditLog to be empty
        file_put_contents(CUSTOMER_FILES . 'versionAuditLog', '');
    }

    function testGetAvailableWidgetsToUpdate() {
        $method = $this->getStaticMethod('getAvailableWidgetsToUpdate');
        $results = $method();
        $this->assertIsA($results, 'array');
        $this->assertSame(0, count($results));

        $results = $method('5.0', array(
            'widgetVersions' => array(
                'foo/bar/baz' => array(
                    '3.1' => array('requires' => array('framework' => array('5.0')))
                ),
            ),
        ), array(
            'foo/bar/baz' => '3.1',
        ));
        $this->assertSame(array(), $results);

        $results = $method('5.0', array(
            'widgetVersions' => array(
                'foo/bar/baz' => array(
                    '4.5' => array('requires' => array('framework' => array('5.2'))),
                    '3.3' => array('requires' => array('framework' => array('5.1'))),
                    '3.0' => array('requires' => array('framework' => array('5.0'))),
                    '2.8' => array('requires' => array('framework' => array('5.0'))),
                    '3.1' => array('requires' => array('framework' => array('5.0'))),
                    '2.7' => array('requires' => array('framework' => array('5.0'))),
                ),
            ),
        ), array(
            'foo/bar/baz' => '2.7'
        ));
        $this->assertSame(array(
            'foo/bar/baz' => '3.1',
        ), $results);

        // test downgrading from framework 5.2 to 5.0
        $results = $method('5.0', array(
            'widgetVersions' => array(
                'foo/bar/baz' => array(
                    '4.5' => array('requires' => array('framework' => array('5.2'))),
                    '3.3' => array('requires' => array('framework' => array('5.1'))),
                    '3.0' => array('requires' => array('framework' => array('5.0'))),
                    '2.8' => array('requires' => array('framework' => array('5.0'))),
                    '3.1' => array('requires' => array('framework' => array('5.0'))),
                    '2.7' => array('requires' => array('framework' => array('5.0'))),
                ),
            ),
        ), array(
            'foo/bar/baz' => '4.5'
        ));
        $this->assertSame(array(
            'foo/bar/baz' => '3.1',
        ), $results);

        $results = $method('5.0', array(
            'widgetVersions' => array(
                'foo/bar/baz' => array(
                    '4.5.1' => array('requires' => array('framework' => array('5.2'))),
                    '3.3.1' => array('requires' => array('framework' => array('5.1'))),
                    '3.0.1' => array('requires' => array('framework' => array('5.0'))),
                    '2.8.3' => array('requires' => array('framework' => array('5.0'))),
                    '3.1.20' => array('requires' => array('framework' => array('5.0'))),
                    '3.1.2' => array('requires' => array('framework' => array('5.0'))),
                    '2.7.1' => array('requires' => array('framework' => array('5.0'))),
                ),
                'banana/doll/day' => array(
                    '1.0.1' => array('requires' => array('framework' => array('5.0'))),
                    '2.0.1' => array('requires' => array('framework' => array('5.1'))),
                ),
            ),
        ), array(
            'foo/bar/baz' => '2.7',
            'banana/doll/day' => '1.0',
            'banana/pure/day' => '4.5',
        ));
        $this->assertSame(array(
            'foo/bar/baz' => '3.1',
        ), $results);

        FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/multiver/WidgetTestA/1.0/info.yml",
            yaml_emit(array(
                'version' => '1.0',
                'requires' => array('framework' => array("3.0"), 'jsModule' => array('standard', 'mobile')))));
        FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/multiver/WidgetTestA/2.0/info.yml",
            yaml_emit(array(
                'version' => '2.0',
                'requires' => array('framework' => array("3.0"), 'jsModule' => array('standard', 'mobile')))));
        FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/multiver/WidgetTestA/3.0/info.yml",
            yaml_emit(array(
                'version' => '3.0',
                'requires' => array('framework' => array("3.0"), 'jsModule' => array('standard', 'mobile')))));
        FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/multiver/WidgetTestA/4.0/info.yml",
            yaml_emit(array(
                'version' => '4.0',
                'requires' => array('framework' => array("4.0"), 'jsModule' => array('standard', 'mobile')))));
        FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/multiver/WidgetTestB/1.0/info.yml",
            yaml_emit(array(
                'version' => '1.0',
                'requires' => array('jsModule' => array('standard', 'mobile')))));
        FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/multiver/WidgetTestB/2.0/info.yml",
            yaml_emit(array(
                'version' => '2.0',
                'requires' => array('jsModule' => array('standard', 'mobile')))));
        FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/multiver/WidgetTestB/3.0/info.yml",
            yaml_emit(array(
                'version' => '3.0',
                'requires' => array('jsModule' => array('standard', 'mobile')))));
        $widgetVersions = Widgets::getDeclaredWidgetVersions(CUSTOMER_FILES);
        $widgetVersions['custom/multiver/WidgetTestA'] = '1.0';
        $widgetVersions['custom/multiver/WidgetTestB'] = '1.0';
        Widgets::updateDeclaredWidgetVersions($widgetVersions);
        Widgets::killCacheVariables();
        Registry::setTargetPages('development');

        $results = $method('3.0', array(
            'widgetVersions' => array(),
        ), array(
            'custom/multiver/WidgetTestA' => '1.0',
            'custom/multiver/WidgetTestB' => '1.0',
        ));
        $this->assertSame(array(
            'custom/multiver/WidgetTestA' => '3.0',
            'custom/multiver/WidgetTestB' => '3.0',
        ), $results);

        $results = $method('3.0', array(
            'widgetVersions' => array(),
        ), array(
            'custom/multiver/WidgetTestA' => '2.0',
            'custom/multiver/WidgetTestB' => '3.0',
        ));
        $this->assertSame(array(
            'custom/multiver/WidgetTestA' => '3.0',
            'custom/multiver/WidgetTestB' => '3.0',
        ), $results);

        $results = $method('4.0', array(
            'widgetVersions' => array(),
        ), array(
            'custom/multiver/WidgetTestA' => '1.0',
            'custom/multiver/WidgetTestB' => '1.0',
        ));
        $this->assertSame(array(
            'custom/multiver/WidgetTestA' => '4.0',
            'custom/multiver/WidgetTestB' => '3.0',
        ), $results);

        //@@@ QA 130502-000068 Test upgrading a non-existent widgets. Widgets which are not on disk will not be upgraded.
        $results = $method('3.11', array('widgetVersions' => array()), array(
            'custom/test/iDontExist' => '1.0',
            'custom/sample/SampleWidget' => '0.9'
        ));
        $this->assertSame(array(
            'custom/sample/SampleWidget' => '1.0'
        ), $results);

        FileSystem::removeDirectoryRecursivelyOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/multiver", true);
        $widgetVersions = Widgets::getDeclaredWidgetVersions(CUSTOMER_FILES);
        unset($widgetVersions['custom/multiver/WidgetTestA']);
        unset($widgetVersions['custom/multiver/WidgetTestB']);
        Widgets::updateDeclaredWidgetVersions($widgetVersions);
        Widgets::killCacheVariables();
        Registry::setTargetPages('development');
    }

    function testGetWidgetVersionDirectory() {
        $getWidgetVersionDirectory = $this->getStaticMethod('getWidgetVersionDirectory');
        $widget = 'standard/reports/Grid';
        $path = Registry::getWidgetPathInfo($widget)->absolutePath;

        // set fake version
        $allWidgets = Widgets::getDeclaredWidgetVersions();
        $realVersions = $allWidgets;
        $fakeVersion = '99.8';
        $allWidgets[$widget] = $fakeVersion;
        $this->assertTrue(Widgets::updateDeclaredWidgetVersions($allWidgets));

        // fake versions to be inserted into the history file
        $versions = array(
            "1.0.0" => array('requires' => array('framework' => array("3.0"))),
            "3.0.2" => array('requires' => array('framework' => array("3.0"))),
            "$fakeVersion.0" => array('requires' => array('framework' => array("3.0"))),
            "$fakeVersion.1" => array('requires' => array('framework' => array("3.0"))),
            "$fakeVersion.4" => array('requires' => array('framework' => array("3.0"))),
            "$fakeVersion.9" => array('requires' => array('framework' => array("3.0"))), // highest nano, should be chosen
            "9.9.9" => array('requires' => array('framework' => array("3.0"))),
            "12.9.9" => array('requires' => array('framework' => array("3.0"))),
        );

        // save original cpHistory
        $getVersionInvoker = function($methodName) {
            return TestHelper::getStaticMethodInvoker('\RightNow\Internal\Utils\Version', $methodName);
        };
        $getVersionHistory = $getVersionInvoker('getVersionHistory');
        $this->assertIsA($realCPHistory = $getVersionHistory(false, false), 'array');
        $this->assertTrue(count($realCPHistory) > 0);

        //Merge in the the newly added directories and write out the updated file
        $this->assertIsA($updatedVersionHistory = $getVersionHistory(false, false), 'array');
        $this->assertTrue(count($updatedVersionHistory) > 0);
        $newWidgetHistory = array_merge($updatedVersionHistory['widgetVersions'][$widget], $versions);
        uksort($newWidgetHistory, "\RightNow\Internal\Utils\Version::compareVersionNumbers");
        $updatedVersionHistory['widgetVersions'][$widget] = $newWidgetHistory;
        $writeVersionHistory = $getVersionInvoker('writeVersionHistory');
        $this->assertTrue($writeVersionHistory($updatedVersionHistory));

        $this->killWidgetCache();
        Widgets::killCacheVariables();
        Version::clearCacheVariables();

        $result = $getWidgetVersionDirectory($widget);
        $this->assertSame("$fakeVersion.9", $result);
        $result = $getWidgetVersionDirectory('something/banana/bar');
        $this->assertSame('', $result);
        $result = $getWidgetVersionDirectory("/$widget");
        $this->assertSame('', $result);

        //Clean up after we test
        $this->killWidgetCache();
        Widgets::killCacheVariables();

        // @@@ QA 130424-000099 - with an older nano specified
        $widget = 'standard/input/FormInput';
        $allWidgets[$widget] = "1.0.1";
        $this->assertTrue(Widgets::updateDeclaredWidgetVersions($allWidgets));
        $result = $getWidgetVersionDirectory($widget);
        $this->assertSame("1.0.1", $result);

        $this->killWidgetCache();
        Widgets::killCacheVariables();

        $allWidgets[$widget] = "current";
        $this->assertTrue(Widgets::updateDeclaredWidgetVersions($allWidgets));
        $result = $getWidgetVersionDirectory($widget);
        $this->assertSame('', $result);

        //Clean up after we test
        $this->killWidgetCache();
        Widgets::killCacheVariables();

        // test 'current'
        $widget = 'standard/reports/Multiline';
        $fakeVersion = 'current';
        $allwidgets[$widget] = $fakeVersion;
        $this->assertTrue(Widgets::updateDeclaredWidgetVersions($allWidgets));

        //Kill before we test
        $this->killWidgetCache();
        Widgets::killCacheVariables();

        $result = $getWidgetVersionDirectory($widget);
        $this->assertSame('', $result);

        // cleanup: reset real versions
        $this->assertTrue(Widgets::updateDeclaredWidgetVersions($realVersions));
        $this->assertTrue($writeVersionHistory($realCPHistory));
        $this->killWidgetCache();
        Version::clearCacheVariables();
        Widgets::killCacheVariables();

    }

    function testVerifyWidgetPath() {
        $verifyWidgetPath = $this->getStaticMethod('verifyWidgetPath');
        try {
            $verifyWidgetPath('');
            $this->fail('Exception wasn\'t hit');
        }
        catch (\Exception $e) {
            $this->pass();
        }
        try {
            $verifyWidgetPath('path');
            $this->fail('Exception wasn\'t hit');
        }
        catch (\Exception $e) {
            $this->pass();
        }
        try {
            $verifyWidgetPath('path/path1');
            $this->pass();
        }
        catch (\Exception $e) {
            $this->fail('Exception wasn\'t hit');
        }
        try {
            $verifyWidgetPath('path/path1/');
            $this->fail('Exception wasn\'t hit');
        }
        catch (\Exception $e) {
            $this->pass();
        }
    }

    function testGetCurrentWidgetVersion() {
        // @@@ QA 130424-000099 - with a nano specified
        $widget = 'custom/input/MyFormInput';
        $allWidgets = Widgets::getDeclaredWidgetVersions();
        $realVersions = $allWidgets;
        $fakeVersion = '1.0.1';
        $allWidgets[$widget] = $fakeVersion;
        $this->assertTrue(Widgets::updateDeclaredWidgetVersions($allWidgets));

        $getCurrentWidgetVersion = $this->getStaticMethod('getCurrentWidgetVersion');
        $this->assertNull($getCurrentWidgetVersion(''));
        $this->assertNull($getCurrentWidgetVersion('/standard/reports/Grid'));
        $this->assertNull($getCurrentWidgetVersion('/custom/reports/Grid'));
        $this->assertNull($getCurrentWidgetVersion('custom/reports/Grid'));
        $this->assertTrue(is_string($getCurrentWidgetVersion('standard/reports/Grid')));

        $this->assertSame('1.0', $getCurrentWidgetVersion($widget));
        $this->assertSame('1.0.1', $getCurrentWidgetVersion($widget, false));

        // cleanup
        Widgets::updateDeclaredWidgetVersions($realVersions);
        Version::clearCacheVariables();
        Widgets::killCacheVariables();
    }

    // @@@ QA 130424-000099 - with a nano specified
    function testGetWidgetNanoVersion() {
        $getWidgetNanoVersion = $this->getStaticMethod('getWidgetNanoVersion');
        $widget = 'standard/input/TextInput';

        // set fake version
        $allWidgets = Widgets::getDeclaredWidgetVersions();
        $realVersions = $allWidgets;
        $fakeVersion = '99.8';
        $allWidgets[$widget] = $fakeVersion;
        $this->assertTrue(Widgets::updateDeclaredWidgetVersions($allWidgets));

        // fake versions to be inserted into the history file
        $versions = array(
            "1.0.0" => "3.0",
            "3.0.2" => "3.0",
            "$fakeVersion.0" => "3.0",
            "$fakeVersion.1" => "3.0",
            "$fakeVersion.4" => "3.0",
            "$fakeVersion.9" => "3.0",
            "9.9.9" => "3.0",
            "12.9.9" => "3.0",
        );

        // save original cpHistory
        $getVersionInvoker = function($methodName) {
            return TestHelper::getStaticMethodInvoker('\RightNow\Internal\Utils\Version', $methodName);
        };
        $getVersionHistory = $getVersionInvoker('getVersionHistory');
        $this->assertIsA($realCPHistory = $getVersionHistory(false, false), 'array');
        $this->assertTrue(count($realCPHistory) > 0);

        //Merge in the the newly added directories and write out the updated file
        $this->assertIsA($updatedVersionHistory = $getVersionHistory(false, false), 'array');
        $this->assertTrue(count($updatedVersionHistory) > 0);
        $newWidgetHistory = array_merge($updatedVersionHistory['widgetVersions'][$widget], $versions);
        uksort($newWidgetHistory, "\RightNow\Internal\Utils\Version::compareVersionNumbers");
        $updatedVersionHistory['widgetVersions'][$widget] = $newWidgetHistory;
        $writeVersionHistory = $getVersionInvoker('writeVersionHistory');
        $this->assertTrue($writeVersionHistory($updatedVersionHistory));

        $this->killWidgetCache();
        Widgets::killCacheVariables();
        Version::clearCacheVariables();

        // make sure the highest nano is selected when only major-minor are in widgetVersions
        $this->assertSame("$fakeVersion.9", $getWidgetNanoVersion($widget, $fakeVersion));
        $this->killWidgetCache();
        Widgets::killCacheVariables();

        $fakeVersion = '99.8';
        $allWidgets[$widget] = "$fakeVersion.1";
        $this->assertTrue(Widgets::updateDeclaredWidgetVersions($allWidgets));
        // make sure the correct nano is returned when major-minor-nano are in widgetVersions
        $this->assertSame("$fakeVersion.1", $getWidgetNanoVersion($widget, $fakeVersion));

        // cleanup: reset real versions
        $this->assertTrue(Widgets::updateDeclaredWidgetVersions($realVersions));
        $this->assertTrue($writeVersionHistory($realCPHistory));
        $this->killWidgetCache();
        Version::clearCacheVariables();
        Widgets::killCacheVariables();
    }

    function testGetWidgetController() {
        $getWidgetController = $this->getStaticMethod('getWidgetController');
        $this->assertSame('<div><b>Widget Error:  - Controller path is not valid</b></div>', $getWidgetController(''));
        $this->assertSame('\RightNow\Widgets\Grid', $getWidgetController('reports/Grid'));
        $this->assertSame('\RightNow\Widgets\Grid', $getWidgetController('/standard/reports/Grid'));
        $this->assertSame('<div><b>Widget Error: /custom/reports/Grid - Controller path is not valid</b></div>', $getWidgetController('/custom/reports/Grid'));
        $this->assertSame('<div><b>Widget Error: custom/reports/Grid - Controller path is not valid</b></div>', $getWidgetController('custom/reports/Grid'));
        $this->assertSame('\RightNow\Widgets\Grid', $getWidgetController('standard/reports/Grid'));
    }

    function testGetWidgetInfo() {
        $getWidgetInfo = $this->getStaticMethod('getWidgetInfo');
        $arrayKeys = array('controller_path', 'view_path', 'js_path', 'base_css', 'js_templates', 'template_path', 'version', 'requires', 'attributes', 'info', 'absolutePath', 'relativePath');
        $this->assertIdentical($arrayKeys, array_keys($getWidgetInfo(Registry::getWidgetPathInfo('standard/reports/Grid'))));
        $this->assertIdentical($arrayKeys, array_keys($getWidgetInfo(Registry::getWidgetPathInfo('reports/Grid'))));

        $arrayKeys = array('controller_path', 'view_path', 'js_path', 'js_templates', 'template_path', 'version', 'requires', 'attributes', 'extends', 'info', 'absolutePath', 'relativePath', 'extends_info');
        $this->assertIdentical($arrayKeys, array_keys($getWidgetInfo(Registry::getWidgetPathInfo('standard/reports/MobileMultiline'))));
        $this->assertIdentical($arrayKeys, array_keys($getWidgetInfo(Registry::getWidgetPathInfo('reports/MobileMultiline'))));
    }

    function testGetWidgetFiles() {
        $getWidgetFiles = $this->getStaticMethod('getWidgetFiles');
        $arrayKeys = array('controller_path', 'view_path', 'js_path', 'base_css', 'js_templates', 'template_path');

        $widget = Registry::getWidgetPathInfo('standard/reports/Grid');
        $this->assertSame($arrayKeys, array_keys($getWidgetFiles($widget->absolutePath, $widget->relativePath)));

        $arrayKeys = array('controller_path', 'view_path', 'js_path', 'js_templates', 'template_path');
        $widget = Registry::getWidgetPathInfo('standard/reports/MobileMultiline');

        $this->assertSame($arrayKeys, array_keys($getWidgetFiles($widget->absolutePath, $widget->relativePath)));

        $widget = Registry::getWidgetPathInfo('standard/feedback/AnswerFeedback');
        $result = $getWidgetFiles($widget->absolutePath, $widget->relativePath);
        $this->assertTrue(count($result['view_partials']) > 0);
        foreach ($result['view_partials'] as $file) {
            $this->assertEndsWith($file, '.html.php');
        }

        $widget = Registry::getWidgetPathInfo('standard/reports/Paginator');
        // Missing relativePath
        try {
            $getWidgetFiles($widget->absolutePath, null);
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
        }

        // Invalid absolutePath
        try {
            $getWidgetFiles('some/invalid/path', $widget->relativePath);
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
        }
    }

    function testGetCssFiles() {
        $getCssFiles = $this->getStaticMethod('getCssFiles');
        $widget = Registry::getWidgetPathInfo('standard/input/TextInput');

        $expected = array(
            // Standard exists as a scss file.
            'base_css'         => array('standard/input/TextInput/base.css'),
        );
        $this->assertSame($expected, $getCssFiles($widget->absolutePath, $widget->relativePath, $widget->className));
    }

    function testIsViewAndLogicOverridden() {
        $method = $this->getStaticMethod('isViewAndLogicOverridden');

        $this->assertFalse($method(array()));
        $meta = array('extends' => array());
        $this->assertFalse($method($meta));

        $meta = array('extends' => array('overrideViewAndLogic' => 'false'));
        $this->assertFalse($method($meta));

        $meta = array('extends' => array('overrideViewAndLogic' => 'true'));
        $this->assertTrue($method($meta));

        $meta = array('extends' => array('overrideViewAndLogic' => true));
        $this->assertTrue($method($meta));
    }

    function testValidateWidgetExtends() {
        $validate = $this->getStaticMethod('validateWidgetExtends');
        $convert = $this->getStaticMethod('convertAttributeTagsToValues');
        $widget = Registry::getWidgetPathInfo('standard/search/MobileProductCategoryList');
        $meta = $convert(Widgets::getWidgetInfo($widget), array('validate' => true, 'eval' => true));
        $controllerWidget = Registry::getWidgetPathInfo($meta['controller_path']);
        $controllerClass = $controllerWidget->namespacedClassName;
        \RightNow\Utils\Framework::installPathRestrictions();
        Widgets::requireWidgetControllerWithPathInfo($widget);
        $widgetInstance = new $controllerClass($meta['attributes']);
        $errors = $validate($meta['extends'], $widget, $widgetInstance, $controllerClass);
        $this->assertEqual(array(), $errors);

        $errors = $validate($meta['extends'], $widget, 'someInvalidWidgetInstance', $controllerClass);
        $this->assertEqual(1, count($errors));
        $this->assertTrue($errors[0][1]); // $severe

        $jsPath = $widget->absolutePath . '/logic.js';
        $viewPath = $widget->absolutePath . '/view.php';
        $controllerPath = $widget->absolutePath . '/controller.php';
        $hasPutFile = array();

        if(!FileSystem::isReadableFile($jsPath)) {
            $hasPutFile[] = 'jsPath';
            file_put_contents($jsPath, '!');
        }
        if(!FileSystem::isReadableFile($controllerPath)) {
            $hasPutFile[] = 'controllerPath';
            file_put_contents($controllerPath, '!');
        }
        if(!FileSystem::isReadableFile($viewPath)) {
            $hasPutFile[] = 'viewPath';
            file_put_contents($viewPath, '!');
        }

        //no view component
        $meta['extends']['components'] = array('php' => true, 'js' => true);
        $meta['extends']['overrideViewAndLogic'] = false;
        $errors = $validate($meta['extends'], $widget, $widgetInstance, $controllerClass);
        $this->assertSame($errors[0][0], 'Widget standard/search/MobileProductCategoryList has a view file, but does not include it in the components attribute in the info.yml file, so it will not be used.');
        $this->assertSame($errors[0][1], false);

        //no js component
        $meta['extends']['components'] = array('php' => true, 'view' => true);
        $meta['extends']['overrideViewAndLogic'] = false;
        $errors = $validate($meta['extends'], $widget, $widgetInstance, $controllerClass);
        $this->assertSame($errors[0][0], 'Widget standard/search/MobileProductCategoryList has a logic file, but does not include it in the components attribute in the info.yml file, so it will not be used.');
        $this->assertSame($errors[0][1], false);

        //no php component
        $meta['extends']['components'] = array('js' => true, 'view' => true);
        $meta['extends']['overrideViewAndLogic'] = false;
        $errors = $validate($meta['extends'], $widget, $widgetInstance, $controllerClass);
        $this->assertSame($errors[0][0], 'Widget standard/search/MobileProductCategoryList has a controller file, but does not include it in the components attribute in the info.yml file, so it will not be used.');
        $this->assertSame($errors[0][1], false);

        foreach($hasPutFile as $path) {
            unlink(${$path});
        }
    }

    function testGetEmptyControllerCode() {
        $invoke = function($widgetPath, $loadController = true) {
            return Widgets::getEmptyControllerCode(Registry::getWidgetPathInfo($widgetPath), $loadController);
        };

        $this->assertSame("namespace RightNow\Widgets;\nclass Grid extends \RightNow\Libraries\Widget\Base { }", $invoke('standard/reports/Grid'));
        $this->assertSame("namespace RightNow\Widgets;\nclass Grid extends \RightNow\Libraries\Widget\Base { }", $invoke('reports/Grid'));

        $expected = "namespace RightNow\Widgets;\nclass MobileMultiline extends \RightNow\Widgets\Multiline { }";
        $this->assertSame($expected, $invoke('standard/reports/MobileMultiline'));

        $this->assertSame($expected, $invoke('reports/MobileMultiline'));
        $this->assertSame($expected, $invoke('reports/MobileMultiline', false));
    }

    function testGetWidgetToExtendFrom() {
        $this->assertTrue(array('controller' => array(), 'view' => array(), 'logic' => array()) == array('controller' => array(), 'view' => array(), 'logic' => array()));
        $getExtends = $this->getStaticMethod('getWidgetToExtendFrom');
        // no extends
        $return = $getExtends(array());
        $this->assertFalse($return);
        $return = $getExtends(array('extends' => ''));
        $this->assertFalse($return);
        $return = $getExtends(array('extends' => null));
        $this->assertFalse($return);

        // non-existent files
        $return = $getExtends(array('extends' => array('widget' => 'standard/input/Banana', 'components' => array('js' => true, 'php' => true, 'view' => true))));
        $this->assertFalse($return);


        $return = $getExtends(array('extends' => array('widget' => 'standard/input/SelectionInput', 'components' => array('js' => true, 'php' => true, 'view' => true))));
        $this->assertTrue(is_array($return));
        $this->assertSame(1, count($return['controller']));
        $this->assertSame(1, count($return['logic']));
        $this->assertSame(1, count($return['view']));

        $return = $getExtends(array('extends' => array('widget' => 'standard/reports/MobileMultiline', 'components' => array('js' => true, 'php' => true, 'view' => true))));
        $this->assertTrue(is_array($return));
        $this->assertSame(1, count($return['controller']));
        $this->assertSame(1, count($return['logic']));
        $this->assertSame(1, count($return['view']));

        $return = $getExtends(array('extends' => array('widget' => 'standard/reports/Multiline', 'components' => array('php' => true, 'view' => true))));
        $this->assertTrue(is_array($return));
        $this->assertSame(1, count($return['controller']));
        $this->assertSame(1, count($return['logic']));
        $this->assertSame(1, count($return['js_templates']));
        $this->assertSame(array('view'), array_keys($return['js_templates'][0]));
        $this->assertSame(1, count($return['view']));

        // overrideViewAndLogic
        $return = $getExtends(array(
            'relativePath' => 'standard/search/MobileProductCategoryList',
            'extends' => array('overrideViewAndLogic' => true, 'widget' => 'standard/search/ProductCategoryList', 'components' => array('php' => true)))
        );
        $this->assertTrue(is_array($return));
        $this->assertIdentical(array('standard/search/ProductCategoryList'), $return['controller']);
        $this->assertSame(0, count($return['logic'])); // MobileProductCategoryList has no logic file currently
        $this->assertIdentical(array(), $return['view']); // MobileProductCategoryList overrides view and logic and shouldn't extend any views

        // create fake widgets to show multiple extensions, overriding view and logic at various points
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/test/WidgetA/1.0/info.yml",
            yaml_emit(array(
                'version' => '1.0',
                'requires' => array(
                    'jsModule' => array('standard', 'mobile'),
                ),
            ))
        );
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/test/WidgetA/1.0/view.php", "");
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/test/WidgetA/1.0/view.ejs", "WidgetA");
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/test/WidgetA/1.0/controller.php", "");
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/test/WidgetA/1.0/logic.js", "");

        $widgetB = array(
            'version' => '1.0',
            'requires' => array(
                'jsModule' => array('standard', 'mobile'),
            ),
            'extends' => array(
                'widget' => 'custom/test/WidgetA',
                'components' => array('view'),
            ),
        );

        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/test/WidgetB/1.0/info.yml",
            yaml_emit($widgetB)
        );
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/test/WidgetB/1.0/view.php", "");
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/test/WidgetB/1.0/view.ejs", "");

        $widgetC = array(
            'version' => '1.0',
            'requires' => array(
                'jsModule' => array('standard', 'mobile'),
            ),
            'extends' => array(
                'widget' => 'custom/test/WidgetB',
                'components' => array(),
                'overrideViewAndLogic' => 'true',
            ),
        );

        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/test/WidgetC/1.0/info.yml",
            yaml_emit($widgetC)
        );
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/test/WidgetC/1.0/logic.js", "");
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/test/WidgetC/1.0/view.php", "");
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/test/WidgetC/1.0/view.ejs", "WidgetC");
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/test/WidgetC/1.0/item.ejs", "WidgetC");

        $widgetD = array(
            'version' => '1.0',
            'requires' => array(
                'jsModule' => array('standard', 'mobile'),
            ),
            'extends' => array(
                'widget' => 'custom/test/WidgetC',
                'components' => array('view'),
            ),
        );

        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/test/WidgetD/1.0/info.yml",
            yaml_emit($widgetD)
        );
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/test/WidgetD/1.0/view.php", "");
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/test/WidgetD/1.0/logic.js", "");
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/test/WidgetD/1.0/view.ejs", "WidgetD");
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/test/WidgetD/1.0/new.ejs", "WidgetD");

        // activate fake widgets
        $allWidgets = Widgets::getDeclaredWidgetVersions(CUSTOMER_FILES);
        $allWidgets['custom/test/WidgetA'] = '1.0';
        $allWidgets['custom/test/WidgetB'] = '1.0';
        $allWidgets['custom/test/WidgetC'] = '1.0';
        $allWidgets['custom/test/WidgetD'] = '1.0';
        Widgets::updateDeclaredWidgetVersions($allWidgets);
        \RightNow\Internal\Utils\Version::clearCacheVariables();
        Widgets::killCacheVariables();
        Registry::setTargetPages('development');

        // make sure 'extend_info' was created correctly for each widget
        $widgetB['relativePath'] = 'custom/test/WidgetB';
        $return = $getExtends($widgetB);

        // WidgetB extends WidgetA. Since WidgetA has controller, view, ejs and logic on disk
        // they should be reflected in WidgetB's extension information.
        $widgetAExtension = array('custom/test/WidgetA');
        $this->assertIdentical($widgetAExtension, $return['controller']);
        $this->assertIdentical($widgetAExtension, $return['view']);
        $this->assertIdentical($widgetAExtension, $return['logic']);
        $this->assertIdentical(end($widgetAExtension), $return['parent']);
        $this->assertIdentical(1, count($return['js_templates']));
        $this->assertIdentical("WidgetA", $return['js_templates'][0]['view']);

        // WidgetC extends WidgetB and overrides view and logic, which should be reflected in the extension information.
        $widgetC['relativePath'] = 'custom/test/WidgetC';
        $return = $getExtends($widgetC);
        $this->assertIdentical($widgetAExtension, $return['controller']); // WidgetC uses WidgetA controller
        $this->assertIdentical('custom/test/WidgetB', $return['parent']); // WidgetB is WidgetC's parent
        // no other extensions should be mentioned, since we are overriding view and logic
        $this->assertIdentical(array(), $return['view']);
        $this->assertIdentical(array(), $return['logic']);
        $this->assertIdentical(0, count($return['js_templates']));

        // WidgetD extends WidgetC. WidgetC also adds a new EJS file.
        $widgetD['relativePath'] = 'custom/test/WidgetD';
        $return = $getExtends($widgetD);
        $widgetCExtension = array('custom/test/WidgetC');
        $this->assertIdentical($widgetAExtension, $return['controller']); // WidgetD uses WidgetA controller
        $this->assertIdentical(end($widgetCExtension), $return['parent']); // WidgetC is WidgetD's parent
        $this->assertIdentical($widgetCExtension, $return['view']); // Should only use view from WidgetC
        $this->assertIdentical($widgetCExtension, $return['logic']); // Should only use logic from WidgetC

        // should contain both EJS files from WidgetC
        $this->assertIdentical(1, count($return['js_templates']));
        $this->assertIdentical('WidgetC', $return['js_templates'][0]['item']);
        $this->assertIdentical('WidgetC', $return['js_templates'][0]['view']);

        // remove fake widgets
        \RightNow\Utils\FileSystem::removeDirectoryRecursivelyOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/test", true);
        unset($allWidgets['custom/test/WidgetA']);
        unset($allWidgets['custom/test/WidgetB']);
        unset($allWidgets['custom/test/WidgetC']);
        unset($allWidgets['custom/test/WidgetD']);
        Widgets::updateDeclaredWidgetVersions($allWidgets);
        \RightNow\Internal\Utils\Version::clearCacheVariables();
        Widgets::killCacheVariables();
     }

    function testGetWidgetRelativePath() {
        $getWidgetRelativePath = $this->getStaticMethod('getWidgetRelativePath');
        $this->assertNull($getWidgetRelativePath(0));
        $this->assertNull($getWidgetRelativePath(null));
        $this->assertNull($getWidgetRelativePath(''));

        $core = dirname(CPCORE);
        $standardPath = "{$core}/widgets";
        $widget = 'standard/input/TextInput';
        $this->assertEqual($widget, $getWidgetRelativePath($widget));
        $this->assertEqual($widget, $getWidgetRelativePath("{$widget}/1.0"));
        $this->assertEqual($widget, $getWidgetRelativePath("{$standardPath}/{$widget}"));
        $this->assertEqual($widget, $getWidgetRelativePath("{$standardPath}//{$widget}"));
        $this->assertEqual($widget, $getWidgetRelativePath("{$standardPath}/{$widget}/1.0"));
        $this->assertEqual($widget, $getWidgetRelativePath("{$standardPath}/{$widget}//1.0"));

        $customPath = CUSTOMER_FILES . 'widgets';
        $widget = 'custom/input/TextInput';
        $this->assertEqual($widget, $getWidgetRelativePath($widget));
        $this->assertEqual($widget, $getWidgetRelativePath("{$widget}/1.0"));
        $this->assertEqual($widget, $getWidgetRelativePath("{$customPath}/{$widget}"));
        $this->assertEqual($widget, $getWidgetRelativePath("{$customPath}/{$widget}/1.0"));
    }

    function testGetWidgetClassName() {
        $getWidgetClassName = $this->getStaticMethod('getWidgetClassName');
        $this->assertSame('Foo', $getWidgetClassName('standard/foo/Foo'));
        $this->assertSame('Foo', $getWidgetClassName('standard/foo/Foo/1.0.1'));
        $this->assertSame('Foo', $getWidgetClassName('/standard/foo/Foo/1.0.1'));
        $this->assertSame('Foo', $getWidgetClassName('/standard/foo/Foo'));
        $this->assertSame('Foo', $getWidgetClassName('custom/Foo'));
        try {
            $getWidgetClassName('');
            $this->fail('Exception wasn\'t hit');
        }
        catch (\Exception $e) {
            $this->pass();
        }
        try {
            $getWidgetClassName('/foo/bar/baz/');
            $this->fail('Exception wasn\'t hit');
        }
        catch (\Exception $e) {
            $this->pass();
        }
        try {
            $getWidgetClassName('/foo/bar/baz/1.0.1/');
            $this->fail('Exception wasn\'t hit');
        }
        catch (\Exception $e) {
            $this->pass();
        }
    }

    function testGetWidgetNamespacedClassName() {
        $getWidgetNamespacedClassName = $this->getStaticMethod('getWidgetNamespacedClassName');
        $this->assertSame('\RightNow\Widgets\FormInput', $getWidgetNamespacedClassName('standard/input/FormInput'));
        $this->assertSame('\RightNow\Widgets\FormInput', $getWidgetNamespacedClassName('standard/input/FormInput/1.0.1'));
        $this->assertSame('\Custom\Widgets\sample\SampleWidget', $getWidgetNamespacedClassName('custom/sample/SampleWidget'));
        $this->assertSame('\Custom\Widgets\sample\SampleWidget', $getWidgetNamespacedClassName('custom/sample/SampleWidget/1.0.1'));
        // @@@  QA 130404-000056
        $this->assertSame('\Custom\Widgets\custom\TextInput', $getWidgetNamespacedClassName('custom/custom/TextInput/1.0'));
        try {
            $getWidgetNamespacedClassName('');
            $this->fail('Exception wasn\'t hit');
        }
        catch (\Exception $e) {
            $this->pass();
        }
        try {
            $getWidgetNamespacedClassName('/foo/bar/baz/');
            $this->fail('Exception wasn\'t hit');
        }
        catch (\Exception $e) {
            $this->pass();
        }
        try {
            $getWidgetNamespacedClassName('/foo/bar/baz/1.0.1/');
            $this->fail('Exception wasn\'t hit');
        }
        catch (\Exception $e) {
            $this->pass();
        }
    }

    // @@@  QA 130404-000056
    function testGetWidgetJSClassName() {
        $this->assertEqual('RightNow.Widgets.FormInput', Widgets::getWidgetJSClassName('standard/input/FormInput'));
        $this->assertEqual('RightNow.Widgets.FormInput', Widgets::getWidgetJSClassName('standard/input/FormInput/1.0.1'));
        $this->assertEqual('Custom.Widgets.sample.SampleWidget', Widgets::getWidgetJSClassName('custom/sample/SampleWidget'));
        $this->assertEqual('Custom.Widgets.sample.SampleWidget', Widgets::getWidgetJSClassName('custom/sample/SampleWidget/1.0'));
        $this->assertEqual('Custom.Widgets.custom.TextInput', Widgets::getWidgetJSClassName('custom/custom/TextInput'));
        $this->assertEqual('Custom.Widgets.custom.TextInput', Widgets::getWidgetJSClassName('custom/custom/TextInput/1.0'));
    }

    function testGetWidgetNamespace() {
        $getWidgetNamespace = $this->getStaticMethod('getWidgetNamespace');
        $this->assertSame('\RightNow\Widgets', $getWidgetNamespace('standard/input/FormInput'));
        $this->assertSame('\RightNow\Widgets', $getWidgetNamespace('standard/input/FormInput/1.0.1'));
        $this->assertSame('\Custom\Widgets\sample', $getWidgetNamespace('custom/sample/SampleWidget'));
        $this->assertSame('\Custom\Widgets\sample', $getWidgetNamespace('custom/sample/SampleWidget/1.0.1'));
        try {
            $getWidgetNamespace('');
            $this->fail('Exception wasn\'t hit');
        }
        catch (\Exception $e) {
            $this->pass();
        }
        try {
            $getWidgetNamespace('/foo/bar/baz/');
            $this->fail('Exception wasn\'t hit');
        }
        catch (\Exception $e) {
            $this->pass();
        }
        try {
            $getWidgetNamespace('/foo/bar/baz/1.0.1/');
            $this->fail('Exception wasn\'t hit');
        }
        catch (\Exception $e) {
            $this->pass();
        }
    }

    function testGetWidgetThemes() {
        $getThemes = $this->getStaticMethod('getThemes');
        $this->assertIdentical(array(), $getThemes('standard/utils/Blank'));
        $this->assertIdentical(array('mobile', 'standard'), $getThemes('custom/sample/SampleWidget'));
        $this->assertIdentical(array(), $getThemes('standard/login/LoginDialog'));
    }

    function testAddInheritedAttributes() {
        $stackOfWidgetAttributesTestVar = array(
            array(
                'table' => 'Arnold Movies',
                'movieCount' => '34'
            ),
            array(
                'table' => 'Arnold Movies',
                'name' => 'Predator Quotes',
                'sub:input_Incident.CustomFields.c.dttm1:quote' => 'Get To Da Choppah!',
                'sub:input_Incident.CustomFields.c.dttm1:table' => 'Mr. Universe Movies',
                'sub:input_incident.customfields.c.dttm1:crazy_casing' => 'Danny Devito',
            )
        );
        $widgetAttributesTestVar = array(
            'crazy_casing' => 'Twins',
            'table' => 'Arnold Movies',
            'name' => 'Predator',
            'sub_id' => 'input_Incident.CustomFields.c.dttm1',
            'regex' => '\w{1,2}\d{1,2}$',
            'rn_container_id' => 'morePredatorQuotes',
        );

        list($class, $addInheritedAttributes, $widgetAttributeStack, $rnContainers) = $this->reflect('method:addInheritedAttributes', 'widgetAttributeStack', 'rnContainers');

        $widgetAttributeStack->setValue(null);
        $this->assertIdentical(array('sheen'), $addInheritedAttributes->invoke($class, array('sheen')));

        $widgetAttributeStack->setValue($stackOfWidgetAttributesTestVar);

        // Insure specified attribute is passed to child widget
        $widgetAttributes = $addInheritedAttributes->invoke($class, $widgetAttributesTestVar);
        $this->assertIdentical($widgetAttributes['quote'], 'Get To Da Choppah!');

        // Insure specified attribute is passed to child widget even if the attribute name is lower case in the stack
        $this->assertIdentical($widgetAttributes['crazy_casing'], 'Danny Devito');

        // Normal attributes will not override any pre-set value
        $this->assertIdentical($widgetAttributes['name'], 'Predator');

        // Specifying an attribute with 'sub:' will override any pre-set value
        $this->assertIdentical($widgetAttributes['table'], 'Mr. Universe Movies');

        // When 'rn_containers' is not present, do NOT attach attributes to the widget
        $this->assertNull($widgetAttributes['anotherQuote']);

        $widgetAttributeStack->setValue($stackOfWidgetAttributesTestVar);
        $rnContainers->setValue(array(
            'morePredatorQuotes' => array(
                'anotherQuote' => "There's something in those trees...",
            )
        ));
        $widgetAttributes = $addInheritedAttributes->invoke($class, $widgetAttributes);
        $this->assertIdentical($widgetAttributes['anotherQuote'], "There's something in those trees...");
    }

    function testParseManifestRNField() {
        $parseManifestRNField = $this->getStaticMethod('parseManifestRNField');

        //Non-RN Fields
        $this->assertSame('this is a string', $parseManifestRNField('this is a string', false));
        $this->assertSame(12, $parseManifestRNField(12, false));
        $this->assertSame(array(1, 2, 3), $parseManifestRNField(array(1, 2, 3), false));
        $this->assertSame(array('a' => 'A', 'b' => 'B'), $parseManifestRNField(array('a' => 'A', 'b' => 'B'), false));
        $this->assertTrue($parseManifestRNField(true, false));

        //Different types of RN fields
        $this->assertTrue(is_integer($parseManifestRNField('rn:cfg:CP_FILE_UPLOAD_MAX_TIME', false)));
        $this->assertTrue(is_integer($parseManifestRNField('rn:cfg:API_MAX_GET_SIZE:RNW_COMMON', false)));
        $this->assertTrue(is_string($parseManifestRNField('rn:msg:HIDE_HINT_CMD', false)));
        $this->assertSame('IMTHEJUGGERNAUT', $parseManifestRNField('rn:astr:IMTHEJUGGERNAUT', false));
        $this->assertSame('valhello', $parseManifestRNField("rn:php:'val' . 'hello'", false));
        $this->assertSame('eval : this : stuff' , $parseManifestRNField("rn:php:'eval : this : stuff'", false));

        //Try failure cases
        try{
            $parseManifestRNField('rn:hello:whoa', false);
        }
        catch(Exception $e){
            $this->assertTrue(is_string($e->getMessage()));
        }

        //Return literal code
        $this->assertSame('\'this is a string\'', $parseManifestRNField('this is a string', true));
        $this->assertSame(12, $parseManifestRNField(12, true));
        $this->assertSame(var_export(array(1,2,3), true), $parseManifestRNField(array(1, 2, 3), true));
        $this->assertSame(var_export(array('a' => 'A', 'b' => 'B'), true), $parseManifestRNField(array('a' => 'A', 'b' => 'B'), true));
        $this->assertSame("true", $parseManifestRNField(true, true));

        $this->assertPattern('@\\\RightNow\\\Utils\\\Config::getConfig\(\d+\)@', $parseManifestRNField('rn:cfg:CP_FILE_UPLOAD_MAX_TIME', true));
        $this->assertPattern("@\\\RightNow\\\Utils\\\Config::getConfig\(\d+\)@", $parseManifestRNField('rn:cfg:API_MAX_GET_SIZE:RNW_COMMON', true));
        $this->assertPattern("@getMessage\(\d+\)@", $parseManifestRNField('rn:msg:HIDE_HINT_CMD', true));
        $this->assertSame('\'IMTHEJUGGERNAUT\'', $parseManifestRNField('rn:astr:IMTHEJUGGERNAUT', true));
        $this->assertSame("'val' . 'hello'", $parseManifestRNField("rn:php:'val' . 'hello'", true));
        $this->assertSame("'eval : this : stuff'", $parseManifestRNField("rn:php:'eval : this : stuff'", true));

        // return with define name vs value (for cp-versions)
        $this->assertSame("\\RightNow\\Utils\\Config::getConfig(CP_FILE_UPLOAD_MAX_TIME)", $parseManifestRNField('rn:cfg:CP_FILE_UPLOAD_MAX_TIME', true, false));
        $this->assertSame("\\RightNow\\Utils\\Config::getMessage(HIDE_HINT_CMD)", $parseManifestRNField('rn:msg:HIDE_HINT_CMD', true, false));
        $this->assertSame("\\RightNow\\Utils\\Config::getMessageJS(PLS_ENTER_VALID_EMAIL_ADDR_MSG)", $parseManifestRNField('rn:msgjs:PLS_ENTER_VALID_EMAIL_ADDR_MSG', true, false));

        try{
            $parseManifestRNField('rn:hello:whoa', true);
        }
        catch(Exception $e){
            $this->assertTrue(is_string($e->getMessage()));
        }
    }

    function testVerifyWidgetReferencesStandard() {
        $versionHistory = $oldVersionHistory = Version::getVersionHistory(false, false);
        Registry::setTargetPages('development');

        $relativePath = 'standard/input/TextInput';
        $versionHistory['widgetVersions'][$relativePath]['1.1.1'] = array('extends' => array('widget' => 'standard/input/FormInput', 'versions' => array('2.0')));
        $versionHistory['widgetVersions'][$relativePath]['1.2.1'] = array('contains' => array(array('widget' => 'standard/input/FormInput', 'versions' => array('2.0'))));
        $this->assertTrue(Version::writeVersionHistory($versionHistory));
        Version::clearCacheVariables();

        foreach(array_keys(Version::getVersionFile(APPPATH.'widgetVersions')) + array_keys(Registry::getCustomWidgets()) as $widgetPath) {
            if ($widgetPath !== $relativePath) {
                $widget = Registry::getWidgetPathInfo($widgetPath);
                $this->assertNull(Widgets::verifyWidgetReferences($widget));
            }
        }

        $expected = 'Widget \'standard/input/TextInput\' declares it supports versions: 2.0 of the widget it extends, \'standard/input/FormInput\', which is on version \'\'. Please either adjust the declared supported versions to contain \'\', or upgrade the \'standard/input/TextInput\' widget on the <a target=\'_blank\' href=\'/ci/admin/versions/manage#widget=standard%2Finput%2FFormInput\'>Widget Management</a> page.';
        $this->assertEqual($expected, Widgets::verifyWidgetReferences(Registry::getWidgetPathInfo($relativePath, '1.1')));
        $expected = "Contained widget standard/input/FormInput does not have the correct declared version. The contained widget's version is ''. It must be one of the following: 2.0. Please upgrade the contained widget on the <a target='_blank' href='/ci/admin/versions/manage#widget=standard%2Finput%2FFormInput'>Widget Management</a> page.";
        $this->assertEqual($expected, Widgets::verifyWidgetReferences(Registry::getWidgetPathInfo($relativePath, '1.2')));

        Version::writeVersionHistory($oldVersionHistory);
        Version::clearCacheVariables();
        Registry::setTargetPages('development');
    }

    function testVerifyWidgetReferencesForFrameworkVersions() {
        $versionHistory = $oldVersionHistory = Version::getVersionHistory(false, false);
        Registry::setTargetPages('development');

        $relativePath = 'standard/input/TextInput';
        $extendsPath = 'standard/input/FormInput';
        $versionHistory['widgetVersions'][$relativePath]['9.1.0'] = array('requires' => array('framework' => array('1.0', '2.0', '4.0')));
        $versionHistory['widgetVersions'][$relativePath]['9.2.0'] = array('requires' => array('framework' => array(CP_FRAMEWORK_VERSION)), 'extends' => array('widget' => $extendsPath, 'versions' => array('9.1')));
        $versionHistory['widgetVersions'][$extendsPath]['9.1.1'] = array('requires' => array('framework' => array ('1.0', '2.0', '4.0')));

        $this->assertTrue(Version::writeVersionHistory($versionHistory));
        Version::clearCacheVariables();

        $this->assertIdentical('The widget standard/input/TextInput is not supported on the current framework version: ' . CP_FRAMEWORK_VERSION . '. It is valid for the following framework versions: 1.0, 2.0, 4.0', Widgets::verifyWidgetReferences(Registry::getWidgetPathInfo($relativePath, '9.1')));

        $this->assertTrue(Text::stringContains(Widgets::verifyWidgetReferences(Registry::getWidgetPathInfo($relativePath, '9.2')),
            "Widget 'standard/input/TextInput' declares it supports versions: 9.1 of the widget it extends, 'standard/input/FormInput', which is on version ''. Please either adjust the declared supported versions to contain '', or upgrade the 'standard/input/TextInput' widget"));

        Version::writeVersionHistory($oldVersionHistory);
        Version::clearCacheVariables();
        Registry::setTargetPages('development');
    }

    function testVerifyWidgetReferencesCustom() {
        FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/widgetrefs/WidgetTestA/1.0/info.yml",
            yaml_emit(array(
                'extends' => array('widget' => 'standard/input/FormInput', 'versions' => array('2.0'), 'components' => array('php')),
                'version' => '1.0',
                'requires' => array('framework' => array(CP_FRAMEWORK_VERSION), 'jsModule' => array('standard', 'mobile')))));
        FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/widgetrefs/WidgetTestB/1.0/info.yml",
            yaml_emit(array(
                'contains' => array(array('widget' => 'standard/input/FormInput', 'versions' => array('2.0'))),
                'version' => '1.0',
                'requires' => array('framework' => array(CP_FRAMEWORK_VERSION), 'jsModule' => array('standard', 'mobile')))));
        $widgetVersions = Widgets::getDeclaredWidgetVersions(CUSTOMER_FILES);
        $widgetVersions['custom/widgetrefs/WidgetTestA'] = '1.0';
        $widgetVersions['custom/widgetrefs/WidgetTestB'] = '1.0';
        Widgets::updateDeclaredWidgetVersions($widgetVersions);
        Widgets::killCacheVariables();
        Registry::setTargetPages('development');

        $widget = Registry::getWidgetPathInfo('custom/widgetrefs/WidgetTestA');
        $this->assertEqual("Widget 'custom/widgetrefs/WidgetTestA' declares it supports versions: 2.0 of the widget it extends, 'standard/input/FormInput', which is on version ''. Please either adjust the declared supported versions to contain '', or upgrade the 'custom/widgetrefs/WidgetTestA' widget on the <a target='_blank' href='/ci/admin/versions/manage#widget=standard%2Finput%2FFormInput'>Widget Management</a> page.", Widgets::verifyWidgetReferences($widget));

        $widget = Registry::getWidgetPathInfo('custom/widgetrefs/WidgetTestB');
        $this->assertEqual("Contained widget standard/input/FormInput does not have the correct declared version. The contained widget's version is ''. It must be one of the following: 2.0. Please upgrade the contained widget on the <a target='_blank' href='/ci/admin/versions/manage#widget=standard%2Finput%2FFormInput'>Widget Management</a> page.", Widgets::verifyWidgetReferences($widget));

        FileSystem::removeDirectoryRecursivelyOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/widgetrefs", true);
        $widgetVersions = Widgets::getDeclaredWidgetVersions(CUSTOMER_FILES);
        unset($widgetVersions['custom/widgetrefs/WidgetTestA']);
        unset($widgetVersions['custom/widgetrefs/WidgetTestB']);
        Widgets::updateDeclaredWidgetVersions($widgetVersions);
        Widgets::killCacheVariables();
        Registry::setTargetPages('development');
    }

    /* @@@ QA 130107-000203 - validate all ancestors for extended widgets */
    function testVerifyWidgetReferencesCustomMultipleExtends() {
        FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/widgetrefs/WidgetTestA/1.0/info.yml",
            yaml_emit(array(
                'version' => '1.0',
                // it should be a while before we support 13.1, but if we do this should get updated
                'requires' => array('framework' => array("13.1"), 'jsModule' => array('standard', 'mobile')))));
        FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/widgetrefs/WidgetTestB/1.0/info.yml",
            yaml_emit(array(
                'extends' => array('widget' => 'custom/widgetrefs/WidgetTestA', 'versions' => array('1.0'), 'components' => array('view')),
                'version' => '1.0',
                'requires' => array('framework' => array(CP_FRAMEWORK_VERSION), 'jsModule' => array('standard', 'mobile')))));
        FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/widgetrefs/WidgetTestC/1.0/info.yml",
            yaml_emit(array(
                'extends' => array('widget' => 'custom/widgetrefs/WidgetTestB', 'versions' => array('1.0'), 'components' => array('view')),
                'version' => '1.0',
                'requires' => array('framework' => array(CP_FRAMEWORK_VERSION), 'jsModule' => array('standard', 'mobile')))));
        $widgetVersions = Widgets::getDeclaredWidgetVersions(CUSTOMER_FILES);
        $widgetVersions['custom/widgetrefs/WidgetTestA'] = '1.0';
        $widgetVersions['custom/widgetrefs/WidgetTestB'] = '1.0';
        $widgetVersions['custom/widgetrefs/WidgetTestC'] = '1.0';
        Widgets::updateDeclaredWidgetVersions($widgetVersions);
        Widgets::killCacheVariables();
        Registry::setTargetPages('development');

        $widget = Registry::getWidgetPathInfo('custom/widgetrefs/WidgetTestA');
        $this->assertEqual('The widget custom/widgetrefs/WidgetTestA is not supported on the current framework version: ' . CP_FRAMEWORK_VERSION . '. It is valid for the following framework versions: 13.1', Widgets::verifyWidgetReferences($widget));

        $widget = Registry::getWidgetPathInfo('custom/widgetrefs/WidgetTestB');
        $this->assertEqual('The widget custom/widgetrefs/WidgetTestB extends from custom/widgetrefs/WidgetTestA which is not supported on the current framework version (' . CP_FRAMEWORK_VERSION . '). custom/widgetrefs/WidgetTestA is supported on the following framework versions: 13.1', Widgets::verifyWidgetReferences($widget));

        $widget = Registry::getWidgetPathInfo('custom/widgetrefs/WidgetTestC');
        $this->assertEqual('The widget custom/widgetrefs/WidgetTestC extends from custom/widgetrefs/WidgetTestA which is not supported on the current framework version (' . CP_FRAMEWORK_VERSION . '). custom/widgetrefs/WidgetTestA is supported on the following framework versions: 13.1', Widgets::verifyWidgetReferences($widget));

        FileSystem::removeDirectoryRecursivelyOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/widgetrefs", true);
        $widgetVersions = Widgets::getDeclaredWidgetVersions(CUSTOMER_FILES);
        unset($widgetVersions['custom/widgetrefs/WidgetTestA']);
        unset($widgetVersions['custom/widgetrefs/WidgetTestB']);
        unset($widgetVersions['custom/widgetrefs/WidgetTestC']);
        Widgets::updateDeclaredWidgetVersions($widgetVersions);
        Widgets::killCacheVariables();
        Registry::setTargetPages('development');
    }

    /* @@@ QA 130308-000107 - validate all ancestors for extended widgets even if there is no framework required on current widget */
    function testVerifyWidgetReferencesCustomMultipleExtendsNoFramework() {
        FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/widgetrefs/WidgetTestA/1.0/info.yml",
            yaml_emit(array(
                'version' => '1.0',
                // it should be a while before we support 13.1, but if we do this should get updated
                'requires' => array('framework' => array("13.1"), 'jsModule' => array('standard', 'mobile')))));
        FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/widgetrefs/WidgetTestB/1.0/info.yml",
            yaml_emit(array(
                'extends' => array('widget' => 'custom/widgetrefs/WidgetTestA', 'versions' => array('1.0'), 'components' => array('view')),
                'version' => '1.0',
                'requires' => array('jsModule' => array('standard', 'mobile')))));
        FileSystem::filePutContentsOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/widgetrefs/WidgetTestC/1.0/info.yml",
            yaml_emit(array(
                'extends' => array('widget' => 'custom/widgetrefs/WidgetTestB', 'versions' => array('1.0'), 'components' => array('view')),
                'version' => '1.0',
                'requires' => array('jsModule' => array('standard', 'mobile')))));
        $widgetVersions = Widgets::getDeclaredWidgetVersions(CUSTOMER_FILES);
        $widgetVersions['custom/widgetrefs/WidgetTestA'] = '1.0';
        $widgetVersions['custom/widgetrefs/WidgetTestB'] = '1.0';
        $widgetVersions['custom/widgetrefs/WidgetTestC'] = '1.0';
        Widgets::updateDeclaredWidgetVersions($widgetVersions);
        Widgets::killCacheVariables();
        Registry::setTargetPages('development');

        $widget = Registry::getWidgetPathInfo('custom/widgetrefs/WidgetTestA');
        $this->assertEqual('The widget custom/widgetrefs/WidgetTestA is not supported on the current framework version: ' . CP_FRAMEWORK_VERSION . '. It is valid for the following framework versions: 13.1', Widgets::verifyWidgetReferences($widget));

        $widget = Registry::getWidgetPathInfo('custom/widgetrefs/WidgetTestB');
        $this->assertEqual('The widget custom/widgetrefs/WidgetTestB extends from custom/widgetrefs/WidgetTestA which is not supported on the current framework version (' . CP_FRAMEWORK_VERSION . '). custom/widgetrefs/WidgetTestA is supported on the following framework versions: 13.1', Widgets::verifyWidgetReferences($widget));

        $widget = Registry::getWidgetPathInfo('custom/widgetrefs/WidgetTestC');
        $this->assertEqual('The widget custom/widgetrefs/WidgetTestC extends from custom/widgetrefs/WidgetTestA which is not supported on the current framework version (' . CP_FRAMEWORK_VERSION . '). custom/widgetrefs/WidgetTestA is supported on the following framework versions: 13.1', Widgets::verifyWidgetReferences($widget));

        FileSystem::removeDirectoryRecursivelyOrThrowExceptionOnFailure(CUSTOMER_FILES . "widgets/custom/widgetrefs", true);
        $widgetVersions = Widgets::getDeclaredWidgetVersions(CUSTOMER_FILES);
        unset($widgetVersions['custom/widgetrefs/WidgetTestA']);
        unset($widgetVersions['custom/widgetrefs/WidgetTestB']);
        unset($widgetVersions['custom/widgetrefs/WidgetTestC']);
        Widgets::updateDeclaredWidgetVersions($widgetVersions);
        Widgets::killCacheVariables();
        Registry::setTargetPages('development');
    }

    function testGetManifestData() {
        $getManifestData = $this->getStaticMethod('getManifestData');

        // Doesn't exist
        $result = $getManifestData(CORE_WIDGET_FILES . 'blah/banana', true);
        $this->assertIsA($result, 'string');
        $this->assertTrue(Text::stringContains($result, 'info.yml'));
        $this->assertTrue(Text::stringContains($result, 'blah/banana'));
        $this->assertTrue(Text::stringContains($result, '/ci/admin/versions/removeMissingActiveWidgets'));

        // Exists
        $result = $getManifestData(CORE_WIDGET_FILES . 'standard/search/AdvancedSearchDialog/', true);
        $this->assertIsA($result, 'array');
        $this->assertTrue(array_key_exists('version', $result));

        // Exists, tabs are converted to spaces
        $dir = get_cfg_var('upload_tmp_dir') . '/unitTest/' . get_class($this);
        $file = "{$dir}/info.yml";

        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure($file, "requires:\n\tframework:\n\t\t- '3.0'\n");
        $result = $getManifestData($dir, true);
        $this->assertIsA($result, 'array');
        $this->assertIdentical(array('3.0'), $result['requires']['framework']);

        // Exists, invalid yaml
        \RightNow\Utils\FileSystem::filePutContentsOrThrowExceptionOnFailure($file, "banana: foo: bar");
        $result = $getManifestData($dir, true);
        $this->assertIsA($result, 'string');
        $this->assertTrue(Text::stringContains($result, 'parse'));
        $this->assertTrue(Text::stringContains($result, 'invalid syntax'));
        $this->assertFalse(Text::stringContains($result, $dir)); // Changed to 'assertFalse' because 'getManifestData()' in now returning relative path after the 'scripts/' directory and no longer returns the absolute widget path. Whereas $dir is pointing to a 'tmp' directory so nothing will be returned.

        \RightNow\Utils\FileSystem::removeDirectory($dir, true);
    }

    function testBuildManifest() {
        $buildManifest = $this->getStaticMethod('buildManifest');

        $data = array(
            'attributes' => array(
                'a_string' => new \RightNow\Libraries\Widget\Attribute(array('name' => 'a_string', 'type' => 'STRING', 'description' => 'a string', 'default' => 'one')),
                'a_bool' => new \RightNow\Libraries\Widget\Attribute(array('name' => 'a_boolean', 'type' => 'BOOLEAN', 'description' => 'a boolean', 'default' => 1)),
            ),
            'info' => array(
                'description' => 'The description of a widget.',
                'urlParameters' => array(
                    'kw' => array('name' => 'keyword', 'description' => 'a keyword', 'example' => 'kw/search'),
                ),
            ),
            'extends' => array('widget' => 'foo/bar/ParentWidget', 'components' => array('php','js')),
            'contains' => array(
                array('widget' => 'foo/bar/ChildWidget1', 'versions' => array('1.0')),
                array('widget' => 'foo/bar/ChildWidget2'),
            ),
            'requires' => array('yui' => array('slider')),
        );

        $expected = array(
          'version' => '1.0',
          'requires' => array(
            'jsModule' => array('standard', 'mobile'),
            'yui'      => array('slider'),
          ),
          'attributes' => array(
            'a_string' => array(
              'name' => 'a_string',
              'value' => 'one',
              'type' => 'STRING',
              'default' => 'one',
              'tooltip' => 'a string',
              'description' => 'a string',
              'options' => array(),
              'min' => NULL,
              'max' => NULL,
              'length' => NULL,
              'optlistId' => NULL,
              'required' => false,
              'inherited' => false,
              'displaySpecialCharsInTagGallery' => false,
            ),
            'a_bool' => array(
              'name' => 'a_boolean',
              'value' => 1,
              'type' => 'BOOLEAN',
              'default' => 1,
              'tooltip' => 'a boolean',
              'description' => 'a boolean',
              'options' => array(),
              'min' => NULL,
              'max' => NULL,
              'length' => NULL,
              'optlistId' => NULL,
              'required' => false,
              'inherited' => false,
              'displaySpecialCharsInTagGallery' => false,
            ),
          ),
          'info' => array(
            'description' => 'The description of a widget.',
            'urlParameters' => array(
              'kw' => array(
                'name' => 'keyword',
                'description' => 'a keyword',
                'example' => 'kw/search',
              ),
            ),
          ),
          'extends' => array(
            'widget' => 'foo/bar/ParentWidget',
            'components' => array('php', 'js'),
          ),
          'contains' => array(
            array('widget' => 'foo/bar/ChildWidget1', 'versions' => array('1.0')),
            array('widget' => 'foo/bar/ChildWidget2'),
          ),
        );

        // output an array
        $this->assertIdentical($expected, $buildManifest($data));

        // output YAML
        $this->assertIdentical($expected, yaml_parse($buildManifest($data, true)));

        // accept attributes as an array of arrays, instead of an array of objects.
        $data = array(
            'attributes' => array(
                'a_string' => array('name' => 'a_string', 'type' => 'STRING', 'description' => 'a string', 'value' => 'one', 'default' => 'one'),
                'a_boolean' => array('name' => 'a_boolean', 'type' => 'boolean', 'description' => 'a boolean', 'value' => 1, 'default' => 1),
            ),
        );
        $expected = array(
          'version' => '1.0',
          'requires' => array(
            'jsModule' => array('standard', 'mobile'),
          ),
          'attributes' => array(
            'a_string' => array(
              'name' => 'a_string',
              'type' => 'STRING',
              'description' => 'a string',
              'value' => 'one',
              'default' => 'one',
            ),
            'a_boolean' => array(
              'name' => 'a_boolean',
              'type' => 'boolean',
              'description' => 'a boolean',
              'value' => 1,
              'default' => 1,
            ),
          ),
        );
        $this->assertIdentical($expected, $buildManifest($data));

        // specify versions
        $data = array(
            'version' => '2.1',
            'frameworkVersion' => '4.1',
        );
        $expected = array(
          'version' => '2.1',
          'requires' => array(
            'jsModule' => array('standard', 'mobile'),
            'framework' => '4.1',
          ),
        );
        $this->assertIdentical($expected, $buildManifest($data));

        // jsModule requirements are applied
        $expected = array('version' => '1.0', 'requires' => array(
            'jsModule' => array('mobile'),
        ));
        $this->assertIdentical($expected, $buildManifest(array('requires' => array('jsModule' => array('mobile')))));

        // yui requirements are applied
        $expected = array('version' => '1.0', 'requires' => array(
            'jsModule' => array('standard', 'mobile'),
            'yui' => array('dial'),
        ));
        $this->assertIdentical($expected, $buildManifest(array('requires' => array('yui' => array('dial')))));
    }

    function testBuildDependencies() {
        // invalid relationship
        try {
            $dependencies = array();
            Widgets::buildDependencies('standard/input/DateInput', '1.0', 'invalidRelationship', array(), $dependencies);
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
        }

        $dependencies = array();
        // extends
        Widgets::buildDependencies(
            'standard/input/DateInput',
            '1.0',
            'extends',
            array(array('widget' => 'standard/input/FormInput', 'versions' => array('1.0'))),
            $dependencies
        );
        $expected = array (
            'standard/input/DateInput' => array(
                '1.0' => array(
                    'extends' => array(
                        'standard/input/FormInput' => array('1.0'),
                    ),
                    'contains' => array(),
                    'children' => array(),
                ),
            ),
            'standard/input/FormInput' => array(
                '1.0' => array(
                    'extends' => array(),
                    'contains' => array(),
                    'children' => array(
                        'standard/input/DateInput' => array('1.0'),
                    ),
                ),
            ),
        );
        $this->assertIdentical($expected, $dependencies);
        // verify that adding the same thing does not change the result
        Widgets::buildDependencies(
            'standard/input/DateInput',
            '1.0',
            'extends',
            array(array('widget' => 'standard/input/FormInput', 'versions' => array('1.0'))),
            $dependencies
        );
        $this->assertIdentical($expected, $dependencies);

        // contains for child widget
        Widgets::buildDependencies(
            'standard/input/DateInput',
            '1.0',
            'contains',
            array(array('widget' => 'standard/output/FieldDisplay', 'versions' => array('1.0'))),
            $dependencies
        );
        $expected = array (
            'standard/input/DateInput' => array(
                '1.0' => array(
                    'extends' => array(
                        'standard/input/FormInput' => array('1.0'),
                    ),
                    'contains' => array(
                        'standard/output/FieldDisplay' => array('1.0'),
                    ),
                    'children' => array(),
                ),
            ),
            'standard/input/FormInput' => array(
                '1.0' => array(
                    'extends' => array(),
                    'contains' => array(),
                    'children' => array(
                        'standard/input/DateInput' => array('1.0'),
                    ),
                ),
            ),
        );
        $this->assertIdentical($expected, $dependencies);

        // contains for parent widget
        Widgets::buildDependencies(
            'standard/input/FormInput',
            '1.0',
            'contains',
            array(
                array('widget' => 'standard/input/SelectionInput', 'versions' => array('1.0')),
                array('widget' => 'standard/input/DateInput', 'versions' => array('1.0')),
                array('widget' => 'standard/input/TextInput', 'versions' => array('1.0')),
            ),
            $dependencies
        );
        $expected = array (
            'standard/input/DateInput' => array(
                '1.0' => array(
                    'extends' => array(
                        'standard/input/FormInput' => array('1.0'),
                    ),
                    'contains' => array(
                        'standard/output/FieldDisplay' => array('1.0'),
                    ),
                    'children' => array(),
                ),
            ),
            'standard/input/FormInput' => array(
                '1.0' => array(
                    'extends' => array(),
                    'contains' => array(
                        'standard/input/SelectionInput' => array('1.0'),
                        'standard/input/DateInput' => array('1.0'),
                        'standard/input/TextInput' => array('1.0'),
                    ),
                    'children' => array(
                        'standard/input/DateInput' => array('1.0'),
                    ),
                ),
            ),
        );
        $this->assertIdentical($expected, $dependencies);

        // additional extends versions
        Widgets::buildDependencies(
            'standard/input/DateInput',
            '1.0',
            'extends',
            array(array('widget' => 'standard/input/FormInput', 'versions' => array('1.1', '1.2'))),
            $dependencies
        );
        $expected = array (
            'standard/input/DateInput' => array(
                '1.0' => array(
                    'extends' => array(
                        'standard/input/FormInput' => array('1.0', '1.1', '1.2'),
                    ),
                    'contains' => array(
                        'standard/output/FieldDisplay' => array('1.0'),
                    ),
                    'children' => array(),
                ),
            ),
            'standard/input/FormInput' => array(
                '1.0' => array(
                    'extends' => array(),
                    'contains' => array(
                        'standard/input/SelectionInput' => array('1.0'),
                        'standard/input/DateInput' => array('1.0'),
                        'standard/input/TextInput' => array('1.0'),
                    ),
                    'children' => array(
                        'standard/input/DateInput' => array('1.0'),
                    ),
                ),
                '1.1' => array(
                    'extends' => array(),
                    'contains' => array(),
                    'children' => array(
                        'standard/input/DateInput' => array('1.0'),
                    ),
                ),
                '1.2' => array(
                    'extends' => array(),
                    'contains' => array(),
                    'children' => array(
                        'standard/input/DateInput' => array('1.0'),
                    ),
                ),
            ),
        );
        $this->assertIdentical($expected, $dependencies);

        //@@@ QA 121128-000122 handle widget relationships when versions are not specified
        // additional extends versions with no versions specified
        Widgets::buildDependencies(
            'standard/input/PasswordInput',
            '1.3',
            'extends',
            array(array('widget' => 'standard/input/FormInput')),
            $dependencies
        );
        $expected = array (
            'standard/input/DateInput' => array(
                '1.0' => array(
                    'extends' => array(
                        'standard/input/FormInput' => array('1.0', '1.1', '1.2'),
                    ),
                    'contains' => array(
                        'standard/output/FieldDisplay' => array('1.0'),
                    ),
                    'children' => array(),
                ),
            ),
            'standard/input/FormInput' => array(
                '1.0' => array(
                    'extends' => array(),
                    'contains' => array(
                        'standard/input/SelectionInput' => array('1.0'),
                        'standard/input/DateInput' => array('1.0'),
                        'standard/input/TextInput' => array('1.0'),
                    ),
                    'children' => array(
                        'standard/input/DateInput' => array('1.0'),
                    ),
                ),
                '1.1' => array(
                    'extends' => array(),
                    'contains' => array(),
                    'children' => array(
                        'standard/input/DateInput' => array('1.0'),
                    ),
                ),
                '1.2' => array(
                    'extends' => array(),
                    'contains' => array(),
                    'children' => array(
                        'standard/input/DateInput' => array('1.0'),
                    ),
                ),
                'N/A' => array(
                    'extends' => array(),
                    'contains' => array(),
                    'children' => array(
                        'standard/input/PasswordInput' => array('1.3'),
                    ),
                ),
            ),
            'standard/input/PasswordInput' => array(
                '1.3' => array(
                    'extends' => array(
                        'standard/input/FormInput' => array('N/A'),
                    ),
                    'contains' => array(),
                    'children' => array(),
                ),
            ),
        );
        $this->assertIdentical($expected, $dependencies);
    }

    function testGetWidgetRelationships() {
        $result = Widgets::getWidgetRelationships(
            array(
                'custom/sample/SampleWidget' => array(
                    'type' => 'custom',
                    'absolutePath' => 'whatever',
                    'relativePath' => 'custom/sample/SampleWidget',
                    'category'     => array('Pine', 'Oak')
                ),
                'standard/input/FormInput' => array(
                    'type' => 'standard',
                    'absolutePath' => 'whatever',
                    'relativePath' => 'standard/input/FormInput',
                ),
                'standard/input/PasswordInput' => array(
                    'type' => 'standard',
                    'absolutePath' => 'whatever',
                    'relativePath' => 'standard/input/PasswordInput',
                ),
                'standard/input/SelectionInput' => array(
                    'type' => 'standard',
                    'absolutePath' => 'whatever',
                    'relativePath' => 'standard/input/SelectionInput',
                ),
                'standard/input/TextInput' => array(
                    'type' => 'standard',
                    'absolutePath' => 'whatever',
                    'relativePath' => 'standard/input/TextInput',
                ),
                'standard/input/BasicTextInput' => array(
                    'type' => 'standard',
                    'absolutePath' => 'whatever',
                    'relativePath' => 'standard/input/BasicTextInput',
                ),
            ),
            array(
                'widgetVersions' => array(
                    'standard/input/FormInput' => array (
                        'category' => array(
                            'Input'
                        ),
                        '1.0.1' => array (
                            'requires' => array (
                                'framework' => array ('3.0', '3.1', '3.2'),
                            ),
                            'contains' => array (
                                array (
                                    'widget' => 'standard/input/SelectionInput',
                                    'versions' => array ('1.0'),
                                ),
                                array (
                                    'widget' => 'standard/input/TextInput',
                                    'versions' => array ('1.0'),
                                ),
                            ),
                        ),
                        '1.1.1' => array (
                            'requires' => array (
                                'framework' => array ('3.1', '3.2'),
                            ),
                            'contains' => array (
                                array (
                                    'widget' => 'standard/input/SelectionInput',
                                    'versions' => array ('1.0', '1.1'),
                                ),
                                array (
                                    'widget' => 'standard/input/TextInput',
                                    'versions' => array ('1.1'),
                                ),
                            ),
                        ),
                    ),
                    'standard/input/PasswordInput' => array (
                        'category' => array(
                            'Input'
                        ),
                        '1.0.1' => array (
                            'requires' => array (
                            ),
                            'contains' => array (
                                array (
                                    'widget' => 'standard/input/SelectionInput',
                                ),
                            ),
                            'extends' => array (
                                'widget' => 'standard/input/TextInput',
                                'versions' => array ('1.1'),
                            ),
                        ),
                        '1.1.1' => array (
                            'requires' => array (
                            ),
                            'contains' => array (
                                array (
                                    'widget' => 'standard/input/SelectionInput',
                                    'versions' => array('1.1'),
                                ),
                            ),
                            'extends' => array (
                                'widget' => 'standard/input/TextInput',
                            ),
                        ),
                    ),
                    'standard/input/SelectionInput' => array (
                        '1.0.1' => array (
                            'requires' => array (
                                'framework' => array ('3.0', '3.1', '3.2'),
                            ),
                        ),
                        '1.1.1' => array (
                            'requires' => array (
                                'framework' => array ('3.1', '3.2'),
                            ),
                        ),
                    ),
                    'standard/input/TextInput' => array (
                        '1.0.1' => array (
                            'requires' => array (
                                'framework' => array ('3.0', '3.1', '3.2'),
                            ),
                        ),
                        '1.1.1' => array (
                            'requires' => array (
                                'framework' => array ('3.1', '3.2'),
                            ),
                        ),
                    ),
                    'standard/input/BasicTextInput' => array (
                        '1.0.1' => array (
                            'requires' => array (
                                'framework' => array ('3.1', '3.2'),
                            ),
                            'extends' => array(
                                'widget' => 'standard/input/TextInput',
                                'versions' => array('1.1'),
                            ),
                        ),
                    ),
                ),
            )
        );
        $expected = array(
            'widgets' => array(
                'custom/sample/SampleWidget' => array(
                    'type' => 'custom',
                    'absolutePath' => 'whatever',
                    'relativePath' => 'custom/sample/SampleWidget',
                    'category' => array(
                        'Oak',
                        'Pine'
                    ),
                    'versions' => array(
                        array(
                            'children' => array(),
                            'contains' => array(),
                            'extends' => array(
                                'standard/input/SelectionInput' => array ('1.9'),
                            ),
                            'framework' => array('99.99'),
                            'version' => '1.0',
                        ),
                    ),
                ),
                'standard/input/BasicTextInput' => array(
                    'type' => 'standard',
                    'absolutePath' => 'whatever',
                    'relativePath' => 'standard/input/BasicTextInput',
                    'versions' => array(
                        array(
                            'children' => array(),
                            'contains' => array(),
                            'extends' => array(
                                'standard/input/TextInput' => array ('1.1'),
                            ),
                            'framework' => array('3.1', '3.2'),
                            'version' => '1.0',
                        ),
                    ),
                ),
                'standard/input/FormInput' => array(
                    'type' => 'standard',
                    'absolutePath' => 'whatever',
                    'relativePath' => 'standard/input/FormInput',
                    'versions' => array(
                        array(
                            'children' => array(),
                            'contains' => array(
                                'standard/input/SelectionInput' => array('1.0'),
                                'standard/input/TextInput' => array('1.0'),
                            ),
                            'extends' => array(),
                            'framework' => array('3.0', '3.1', '3.2'),
                            'version' => '1.0',
                        ),
                        array(
                            'children' => array(),
                            'contains' => array(
                                'standard/input/SelectionInput' => array('1.0', '1.1'),
                                'standard/input/TextInput' => array('1.1'),
                            ),
                            'extends' => array(),
                            'framework' => array('3.1', '3.2'),
                            'version' => '1.1',
                        ),
                    ),
                ),
                'standard/input/PasswordInput' => array(
                    'type' => 'standard',
                    'absolutePath' => 'whatever',
                    'relativePath' => 'standard/input/PasswordInput',
                    'versions' => array(
                        array(
                            'children' => array(),
                            'contains' => array(
                                'standard/input/SelectionInput' => array('N/A'),
                            ),
                            'extends' => array(
                                'standard/input/TextInput' => array('1.1'),
                            ),
                            'framework' => null,
                            'version' => '1.0',
                        ),
                        array(
                            'children' => array(),
                            'contains' => array(
                                'standard/input/SelectionInput' => array('1.1'),
                            ),
                            'extends' => array(
                                'standard/input/TextInput' => array('N/A'),
                            ),
                            'framework' => null,
                            'version' => '1.1',
                        ),
                    ),
                ),
                'standard/input/SelectionInput' => array(
                    'type' => 'standard',
                    'absolutePath' => 'whatever',
                    'relativePath' => 'standard/input/SelectionInput',
                    'versions' => array(
                        array(
                            'framework' => array('3.0', '3.1', '3.2'),
                            'version' => '1.0',
                        ),
                        array(
                            'framework' => array('3.1', '3.2'),
                            'version' => '1.1',
                        ),
                    ),
                ),
                'standard/input/TextInput' => array(
                    'type' => 'standard',
                    'absolutePath' => 'whatever',
                    'relativePath' => 'standard/input/TextInput',
                    'versions' => array(
                        array(
                            'children' => array(
                                'standard/input/PasswordInput' => array ('1.1'),
                            ),
                            'framework' => array('3.0', '3.1', '3.2'),
                            'version' => '1.0',
                        ),
                        array(
                            'children' => array(
                                'standard/input/PasswordInput' => array ('1.0', '1.1'),
                                'standard/input/BasicTextInput' => array ('1.0'),
                            ),
                            'contains' => array(),
                            'extends' => array(),
                            'framework' => array('3.1', '3.2'),
                            'version' => '1.1',
                        ),
                    ),
                ),
            ),
            'errors' => false,
        );

        // hard-code this value, so we don't have to update this test with every framework bump
        $result['widgets']['custom/sample/SampleWidget']['versions'][0]['framework'] = array('99.99');
        $this->assertSame($expected, $result);

        $result = Widgets::getWidgetRelationships();
        $this->assertSame(array('Community2', 'Moderation2'), $result['widgets']['custom/attributetest/MultiOptionTest']['category']);
        $this->assertSame(array(), $result['widgets']['custom/extended/ChildWidget']['category']);
        $this->assertSame(array(), $result['widgets']['custom/extended/GrandParentWidget']['category']);
    }

    function testDetermineWidgetCssFiles() {
        Widgets::killCacheVariables();
        $method = $this->getStaticMethod('determineWidgetCssFiles');

        $results = $method(null, Registry::getWidgetPathInfo('standard/utils/SocialBookmarkLink'), null);
        $this->assertSame(2, count($results));
        $this->assertSame('base', $results[0]['type']);
        $this->assertSame(CORE_WIDGET_FILES . 'standard/utils/SocialBookmarkLink/base.css', $results[0]['path']);
        $this->assertSame('presentation', $results[1]['type']);
        $this->assertSame('standard/utils/SocialBookmarkLink', $results[1]['relativePath']);
        $this->assertSame(CORE_WIDGET_FILES . 'standard/utils/SocialBookmarkLink', $results[1]['absolutePath']);

        $results = $method(null, Registry::getWidgetPathInfo('custom/sample/SampleWidget'), null);
        $this->assertSame(4, count($results));

        $this->assertSame('base', $results[0]['type']);
        $this->assertSame(CORE_WIDGET_FILES . 'standard/input/SelectionInput/base.css', $results[0]['path']);

        $this->assertSame('presentation', $results[1]['type']);
        $this->assertSame('standard/input/SelectionInput', $results[1]['relativePath']);
        $this->assertSame(CORE_WIDGET_FILES . 'standard/input/SelectionInput', $results[1]['absolutePath']);

        $this->assertSame('base', $results[2]['type']);
        $this->assertSame(APPPATH . 'widgets/custom/sample/SampleWidget/1.0/base.css', $results[2]['path']);

        $this->assertSame('presentation', $results[3]['type']);
        $this->assertSame('custom/sample/SampleWidget', $results[3]['relativePath']);
        $this->assertSame(APPPATH . 'widgets/custom/sample/SampleWidget/1.0', $results[3]['absolutePath']);
    }

    function testGetWidgetDetailsForHeader() {
        $method = $this->getStaticMethod('getWidgetDetailsForHeader');

        // Base case
        $results = $method(array());
        $this->assertIsA($results, 'array');
        $this->assertIsA($results['urlParameters'], 'array');
        $this->assertSame(0, count($results['urlParameters']));
        $this->assertIsA($results['javaScriptModuleProblems'], 'array');
        $this->assertSame(0, count($results['javaScriptModuleProblems']));

        // Widget, no params
        $results = $method(array(
            'standard/foo/bar' => array(
                'meta' => array(
                    'requires' => array('jsModule' => array('none')),
                    'info' => array('description' => 'yeeeaaaaahh'),
                ),
            ),
        ));
        $this->assertIsA($results, 'array');
        $this->assertIsA($results['urlParameters'], 'array');
        $this->assertSame(0, count($results['urlParameters']));
        $this->assertIsA($results['javaScriptModuleProblems'], 'array');
        $this->assertSame(1, count($results['javaScriptModuleProblems']));

        // Params
        $results = $method(array(
            'standard/foo/bar' => array(
                'meta' => array(
                    'requires' => array('jsModule' => array('standard')),
                    'info' => array(
                        'description' => 'yeeeaaaaahh',
                        'urlParameters' => array(
                            'p' => array(
                                'name' => 'rn:msg:PRODUCT_LBL',
                                'description' => 'rn:msg:CMMA_SPARATED_IDS_COMMAS_DENOTING_MSG'
                            ),
                            'username' => array(
                                'name' => 'rn:msg:USERNAME_LBL',
                                'description' => 'rn:msg:POPULATES_USERNAME_FLD_VALUE_URL_MSG',
                                'example' => 'username/banana',
                            ),
                        ),
                    ),
                ),
            ),
        ));
        $this->assertIsA($results, 'array');
        $this->assertIsA($results['urlParameters'], 'array');
        $this->assertSame(2, count($results['urlParameters']));
        $params = $results['urlParameters'];
        $this->assertIsA($params['p'], 'stdClass');
        $this->assertIsA($params['p']->name, 'string');
        $this->assertFalse(Text::stringContains($params['p']->name, 'rn:msg'));
        $this->assertIsA($params['p']->description, 'string');
        $this->assertFalse(Text::stringContains($params['p']->description, 'rn:msg'));
        $this->assertSame(array('standard/foo/bar'), $params['p']->widgetsUsedBy);
        $this->assertSame('p', $params['p']->key);

        $this->assertIsA($params['username'], 'stdClass');
        $this->assertIsA($params['username']->name, 'string');
        $this->assertFalse(Text::stringContains($params['username']->name, 'rn:msg'));
        $this->assertIsA($params['username']->description, 'string');
        $this->assertFalse(Text::stringContains($params['username']->description, 'rn:msg'));
        $this->assertSame(array('standard/foo/bar'), $params['username']->widgetsUsedBy);
        $this->assertSame('username', $params['username']->key);
        $this->assertSame('username/banana', $params['username']->example);

        $this->assertIsA($results['javaScriptModuleProblems'], 'array');
        $this->assertSame(0, count($results['javaScriptModuleProblems']));
    }

    function testGetControllerArray() {
        $getControllerArray = $this->getStaticMethod('getControllerArray');
        $widget = Registry::getWidgetPathInfo('standard/search/KeywordText');
        $result = $getControllerArray($widget);
        $this->assertIsA($result, 'array');
        $this->assertTrue($result[0]);
        $this->assertIdentical('\RightNow\Widgets\KeywordText', $result[1]);
        $this->assertTrue(Text::endsWith($result[2], '.cfg/scripts/cp/core/widgets/standard/search/KeywordText/controller.php'));
    }

    function testRequireWidgetControllerWithPathInfo() {
        $widget = Registry::getWidgetPathInfo('/standard/search/CombinedSearchResults');
        $this->assertFalse(class_exists($widget->namespacedClassName));
        Widgets::requireWidgetControllerWithPathInfo($widget);
        $this->assertTrue(class_exists($widget->namespacedClassName));
    }

    function testGetParentControllers() {
        $widget = Registry::getWidgetPathInfo('/standard/search/CombinedSearchResults');
        $result = Widgets::getParentControllers($widget);
        $this->assertIsA($result, 'array');
        $this->assertEqual(2, count($result));
        $this->assertTrue(Text::endsWith($result[0][2], '.cfg/scripts/cp/core/widgets/standard/reports/Multiline/controller.php'));
        $this->assertTrue(Text::endsWith($result[1][2], '.cfg/scripts/cp/core/widgets/standard/search/CombinedSearchResults/controller.php'));

        $widget = Registry::getWidgetPathInfo('standard/chat/ChatOffTheRecordDialog');
        $result = Widgets::getParentControllers($widget);
        $this->assertIsA($result, 'array');
        $this->assertEqual(2, count($result));
        $this->assertFalse($result[0][0]);
        $this->assertFalse($result[1][0]);
        $this->assertIdentical("namespace RightNow\Widgets;\nclass ChatPostMessage extends \RightNow\Libraries\Widget\Base { }", $result[0][2]);
        $this->assertIdentical("namespace RightNow\Widgets;\nclass ChatOffTheRecordDialog extends \RightNow\Widgets\ChatPostMessage { }", $result[1][2]);
    }

    function testGetParentWidget() {
        $result = Widgets::getParentWidget(Registry::getWidgetPathInfo('/standard/search/CombinedSearchResults'));
        $this->assertEqual('standard/reports/Multiline', $result->relativePath);
        $this->assertNull(Widgets::getParentWidget(Registry::getWidgetPathInfo('standard/reports/Multiline')));

        // $loadController false
        $result = Widgets::getParentWidget(Registry::getWidgetPathInfo('/standard/search/CombinedSearchResults'), false);
        $this->assertEqual('standard/reports/Multiline', $result->relativePath);
        $this->assertNull(Widgets::getParentWidget(Registry::getWidgetPathInfo('standard/reports/Multiline')));
    }

    function testAddServerConstraints() {
        $result = Widgets::addServerConstraints('/app/ask', 'postRequest/sendForm');

        //Strip out interface specific token.
        $result = preg_replace("@>\s*<@", '><', preg_replace("@name='validationToken'(\s*)value='([^']*)'@", "name='validationToken'$1value=''", $result));
        $this->assertIdentical("<input type='hidden' name='validationToken' value=''/><input type='hidden' name='constraints' value='W10='/><input type='hidden' name='handler' value='postRequest/sendForm'/>", $result);
    }

    function testResetContainerID(){
        $replacerBegin = $this->getStaticMethod('containerReplacerBegin');
        $resetContainerID = $this->getStaticMethod('resetContainerID');

        $matches = array('<rn:container report_id="176">', 'container', '"');
        $this->assertTrue(Text::stringContains($replacerBegin($matches), '\RightNow\Utils\Widgets::$rnContainers["rnc_1"]'));
        $this->assertTrue(Text::stringContains($replacerBegin($matches), '\RightNow\Utils\Widgets::$rnContainers["rnc_2"]'));
        $resetContainerID();
        $this->assertTrue(Text::stringContains($replacerBegin($matches), '\RightNow\Utils\Widgets::$rnContainers["rnc_1"]'));
        $resetContainerID();
        $this->assertTrue(Text::stringContains($replacerBegin($matches), '\RightNow\Utils\Widgets::$rnContainers["rnc_1"]'));

        $returnedPhpLines = explode("\n", $replacerBegin(array('<rn:container report_id="176" regex="\w{1,2}\d{1,2}$">', 'container', '"')));
        $lineWithEncodedJson = $returnedPhpLines[3];
        $encodedJson = Text::getSubstringBefore(Text::getSubstringAfter($lineWithEncodedJson, "'"), "'");
        $result = json_decode(base64_decode($encodedJson));

        $this->assertIdentical($result->report_id, '176');
        $this->assertIdentical($result->regex, '\w{1,2}\d{1,2}$');
        $this->assertIdentical($result->rn_container_id, 'rnc_2');
    }

    function testWidgetReplacerPaths() {
        $method = $this->getMethod('widgetReplacer', true);

        $input = array('<rn:widget path="" redirect_url="/app/account/overview" initial_focus="true"/>');
        $this->assertIdentical($method($input), Config::getMessage(WIDGET_TAG_PATH_ATTRIB_DISPLAYED_MSG));

        //Just accept the invalid path, it will generate an error later on in the deploy process
        $input = array('<rn:widget path="invalid/Path" redirect_url="/app/account/overview" initial_focus="true"/>');
        $this->assertIdentical($method($input), "<?=\RightNow\Utils\Widgets::rnWidgetRenderCall('invalid/Path', array('redirect_url' => '/app/account/overview','initial_focus' => 'true',));\n?>");

        $input = array('<rn:widget path="standard/login/LoginForm" redirect_url="/app/account/overview" initial_focus="true"/>');
        $this->assertIdentical($method($input), "<?=\RightNow\Utils\Widgets::rnWidgetRenderCall('standard/login/LoginForm', array('redirect_url' => '/app/account/overview','initial_focus' => 'true',));\n?>");

        $input = array('<rn:widget path="login/LoginForm" redirect_url="/app/account/overview" initial_focus="true"/>');
        $this->assertIdentical($method($input), "<?=\RightNow\Utils\Widgets::rnWidgetRenderCall('login/LoginForm', array('redirect_url' => '/app/account/overview','initial_focus' => 'true',));\n?>");

        $input = array('<rn:widget path="standard/input/FormInput" name="Contact.Login" required="true"/>');
        $this->assertIdentical($method($input), "<?=\RightNow\Utils\Widgets::rnWidgetRenderCall('standard/input/FormInput', array('name' => 'Contact.Login','required' => 'true',));\n?>");
    }

    function testWidgetReplacerVersions() {
        $method = $this->getMethod('widgetReplacer', true);

        $input = array('<rn:widget path="standard/login/LoginForm/1.0" redirect_url="/app/account/overview" initial_focus="true"/>');
        $path = 'standard/login/LoginForm/1.0';
        $this->assertIdentical($method($input), sprintf(Config::getMessage(WIDGET_PATH_PCT_S_IS_INVALID_MSG), $path));

        $input = array('<rn:widget path="standard/login/LoginForm/6544654651.0" redirect_url="/app/account/overview" initial_focus="true"/>');
        $path = 'standard/login/LoginForm/6544654651.0';
        $this->assertIdentical($method($input), sprintf(Config::getMessage(WIDGET_PATH_PCT_S_IS_INVALID_MSG), $path));
    }

    function testWidgetReplacerAttrs() {
        $method = $this->getMethod('widgetReplacer', true);

        //Accept invalid attributes, it will generate an error later on in the deploy process
        $input = array('<rn:widget path="login/LoginForm" redirect_url="/app/account/overview" invalid_attribute="invalid"/>');
        $this->assertIdentical($method($input), "<?=\RightNow\Utils\Widgets::rnWidgetRenderCall('login/LoginForm', array('redirect_url' => '/app/account/overview','invalid_attribute' => 'invalid',));\n?>");
    }

    function testGetWidgetContextData() {
        list($widgetInstance, $widgetPathInfo, $meta) = $this->getWidgetInstancePathInfoMeta('standard/feedback/SocialContentRating');

        $getWidgetContextDataMethod = $this->getStaticMethod('getWidgetContextData');
        $result = $getWidgetContextDataMethod($widgetInstance, $widgetPathInfo->relativePath, $meta['extends_php'], array(
            'label_upvote_disabled_tooltip' => array( 'value' => 'cucumber' )
        ));
        $this->assertEqual($result, array(
            'nonDefaultAttrValues' => array(
                'label_upvote_disabled_tooltip' => array(
                    'value' => 'cucumber'
                ),
            ),
            'clickstream' => array(
                'submitVoteHandler' => 'social_content_rate'
            ),
            'login_required' => array(
                'submitVoteHandler' => true
            )
        ));

        $result1 = $getWidgetContextDataMethod($widgetInstance, $widgetPathInfo->relativePath, $meta['extends_php'], array(
            'token_check' => array( 'value' => 'false')
        ));

        $this->assertEqual($result1, array(
            'nonDefaultAttrValues' => array(
                'token_check' => array(
                    'value' => 'false'
                )
            ),
            'clickstream' => array(
                'submitVoteHandler' => 'social_content_rate'
            ),
            'login_required' => array(
                'submitVoteHandler' => true
            )
        ));
    }

    function testInspectWidgetAjaxHandler() {
        list($widgetInstance, $widgetPathInfo, $meta) = $this->getWidgetInstancePathInfoMeta('standard/discussion/BestAnswerDisplay');
        $ajaxHandlers = $widgetInstance->getAjaxHandlers();
        $ajaxHandlerKeys = array_keys($ajaxHandlers);
        $ajaxHandler = $ajaxHandlerKeys[0];

        $inspectWidgetAjaxHandlerMethod = $this->getStaticMethod('inspectWidgetAjaxHandler');

        // valid - confirm null
        $result = $inspectWidgetAjaxHandlerMethod($widgetInstance, $ajaxHandlers[$ajaxHandler], $widgetPathInfo->relativePath);
        $this->assertNull($result);

        // invalid
        $result = $inspectWidgetAjaxHandlerMethod($widgetInstance, 'ruh roh', $widgetPathInfo->relativePath);
        $this->assertIsA($result, 'string');
        $matches = array();
        preg_match('/error/i', $result, $matches);
        $this->assertTrue(count($matches) > 0);
    }

    function testAddFormToken() {
        list($widgetInstance, $widgetPathInfo, $meta) = $this->getWidgetInstancePathInfoMeta('standard/discussion/QuestionComments');
        $getAddFormTokenMethod = $this->getStaticMethod('addFormToken');
        $result = $getAddFormTokenMethod($widgetInstance, $widgetPathInfo);
        $this->assertNotNull($result->info['formToken']);
    }

    function testAccumulateWidgetContextData() {
        list($widgetInstance, $widgetPathInfo, $meta) = $this->getWidgetInstancePathInfoMeta('standard/discussion/QuestionDetail');

        // Manually get method to pass args by reference
        $reflectionClass = new ReflectionClass('RightNow\Utils\Widgets');
        $accumulateWidgetContextDataMethod = $reflectionClass->getMethod('accumulateWidgetContextData');
        $accumulateWidgetContextDataMethod->setAccessible(true);

        $contextData = $clickstreamActions = $loginRequirements = $tokenCheck = array();
        $accumulateWidgetContextDataMethod->invokeArgs(null, array(
                $widgetInstance,
                array(
                    'method' => 'delete',
                    'clickstream' => 'question_delete',
                    'exempt_from_login_requirement' => true,
                    'token_check' => false
                ),
                array(
                    'salad' => 'ceasar'
                ),
                &$contextData, &$clickstreamActions, &$loginRequirements, &$tokenCheck
            )
        );

        $this->assertEqual($contextData, array(
            'nonDefaultAttrValues' => array(
                'salad' => 'ceasar',
            ),
        ));
        $this->assertEqual($clickstreamActions, array( 'delete' => 'question_delete' ));
        $this->assertEqual($loginRequirements, array( 'delete' => 1 ));
        $this->assertEqual($tokenCheck, array( 'delete' => false ));
    }

    function testGetMethodNameFromAjaxHandler() {
        $getMethodNameFromAjaxHandlerMethod = $this->getStaticMethod('getMethodNameFromAjaxHandler');

        $this->assertEqual('burrito', $getMethodNameFromAjaxHandlerMethod('burrito'));
        $ajaxHandler = array( 'method' => 'quesadilla' );
        $this->assertEqual('quesadilla', $getMethodNameFromAjaxHandlerMethod($ajaxHandler));
        $this->assertEqual('', $getMethodNameFromAjaxHandlerMethod(array('tostada')));
    }

    function testSetContextDataToWidgetInstance() {
        list($widgetInstance, $widgetPathInfo, $meta) = $this->getWidgetInstancePathInfoMeta('standard/discussion/QuestionComments');

        $getWidgetContextDataMethod = $this->getStaticMethod('getWidgetContextData');
        $contextData = $getWidgetContextDataMethod($widgetInstance, $widgetPathInfo->relativePath, $meta['extends_php'], $widgetPathInfo->meta['attributes']);

        $setContextDataToWidgetInstanceMethod = $this->getStaticMethod('setContextDataToWidgetInstance');

        $result = $setContextDataToWidgetInstanceMethod($widgetInstance, $contextData);
        $this->assertNotNull($result->info['contextData']);
        $this->assertNotNull($result->info['contextToken']);
        $this->assertNotNull($result->info['timestamp']);
    }

    function testCreateWidgetInDevelopmentDoesNotThrowErrorIfWidgetWasNotCachedOnPageControllerProperty() {
        $widget = Registry::getWidgetPathInfo('standard/utils/PrintPageLink');
        $method = $this->getStaticMethod('createWidgetInDevelopment');
        $result = $method($widget, false);
        $this->assertIsA($result, 'RightNow\Widgets\PrintPageLink');
    }

    function testCreateWidgetInDevelopmentThrowsErrorIfWidgetsWereCachedOnPageControllerProperty() {
        get_instance()->widgetCallsOnPage = array();
        $widget = Registry::getWidgetPathInfo('standard/utils/PrintPageLink');
        $method = $this->getStaticMethod('createWidgetInDevelopment');
        $result = $method($widget, false);
        $this->assertIsA($result, 'string');
        get_instance()->widgetCallsOnPage = null;
    }

    function killWidgetCache() {
        static $cache = null;
        $cache || $cache = new \RightNow\Libraries\Cache\PersistentReadThroughCache(1, function() { });
        $cacheKey = "RightNow\Utils\Widgets:getWidgetVersionDirectory";
        try{
            $cache->expire($cacheKey);
        }
        catch(\Exception $e) { }
    }

}

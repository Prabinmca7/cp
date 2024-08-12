<?php

use RightNow\Utils\Text,
    RightNow\Internal\Libraries\Widget\Registry,
    RightNow\Internal\Libraries\Widget\Documenter,
    RightNow\Internal\Libraries\Widget\Builder,
    RightNow\Libraries\ThirdParty\SimpleHtmlDom,
    RightNow\Utils\Widgets;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

require_once CPCORE . 'Internal/Libraries/Widget/Documenter.php';

class FakeFileWriter extends \RightNow\Internal\Libraries\Widget\FileWriter {
    public $directoryExists = false;
    public $writeDirectory = true;
    public $wroteDirectory = false;
    public $returnError = false;
    public $dir = '';
    public $written = array();

    function __construct($options = array()) {
        foreach ($options as $prop => $value) {
            $this->{$prop} = $value;
        }
    }
    function setDirectory($path) {
        $this->dir = $path;
        parent::setDirectory($path);
    }
    function directoryAlreadyExists() {
        return $this->directoryExists;
    }
    function writeDirectory() {
        return $this->wroteDirectory = $this->writeDirectory;
    }
    function baseDir() {
        return $this->baseDir;
    }
    function write($path, $content, $absolutePathSpecified = false) {
        if ($this->returnError) return false;

        $this->written[$path] = $content;
        return true;
    }
}

class FakeWidgetEnabler {
    public static $args;
    public static $error = false;

    public static function updateWidgetVersion() {
        self::$args = func_get_args();
        return (self::$error)
            ? false
            : array('some/widget/path' => '1.0');
    }
}

class WidgetBuilderTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Libraries\Widget\Builder';

    function printCode($code) {
        $caller = debug_backtrace();
        $caller = $caller[1]['function'];
        $this->echoContent("<br>$caller Code:<br>"
            . "<pre style='cursor:pointer;height:50px;overflow:hidden;'; onclick='this.style.height=(this.style.height==\"50px\")?\"auto\":\"50px\"'>"
            . var_export(htmlspecialchars($code), true) . "</pre><br>");
    }

    function writeFile($path) {
        if (!\RightNow\Utils\FileSystem::isReadableFile($path)) {
            @file_put_contents($path, '');
            $this->wroteFile = $path;
        }
    }

    function deleteFile() {
        if ($this->wroteFile) {
            @unlink($this->wroteFile);
            $this->wroteFile = null;
        }
    }

    function testConstructor() {
        list($builder, $name, $path, $errors) = $this->reflect('name', 'folderPath', 'errors');

        $b = $builder->newInstance('', '');
        $this->assertIdentical('', $name->getValue($b));
        $this->assertIdentical('', $path->getValue($b));
        $this->assertSame(2, count($errors->getValue($b)));

        $b = $builder->newInstance('hey#now', 'nonon.o');
        $this->assertIdentical('', $name->getValue($b));
        $this->assertIdentical('', $path->getValue($b));
        $this->assertSame(2, count($errors->getValue($b)));

        $b = $builder->newInstance(' heynow/', 'nonon.o');
        $this->assertIdentical('heynow', $name->getValue($b));
        $this->assertIdentical('', $path->getValue($b));
        $this->assertSame(1, count($errors->getValue($b)));

        $b = $builder->newInstance('hey[now', '/nonon/o/');
        $this->assertIdentical('', $name->getValue($b));
        $this->assertIdentical('nonon/o', $path->getValue($b));
        $this->assertSame(1, count($errors->getValue($b)));

        $b = $builder->newInstance('HEYNOW-MINE-me', '/nonono/foo/bar');
        $this->assertIdentical('HEYNOW-MINE-me', $name->getValue($b));
        $this->assertIdentical('nonono/foo/bar', $path->getValue($b));
        $this->assertSame(0, count($errors->getValue($b)));
    }

    function testSaveErrors() {
        // Invalid file writer
        $builder = new Builder('foo', 'bar');
        $this->assertFalse($builder->save((object) array()));

        // Invalid widget updater
        $this->assertFalse($builder->save(null, 'SomeClassDoesntExist'));
        $this->assertFalse($builder->save(null, 'Widget'));

        // Widget dir already exists
        $builder = new Builder('foo', 'bar');
        $writer = new FakeFileWriter(array(
            'directoryExists' => true,
        ));
        $this->assertFalse($builder->save($writer));
        $this->assertIdentical('A widget by that name already exists: custom/bar/foo', implode('<br>', $builder->getErrors()));
        $this->assertSame('custom/bar/foo', $writer->dir);

        // Error writing widget dir
        $builder = new Builder('foo', 'bar');
        $writer = new FakeFileWriter(array(
            'writeDirectory' => false,
        ));
        $this->assertFalse($builder->save($writer));
        $this->assertIdentical('An error was encountered trying to create widget directory: custom/bar/foo', implode('<br>', $builder->getErrors()));
        $this->assertSame('custom/bar/foo', $writer->dir);
        $this->assertFalse($writer->wroteDirectory);

        // Error writing a widget file
        $builder = new Builder('foo', 'bar');
        $writer = new FakeFileWriter(array(
            'returnError' => true,
        ));
        $this->assertFalse($builder->save($writer, 'FakeWidgetEnabler'));
        $this->assertIdentical('An error was encountered trying to create widget component: custom/bar/foo/info.yml', implode('<br>', $builder->getErrors()));
        $this->assertSame('custom/bar/foo', $writer->dir);
        $this->assertTrue($writer->wroteDirectory);
        $this->assertNull(FakeWidgetEnabler::$args);

        // Error enabling the widget
        $builder = new Builder('banana', 'not');
        $writer = new FakeFileWriter;
        FakeWidgetEnabler::$error = true;
        $wrote = $builder->save($writer, FakeWidgetEnabler);
        $this->assertIdentical(array('An error was encountered while attempting to activate custom/not/banana'), $builder->getErrors());
        $this->assertSame('custom/not/banana', $writer->dir);
        $this->assertTrue($writer->wroteDirectory);
        $this->assertIdentical(array('custom/not/banana', '1.0'), FakeWidgetEnabler::$args);
        FakeWidgetEnabler::$error = false;
    }

    function testSaveWithNoComponents() {
        // No components
        $builder = new Builder('foo', 'bar');
        $writer = new FakeFileWriter;
        $wrote = $builder->save($writer, FakeWidgetEnabler);
        $this->assertIdentical(array(), $builder->getErrors());
        $this->assertSame('custom/bar/foo', $writer->dir);
        $this->assertTrue($writer->wroteDirectory);
        $this->assertIdentical(array(
            'widget' => array(
                'davLabel' => 'View Code',
                'davLink' => '/dav/cp/customer/development/widgets/custom/bar/foo/1.0',
                'docLabel' => 'View Documentation',
                'docLink' => '/ci/admin/versions/manage#widget=custom%2Fbar%2Ffoo'
            ),
            'files' => array(
                'manifest' => '/dav/cp/customer/development/widgets/custom/bar/foo/1.0/info.yml',
                'presentation' => '/dav/cp/customer/assets/themes/standard/widgetCss/foo.css',
                'base' => '/dav/cp/customer/development/widgets/custom/bar/foo/1.0/base.css',
            ),
        ), $wrote);
        $this->assertIdentical(CUSTOMER_FILES . 'widgets/custom/bar/foo/1.0', $writer->baseDir());
        $this->assertIdentical(array('custom/bar/foo', '1.0'), FakeWidgetEnabler::$args);
    }

    function testSaveWithAllComponents() {
        // All components
        $builder = new Builder('foo', 'barr');
        $builder->addComponent('php');
        $builder->addComponent('js');
        $builder->addComponent('view');
        $builder->addComponent('jsView');
        $builder->addComponent('parentCss');
        $writer = new FakeFileWriter;
        $wrote = $builder->save($writer, FakeWidgetEnabler);
        $this->assertIdentical(array(), $builder->getErrors());
        $this->assertSame('custom/barr/foo', $writer->dir);
        $this->assertTrue($writer->wroteDirectory);
        $this->assertIdentical(array(
            'widget' => array(
                'davLabel' => 'View Code',
                'davLink' => '/dav/cp/customer/development/widgets/custom/barr/foo/1.0',
                'docLabel' => 'View Documentation',
                'docLink' => '/ci/admin/versions/manage#widget=custom%2Fbarr%2Ffoo'
            ),
            'files' => array(
                'manifest'     => '/dav/cp/customer/development/widgets/custom/barr/foo/1.0/info.yml',
                'php'          => '/dav/cp/customer/development/widgets/custom/barr/foo/1.0/controller.php',
                'js'           => '/dav/cp/customer/development/widgets/custom/barr/foo/1.0/logic.js',
                'view'         => '/dav/cp/customer/development/widgets/custom/barr/foo/1.0/view.php',
                'view.ejs'     => '/dav/cp/customer/development/widgets/custom/barr/foo/1.0/view.ejs',
                'presentation' => '/dav/cp/customer/assets/themes/standard/widgetCss/foo.css',
                'base'         => '/dav/cp/customer/development/widgets/custom/barr/foo/1.0/base.css',
            ),
        ), $wrote);
        $this->assertIdentical(CUSTOMER_FILES . 'widgets/custom/barr/foo/1.0', $writer->baseDir());
        $this->assertIdentical(array('custom/barr/foo', '1.0'), FakeWidgetEnabler::$args);
    }

    // Existing presentation CSS is not overwritten when *not* inheriting parent's CSS. This presupposes there's a widget called TextInput.
    function testSaveDoesNotOverwriteExistingCSS() {
        $this->writeFile(HTMLROOT . '/euf/assets/themes/standard/widgetCss/TextInput.css');

        $builder = new Builder('TextInput', 'somethingUnique');
        $builder->addComponent('php');
        $writer = new FakeFileWriter;
        $wrote = $builder->save($writer, FakeWidgetEnabler);
        $this->assertIdentical(array(), $builder->getErrors());
        foreach ($writer->written as $path => $junk) {
            if (Text::endsWith($path, "TextInput.css")) {
                $this->fail("Because TextInput.css already exists, the Builder shouldn't tell the writer to write one.");
            }
        }

        $this->deleteFile();
    }

    // Existing presentation CSS is not overwritten when inheriting parent's CSS. This presupposes there's a widget called TextInput.
    function testSaveDoesNotOverwriteExistingCSSWhenInheriting() {
        $this->writeFile(HTMLROOT . '/euf/assets/themes/standard/widgetCss/TextInput.css');

        $builder = new Builder('TextInput', 'somethingUnique');
        $builder->addComponent('php');
        $builder->addComponent('parentCss');
        $writer = new FakeFileWriter;
        $wrote = $builder->save($writer, FakeWidgetEnabler);
        $this->assertIdentical(array(), $builder->getErrors());
        foreach ($writer->written as $path => $junk) {
            if (Text::endsWith($path, "TextInput.css")) {
                $this->fail("Because TextInput.css already exists, the Builder shouldn't tell the writer to write one.");
            }
        }

        $this->deleteFile();
    }

    function testSaveWithViewPartials() {
        $builder = new Builder('bananas', 'park');
        $parent = Registry::getWidgetPathInfo('standard/feedback/AnswerFeedback');
        $parentInfo = Widgets::getWidgetInfo($parent);
        $parentPartials = $parentInfo['view_partials'];
        $builder->setParent($parent);
        $builder->addComponent('view');
        $writer = new FakeFileWriter;
        $wrote = $builder->save($writer, FakeWidgetEnabler);
        $this->assertIdentical(array(), $builder->getErrors());
        foreach ($parentPartials as $name) {
            $this->assertTrue(array_key_exists($name, $writer->written));
            $this->assertTrue(array_key_exists($name, $wrote['files']));
            $this->assertEndsWith($wrote['files'][$name], $name);
        }
    }

    function testSaveOverriddingViewWithPartials() {
        $builder = new Builder('bananas', 'park');
        $parent = Registry::getWidgetPathInfo('standard/feedback/AnswerFeedback');
        $parentInfo = Widgets::getWidgetInfo($parent);
        $parentPartials = $parentInfo['view_partials'];
        $builder->setParent($parent);
        $builder->addComponent('view', true);
        $writer = new FakeFileWriter;
        $wrote = $builder->save($writer, FakeWidgetEnabler);
        $this->assertIdentical(array(), $builder->getErrors());
        foreach ($parentPartials as $name) {
            $this->assertFalse(array_key_exists($name, $writer->written));
            $this->assertFalse(array_key_exists($name, $wrote['files']));
        }
    }

    function testYamlConstruction() {
        // Base
        $builder = new Builder('foo', 'bar');
        $writer = new FakeFileWriter;
        $builder->save($writer, FakeWidgetEnabler);
        $yaml = yaml_parse($writer->written['info.yml']);
        $this->assertIsA($yaml, 'array');
        $this->assertSame(2, count($yaml));
        $this->assertSame('1.0', $yaml['version']);
        $this->assertSame(array('standard', 'mobile'), $yaml['requires']['jsModule']);
        $this->assertNull($yaml['requires']['framework']);

        // Extends components
        $builder = new Builder('foo', 'bar');
        $builder->setParent(Registry::getWidgetPathInfo('standard/utils/EmailAnswerLink'));
        $builder->addComponent('php');
        $builder->addComponent('js');
        $builder->addComponent('view');
        $builder->addComponent('jsView');
        $builder->addComponent('parentCss');
        $writer = new FakeFileWriter;
        $builder->save($writer, FakeWidgetEnabler);
        $yaml = yaml_parse($writer->written['info.yml']);
        $this->assertSame(3, count($yaml));
        $this->assertSame('standard/utils/EmailAnswerLink', $yaml['extends']['widget']);
        $this->assertIdentical(array('php', 'js', 'view', 'css'), $yaml['extends']['components']);

        // Overrides parent view + JS
        $builder = new Builder('foo', 'bar');
        $builder->setParent(Registry::getWidgetPathInfo('standard/utils/EmailAnswerLink'));
        $builder->addComponent('php');
        $builder->addComponent('js', true);
        $builder->addComponent('view', true);
        $builder->addComponent('jsView', true);
        $builder->addComponent('parentCss', true);
        $writer = new FakeFileWriter;
        $builder->save($writer, FakeWidgetEnabler);
        $yaml = yaml_parse($writer->written['info.yml']);
        $this->assertSame(3, count($yaml));
        $this->assertSame('standard/utils/EmailAnswerLink', $yaml['extends']['widget']);
        $this->assertIdentical(array('php', 'css'), $yaml['extends']['components']);
        $this->assertIdentical('true', $yaml['extends']['overrideViewAndLogic']);

        // Attributes
        $builder = new Builder('foo', 'bar');
        $attributes = array(
            'banana_string' => array(
                'type'        => 'string',
                'default'     => 'foo',
                'name'        => 'Banana String',
                'description' => 'desc',
            ),
            'banana_int' => array(
                'type'        => 'int',
                'default'     => 23,
                'name'        => 'Banana Int',
                'description' => 'desc',
            ),
            'banana_bool' => array(
                'type'        => 'bool',
                'default'     => true,
                'name'        => 'Banana Bool',
                'description' => 'desc',
            ),
            'banana_filepath' => array(
                'type'        => 'filepath',
                'default'     => 'foo',
                'name'        => 'Banana FP',
                'description' => 'desc',
            ),
            'banana_ajax' => array(
                'type'        => 'string',
                'default'     => 'foo',
                'name'        => 'Banana Ajax',
                'description' => 'desc',
            ),
            'banana_option' => array(
                'type'        => 'option',
                'default'     => 'new',
                'name'        => 'Banana Option',
                'description' => 'desc',
                'options'     => array('new', 'old'),
            ),
        );
        $builder->setAttributes(array_map(function($attr) { return (object) $attr; }, $attributes));
        $writer = new FakeFileWriter;
        $builder->save($writer, FakeWidgetEnabler);
        $yaml = yaml_parse($writer->written['info.yml']);
        $this->assertSame(3, count($yaml));
        foreach ($attributes as $key => $attr) {
            $this->assertIdentical($attr, $yaml['attributes'][$key]);
        }

        // Info
        $builder = new Builder('foo', 'bar');
        $input = array(
            'description' => 'bananas',
            'jsModule' => array(
                'standard',
            ),
            'urlParams' => array(
                'banana' => array(
                    'name' => 'NAME',
                    'description' => 'DESCRIPTION',
                    'example' => 'EXAMPLE',
                )
            )
        );
        $builder->setInfo($input);
        $writer = new FakeFileWriter;
        $builder->save($writer, FakeWidgetEnabler);
        $yaml = yaml_parse($writer->written['info.yml']);
        $this->assertIdentical($input['description'], $yaml['info']['description']);
        $this->assertIdentical($input['urlParams'], $yaml['info']['urlParameters']);
        $this->assertIdentical($input['jsModule'], $yaml['requires']['jsModule']);
    }

    function testParseHTMLEntitiesOnAttributes() {
        list($builder, $parseHTMLEntitiesOnAttributes) = $this->reflect('method:parseHTMLEntitiesOnAttributes');
        $builder = $builder->newInstance('foo', 'bar');

        // Passing in an attribute Array
        $encodedArray = array(
            'attributeArray' => array(
                'name'        => '&lt;No Decoding This&gt;',
                'description' => '&lt;decode this&gt;&amp;&lt;this&gt;',
                'default'     => array('&lt;decode this&gt;&amp;&lt;this&gt;'),
            ),
        );

        $decodedArray = array(
            'attributeArray' => array(
                'name'        => '&lt;No Decoding This&gt;',
                'description' => '<decode this>&<this>',
                'default'     => array('<decode this>&<this>'),
            ),
        );

        $result = $parseHTMLEntitiesOnAttributes->invoke($builder, $encodedArray, true);
        $this->assertSame($result, $decodedArray);

        $result = $parseHTMLEntitiesOnAttributes->invoke($builder, $decodedArray, false);
        $this->assertSame($result, $encodedArray);

        // Passing in an attribute Object
        $encodedObject = new stdClass();
        $encodedObject->name = '<no encoding & this>';
        $encodedObject->description = '&lt;encode this&gt;&amp;&lt;this&gt;';
        $encodedObject->default =  array('&lt;encode this&gt;&amp;&lt;this&gt;');

        $decodedObject = new stdClass();
        $decodedObject->name = '<no encoding & this>';
        $decodedObject->description = '<encode this>&<this>';
        $decodedObject->default =  array('<encode this>&<this>');

        $results = $parseHTMLEntitiesOnAttributes->invoke($builder, array(clone $encodedObject), true);
        $this->assertSame($results[0]->name, $decodedObject->name);
        $this->assertSame($results[0]->description, $decodedObject->description);
        $this->assertSame($results[0]->default[0], $decodedObject->default[0]);

        $results = $parseHTMLEntitiesOnAttributes->invoke($builder, array(clone $decodedObject), false);
        $this->assertSame($results[0]->name, $encodedObject->name);
        $this->assertSame($results[0]->description, $encodedObject->description);
        $this->assertSame($results[0]->default[0], $encodedObject->default[0]);
    }

    function testCss() {
        list(
            $builder,
            $components,
            $generateCss,
            $addComponent,
            $setParent
            ) = $this->reflect('components', 'method:generateCss', 'method:addComponent', 'method:setParent');

        // Single widget
        $b = $builder->newInstance('foo', 'bar');
        $results = $generateCss->invoke($b);
        $this->assertIsA($results, 'array');
        $this->assertSame(2, count($results));
        $this->printCode($results['presentation']);
        $this->assertTrue(Text::stringContains($results['presentation'], '.rn_foo {'));
        $this->assertTrue(Text::stringContains($results['presentation'], 'Presentation CSS'));
        $this->printCode($results['base']);
        $this->assertTrue(Text::stringContains($results['base'], '.rn_foo {'));
        $this->assertTrue(Text::stringContains($results['base'], 'Base CSS for foo'));

        // With Parent
        $setParent->invoke($b, Registry::getWidgetPathInfo('standard/utils/AnnouncementText'));
        $results = $generateCss->invoke($b);
        $this->assertIsA($results, 'array');
        $this->assertSame(2, count($results));
        $this->printCode($results['presentation']);
        $this->assertTrue(Text::stringContains($results['presentation'], '.rn_foo.rn_AnnouncementText {'));
        $this->assertTrue(Text::stringContains($results['presentation'], 'Presentation CSS'));
        $this->printCode($results['base']);
        $this->assertTrue(Text::stringContains($results['base'], '.rn_foo.rn_AnnouncementText {'));
        $this->assertTrue(Text::stringContains($results['base'], 'Base CSS for foo'));

        // With Include Parent and parent CSS include
        $setParent->invoke($b, Registry::getWidgetPathInfo('standard/utils/AnnouncementText'));
        $addComponent->invoke($b, 'parentCss');
        $results = $generateCss->invoke($b);
        $this->assertIsA($results, 'array');
        $this->assertSame(2, count($results));
        $this->printCode($results['presentation']);
        $this->assertTrue(Text::stringContains($results['presentation'], '.rn_foo.rn_AnnouncementText {'));
        $this->assertTrue(Text::stringContains($results['presentation'], 'Presentation CSS'));
        $this->printCode($results['base']);
        $this->assertTrue(Text::stringContains($results['base'], '.rn_foo.rn_AnnouncementText {'));
        $this->assertTrue(Text::stringContains($results['base'], 'Base CSS for foo'));
        $this->assertIdentical(array('parentCss' => true), $components->getValue($b));
    }

    function testMergeParentAttributes() {
        list(
            $builder,
            $merge,
            $setParent,
            $setAttributes,
            ) = $this->reflect('method:mergeParentAttributes', 'method:setParent', 'method:setAttributes');

        $parent = Registry::getWidgetPathInfo('standard/search/AdvancedSearchDialog');
        $manifest = Widgets::convertAttributeTagsToValues($parent->meta, array('validate' => false));
        $parentAttributes = $manifest['attributes'];

        $cloneAttributes = function($parentAttributes){
            $clone = array();
            foreach($parentAttributes as $key => $values) {
                $clone[$key] = clone $values;
            }
            return $clone;
        };

        // No changes, resulting attributes should be an empty array
        $b = $builder->newInstance('aFolder', 'YetaWidget');
        $setParent->invoke($b, $parent);
        $setAttributes->invoke($b, $parentAttributes);
        $actual = $merge->invoke($b);
        $this->assertIdentical(array(), $actual);

        // No parent
        $b = $builder->newInstance('aFolder', 'YetaWidget');
        $setAttributes->invoke($b, $parentAttributes);
        $this->assertIdentical($parentAttributes, $merge->invoke($b));

        // Child attribute that does not exist on parent.
        $b = $builder->newInstance('aFolder', 'YetaWidget');
        $setParent->invoke($b, $parent);
        $attributes = $cloneAttributes($parentAttributes);
        $attributes['someNewAttribute'] = new \RightNow\Libraries\Widget\Attribute(array('name' => 'a_string', 'type' => 'STRING', 'description' => 'a string', 'default' => 'one'));
        $setAttributes->invoke($b, $attributes);
        $actual = $merge->invoke($b);
        $this->assertTrue(array_key_exists('someNewAttribute', $actual));
        $this->assertEqual(1, count($actual));

        // 'description' 'type' and 'default' modifications preserved
        $b = $builder->newInstance('aFolder', 'YetaWidget');
        $setParent->invoke($b, $parent);
        $attributes = $cloneAttributes($parentAttributes);
        $attributes['display_sort_filter']->type = 'STRING'; // BOOLEAN to STRING
        $attributes['display_sort_filter']->default = 'true'; // true to 'true'
        $attributes['additional_filters']->description = 'blah blah blah';
        $attributes['additional_filters']->required = true;
        $setAttributes->invoke($b, $attributes);
        $actual = $merge->invoke($b);
        $this->assertTrue(array_key_exists('display_sort_filter', $actual));
        $this->assertEqual('STRING', $actual['display_sort_filter']->type);
        $this->assertTrue(array_key_exists('additional_filters', $actual));
        $this->assertEqual('blah blah blah', $actual['additional_filters']->description);
        $this->assertEqual(2, count($actual));
        $this->assertIdentical(true, $actual['additional_filters']->required);

        //@@@ QA 130621-000096 Changing the required directive updates the extending widget's info.yml
        $b = $builder->newInstance('aFolder', 'YetaWidget');
        $setParent->invoke($b, $parent);
        $attributes = $cloneAttributes($parentAttributes);
        $attributes['display_sort_filter']->required = true;
        $setAttributes->invoke($b, $attributes);
        $actual = $merge->invoke($b);
        $this->assertTrue(array_key_exists('display_sort_filter', $actual));
        $this->assertTrue($actual['display_sort_filter']->required);

        // Unset parent attributes
        $b = $builder->newInstance('aFolder', 'YetaWidget');
        $setParent->invoke($b, $parent);
        $attributes = $cloneAttributes($parentAttributes);
        unset($attributes['report_id'], $attributes['report_page_url']);
        $setAttributes->invoke($b, $attributes);
        $actual = $merge->invoke($b);
        $this->assertEqual(2, count($actual));
        $this->assertIdentical(array('report_id', 'report_page_url'), array_keys($actual));
        $this->assertIdentical(array('unset', 'unset'), array_values($actual));

        //Option and multioption types
        $parent = Registry::getWidgetPathInfo('custom/attributetest/MultiOptionTest');
        $manifest = Widgets::convertAttributeTagsToValues($parent->meta, array('validate' => false));
        $parentAttributes = $manifest['attributes'];

        $b = $builder->newInstance('aFolder', 'YetaWidget');
        $setParent->invoke($b, $parent);
        $attributes = $cloneAttributes($parentAttributes);
        $attributes['multioption1']->default = array('a'); // changing default
        $attributes['multioption2']->options = array('a', 'b', 'c', 'd'); // adding an option
        $attributes['multioption3']->default = null; // changing default
        $setAttributes->invoke($b, $attributes);
        $actual = $merge->invoke($b);
        $this->assertTrue(array_key_exists('multioption1', $actual));
        $this->assertTrue(array_key_exists('multioption2', $actual));
        $this->assertTrue(array_key_exists('multioption3', $actual));
        $this->assertIdentical(array('a'), $actual['multioption1']->default);
        $this->assertIdentical(array('a', 'b', 'c', 'd'), $actual['multioption2']->options);
        $this->assertNull($actual['multioption3']->default);
    }

    function testCopyingParentsYuiRequirements() {
        $parent = Registry::getWidgetPathInfo('standard/reports/Grid');

        $builder = new Builder('foo', 'bar');
        $builder->setParent($parent);
        $builder->addComponent('php');
        $builder->addComponent('js', true);
        $writer = new FakeFileWriter;
        $builder->save($writer, FakeWidgetEnabler);
        $yaml = yaml_parse($writer->written['info.yml']);

        $this->assertIsA($yaml['requires']['yui'], 'array');
        $this->assertIdentical($parent->meta['requires']['yui'], $yaml['requires']['yui']);

        $parent = Registry::getWidgetPathInfo('standard/utils/Blank');
        $builder->setParent($parent);
        $builder->addComponent('php');
        $builder->addComponent('js', true);
        $builder->save($writer, FakeWidgetEnabler);
        $yaml = yaml_parse($writer->written['info.yml']);

        $this->assertFalse(array_key_exists('yui', $yaml['requires']));
    }

    function testMergingParentsYuiRequirementsWithChilds() {
        $parent = Registry::getWidgetPathInfo('standard/reports/Grid');

        $builder = new Builder('foo', 'bar');
        $builder->setParent($parent);
        $builder->addComponent('php');
        $builder->addComponent('js', true);
        $parentModules = $parent->meta['requires']['yui'];
        $additional = array('foo', 'bar', 'banana');
        $additional []= $parentModules[0];
        $builder->setYUIModules($additional);
        $writer = new FakeFileWriter;
        $builder->save($writer, FakeWidgetEnabler);
        $yaml = yaml_parse($writer->written['info.yml']);

        $this->assertIsA($yaml['requires']['yui'], 'array');
        $this->assertIdentical(array_values(array_unique(array_merge($parentModules, $additional))), $yaml['requires']['yui']);

        $parent = Registry::getWidgetPathInfo('standard/utils/Blank');
        $builder->setParent($parent);
        $builder->addComponent('php');
        $builder->addComponent('js', true);
        $builder->setYUIModules($additional);
        $builder->save($writer, FakeWidgetEnabler);
        $yaml = yaml_parse($writer->written['info.yml']);

        $this->assertIsA($yaml['requires']['yui'], 'array');
        $this->assertIdentical($additional, $yaml['requires']['yui']);
    }

    function testController() {
        list(
            $builder,
            $components,
            $addComponent,
            $setParent,
            $setAttrs
            ) = $this->reflect('components', 'method:addComponent', 'method:setParent', 'method:setAttributes');

        // Invalid
        $b = $builder->newInstance('foo', 'bar');
        $this->assertFalse($addComponent->invoke($b, 'controller'));

        // No parent
        $this->assertTrue($addComponent->invoke($b, 'php'));
        $code = $components->getValue($b);
        $this->assertSame(1, count($code));
        $this->assertTrue(array_key_exists('php', $code));
        $code = $code['php'];
        $this->printCode($code);
        $this->assertTrue(Text::stringContains($code, 'namespace Custom\Widgets\bar;'));
        $this->assertTrue(Text::stringContains($code, 'parent::__construct($attrs);'));
        $this->assertTrue(Text::stringContains($code, 'class foo extends \RightNow\Libraries\Widget\Base'));

        // Parent with additional protected methods
        $setParent->invoke($b, Registry::getWidgetPathInfo('standard/input/SelectionInput'));
        $this->assertTrue($addComponent->invoke($b, 'php'));
        $code = $components->getValue($b);
        $this->assertSame(1, count($code));
        $this->assertTrue(array_key_exists('php', $code));
        $code = $code['php'];
        $this->printCode($code);
        $this->assertTrue(Text::stringContains($code, 'public function outputSelected($key)'));
        $this->assertTrue(Text::stringContains($code, 'public function outputChecked($currentIndex)'));

        // Parent
        $setParent->invoke($b, Registry::getWidgetPathInfo('standard/utils/AnnouncementText'));
        $this->assertTrue($addComponent->invoke($b, 'php'));
        $code = $components->getValue($b);
        $this->assertSame(1, count($code));
        $this->assertTrue(array_key_exists('php', $code));
        $code = $code['php'];
        $this->printCode($code);
        $this->assertTrue(Text::stringContains($code, 'namespace Custom\Widgets\bar;'));
        $this->assertTrue(Text::stringContains($code, 'parent::__construct($attrs);'));
        $this->assertTrue(Text::stringContains($code, 'class foo extends \RightNow\Widgets\AnnouncementText'));

        // Parent with protected methods
        $setParent->invoke($b, Registry::getWidgetPathInfo('standard/input/TextInput'));
        $this->assertTrue($addComponent->invoke($b, 'php'));
        $code = $components->getValue($b);
        $this->assertSame(1, count($code));
        $this->assertTrue(array_key_exists('php', $code));
        $code = $code['php'];
        $this->printCode($code);
        $this->assertTrue(Text::stringContains($code, 'public function outputConstraints()'));
        $this->assertTrue(Text::stringContains($code, 'protected function determineDisplayType'));

        // Parent with AJAX handler (local handler shouldn't be generated)
        $attrs = Documenter::getWidgetAttributes(Registry::getWidgetPathInfo('standard/utils/EmailAnswerLink'));
        $this->assertTrue($setAttrs->invoke($b, $attrs));
        $setParent->invoke($b, Registry::getWidgetPathInfo('standard/utils/EmailAnswerLink'));
        $this->assertTrue($addComponent->invoke($b, 'php'));
        $code = $components->getValue($b);
        $this->assertSame(1, count($code));
        $this->assertTrue(array_key_exists('php', $code));
        $code = $code['php'];
        $this->printCode($code);
        $this->assertTrue(Text::stringContains($code, 'static function emailAnswer($parameters)'));
        $this->assertFalse(Text::stringContains($code, 'function handle_send_email_ajax('));

        // Single AJAX
        $setAttrs->invoke($b, array(
            'banana' => (object) array(
                'name' => 'human readable',
                'description' => 'description',
                'type' => 'ajax',
                'default' => '/ci/ajax/widget',
            ),
        ));
        $this->assertTrue($addComponent->invoke($b, 'php'));
        $code = $components->getValue($b);
        $this->assertSame(1, count($code));
        $this->assertTrue(array_key_exists('php', $code));
        $code = $code['php'];
        $this->printCode($code);
        $this->assertTrue(Text::stringContains($code, 'parent::__construct($attrs);'));
        $this->assertTrue(Text::stringContains($code, 'setAjaxHandlers'));
        $this->assertTrue(Text::stringContains($code, '\'banana\''));
        $this->assertTrue(Text::stringContains($code, '\'handle_banana\''));
        $this->assertTrue(Text::stringContains($code, 'function handle_banana($params)'));

        // Multiple AJAX
        $setAttrs->invoke($b, array(
            'banana' => (object) array(
                'name' => 'human readable',
                'description' => 'description',
                'type' => 'ajax',
                'default' => '/ci/ajax/widget',
            ),
            'green_black_ajax' => (object) array(
                'name' => 'name',
                'description' => 'desc',
                'type' => 'ajax',
                'default' => '/ci/ajax/widget',
            ),
            'noHandler' => (object) array(
                'name' => 'name',
                'description' => 'desc',
                'type' => 'ajax',
                'default' => '/cc/myCustom/foo',
            ),
        ));
        $this->assertTrue($addComponent->invoke($b, 'php'));
        $code = $components->getValue($b);
        $this->assertSame(1, count($code));
        $this->assertTrue(array_key_exists('php', $code));
        $code = $code['php'];
        $this->printCode($code);
        $this->assertTrue(Text::stringContains($code, 'parent::__construct($attrs);'));
        $this->assertTrue(Text::stringContains($code, 'setAjaxHandlers'));
        $this->assertTrue(Text::stringContains($code, '\'banana\''));
        $this->assertTrue(Text::stringContains($code, '\'handle_banana\''));
        $this->assertTrue(Text::stringContains($code, 'function handle_banana($params)'));
        $this->assertTrue(Text::stringContains($code, '\'green_black_ajax\''));
        $this->assertTrue(Text::stringContains($code, '\'handle_green_black_ajax\''));
        $this->assertTrue(Text::stringContains($code, 'function handle_green_black_ajax($params)'));
        $this->assertFalse(Text::stringContains($code, 'noHandler'));

        // NS shouldn't be jacked up when given a dir structure
        $b = $builder->newInstance('bananas', 'kele/sowa/bissa/boloko');
        $this->assertTrue($addComponent->invoke($b, 'php'));
        $code = $components->getValue($b);
        $this->assertSame(1, count($code));
        $this->assertTrue(array_key_exists('php', $code));
        $code = $code['php'];
        $this->printCode($code);
        $this->assertTrue(Text::stringContains($code, 'namespace Custom\Widgets\kele\sowa\bissa\boloko;'));
        $this->assertTrue(Text::stringContains($code, 'parent::__construct($attrs);'));
        $this->assertTrue(Text::stringContains($code, 'class bananas extends \RightNow\Libraries\Widget\Base'));
    }

    function testJavascript() {
        list(
            $builder,
            $components,
            $addComponent,
            $setParent,
            $setAttrs
            ) = $this->reflect('components', 'method:addComponent', 'method:setParent', 'method:setAttributes');

        // Invalid
        $b = $builder->newInstance('foo', 'bar');
        $this->assertFalse($addComponent->invoke($b, 'javascript'));

        // No parent
        $this->assertTrue($addComponent->invoke($b, 'js'));
        $code = $components->getValue($b);
        $this->assertSame(1, count($code));
        $this->assertTrue(array_key_exists('js', $code));
        $code = $code['js'];
        $this->printCode($code);
        $this->assertTrue(Text::stringContains($code, "RightNow.namespace('Custom.Widgets.bar.foo');"));
        $this->assertTrue(Text::stringContains($code, "Custom.Widgets.bar.foo = RightNow.Widgets.extend"));
        $this->assertFalse(Text::stringContains($code, 'this.parent'));
        $this->assertTrue(Text::endsWith($code, "}\n});"));

        // Parent: no override
        $setParent->invoke($b, Registry::getWidgetPathInfo('standard/utils/EmailAnswerLink'));
        $this->assertTrue($addComponent->invoke($b, 'js'));
        $code = $components->getValue($b);
        $this->assertSame(1, count($code));
        $this->assertTrue(array_key_exists('js', $code));
        $code = $code['js'];
        $this->printCode($code);
        $this->assertTrue(Text::stringContains($code, "RightNow.namespace('Custom.Widgets.bar.foo');"));
        $this->assertTrue(Text::stringContains($code, "Custom.Widgets.bar.foo = RightNow.Widgets.EmailAnswerLink.extend"));
        $this->assertTrue(Text::stringContains($code, 'overrides:'));
        $this->assertTrue(Text::stringContains($code, 'this.parent()'));
        $this->assertTrue(Text::stringContains($code, 'constructor:'));
        $this->assertTrue(Text::stringContains($code, '_onEmailLinkClick:'));
        $this->assertTrue(Text::stringContains($code, '_closeDialog:'));
        $this->assertTrue(Text::stringContains($code, '_submitClicked:'));
        $this->assertTrue(Text::stringContains($code, '_validateFormData:'));
        $this->assertTrue(Text::stringContains($code, '_validateEmailAddress:'));
        $this->assertTrue(Text::stringContains($code, '_submitRequest:'));
        $this->assertTrue(Text::stringContains($code, '_onResponseReceived:'));
        $this->assertTrue(Text::stringContains($code, '_addErrorMessage:'));
        $this->assertTrue(Text::endsWith($code, "}\n});"));

        // Parent: override
        $setParent->invoke($b, Registry::getWidgetPathInfo('standard/utils/EmailAnswerLink'));
        $this->assertTrue($addComponent->invoke($b, 'js', true));
        $code = $components->getValue($b);
        $this->assertSame(1, count($code));
        $this->assertTrue(array_key_exists('js', $code));
        $code = $code['js'];
        $this->printCode($code);
        $this->assertTrue(Text::stringContains($code, "RightNow.namespace('Custom.Widgets.bar.foo');"));
        $this->assertTrue(Text::stringContains($code, "Custom.Widgets.bar.foo = RightNow.Widgets.extend"));
        $this->assertFalse(Text::stringContains($code, 'overrides:'));
        $this->assertTrue(Text::stringContains($code, 'constructor:'));
        $this->assertFalse(Text::stringContains($code, 'this.parent()'));
        $this->assertTrue(Text::endsWith($code, "}\n});"));

        // Single AJAX attr
        $parent = Registry::getWidgetPathInfo('standard/knowledgebase/SearchSuggestions');
        $setParent->invoke($b, $parent);
        $setAttrs->invoke($b, array(
            'banana' => (object) array(
                'name' => 'human readable',
                'description' => 'description',
                'type' => 'ajax',
                'default' => '/ci/ajax/widget',
            ),
        ));
        $this->assertTrue($addComponent->invoke($b, 'js'));
        $code = $components->getValue($b);
        $code = $code['js'];
        $this->printCode($code);
        $this->assertTrue(Text::stringContains($code, "getBanana"));
        $this->assertTrue(Text::stringContains($code, "bananaCallback"));
        $this->assertTrue(Text::stringContains($code, "RightNow.Ajax.makeRequest(this.data.attrs.banana,"));
        $this->assertTrue(Text::endsWith($code, "}\n});"));

        // Multiple AJAX attrs
        $setAttrs->invoke($b, array(
            'banana' => (object) array(
                'name' => 'human readable',
                'description' => 'description',
                'type' => 'ajax',
                'default' => '/ci/ajax/widget',
            ),
            'green_black_ajax' => (object) array(
                'name' => 'name',
                'description' => 'desc',
                'type' => 'ajax',
                'default' => '/ci/ajax/widget',
            ),
            'noHandler' => (object) array(
                'name' => 'name',
                'description' => 'desc',
                'type' => 'ajax',
                'default' => '/cc/myCustom/foo',
            ),
        ));
        $this->assertTrue($addComponent->invoke($b, 'js'));
        $code = $components->getValue($b);
        $code = $code['js'];
        $this->printCode($code);
        $this->assertTrue(Text::stringContains($code, "getBanana"));
        $this->assertTrue(Text::stringContains($code, "bananaCallback"));
        $this->assertTrue(Text::stringContains($code, "RightNow.Ajax.makeRequest(this.data.attrs.banana,"));
        $this->assertTrue(Text::stringContains($code, "getGreen_black_ajax"));
        $this->assertTrue(Text::stringContains($code, "bananaCallback"));
        $this->assertTrue(Text::stringContains($code, "RightNow.Ajax.makeRequest(this.data.attrs.green_black_ajax,"));
        $this->assertTrue(Text::stringContains($code, "RightNow.Ajax.makeRequest(this.data.attrs.noHandler,"));
        $this->assertTrue(Text::endsWith($code, "}\n});"));

        $setAttrs->invoke($b, array());

        // Single JS view
        $parent = Registry::getWidgetPathInfo('standard/knowledgebase/SearchSuggestions');
        $setParent->invoke($b, $parent);
        $this->assertTrue($addComponent->invoke($b, 'jsView'));
        $this->assertTrue($addComponent->invoke($b, 'js'));
        $code = $components->getValue($b);
        $code = $code['js'];
        $this->printCode($code);
        $this->assertTrue(Text::stringContains($code, "renderView"));
        $this->assertTrue(Text::stringContains($code, "new EJS({text: this.getStatic().templates.view}).render({"));
        $this->assertTrue(Text::endsWith($code, "}\n});"));

        // Multiple JS views
        $parent = Registry::getWidgetPathInfo('standard/input/FileAttachmentUpload');
        $setParent->invoke($b, $parent);
        $this->assertTrue($addComponent->invoke($b, 'jsView'));
        $this->assertTrue($addComponent->invoke($b, 'js'));
        $code = $components->getValue($b);
        $code = $code['js'];
        $this->printCode($code);
        $this->assertTrue(Text::stringContains($code, "renderAttachmentItem"));
        $this->assertTrue(Text::stringContains($code, "new EJS({text: this.getStatic().templates.attachmentItem}).render({"));
        $this->assertTrue(Text::stringContains($code, "renderError"));
        $this->assertTrue(Text::stringContains($code, "new EJS({text: this.getStatic().templates.error}).render({"));
        $this->assertTrue(Text::stringContains($code, "renderMaxMessage"));
        $this->assertTrue(Text::stringContains($code, "new EJS({text: this.getStatic().templates.maxMessage}).render({"));
        $this->assertTrue(Text::endsWith($code, "}\n});"));


        // Overrides + JS views + AJAX
        $setAttrs->invoke($b, array(
            'banana' => (object) array(
                'name' => 'human readable',
                'description' => 'description',
                'type' => 'ajax',
                'default' => '/ci/ajax/widget',
            ),
            'green_black_ajax' => (object) array(
                'name' => 'name',
                'description' => 'desc',
                'type' => 'ajax',
                'default' => '/ci/ajax/widget',
            ),
            'noHandler' => (object) array(
                'name' => 'name',
                'description' => 'desc',
                'type' => 'ajax',
                'default' => '/cc/myCustom/foo',
            ),
        ));
        $this->assertTrue($addComponent->invoke($b, 'js', true));
        $code = $components->getValue($b);
        $code = $code['js'];
        $this->printCode($code);
        $this->assertTrue(Text::stringContains($code, "getBanana"));
        $this->assertTrue(Text::stringContains($code, "bananaCallback"));
        $this->assertTrue(Text::stringContains($code, "RightNow.Ajax.makeRequest(this.data.attrs.banana,"));
        $this->assertTrue(Text::stringContains($code, "getGreen_black_ajax"));
        $this->assertTrue(Text::stringContains($code, "bananaCallback"));
        $this->assertTrue(Text::stringContains($code, "RightNow.Ajax.makeRequest(this.data.attrs.green_black_ajax,"));
        $this->assertTrue(Text::stringContains($code, "renderAttachmentItem"));
        $this->assertTrue(Text::stringContains($code, "new EJS({text: this.getStatic().templates.attachmentItem}).render({"));
        $this->assertTrue(Text::stringContains($code, "renderError"));
        $this->assertTrue(Text::stringContains($code, "new EJS({text: this.getStatic().templates.error}).render({"));
        $this->assertTrue(Text::stringContains($code, "renderMaxMessage"));
        $this->assertTrue(Text::stringContains($code, "new EJS({text: this.getStatic().templates.maxMessage}).render({"));
        $this->assertTrue(Text::endsWith($code, "}\n});"));
    }

    function testJSView() {
        list(
            $builder,
            $components,
            $addComponent,
            $setParent,
            ) = $this->reflect('components', 'method:addComponent', 'method:setParent');

        $b = $builder->newInstance('banana', 'plants/herbaceous');

        // No parent
        $this->assertTrue($addComponent->invoke($b, 'jsView'));
        $code = $components->getValue($b);
        $this->assertSame(1, count($code));
        $this->assertTrue(array_key_exists('jsView', $code));
        $code = $code['jsView'];
        $this->assertIsA($code, 'array');
        $this->assertSame(1, count($code));
        $this->assertIdentical('', $code['view']);

        // Parent
        $parent = Registry::getWidgetPathInfo('standard/knowledgebase/SearchSuggestions');
        $setParent->invoke($b, $parent);
        $this->assertTrue($addComponent->invoke($b, 'jsView'));
        $code = $components->getValue($b);
        $this->assertSame(1, count($code));
        $this->assertTrue(array_key_exists('jsView', $code));
        $code = $code['jsView'];
        $this->assertIsA($code, 'array');
        $this->assertSame(1, count($code));
        $code = $code['view'];
        $this->printCode($code);
        $html = SimpleHtmlDom\str_get_html(file_get_contents($parent->absolutePath . '/view.ejs'), true, true, SimpleHtmlDom\Defines::DEFAULT_TARGET_CHARSET, false);
        foreach ($html->find('rn:block') as $parentBlock) {
            $this->assertTrue(Text::stringContains($code, "<!--\n<rn:block id='{$parentBlock->id}'>"));
        }

        // Parent with empty view.ejs
        $file = "WidgetBuilderTestEjs/1.0/view.ejs";
        $this->writeTempFile($file, '');
        $parent = new \RightNow\Internal\Libraries\Widget\PathInfo('custom', "custom/WidgetBuilderTestEjs", $this->getTestDir() . 'WidgetBuilderTestEjs', '1.0');
        $setParent->invoke($b, $parent);
        $this->assertTrue($addComponent->invoke($b, 'jsView'));
        $code = $components->getValue($b);
        $this->assertSame(1, count($code));
        $this->assertTrue(array_key_exists('jsView', $code));
        $code = $code['jsView'];
        $this->assertIsA($code, 'array');
        $this->assertSame(1, count($code));
        $code = $code['view'];
        $this->assertIdentical('', $code);
        $this->eraseTempDir();

        // Parent w/ multiple JS views
        $parent = Registry::getWidgetPathInfo('standard/input/FileAttachmentUpload');
        $setParent->invoke($b, $parent);
        $this->assertTrue($addComponent->invoke($b, 'jsView'));
        $code = $components->getValue($b);
        $this->assertSame(1, count($code));
        $this->assertTrue(array_key_exists('jsView', $code));
        $code = $code['jsView'];
        $this->assertIsA($code, 'array');
        $this->assertSame(4, count($code));
        foreach ($code as $name => $view) {
            $this->printCode($view);
            $html = SimpleHtmlDom\str_get_html(file_get_contents($parent->absolutePath . "/$name.ejs"), true, true, SimpleHtmlDom\Defines::DEFAULT_TARGET_CHARSET, false);
            foreach ($html->find('rn:block') as $parentBlock) {
                $this->assertTrue(Text::stringContains($view, "<!--\n<rn:block id='{$parentBlock->id}'>"));
            }
        }
        // Override Parent w/ multiple JS views
        $parent = Registry::getWidgetPathInfo('standard/input/FileAttachmentUpload');
        $setParent->invoke($b, $parent);
        $this->assertTrue($addComponent->invoke($b, 'jsView', true));
        $code = $components->getValue($b);
        $this->assertSame(1, count($code));
        $this->assertTrue(array_key_exists('jsView', $code));
        $code = $code['jsView'];
        $this->assertIsA($code, 'array');
        $this->assertSame(4, count($code));
        foreach ($code as $name => $view) {
            $this->assertIdentical('', $view);
        }
    }

    function testGetJSViews() {
        list($builder, $getJSViews) = $this->reflect('method:getJSViews');
        $b = $builder->newInstance('banana', 'plants/herbaceous');
        $parent = Registry::getWidgetPathInfo('standard/knowledgebase/SearchSuggestions');
        $parentViews = Widgets::getJavaScriptTemplates($parent->absolutePath);
        $return = $getJSViews->invoke($b, $parent, false);
        // if we are not overriding the view, we should get rn:blocks
        foreach ($parentViews as $name => $content) {
            $html = SimpleHtmlDom\str_get_html($parentViews[$name], true, true, SimpleHtmlDom\Defines::DEFAULT_TARGET_CHARSET, false);
            foreach ($html->find('rn:block') as $parentBlock) {
                $this->assertTrue(Text::stringContains($return[$name], "<!--\n<rn:block id='{$parentBlock->id}'>"));
            }
        }

        // if the view is overwritten, the view should be empty
        $return = $getJSViews->invoke($b, $parent, true);
        $this->assertSame($return['view'], '');

        Registry::initialize(true);

        file_put_contents(CORE_WIDGET_FILES . 'standard/utils/Blank/whatever.ejs', "<rn:block id='theRepeatedBlock'/>\n<rn:block id='theRepeatedBlock'/>\n<rn:block id='theRepeatedBlock'/>");

        $b = $builder->newInstance('banana', 'plants/herbaceous');
        $parent = Registry::getWidgetPathInfo('standard/utils/Blank');
        $return = $getJSViews->invoke($b, $parent, false);

        $this->assertIdentical($return, array('whatever' => "<!--\n<rn:block id='theRepeatedBlock'>\n\n</rn:block>\n-->\n\n"));

        unlink(CORE_WIDGET_FILES . 'standard/utils/Blank/whatever.ejs');

        Registry::initialize(true);
    }

    function testView() {
        list(
            $builder,
            $components,
            $addComponent,
            $setParent,
            ) = $this->reflect('components', 'method:addComponent', 'method:setParent');

        $b = $builder->newInstance('banana', 'plants/herbaceous');

        // No parent
        $this->assertTrue($addComponent->invoke($b, 'view'));
        $code = $components->getValue($b);
        $this->assertSame(1, count($code));
        $this->assertTrue(array_key_exists('view', $code));
        $this->assertFalse(array_key_exists('viewPartials', $code));
        $viewCode = $code['view'];
        $this->printCode($viewCode);
        $this->assertStringContains($viewCode, 'instanceID');
        $this->assertStringContains($viewCode, 'classList');

        // Parent
        $parent = Registry::getWidgetPathInfo('standard/utils/EmailAnswerLink');
        $setParent->invoke($b, $parent);
        $this->assertTrue($addComponent->invoke($b, 'view'));
        $code = $components->getValue($b);
        $this->assertSame(2, count($code));
        $this->assertTrue(array_key_exists('view', $code));
        $this->assertTrue(array_key_exists('viewPartials', $code));
        $viewCode = $code['view'];
        $this->printCode($viewCode);
        $html = SimpleHtmlDom\str_get_html(file_get_contents($parent->view), true, true, SimpleHtmlDom\Defines::DEFAULT_TARGET_CHARSET, false);
        foreach ($html->find('rn:block') as $parentBlock) {
            $this->assertStringContains($viewCode, "<!--\n<rn:block id='{$parent->className}-{$parentBlock->id}'>");
        }

        // // Parent w/ sub-widgets
        $parent = Registry::getWidgetPathInfo('standard/search/AdvancedSearchDialog');
        $setParent->invoke($b, $parent);
        $this->assertTrue($addComponent->invoke($b, 'view'));
        $code = $components->getValue($b);
        $this->assertSame(2, count($code));
        $this->assertTrue(array_key_exists('view', $code));
        $this->assertTrue(array_key_exists('viewPartials', $code));
        $viewCode = $code['view'];
        $this->printCode($viewCode);
        $html = SimpleHtmlDom\str_get_html(file_get_contents($parent->view), true, true, SimpleHtmlDom\Defines::DEFAULT_TARGET_CHARSET, false);
        foreach ($html->find('rn:block') as $parentBlock) {
            $this->assertStringContains($viewCode, "<!--\n<rn:block id='{$parent->className}-{$parentBlock->id}'>");
        }

        $html = SimpleHtmlDom\str_get_html($viewCode, true, true, SimpleHtmlDom\Defines::DEFAULT_TARGET_CHARSET, false);
        foreach ($html->find('rn:block') as $block) {
            $this->assertTrue(
                // Yeeeeaaaaaaahhh
                Text::stringContains($block->id, 'AdvancedSearchDialog') ||
                Text::stringContains($block->id, 'KeywordText') ||
                Text::stringContains($block->id, 'WebSearchSort') ||
                Text::stringContains($block->id, 'WebSearchType') ||
                Text::stringContains($block->id, 'SortList') ||
                Text::stringContains($block->id, 'FilterDropdown') ||
                Text::stringContains($block->id, 'SearchTypeList') ||
                Text::stringContains($block->id, 'ProductCategorySearchFilter')
            );
        }

        // Override parent's view
        $parent = Registry::getWidgetPathInfo('standard/utils/EmailAnswerLink');
        $setParent->invoke($b, $parent);
        $this->assertTrue($addComponent->invoke($b, 'view', true));
        $code = $components->getValue($b);
        $this->assertSame(2, count($code));
        $this->assertTrue(array_key_exists('view', $code));
        $this->assertTrue(array_key_exists('viewPartials', $code));
        $viewCode = $code['view'];
        $this->printCode($viewCode);
        $this->assertIdentical("<? /* Overriding EmailAnswerLink's view */ ?>\n<div id=\"rn_<?= \$this->instanceID ?>\" class=\"<?= \$this->classList ?>\">\nbanana\n</div>", $viewCode);

        // Extend parent's view but parent's view is empty
        list(
            $builder,
            $parent,
            $setView
            ) = $this->reflect('parent', 'method:setView');

        $b = $builder->newInstance('banana', 'trees/coniferous');

        $file = "WidgetBuilderTest/1.0/view.php";
        $this->writeTempFile($file, '');
        $parent->setValue($b, new \RightNow\Internal\Libraries\Widget\PathInfo('custom', "custom/WidgetBuilderTest", $this->getTestDir() . 'WidgetBuilderTest', '1.0'));
        $this->assertIdentical('', $setView->invoke($b));
        $this->eraseTempDir();

        // Parent doesn't have a view file
        $this->assertIdentical('', $setView->invoke($b));

        Registry::initialize(true);

        file_put_contents(CORE_WIDGET_FILES . 'standard/utils/Blank/view.php', "<rn:block id='theRepeatedBlockView'/>\n<rn:block id='theRepeatedBlockView'/>\n<rn:block id='theRepeatedBlockView'/>");
        file_put_contents(CORE_WIDGET_FILES . 'standard/utils/Blank/partial.html.php', "<rn:block id='theRepeatedBlockPartial'/>\n<rn:block id='theRepeatedBlockPartial'/>\n<rn:block id='theRepeatedBlockPartial'/>");

        $b = $builder->newInstance('banana', 'plants/orchid');
        $blankWidget = Registry::getWidgetPathInfo('standard/utils/Blank');
        $parent->setValue($b, $blankWidget);
        $return = $setView->invoke($b);

        $this->assertIdentical($return, "<!--\n<rn:block id='Blank-theRepeatedBlockView'>\n\n</rn:block>\n-->\n\n");
        $this->assertIdentical($components->getValue($b), array('viewPartials' => array('partial.html.php' => "<!--\n<rn:block id='theRepeatedBlockPartial'>\n\n</rn:block>\n-->\n\n")));

        unlink(CORE_WIDGET_FILES . 'standard/utils/Blank/view.php');
        unlink(CORE_WIDGET_FILES . 'standard/utils/Blank/partial.html.php');

        Registry::initialize(true);
    }

    function testViewWithPartials() {
        list(
            $builder,
            $components,
            $addComponent,
            $setParent,
            ) = $this->reflect('components', 'method:addComponent', 'method:setParent');

        $b = $builder->newInstance('banana', 'plants/herbaceous');
        $parent = Registry::getWidgetPathInfo('standard/feedback/AnswerFeedback');
        $setParent->invoke($b, $parent);

        $this->assertTrue($addComponent->invoke($b, 'view'));
        $code = $components->getValue($b);
        $this->assertSame(2, count($code));
        $this->assertTrue(array_key_exists('view', $code));
        $this->assertIsA($code['viewPartials'], 'array');

        foreach ($code['viewPartials'] as $name => $content) {
            $html = SimpleHtmlDom\str_get_html(file_get_contents($parent->absolutePath . '/' . $name), true, true, SimpleHtmlDom\Defines::DEFAULT_TARGET_CHARSET, false);
            foreach ($html->find('rn:block') as $parentBlock) {
                $this->assertStringContains($content, "<!--\n<rn:block id='{$parentBlock->id}'>");
            }
        }
    }

    function testMultiViewHierarchyWithPartials() {
        list(
            $builder,
            $components,
            $addComponent,
            $setParent,
            ) = $this->reflect('components', 'method:addComponent', 'method:setParent');

        $b = $builder->newInstance('banana', 'plants/graminoids');
        $parent = Registry::getWidgetPathInfo('custom/feedback/ExtendedCustomAnswerFeedback');
        $grandMother = Registry::getWidgetPathInfo('standard/feedback/AnswerFeedback');
        $setParent->invoke($b, $parent);

        $this->assertTrue($addComponent->invoke($b, 'view'));
        $code = $components->getValue($b);
        $this->assertSame(2, count($code));
        $this->assertTrue(array_key_exists('view', $code));
        $this->assertIsA($code['viewPartials'], 'array');

        foreach ($code['viewPartials'] as $name => $content) {
            $html = SimpleHtmlDom\str_get_html(file_get_contents($grandMother->absolutePath . '/' . $name), true, true, SimpleHtmlDom\Defines::DEFAULT_TARGET_CHARSET, false);
            $rnBlocks = $html->find('rn:block');
            for($i = 0; $i < count($rnBlocks); $i++) {
                if($i === 0)
                    $this->assertStringContains($content, "<!--\n<rn:block id='{$rnBlocks[$i]->id}'>");
            }
        }
    }

    function testSetYUIModules() {
        list(
            $builder,
            $yuiModules,
            $method
            ) = $this->reflect('yuiModules', 'method:setYUIModules');

        $b = $builder->newInstance('banana', 'plants/herbaceous');

        // Not an array
        $method->invoke($b, null);
        $mods = $yuiModules->getValue($b);
        $this->assertIdentical(array(), $mods);

        $method->invoke($b, array('foo', 'banana'));
        $mods = $yuiModules->getValue($b);
        $this->assertIdentical(array('foo', 'banana'), $mods);
    }

    function testSetInfo() {
        list(
            $builder,
            $info,
            $method
            ) = $this->reflect('info', 'method:setInfo');

        $b = $builder->newInstance('banana', 'plants/herbaceous');

        // Not an array
        $method->invoke($b, null);
        $setInfo = $info->getValue($b);
        $this->assertIdentical(array(), $setInfo);

        $method->invoke($b, array('foo', 'banana'));
        $setInfo = $info->getValue($b);
        $this->assertIdentical(array('foo', 'banana'), $setInfo);
    }

    function testSetAttributes() {
        list(
            $builder,
            $attributes,
            $errors,
            $method
            ) = $this->reflect('attributes', 'errors', 'method:setAttributes');

        $b = $builder->newInstance('banana', 'plants/herbaceous');

        $return = $method->invoke($b, array());
        $this->assertTrue($return);
        $this->assertIdentical(array(), $errors->getValue($b));

        $return = $method->invoke($b, array('banana' => array('name' => 'bananas', 'description' => 'hey', 'type' => 'string')));
        $this->assertTrue($return);
        $this->assertIdentical(array(), $errors->getValue($b));
        $attrs = $attributes->getValue($b);
        $this->assertIsA($attrs['banana'], 'stdClass');

        $return = $method->invoke($b, array('banana' => array('name' => 'bananas', 'type' => 'string')));
        $this->assertFalse($return);
        $this->assertIdentical(1, count($errors->getValue($b)));
    }

    function testGetBlockBoilerPlate() {
        list(
            $builder,
            $method
            ) = $this->reflect('method:getBlockBoilerPlate');

        $b = $builder->newInstance('banana', 'plants/herbaceous');

        $html = SimpleHtmlDom\str_get_html("<rn:block id='whatever1'/><rn:block id='whatever1'/><rn:block id='whatever2'/>", true, true, SimpleHtmlDom\Defines::DEFAULT_TARGET_CHARSET, false);

        $return = $method->invokeArgs($b, array($html));
        $this->assertIdentical("<!--\n<rn:block id='whatever1'>\n\n</rn:block>\n-->\n\n<!--\n<rn:block id='whatever2'>\n\n</rn:block>\n-->\n\n", $return);

        $return = $method->invokeArgs($b, array($html, 'moar-'));
        $this->assertIdentical("<!--\n<rn:block id='moar-whatever1'>\n\n</rn:block>\n-->\n\n<!--\n<rn:block id='moar-whatever2'>\n\n</rn:block>\n-->\n\n", $return);
    }
}

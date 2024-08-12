<?php
\RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Url,
    RightNow\Utils\Text,
    RightNow\Libraries\Widget\Base,
    RightNow\Libraries\Widget\Attribute,
    RightNow\Libraries\Widget\ClassList;

class BaseChild extends Base {
	function classList() {
		return $this->classList->classes;
	}

    function getCollapsedAttributes() {
        return parent::getCollapsedAttributes();
    }

    function getView(&$data) {
        echo $this->data['attrs']['value'];
    }

    function loadHelper($helper) {
        parent::loadHelper($helper);
    }

    function helper($helper) {
        return parent::helper($helper);
    }
}
class BaseChildChild extends BaseChild {}
class BaseChildChildChild extends BaseChildChild {}

class BaseAjaxChild extends BaseChild{
    function __construct($attrs){
        parent::__construct($attrs);
        $this->setAjaxHandlers(array(
            'ajax_attribute' => array(
                'method' => 'methodName',
                'clickstream' => 'clickstreamEntry',
            ),
        ));
    }
}

class BaseWidgetTest extends CPTestCase {
	function testClassList() {
		$a = new BaseChild(array());
		$this->assertIdentical(array('rn_BaseChild'), $a->classList());
		$b = new BaseChildChild(array());
		$this->assertIdentical(array('rn_BaseChildChild', 'rn_BaseChild'), $b->classList());
		$c = new BaseChildChildChild(array());
		$this->assertIdentical(array('rn_BaseChildChildChild', 'rn_BaseChildChild', 'rn_BaseChild'), $c->classList());
	}

    function testBaseSettersAndGetters() {
        $obj = new BaseChild(array());
        $obj->data = array('1234');
        $this->assertEqual($obj->data, array('1234'));

        try {
            $obj->someField = true;
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
        }
    }

    function testAddHeadContent() {
        $baseChild = new BaseChild(array());
        $baseChild->CI->clientLoader = new \RightNow\Libraries\ClientLoader(new \RightNow\Internal\Libraries\ProductionModeClientLoaderOptions());
        $baseChild->addStylesheet('/rnt/rnw/css/admin.css');

        $linkTag = $baseChild->CI->clientLoader->createCSSTag('/rnt/rnw/css/ma.css');
        $baseChild->addHeadContent($linkTag);

        $headerContent = $baseChild->CI->clientLoader->getHeadContent();
        if (preg_match_all('/^\<link.*href=["\'](.+?)["\']/m', $headerContent, $matches)) {
            $this->assertSame("/rnt/rnw/css/admin.css", $matches[1][0]);
            $this->assertSame("/rnt/rnw/css/ma.css", $matches[1][1]);
        }
        else {
            $this->fail();
        }
    }

    function testJavaScript() {
        $baseChild = new BaseChild(array());
        $baseChild->CI->clientLoader = new \RightNow\Libraries\ClientLoader(new \RightNow\Internal\Libraries\ProductionModeClientLoaderOptions());

        // add include
        $value = $baseChild->addJavaScriptInclude("/path/to/somefile.js");
        $jsValue = "<script src='/path/to/somefile.js'></script>";
        $this->assertSame($jsValue, $value);

        // add inline code
        $baseChild->CI->clientLoader->addJavaScriptInLine("var test = true;", true);
        $value = $baseChild->CI->clientLoader->getAdditionalJavaScriptReferences();
        $this->assertSame(Text::stringContains($value, "var test = true;"), true);

        // add another file as inline code
        $testJavaScriptFile = CPCORE . 'Libraries/tests/test.js';
        file_put_contents($testJavaScriptFile, "var anotherTest = false;");
        $baseChild->addJavaScriptInline($testJavaScriptFile);

        $value = $baseChild->CI->clientLoader->getAdditionalJavaScriptReferences();
        $jsValue .= "\n<script>\nvar test = true;\nvar anotherTest = false;\n</script>\n";
        $this->assertSame($jsValue, $value);
        unlink($testJavaScriptFile);
    }

    function testDataArray() {
        $baseChild = new BaseChild(array());
        $this->assertSame($baseChild->getDataArray(), null);

        $dataArray = $baseChild->initDataArray();
        $this->assertSame($baseChild->getDataArray(), $dataArray);

        // make sure the following keys were set
        foreach (array('attrs', 'info', 'js', 'name') as $key) {
            $this->assertSame(array_key_exists($key, $dataArray), true);
        }
    }

    function testParameter() {
        $baseChild = new BaseChild(array());
        $this->assertSame($baseChild->getParameter(), array());

        $baseChild->setParameter("key", "value");
        $this->assertSame($baseChild->getParameter(), array("key" => "value"));
        $this->assertSame($baseChild->getParameter("key"), "value");

        $baseChild->setParameter("arrayKey", array("key" => "value"));
        $this->assertSame($baseChild->getParameter("arrayKey"), array("key" => "value"));
    }

    function testGetJS() {
        $baseChild = new BaseChild(array());
        $this->assertSame($baseChild->getJS(), array());

        $baseChild->js = array("key" => "value", "key2" => "value2");
        $this->assertSame($baseChild->getJS("key"), "value");
        $this->assertSame($baseChild->getJS("key2"), "value2");

        $data = $baseChild->initDataArray();
        $this->assertSame($data["js"]["key"], "value");
        $this->assertSame($data["js"]["key2"], "value2");

        $data = $baseChild->getDataArray();
        $this->assertSame($data["js"]["key"], "value");
        $this->assertSame($data["js"]["key2"], "value2");
    }

    function testInfo() {
        $baseChild = new BaseChild(array());
        $this->assertSame(array_key_exists("w_id", $baseChild->getInfo()), true);

        $baseChild->setInfo("key", "value");
        $baseChild->setInfo("arrayKey", array("key1" => "value1", "key2" => "value2"));

        $this->assertSame($baseChild->getInfo("key"), "value");
        $this->assertSame($baseChild->getInfo("arrayKey"), array("key1" => "value1", "key2" => "value2"));

        $arrayData = $baseChild->getInfo("arrayKey");
        $this->assertSame(array_key_exists("key1", $arrayData), true);
        $this->assertSame(array_key_exists("key2", $arrayData), true);
        $this->assertSame($arrayData["key1"], "value1");
        $this->assertSame($arrayData["key2"], "value2");

        $data = $baseChild->initDataArray();
        $this->assertSame($data["info"]["key"], "value");
        $this->assertSame(is_array($data["info"]["arrayKey"]), true);
        $this->assertSame($data["info"]["arrayKey"]["key1"], "value1");
        $this->assertSame($data["info"]["arrayKey"]["key2"], "value2");
    }

    function testGetAttribute() {
        $initAttrs = array(
            'value' =>
                new \RightNow\Libraries\Widget\Attribute(array(
                    'name'  => 'value',
                    'type'  => 'STRING',
                    'value' => 'some value')),
        );

        $baseChild = new BaseChild($initAttrs);
        $this->assertIdentical($baseChild->getAttribute(), $initAttrs);
        $this->assertEqual($baseChild->getAttribute('value'), 'some value');

        $data = $baseChild->initDataArray();
        $this->assertEqual($data['attrs']['value'], 'some value');

        $collapsedAttrs = $baseChild->getCollapsedAttributes();
        $this->assertEqual($collapsedAttrs['value'], 'some value');
    }

    function testPath() {
        $baseChild = new BaseChild(array());
        $baseChild->setPath("path/to/widget");
        $this->assertSame($baseChild->getPath(), "path/to/widget");
    }

    function testSetViewContent() {
        $initAttrs = array(
            'value' =>
                new \RightNow\Libraries\Widget\Attribute(array(
                    'name'  => 'value',
                    'type'  => 'STRING',
                    'value' => 'some value')),
        );

        $baseChild = new BaseChild($initAttrs);
        $baseChild->setViewContent("<b><?=\$this->data['attrs']['value']?></b>");
        $viewContent = $baseChild->renderDevelopment();
        $this->assertSame("<b>some value</b>", $viewContent);
    }

    function testErrors() {
        $baseChild = new BaseChild(array());
        $baseChild->setPath("custom/sample/SampleWidget");

        try {
            // exception is the strlen is 0 for the error message
            $baseChild->reportError("");
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
        }

        $errorMessage = $baseChild->reportError("error message");
        $this->assertTrue(Text::stringContains($errorMessage, "custom/sample/SampleWidget"));
        $this->assertTrue(Text::stringContains($errorMessage, "error message"));

        $warningMessage = $baseChild->reportError("warning message", false);
        $this->assertTrue(Text::stringContains($warningMessage, "custom/sample/SampleWidget"));
        $this->assertTrue(Text::stringContains($warningMessage, "warning message"));

        $errorMessage = $baseChild->widgetError("path/to/widget", "error message");
        $this->assertTrue(Text::stringContains($errorMessage, "path/to/widget"));
        $this->assertTrue(Text::stringContains($errorMessage, "error message"));

        $warningMessage = $baseChild->widgetError("path/to/widget", "warning message", false);
        $this->assertTrue(Text::stringContains($warningMessage, "path/to/widget"));
        $this->assertTrue(Text::stringContains($warningMessage, "warning message"));
    }

    function testAjaxHandlers() {
        $baseChild = new BaseChild(array());
        $this->assertSame(is_array($baseChild->getAjaxHandlers()), true);
        $baseChild->setAjaxHandlers(array("key" => "value"));
        $this->assertSame($baseChild->getAjaxHandlers(), array());

        $baseChild = new BaseAjaxChild(array());
        $this->assertSame($baseChild->getAjaxHandlers(), array());

        $baseChild = new BaseAjaxChild(array('ajax_attribute' => new Attribute(array(
            'name' => "'ajax_attribute'",
            'value' => "'ajax_attribute'",
            'type' => "ajax",
        ))));
        $this->assertSame($baseChild->getAjaxHandlers(), array(
            'ajax_attribute' => array(
                'method' => 'methodName',
                'clickstream' => 'clickstreamEntry',
            ),
        ));
    }

    function testSetViewFunctionName() {
        $attrs = array(
            'value' =>
                new \RightNow\Libraries\Widget\Attribute(array(
                    'name'  => 'value',
                    'type'  => 'STRING',
                    'value' => 'some value')),
        );
        $baseChild = new BaseChild($attrs);
        $baseChild->initDataArray();
        $baseChild->setViewFunctionName("getView");
        $output = $baseChild->renderProduction();
        $this->assertTrue(Text::stringContains($output, "some value"));
    }

    function testLoadHelperDisplaysErrorWhenHelperIsNotFound() {
        $baseChild = new BaseChild(array());
        $result = $this->returnResultAndContent(function() use ($baseChild) {
            return $baseChild->loadHelper('no');
        });
        $this->assertNull($result[0]);
        $this->assertStringContains($result[1], 'no');
    }

    function testHelperThrowsExceptionWhenHelperIsNotFound() {
        $baseChild = new BaseChild(array());
        $baseChild->setHelper();
        $result = $this->returnResultAndContent(function() use ($baseChild) {
            try {
                $baseChild->helper('no');
            }
            catch (\Exception $e) {
                return $e->getMessage();
            }
        });
        $this->assertIsA($result[0], 'string', "Exception wasn't thrown");
        $this->assertIsA($result[1], 'string', "Error wasn't echoed");
    }

    function testSetHelper() {
        $baseChild = new BaseChild(array());
        $this->assertNull($baseChild->helper);
        $baseChild->setHelper();
        $this->assertIsA($baseChild->helper, 'RightNow\Libraries\Widget\Helper');

        $baseChild = new BaseChild(array());
        $baseChild->setPath('standard/discussion/QuestionComments');
        $baseChild->setHelper();
        $this->assertIsA($baseChild->helper, 'RightNow\Libraries\Widget\Helper');
        $this->assertSame(get_class($baseChild->helper), 'RightNow\Helpers\QuestionCommentsHelper');
    }

    function testGetParametersFromHelper() {
        $this->addUrlParameters(array('user' => 123));

        $baseChild = new BaseChild(array());
        $result = $baseChild->getParametersFromHelper('author:userFromUrl, nope:nada');
        $this->assertIdentical(array('author' => '123'), $result);

        // Custom helper over-rides
        $customHelper = CUSTOMER_FILES . 'helpers/Url.php';
        $content = <<<PHP
<?php

namespace Custom\Helpers;

class UrlHelper extends \RightNow\Libraries\Widget\Helper {
    static function userFromUrl() {
        return 'bob';
    }
}
PHP;
        umask(0);
        file_put_contents($customHelper, $content);
        $result = $baseChild->getParametersFromHelper('author:userFromUrl');
        $this->assertIdentical(array('author' => 'bob'), $result);
        unlink($customHelper);

        $this->restoreUrlParameters();
    }
}

class AttributeTest extends CPTestCase {
    function testAttribute() {
        $attribute = new Attribute(array(
            'name'          => "'foo'",
            'value'         => "'bar'",
            'type'          => "STRING",
            'default'       => "'abc'",
            'tooltip'       => "'The tooltip for the attribute'",
            'description'   => "'The description for the attribute'",
            'options'       => array('abc', 'def', 'ghi', 'bar'),
        ));

        // this will test __set_state() as it's part of the toString() output
        $codeValue = $attribute->toString();
        eval("\$newAttribute = " . $codeValue . ";");

        // The keys: name, description, tooltip, default, value and optlistId
        // do not get quoted when toString() is called, so remove the extra quote from
        // the original values for those keys
        foreach (array('name', 'description', 'tooltip', 'default', 'value', 'optlistId') as $key) {
            $this->assertEqual(trim($attribute->$key, "'"), $newAttribute->$key);
        }
        $this->assertEqual($attribute->options, $newAttribute->options);
        $this->assertEqual($attribute->type, $newAttribute->type);

        // properties with defaults
        $this->assertEqual($attribute->required, $newAttribute->required);
        $this->assertEqual($attribute->inherited, $newAttribute->inherited);
        $this->assertEqual($attribute->displaySpecialCharsInTagGallery, $newAttribute->displaySpecialCharsInTagGallery);
    }
}

class ClassListTest extends CPTestCase {
	function testConstructor() {
		$list = new ClassList;
		$this->assertIdentical(array(), $list->classes);

		$list = new ClassList(0);
		$this->assertIdentical(array(), $list->classes);

		$list = new ClassList('');
		$this->assertIdentical(array(), $list->classes);

		$list = new ClassList(array());
		$this->assertIdentical(array(), $list->classes);

		$list = new ClassList('foo bar');
		$this->assertIdentical(array('foo bar'), $list->classes);

		$list = new ClassList(' banana  ');
		$this->assertIdentical(array('banana'), $list->classes);

		$list = new ClassList('a', 'b', '', 0, ' d ');
		$this->assertIdentical(array('a', 'b', 'd'), $list->classes);

		$list = new ClassList(array('  a ', null, 'b'));
		$this->assertIdentical(array('a', 'b'), $list->classes);

		$list = new ClassList(array('a'), array());
		$this->assertIdentical(array(), $list->classes);
	}

	function testAdd() {
		$list = new ClassList;

		$list->add(null)->add(null)->add(null);
		$this->assertIdentical(array(), $list->classes);

		$list->add('foo')->add('bar')->add(' b a z ');
		$this->assertIdentical(array('foo', 'bar', 'b a z'), $list->classes);

		$list->add('foo')->add(' foo ');
		$this->assertIdentical(array('foo', 'bar', 'b a z'), $list->classes);
	}

	function testRemove() {
		$list = new ClassList(array('foo', 'bar', 'baz'));

		$list->remove(null)->remove(null)->remove(null);
		$this->assertIdentical(array('foo', 'bar', 'baz'), $list->classes);

		$list->remove('foo')->remove('bar')->remove('baz');
		$this->assertIdentical(array(), $list->classes);

		$list->add('banana')->remove(' banana ');
		$this->assertIdentical(array(), $list->classes);
	}

	function testToggle() {
		$list = new ClassList;

		$this->assertTrue($list->toggle(null));
		$this->assertIdentical(array(), $list->classes);

		$this->assertTrue($list->toggle('a'));
		$this->assertIdentical(array('a'), $list->classes);
		$this->assertFalse($list->toggle(' a '));
		$this->assertIdentical(array(), $list->classes);
	}

	function testContains() {
		$list = new ClassList;

		$this->assertFalse($list->contains(null));
		$this->assertTrue($list->add(' a ')->contains('a'));
		$this->assertFalse($list->contains('b'));
		$this->assertFalse($list->remove('a')->contains('a'));
	}

	function testToString() {
		$list = new ClassList;

		$this->assertIdentical('', $list . '');
		$list->add(' a ');
		$this->assertIdentical('a', (string) $list);
		$this->assertIdentical('', (string) $list->add('b')->add(' c ')->remove('a')->remove('c')->remove('b'));
	}
}

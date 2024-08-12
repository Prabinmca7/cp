<?
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Text,
    RightNow\Internal\Utils\Deployment\CodeWriter;

class CodeWriterTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Utils\Deployment\CodeWriter';

    function testDeleteOpeningPHP () {
        $cases = array(
            array('<?missouri', 'missouri'),
            array("    <?missouri", 'missouri'),
            array('<? missouri', ' missouri'),
            array('/<? missouri', '/<? missouri'),
            array('<?pmissouri', 'missouri'),
            array('    <?pmissouri', 'missouri'),
            array('<?p missouri', ' missouri'),
            array('/<?p missouri', '/<?php missouri'),
        );
        foreach ($cases as $case) {
            if (Text::stringContains($case[0], '<?p')) $case[0] = str_replace('?p', '?php', $case[0]);
            $this->assertIdentical($case[1], CodeWriter::deleteOpeningPHP($case[0]));
        }
    }

    function testDeleteClosingPHP () {
        $cases = array(
            array('sea?>', 'sea'),
            array('sea  ?>', 'sea  '),
            array('sea?>     ', 'sea'),
            array('sea?/>     ', 'sea?/>'),
        );
        foreach ($cases as $case) {
            $this->assertIdentical($case[1], CodeWriter::deleteClosingPHP($case[0]));
        }
    }

    function testCreateArray () {
        $this->assertIdentical("array()", CodeWriter::createArray(array()));
        $this->assertIdentical("array('foo' => 0,\n)", CodeWriter::createArray(array('foo' => 0)));
        $this->assertIdentical("array('foo' => '1bananasfoo',\n'bar' => '2bananasbar',\n)", CodeWriter::createArray(array('foo' => 1, 'bar' => 2), function ($key, $val) {
            return "'{$val}bananas{$key}'";
        }));
    }

    function testModifyPHPToAllowForCombination () {
        $cases = array(
            array("justsomecode", "namespace{ \n justsomecode\n}\n"),
            array("namespace {...}", "namespace{ \n namespace {...}\n}\n"),
            array("namespace Bananas;", "namespace Bananas{\n}\n"),
            array("namespace Bananas\People;", "namespace Bananas\People{\n}\n"),
            array("namespace Bananas;\nclass Foo{...}", "namespace Bananas{\nclass Foo{...}\n}\n"),
            array("<?\nnamespace Foo\Bar;\n echo 'hi there';", "\nnamespace Foo\Bar{\n echo 'hi there';\n}\n"),
            array("<?php\nnamespace Foo\Bar;\n echo 'hi there';", "\nnamespace Foo\Bar{\n echo 'hi there';\n}\n"),
            array("<?php\nnamespace Foo\Bar;\n echo 'hi there';?>", "\nnamespace Foo\Bar{\n echo 'hi there';\n}\n"),
            array("<?\nnamespace Foo\Bar{\n echo 'hi there';\n}", "\nnamespace Foo\Bar{\n echo 'hi there';\n}"),
            array("<?\nnamespace Foo\Bar\abc123def{\n echo 'hi there';\n}", "\nnamespace Foo\Bar\abc123def{\n echo 'hi there';\n}"),
            array("<?\nnamespace My\Cus_om\Wi_dget{\n echo 'hi there';\n}", "\nnamespace My\Cus_om\Wi_dget{\n echo 'hi there';\n}"),
            array("<?\nnamespace one\Very\long\Namespaced\custom\widget\class\path\and\class{\n echo 'hi there';\n}",
                "\nnamespace one\Very\long\Namespaced\custom\widget\class\path\and\class{\n echo 'hi there';\n}"),
        );
        foreach ($cases as $case) {
            $this->assertIdentical($case[1], CodeWriter::modifyPhpToAllowForCombination($case[0]));
        }
    }

    function testInsertAfterNamespace () {
        $this->assertIdentical("", CodeWriter::insertAfterNamespace("", "rescue"));
        $this->assertIdentical("blah", CodeWriter::insertAfterNamespace("blah", "rescue"));
        $this->assertIdentical("namespace blah", CodeWriter::insertAfterNamespace("namespace blah", "rescue"));
        $this->assertIdentical("namespace blah{rescue", CodeWriter::insertAfterNamespace("namespace blah{", "rescue"));
        $this->assertIdentical("namespace blah;rescue", CodeWriter::insertAfterNamespace("namespace blah;", "rescue"));
        $this->assertIdentical("<?\nnamespace Foo\Bar;\nrescue", CodeWriter::insertAfterNamespace("<?\nnamespace Foo\Bar;", "\nrescue"));
        $this->assertIdentical("<?\nnamespace Foo\Bar  ;\nrescue", CodeWriter::insertAfterNamespace("<?\nnamespace Foo\Bar  ;", "\nrescue"));
        $this->assertIdentical("<?\nnamespace Foo\Bar{\nrescue", CodeWriter::insertAfterNamespace("<?\nnamespace Foo\Bar{", "\nrescue"));
        $this->assertIdentical("<?\nnamespace Foo\Bar {\nrescue", CodeWriter::insertAfterNamespace("<?\nnamespace Foo\Bar {", "\nrescue"));
    }

    function testScriptTag () {
        $this->assertIdentical("<script type=\"text/javascript\" src=\"\"></script>\n", CodeWriter::scriptTag(''));
        $this->assertIdentical("<script type=\"text/javascript\" src=\"blah\"></script>\n", CodeWriter::scriptTag('blah'));
        $this->assertIdentical("<script type=\"text/javascript\" src=\" bananas/scripts \"></script>\n", CodeWriter::scriptTag(' bananas/scripts '));
    }

    function testLinkTag () {
        $this->assertIdentical("<link href='' rel='stylesheet' type='text/css' media='all'/>\n", CodeWriter::linkTag(''));
        $this->assertIdentical("<link href='blah' rel='stylesheet' type='text/css' media='all'/>\n", CodeWriter::linkTag('blah'));
        $this->assertIdentical("<link href=' bananas/styles ' rel='stylesheet' type='text/css' media='all'/>\n", CodeWriter::linkTag(' bananas/styles '));
    }

    function testGetBaseHrefTagWithPath () {
        $expected = "<base href='<?=\RightNow\Utils\Url::getShortEufBaseUrl('sameAsRequest', \RightNow\Utils\FileSystem::getOptimizedAssetsDir() . '%s');?>'></base>\n";
        $this->assertIdentical(sprintf($expected, ''), CodeWriter::getBaseHrefTag('', false, null));
        $this->assertIdentical(sprintf($expected, 'blah'), CodeWriter::getBaseHrefTag('blah', false, null));
        $this->assertIdentical(sprintf($expected, ' bananas/style '), CodeWriter::getBaseHrefTag(' bananas/style ', false, null));
    }

    function testGetBaseHrefTagWithPathAndHTMLFive () {
        $expected = "<base href='<?=\RightNow\Utils\Url::getShortEufBaseUrl('sameAsRequest', \RightNow\Utils\FileSystem::getOptimizedAssetsDir() . '%s');?>'/>\n";
        $this->assertIdentical(sprintf($expected, ''), CodeWriter::getBaseHrefTag('', true, null));
        $this->assertIdentical(sprintf($expected, 'blah'), CodeWriter::getBaseHrefTag('blah', true, null));
        $this->assertIdentical(sprintf($expected, ' bananas/style '), CodeWriter::getBaseHrefTag(' bananas/style ', true, null));
    }

    function testGetBaseHrefTagWithOptimizedPath () {
        $expected = "<base href='<?=\RightNow\Utils\Url::getShortEufBaseUrl('sameAsRequest', '%s');?>'></base>\n";
        $this->assertIdentical(sprintf($expected, 'me'), CodeWriter::getBaseHrefTag('', false, 'me'));
        $this->assertIdentical(sprintf($expected, 'merenque'), CodeWriter::getBaseHrefTag('blah', false, 'merenque'));
        $this->assertIdentical(sprintf($expected, ' pimenta/com '), CodeWriter::getBaseHrefTag(' bananas/style ', false, ' pimenta/com '));
    }

    function testGetBaseHrefTagWithOptimizedPathAndHTMLFive () {
        $expected = "<base href='<?=\RightNow\Utils\Url::getShortEufBaseUrl('sameAsRequest', '%s');?>'/>\n";
        $this->assertIdentical(sprintf($expected, 'me'), CodeWriter::getBaseHrefTag('', true, 'me'));
        $this->assertIdentical(sprintf($expected, 'merenque'), CodeWriter::getBaseHrefTag('blah', true, 'merenque'));
        $this->assertIdentical(sprintf($expected, ' pimenta/com '), CodeWriter::getBaseHrefTag(' bananas/style ', true, ' pimenta/com '));
    }

    function testCreateRuntimeThemeConditions() {
        $this->assertIdentical("<?\n?>\n", CodeWriter::createRuntimeThemeConditions(array()));
        $this->assertIdentical("<?\nif (get_instance()->themes->getTheme() === 'hey') { ?>\nmy css\n<?}?>\n",
            CodeWriter::createRuntimeThemeConditions(array('hey' => 'my css')));
        $this->assertIdentical("<?\nif (get_instance()->themes->getTheme() === 'hey') { ?>\nmy css\n<?}"
            . "\nelse if (get_instance()->themes->getTheme() === 'bananas') { ?>\nclass\n<?}"
            . "?>\n",
            CodeWriter::createRuntimeThemeConditions(array('hey' => 'my css', 'bananas' => 'class')));
    }

    function testBuildMetaDataArray () {
        $this->assertIdentical("get_instance()->_checkMeta(array());\n", CodeWriter::buildMetaDataArray(array()));
        $this->assertIdentical("get_instance()->_checkMeta(array('galera'=>'de',\n'laje'=>''));\n", CodeWriter::buildMetaDataArray(array(
            'galera' => 'de',
            'laje' => null,
        )));
    }

    function testBuildOptimizedPageContent() {
        $expected = <<<EXP
<?php
namespace{
    get_instance()->themes->setRuntimeThemeData(theme info);
    meta data
    get_instance()->clientLoader->setJavaScriptModule(get_instance()->meta['javascript_module']);
}
widget code goes here
namespace{
    use \RightNow\Utils\FileSystem;
    ?>page content goes here<?
}
?>

EXP;
        $actual = CodeWriter::buildOptimizedPageContent('page content goes here', 'widget code goes here', 'meta data', 'theme info');
        $this->assertIdentical($expected, $actual);
    }

    function testCreateViewMethodWrapper () {
        $expected = <<<FUNC
    function bananas (\$data) {
        extract(\$data);
        ?>ela<?
    }
FUNC;
        $this->assertIdentical($expected, CodeWriter::createViewMethodWrapper('bananas', 'ela'));
    }

    function testCreateViewMethodWrapperForStatic () {
        $expected = <<<FUNC
    static function bananas (\$data) {
        extract(\$data);
        ?>benefit<?
    }
FUNC;
        $this->assertIdentical($expected, CodeWriter::createViewMethodWrapper('bananas', 'benefit', true));
    }

    function testCreatePassThruViewMethod () {
        $expected = <<<FUNC
        function bananas (\$data) {
            parent::devotion(\$data);
        }
FUNC;
        $this->assertIdentical($expected, CodeWriter::createPassThruViewMethod('bananas', 'devotion'));
    }

    function testCreateWidgetHeaderFunction () {
        $expected = <<<'EXP'

function bananas() {
    $result = array(
        'js_name'        => 'coraco',
        'library_name'   => 'Bananas',
        'view_func_name' => 'dub',
        'meta'           => array('em' => 'i'),
    );
    $result['meta']['attributes'] = array('strong' => 'i');
    return $result;
}

EXP;
        $actual = CodeWriter::createWidgetHeaderFunction('bananas', array(
            'jsName'            => 'coraco',
            'className'         => 'Bananas',
            'viewFunctionName'  => 'dub',
            'metaArray'         => "array('em' => 'i')",
            'writtenAttributes' => "array('strong' => 'i')",
        ));

        $this->assertIdentical($expected, $actual);
    }

    function testWrapInsideClass () {
        $expected = <<<'EXP'
        namespace {
            class Bananas {
                hey.
            }
        }

EXP;
        $actual = CodeWriter::wrapInsideClass('hey.', array(
            'className' => 'Bananas',
        ));
        $this->assertIdentical($expected, $actual);
    }

    function testWrapInsideClassWithNamespace () {
        $expected = <<<'EXP'
        namespace mr_kite {
            class Bananas {
                hey.
            }
        }

EXP;
        $actual = CodeWriter::wrapInsideClass('hey.', array(
            'namespace' => 'mr_kite',
            'className' => 'Bananas',
        ));
        $this->assertIdentical($expected, $actual);
    }

    function testWrapInsideClassWithExtends () {
        $expected = <<<'EXP'
        namespace {
            class Bananas extends CryBabyCry {
                hey.
            }
        }

EXP;
        $actual = CodeWriter::wrapInsideClass('hey.', array(
            'extends' => 'CryBabyCry',
            'className' => 'Bananas',
        ));
        $this->assertIdentical($expected, $actual);
    }
}

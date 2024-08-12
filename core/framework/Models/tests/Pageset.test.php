<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Libraries\PageSetMapping,
    RightNow\Connect\v1_4 as ConnectPHP;

class PagesetModelTest extends CPTestCase {
    public $testingClass = 'RightNow\Models\Pageset';

    function __construct() {
        parent::__construct();
        $this->writeTempDir();
    }

    function instance() {
        return new RightNow\Models\Pageset();
    }

    function testGetEnabledPageSetMappingArrays() {
        $mappingArrays = $this->instance()->getEnabledPageSetMappingArrays();
        $this->assertIdentical(0, count($mappingArrays));
        $this->assertTrue(is_array($mappingArrays));

        $mappingArrays = $this->instance()->getPageSetMappingArrays();
        if (is_array($mappingArrays['standard'])) {
            $mappingItem = array_shift($mappingArrays['standard']);
            $method = $this->getMethod('enableItem');
            $return = $method($mappingItem->id, true);

            $mappingArrays = $this->instance()->getEnabledPageSetMappingArrays();
            $this->assertIdentical(1, count($mappingArrays));
            $this->assertIsA($mappingArrays[$mappingItem->value], 'RightNow\Libraries\PageSetMapping');

            $method($mappingItem->id, false);
        }
        else {
            $this->fail();
        }
    }

    function testGetPageSetMappingArrays() {
        $mappingArrays = $this->instance()->getPageSetMappingArrays();

        $this->assertTrue(is_array($mappingArrays['standard']));
        $this->assertTrue(is_array($mappingArrays['custom']));
        $this->assertIdentical(3, count($mappingArrays['standard']));
        $this->assertIdentical(0, count($mappingArrays['custom']));
        foreach($mappingArrays['standard'] as $mapping){
            $this->assertIsA($mapping, 'RightNow\Libraries\PageSetMapping');
            $this->assertTrue($mapping->locked);
            $this->assertFalse($mapping->enabled);
            $this->assertTrue(($mapping->value ==='mobile') || ($mapping->value === 'basic'));
        }

        $mappingArrays = $this->instance()->getPageSetMappingArrays(DOCROOT . '/cp/generated/production/source/');
        $this->assertTrue(is_array($mappingArrays['standard']));
        $this->assertTrue(is_array($mappingArrays['custom']));
        $this->assertIdentical(0, count($mappingArrays['standard']));
        $this->assertIdentical(0, count($mappingArrays['custom']));
    }

    function testGetDeployedContent() {
        $mappings = array(
            'standard' => array(
                new PageSetMapping(array(
                    'id' => 1,
                    'item' => '/iphone/i',
                    'description' => 'iPhone',
                    'value' => 'mobile',
                    'locked' => true,
                    'enabled' => true,
                )),
                new PageSetMapping(array(
                    'id' => 2,
                    'item' => '/Android/i',
                    'description' => 'Android',
                    'value' => 'mobile',
                    'locked' => true,
                    'enabled' => true
                ))
            ),
            'custom' => array(
                new PageSetMapping(array(
                    'id' => 4,
                    'item' => '/xphone/i',
                    'description' => 'Xphone',
                    'value' => 'mobile',
                    'enabled' => true,
                    'locked' => true
                ))
            ),
         );

        $expected = "<?\n" .
            "/****************************\n" .
            "**\n" .
            "** Edits to this file should be done through the Page Set Mapping interface\n" .
            "**\n" .
            "** *****************************/\n" .
            "function getPageSetMapping() {\n" .
            "return array(\n" .
            "4 => new \RightNow\Libraries\PageSetMapping(array('id' => 4, 'item' => '/xphone/i', 'description' => 'Xphone', 'value' => 'mobile', 'enabled' => true, 'locked' => true)));\n" .
            "}\n" .
            "function getRNPageSetMapping() {\n" .
            "return array(\n" .
            "1 => new \RightNow\Libraries\PageSetMapping(array('id' => 1, 'item' => '/iphone/i', 'description' => 'iPhone', 'value' => 'mobile', 'enabled' => true, 'locked' => true)),\n" .
            "2 => new \RightNow\Libraries\PageSetMapping(array('id' => 2, 'item' => '/Android/i', 'description' => 'Android', 'value' => 'mobile', 'enabled' => true, 'locked' => true)));\n" .
            "}\n";

        $this->assertEqual(
            $expected,
            $this->instance()->getDeployedContent($mappings)
        );
    }

    function testGetCommittedMappings() {
        $invoke = $this->getMethod('getCommittedMappings');
        $path = 'config/pageSetMapping.php';
        $basePath = get_cfg_var('doc_root') . '/cp/generated/production/source/';
        $tmpPath = $this->getTestDir() . "/$path";
        if (!\RightNow\Utils\FileSystem::isReadableDirectory($this->getTestDir() . 'config')) {
            mkdir($this->getTestDir() . 'config');
        }
        $productionArray = $invoke($basePath);
        $this->assertEqual(
            array('standard' => array(), 'custom' => array()),
            $productionArray
        );
        // we cannot redeclare function names, so just verify that file gets loaded
        file_put_contents($tmpPath, "<?\nfunction getPageSetMapping2() {return array();}\nfunction getRNPageSetMapping2() {return array(\n"
            . "1 => new \RightNow\Libraries\PageSetMapping(array('id' => 1, 'item' => '/iphone/i', 'description' => 'iPhone', 'value' => 'mobile', 'enabled' => true, 'locked' => true)),\n"
            . "2 => new \RightNow\Libraries\PageSetMapping(array('id' => 2, 'item' => '/Android/i', 'description' => 'Android', 'value' => 'mobile', 'enabled' => true, 'locked' => true)));}");
        $invoke($this->getTestDir());
        $this->assertEqual(
            array(),
            getPageSetMapping2()
        );
        $this->assertEqual(
            array(
                1 => new \RightNow\Libraries\PageSetMapping(array('id' => 1, 'item' => '/iphone/i', 'description' => 'iPhone', 'value' => 'mobile', 'enabled' => true, 'locked' => true)),
                2 => new \RightNow\Libraries\PageSetMapping(array('id' => 2, 'item' => '/Android/i', 'description' => 'Android', 'value' => 'mobile', 'enabled' => true, 'locked' => true))
            ),
            getRNPageSetMapping2()
        );
        $this->assertEqual(
            $productionArray,
            $invoke('/some/bogus/directory')
        );
    }

    function testMergePageSetArrays() {
        $invoke = $this->getMethod('mergePageSetArrays');
        $arrays = array(
          'custom' => array(1, 2, 3),
          'standard' => array(4, 5, 6),
        );
        $this->assertEqual(
            array(1, 2, 3, 4, 5, 6),
            $invoke($arrays)
        );
    }

    function testGetPageSetMappingComparedArray() {
        $this->assertTrue(is_array($this->instance()->getPageSetMappingComparedArray()));
        // Note: This is further tested via testmergeComparedPageSetArrays() below.
    }

    function testMergeComparedPageSetArrays() {
        $sourcePageSets = array(
            new PageSetMapping(array('id'=>1, 'item'=>'/iphone/i', 'value'=>'mobile', 'description'=>'iPhone', 'enabled'=>false, 'locked'=>true)), // Same in source and target
            new PageSetMapping(array('id'=>2, 'item'=>'/Android/i', 'value'=>'mobile', 'description'=>'Android', 'enabled'=>false, 'locked'=>true)), // description differs btwn. source and target
            new PageSetMapping(array('id'=>3, 'item'=>'/webOS/i', 'value'=>'mobile', 'description'=>'Palm', 'enabled'=>false, 'locked'=>true)), // identical details but different id. (may consider only displaying one?)
            new PageSetMapping(array('id'=>5, 'item'=>'/xphone/i', 'value'=>'mobile', 'description'=>'Xphone', 'enabled'=>false, 'locked'=>true)), // exists in source but not target.
         );

        $targetPageSets = array(
            new PageSetMapping(array('id'=>1, 'item'=>'/iphone/i', 'value'=>'mobile', 'description'=>'iPhone', 'enabled'=>false, 'locked'=>true)), // Same in source and target
            new PageSetMapping(array('id'=>2, 'item'=>'/Android/i', 'value'=>'mobile', 'description'=>'android', 'enabled'=>false, 'locked'=>true)), // description differs btwn. source and target
            new PageSetMapping(array('id'=>4, 'item'=>'/webOS/i', 'value'=>'mobile', 'description'=>'Palm', 'enabled'=>false, 'locked'=>true)), // identical details but different id. (may consider only displaying one?)
            new PageSetMapping(array('id'=>6, 'item'=>'/yphone/i', 'value'=>'mobile', 'description'=>'Yphone', 'enabled'=>false, 'locked'=>true)), // exists in target but not source.
         );

        $expected = array(
          1 =>
          array('exists'      => array(true, true),
                'id'          => array(1, 1),
                'item'        => array('/iphone/i', '/iphone/i'),
                'description' => array('iPhone', 'iPhone'),
                'value'       => array('mobile', 'mobile'),
                'enabled'     => array(false, false),
                'locked'      => array(true, true),
          ),
          2 =>
          array('exists'      => array(true, true),
                'id'          => array(2, 2),
                'item'        => array('/Android/i', '/Android/i'),
                'description' => array('Android', 'android'),
                'value'       => array('mobile', 'mobile'),
                'enabled'     => array(false, false),
                'locked'      => array(true, true),
          ),
          3 =>
          array('exists'      => array(true, false),
                'id'          => array(3, null),
                'item'        => array('/webOS/i', null),
                'description' => array('Palm', null),
                'value'       => array('mobile', null),
                'enabled'     => array(false, null),
                'locked'      => array(true, null),
          ),
          4 =>
          array('exists'      => array(false, true),
                'id'          => array(null, 4),
                'item'        => array(null, '/webOS/i'),
                'description' => array(null, 'Palm'),
                'value'       => array(null, 'mobile'),
                'enabled'     => array(null, false),
                'locked'      => array(null, true),
          ),
          5 =>
          array('exists'      => array(true, false),
                'id'          => array(5, null),
                'item'        => array('/xphone/i', null),
                'description' => array('Xphone', null),
                'value'       => array('mobile', null),
                'enabled'     => array(false, null),
                'locked'      => array(true, null),
          ),
          6 =>
          array('exists'      => array(false, true),
                'id'          => array(null, 6),
                'item'        => array(null, '/yphone/i'),
                'description' => array(null, 'Yphone'),
                'value'       => array(null, 'mobile'),
                'enabled'     => array(null, false),
                'locked'      => array(null, true),
          ),
        );

        $invoke = $this->getMethod('mergeComparedPageSetArrays');
        $this->assertEqual(
            $expected,
            $invoke($sourcePageSets, $targetPageSets)
        );
    }

    function testGetPageSetTypeFromID() {
        $invoke = $this->getMethod('getPageSetTypeFromID');
        $this->assertEqual(
            'custom',
            $invoke(CP_FIRST_CUSTOM_PAGESET_ID)
        );
        $this->assertEqual(
            'custom',
            $invoke(CP_FIRST_CUSTOM_PAGESET_ID + 1)
        );
        $this->assertEqual(
            'standard',
            $invoke(CP_FIRST_CUSTOM_PAGESET_ID - 1)
        );

        try {
            $invoke('notAnInteger');
        }
        catch (Exception $e) {
            $error = $e->getMessage();
        }
        $this->assertEqual($error, 'pageSetID not an integer');
    }

    function testpageSetIdIsCustom() {
        $invoke = $this->getMethod('pageSetIdIsCustom');
        $this->assertTrue($invoke(CP_FIRST_CUSTOM_PAGESET_ID));
        $this->assertFalse($invoke(CP_FIRST_CUSTOM_PAGESET_ID - 1));
    }

    function testGetPageSetMappingFromComparedArray() {
        $changes = array(
          1 => 1,
          2 => 1,
          3 => 2,
          10000 => 1,
          10001 => 2
        );

        $comparedArray = array(
          1 =>
          array('exists'      => array(true, false),
                'id'          => array(1, null),
                'item'        => array('/iphone/i', null),
                'description' => array('iPhone', null),
                'value'       => array('mobile', null),
                'enabled'     => array(false, null),
                'locked'      => array(true, null),
          ),
          2 =>
          array('exists'      => array(true, true),
                'id'          => array(2, 2),
                'item'        => array('/Android/i', '/Android/g'),
                'description' => array('Android', 'android'),
                'value'       => array('mobile', 'mobile'),
                'enabled'     => array(true, true),
                'locked'      => array(true, true),
          ),
          10000 =>
          array('exists'      => array(true, false),
                'id'          => array(10000, null),
                'item'        => array('/xphone/i', null),
                'description' => array('Xphone', null),
                'value'       => array('mobile', null),
                'enabled'     => array(true, null),
                'locked'      => array(false, null),
          ),
          10001 =>
          array('exists'      => array(true, true),
                'id'          => array(10001, 10001),
                'item'        => array('/yphone/i', '/yphone/i'),
                'description' => array('Yphone', 'Yphone'),
                'value'       => array('mobile', 'mobile'),
                'enabled'     => array(false, true),
                'locked'      => array(true, true),
          ),
          10002 =>
          array('exists'      => array(true, true),
                'id'          => array(10002, 10002),
                'item'        => array('/zphone/i', '/zphone/i'),
                'description' => array('Zphone', 'Zphone'),
                'value'       => array('mobile', 'mobile'),
                'enabled'     => array(true, true),
                'locked'      => array(true, true),
          ),
        );

        $expected = array(
          'standard' => array(1 => new PageSetMapping(array('id'=>1, 'item'=>'/iphone/i', 'value'=>'mobile', 'description'=>'iPhone', 'enabled'=>true, 'locked'=>true)),
                              2 => new PageSetMapping(array('id'=>2, 'item'=>'/Android/i', 'value'=>'mobile', 'description'=>'Android', 'enabled'=>true, 'locked'=>true)),
                             ),
          'custom' => array(10000 => new PageSetMapping(array('id'=>10000, 'item'=>'/xphone/i', 'value'=>'mobile', 'description'=>'Xphone', 'enabled'=>true, 'locked'=>false)),
                            10002 => new PageSetMapping(array('id'=>10002, 'item'=>'/zphone/i', 'value'=>'mobile', 'description'=>'Zphone', 'enabled'=>true, 'locked'=>true))
                           ),
        );
        $this->assertEqual(
            $expected,
            $this->instance()->getPageSetMappingFromComparedArray($changes, null, $comparedArray)
        );
    }

    function testGetPageSetMappingMergedArray(){
        $this->assertTrue(is_array($this->instance()->getPageSetMappingMergedArray()));
    }

    function testGetPageSetDefaultArray() {
        $method = $this->getMethod('getPageSetDefaultArray');
        $results = $method();
        if(count($results) > 0){
            $this->assertIdentical(2, count($results));
            $iphone = current($results);
            $this->assertIdentical('/iphone/i', $iphone->item);
            $this->assertIdentical('iPhone', $iphone->description);
            $this->assertIdentical('mobile', $iphone->value);
            $this->assertIdentical(true, $iphone->locked);
            $android = next($results);
            $this->assertIdentical('/Android/i', $android->item);
            $this->assertIdentical('Android', $android->description);
            $this->assertIdentical('mobile', $android->value);
            $this->assertIdentical(true, $android->locked);
        }
    }

    function testGetPageSetDefaultMappingUniqueValues() {
        $method = $this->getMethod('getUncommittedMappings');
        $method(); // break caching
        $method = $this->getMethod('getPageSetDefaultMappingUniqueValues');
        $result = $method();
        $this->assertIdentical(array('mobile' => 'mobile', 'basic' => 'basic'), $result);
    }

    function testItemCrud() {
        // add item
        $method = $this->getMethod('addItem');
        $mockItem = $method(array(
            'item' => '/banana/',
            'description' => 'bananas',
            'value' => 'BANANA',
        ));
        $this->assertIsA($mockItem, '\RightNow\Libraries\PageSetMapping');
        $this->assertSame('/banana/', $mockItem->item);
        $this->assertSame('BANANA', $mockItem->value);
        $this->assertSame('bananas', $mockItem->description);
        $this->assertIsA($mockItem->id, 'int');
        $this->assertTrue($mockItem->id > 1000);

        // update item
        $method = $this->getMethod('updateItem');
        $toUpdate = array();
        $toUpdate[$mockItem->id] = array(
            'item' => '/foo/',
            'description' => 'bar',
            'value' => 'baz',
        );
        $return = $method($toUpdate);
        $this->assertIsA($return, '\RightNow\Libraries\PageSetMapping');
        $this->assertSame('/foo/', $return->item);
        $this->assertSame('baz', $return->value);
        $this->assertSame('bar', $return->description);
        $this->assertSame($mockItem->id, $return->id);
        $mockItem = $return;

        // enable item
        $method = $this->getMethod('enableItem');
        $return = $method($mockItem->id, true);
        $this->assertTrue($return);
        $return = $method($mockItem->id, false);
        $this->assertTrue($return);
        $return = $method(342341, true);
        $this->assertFalse($return);

        // uncommitted mappings
        $method = $this->getMethod('getUncommittedMappings');
        $mappings = $method();
        $this->assertSame(3, count($mappings['standard']));
        $this->assertTrue(count($mappings['custom']) > 0);
        $this->assertTrue(array_key_exists($mockItem->id, $mappings['custom']));

        // delete item
        $method = $this->getMethod('deleteItem');
        $this->assertFalse($method(1));
        $this->assertTrue($method($mockItem->id));
        $this->assertFalse($method(12324));
        try {
            $method('banana');
            $this->fail();
        }
        catch (\Exception $e) {
            $this->pass();
        }
    }

    function testGetPageSetFilePath(){
        $this->assertIdentical('config/pageSetMapping.php', $this->instance()->getPageSetFilePath());
    }

    function testGetFacebookPageSetID(){
        $this->assertIdentical(3, $this->instance()->getFacebookPageSetID());
    }

    function testIsValueValid() {
        $method = $this->getMethod('isValueValid');
        $this->assertTrue($method('http://www.oracle.com'));
        $this->assertTrue($method('http://oracle.com'));
        $this->assertTrue($method('HTTP://oracle.com'));
        $this->assertTrue($method('https://oracle.com'));
        $this->assertTrue($method('HTTPS://oracle.com'));
        $this->assertTrue($method('mobile'));

        $this->assertFalse($method('ftp://oracle.com'));
        $this->assertFalse($method('iphone'));
        $this->assertFalse($method('android'));
        $this->assertFalse($method('httpsomevalue')); // value starting with http
        $this->assertFalse($method('./../'));
        $this->assertFalse($method('.'));
        $this->assertFalse($method('.Hello'));
        $this->assertFalse($method('Hello.'));
        $this->assertFalse($method('./hello/world/'));
        $this->assertFalse($method('../hello/world/'));
        $this->assertFalse($method('/hello/world/.'));
        $this->assertFalse($method('/hello/world/..'));

        \RightNow\Utils\FileSystem::mkdirOrThrowExceptionOnFailure(CUSTOMER_FILES . "views/pages/android");
        $this->assertTrue($method('android'));
        \RightNow\Utils\FileSystem::removeDirectory(CUSTOMER_FILES . "views/pages/android", true);
    }

    function testGetAttrValue() {
        $method = $this->getMethod('getAttrValue');
        $this->assertSame(3, $method(1, 1));
        $this->assertSame(3, $method(true, true));

        $this->assertSame(1, $method(0, 1));
        $this->assertSame(1, $method(false, true));

        $this->assertSame(2, $method(1, 0));
        $this->assertSame(2, $method(true, false));

        $this->assertSame(0, $method(0, 0));
        $this->assertSame(0, $method(false, false));
    }

    function testGetInitialPageSetArray() {
        $method = $this->getMethod('getInitialPageSetArray');
        $this->assertIdentical(array('standard' => array(), 'custom' => array()), $method());
    }

}

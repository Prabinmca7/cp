<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class TestBasicProductCategorySearchFilter extends WidgetTestCase
{
    public $testingWidget = "standard/search/BasicProductCategorySearchFilter";

    function setUp()
    {
        $this->setMockSession();
        $this->CI->session->setReturnValue('canSetSessionCookies', true);
        $this->CI->session->returns('getSessionData', true, array('cookiesEnabled'));
    }

    function tearDown()
    {
        $this->unsetMockSession();
    }

    function testGetData()
    {
        $this->createWidgetInstance();
        $data = $this->getWidgetData();

        // products
        $this->assertIdentical($data['attrs']['filter_type'], 'Product');
        $this->assertIdentical($data['applyUrl'], '/app/answers/list');
        $this->assertTrue(is_array($data['levelData']));

        foreach ($data['levelData'] as $levelData) {
            foreach (array('id', 'label', 'hasChildren', 'url') as $key) {
                if (!array_key_exists($key, $levelData))
                    $this->fail();
            }
        }
        $this->assertTrue(is_array($data['selectedData']));
        $this->assertTrue(count($data['selectedData']) === 0);

        // products with URL parameter
        $this->addUrlParameters(array('p' => 10));
        $data = $this->getWidgetData();
        $this->assertIdentical($data['applyUrl'], '/app/answers/list/p/10');
        $this->assertTrue(is_array($data['selectedData']));

        foreach ($data['selectedData'] as $selectedData) {
            foreach (array('id', 'label', 'url') as $key) {
                if (!array_key_exists($key, $selectedData))
                    $this->fail();
            }

            $url = \RightNow\Utils\Url::addParameter($data['applyUrl'], 'p', $selectedData['id']);
            $this->assertIdentical($url, $selectedData['url']);
        }
        $this->restoreUrlParameters();

        // categories
        $this->createWidgetInstance(array('filter_type' => 'Category'));
        $data = $this->getWidgetData();

        $this->assertIdentical($data['attrs']['filter_type'], 'Category');
        $this->assertIdentical($data['applyUrl'], '/app/answers/list');
        $this->assertTrue(is_array($data['levelData']));
        foreach ($data['levelData'] as $levelData) {
            foreach (array('id', 'label', 'hasChildren', 'url') as $key) {
                if (!array_key_exists($key, $levelData))
                    $this->fail();
            }
        }
        $this->assertTrue(is_array($data['selectedData']));
        $this->assertTrue(count($data['selectedData']) === 0);

        // cateogires with URL parameter
        $this->addUrlParameters(array('c' => 77));
        $data = $this->getWidgetData();
        $this->assertIdentical($data['applyUrl'], '/app/answers/list/c/77');
        $this->assertTrue(is_array($data['selectedData']));

        foreach ($data['selectedData'] as $selectedData) {
            foreach (array('id', 'label', 'url') as $key) {
                if (!array_key_exists($key, $selectedData))
                    $this->fail();
            }

            $url = \RightNow\Utils\Url::addParameter($data['applyUrl'], 'c', $selectedData['id']);
            $this->assertIdentical($url, $selectedData['url']);
        }
        $this->restoreUrlParameters();
    }

    function testSetUrlEndpoints()
    {
        // products
        $this->createWidgetInstance(array('filter_type' => 'Product'));
        $originalData = $this->widgetInstance->getDataArray();
        $method = $this->getWidgetMethod('setUrlEndpoints');

        $selectedData = array(
            array('id' => 1, 'label' => 'Mobile Phones'),
            array('id' => 2, 'label' => 'Android'),
            array('id' => 10, 'label' => 'HTC'),
        );

        // this method only modifies the data array, so there is no return
        $method($selectedData);
        $currentData = $this->widgetInstance->getDataArray();

        $this->assertIdentical($originalData['attrs'], $currentData['attrs']);
        $this->assertTrue(!array_key_exists('applyUrl', $originalData));
        $this->assertTrue(array_key_exists('applyUrl', $currentData));
        // make sure applyUrl got value from $selectedData
        $this->assertIdentical($currentData['applyUrl'], '/app/answers/list/p/10');

        // if there is no selected data or URL parameters, nothing should be added to applyUrl
        $method(null);
        $currentData = $this->widgetInstance->getDataArray();
        $this->assertIdentical($currentData['applyUrl'], '/app/answers/list');

        // if URL parameter is set, make sure that is used instead
        $this->addUrlParameters(array('p' => 999));
        $method($selectedData);
        $currentData = $this->widgetInstance->getDataArray();
        $this->assertIdentical($currentData['applyUrl'], '/app/answers/list/p/999');

        $this->restoreUrlParameters();

        // categories
        $this->createWidgetInstance(array('filter_type' => 'Category'));
        $originalData = $this->widgetInstance->getDataArray();
        $method = $this->getWidgetMethod('setUrlEndpoints');

        // this method only modifies the data array, so there is no return
        $method($selectedData);
        $currentData = $this->widgetInstance->getDataArray();

        $this->assertIdentical($originalData['attrs'], $currentData['attrs']);
        $this->assertTrue(!array_key_exists('applyUrl', $originalData));
        $this->assertTrue(array_key_exists('applyUrl', $currentData));
        // make sure applyUrl got value from $selectedData
        $this->assertIdentical($currentData['applyUrl'], '/app/answers/list/c/10');

        // if there is no selected data or URL parameters, nothing should be added to applyUrl
        $method(null);
        $currentData = $this->widgetInstance->getDataArray();
        $this->assertIdentical($currentData['applyUrl'], '/app/answers/list');

        // if URL parameter is set, make sure that is used instead
        $this->addUrlParameters(array('c' => 999));
        $method($selectedData);
        $currentData = $this->widgetInstance->getDataArray();
        $this->assertIdentical($currentData['applyUrl'], '/app/answers/list/c/999');

        $this->restoreUrlParameters();
    }

    function testAddUrlKeysAndEscapeLabels()
    {
        $items = array(
            array('id' => 1, 'label' => 'Blah', 'hasChildren' => true),
            array('id' => 2, 'label' => 'Blah Blah', 'hasChildren' => false),
            array('id' => 3, 'label' => 'With <special> chars', 'hasChildren' => false),
        );

        $this->createWidgetInstance();
        $method = $this->getWidgetMethod('addUrlKeysAndEscapeLabels');

        $results = $method($items, 'p');
        $data = $this->widgetInstance->getDataArray(); // by-pass calling getData()

        $this->assertTrue(!array_key_exists('applyUrl', $data));
        $this->assertTrue(is_array($results));

        // make sure each item had 'url' added
        foreach ($results as $result) {
            $this->assertTrue(array_key_exists('url', $result));

            // since applyUrl is null, make sure 'url' is as we expect
            $this->assertIdentical($result['url'], "/p/{$result['id']}");
        }

        // switch the filter type to categories
        $results = $method($items, 'c');
        foreach ($results as $result) {
            $this->assertTrue(array_key_exists('url', $result));
            $this->assertIdentical($result['url'], "/c/{$result['id']}");
        }

        // add a page to CI for 'hasChildren' tests
        $this->widgetInstance->CI->page = "asdf";
        // add the applyUrl and make sure 'url' contains it's value
        $data = $this->getWidgetData();
        $this->assertTrue(array_key_exists('applyUrl', $data));
        $this->assertIdentical($data['applyUrl'], '/app/answers/list');

        $results = $method($items, 'p');
        foreach ($results as $result) {
            $this->assertTrue(array_key_exists('url', $result));
            if ($result['hasChildren']) {
                $this->assertIdentical($result['url'], "/app/asdf/p/{$result['id']}");
            }
            else {
                $this->assertIdentical($result['url'], "{$data['applyUrl']}/p/{$result['id']}");
            }
        }

        // categories
        $results = $method($items, 'c');
        foreach ($results as $result) {
            $this->assertTrue(array_key_exists('url', $result));
            if ($result['hasChildren']) {
                $this->assertIdentical($result['url'], "/app/asdf/c/{$result['id']}");
            }
            else {
                $this->assertIdentical($result['url'], "{$data['applyUrl']}/c/{$result['id']}");
            }
        }

        // make sure that the label with HTML entities were escaped
        $this->assertIdentical($results[2]['label'], 'With &lt;special&gt; chars');
    }
}

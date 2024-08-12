<?

use RightNow\Models\Search,
    RightNow\Internal\Utils\SearchSourceConfiguration;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class SearchTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Utils\SearchSourceConfiguration';

    function testCustomOverridesCore () {
        list($class, $coreMapping, $customMapping, $mergedMapping, $errors) = $this->reflect('coreMapping', 'customMapping', 'mergedMapping', 'errors');
        $mergedMapping->setValue(null);
        $coreMapping->setValue(array('love' => array('model' => 'prosthetic', 'filters' => array('query' => 'hunger'), 'endpoint' => 'above')));
        $customMapping->setValue(array('love' => array('model' => 'thirst', 'filters' => array('query' => 'knew', 'custom' => 'post'), 'endpoint' => 'impala')));

        $result = SearchSourceConfiguration::getSearchMapping();

        $this->assertIdentical(array(
            'love' => array(
                'model' => 'custom/thirst',
                'filters' => array(
                    'query'  => 'knew',
                    'custom' => 'post',
                ),
                'endpoint' => 'impala',
            ),
        ), $result);

        $coreMapping->setValue(null);
        $customMapping->setValue(null);
        $mergedMapping->setValue(null);
        $errors->setValue(array());
    }

    function testErrorWhenModelIsNotSpecified () {
        list($class, $coreMapping, $customMapping, $mergedMapping, $errors) = $this->reflect('coreMapping', 'customMapping', 'mergedMapping', 'errors');
        $mergedMapping->setValue(null);
        $coreMapping->setValue(array('love' => array('model' => 'prosthetic', 'filters' => array('query' => 'hunger'))));
        $customMapping->setValue(array('love2' => array('filters' => array('query' => 'knew', 'custom' => 'post'))));

        $result = SearchSourceConfiguration::getSearchMapping();

        $this->assertIdentical(array(
            'love' => array(
                'model' => 'standard/prosthetic',
                'filters' => array(
                    'query'  => 'hunger',
                ),
                'endpoint' => null,
            ),
        ), $result);

        $errorsGenerated = SearchSourceConfiguration::getMappingErrors();

        $this->assertSame(1, count($errorsGenerated));
        $this->assertStringContains($errorsGenerated[0], 'model');
        $this->assertStringContains($errorsGenerated[0], 'love2');

        $coreMapping->setValue(null);
        $customMapping->setValue(null);
        $mergedMapping->setValue(null);
        $errors->setValue(array());
    }

    function testErrorWhenInvalidSourceStructure () {
        list($class, $coreMapping, $customMapping, $mergedMapping, $errors) = $this->reflect('coreMapping', 'customMapping', 'mergedMapping', 'errors');
        $mergedMapping->setValue(null);
        $errors->setValue(array());
        $coreMapping->setValue(array('love' => array('model' => 'prosthetic', 'filters' => array('query' => 'hunger'))));
        $customMapping->setValue(array('love2' => (object) array('model' => 'bananas', 'filters' => array('query' => 'knew', 'custom' => 'post'))));

        $result = SearchSourceConfiguration::getSearchMapping();

        $this->assertIdentical(array(
            'love' => array(
                'model' => 'standard/prosthetic',
                'filters' => array(
                    'query'  => 'hunger',
                ),
                'endpoint' => null,
            ),
        ), $result);

        $errorsGenerated = SearchSourceConfiguration::getMappingErrors();

        $this->assertSame(1, count($errorsGenerated));
        $this->assertStringContains($errorsGenerated[0], 'incorrect format');
        $this->assertStringContains($errorsGenerated[0], 'love2');

        $coreMapping->setValue(null);
        $customMapping->setValue(null);
        $mergedMapping->setValue(null);
        $errors->setValue(array());
    }
}

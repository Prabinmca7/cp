<?

use RightNow\Internal\Api\Libraries\BaseMapper;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class BaseMapperLibraryTest extends CPTestCase {
    public $testingClass = 'RightNow\Internal\Api\Libraries\BaseMapper';

    function testToSearchResult () {
        try {
            $mapper = BaseMapperImplLibraryTest::toSearchResults((object) array());
            $this->fail("Should've thrown");
        }
        catch (\Exception $e) {
            $this->assertIdentical("The 'toSearchResults' function must be implemented by the child class RightNow\Libraries\SearchMappers\BaseMapper", $e->getMessage());
        }
    }
}

class BaseMapperImplLibraryTest extends RightNow\Libraries\SearchMappers\BaseMapper {
}

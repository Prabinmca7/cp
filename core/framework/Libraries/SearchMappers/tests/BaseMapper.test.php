<?

use RightNow\Libraries\SearchMappers\BaseMapper;

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class BaseMapperTest extends CPTestCase {
    public $testingClass = 'RightNow\Libraries\SearchMappers\BaseMapper';

    function testToSearchResult () {
        try {
            $mapper = BaseMapperImplTest::toSearchResults((object) array());
            $this->fail("Should've thrown");
        }
        catch (\Exception $e) {
            $this->assertIdentical("The 'toSearchResults' function must be implemented by the child class RightNow\Libraries\SearchMappers\BaseMapper", $e->getMessage());
        }
    }
}

class BaseMapperImplTest extends RightNow\Libraries\SearchMappers\BaseMapper {
}

<?

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Utils\Framework;
use RightNow\Utils\Text as Text;

class ContentTypeNotificationManagerTest extends WidgetTestCase {
    public $testingWidget = 'standard/okcs/ContentTypeNotificationManager';

    /**
    * UnitTest case to test default attributes.
    */
    function testFetchedContentTypes()
    {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->createWidgetInstance(); 
        $data = $this->getWidgetData();
        $this->assertIsA($data['js']['contentTypes'],'Array');

        foreach ($data['js']['contentTypes'] as $item) {
            $this->assertSame(1, count($item->referenceKey));
            $this->assertSame(1, count($item->name));
            $this->assertSame(1, count($item->recordId));
            $this->assertSame(1, count($item->dateAdded));
            $this->assertSame(1, count($item->dateModified));
            $this->assertSame(1, count($item->indexStatus));
        }
    }
}

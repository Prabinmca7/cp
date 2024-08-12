<?

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class OkcsFavoritesListTest extends WidgetTestCase {
    public $testingWidget = 'standard/okcs/OkcsFavoritesList';

    function testGetTableFields() {
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->createWidgetInstance(array('display_fields' => 'answerID'));
        $getTableFields = $this->getWidgetMethod('getTableFields');
        $tableFields = $getTableFields();
        $expected = array(
            'name'          => 'answerID',
            'label'         => 'answerID',
            'columnID'      => 0
        );
        $this->assertIdentical($tableFields[0], $expected);
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }
    
    /**
    * UnitTest case to test widget controller with valid attributes to fetch list of favorite details for the logged in user
    */
    function testDefaultAttributes()
    {
        $this->logIn();
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $this->createWidgetInstance();
        $data = $this->getWidgetData();

        $this->assertNotNull($data['favoritesList']);
        $this->assertSame(5, count($data['favoritesList']));
        foreach($data['favoritesList'] as $favorite) {
            $this->assertNotNull($favorite["title"]);
            $this->assertNotNull($favorite["answerId"]);
            $this->assertNotNull($favorite["documentId"]);
        }

        $this->restoreUrlParameters();
        $this->logOut();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }

    /**
    * UnitTest case to test widget controller with valid attributes to fetch list of favorite details for the logged in user
    */
    function testTablePaginationAttribute()
    {
        $this->logIn();
        \Rnow::updateConfig('OKCS_ENABLED', 1, true);
        \Rnow::updateConfig('OKCS_API_TIMEOUT', 0, true);
        \Rnow::updateConfig('OKCS_IM_API_URL', 'http://'.\Rnow::getConfig(OE_WEB_SERVER).'/ci/unitTest/OkcsKmApi/endpoint/', true);
        $widget->data['attrs']['view_type'] = "table";
        $widget->data['attrs']['enable_pagination_for_table'] = true;

        $this->createWidgetInstance();
        $data = $this->getWidgetData();

        $this->assertNotNull($data['favoritesList']);
        $this->assertSame(5, count($data['favoritesList']));
        foreach($data['favoritesList'] as $favorite) {
            $this->assertNotNull($favorite["title"]);
            $this->assertNotNull($favorite["answerId"]);
            $this->assertNotNull($favorite["documentId"]);
        }

        $this->restoreUrlParameters();
        $this->logOut();
        \Rnow::updateConfig('OKCS_ENABLED', 0, true);
    }
}

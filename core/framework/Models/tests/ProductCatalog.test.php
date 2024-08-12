<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Connect\v1_4 as Connect,
    RightNow\Utils\Framework,
    RightNow\Utils\Text;

class ProductCatalogTest extends CPTestCase {
    public $testingClass = 'RightNow\Models\ProductCatalog';

    function __construct() {
        parent::__construct();
        $this->model = new RightNow\Models\ProductCatalog();
        $this->productIDs = array(5, 6, 7, 9, 10);
        $this->interfaceID = \RightNow\Api::intf_id();
    }

    //@@@ QA 130417-000116 RightNow A&C: End User new Sales Product Search Widgi for Asset Advanced Search in CP
    function responseCheck($response, $expectedReturn = 'object', $errorCount = 0, $warningCount = 0) {
        try {
            $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
            if ($expectedReturn === null) {
                $this->assertNull($response->result);
            }
            else {
                $this->assertIsA($response->result, $expectedReturn);
            }
            $this->assertSame($errorCount, count($response->errors));
            $this->assertSame($warningCount, count($response->warnings), var_export($response->warnings, true));
        }
        catch (Exception $e) {
            $this->assertNull($e->getMessage());
        }
    }

    //@@@ QA 130402-000072 Task 50282 Test Fetching of Product
    function testGet() {
        $this->responseCheck($this->model->get(9999), null, 1);
        $this->responseCheck($this->model->get(null), null, 1);

        foreach ($this->productIDs as $id) {
            $response = $this->model->get($id);
            $this->responseCheck($response, 'RightNow\Connect\v1_4\SalesProduct');
        }
    }

    //@@@ QA 130402-000072 Task 50282 Test Fetching Products at different levels
    function testGetDirectDescendants() {
        //Top Level Products
        $expected = array(
            array('id' => 222005, 'label' => 'Laptops', 'hasChildren' => true),
            array('id' => 222004, 'label' => 'Printers', 'hasChildren' => true),
            array('id' => 222016, 'label' => 'Watches', 'hasChildren' => true),
            array('id' => 5, 'label' => 'GS2010', 'hasChildren' => false, 'serialized' => true),
            array('id' => 6, 'label' => 'GS2010 Green', 'hasChildren' => false, 'serialized' => true),
            array('id' => 20, 'label' => 'OnlyServiceVisible', 'hasChildren' => false, 'serialized' => false)
        );
        $response = $this->model->getDirectDescendants(null, 0, false);
        $this->responseCheck($response, 'array');
        $this->assertIdentical($response->result, $expected);
        // Folders with descendants
        $expected = array(      
            array('id' => 8, 'label' => 'HP Deskjet A400', 'hasChildren' => false, 'serialized' => true),
            array('id' => 9, 'label' => 'HP Deskjet Advantage', 'hasChildren' => false, 'serialized' => true)
        );

        $response = $this->model->getDirectDescendants(222012, 2, false);
        $this->responseCheck($response, 'array');
        $this->assertIdentical($response->result, $expected);

        $expected = array(      
            array('id' => 14, 'label' => 'Casio G-Shock', 'hasChildren' => false, 'serialized' => true)
        );

        $response = $this->model->getDirectDescendants(222016, 1, false);
        $this->responseCheck($response, 'array');
        $this->assertIdentical($response->result, $expected);

        $expected = array(
            array('id' => 222014, 'label' => 'Canon', 'hasChildren' => true),
            array('id' => 222013, 'label' => 'HP', 'hasChildren' => true)
        );

        $response = $this->model->getDirectDescendants(222008, 2, false);
        $this->responseCheck($response, 'array');

        $this->assertIdentical($response->result, $expected);
    }

    //@@@ QA 130402-000072 Task 50282 Test Fetching Products in a required format
    function testGetFormattedChain() {
        $response = $this->model->getFormattedChain(213219376788); //Invalid ID
        $this->responseCheck($response, 'array', 1);
        $this->assertIdentical($response->result, array());

        //Top level products and categories - result should only include self
        $expected = array(array('id' => 1, 'label' => '1yr - 400 Airtime Unlimited Nights & Weekends'));
        $response = $this->model->getFormattedChain(1);
        $this->responseCheck($response, 'array');
        $this->assertIdentical($response->result, $expected);

        //Products and categories with an actual chain
        $expected = array(
            array('id' => 222004, 'label' => 'Printers'),
            array('id' => 222008, 'label' => 'LaserJet'),
            array('id' => 222014, 'label' => 'Canon'),
            array('id' => 11, 'label' => 'MF4770M')
        );

        $response = $this->model->getFormattedChain(11);  //Asset Id 6
        $this->responseCheck($response, 'array');
        
        //Flat array functionality - i.e. excluding labels
        $expected = array(222004, 222008, 222014, 11);
        $response = $this->model->getFormattedChain(11, true);
        $this->responseCheck($response, 'array');
        $this->assertIdentical($response->result, $expected);     
    }

    //@@@ QA 130905-000031 Registering an asset from end user page that is a level 1 throws warning
    function testGetFormattedChainForFirstLevelProduct() {
        $expected = array(222016, 14);
        $response = $this->model->getFormattedChain(14, true);
        $this->responseCheck($response, 'array');
        $this->assertIdentical($response->result, $expected);  
    }

    //@@@ QA 130604-000192 Test Fetching Products in a required sorted format 
    function testGetCompleteProductCatalogHierarchy(){
        $getSortedItems = $this->getMethod('getCompleteProductCatalogHierarchy');
        $result = $getSortedItems();
        
        foreach($result as $product){
            $this->assertTrue(is_string($product[0]));
            $this->assertTrue(is_int($product[1]));
            $this->assertTrue(is_int($product['level']));
            $this->assertTrue(is_string($product['hier_list']));
        }

        $expected = array(
            array ('Laptops', 222005, array (222005, ), true, 'level' => 0, 'hier_list' => '222005', ),
            array ('Dell', 222010, array (222005, 222010, ), true, 'level' => 1, 'hier_list' => '222005,222010', ), 
            array ('Dell Latitude E6430', 13, array (222005, 222010, 13, ), false, 'level' => 2, 'hier_list' => '222005,222010,13', ), 
            array ('Printers', 222004, array (222004, ), true, 'level' => 0, 'hier_list' => '222004', ),
            array ('DotMatrix', 222006, array (222004, 222006, ), true, 'level' => 1, 'hier_list' => '222004,222006', ),
            array ('HP', 222011, array (222004, 222006, 222011, ), true, 'level' => 2, 'hier_list' => '222004,222006,222011', ), 
            array ('HP2932A', 7,  array (222004, 222006, 222011, 7, ), false, 'level' => 3, 'hier_list' => '222004,222006,222011,7', ),
            array ('Inkjet', 222007, array (222004, 222007, ), true, 'level' => 1, 'hier_list' => '222004,222007', ), 
            array ('HP', 222012, array (222004, 222007, 222012, ), true, 'level' => 2, 'hier_list' => '222004,222007,222012', ), 
            array ('HP Deskjet A400', 8, array (222004, 222007, 222012, 8, ), false, 'level' => 3, 'hier_list' => '222004,222007,222012,8', ), 
            array ('HP Deskjet Advantage', 9, array (222004, 222007, 222012, 9, ), false, 'level' => 3, 'hier_list' => '222004,222007,222012,9', ),
            array ('LaserJet', 222008, array ( 222004, 222008, ), true, 'level' => 1, 'hier_list' => '222004,222008', ), 
            array ('Canon', 222014, array (222004, 222008, 222014, ), true, 'level' => 2, 'hier_list' => '222004,222008,222014', ), 
            array ('MF4770M', 11, array (222004, 222008, 222014, 11, ), false, 'level' => 3, 'hier_list' => '222004,222008,222014,11', ),             
            array ('HP',222013,  array (222004, 222008, 222013, ), true, 'level' => 2, 'hier_list' => '222004,222008,222013', ), 
            array ('HP LaserJet Pro 400', 10, array (222004, 222008, 222013, 10, ), false, 'level' => 3, 'hier_list' => '222004,222008,222013,10', ), 
            array ('SparkPrinting', 222009, array (222004, 222009, ),  true, 'level' => 1, 'hier_list' => '222004,222009', ), 
            array ('Sinclair', 222015, array (222004, 222009, 222015, ), true, 'level' => 2, 'hier_list' => '222004,222009,222015', ), 
            array ('ZxPrinter', 12,  array (222004, 222009, 222015, 12, ), false, 'level' => 3, 'hier_list' => '222004,222009,222015,12', ), 
            array ('Watches', 222016, array (222016, ), true, 'level' => 0, 'hier_list' => '222016', ), 
            array ('Casio G-Shock', 14, array (222016, 14, ), false, 'level' => 1, 'hier_list' => '222016,14', ),
            array ('GS2010', 5,  array (5, ), false, 'level' => 0, 'hier_list' => '5', ), 
            array ('GS2010 Green', 6, array (6, ), false, 'level' => 0, 'hier_list' => '6', ),
            array ('OnlyServiceVisible', 20, 2 => array (20, ), false, 'level' => 0, 'hier_list' => '20', ), 
        );

        $this->assertIdentical($result, $expected);
    }
    
    //@@@ QA 131105-000000 RightNow A&C: Recognize Product Catalog Disabled and Visibility Settings in Register Product Select CP
    function testGetCompleteProductCatalogHierarchyForSearch(){
        $getSortedItems = $this->getMethod('getCompleteProductCatalogHierarchy');
        $result = $getSortedItems(true);
        
        foreach($result as $product){
            $this->assertTrue(is_string($product[0]));
            $this->assertTrue(is_int($product[1]));
            $this->assertTrue(is_int($product['level']));
            $this->assertTrue(is_string($product['hier_list']));
        }

        $expected = array(
            array ('Laptops', 222005, array (222005, ), true, 'level' => 0, 'hier_list' => '222005', ),
            array ('Dell', 222010, array (222005, 222010, ), true, 'level' => 1, 'hier_list' => '222005,222010', ), 
            array ('Dell Latitude E6430', 13, array (222005, 222010, 13, ), false, 'level' => 2, 'hier_list' => '222005,222010,13', ), 
            array ('Printers', 222004, array (222004, ), true, 'level' => 0, 'hier_list' => '222004', ),
            array ('DotMatrix', 222006, array (222004, 222006, ), true, 'level' => 1, 'hier_list' => '222004,222006', ),
            array ('HP', 222011, array (222004, 222006, 222011, ), true, 'level' => 2, 'hier_list' => '222004,222006,222011', ), 
            array ('HP2932A', 7,  array (222004, 222006, 222011, 7, ), false, 'level' => 3, 'hier_list' => '222004,222006,222011,7', ),
            array ('Inkjet', 222007, array (222004, 222007, ), true, 'level' => 1, 'hier_list' => '222004,222007', ), 
            array ('HP', 222012, array (222004, 222007, 222012, ), true, 'level' => 2, 'hier_list' => '222004,222007,222012', ), 
            array ('HP Deskjet A400', 8, array (222004, 222007, 222012, 8, ), false, 'level' => 3, 'hier_list' => '222004,222007,222012,8', ), 
            array ('HP Deskjet Advantage', 9, array (222004, 222007, 222012, 9, ), false, 'level' => 3, 'hier_list' => '222004,222007,222012,9', ),
            array ('LaserJet', 222008, array ( 222004, 222008, ), true, 'level' => 1, 'hier_list' => '222004,222008', ), 
            array ('Canon', 222014, array (222004, 222008, 222014, ), true, 'level' => 2, 'hier_list' => '222004,222008,222014', ), 
            array ('MF4770M', 11, array (222004, 222008, 222014, 11, ), false, 'level' => 3, 'hier_list' => '222004,222008,222014,11', ),             
            array ('HP',222013,  array (222004, 222008, 222013, ), true, 'level' => 2, 'hier_list' => '222004,222008,222013', ), 
            array ('HP LaserJet Pro 400', 10, array (222004, 222008, 222013, 10, ), false, 'level' => 3, 'hier_list' => '222004,222008,222013,10', ), 
            array ('SparkPrinting', 222009, array (222004, 222009, ),  true, 'level' => 1, 'hier_list' => '222004,222009', ), 
            array ('Sinclair', 222015, array (222004, 222009, 222015, ), true, 'level' => 2, 'hier_list' => '222004,222009,222015', ), 
            array ('ZxPrinter', 12,  array (222004, 222009, 222015, 12, ), false, 'level' => 3, 'hier_list' => '222004,222009,222015,12', ), 
            array ('Watches', 222016, array (222016, ), true, 'level' => 0, 'hier_list' => '222016', ), 
            array ('Casio G-Shock', 14, array (222016, 14, ), false, 'level' => 1, 'hier_list' => '222016,14', ), 
            array ('1yr - 400 Airtime Unlimited Nights & Weekends', 1, array (1, ), false, 'level' => 0, 'hier_list' => '1', ), 
            array ('1yr - 600 Airtime Unlimited Nights & Weekends', 2, array (2, ), false, 'level' => 0, 'hier_list' => '2', ), 
            array ('2yr - 400 Airtime Unlimited Nights & Weekends', 3, array (3, ), false, 'level' => 0, 'hier_list' => '3', ), 
            array ('2yr - 600 Airtime Unlimited Nights & Weekends', 4, array (4, ), false, 'level' => 0, 'hier_list' => '4', ), 
            array ('GS2010', 5,  array (5, ), false, 'level' => 0, 'hier_list' => '5', ), 
            array ('GS2010 Green', 6, array (6, ), false, 'level' => 0, 'hier_list' => '6', ),
            array ('OnlyDisabledAndVisible', 19, array ( 19, ), false, 'level' => 0, 'hier_list' => '19', ), 
            array ('OnlyServiceVisible', 20, 2 => array (20, ), false, 'level' => 0, 'hier_list' => '20', ), 
            array ('OnlyVisible', 17, 2 => array (17, ), false, 'level' => 0, 'hier_list' => '17', ),

        );

        $this->assertIdentical($result, $expected);
    }
    
    //@@@ QA 131105-000000 RightNow A&C: Recognize Product Catalog Disabled and Visibility Settings in Register Product Select CP
    function testGetDirectDescendantsDuringSearch() {
        //Top Level Products
        $expected = array(
            array('id' => 222005, 'label' => 'Laptops', 'hasChildren' => true),
            array('id' => 222004, 'label' => 'Printers', 'hasChildren' => true),
            array('id' => 222016, 'label' => 'Watches', 'hasChildren' => true),
            array('id' => 1, 'label' => '1yr - 400 Airtime Unlimited Nights & Weekends', 'hasChildren' => false, 'serialized' => false),
            array('id' => 2, 'label' => '1yr - 600 Airtime Unlimited Nights & Weekends', 'hasChildren' => false, 'serialized' => false),
            array('id' => 3, 'label' => '2yr - 400 Airtime Unlimited Nights & Weekends', 'hasChildren' => false, 'serialized' => false),
            array('id' => 4, 'label' => '2yr - 600 Airtime Unlimited Nights & Weekends', 'hasChildren' => false, 'serialized' => false),
            array('id' => 5, 'label' => 'GS2010', 'hasChildren' => false, 'serialized' => true),
            array('id' => 6, 'label' => 'GS2010 Green', 'hasChildren' => false, 'serialized' => true),
            array('id' => 19, 'label' => 'OnlyDisabledAndVisible', 'hasChildren' => false, 'serialized' => false), 
            array('id' => 20, 'label' => 'OnlyServiceVisible', 'hasChildren' => false, 'serialized' => false), 
            array('id' => 17, 'label' => 'OnlyVisible', 'hasChildren' => false, 'serialized' => false)
        );
        $response = $this->model->getDirectDescendants(null, 0, true);
        $this->responseCheck($response, 'array');
        $this->assertIdentical($response->result, $expected);
        // Folders with descendants
        $expected = array(      
            array('id' => 8, 'label' => 'HP Deskjet A400', 'hasChildren' => false, 'serialized' => true),
            array('id' => 9, 'label' => 'HP Deskjet Advantage', 'hasChildren' => false, 'serialized' => true)
        );

        $response = $this->model->getDirectDescendants(222012, 2, true);
        $this->responseCheck($response, 'array');
        $this->assertIdentical($response->result, $expected);

        $expected = array(      
            array('id' => 14, 'label' => 'Casio G-Shock', 'hasChildren' => false, 'serialized' => true)
        );

        $response = $this->model->getDirectDescendants(222016, 1, true);
        $this->responseCheck($response, 'array');
        $this->assertIdentical($response->result, $expected);

        $expected = array(
            array('id' => 222014, 'label' => 'Canon', 'hasChildren' => true),
            array('id' => 222013, 'label' => 'HP', 'hasChildren' => true)
        );

        $response = $this->model->getDirectDescendants(222008, 2, true);
        $this->responseCheck($response, 'array');

        $this->assertIdentical($response->result, $expected);
    }

    //@@@ QA 131126-000077 Assets - CP - display issue in asset details page
    function testGetFormattedChainForNonVisibleProduct() {
    	$expected = array(16);
        $response = $this->model->getFormattedChain(16, true, true);
        $this->responseCheck($response, 'array');
        $this->assertIdentical($response->result, $expected);

        $expected = array();
        $response = $this->model->getFormattedChain(16, false, false);
        $this->responseCheck($response, 'array');
        $this->assertIdentical($response->result, $expected);  
    }
}

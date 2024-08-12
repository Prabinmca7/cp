<?php

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

class SocialSubscriptionTest extends CPTestCase {

    public $testingClass = 'RightNow\Models\SocialSubscription';

    /**
     * MUT (model under test): SocialSubscription
     *
     * @var RightNow\Models\SocialSubscription
     */
    private $model;

    function __construct () {
        parent::__construct();
        $this->model = new RightNow\Models\SocialSubscription();
        // ID of the social question
        $this->socialQuestionID = 4;
        $this->socialUserID = 109;
        $this->productID = 130;
        $this->categoryID = 68;
        $this->invisibleCategoryID = 122;
    }

    function setUp () {
        parent::setUp();
        $this->logIn('slatest');
    }

    function testAddSubscriber () {
        $this->logOut();
        $this->_testUserPermissionsOnModel(array(0,'Question'), $this->model, 'addSubscription');
        $this->logIn('slatest');

        // if question id and user id not passed then it should be an error
        $response = $this->model->addSubscription(null, "Question");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));

        // if question id is null then it should be an error
        $response = $this->model->addSubscription(null, "Question");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));

        // successful subscription for a valid user and question
        $response = $this->model->addSubscription($this->socialQuestionID, "Question");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($response->result, CONNECT_NAMESPACE_PREFIX . '\CommunityUser');
        $this->model->deleteSubscription($this->socialQuestionID, "Question");
    }

    function testDeleteSubscription () {
        $this->logOut();
        $this->_testUserPermissionsOnModel(array(0,'Question'), $this->model, 'deleteSubscription');
        $this->logIn('slatest');

        $deletedQuestion = $this->CI->model('CommunityQuestion')->create(array(
                'CommunityQuestion.Product' => (object) array('value' => 6),
                'CommunityQuestion.Subject' => (object) array('value' => 'test question ' . __FUNCTION__),
                'CommunityQuestion.Body' => (object) array('value' => 'I am the question'),
                'CommunityQuestion.StatusWithType.Status.ID' => (object) array('value' => 31),
            ))->result;

        // if question id and user id not passed then it should be an error
        $response = $this->model->deleteSubscription(null, "Question");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));

        // if question is deleted then it should be an error
        $response = $this->model->deleteSubscription($deletedQuestion->ID, "Question");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));

        // deleting a subscription that does not exist should be an error
        $response = $this->model->deleteSubscription($this->socialQuestionID, "Question");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));

        // successfully unsubscribing to a question
        $this->model->addSubscription($this->socialQuestionID, "Question");
        $response = $this->model->deleteSubscription($this->socialQuestionID, "Question");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue($response->result, "Subscription could not be deleted");
        
        // unsubscribing to all questions
        $response = $this->model->deleteSubscription(-1, "Question");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertTrue($response->result, "Subscriptions could not be deleted");              
        $socialUserObject = $this->CI->model('CommunityUser')->get($this->socialUserID)->result;
        $this->assertSame(0, count($socialUserObject->CommunityQuestionSubscriptions));
    }

    function testIsUserSubscribed () {
        // if question id and user id not passed then it should be an error
        $response = $this->model->getSubscriptionID(null, null, "Question");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));

        $this->model->addSubscription($this->socialQuestionID, "Question");
        $subscriptionID = $this->model->getSubscriptionID($this->socialQuestionID, $this->socialUserID, "Question")->result;
        $this->assertTrue($subscriptionID !== null, "User is not subscribed to the question");

        $this->model->deleteSubscription($this->socialQuestionID, "Question");
        $subscriptionID = $this->model->getSubscriptionID($this->socialQuestionID, $this->socialUserID, "Question")->result;
        $this->assertTrue($subscriptionID === null, "User is subscribed to the question");
    }

    function testAddProductSubscription () {
        // if question id and user id not passed then it should be an error
        $response = $this->model->addSubscription(null, "Product");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));

        // if question id is null then it should be an error
        $response = $this->model->addSubscription(null, "Product");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));

        // successful subscription for a valid user and product
        $response = $this->model->addSubscription($this->productID, "Product");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($response->result, CONNECT_NAMESPACE_PREFIX . '\CommunityUser');
        
        $this->logOut(); 
        $this->logIn('userprodonly');
        // user does not have permission on the product thus it should be an error
        $response = $this->model->addSubscription($this->productID, "Product");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));
        $this->logOut();
        $this->logIn('slatest');
    }

    function testAddCategorySubscription () {
        // if question id and user id not passed then it should be an error
        $response = $this->model->addSubscription(null, "Category");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));

        // if question id is null then it should be an error
        $response = $this->model->addSubscription(null, "Category");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));

        // successful subscription for a valid user and category
        $response = $this->model->addSubscription($this->categoryID, "Category");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($response->result, CONNECT_NAMESPACE_PREFIX . '\CommunityUser');
    }

    function testGetProductSubscriptionID () {
        // delete any existing subscriptions
        $response = $this->model->deleteSubscription($this->productID, "Product");

        $response = $this->assertResponseObject($this->model->getSubscriptionID($this->productID, $this->socialUserID, "Product"));
        $this->assertNull($response->result, "");
        $response = $this->assertResponseObject($this->model->addSubscription($this->productID, "Product"));
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($response->result, CONNECT_NAMESPACE_PREFIX . '\CommunityUser');
        $response = $this->assertResponseObject($this->model->getSubscriptionID($this->productID, $this->socialUserID, "Product"));
        $this->assertNotNull($response->result);
    }

    function testGetCategorySubscriptionID () {
        // delete any existing subscriptions
        $response = $this->model->deleteSubscription($this->categoryID, "Category");

        $response = $this->model->getSubscriptionID($this->categoryID, $this->socialUserID, "Category");
        $this->assertNull($response->result);
        $response = $this->model->addSubscription($this->categoryID, "Category");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($response->result, CONNECT_NAMESPACE_PREFIX . '\CommunityUser');
        $response = $this->model->getSubscriptionID($this->categoryID, $this->socialUserID, "Category");
        $this->assertNotNull($response->result);
    }

    function testDeleteProductSubscriptionID () {
        // delete any existing subscriptions
        $response = $this->model->deleteSubscription($this->productID, "Product");

        $response = $this->model->getSubscriptionID($this->productID, $this->socialUserID, "Product");
        $this->assertNull($response->result);
        $response = $this->model->addSubscription($this->productID, "Product");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertIsA($response->result, CONNECT_NAMESPACE_PREFIX . '\CommunityUser');
        $response = $this->model->deleteSubscription($this->productID, "Product");
        $this->assertTrue($response->result);
        $response = $this->model->getSubscriptionID($this->productID, $this->socialUserID, "Product");
        $this->assertNull($response->result);
        // unsubscribing to all products
        $response = $this->model->deleteSubscription(-1, "Product");
        $this->assertTrue($response->result, "Subscriptions could not be deleted");
        $socialUserObject = $this->CI->model('CommunityUser')->get($this->socialUserID)->result;
        $this->assertSame(0, count($socialUserObject->CommunityProductSubscriptions));
    }

    function testDeleteCategorySubscriptionID () {
        // delete any existing subscriptions
        $response = $this->model->deleteSubscription($this->categoryID, "Category");

        $response = $this->model->getSubscriptionID($this->categoryID, $this->socialUserID, "Category");
        $this->assertNull($response->result);
        $response = $this->model->addSubscription($this->categoryID, "Category");
        $this->assertNotNull($response->result);
        $response = $this->model->deleteSubscription($this->categoryID, "Category");
        $this->assertTrue($response->result);
        $response = $this->model->getSubscriptionID($this->categoryID, $this->socialUserID, "Category");
        $this->assertNull($response->result);
    }

    function testAddInvisibleCategorySubscription () {
        $response = $this->model->addSubscription($this->invisibleCategoryID, "Category");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));
    }
    
    function testAddSubscriberForNonActiveUser () {
        $this->logOut();
        $this->_testUserPermissionsOnModel(array(0,'Question'), $this->model, 'addSubscription');
        $this->logIn('userdeleted');

        // subscription fails for a non-active user
        $response = $this->model->addSubscription($this->socialQuestionID, "Question");
        $this->assertIsA($response, 'RightNow\Libraries\ResponseObject');
        $this->assertSame(1, count($response->errors));
        $this->logOut();
    }
}

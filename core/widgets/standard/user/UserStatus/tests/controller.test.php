<?

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\UnitTest\Fixture as Fixture;

class UserStatusTest extends WidgetTestCase {
    public $testingWidget = 'standard/user/UserStatus';

    private $fixtureInstance;
    private $users = array();

    function setUpBeforeClass() {
        $this->fixtureInstance = new Fixture();
        $this->users['active'] = $this->fixtureInstance->make('UserActive1')->ID;
        $this->users['archived'] = $this->fixtureInstance->make('UserArchive')->ID;
        $this->users['pending'] = $this->fixtureInstance->make('UserPending')->ID;
        $this->users['suspended'] = $this->fixtureInstance->make('UserSuspended')->ID;
        $this->users['deleted'] = $this->fixtureInstance->make('UserDeleted')->ID;
    }

    function tearDownAfterClass() {
        $this->fixtureInstance->destroy();
        $this->fixtureInstance = null;
    }

    function getData($user, $attributes = array()) {
        $this->addUrlParameters(array('user' => $user));
        $instance = $this->createWidgetInstance($attributes);
        $data = $this->getWidgetData();
        $this->restoreUrlParameters();
        return $data;
    }

    function getUser($user) {
        $user = $this->CI->model('CommunityUser')->get($user)->result;
        \RightNow\Libraries\Decorator::add($user, array('class' => 'Permission/SocialUserPermissions', 'property' => 'SocialPermissions'));
        return $user;
    }

    function testGetData() {
        // No user
        $data = $this->getData(null);
        $this->assertNull($data['status']);

        // Active user, display_active false
        $data = $this->getData($this->users['active']);
        $this->assertNull($data['status']);

        // Active user, display_active true
        $data = $this->getData($this->users['active'], array("display_active" => "true"));
        $this->assertIdentical($data['status'], 'active');

        // Deleted user
        $data = $this->getData($this->users['deleted']);
        $this->assertNull($data['status']);

        // Archived user
        $data = $this->getData($this->users['archived']);
        $this->assertIdentical($data['status'], 'archived');
    }

    function testGetUserStatus() {
        $this->createWidgetInstance();
        $getUserStatus = $this->getWidgetMethod('getUserStatus');

        foreach($this->users as $expectedStatus => $id) {
            $status = $getUserStatus($this->getUser($id));
            $this->assertIdentical($status, $expectedStatus);
        }
    }
}

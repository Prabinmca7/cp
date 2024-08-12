<?

RightNow\UnitTest\Helper::loadTestedFile(__FILE__);
use RightNow\UnitTest\Fixture as Fixture;

class ModerationProfileTest extends WidgetTestCase {
    public $testingWidget = 'standard/moderation/ModerationProfile';

    function setUp() {
        $this->fixtureInstance = new Fixture();
        $this->fixtureActive1 = $this->fixtureInstance->make('UserActive1');
        $this->fixtureActive2 = $this->fixtureInstance->make('UserActive2');
        $this->fixtureMod = $this->fixtureInstance->make('UserModActive');
    }

    function tearDown() {
        $this->fixtureInstance->destroy();
    }

    function getData($user) {
        $this->addUrlParameters(array('user' => $user));
        $instance = $this->createWidgetInstance();
        $data = $this->getWidgetData();
        $this->restoreUrlParameters();
        return $data;
    }

    function testGetData() {
        // No valid profile
        $data = $this->getData(null);
        $this->assertNull($data['userData']);

        // Active user profile, no one logged in
        $data = $this->getData($this->fixtureActive1->ID);
        $this->assertNull($data['userData']);

        // Active user profile, non-permissioned user logged in
        $this->logIn($this->fixtureActive1->Contact->Login);
        $data = $this->getData($this->fixtureActive2->ID);
        $this->assertNull($data['userData']);
        $this->logOut();

        // Active user profile, moderator logged in
        $this->logIn($this->fixtureMod->Contact->Login);
        $data = $this->getData($this->fixtureActive1->ID);
        $this->assertTrue($data['userData'] === array(
            'email'     => $this->fixtureActive1->Contact->Emails[0]->Address,
            'firstName' => $this->fixtureActive1->Contact->Name->First,
            'lastName'  => $this->fixtureActive1->Contact->Name->Last));
        $this->logOut();
    }
}

<?php
RightNow\UnitTest\Helper::loadTestedFile(__FILE__);

use RightNow\Connect\v1_4 as Connect,
    RightNow\Libraries\Decorator,
    RightNow\Utils\Connect as ConnectUtil,
    RightNow\Libraries\TabularDataObject,
    RightNow\Decorators;

class SocialUserPermissionsTest extends CPTestCase {

    function getMockedUser(array $fields){
        $question = new MockedSocialUser();
        foreach($fields as $key => $value){
            $question->$key = $value;
        }
        return $question;
    }

    function clearCache($decoratorInstance){
        $cache = new \ReflectionProperty($decoratorInstance, 'cache');
        $cache->setAccessible(true);
        $cache->setValue($decoratorInstance, array());
    }

    function getDecoratedUser($id = null){
        if($id === null){
            $user = new Connect\CommunityUser;
        }
        else{
            $user = Connect\CommunityUser::fetch($id);
        }
        Decorator::add($user, array('class' => 'Permission/SocialUserPermissions', 'property' => 'SocialPermissions'));
        return $user;
    }

    function testStatus(){
        $mockUser = $this->getMockedUser(array('StatusWithType' => (object)array('StatusType' => (object)array('ID' => STATUS_TYPE_SSS_USER_ACTIVE))));
        $decorator = new Decorators\SocialUserPermissions($mockUser);

        $this->assertTrue($decorator->isActive());
        $this->assertFalse($decorator->isPending());
        $this->assertFalse($decorator->isSuspended());
        $this->assertFalse($decorator->isDeleted());
        $this->assertFalse($decorator->isArchived());

        $this->clearCache($decorator);
        $mockUser->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_USER_PENDING;
        $this->assertFalse($decorator->isActive());
        $this->assertTrue($decorator->isPending());
        $this->assertFalse($decorator->isSuspended());
        $this->assertFalse($decorator->isDeleted());
        $this->assertFalse($decorator->isArchived());

        $this->clearCache($decorator);
        $mockUser->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_USER_SUSPENDED;
        $this->assertFalse($decorator->isActive());
        $this->assertFalse($decorator->isPending());
        $this->assertTrue($decorator->isSuspended());
        $this->assertFalse($decorator->isDeleted());
        $this->assertFalse($decorator->isArchived());

        $this->clearCache($decorator);
        $mockUser->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_USER_DELETED;
        $this->assertFalse($decorator->isActive());
        $this->assertFalse($decorator->isPending());
        $this->assertFalse($decorator->isSuspended());
        $this->assertTrue($decorator->isDeleted());
        $this->assertFalse($decorator->isArchived());

        $this->clearCache($decorator);
        $mockUser->StatusWithType->StatusType->ID = STATUS_TYPE_SSS_USER_ARCHIVE;
        $this->assertFalse($decorator->isActive());
        $this->assertFalse($decorator->isPending());
        $this->assertFalse($decorator->isSuspended());
        $this->assertFalse($decorator->isDeleted());
        $this->assertTrue($decorator->isArchived());
    }

    function testIsModerator(){
        
        // user moderator should return true
        $this->logIn('usermoderator');
        $user = $this->CI->model('CommunityUser')->get()->result;
        $this->clearCache($user->SocialPermissions);
        $this->assertTrue($user->SocialPermissions->isModerator());
        $this->logOut();
        
        // moderator should return true
        $this->logIn('slatest');
        $user = $this->CI->model('CommunityUser')->get()->result;
        $this->clearCache($user->SocialPermissions);
        $this->assertTrue($user->SocialPermissions->isModerator());
        $this->logOut();

        //non-moderator should return false
        $this->logIn('useractive1');
        $user = $this->CI->model('CommunityUser')->get()->result;
        $this->clearCache($user->SocialPermissions);
        $this->assertFalse($user->SocialPermissions->isModerator());
        $this->logOut();
    }

    function testCanRead(){
        $mockUser = $this->getMockedUser(array());
        $decorator = new Decorators\SocialUserPermissions($mockUser);

        $this->assertFalse($decorator->canRead());

        $this->clearCache($decorator);
        $mockUser->setResultForPermission(PERM_SOCIALUSER_READ, false);
        $this->assertFalse($decorator->canRead());

        $this->clearCache($decorator);
        $mockUser->setResultForPermission(PERM_SOCIALUSER_READ, true);
        $this->assertTrue($decorator->canRead());
    }

    function testCanReadContactDetails(){
        // user with permission
        $this->logIn('slatest');
        $user = $this->CI->model('CommunityUser')->get()->result;
        $this->clearCache($user->SocialPermissions);
        $this->assertTrue($user->SocialPermissions->canReadContactDetails());
        $this->logOut();

        // user without permission
        $this->logIn('useractive1');
        $user = $this->CI->model('CommunityUser')->get()->result;
        $this->clearCache($user->SocialPermissions);
        $this->assertFalse($user->SocialPermissions->canReadContactDetails());
        $this->logOut();
    }

    function testCanReadWithTabularData() {
        // not-logged-in user should still have permission
        $tabularUser = new TabularDataObject();
        $decorator = new Decorators\SocialUserPermissions($tabularUser);
        $this->assertTrue($decorator->canRead());

        // regular user should also have permission
        $this->logIn('useractive1');
        $tabularUser = new TabularDataObject();
        $decorator = new Decorators\SocialUserPermissions($tabularUser);
        $this->assertTrue($decorator->canRead());
    }

    function testCanCreate() {
        // admins should be allowed to create
        $this->logIn('slatest');
        $user = new Connect\CommunityUser();
        $decorator = new Decorators\SocialUserPermissions($user);
        $this->assertTrue($decorator->canCreate());
        $this->logOut();

        // moderators should not be allowed to create
        $this->logIn('modactive1');
        $this->clearCache($decorator);
        $this->assertFalse($decorator->canCreate());
        $this->logOut();

        // inactive moderator should not be allowed to create
        $this->logIn('modpending');
        $this->clearCache($decorator);
        $this->assertFalse($decorator->canCreate());
        $this->logOut();

        // non moderator should not be able to create
        $this->logIn('useractive1');
        $this->clearCache($decorator);
        $this->assertFalse($decorator->canCreate());
    }

    function testCanUpdate() {
        // anonymous (non-logged-in) user cannot update
        $user = $this->getDecoratedUser(101);
        $this->assertFalse($user->SocialPermissions->canUpdate());

        // moderator should be allowed to change
        $this->logIn('modactive1');
        $user = $this->CI->model('CommunityUser')->get()->result;
        $this->clearCache($user->SocialPermissions);
        $this->assertTrue($user->SocialPermissions->canUpdate());
        $this->logOut();

        // non moderator should not be able to change others
        $this->logIn('useractive1');
        $this->clearCache($user->SocialPermissions);
        $this->assertFalse($user->SocialPermissions->canUpdate());

        // but they should be able to change themselves
        $user = $this->getDecoratedUser($this->CI->model('CommunityUser')->get()->result->ID);
        $this->assertTrue($user->SocialPermissions->canUpdate());
        $this->logOut();

        // pending moderators are allowed to change their own display name and avatar
        // (but cannot update anything else)
        $this->logIn('modpending');
        $user = $this->getDecoratedUser($this->CI->model('CommunityUser')->get()->result->ID);
        $this->clearCache($user->SocialPermissions);
        $this->assertTrue($user->SocialPermissions->canUpdate());
        $this->assertTrue($user->SocialPermissions->canUpdateAvatar());
        $this->assertTrue($user->SocialPermissions->canUpdateDisplayName());
        $this->assertFalse($user->SocialPermissions->canDelete());
        $this->assertFalse($user->SocialPermissions->canUpdateStatus());
        $this->logOut();

        // suspended moderators are allowed to change their own display name and avatar
        // (but cannot update anything else)
        $this->logIn('modsuspended');
        $user = $this->getDecoratedUser($this->CI->model('CommunityUser')->get()->result->ID);
        $this->clearCache($user->SocialPermissions);
        $this->assertTrue($user->SocialPermissions->canUpdate());
        $this->assertTrue($user->SocialPermissions->canUpdateAvatar());
        $this->assertTrue($user->SocialPermissions->canUpdateDisplayName());
        $this->assertFalse($user->SocialPermissions->canDelete());
        $this->assertFalse($user->SocialPermissions->canUpdateStatus());
        $this->logOut();

        // deleted and archived mods cannot update anything
        $this->logIn('moddeleted');
        $user = $this->getDecoratedUser($this->CI->model('CommunityUser')->get()->result->ID);
        $this->clearCache($user->SocialPermissions);
        $this->assertFalse($user->SocialPermissions->canUpdate());
        $this->assertFalse($user->SocialPermissions->canUpdateAvatar());
        $this->assertFalse($user->SocialPermissions->canUpdateDisplayName());
        $this->assertFalse($user->SocialPermissions->canDelete());
        $this->assertFalse($user->SocialPermissions->canUpdateStatus());
        $this->logOut();

        $this->logIn('modarchive');
        $user = $this->getDecoratedUser($this->CI->model('CommunityUser')->get()->result->ID);
        $this->clearCache($user->SocialPermissions);
        $this->assertFalse($user->SocialPermissions->canUpdate());
        $this->assertFalse($user->SocialPermissions->canUpdateAvatar());
        $this->assertFalse($user->SocialPermissions->canUpdateDisplayName());
        $this->assertFalse($user->SocialPermissions->canDelete());
        $this->assertFalse($user->SocialPermissions->canUpdateStatus());
        $this->logOut();
    }

    function testCanUpdateWithTabularData() {
        // regular user should not have permission
        $this->logIn('useractive1');
        $tabularUser = new TabularDataObject();
        $decorator = new Decorators\SocialUserPermissions($tabularUser);
        $this->assertFalse($decorator->canUpdate());

        // admin user should have permission
        $this->logIn('useradmin');
        $tabularUser = new TabularDataObject();
        $decorator = new Decorators\SocialUserPermissions($tabularUser);
        $this->assertTrue($decorator->canUpdate());
    }

    function testCanUpdateChecksPermissions() {
        $this->logIn('useractive1');
        $mockUser = $this->getMockedUser(array('StatusWithType' => (object)array('StatusType' => (object)array('ID' => STATUS_TYPE_SSS_USER_ACTIVE))));
        $decorator = new Decorators\SocialUserPermissions($mockUser);

        // permissions are off by default
        $this->assertFalse($decorator->canUpdate());

        // succeeds with only UPDATE
        $this->clearCache($decorator);
        $mockUser->setResultForPermission(PERM_SOCIALUSER_UPDATE, true);
        $this->assertTrue($decorator->canUpdate());
    }

    function testCanUpdateAvatar() {
        // anonymous (non-logged-in) user cannot update avatar
        $user = $this->getDecoratedUser(101);
        $this->assertFalse($user->SocialPermissions->canUpdateAvatar());

        // moderator should be allowed to change avatar
        $this->logIn('modactive1');
        $this->clearCache($user->SocialPermissions);
        $this->assertTrue($user->SocialPermissions->canUpdateAvatar());
        $this->logOut();

        // non moderator should not be able to change other's avatar
        $this->logIn('useractive1');
        $this->clearCache($user->SocialPermissions);
        $this->assertFalse($user->SocialPermissions->canUpdateAvatar());

        // but they should be able to change their own
        $user = $this->getDecoratedUser($this->CI->model('CommunityUser')->get()->result->ID);
        $this->assertTrue($user->SocialPermissions->canUpdateAvatar());
        $this->logOut();

        // non active moderators shouldn't be allowed to change avatar
        $this->logIn('modpending');
        $this->clearCache($user->SocialPermissions);
        $this->assertFalse($user->SocialPermissions->canUpdateAvatar());
        $this->logOut();

        $this->logIn('modsuspended');
        $this->clearCache($user->SocialPermissions);
        $this->assertFalse($user->SocialPermissions->canUpdateAvatar());
        $this->logOut();

        $this->logIn('moddeleted');
        $this->clearCache($user->SocialPermissions);
        $this->assertFalse($user->SocialPermissions->canUpdateAvatar());
        $this->logOut();

        $this->logIn('modarchive');
        $this->clearCache($user->SocialPermissions);
        $this->assertFalse($user->SocialPermissions->canUpdateAvatar());
        $this->logOut();
    }

    function testCanUpdateAvatarChecksPermissions() {
        $this->logIn('useractive1');
        $mockUser = $this->getMockedUser(array('StatusWithType' => (object)array('StatusType' => (object)array('ID' => STATUS_TYPE_SSS_USER_ACTIVE))));
        $decorator = new Decorators\SocialUserPermissions($mockUser);

        // permissions are off by default
        $this->assertFalse($decorator->canUpdateAvatar());

        // fails with only UPDATE
        $mockUser = $this->getMockedUser(array('StatusWithType' => (object)array('StatusType' => (object)array('ID' => STATUS_TYPE_SSS_USER_ACTIVE))));
        $mockUser->setResultForPermission(PERM_SOCIALUSER_UPDATE, true);
        $decorator = new Decorators\SocialUserPermissions($mockUser);
        $this->assertFalse($decorator->canUpdateAvatar());

        // succeeds with both UPDATE and UPDATE_AVATAR
        $mockUser = $this->getMockedUser(array('StatusWithType' => (object)array('StatusType' => (object)array('ID' => STATUS_TYPE_SSS_USER_ACTIVE))));
        $mockUser->setResultForPermission(PERM_SOCIALUSER_UPDATE_AVATAR, true);
        $mockUser->setResultForPermission(PERM_SOCIALUSER_UPDATE, true);
        $decorator = new Decorators\SocialUserPermissions($mockUser);
        $this->assertTrue($decorator->canUpdateAvatar());

        // fails with only UPDATE_AVATAR
        $mockUser = $this->getMockedUser(array('StatusWithType' => (object)array('StatusType' => (object)array('ID' => STATUS_TYPE_SSS_USER_ACTIVE))));
        $mockUser->setResultForPermission(PERM_SOCIALUSER_UPDATE, false);
        $decorator = new Decorators\SocialUserPermissions($mockUser);
        $this->assertFalse($decorator->canUpdateAvatar());
    }

    function testCanUpdateDisplayName() {
        $this->logOut();
        // anonymous (non-logged-in) user cannot update display name
        $user = $this->getDecoratedUser(101);
        $this->assertFalse($user->SocialPermissions->canUpdateDisplayName());

        // moderator should be allowed to change display name
        $this->logIn('modactive1');
        $this->clearCache($user->SocialPermissions);
        $this->assertTrue($user->SocialPermissions->canUpdateDisplayName());
        $this->logOut();

        // non moderator should not be able to change other's display name
        $this->logIn('useractive1');
        $this->clearCache($user->SocialPermissions);
        $this->assertFalse($user->SocialPermissions->canUpdateDisplayName());

        // but they should be able to change their own
        $user = $this->getDecoratedUser($this->CI->model('CommunityUser')->get()->result->ID);
        $this->clearCache($user->SocialPermissions);
        $this->clearCache($user->SocialPermissions);
        $this->assertTrue($user->SocialPermissions->canUpdateDisplayName());
        $this->logOut();

        // non active moderators shouldn't be allowed to change display name
        $this->logIn('modpending');
        $this->clearCache($user->SocialPermissions);
        $this->assertFalse($user->SocialPermissions->canUpdateDisplayName());
        $this->logOut();

        $this->logIn('modsuspended');
        $this->clearCache($user->SocialPermissions);
        $this->assertFalse($user->SocialPermissions->canUpdateDisplayName());
        $this->logOut();

        $this->logIn('moddeleted');
        $this->clearCache($user->SocialPermissions);
        $this->assertFalse($user->SocialPermissions->canUpdateDisplayName());
        $this->logOut();

        $this->logIn('modarchive');
        $this->clearCache($user->SocialPermissions);
        $this->assertFalse($user->SocialPermissions->canUpdateDisplayName());
        $this->logOut();
    }

    function testCanUpdateDisplayNameChecksPermissions() {
        $this->logIn('useractive1');
        $mockUser = $this->getMockedUser(array('StatusWithType' => (object)array('StatusType' => (object)array('ID' => STATUS_TYPE_SSS_USER_ACTIVE))));
        $decorator = new Decorators\SocialUserPermissions($mockUser);

        // permissions are off by default
        $this->assertFalse($decorator->canUpdateDisplayName());

        // fails with only UPDATE
        $mockUser = $this->getMockedUser(array('StatusWithType' => (object)array('StatusType' => (object)array('ID' => STATUS_TYPE_SSS_USER_ACTIVE))));
        $mockUser->setResultForPermission(PERM_SOCIALUSER_UPDATE, true);
        $decorator = new Decorators\SocialUserPermissions($mockUser);
        $this->assertFalse($decorator->canUpdateDisplayName());

        // succeeds with both UPDATE and UPDATE_DISPLAYNAME
        $mockUser = $this->getMockedUser(array('StatusWithType' => (object)array('StatusType' => (object)array('ID' => STATUS_TYPE_SSS_USER_ACTIVE))));
        $mockUser->setResultForPermission(PERM_SOCIALUSER_UPDATE_DISPLAYNAME, true);
        $mockUser->setResultForPermission(PERM_SOCIALUSER_UPDATE, true);
        $decorator = new Decorators\SocialUserPermissions($mockUser);
        $this->assertTrue($decorator->canUpdateDisplayName());

        // fails with only UPDATE_DISPLAYNAME
        $mockUser = $this->getMockedUser(array('StatusWithType' => (object)array('StatusType' => (object)array('ID' => STATUS_TYPE_SSS_USER_ACTIVE))));
        $mockUser->setResultForPermission(PERM_SOCIALUSER_UPDATE, false);
        $decorator = new Decorators\SocialUserPermissions($mockUser);
        $this->assertFalse($decorator->canUpdateDisplayName());
    }

    function testCanUpdateStatus() {
        $user = $this->getDecoratedUser(101);
        $this->assertFalse($user->SocialPermissions->canUpdateStatus());

        //Moderator should be allowed to change status of a user
        $this->logIn('modactive1');
        $user = $this->getDecoratedUser(102);
        $this->assertTrue($user->SocialPermissions->canUpdateStatus());
        $this->logOut();

        //Moderator should not be allowed to change status of a deleted user
        //Recently Connect API is throwing exception on changing status of deleted user
        /*$user->StatusWithType->Status = 40; // deleted user
        $user->save();
        $this->assertEqual(STATUS_TYPE_SSS_USER_DELETED, $user->StatusWithType->StatusType->ID);
        $this->clearCache($user->SocialPermissions);
        $this->assertFalse($user->SocialPermissions->canUpdateStatus());
        $user->StatusWithType->Status = 38; // active user
        $user->save();
        $this->assertEqual(STATUS_TYPE_SSS_USER_ACTIVE, $user->StatusWithType->StatusType->ID);*/

        //Non moderator should not be able to change status
        $this->logIn('useractive1');
        $this->clearCache($user->SocialPermissions);
        $this->assertFalse($user->SocialPermissions->canUpdateStatus());
        $this->logOut();

        //Non active moderators shouldn't be allowed to change status
        $this->logIn('modpending');
        $this->clearCache($user->SocialPermissions);
        $this->assertFalse($user->SocialPermissions->canUpdateStatus());
        $this->logOut();

        $this->logIn('modsuspended');
        $this->clearCache($user->SocialPermissions);
        $this->assertFalse($user->SocialPermissions->canUpdateStatus());
        $this->logOut();

        $this->logIn('moddeleted');
        $this->clearCache($user->SocialPermissions);
        $this->assertFalse($user->SocialPermissions->canUpdateStatus());
        $this->logOut();

        $this->logIn('modarchive');
        $this->clearCache($user->SocialPermissions);
        $this->assertFalse($user->SocialPermissions->canUpdateStatus());
        $this->logOut();

        $this->logIn('userupdateonly');
        $this->clearCache($user->SocialPermissions);
        $this->assertTrue($user->SocialPermissions->canUpdateStatus());
        $this->logOut();

        $this->logIn('userdeletenoupdatestatus');
        $this->clearCache($user->SocialPermissions);
        $this->assertFalse($user->SocialPermissions->canUpdateStatus());
        $this->logOut();
    }

    function testCanUpdateStatusChecksPermissions() {
        $this->logIn('useractive1');
        $mockUser = $this->getMockedUser(array('StatusWithType' => (object)array('StatusType' => (object)array('ID' => STATUS_TYPE_SSS_USER_ACTIVE))));
        $decorator = new Decorators\SocialUserPermissions($mockUser);

        // permissions are off by default
        $this->assertFalse($decorator->canUpdateStatus());

        // fails with only UPDATE
        $this->clearCache($decorator);
        $mockUser->setResultForPermission(PERM_SOCIALUSER_UPDATE, true);
        $this->assertFalse($decorator->canUpdateStatus());

        // succeeds with both UPDATE and UPDATE_STATUS
        $this->clearCache($decorator);
        $mockUser->setResultForPermission(PERM_SOCIALUSER_UPDATE_STATUS, true);
        $this->assertTrue($decorator->canUpdateStatus());

        // fails with only UPDATE_STATUS
        $this->clearCache($decorator);
        $mockUser->setResultForPermission(PERM_SOCIALUSER_UPDATE, false);
        $this->assertFalse($decorator->canUpdateStatus());
    }

    function testCanDelete() {
        $user = $this->getDecoratedUser(101);
        $this->assertFalse($user->SocialPermissions->canDelete());

        // Moderator should be allowed to delete a user
        /* TK - after 140807-000069, this assert should pass
        $this->logIn('modactive1');
        $user = $this->CI->model('CommunityUser')->get()->result;
        $this->clearCache($user->SocialPermissions);
        $this->assertTrue($user->SocialPermissions->canDelete());
        $this->logOut();
         */

        // Suspended or pending moderators should not be allowed to delete a user
        $this->logIn('modpending');
        $this->clearCache($user->SocialPermissions);
        $this->assertFalse($user->SocialPermissions->canDelete());
        $this->logOut();

        $this->logIn('modsuspended');
        $this->clearCache($user->SocialPermissions);
        $this->assertFalse($user->SocialPermissions->canDelete());
        $this->logOut();

        // Admin should be allowed to delete a user
        $this->logIn('slatest');
        $this->clearCache($user->SocialPermissions);
        $this->assertTrue($user->SocialPermissions->canDelete());

        // Non moderator should not be able to delete
        $this->logIn('useractive1');
        $this->clearCache($user->SocialPermissions);
        $this->assertFalse($user->SocialPermissions->canDelete());
        $this->logOut();

        $this->logIn('userupdateonly');
        $this->clearCache($user->SocialPermissions);
        $this->assertFalse($user->SocialPermissions->canDelete());
        $this->logOut();

        $this->logIn('userdeletenoupdatestatus');
        $this->clearCache($user->SocialPermissions);
        $this->assertTrue($user->SocialPermissions->canDelete());
        $this->logOut();
    }

    function testCanDeleteChecksPermissions() {
        $this->logIn('useractive1');
        $mockUser = $this->getMockedUser(array('StatusWithType' => (object)array('StatusType' => (object)array('ID' => STATUS_TYPE_SSS_USER_ACTIVE))));
        $decorator = new Decorators\SocialUserPermissions($mockUser);

        // permissions are off by default
        $this->assertFalse($decorator->canDelete());

        // fails with only UPDATE
        $this->clearCache($decorator);
        $mockUser->setResultForPermission(PERM_SOCIALUSER_UPDATE, true);
        $this->assertFalse($decorator->canDelete());

        // succeeds with both UPDATE and UPDATE_STATUS_DELETE
        $this->clearCache($decorator);
        $mockUser->setResultForPermission(PERM_SOCIALUSER_UPDATE_STATUS_DELETE, true);
        $this->assertTrue($decorator->canDelete());

        // fails with only UPDATE_STATUS_DELETE
        $this->clearCache($decorator);
        $mockUser->setResultForPermission(PERM_SOCIALUSER_UPDATE_STATUS_DELETE, false);
        $this->assertFalse($decorator->canDelete());
    }
}

class MockedSocialUser extends Connect\RNObject{
    private $permissionResults = array();

    static function &getMetadata(){
        return (object)array('COM_type' => 'CommunityUser');
    }

    function setResultForPermission($permissionID, $returnValue){
        $this->permissionResults[$permissionID] = $returnValue;
    }

    function clearPermissions(){
        $this->permissionResults = array();
    }

    function hasPermission($permission){
        $permissionID = $permission instanceof ConnectPHP\NamedID ? $permission->ID : $permission;
        return $this->permissionResults[$permissionID] ?: false;
    }
}

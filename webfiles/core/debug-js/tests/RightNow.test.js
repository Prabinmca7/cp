UnitTest.addSuite({
    type: UnitTest.Type.Framework,
    namespaces: ['RightNow.Profile']
}, function(Y){
    var rightnowProfileTests = new Y.Test.Suite("RightNow.Profile");

    rightnowProfileTests.add(new Y.Test.Case({
        name: "profileTests",

        testIsLoggedIn: function() {
            this.setProfileData({isLoggedIn: false});
            Y.Assert.areSame(RightNow.Profile.isLoggedIn(), false);
            this.setProfileData({isLoggedIn: true});
            Y.Assert.areSame(RightNow.Profile.isLoggedIn(), true);
        },

        testIsSocialUser: function(){
            //Doesn't matter what the social user ID is, if they aren't logged in, they aren't a social user
            this.setProfileData({isLoggedIn: false, socialUserID: null});
            Y.Assert.areSame(RightNow.Profile.isSocialUser(), false);
            this.setProfileData({isLoggedIn: false, socialUserID: 10});
            Y.Assert.areSame(RightNow.Profile.isSocialUser(), false);
            this.setProfileData({isLoggedIn: false, socialUserID: 0});
            Y.Assert.areSame(RightNow.Profile.isSocialUser(), false);

            this.setProfileData({isLoggedIn: true, socialUserID: null});
            Y.Assert.areSame(RightNow.Profile.isSocialUser(), false);
            this.setProfileData({isLoggedIn: true, socialUserID: 10});
            Y.Assert.areSame(RightNow.Profile.isSocialUser(), true);
            this.setProfileData({isLoggedIn: true, socialUserID: 0});
            Y.Assert.areSame(RightNow.Profile.isSocialUser(), true);
        },

        testSocialUserID: function(){
            //Doesn't matter what the social user ID is, if they aren't logged in, they don't have an ID
            this.setProfileData({isLoggedIn: false, socialUserID: null});
            Y.Assert.areSame(RightNow.Profile.socialUserID(), null);
            this.setProfileData({isLoggedIn: false, socialUserID: 10});
            Y.Assert.areSame(RightNow.Profile.socialUserID(), null);
            this.setProfileData({isLoggedIn: false, socialUserID: 0});
            Y.Assert.areSame(RightNow.Profile.socialUserID(), null);

            this.setProfileData({isLoggedIn: true, socialUserID: null});
            Y.Assert.areSame(RightNow.Profile.socialUserID(), null);
            this.setProfileData({isLoggedIn: true, socialUserID: 10});
            Y.Assert.areSame(RightNow.Profile.socialUserID(), 10);
            this.setProfileData({isLoggedIn: true, socialUserID: 0});
            Y.Assert.areSame(RightNow.Profile.socialUserID(), 0);
        },

        testFirstName: function() {
            this.setProfileData({isLoggedIn: false});
            Y.Assert.areSame(RightNow.Profile.firstName(), '');
            this.setProfileData({isLoggedIn: true, firstName: 'asdf'});
            Y.Assert.areSame(RightNow.Profile.firstName(), 'asdf');
        },

        testLastName: function() {
            this.setProfileData({isLoggedIn: false});
            Y.Assert.areSame(RightNow.Profile.lastName(), '');
            this.setProfileData({isLoggedIn: true, lastName: 'fdsa'});
            Y.Assert.areSame(RightNow.Profile.lastName(), 'fdsa');
        },

        testFullName: function() {
            this.setProfileData({isLoggedIn: false});
            Y.Assert.areSame(RightNow.Profile.fullName(), '');
            this.setProfileData({isLoggedIn: true, firstName: 'first', lastName: 'last'});
            Y.Assert.areSame(RightNow.Profile.fullName(), 'first last');

            // change intl_nameorder config
            RightNow.Interface.setConfigbase(function(){return {"intl_nameorder":true}});
            Y.Assert.areSame(RightNow.Profile.fullName(), 'last first');
            RightNow.Interface.setConfigbase(function(){return {"intl_nameorder":false}});
        },

        testEmailAddress: function() {
            this.setProfileData({isLoggedIn: false});
            Y.Assert.areSame(RightNow.Profile.emailAddress(), '');
            this.setProfileData({isLoggedIn: true, email: 'asdf@email.null'});
            Y.Assert.areSame(RightNow.Profile.emailAddress(), 'asdf@email.null');
        },

        testContactID: function() {
            this.setProfileData({isLoggedIn: false});
            Y.Assert.areSame(RightNow.Profile.contactID(), null);
            this.setProfileData({isLoggedIn: true, contactID: 1234});
            Y.Assert.areSame(RightNow.Profile.contactID(), 1234);
        },

        testPreviouslySeenEmail: function() {
            this.setProfileData({previouslySeenEmail: null});
            Y.Assert.areSame(RightNow.Profile.previouslySeenEmail(), '');
            this.setProfileData({previouslySeenEmail: 'asdf@email.null'});
            Y.Assert.areSame(RightNow.Profile.previouslySeenEmail(), 'asdf@email.null');
        },

        setProfileData: function(data) {
            RightNow.Env = (function() {
                var _props = {
                    profileData: data
                };
                return function(prop) {
                    return _props[prop];
                };
            })();
        }
    }));

    return rightnowProfileTests;
});
UnitTest.run();

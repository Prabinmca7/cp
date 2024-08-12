/**
 * Synopsis:
 * - Logged-in social user who is the author of the question / comment.
 */

UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'SocialContentFlagging_0'
}, function (Y, widget, baseSelector) {

    Y.one(document.head).appendChild('<style type="text/css">.yui3-panel-hidden{display:none;}</style>');

    var suite = new Y.Test.Suite({
        name: "standard/feedback/SocialContentFlagging"
    });

    suite.add(new Y.Test.Case({
        name: "Non logged in user or no social user",

        setUp: function () {
            this.button = Y.one(baseSelector + '_Button');
        },

        tearDown: function () {
            this.button.simulate('click');
        },

        checkBannerDisplay: function(errorMessage, shouldDisplay) {
            var errors = {"errors":[{"errorCode": errorMessage}]};

            widget.onFlagSubmitted(errors, this.origObj);
            shouldDisplay ? Y.Assert.isNotNull(Y.one('.rn_BannerAlert')) : Y.Assert.isNull(Y.one('.rn_BannerAlert'));
        },

        "Login event is fired with no logged in user": function () {
            if(!RightNow.Profile.isLoggedIn()){
                var baseSubscriber = function(event, args){
                    Y.Assert.areSame(event, 'evt_requireLogin');
                    Y.Assert.isInstanceOf(RightNow.Event.EventObject, args[0]);
                };

                RightNow.Event.subscribe('evt_requireLogin', baseSubscriber);
                this.button.simulate('click');
                RightNow.Event.unsubscribe('evt_requireLogin', baseSubscriber);
            }
            else if(!RightNow.Profile.isSocialUser()){
                var baseSubscriber = function(evt, obj){
                    Y.Assert.areSame(evt, 'evt_userInfoRequired');
                    //Normally we would check for a populated event object,
                    //but the widget is currently not passing an object when it
                    //fires this event.
                    Y.Assert.isTrue(Y.Object.isEmpty(obj));
                };

                RightNow.Event.subscribe('evt_userInfoRequired', baseSubscriber);
                this.button.simulate('click');
                RightNow.Event.unsubscribe('evt_userInfoRequired', baseSubscriber);
            }
        },

        "Ajax request made with no logged in user" : function() {
            var test = this;
            var loginEventFired = false;
            RightNow.Event.subscribe('evt_requireLogin', function(eventName, eventData){
                loginEventFired = true;
            }, this);

            function response(resp) {
                test.resume(function() {
                   RightNow.Ajax.checkForSocialExceptions(resp);
                   Y.Assert.isTrue(loginEventFired);
                });
            }
            var postData = {w_id: 0, rn_contextData: widget.data.contextData, rn_contextToken: widget.data.contextToken, rn_timestamp: widget.data.timestamp};
            RightNow.Ajax.makeRequest('/ci/ajax/widget/standard/feedback/SocialContentFlagging/submitFlagHandler', postData, {
                successHandler: response,
                json: true,
                type: 'POST'
            });
            this.wait();
        },

        "Check banner display with various errors": function () {
            this.checkBannerDisplay("ERROR_USER_NOT_LOGGED_IN", false);
            this.checkBannerDisplay("ERROR_USER_HAS_NO_SOCIAL_USER", false);
            this.checkBannerDisplay("ERROR_USER_HAS_BLANK_SOCIAL_USER", false);
            this.checkBannerDisplay("UNRELATED_ERROR", true);
        }
    }));

    return suite;
}).run();

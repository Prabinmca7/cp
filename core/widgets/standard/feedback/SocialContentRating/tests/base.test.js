/**
 * Synopsis:
 * - Logged-in social user who is the author of the question / comment.
 */

UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'SocialContentRating_0'
}, function (Y, widget, baseSelector) {

    Y.one(document.head).appendChild('<style type="text/css">.yui3-panel-hidden{display:none;}</style>');

    var suite = new Y.Test.Suite({
        name: "standard/feedback/SocialContentRating"
    });

    suite.add(new Y.Test.Case({
        name: "Non logged in user or no social user",

        setUp: function () {
            this.button = Y.one(".rn_UpvoteButton");
        },

        tearDown: function () {
            this.button.simulate('click');
        },

        checkBannerDisplay: function(errorMessage, shouldDisplay) {
            var errors = {"errors":[{"errorCode": errorMessage}]};

            widget._onResponseReceived(errors);
            var banner = Y.one('.rn_BannerAlert');
            if(shouldDisplay) {
                Y.Assert.isNotNull(banner);
                banner.remove();
            }
            else {
                Y.Assert.isNull(banner);
            }
        },

        "Login event is fired with no logged in user": function () {
            var baseSubscriber;

            if(!RightNow.Profile.isLoggedIn()){
                baseSubscriber = function(event, args){
                    Y.Assert.areSame(event, 'evt_requireLogin');
                    Y.Assert.isInstanceOf(RightNow.Event.EventObject, args[0]);
                };

                RightNow.Event.subscribe('evt_requireLogin', baseSubscriber);
                this.button.simulate('click');
                RightNow.Event.unsubscribe('evt_requireLogin', baseSubscriber);
            }
            else if(!RightNow.Profile.isSocialUser()){
                baseSubscriber = function(event, obj){
                    Y.Assert.areSame(event, 'evt_userInfoRequired');
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

        "Check banner display with various errors": function () {
            this.checkBannerDisplay("ERROR_USER_NOT_LOGGED_IN", false);
            this.checkBannerDisplay("ERROR_USER_HAS_NO_SOCIAL_USER", false);
            this.checkBannerDisplay("ERROR_USER_HAS_BLANK_SOCIAL_USER", false);
            this.checkBannerDisplay("UNRELATED_ERROR", true);
            this.checkBannerDisplay(null, true);
        },

        "A response indicating an error displays custom error message": function() {
            var alertCount = Y.all('.rn_Alert').size();
            widget._onResponseReceived({result: false, errors: [{externalMessage: "User does not have permission to rate a comment"}]});

            Y.assert(alertCount + 1 === Y.all('.rn_Alert').size());
            Y.assert(Y.all('.rn_Alert').slice(-1).get('text').toString().indexOf('User does not have permission to rate a comment') > -1);
        }
    }));

    return suite;
}).run();

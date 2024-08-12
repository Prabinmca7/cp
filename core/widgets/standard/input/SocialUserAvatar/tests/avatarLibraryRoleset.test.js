UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'SocialUserAvatar_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/input/SocialUserAvatar - Gravatar & Social Options Behavior"
    });

    suite.add(new Y.Test.Case({
        name: "Functional",

        setUp: function() {
            this.expectedValuesOnButton = {
                folder: 'everyone',
                w_id: widget.data.info.w_id,
                focusClickToChooseText: false,
            };
            this.expectedValuesOnTab = {
                folder: 'everyone',
                w_id: widget.data.info.w_id,
                focusClickToChooseText: true,
            };
        },
        
        "Tabs are present to provide roleSet support for avatar library": function() {
            var responseInfo = {"numberOfPages":1,"files":["everyone\/mountains.jpg","everyone\/kingfisher.jpg","everyone\/flower3.jpg"]},
                responseObject = new RightNow.Event.EventObject(widget, {data: this.expectedValuesOnButton});
            UnitTest.overrideMakeRequest(widget.data.attrs.submit_avatar_library_action_ajax, this.expectedValuesOnButton, '_onSubmitSuccess', widget, responseInfo, responseObject);
            widget._onButtonClick();
            widget.data.js.rolesetsFolderMap = {"everyone":"All Users","moderator":"Moderators"};
            Y.Assert.isFalse(Y.one(baseSelector + ' .rn_AvatarLibraryForm').hasClass('rn_Hidden'));
            Y.Assert.isNull(Y.one(baseSelector + ' .rn_NoImages'));
            Y.Assert.isNotNull(Y.one(baseSelector + ' .rn_RoleSetTabs'));
            Y.Assert.areSame(Y.one(document.activeElement),Y.one(baseSelector + ' .rn_SelectedTab'));
        },
        
        "Focus is on the click to choose text when tab is clicked": function() {
            var responseInfo = {"numberOfPages":1,"files":["everyone\/mountains.jpg","everyone\/kingfisher.jpg","everyone\/flower3.jpg"]},
                responseObject = new RightNow.Event.EventObject(widget, {data: this.expectedValuesOnTab});
            widget._onSubmitSuccess(responseInfo, responseObject);
            widget.data.js.rolesetsFolderMap = {"everyone":"All Users","moderator":"Moderators"};
            Y.Assert.isFalse(Y.one(baseSelector + ' .rn_AvatarLibraryForm').hasClass('rn_Hidden'));
            Y.Assert.isNull(Y.one(baseSelector + ' .rn_NoImages'));
            Y.Assert.isNotNull(Y.one(baseSelector + ' .rn_RoleSetTabs'));
            var clickToChooseText = Y.one(baseSelector + " .rn_ClickToChooseText");
            Y.Assert.areSame(Y.one(document.activeElement),clickToChooseText);
        }
    }));

    return suite;
}).run();

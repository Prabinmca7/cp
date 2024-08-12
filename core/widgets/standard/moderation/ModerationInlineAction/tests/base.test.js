UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'ModerationInlineAction_0',
    subInstanceIDs: ['ModerationInlineAction_0']
}, function (Y, widget, baseSelector) {
    Y.one(document.head).appendChild('<style type="text/css">.yui3-panel-hidden{display:none;}</style>');
    var suite = new Y.Test.Suite({
        name: "standard/moderation/ModerationInlineAction"
    });

    suite.add(new Y.Test.Case({
        name: "Inline Content Moderation",
        setUp: function () {
            this.dropdown = Y.one(baseSelector + ' .rn_Dropdown');
            this.button = Y.one(baseSelector + '_Button');
            RightNow.Ajax.makeRequest = Y.bind(this.makeRequestMock, this);
        },

        tearDown: function () {
            this.button.simulate('click');
            RightNow.Ajax.makeRequest.calledWith = null;
        },

        removeBanner: function () {
            // Remove any active banners
            var banner = Y.one('.rn_BannerAlert');
            if(banner) {
                banner.remove();
            }
        },

        makeRequestMock: function () {
            this.makeRequestMock.calledWith = Array.prototype.slice.call(arguments);
        },

        panelIsHidden: function () {
            return this.dropdown.ancestor('.yui3-panel-hidden');
        },

        "Dropdown displays when action menu button is clicked": function () {
            this.button.simulate('click');
            this.dropdown = Y.one(baseSelector + ' .rn_Dropdown');
            Y.assert(this.dropdown);
            Y.assert(!this.panelIsHidden());
            Y.Assert.isNotNull(this.dropdown.all('a'));
        },

        "Moderator action is submitted to the server": function () {
            this.button.simulate('click');
            this.dropdown = Y.one(baseSelector + ' .rn_Dropdown');
            var action = this.dropdown.one('a');
            action.simulate('click');
            var request = this.makeRequestMock.calledWith;
            Y.Assert.areSame(widget.data.attrs.submit_moderator_action_ajax, request[0]);
            Y.Assert.areSame('CommunityQuestion', request[1].objectType);
            Y.Assert.areSame('30', request[1].actionID);
            progressIcon = Y.one(this.baseSelector + ' .rn_Hidden');
            Y.assert(!progressIcon);
        },

        "Menu items should change based on the action performed": function () {
            //question is restored and we get success ajax response, so the menu should show 'Suspend Question'
            var response = {"updatedObject":{"objectType":"CommunityQuestion","ID":13,"statusID":29,"statusWithTypeID":22,"isContentLocked":true},"updatedUserActions":{"Suspend Author":39},"updatedContentActions":{"Suspend Question":30,"Unlock Question":"unlock"},"error":null};
            widget._onModeratorActionSubmitSuccess(response);
            this.button.simulate('click');
            this.dropdown = Y.one(baseSelector + ' .rn_Dropdown');
            Y.assert(this.dropdown);
            var actionList = this.dropdown.all('a');
            var action = actionList.pop();
            var actionText = action.one('span');
            Y.Assert.areSame('39', action.getAttribute('data-action-id'));
            Y.Assert.areSame('Suspend Author', actionText.getHTML());
            action = actionList.pop();
            actionText = action.one('span');
            Y.Assert.areSame('unlock', action.getAttribute('data-action-id'));
            Y.Assert.areSame('Unlock Question', actionText.getHTML());
            action = actionList.pop();
            actionText = action.one('span');
            Y.Assert.areSame('30', action.getAttribute('data-action-id'));
            Y.Assert.areSame('Suspend Question', actionText.getHTML());
        },

        "Verify banner on Response": function() {
            this.removeBanner();
            var navigate = RightNow.Url.navigate,
                response = {"updatedObject":{"objectType":"CommunityQuestion","ID":13, "successMessage":"SPOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOON!", "statusID":29,"statusWithTypeID":22,"isContentLocked":true},"updatedUserActions":{"Suspend Author":39},"updatedContentActions":{"Suspend Question":30,"Unlock Question":"unlock"},"error":null};
            RightNow.Url.navigate = function(){};

            // Banner should not appear
            widget.data.attrs.refresh_page_on_moderator_action = true;
            widget._onModeratorActionSubmitSuccess(response);
            Y.Assert.isNull(Y.one('.rn_BannerAlert'));

            // Banner should appear
            widget.data.attrs.refresh_page_on_moderator_action = false;
            widget._onModeratorActionSubmitSuccess(response);
            Y.Assert.isNotNull(Y.one('.rn_BannerAlert'));

            RightNow.Url.navigate = navigate;
        }
    }));
    return suite;
}).run();
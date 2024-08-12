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
            Y.Assert.areSame('CommunityUser', request[1].objectType);
            Y.Assert.areSame('39', request[1].actionID);
            progressIcon = Y.one(this.baseSelector + ' .rn_Hidden');
            Y.assert(!progressIcon);
        },

        "Menu items should change based on the action performed": function () {
            //user is restored and we get success ajax response, so the menu should show 'Suspend User'
            var response = {"updatedObject":{"objectType":"CommunityUser","ID":11301,"statusID":38,"statusWithTypeID":31},"updatedUserActions":{"Suspend User":39,"Archive User":41,"Delete User":40},"updatedContentActions":[],"error":null};
            widget._onModeratorActionSubmitSuccess(response);
            this.button.simulate('click');
            this.dropdown = Y.one(baseSelector + ' .rn_Dropdown');
            Y.assert(this.dropdown);
            var actionList = this.dropdown.all('a');
            var action = actionList.pop();
            var actionText = action.one('span');
            Y.Assert.areSame('40', action.getAttribute('data-action-id'));
            Y.Assert.areSame('Delete User', actionText.getHTML());
            action = actionList.pop();
            actionText = action.one('span');
            Y.Assert.areSame('41', action.getAttribute('data-action-id'));
            Y.Assert.areSame('Archive User', actionText.getHTML());
            action = actionList.pop();
            actionText = action.one('span');
            Y.Assert.areSame('39', action.getAttribute('data-action-id'));
            Y.Assert.areSame('Suspend User', actionText.getHTML());
        }
    }));
    return suite;
}).run();
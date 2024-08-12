/**
 * Synopsis:
 * - Logged-in social user who is the author of the question / comment.
 * - More than one flag type is enabled so that the dropdown functionality is present.
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
        name: "UI Behavior",

        setUp: function () {
            this.dropdown = Y.one(baseSelector + ' .rn_Dropdown');
            this.button = Y.one(baseSelector + '_Button');
        },

        tearDown: function () {
            this.button.simulate('click');
            RightNow.Ajax.makeRequest.calledWith = null;
        },

        panelIsHidden: function () {
            return !!this.dropdown.ancestor('.yui3-panel-hidden');
        },

        "Dropdown displays when button is clicked and its first item is focused": function () {
            Y.assert(!Y.one(baseSelector + ' .rn_Dropdown'));
            this.button.simulate('click');
            this.dropdown = Y.one(baseSelector + ' .rn_Dropdown');
            Y.assert(this.dropdown);
            Y.assert(!this.panelIsHidden());
            Y.Assert.areSame(document.activeElement, Y.Node.getDOMNode(this.dropdown.one('a')));
        },

        "Flag id is submitted to the server": function () {
            RightNow.Ajax.makeRequest = function () {
                RightNow.Ajax.makeRequest.calledWith = Array.prototype.slice.call(arguments);
            };

            this.button.simulate('click');
            var firstFlag = this.dropdown.one('a');

            firstFlag.simulate('click');
            // Panel is not hidden while waiting for server response.
            Y.assert(!this.panelIsHidden());

            Y.Assert.areSame(widget.data.attrs.submit_flag_ajax, RightNow.Ajax.makeRequest.calledWith[0]);
            Y.Assert.areSame(firstFlag.getAttribute('data-id'), RightNow.Ajax.makeRequest.calledWith[1].flagID);
        },

        "Flag is rendered on server response": function () {
            this.button.simulate('click');
            var firstFlag = this.dropdown.one('a');
            firstFlag.simulate('click');
            widget.onFlagSubmitted({ type: firstFlag.getAttribute('data-id') }, {});

            Y.assert(firstFlag.hasClass('rn_Selected'));
            Y.assert(!this.button.one('.rn_Flagged').hasClass('rn_Hidden'));
            Y.assert(this.button.one('.rn_Unflagged').hasClass('rn_Hidden'));
            Y.assert(this.panelIsHidden(), 'Panel is not hidden');
            Y.assert(!this.button.hasClass('rn_Loading'));
            Y.Assert.areSame('false', this.button.getAttribute('aria-busy'));
            Y.Assert.areSame(document.activeElement, Y.Node.getDOMNode(this.button));
        },

        "Flag id is not submitted to the server if it's already selected": function () {
            var firstFlag = this.dropdown.one('a');
            firstFlag.simulate('click');
            Y.Assert.isNull(RightNow.Ajax.makeRequest.calledWith);
        }
    }));

    return suite;
}).run();

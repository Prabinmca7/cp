/**
 * Synopsis:
 * - Logged-in social user who is the author of the question / comment.
 */

UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'SocialContentFlagging_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/feedback/SocialContentFlagging"
    });

    suite.add(new Y.Test.Case({
        name: "UI behavior when there's a single flag",

        setUp: function () {
            this.button = Y.one(baseSelector + '_Button');
        },

        "Flag id is submitted to the server when flag button is clicked": function () {
            RightNow.Ajax.makeRequest = function () {
                RightNow.Ajax.makeRequest.calledWith = Array.prototype.slice.call(arguments);
            };

            this.button.simulate('click');

            Y.assert(!Y.one(baseSelector + ' .rn_Dropdown'));
            Y.assert(this.button.hasClass('rn_Loading'));

            Y.Assert.areSame(widget.data.attrs.submit_flag_ajax, RightNow.Ajax.makeRequest.calledWith[0]);
            Y.Assert.areSame(widget.data.js.flags[0].ID, RightNow.Ajax.makeRequest.calledWith[1].flagID);
        },

        "DOM is updated correctly on response": function () {
            widget.onFlagSubmitted({type: 1, _isParsed: true}, this.origObj);
            Y.Assert.isFalse(Y.one(baseSelector + ' .rn_Flagged').hasClass('rn_Hidden'));
            Y.Assert.areSame(this.button.getAttribute('title'), widget.data.attrs.label_already_flagged_tooltip);
        },

        "Afterwards, event is no longer fired if flag button is clicked again": function () {
            RightNow.Event.subscribe("evt_ContentFlagSubmit", function(evt, obj){
                Y.Assert.fail();
            }, this);

            this.button.simulate('click');
            RightNow.Event.unsubscribe("evt_ContentFlagSubmit");
        }
    }));

    return suite;
}).run();

/*
 * By default, it's difficult to get this widget to actually render (requires attributes pointing to legit community post types).
 * That doesn't mean the JS can't be tested. Manually load in the JS file, provide the minimum necessary DOM fixture, instantiate
 * the widget, and begin testing.
 */
UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    jsFiles: ['/cgi-bin/{cfg}/php/cp/core/widgets/standard/social/CommunityPostSubmit/logic.js']
    /* omit `instanceID` since we don't want the UnitTest runner to instantiate the widget */
}, function(Y, widget, baseSelector){
    var suite = new Y.Test.Suite({
        name: "standard/social/CommunityPostSubmit",
        setUp: function() {
            // If I were testing something that dealt with the DOM, then this is where I'd jam in
            // a DOM fixture so that the widget could appropriately find the elements it needs.

            widget = new RightNow.Widgets.CommunityPostSubmit({js: {}, attrs: {}}, 'instance_id', Y);
        }
    });

    suite.add(new Y.Test.Case({
        name: "Server response is dealt with appropriately",

        tearDown: function() {
            Y.one('.rn_Dialog').remove();
        },

        "When the response's message is a string then it's displayed in a popup": function() {
            widget._onFormSubmitResponse({ message: 'bananas', created: true });

            Y.Assert.areSame(1, Y.all('.rn_Dialog').size());
            Y.assert(Y.one('.rn_Dialog .yui3-widget-bd').getHTML().indexOf('bananas') > -1);
        },

        "When the response doesn't have a message then label_confirm_dialog is displayed in a popup": function() {
            widget.data.attrs.label_confirm_dialog = 'fades';
            widget._onFormSubmitResponse({ created: true });

            Y.Assert.areSame(1, Y.all('.rn_Dialog').size());
            Y.assert(Y.one('.rn_Dialog .yui3-widget-bd').getHTML().indexOf('fades') > -1);
        },

        "Error response's message is displayed in a popup": function() {
            widget._onFormSubmitResponse({ error: true, errorCode: 92, message: 'boloko' });

            Y.Assert.areSame(1, Y.all('.rn_Dialog').size());
            Y.assert(Y.one('.rn_Dialog .yui3-widget-bd').one('*').hasClass('rn_WarningContent'));
            Y.assert(Y.one('.rn_Dialog .yui3-widget-bd').getHTML().indexOf('boloko') > -1);
        },

        "When the response has a message property that's an object then label_confirm_dialog is displayed in a popup": function() {
            widget.data.attrs.label_confirm_dialog = 'premium';
            widget._onFormSubmitResponse({ created: true, message: { hey: 'the server incorrectly sent an object, but whatevs...' } });

            Y.Assert.areSame(1, Y.all('.rn_Dialog').size());
            Y.assert(Y.one('.rn_Dialog .yui3-widget-bd').getHTML().indexOf('premium') > -1);
        }
    }));

    return suite;
}).run();

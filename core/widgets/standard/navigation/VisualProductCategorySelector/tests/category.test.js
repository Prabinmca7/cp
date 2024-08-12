RightNow.Ajax.makeRequest = function () {
    RightNow.Ajax.makeRequest.called = 0;
    RightNow.Ajax.makeRequest.calledWith = arguments;
};

UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'VisualProductCategorySelector_0'
}, function(Y, widget, baseSelector){
    var suite = new Y.Test.Suite({
        name: "standard/navigation/VisualProductCategorySelector"
    });

    suite.add(new Y.Test.Case({
        name: "Behavior and Event Handling",

        resetMakeRequest: function() {
            RightNow.Ajax.makeRequest.called = 0;
            RightNow.Ajax.makeRequest.calledWith = null;
        },

        setUp: function() {
            this.link = Y.one('.rn_ShowChildren');
            this.resetMakeRequest();
        },

        tearDown: function() {
            Y.all('.rn_Loading').addClass('rn_Hidden');
            this.resetMakeRequest();
        },

        "Global event is fired prior to doing AJAX request and filter is set to category": function() {
            var eventArgs,
                eventHandler = function(evt, args) {
                    eventArgs = args;
                    return false;
                };

            RightNow.Event.on('evt_ItemRequest', eventHandler);

            this.link.simulate('click');

            Y.assert(!RightNow.Ajax.makeRequest.called);

            Y.Assert.isArray(eventArgs);
            Y.Assert.areSame(parseInt(this.link.getAttribute('data-id'), 10), eventArgs[0].data.id);
            Y.Assert.areSame('category', eventArgs[0].data.filter);
            Y.Assert.isFalse(eventArgs[0].data.linking);

            RightNow.Event.unsubscribe('evt_ItemRequest', eventHandler);
        },

        "AJAX request goes through properly and loading indicator is set and filter is to category while waiting for response": function() {
            this.link.simulate('click');

            Y.Assert.areSame(widget.data.attrs.sub_item_ajax, RightNow.Ajax.makeRequest.calledWith[0]);
            Y.Assert.isNumber(RightNow.Ajax.makeRequest.calledWith[1].id);
            Y.Assert.isFalse(RightNow.Ajax.makeRequest.calledWith[1].linking);
            Y.Assert.areSame('category', RightNow.Ajax.makeRequest.calledWith[1].filter);

            var loadDiv = Y.one(baseSelector + ' .rn_Items').one('*');
            Y.assert(loadDiv.hasClass('rn_Loading'));
            Y.assert(!loadDiv.hasClass('rn_Hidden'));
        }
    }));

    return suite;
}).run();
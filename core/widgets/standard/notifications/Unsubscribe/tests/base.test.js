UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'Unsubscribe_0',
}, function(Y, widget, baseSelector){
    var unsubscribeTests = new Y.Test.Suite({
        name: 'standard/notifications/Unsubscribe',

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.contactID = 1172;
                }
            };

            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    unsubscribeTests.add(new Y.Test.Case({
        name: 'Event Handling for Answer (Un|Re)subscribe Operations',

        testUnsubscribeAnswer: function() {
            this.initValues();

            var fieldset = Y.one('#rn_Unsubscribe_0_0'),
                button = fieldset.one('button');
            if (button) {
                var that = this,
                    tmpMakeRequest = RightNow.Ajax.makeRequest,
                    testMakeRequest = function(url, data, options) {
                        try {
                            //Test the input data
                            var mockData = {cid: that.contactID, filter_type: 'answer', id: 48};
                            Y.Assert.areSame('/ci/ajaxRequest/deleteNotification', url);
                            Y.Assert.areSame(data.cid, mockData.cid);
                            Y.Assert.areSame(data.filter_type, mockData.filter_type);
                            Y.Assert.areSame(data.id, mockData.id);
                            Y.Assert.areSame(options.successHandler, widget._onResponse);
                            Y.Assert.areSame(options.scope, widget);
                            Y.Assert.isTrue(options.json);
                            Y.Assert.isFunction(options.successHandler);
                            originalData = options.data.data;
                            mockOriginalData = {index: 0, action: 'unsubscribe', responseEvent: 'evt_answerNotificationDeleteResponse'};
                            Y.Assert.areSame(originalData.index, mockOriginalData.index);
                            Y.Assert.areSame(originalData.action, mockOriginalData.action);
                            Y.Assert.areSame(originalData.responseEvent, mockOriginalData.responseEvent);
                        }
                        catch(err) {
                            that.delayedErrorMessage = err;
                        }

                        //Trigger the event handler
                        options.successHandler.apply(widget, [{errors: []}, options.data]);
                    },
                    testResponse = function(type, args) {
                        try {
                            Y.Assert.areSame('evt_answerNotificationDeleteResponse', type);
                            Y.Assert.areSame(0, args[0].response.errors.length);
                        }
                        catch(err) {
                            that.delayedErrorMessage = err;
                        }
                    };

                RightNow.Ajax.makeRequest = testMakeRequest;
                RightNow.Event.subscribe('evt_answerNotificationDeleteResponse', testResponse, this);
                Y.one(button).simulate('click');
                RightNow.Ajax.makeRequest = tmpMakeRequest;

                var messageDiv = fieldset.one('span');
                Y.Assert.areSame(messageDiv.get('innerHTML'), widget.data.attrs.label_unsub_success);
                Y.Assert.areSame(button.get('innerHTML'), widget.data.attrs.label_resub_button);

                if(this.delayedErrorMessage) {
                    Y.Assert.fail(this.delayedErrorMessage.toString());
                }
            }
        },

        testResubscribeAnswer: function() {
            this.initValues();

            var fieldset = Y.one('#rn_Unsubscribe_0_0'),
                button = fieldset.one('button');
            if (button) {
                var that = this,
                    tmpMakeRequest = RightNow.Ajax.makeRequest,
                    testMakeRequest = function(url, data, options) {
                        try {
                            //Test the input data
                            var mockData = {cid: that.contactID, filter_type: 'answer', id: 48};
                            Y.Assert.areSame('/ci/ajaxRequest/addOrRenewNotification', url);
                            Y.Assert.areSame(data.cid, mockData.cid);
                            Y.Assert.areSame(data.filter_type, mockData.filter_type);
                            Y.Assert.areSame(data.id, mockData.id);
                            Y.Assert.areSame(options.successHandler, widget._onResponse);
                            Y.Assert.areSame(options.scope, widget);
                            Y.Assert.isTrue(options.json);
                            Y.Assert.isFunction(options.successHandler);
                            originalData = options.data.data;
                            mockOriginalData = {index: 0, action: 'subscribe', responseEvent: 'evt_answerNotificationResponse'};
                            Y.Assert.areSame(originalData.index, mockOriginalData.index);
                            Y.Assert.areSame(originalData.action, mockOriginalData.action);
                            Y.Assert.areSame(originalData.responseEvent, mockOriginalData.responseEvent);
                        }
                        catch(err) {
                            that.delayedErrorMessage = err;
                        }

                        //Trigger the event handler
                        options.successHandler.apply(widget, [{errors: []}, options.data]);
                    },
                    testResponse = function(type, args) {
                        try {
                            Y.Assert.areSame('evt_answerNotificationDeleteResponse', type);
                            Y.Assert.areSame(0, args[0].response.errors.length);
                        }
                        catch(err) {
                            that.delayedErrorMessage = err;
                        }
                    };

                RightNow.Ajax.makeRequest = testMakeRequest;
                RightNow.Event.subscribe('evt_answerNotificationDeleteResponse', testResponse, this);
                Y.one(button).simulate('click');
                RightNow.Ajax.makeRequest = tmpMakeRequest;

                var messageDiv = fieldset.one('span');
                Y.Assert.areSame(messageDiv.get('innerHTML'), widget.data.attrs.label_sub_success);
                Y.Assert.areSame(button.get('innerHTML'), widget.data.attrs.label_unsub_button);

                if(this.delayedErrorMessage) {
                    Y.Assert.fail(this.delayedErrorMessage.toString());
                }
            }
        },

        testUnsuccessfulResponse: function() {
            this.initValues();
            var messageVal = 'spongebob',
                messageDiv = Y.one('#rn_Unsubscribe_0_0 span'),
                button = Y.one('#rn_Unsubscribe_0_0 button');

            widget._onResponse({error: messageVal}, {data:{action:'subscribe', index: 0}});
            Y.Assert.areSame(messageDiv.get('innerHTML'), messageVal);
            Y.Assert.areSame(button.get('innerHTML'), widget.data.attrs.label_resub_button);

            widget._onResponse({error: messageVal}, {data:{action:'unsubscribe', index: 0}});
            Y.Assert.areSame(messageDiv.get('innerHTML'), messageVal);
            Y.Assert.areSame(button.get('innerHTML'), widget.data.attrs.label_unsub_button);
        }
    }));

    unsubscribeTests.add(new Y.Test.Case({
        name: 'Event Handling for Product/Category (Un|Re)subscribe Operations',

        testUnsubscribeProduct: function() {
            this.initValues();

            var fieldset = Y.one('#rn_Unsubscribe_0_1'),
                button = fieldset.one('button');
            if (button) {
                var that = this,
                    tmpMakeRequest = RightNow.Ajax.makeRequest,
                    testMakeRequest = function(url, data, options) {
                        try {
                            //Test the input data
                            var mockData = {cid: that.contactID, filter_type: 'product', id: 130};
                            Y.Assert.areSame('/ci/ajaxRequest/deleteNotification', url);
                            Y.Assert.areSame(data.cid, mockData.cid);
                            Y.Assert.areSame(data.filter_type, mockData.filter_type);
                            Y.Assert.areSame(data.id, mockData.id);
                            Y.Assert.areSame(options.successHandler, widget._onResponse);
                            Y.Assert.areSame(options.scope, widget);
                            Y.Assert.isTrue(options.json);
                            Y.Assert.isFunction(options.successHandler);
                            originalData = options.data.data;
                            mockOriginalData = {index: 1, action: 'unsubscribe', responseEvent: 'evt_prodCatDeleteResponse'};
                            Y.Assert.areSame(originalData.index, mockOriginalData.index);
                            Y.Assert.areSame(originalData.action, mockOriginalData.action);
                            Y.Assert.areSame(originalData.responseEvent, mockOriginalData.responseEvent);
                        }
                        catch(err) {
                            that.delayedErrorMessage = err;
                        }

                        //Trigger the event handler
                        options.successHandler.apply(widget, [{errors: []}, options.data]);
                    },
                    testResponse = function(type, args) {
                        try {
                            Y.Assert.areSame('evt_prodCatDeleteResponse', type);
                            Y.Assert.areSame(0, args[0].response.errors.length);
                        }
                        catch(err) {
                            that.delayedErrorMessage = err;
                        }
                    };

                RightNow.Ajax.makeRequest = testMakeRequest;
                RightNow.Event.subscribe('evt_prodCatDeleteResponse', testResponse, this);
                Y.one(button).simulate('click');
                RightNow.Ajax.makeRequest = tmpMakeRequest;

                var messageDiv = fieldset.one('span');
                Y.Assert.areSame(messageDiv.get('innerHTML'), widget.data.attrs.label_unsub_success);
                Y.Assert.areSame(button.get('innerHTML'), widget.data.attrs.label_resub_button);

                if(this.delayedErrorMessage) {
                    Y.Assert.fail(this.delayedErrorMessage.toString());
                }
            }
        },

        testResubscribeProduct: function() {
            this.initValues();

            var fieldset = Y.one('#rn_Unsubscribe_0_1'),
                button = fieldset.one('button');
            if (button) {
                var that = this,
                    tmpMakeRequest = RightNow.Ajax.makeRequest,
                    testMakeRequest = function(url, data, options) {
                        try {
                            //Test the input data
                            var mockData = {cid: that.contactID, filter_type: 'product', id: 130};
                            Y.Assert.areSame('/ci/ajaxRequest/addOrRenewNotification', url);
                            Y.Assert.areSame(data.cid, mockData.cid);
                            Y.Assert.areSame(data.filter_type, mockData.filter_type);
                            Y.Assert.areSame(data.id, mockData.id);
                            Y.Assert.areSame(options.successHandler, widget._onResponse);
                            Y.Assert.areSame(options.scope, widget);
                            Y.Assert.isTrue(options.json);
                            Y.Assert.isFunction(options.successHandler);
                            originalData = options.data.data;
                            mockOriginalData = {index: 1, action: 'subscribe', responseEvent: 'evt_prodCatAddResponse'};
                            Y.Assert.areSame(originalData.index, mockOriginalData.index);
                            Y.Assert.areSame(originalData.action, mockOriginalData.action);
                            Y.Assert.areSame(originalData.responseEvent, mockOriginalData.responseEvent);
                        }
                        catch(err) {
                            that.delayedErrorMessage = err;
                        }

                        //Trigger the event handler
                        options.successHandler.apply(widget, [{errors: []}, options.data]);
                    },
                    testResponse = function(type, args) {
                        try {
                            Y.Assert.areSame('evt_prodCatAddResponse', type);
                            Y.Assert.areSame(0, args[0].response.errors.length);
                        }
                        catch(err) {
                            that.delayedErrorMessage = err;
                        }
                    };

                RightNow.Ajax.makeRequest = testMakeRequest;
                RightNow.Event.subscribe('evt_prodCatAddResponse', testResponse, this);
                Y.one(button).simulate('click');
                RightNow.Ajax.makeRequest = tmpMakeRequest;

                var messageDiv = fieldset.one('span');
                Y.Assert.areSame(messageDiv.get('innerHTML'), widget.data.attrs.label_sub_success);
                Y.Assert.areSame(button.get('innerHTML'), widget.data.attrs.label_unsub_button);

                if(this.delayedErrorMessage) {
                    Y.Assert.fail(this.delayedErrorMessage.toString());
                }
            }
        }
    }));
    return unsubscribeTests;
});
UnitTest.run();

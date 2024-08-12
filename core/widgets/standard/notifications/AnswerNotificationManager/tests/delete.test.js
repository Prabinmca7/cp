UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'AnswerNotificationManager_0',
}, function(Y, widget, baseSelector){
    var answerNotificationManagerTests = new Y.Test.Suite({
        name: "standard/notifications/AnswerNotificationManager"
    });

    answerNotificationManagerTests.add(new Y.Test.Case({
        name: "Event Handling and Operation Delete",

        testAnswerNotificationDeleteResponseCase: function() {

            var button = Y.one(baseSelector).one('.rn_Notification .rn_Notification_Delete'),
                that = this,
                tmpMakeRequest = RightNow.Ajax.makeRequest,
                testMakeRequest = function(url, data, options) {
                    try {
                        //Test the input data
                        var mockData = {filter_type: 'answer', id: 49};
                        Y.Assert.areSame("/ci/ajaxRequest/deleteNotification", url);
                        Y.Assert.areSame(data.id, mockData.id);
                        Y.Assert.areSame(data.filter_type, mockData.filter_type);
                        Y.Assert.areSame(options.successHandler, widget.onDeleteResponse);
                        Y.Assert.areSame(options.scope, widget);
                        Y.Assert.areSame(options.data.data, data);
                        Y.Assert.isTrue(options.json);
                        Y.Assert.isFunction(options.successHandler);
                    }
                    catch(err) {
                        that.delayedErrorMessage = err;
                    }

                    //Trigger the event handler
                    options.successHandler.apply(widget, [{}, {data: data}]);
                },
                testDeleteResponse = function(type, args) {
                    try {
                        Y.Assert.areSame("evt_answerNotificationDeleteResponse", type);
                    }
                    catch(err) {
                        that.delayedErrorMessage = err;
                    }
                };

                //Pretend to delete the notification and make sure the notification deleted message appears
                RightNow.Ajax.makeRequest = testMakeRequest;
                RightNow.Event.subscribe("evt_answerNotificationDeleteResponse", testDeleteResponse, this);
                Y.one(button).simulate('click');
                RightNow.Ajax.makeRequest = tmpMakeRequest;
                Y.Assert.isTrue(Y.one('.rn_Alert').get('text').indexOf(widget.data.attrs.label_notif_deleted) > -1);

                if(this.delayedErrorMessage)
                    Y.Assert.fail(this.delayedErrorMessage.name + ': ' + this.delayedErrorMessage.message);
    }
    }));

    return answerNotificationManagerTests;
});
UnitTest.run();

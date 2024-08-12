UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'AnswerNotificationManager_0',
}, function(Y, widget, baseSelector){
    var answerNotificationManagerTests = new Y.Test.Suite({
        name: "standard/notifications/AnswerNotificationManager"
    });

    answerNotificationManagerTests.add(new Y.Test.Case({
        name: "Event Handling and Operation Renew",

        testAnswerNotificationRenewResponseCase: function() {
            var button = Y.one(baseSelector).one('.rn_Notification .rn_Notification_Renew'),
                expiresContent = Y.one(baseSelector).one('.rn_Notification .rn_Notification_Info').get('text'),
                that = this,
                tmpMakeRequest = RightNow.Ajax.makeRequest,
                testMakeRequest = function(url, data, options) {
                    try {
                        //Test the input data
                        var mockData = {filter_type: 'answer', id: 49};
                        Y.Assert.areSame("/ci/ajaxRequest/addOrRenewNotification", url);
                        Y.Assert.areSame(data.id, mockData.id);
                        Y.Assert.areSame(data.filter_type, mockData.filter_type);
                        Y.Assert.areSame(options.successHandler, widget.onRenewResponse);
                        Y.Assert.areSame(options.scope, widget);
                        Y.Assert.areSame(options.data.data, data);
                        Y.Assert.isTrue(options.json);
                        Y.Assert.isFunction(options.successHandler);
                    }
                    catch(err) {
                        that.delayedErrorMessage = err;
                    }

                    //Trigger the event handler
                    options.successHandler.apply(widget, [{
                        notifications: [
                            {expiration: 'Expires 02/11/2012 (4 days)'}
                        ]
                    }, data]);
                },
                testRenewResponse = function(type, args) {
                    try {
                        Y.Assert.areSame("evt_answerNotificationRenewResponse", type);
                        Y.Assert.areSame("Expires 02/11/2012 (4 days)", args[0].response.notifications[0].expiration);
                    }
                    catch(err) {
                        that.delayedErrorMessage = err;
                    }
                };

            //Before clicking renew, the expires date should be whatever is in the database
            Y.Assert.isTrue(expiresContent.indexOf('Expires 02/25/2009 (') > 0);
            RightNow.Ajax.makeRequest = testMakeRequest;
            RightNow.Event.subscribe("evt_answerNotificationRenewResponse", testRenewResponse, this);
            Y.one(button).simulate('click');
            RightNow.Ajax.makeRequest = tmpMakeRequest;

            //Message div should display the notification message
            Y.Assert.isTrue(Y.one('.rn_Alert').get('text').indexOf(widget.data.attrs.label_notif_renewed) > -1);

            if(this.delayedErrorMessage)
                Y.Assert.fail(this.delayedErrorMessage.name + ': ' + this.delayedErrorMessage.message);
        }
    }));

    return answerNotificationManagerTests;
});
UnitTest.run();

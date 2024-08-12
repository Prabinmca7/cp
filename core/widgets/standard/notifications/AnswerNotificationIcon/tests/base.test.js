UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'AnswerNotificationIcon_0'
}, function(Y, widget, baseSelector){
    var answerNotificationIconTests = new Y.Test.Suite({
        name: "standard/notifications/AnswerNotificationIcon",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'AnswerNotificationIcon_0';
                    if (this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID)) {
                        this.widgetData = this.instance.data;
                        this.notificationsUrl = this.widgetData.attrs.notifications_url;
                        this.linkLabel = this.widgetData.attrs.label_notification_link;
                        this.button = document.getElementById('rn_' + this.instanceID + '_Trigger');
                        this.iconPath = this.widgetData.attrs.icon_path;
                        this.iconAlt = this.widgetData.attrs.label_icon_alt;
                        this.toolTip = this.widgetData.attrs.label_tooltip;
                        var bannerAlert = Y.one('.rn_BannerAlert');
                        if(bannerAlert) {
                            bannerAlert.remove();
                        }
                    }
                },

                checkEventParameters: function(eventName, type, args)
                {
                    Y.Assert.areSame(eventName, type);
                    Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
                    Y.Assert.isObject(args.filters);
                    Y.Assert.areSame(this.instanceID, args.w_id);
                }
            };

            for(var item in this.items)
            {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    answerNotificationIconTests.add(new Y.Test.Case(
    {
        name: "Event Handling for add and renew",

        testAdd: function() {
            this.initValues();
            if (this.button) {
                var that = this,
                    tmpMakeRequest = RightNow.Ajax.makeRequest,
                    testMakeRequest = function(url, data, options) {
                        try {
                            //Test the input data
                            var mockData = {filter_type: 'answer', id: '1'};
                            Y.Assert.areSame('/ci/ajaxRequest/addOrRenewNotification', url);
                            Y.Assert.areSame(data.id, mockData.id);
                            Y.Assert.areSame(data.filter_type, mockData.filter_type);
                            Y.Assert.areSame(options.successHandler, that.instance._onNotificationResponse);
                            Y.Assert.areSame(options.scope, that.instance);
                            Y.Assert.areSame(options.data.data, data);
                            Y.Assert.isTrue(options.json);
                            Y.Assert.isFunction(options.successHandler);
                        }
                        catch(err) {
                            that.delayedErrorMessage = err;
                        }

                        //Trigger the event handler
                        options.successHandler.apply(that.instance, [{
                            action: 'add',
                            error: null,
                            notifications: []
                        }, data]);
                    },
                    testResponse = function(type, args) {
                        try {
                            Y.Assert.areSame('evt_answerNotificationResponse', type);
                            Y.Assert.isNull(args[0].response.error);
                            Y.Assert.areSame('add', args[0].response.action);
                        }
                        catch(err) {
                            that.delayedErrorMessage = err;
                        }
                    };

                RightNow.Ajax.makeRequest = testMakeRequest;
                RightNow.Event.subscribe("evt_answerNotificationResponse", testResponse, this);
                Y.one(this.button).simulate('click');
                RightNow.Ajax.makeRequest = tmpMakeRequest;
                Y.Assert.isTrue(Y.one('.rn_BannerAlert').get('innerHTML').indexOf(widget.data.attrs.label_subscribed) > -1);
                RightNow.Event.unsubscribe("evt_answerNotificationResponse", testResponse, this);

                if(this.delayedErrorMessage) {
                    Y.Assert.fail(this.delayedErrorMessage.toString());
                }
            }
        },

        testRenew: function() {
            this.initValues();
            if (this.button) {
                var that = this,
                    tmpMakeRequest = RightNow.Ajax.makeRequest,
                    testMakeRequest = function(url, data, options) {
                        try {
                            //Test the input data
                            var mockData = {filter_type: 'answer', id: '1'};
                            Y.Assert.areSame('/ci/ajaxRequest/addOrRenewNotification', url);
                            Y.Assert.areSame(data.id, mockData.id);
                            Y.Assert.areSame(data.filter_type, mockData.filter_type);
                            Y.Assert.areSame(options.successHandler, that.instance._onNotificationResponse);
                            Y.Assert.areSame(options.scope, that.instance);
                            Y.Assert.areSame(options.data.data, data);
                            Y.Assert.isTrue(options.json);
                            Y.Assert.isFunction(options.successHandler);
                        }
                        catch(err) {
                            that.delayedErrorMessage = err;
                        }

                        //Trigger the event handler
                        options.successHandler.apply(that.instance, [{
                            action: 'renew',
                            error: null,
                            notifications: []
                        }, data]);
                    },
                    testResponse = function(type, args) {
                        try {
                            Y.Assert.areSame('evt_answerNotificationResponse', type);
                            Y.Assert.isNull(args[0].response.error);
                            Y.Assert.areSame('renew', args[0].response.action);
                        }
                        catch(err) {
                            that.delayedErrorMessage = err;
                        }
                    };

                RightNow.Ajax.makeRequest = testMakeRequest;
                RightNow.Event.subscribe("evt_answerNotificationResponse", testResponse, this);
                Y.one(this.button).simulate('click');
                RightNow.Ajax.makeRequest = tmpMakeRequest;
                Y.Assert.isTrue(Y.one('.rn_BannerAlert').get('innerHTML').indexOf(widget.data.attrs.label_renewed) > -1);

                RightNow.Event.unsubscribe("evt_answerNotificationResponse", testResponse, this);

                if(this.delayedErrorMessage) {
                    Y.Assert.fail(this.delayedErrorMessage.toString());
                }
            }
        }
    }));

    return answerNotificationIconTests;
});
UnitTest.run();
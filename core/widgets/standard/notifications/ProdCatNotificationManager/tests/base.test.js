UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'ProdCatNotificationManager_0',
    subInstanceIDs: ['ProductCategoryInput_1', 'ProductCategoryInput_2']
}, function(Y, widget, baseSelector){
    var prodCatNotificationManagerTests = new Y.Test.Suite({
        name: "standard/notifications/ProdCatNotificationManager",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'ProdCatNotificationManager_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;

                    this.deleteLabel = this.widgetData.attrs.label_delete_button;
                    this.noNotifsLabel = this.widgetData.attrs.label_no_notifs;
                    this.notifDeletedLabel = this.widgetData.attrs.label_notif_deleted;
                    this.notifRenewedLabel = this.widgetData.attrs.label_notif_renewed;
                    this.renewLabel = this.widgetData.attrs.label_renew_button;
                    this.productPageURL = this.widgetData.attrs.prod_page_url;
                    this.categoryPageURL = this.widgetData.attrs.cat_page_url;
                    this.messageElement = ((this.widgetData.attrs.message_element) ? Y.one('#' + this.widgetData.attrs.message_element) : null)
                        || Y.one(this.instance.baseSelector + '_Message');
                },

                validateMessage: function(attributeToCheck) {
                    var messageBox = Y.one('.rn_MessageBox');
                    if(messageBox) {
                        Y.Assert.areSame(attributeToCheck, messageBox.get('innerHTML'));
                    }
                    else {
                        this.wait(function() {
                            var banner = Y.one('.rn_Alert');
                            Y.Assert.areSame(Y.Node.getDOMNode(banner), document.activeElement);
                            Y.Assert.isTrue(banner.get('text').indexOf(attributeToCheck) > -1);
                        }, 1000);
                    }
                },
            };

            for(var item in this.items)
            {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    prodCatNotificationManagerTests.add(new Y.Test.Case(
    {
        name: "Event Handling and Operation Renew",

        testProdCatNotificationRenewResponseCase: function() {
            this.initValues();

            var button = document.getElementById('rn_' + this.instanceID + '_Renew_0');
            if (button) {
                var expiresContent = Y.one('#rn_' + this.instanceID + '_Expiration_0');
                Y.Assert.isString(expiresContent.get('innerHTML'));
                Y.Assert.isTrue(expiresContent.get('innerHTML').indexOf('Expires ') > 0);

                var that = this,
                    tmpMakeRequest = RightNow.Ajax.makeRequest,
                    testMakeRequest = function(url, data, options) {
                        try {
                            //Test the input data
                            var mockData = {filter_type: 14, id: 122};
                            Y.Assert.areSame("/ci/ajaxRequest/addOrRenewNotification", url);
                            Y.Assert.areSame(data.id, mockData.id);
                            Y.Assert.areSame(data.filter_type, mockData.filter_type);
                            Y.Assert.areSame(options.successHandler, that.instance._onRenewResponse);
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
                            notifications: [
                                {expiration: 'Expires 02/11/2012 (4 days)'}
                            ]
                        }, data]);
                    },
                    testRenewResponse = function(type, args) {
                        try {
                            Y.Assert.areSame("evt_prodCatNotificationRenewResponse", type);
                            Y.Assert.areSame("Expires 02/11/2012 (4 days)", args[0].response.notifications[0].expiration);
                        }
                        catch(err) {
                            that.delayedErrorMessage = err;
                        }
                    };

                RightNow.Ajax.makeRequest = testMakeRequest;
                RightNow.Event.subscribe("evt_prodCatNotificationRenewResponse", testRenewResponse, this);
                Y.one(button).simulate('click');
                RightNow.Ajax.makeRequest = tmpMakeRequest;

                this.validateMessage(this.widgetData.attrs.label_notif_renewed);

                if(this.delayedErrorMessage)
                    Y.Assert.fail(this.delayedErrorMessage.name + ': ' + this.delayedErrorMessage.message);
            }
        }
    }));

    prodCatNotificationManagerTests.add(new Y.Test.Case(
    {
        name: "Event Handling and Operation Delete",

        testProdCatNotificationDeleteResponseCase: function() {
            this.initValues();

            var button = document.getElementById('rn_' + this.instanceID + '_Delete_1');
            if (button) {
                var that = this,
                    tmpMakeRequest = RightNow.Ajax.makeRequest,
                    testMakeRequest = function(url, data, options) {
                        try {
                            //Test the input data
                            var mockData = {filter_type: 13, id: 130};
                            Y.Assert.areSame("/ci/ajaxRequest/deleteNotification", url);
                            Y.Assert.areSame(data.id, mockData.id);
                            Y.Assert.areSame(data.filter_type, mockData.filter_type);
                            Y.Assert.areSame(options.successHandler, that.instance._onDeleteResponse);
                            Y.Assert.areSame(options.scope, that.instance);
                            Y.Assert.areSame(options.data.data, data);
                            Y.Assert.isTrue(options.json);
                            Y.Assert.isFunction(options.successHandler);
                        }
                        catch(err) {
                            that.delayedErrorMessage = err;
                        }

                        //Trigger the event handler
                        options.successHandler.apply(that.instance, [{}, data]);
                    },
                    testDeleteResponse = function(type, args) {
                        try {
                            Y.Assert.areSame("evt_prodCatNotificationDeleteResponse", type);
                        }
                        catch(err) {
                            that.delayedErrorMessage = err;
                        }
                    };

                RightNow.Ajax.makeRequest = testMakeRequest;
                RightNow.Event.subscribe("evt_prodCatNotificationDeleteResponse", testDeleteResponse, this);
                Y.one(button).simulate('click');
                RightNow.Ajax.makeRequest = tmpMakeRequest;

                this.validateMessage(this.notifDeletedLabel);
                if(this.delayedErrorMessage)
                    Y.Assert.fail(this.delayedErrorMessage.name + ': ' + this.delayedErrorMessage.message);
            }
        }
    }));

    prodCatNotificationManagerTests.add(new Y.Test.Case(
    {
        name: "Event Handling and Operation Add Product or Category",

        testProdCatNotificationAddResponseCase: function() {
            this.initValues();
            var element = Y.one(baseSelector + '_Notification_0'),
                responseInfo = [{"notifications":[{"id":163,"type":"product","label":"Product","summary":"Mobile Broadband","chain":"163","startDate":"10\/13\/2016","expiration":"Expires 10\/20\/2016 (7 days)","rawStartTime":1476351389},{"id":153,"type":"category","label":"Category","summary":"Mobile Services","chain":"153","startDate":"10\/05\/2016","expiration":"Expires 10\/12\/2016 (-1 days)","rawStartTime":1475657811}],"action":"create"}],
                eventObject = new RightNow.Event.EventObject(widget, {data: {filter_type: "Product", id: 163}});
                
            if(element) {
                element.remove();
            }
            widget._onProdCatAdded(eventObject, responseInfo);
            Y.Assert.areSame(widget._numberOfNotifs, 2);
            Y.Assert.isFalse(widget._widgetContainer.hasClass('rn_Loading'));
            Y.Assert.areSame(Y.one(baseSelector + '_Notification_0').get('children').item(0).get('children').item(0).getAttribute('href'), this.productPageURL + "/p/" + responseInfo[0].notifications[0].id);
            Y.Assert.areSame(Y.one(baseSelector + '_Notification_1').get('children').item(0).get('children').item(0).getAttribute('href'), this.categoryPageURL + "/c/" + responseInfo[0].notifications[1].id);
        }
    }));

    prodCatNotificationManagerTests.add(new Y.Test.Case(
    {
        name: "Error Handling Tests",

        tearDown: function() {
            widget._closeDialog();
        },

        "Add product error should be cleared out when dialog is closed": function() {
            this.initValues();

            var dialog = Y.one(widget.baseSelector + '_Dialog'),
                addButton = Y.one('.rn_AddButton'),
                dialogButtons = dialog.all('button'),
                hasProductError = false;

            addButton.simulate('click');
            // click product set button
            dialogButtons.item(1).simulate('click');
            Y.all('.rn_Dialog .rn_MessageBox').each(function(error) {
                var errorText = error.one('b').get('text');
                if (!error.hasClass('rn_Hidden') && errorText === "Select a product") {
                    hasProductError = true;
                }
            });
            Y.Assert.isTrue(hasProductError);
            widget._closeDialog();
            addButton.simulate('click');
            Y.all('.rn_Dialog .rn_MessageBox').each(function(error) {
                Y.Assert.isTrue(error.hasClass('rn_Hidden'));
            });
        },

        "Add category error should be cleared out when dialog is closed": function() {
            this.initValues();

            var dialog = Y.one(widget.baseSelector + '_Dialog'),
                addButton = Y.one('.rn_AddButton'),
                dialogButtons = dialog.all('button'),
                hasCategoryError = false;

            addButton.simulate('click');
            // click category set button
            dialogButtons.item(3).simulate('click');
            Y.all('.rn_Dialog .rn_MessageBox').each(function(error) {
                var errorText = error.one('b').get('text');
                if (!error.hasClass('rn_Hidden') && errorText === "Select a category") {
                    hasCategoryError = true;
                }
            });
            Y.Assert.isTrue(hasCategoryError);
            widget._closeDialog();
            addButton.simulate('click');
            Y.all('.rn_Dialog .rn_MessageBox').each(function(error) {
                Y.Assert.isTrue(error.hasClass('rn_Hidden'));
            });
        },

        "Both errors should be cleared out when dialog is closed": function() {
            this.initValues();

            var dialog = Y.one(widget.baseSelector + '_Dialog'),
                addButton = Y.one('.rn_AddButton'),
                dialogButtons = dialog.all('button'),
                hasProductError = hasCategoryError = false;

            addButton.simulate('click');
            // click product set button
            dialogButtons.item(1).simulate('click');
            // click category set button
            dialogButtons.item(3).simulate('click');
            Y.all('.rn_Dialog .rn_MessageBox').each(function(error) {
                if (!error.hasClass('rn_Hidden')) {
                    var errorText = error.one('b').get('text');
                    if (errorText === "Select a product") {
                        hasProductError = true;
                    }
                    else if (errorText === "Select a category") {
                        hasCategoryError = true;
                    }
                }
            });
            Y.Assert.isTrue(hasProductError);
            Y.Assert.isTrue(hasCategoryError);

            widget._closeDialog();
            addButton.simulate('click');
            Y.all('.rn_Dialog .rn_MessageBox').each(function(error) {
                Y.Assert.isTrue(error.hasClass('rn_Hidden'));
            });
        },

        "Both dialog errors can be shown again after being cleared out once": function() {
            this.initValues();

            var dialog = Y.one(widget.baseSelector + '_Dialog'),
                addButton = Y.one('.rn_AddButton'),
                dialogButtons = dialog.all('button'),
                hasProductError = hasCategoryError = false;

            addButton.simulate('click');
            // click product set button
            dialogButtons.item(1).simulate('click');
            // click category set button
            dialogButtons.item(3).simulate('click');
            widget._closeDialog();
            addButton.simulate('click');
            // click product set button
            dialogButtons.item(1).simulate('click');
            // click category set button
            dialogButtons.item(3).simulate('click');
            Y.all('.rn_Dialog .rn_MessageBox').each(function(error) {
                if (!error.hasClass('rn_Hidden')) {
                    var errorText = error.one('b').get('text');
                    if (errorText === "Select a product") {
                        hasProductError = true;
                    }
                    else if (errorText === "Select a category") {
                        hasCategoryError = true;
                    }
                }
            });
            Y.Assert.isTrue(hasProductError);
            Y.Assert.isTrue(hasCategoryError);
        }
    }));

    return prodCatNotificationManagerTests;
});
UnitTest.run();

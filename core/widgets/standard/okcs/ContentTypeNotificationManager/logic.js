RightNow.Widgets.ContentTypeNotificationManager = RightNow.SearchProducer.extend({
    overrides: {
        constructor: function() {
            this._widgetContainer = this.Y.one(this.baseSelector + '_List');
            this._messageDiv = (this.data.attrs.message_element) ? this.Y.one('#' + this.data.attrs.message_element) : null;
            this.notificationList = this.Y.one(this.baseSelector + '_List');

            if(!this._widgetContainer) return;
            this._hasmore = this.data.js.hasMore;

            this.isContentTypePopulated = false;
            this._contentTypeList = this.data.js.contentTypes;
            this._contentTypeCategList = [];
            this._currentDisplayedNotifCount = 0;
            this._errorDisplay = this.Y.one(this.baseSelector + '_ErrorLocation');
            this._notificationName = this.Y.one(this.baseSelector + '_Name');
            this._contentType = this.Y.one(this.baseSelector + '_ContentType');
            this._product = this.Y.one(this.baseSelector + '_Product');
            this._category = this.Y.one(this.baseSelector + '_Category');
            this.Y.all(this.baseSelector + ' button.rn_Notification_Delete').on("click", this._unsubscribeContentType, this);
            this.contentTypeRecordId = this.productRecordId = this.categoryRecordId = this.selectedContentType = this.subscriptionName = '';
            this.Y.one(this.baseSelector + '_AddButton').on('click', this._openDialog, this);

            RightNow.Event.subscribe("evt_contentTypeSelected", this._fetchProdCategories, this);
            RightNow.Event.subscribe("evt_productSelected", this._updateProductSelected, this);
            RightNow.Event.subscribe("evt_categorySelected", this._updateCategorySelected, this);
            RightNow.Event.subscribe("evt_getCtDom", this._updateContentTypeDom, this);
            this.Y.one(window).on('scroll', this.Y.bind(this._handleScroll, this));
        }
    },
    
    /**
     * Get the paginated results when scroll reaches end of window
     * @param {Object} evt Event
     */
    _handleScroll: function(evt){
        if (evt !== undefined && evt.target !== undefined && evt.target._node !== undefined && evt.target._node.documentElement !== undefined && evt._currentTarget !== undefined) {
            var elem = evt.target._node.documentElement;
            if ((evt._currentTarget.pageYOffset > (elem.scrollHeight - elem.offsetHeight - 10) ) && this._hasmore) {
                this._currentDisplayedNotifCount += 20;
                if(!this._ajaxCallInProgress)
                {
                    RightNow.Event.fire("evt_pageLoading");
                    var eventObject = new RightNow.Event.EventObject(this, {data: { getMoreContentTypeNotif: 'getMoreContentTypeNotif', offset: this._currentDisplayedNotifCount}});
                    RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_notif_data_ajax, eventObject.data, {
                        successHandler: function(response,args){
                            RightNow.Event.fire("evt_pageLoaded");
                            if(response && response.items && response.items.length) {
                                var endOfPage = this.Y.one('.rn_NotificationList');
                                endOfPage.appendChild(new EJS({text: this.getStatic().templates.list}).render({subscriptionList: response,
                                    fields: this.data.js.fields,
                                    labelDeleteButton: this.data.attrs.label_delete_button,
                                    labelContentType: this.data.attrs.label_content_type,
                                    labelProduct: this.data.attrs.label_product,
                                    labelCategory: this.data.attrs.label_category,
                                    target: this.data.attrs.target,
                                    enableProdCategHyperlink: this.data.attrs.disable_prod_categ_hyperlink,
                                    getCategories: this._getCategories,
                                    widgetInstanceID: this.baseDomID,
                                    subscriptionListLength: response.items.length,
                                    answerUrl: this.data.js.answerUrl}));
                                this._hasmore = response.hasMore;
                                this.Y.all(this.baseSelector + ' button.rn_Notification_Delete').detach("click", this._unsubscribeContentType, this);
                                this.Y.all(this.baseSelector + ' button.rn_Notification_Delete').on("click", this._unsubscribeContentType, this);
                                return;
                            }
                        },
                        failureHandler: function(response){
                            RightNow.Event.fire("evt_pageLoaded");
                            this._displayErrorOnFailure(response);
                        },
                        json: true,
                        scope: this
                    });
                }
            }
        }
    },

    /**
    * Creates and opens a dialog allowing the user to add product or category notifications
    */
    _openDialog: function()
    {
        var dialogBody = this.Y.one(this.baseSelector + "_Dialog");
        if(dialogBody && !this._dialog)
        {
            dialogBody = this.Y.Node.getDOMNode(dialogBody.removeClass('rn_Hidden'));
            var buttons = [ {text: this.data.attrs.label_dialog_add, handler: {fn: this._addNotification, scope: this}, isDefault: true},
                            {text: this.data.attrs.label_dialog_cancel, handler: {fn: this._closeDialog, scope: this}, isDefault: true}];
            this._dialog = RightNow.UI.Dialog.actionDialog(this.data.attrs.label_add_notif_dialog, dialogBody, {"buttons": buttons});
            RightNow.UI.show(dialogBody);
        }
        this._dialog.show();
        if(!this.isContentTypePopulated) {
            this._showContentTypes(this._contentTypeList);
            this.isContentTypePopulated = true;
        }
    },

    /**
    * Closes the dialog and resets the widget to display a loading icon if data was requested from the server
    * @param evt {String|Object} Event name or node that was clicked
    */
    _addNotification: function(evt)
    {
        this.subscriptionName = this._notificationName.get('value').trim();
        if(this._validateFormData()) {
            eventObject = new RightNow.Event.EventObject(this, {data: {
                'name': this.subscriptionName,
                'ctRecordId' : this.contentTypeRecordId,
                'productRecordId' : this.productRecordId,
                'categoryRecordId' : this.categoryRecordId
            }});
            RightNow.Ajax.makeRequest(this.data.attrs.add_subscription_ajax, eventObject.data, {
                successHandler: function(response) {
                    if(response.result === 'OKDOM-USER0019') {
                        RightNow.UI.displayBanner(this.data.attrs.label_failure_dup_notif, { type: 'ERROR', focus: true });
                        RightNow.Event.fire("evt_pageLoaded");
                    }
                    else if(response.result === 'OKDOM-USER0020') {
                        RightNow.UI.displayBanner(this.data.attrs.label_failure_inv_hier_notif, { type: 'ERROR', focus: true });
                        RightNow.Event.fire("evt_pageLoaded");
                    }
                    else if(response.failure) {
                        RightNow.UI.displayBanner(this.data.attrs.label_failure_notif_added, { type: 'ERROR', focus: true });
                        RightNow.Event.fire("evt_pageLoaded");
                    }
                    else {
                        this.displayMessage(this.data.attrs.label_notif_added);
                        var topItemNode = this.Y.one(".rn_NotificationList .rn_Notification");
                        if(topItemNode !== null) {
                            topItemNode.insert(new EJS({text: this.getStatic().templates.list}).render({subscriptionList: response,
                                labelDeleteButton: this.data.attrs.label_delete_button,
                                labelContentType: this.data.attrs.label_content_type,
                                labelProduct: this.data.attrs.label_product,
                                labelCategory: this.data.attrs.label_category,
                                target: this.data.attrs.target,
                                enableProdCategHyperlink: this.data.attrs.disable_prod_categ_hyperlink,
                                getCategories: this._getCategories,
                                widgetInstanceID: this.baseDomID,
                                subscriptionListLength: 1
                             }), "before");
                        }
                        else {
                            var endOfPage = this.Y.one('.rn_NotificationList');
                            endOfPage.set("innerHTML", "");
                            endOfPage.appendChild(new EJS({text: this.getStatic().templates.list}).render({subscriptionList: response,
                                labelDeleteButton: this.data.attrs.label_delete_button,
                                labelContentType: this.data.attrs.label_content_type,
                                labelProduct: this.data.attrs.label_product,
                                labelCategory: this.data.attrs.label_category,
                                target: this.data.attrs.target,
                                enableProdCategHyperlink: this.data.attrs.disable_prod_categ_hyperlink,
                                getCategories: this._getCategories,
                                widgetInstanceID: this.baseDomID,
                                subscriptionListLength: 1
                            }));
                        }
                        this.Y.all(this.baseSelector + ' button.rn_Notification_Delete').detach("click", this._unsubscribeContentType, this);
                        this.Y.all(this.baseSelector + ' button.rn_Notification_Delete').on("click", this._unsubscribeContentType, this);
                        RightNow.Event.fire("evt_pageLoaded");
                    }
                },
                failureHandler: this._displayErrorOnFailure,
                json: true, scope: this
            });
            this._closeDialog();
            RightNow.Event.fire("evt_pageLoading");
        }
    },
    
    /**
     * Remove answer from the subscription list
     * @param {Object} evt Event
     */
    _unsubscribeContentType: function(evt) {
        var subscriptionID = evt.target.getAttribute('id'),
            eventObject = new RightNow.Event.EventObject(this, {data: {
            subscriptionID: subscriptionID
        }});
        RightNow.Event.fire("evt_pageLoading");
        RightNow.Ajax.makeRequest(this.data.attrs.delete_notification_ajax, eventObject.data, {
            successHandler: function(response, args){
                if(response.failure) {
                    RightNow.UI.displayBanner(this.data.attrs.label_failure_notif_deleted, { type: 'ERROR', focus: true });
                }
                else {
                    this.displayMessage(this.data.attrs.label_notif_deleted);
                    var item = this.Y.one(this.baseSelector + '_' + subscriptionID), scope = this;
                    if(item) {
                        item.transition({
                            opacity: 0,
                            duration: 0.4
                        }, function() {
                            this.remove();
                            if(!scope.notificationList.all('.rn_Notification').size()) {
                                scope.notificationList.append(scope.data.attrs.label_no_notifs);
                            }
                        });
                    }
                }
                RightNow.Event.fire("evt_pageLoaded");
            },
            failureHandler: this._displayErrorOnFailure,
            json: true, data: {subscriptionID : subscriptionID}, scope: this
        });
    },

    /**
    * Closes the dialog and resets the widget to display a loading icon if data was requested from the server
    * @param evt {String|Object} Event name or node that was clicked
    */
    _closeDialog: function(evt)
    {
        //only display loading icon when coming from evt_menuFilterSelectRequest (not cancelling)
        if(typeof evt === 'string'){
            this._widgetContainer.set('innerHTML', "").addClass('rn_Loading');
            if(this._messageDiv) {
                this._messageDiv.removeClass('rn_MessageBox').set('innerHTML', "");
            }
        }
        if(this._dialog) {
            this._dialog.hide();
            this._clearErrorMessage();
            // Reset all form data
            RightNow.Event.fire("evt_clearFormData");
            this._notificationName.set('value', '');
            this.contentTypeRecordId = this.productRecordId = this.categoryRecordId = this.prodName = this.categoryName = this.selectedContentType = '';
        }
    },

    /**
     * Clears out the error message divs and their classes.
     */
    _clearErrorMessage: function() {
        if(this._errorDisplay) {
            this._errorDisplay.set("innerHTML", '').removeClass('rn_MessageBox rn_ErrorMessage');
        }
    },

    /**
     * Content Type AJAX call success handler.
     * @param {Object} response Response Object
     */
    _showContentTypes: function(response) {
        if(response === null) {
            RightNow.UI.displayBanner(this.data.attrs.label_no_content_type, { type: 'ERROR' });
            this._closeDialog();
        }
        else {
          RightNow.Event.fire("evt_populateContentType", response);
        }
    },

    /**
     * Event handler to fetch products and categories
     * @param {string} type Event name
     * @param {array} args Event arguments
     */
    _fetchProdCategories: function(evt, args) {
        var ctRecord = args[0].details[0];
        this.contentTypeRecordId = ctRecord.value;
        this.selectedContentType = ctRecord.label;
        var productEventObject = new RightNow.Event.EventObject(this, {data: { channelRecordID: this.contentTypeRecordId, offset: 0, productCategoryApiVersion: this.data.js.productCategoryApiVersion, type:'PRODUCT'}});
        var categoryEventObject = new RightNow.Event.EventObject(this, {data: { channelRecordID: this.contentTypeRecordId, offset: 0, productCategoryApiVersion: this.data.js.productCategoryApiVersion, type:'CATEGORY'}});

        if(this.contentTypeRecordId === 0) {
            var responseObject = {},
            response = new Array();
            responseObject.categories = {};
            responseObject.categories.items = [];
            response = responseObject;
            this.selectedContentType = '';
            RightNow.Event.fire("evt_contentTypeChanged_p", response);
            RightNow.Event.fire("evt_contentTypeChanged_c", response);
            return;
        }
        if(this._contentTypeCategList[this.selectedContentType]) {
            if(this._contentTypeCategList[this.selectedContentType].products)
                RightNow.Event.fire("evt_contentTypeChanged_p", this._contentTypeCategList[this.selectedContentType].products);
            if(this._contentTypeCategList[this.selectedContentType].categories)
                RightNow.Event.fire("evt_contentTypeChanged_c", this._contentTypeCategList[this.selectedContentType].categories);
            return;
        }
        RightNow.Event.fire("evt_pageLoading");

        this._contentTypeCategList[this.selectedContentType] = {};
        RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, productEventObject.data, {
            successHandler: function(response){
                RightNow.Event.fire("evt_contentTypeChanged_p", response);
                RightNow.Event.fire("evt_pageLoaded");
                this._contentTypeCategList[this.selectedContentType].products = response;
            },
            failureHandler: this._displayErrorOnFailure,
            json: true,
            scope: this
        });
        RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, categoryEventObject.data, {
            successHandler: function(response){
                RightNow.Event.fire("evt_contentTypeChanged_c", response);
                this._contentTypeCategList[this.selectedContentType].categories = response;
            },
            failureHandler: this._displayErrorOnFailure,
            json: true,
            scope: this
        });
    },

    /**
     * Event handler to fetch the content type dom
     * @param {string} type Event name
     * @param {array} args Event arguments
     */
    _updateContentTypeDom: function(evt,args) {
        this.ctDom = args[0];
    },

    /**
     * Event handler when Product dropdown is selected
     * @param {string} type Event name
     * @param {array} args Event arguments
     */
    _updateProductSelected: function(evt,args) {
        this.productRecordId = args[0].value;
    },

    /**
     * Event handler when Category dropdown is selected
     * @param {string} type Event name
     * @param {array} args Event arguments
     */
    _updateCategorySelected: function(evt,args) {
        this.categoryRecordId = args[0].value;
    },

    /**
    * Displays success message in message box above widget or as user specified div.
    * @param message String Message to display.
    */
    displayMessage: function(message) {
        if(this.messageBox) {
            this.messageBox.setStyle("opacity", 0).addClass("rn_MessageBox");
            this.messageBox.transition({
                opacity: 1,
                duration: 0.4
            });
            this.messageBox.set('innerHTML', message);
            RightNow.UI.updateVirtualBuffer();
            this.messageBox.set('tabIndex', 0).focus();
        }
        else {
            RightNow.UI.displayBanner(message, { baseClass: this.baseSelector });
        }
    },

    /**
     * Utility function to validate the form input fields.
     * @return Boolean if the input field validated successfully
     */
    _validateFormData: function() {
        var nameIsValid = contentTypeIsValid = true;
        if(this._errorDisplay) {
            this._errorDisplay.set("innerHTML", "").removeClass('rn_MessageBox rn_ErrorMessage');
        }
        if(this.subscriptionName === '' ) {
            var nameIsValid = false;
            this._addErrorMessage(this.data.attrs.label_notif_name + ' ' + RightNow.Interface.getMessage("FIELD_IS_REQUIRED_MSG"), this._notificationName.get('id'));
        }
        if(this.selectedContentType === '') {
            var contentTypeIsValid = false;
            this._addErrorMessage(this.data.attrs.label_content_type + ' ' + RightNow.Interface.getMessage("FIELD_IS_REQUIRED_MSG"), this.ctDom);
        }
        return nameIsValid && contentTypeIsValid;
    },

    /**
     * Adds an error message to the page and adds the correct CSS classes
     * @param message String The error message to display
     * @param focusElement HTMLElement The HTML element to focus on when the error message link is clicked
     */
    _addErrorMessage: function(message, focusElement) {
        if(this._errorDisplay !== '') {
            this._errorDisplay.addClass('rn_MessageBox rn_ErrorMessage');
            //add link to message so that it can receive focus for accessibility reasons
            var newMessage = '<a href="javascript:void(0);" onclick="document.getElementById(\'' + focusElement + '\').focus(); return false;">' + message + '</a>';
            var oldMessage = this._errorDisplay.get("innerHTML");
            if (oldMessage !== "")
                newMessage = oldMessage + '<br/>' + newMessage;
            this._errorDisplay.set("innerHTML", newMessage);
            this._errorDisplay.one('a').focus();
            this._errorDisplay.one("h2") ? this._errorDisplay.one("h2").setHTML(RightNow.Interface.getMessage("ERRORS_LBL")) : this._errorDisplay.prepend("<h2>" + RightNow.Interface.getMessage("ERROR_LBL") + "</h2>");
            this._errorDisplay.one("h2").setAttribute('role', 'alert');
        }
    },

    _getCategories: function(subscription) {
        var prodCat = {};
        if(subscription.categories && subscription.categories.length > 0) {
            var product = '';
            var category = '';
            var prodParam, detailUrl;
            for(var i = 0; i < subscription.categories.length; i++) {
                var name = subscription.categories[i].name,
                    slugifiedName = RightNow.Text.slugify(name);
                if(subscription.categories[i].externalType === 'PRODUCT') {
                    product += product === '' ? name : ', ' . name;
                    prodParam = '/p/';
                    detailUrl = RightNow.Interface.getConfig("CP_PRODUCTS_DETAIL_URL");
                    var phref = '/app' + '/' + detailUrl + '/categoryRecordID/' + subscription.categories[i].referenceKey + prodParam + subscription.categories[i].externalId + '/~/' + slugifiedName;
                }
                else if(subscription.categories[i].externalType === 'CATEGORY') {
                    category += category === '' ? name : ', ' . name;
                    prodParam = '/c/';
                    detailUrl = RightNow.Interface.getConfig("CP_CATEGORIES_DETAIL_URL");
                    var chref = '/app' + '/' + detailUrl + '/categoryRecordID/' + subscription.categories[i].referenceKey + prodParam + subscription.categories[i].externalId + '/~/' + slugifiedName;
                }
            }
            if(product !== '') {
                prodCat.product = product;
                prodCat.phref = phref;
            }
            if(category !== '') {
                prodCat.category = category;
                prodCat.chref = chref;
            }
        }
        return prodCat;
    },

    /**
    * Displays error message from the ajax response failure.
    * @param response Object response.
    */
    _displayErrorOnFailure: function(response) {
        var message = response.suggestedErrorMessage || RightNow.Interface.getMessage('THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG');
        if(response.status === 403 && response.responseText !== undefined){
            message = response.responseText;
            RightNow.Event.fire("evt_pageLoaded");
        }
        RightNow.UI.Dialog.messageDialog(message, {"icon": "WARN", exitCallback: function(){window.location.reload(true);}});
    }
});

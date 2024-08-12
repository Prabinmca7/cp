RightNow.Widgets.AssetCheck = RightNow.Widgets.extend({
    constructor: function() {
        this._serialNumberElement = this.Y.one(this.baseSelector + '_AssetSerialNumberInput');
        this._serialNumberSubmitElement = this.Y.one(this.baseSelector + '_SerialNumberSubmit');
        this._serialNumberSubmitElement.on("click", this._onSerialNumberSubmitClick, this);

        this._errorDisplay = this.Y.one(this.baseSelector + "_ErrorMessage");
        RightNow.Event.subscribe("evt_productSelectedFromCatalog", this._onProductSelected, this);

        this.productID = null;
        this.serialized = false;
        this.requestInProgress = false;
    },

    /**
     * Event handler executed whenever a product is selected from product menu
     * @param {String} type Event name
     * @param {Object} args Event arguments
     */
    _onProductSelected: function (type, args) {
        this._removeFormErrors();
        this.productID = args[0].data.productID;
        this.serialized = args[0].data.serialized;
        this._serialNumberElement.set("value", "").set("disabled", !this.serialized);
        var productSelectedMsgDiv = this.Y.one(this.baseSelector + "_ProductSelectedMsg");
        var productSelectedMsgScreenReaderDiv = this.Y.one(this.baseSelector + "_ProductSelectedMsgScreenReader");
        productSelectedMsgDiv.set('innerHTML', '');
        productSelectedMsgScreenReaderDiv.set('innerHTML', '');
        if(this.productID !== 0) {
            var message = (this.serialized) ? this.data.attrs.label_product_requires_serial_number : this.data.attrs.label_product_does_not_require_serial_number;
            productSelectedMsgScreenReaderDiv.set('innerHTML', message);
            productSelectedMsgDiv.removeClass('rn_Hidden').addClass('rn_HintText rn_ErrorLabel').set('innerHTML', message);
        }
    },

    /**
     * Event handler executed when form is being submitted
     * @param {Object} evt Click event
     */
    _onSerialNumberSubmitClick: function(evt) {
        this.productID = parseInt(this.productID, 10);
        if(isNaN(this.productID) || this.productID === 0) {
            RightNow.Event.fire("evt_noProductSelected", new RightNow.Event.EventObject(this, {data: {
                errorMsg: this.data.attrs.label_invalid_product_warning,
                errorLocation: this.data.attrs.error_location
            }}));
        }
        else if(this.serialized) {
            this._handleSerializedProductRegistration();
        }
        else {
            this._handleNonSerializedProductRegistration();
        }
    },

    /**
     * Handles serialized product registration
     */
    _handleSerializedProductRegistration: function() {
        if(this._serialNumberElement && this.requestInProgress === false) {
            var serialNumber = this.Y.Lang.trim(this._serialNumberElement.get("value"));
            if (serialNumber !== '') {
                this.requestInProgress = true;
                var eo = new RightNow.Event.EventObject(this, {data: {"w_id": this.data.info.w_id, serialNumber: serialNumber, productID: this.productID}});
                
                if(this.data.js.f_tok){
                    RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
                    // Get a new f_tok value on each ajax request
                    RightNow.Event.fire("evt_formTokenRequest", new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

                    eo.data.f_tok = this.data.js.f_tok;
                }
        
                if (RightNow.Event.fire("evt_serialNumberValidationRequest", eo)) {
                    RightNow.Ajax.makeRequest(this.data.attrs.serial_number_validate_ajax, eo.data, {
                        successHandler: this._onSerialNumberValidationResponse,
                        scope: this,
                        data: eo,
                        json: true
                    });
                    //Show the loading icon and status message
                    this._loadingElement = this._loadingElement || this.Y.one(this.baseSelector + "_LoadingIcon");
                    RightNow.UI.show(this._loadingElement);
                }
            }
            else {
                this._displayError(this.data.attrs.label_empty_serial_number_warning);
                if(this.data.attrs.always_show_hint) {
                    RightNow.Event.fire("evt_hintAlign", this);
                }
            }
        }
    },

    /**
     * Handles non-serialized product registration
     */
    _handleNonSerializedProductRegistration: function() {
        var additionalParameters = (this.data.attrs.additional_parameters ? "/" + this.data.attrs.additional_parameters : "");
        RightNow.Url.navigate(this.data.attrs.redirect_register_non_serialized_asset + this.productID + this.data.attrs.add_params_to_url + additionalParameters);
    },

    /**
     * Event handler for when form submission returns from the server
     * @param {Object|Boolean} response Server response to request
     * @param {Object} originalEventObject Event arguments
     */
    _onSerialNumberValidationResponse: function(response, originalEventObject) {
        if (RightNow.Event.fire("evt_SerialNumberValidationResponse", response, originalEventObject)) {
            if(response) {
                var additionalParameters = (this.data.attrs.additional_parameters ? "/" + this.data.attrs.additional_parameters : "");
                var serialNumber = this.Y.Lang.trim(this._serialNumberElement.get("value"));
                RightNow.Url.navigate(this.data.attrs.redirect_register_asset + "/asset_id/" + response + "/serial_no/" + encodeURIComponent(serialNumber) + this.data.attrs.add_params_to_url + additionalParameters);
            }
            else {
                this._displayError(this.data.attrs.label_invalid_serial_number_warning);
                if(this.data.attrs.always_show_hint) {
                    RightNow.Event.fire("evt_hintAlign", this);
                }
                RightNow.UI.hide(this._loadingElement);
            }
        }
        this.requestInProgress = false;
    },

    /**
     * Adds error messages to the common error element and adds
     * error indicators to the widget field and label.
     * @param {Array} errors Error messages
     */
    _displayError: function(errors) {
        this._toggleErrorIndicator(false);
        var errorDisplay = this.Y.one("#" + this.data.attrs.error_location);
        if(errorDisplay) {
            var id = this._serialNumberElement.get("id");
            var message = RightNow.Text.sprintf(errors, this.data.attrs.serial_number_label_input + " " + errors);
            var dataFieldDiv = this.Y.Node.create("<div data-field=\"" + this._fieldName + "\">");
            this.Y.Node.create("<b><a href='javascript:void(0);' onclick='document.getElementById(\"" + id + "\").focus(); return false;'>" + message + "</a></b>").appendTo(dataFieldDiv);
            errorDisplay.addClass('rn_MessageBox rn_ErrorMessage').set("innerHTML","");
            dataFieldDiv.appendTo(errorDisplay);
            errorDisplay.one("a").focus();
        }

        this._toggleErrorIndicator(true);
    },

    /**
     * Adds / removes the error indicators on the
     * field and label.
     * @param {Boolean} showOrHide T to add, F to remove
     */
    _toggleErrorIndicator: function(showOrHide) {
        var method = ((showOrHide) ? "addClass" : "removeClass");
        this._serialNumberElement[method]("rn_ErrorField");
        this.Y.one(this.baseSelector + "_Label")[method]("rn_ErrorLabel");
    },

    /**
     * Clears out the error div.
     */
    _removeFormErrors: function() {
        var errorDisplay = this.Y.one("#" + this.data.attrs.error_location);
        if(errorDisplay) {
            errorDisplay.removeClass("rn_MessageBox rn_ErrorMessage").set("innerHTML", "");
        }
        RightNow.Event.fire("evt_removeErrorsFromProductCatalog", this);
        this._toggleErrorIndicator(false);
    }
});

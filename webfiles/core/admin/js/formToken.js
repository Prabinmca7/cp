YUI.add('FormToken', function(Y) {
Y.FormToken = {};

/**
 * Refreshes the valid form token which is about to expire
 *
 * @param object onSuccess Succes Callback function to be invoked on new token response
 * @param object scope Object scope to be used
 */
Y.FormToken.getNewToken = function(onSuccess, scope) {
    _pollingBegan = typeof _pollingBegan === "undefined" ? new Date().getTime() : _pollingBegan;
            timeToRefresh = (submitTokenExp - (submitTokenExp / 6)) * 60000,
            timeSincePollingBegan = new Date().getTime() - _pollingBegan;
    if (timeSincePollingBegan > timeToRefresh) {
        _pollingBegan = new Date().getTime();
        var onNewTokenSuccess = function(id, response) {
            if (!Y.FormToken.parseResponse(response)) {
                return;
            }
            onSuccess(id, response);
        };
        Y.FormToken.makeAjax('/ci/admin/commonActions/getNewFormToken', {
            method: 'POST',
            data: "formToken=" + formToken,
            on: {
                success: onNewTokenSuccess
            },
            context: scope
        });
    }
    else {
        var response = {responseText: '{"newToken": "' + formToken + '"}'};
        onSuccess(0, response);
    }
};

/**
 * Makes Ajax post with the form token
 *
 * @param string url The AJAX endpoint
 * @param object config The AJAX post configurations
 * @param object scope Scope to be used for the callbacks
 */
Y.FormToken.makeAjaxWithToken = function(url, config, scope) {
    config.context = scope || config.context;
    config.data = config.data === undefined ? "" : config.data;
    var onTokenSuccess = function(id, response) {
        var responseData = Y.FormToken.parseResponse(response);
        if (!responseData) {
            return;
        }
        if (typeof config.data === 'string') {
            config.data += (config.data !== "" ? "&" : "") + ("formToken=" + responseData.newToken);
        }
        else {
            config.data.formToken = responseData.newToken;
        }
        var onSuccess = Y.bind(config.on.success, scope);
        config.on.success = function(id, response) {
            var responseData = Y.FormToken.parseResponse(response);
            if (!responseData) {
                return;
            }
            onSuccess(id, response);
        };
        Y.FormToken.makeAjax(url, config);
    };
    Y.FormToken.getNewToken(onTokenSuccess, scope);
};

/**
 * Creates Dialog to display the error message
 *
 * @param string text Error message
 * @returns object Dialog panel object
 */
Y.FormToken.tokenExpiredDialog = function(text) {
    var dialog = new Y.Panel({
            width: "400px",
            centered: true,
            modal: true,
            bodyContent: text,
            headerContent: labels.warning,
            visible: false,
            render: Y.one(document.body),
            constraintoviewport: true,
            buttons: [{ value: labels.ok, action: function(e){e.halt(); this.destroy();} }],
            zIndex: 100
        });
        dialog.after('visibleChange', function(e) {
            // Focus on the first button.
            e.newVal && this.getStdModNode(Y.WidgetStdMod.FOOTER).one('button').focus();
        });

        return dialog.set('visible', true);
};

/**
 * Makes ajax request
 * 
 * @param {string} url Ajax url
 * @param {object} config Ajax configurations
 */
Y.FormToken.makeAjax = function(url, config){
    Y.io(url, config);
};

/**
 * Parses the response and displays the error message for parsing errors
 * 
 * @param {object} response Response object
 * @returns {Boolean | object} Parsed data if no parsing errors else false
 */
Y.FormToken.parseResponse = function(response){
    var parserError = false;
    try {
        var responseData = Y.JSON.parse(response.responseText);
    } catch (e) {
        parserError = true;
    }
    var errorMsgExist = responseData && responseData.errors && responseData.errors.length;
    if (!responseData || errorMsgExist) {
        var errorMsg = parserError ? labels.parserError : (errorMsgExist ? responseData.errors[0].externalMessage : labels.genericError);
        Y.FormToken.tokenExpiredDialog(errorMsg);
        return false;
    }
    return responseData;
};
}, null, {
    requires: ["node", "json", "io-base", "panel"]
});
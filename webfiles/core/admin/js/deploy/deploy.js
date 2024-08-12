/*global messages,postData,filesDataTable,configurationsDataTable,versionDataSelection*/
var getPostVariables, post,
    makeAjaxRequest,
    menuTemplate = '<select id="<%= recordID %>" class="<%= dataTableName %>" onChange="dropDownChangeEvent(this);">' +
    '<% for (var i in options) { %>' +
        '<% if (!options.hasOwnProperty(i)) continue; %>' +
        '<option value="<%= i %>"<%= options[i].disabled %><%= options[i].selected %>><%= options[i].label %></option>' +
    '<% } %>' +
    '</select>';

YUI().use("node", "json", "io-base", "querystring-stringify-simple", "panel", "FormToken", function(Y) {
/**
 * Creates a dialog with the specified properties.
 * @param {string} text Body text
 * @param {string} text Header text
 * @param {array} buttons Buttons for the dialog
 * @return {object} Y.Panel instance
 */
function promptToContinue(text, header, buttons) {
    var dialog = new Y.Panel({
            width: "400px",
            centered: true,
            modal: true,
            bodyContent: text,
            headerContent: header,
            visible: false,
            render: Y.one(document.body),
            constraintoviewport: true,
            buttons: buttons,
            zIndex: 100
        });
    dialog.after('visibleChange', function(e) {
        // Focus on the first button.
        e.newVal && this.getStdModNode(Y.WidgetStdMod.FOOTER).one('button').focus();
    });

    return dialog.set('visible', true);
}

/**
 * Creates and ensures that there's only one dialog created.
 */
var waitContainer = {
    setOptions: function(opts) {
        this.opts = opts;
    },
    get: function() {
        return this.waitDialog || (this.waitDialog = new Y.Panel({
            width: "400px",
            centered: true,
            render: Y.one(document.body),
            visible: false,
            headerContent: this.opts.loadingLabel,
            bodyContent: this.opts.loadingBody,
            constraintoviewport: true,
            buttons: [],
            modal: true,
            zindex: 101
        }));
    }
};

/**
 * Fires off a request to create a deploy lock.
 * @param {object} handlers event handlers for the request
 * @param {number} accountID current user's account id
 * @param {string} operation being done (rollback, promote, stage)
 * @param {number} existingLockAccountID existing lock's user account id
 * @param {string} existingLockCreatedTime timestamp
 */
function createLock(handlers, accountID, deployType, existingLockAccountID, existingLockCreatedTime) {
    Y.FormToken.makeAjaxWithToken('/ci/admin/deploy/lockCreate', {
        method: 'POST',
        data: {
            accountID: accountID,
            deployType: deployType,
            existingLockAccountID: existingLockAccountID || '',
            existingLockCreatedTime: existingLockCreatedTime || ''
        },
        on: handlers
    }, this);
}

/**
 * Enable or Disable elements.
 * @param {object} elements keys are ids, values ar properties to set on the element
 * @param {string} status some sort of status
 */
function toggleElements(elements, status) {
    var element;
    Y.Object.each(elements, function(actions, key) {
        if (element = Y.one("#" + key)) {
            if (actions.onStatus && actions.onStatus !== status) {
                return;
            }
            if (actions.className !== undefined) {
                element.set("className", actions.className);
            }
            if (actions.href) {
                element.set("href", actions.href);
            }
            if (actions.state === 'enable') {
                element.set("disabled", false);
            }
            else if (actions.state === 'disable') {
                element.set("disabled", true);
            }
        }
    });
}

/**
 * Removes an existing lock.
 * @param {object} div Node to set the response on
 * @param {number} accountID user's id
 * @param {function=} callback to call on success or error
 */
function removeLock(div, accountID, callback) {
    var errorRemovingLockMsg = '<span class="errorText">' + messages.errorRemovingLockLabel + '</span><br>';
    var handlers = {
        success : function(transactionID, o) {
            var response = Y.JSON.parse(o.responseText), // Example: {'state' : 1}
                innerHTML = '';
            if (response && response.lock_removed === true) {
                innerHTML = messages.lockRemovedLabel + "<br>";
            }
            else {
                innerHTML = errorRemovingLockMsg;
                if (response && response.lock_details) {
                    innerHTML += messages.lockOverriddenLabel + "<br>" + response.lock_details;
                }
            }
            div.set("innerHTML", innerHTML);
            callback && callback();
        },
        failure : function(transactionID, o) {
            div.append(errorRemovingLockMsg +
                "HTTP status: " + o.status + "<br>" +
                "Status code message: " + o.statusText + "<br>"
            );
            callback && callback();
        },
        customevents : {
            onStart : function(o) {
                div.setHTML(messages.attemptingToRemoveLockLabel + '<br>');
            }
        }
    };

    Y.FormToken.makeAjaxWithToken('/ci/admin/deploy/lockRemove', {method: 'POST', on: handlers, data: {accountID: accountID}}, this);
}

/**
 * Displays a dialog and makes a request to do the stage/deploy/rollback
 * @param {object} args various things
 * @param {object} div Node to write a message onto when the request is done
 * @param {string} lockCreatedTime timestamp
 */
function triggerRequest(args, div, lockCreatedTime) {
    var content = Y.one("#" + args.divId),
        errorRemovingLockMsg = '<span class="errorText">' + messages.errorRemovingLockLabel + '</span><br>',
        onComplete = function(div, accountID, toToggle) {
            removeLock(div, accountID);
        },
        callback = {
        success : function(transactionID, o) {
            var response;
            try {
                response = Y.JSON.parse(o.responseText);

                //If we're in the staging wizard and an error is encountered, display additional
                //messaging to help the user re-stage. This should not impact promote or rollback.

                if (response.status === 'error' && Y.one("#contentsContainer .stageErrorAction")) {
                    Y.one("#contentsContainer .stageErrorAction").removeClass("hide");
                }
                else if (response.status !== 'running') {
                    toggleElements(args.toggleElements, response.status);
                }
            }
            catch (e) {
                response = {'status': 'error', 'html': o.responseText};
            }
            if (response.status === 'running') {
                args.target = response.statusRequest;
                args.postData.logPath = response.logPath;
                args.postData.lastResponse = response.lastResponse;
                triggerRequest(args, div, lockCreatedTime);
            }
            else {
                content.setStyle("visibility", "visible");
                waitContainer.get().hide();
                content.set("innerHTML", response.html);
                // For whatever reason, if 100s of files, a bunch of whitespace is appended to the footer. Scroll back to the top.
                window.scrollTo(0, 0);
                // The Deployer should have already removed the lock, but in the interest of ensuring no stale locks get left behind ...
                onComplete(div, args.accountID, args.toggleElements);
                Y.one('#submitArea').destroy();
            }
        },
        failure : function() {
            content.setStyle("visibility", "visible")
                .set("innerHTML", "CONNECTION FAILED!");
            waitContainer.get().hide();
            onComplete(div, args.accountID, args.toggleElements);
        }
    };
    var waitPanel = waitContainer.get();
    waitPanel.show();
    // Panel 3.6.0 has problems w/ multiple modal panels - the 2nd one won't be completely modal
    // because hiding the first one messes up the modal mask.
    // So... manually boost the z-index of the panel and its mask after the panel's been displayed.
    waitPanel.set('zIndex', 101);
    Y.all('.yui3-widget-mask').setStyle('zIndex', 100);

    args.postData.lockCreatedTime = lockCreatedTime; // Used by deployer
    
    Y.FormToken.makeAjaxWithToken(args.target, {method: 'POST', on: callback, data: args.postData}, this);
}

// PUBLISHED ON THE WINDOW OBJECT

/**
 * @return {object} variables to use for the request
 */
this.getPostVariables = function() {
    if (Y.one('#formSubmitted')) {
        // Once a stage, promote or rollback is performed, no longer persist post data.
        return {};
    }

    var fileIDs = {};
    var variables = postData, record, x, id;

    if (typeof filesDataTable !== 'undefined') {
        filesDataTable.get('data').each(function (record) {
            fileIDs[record.get('fileID')] = record.get('selectedOption');
        });
    }

    if (Y.Object.keys(fileIDs).length !== 0) {
        variables.fileIDs = Y.JSON.stringify(fileIDs);
    }

    if (typeof configurationsDataTable !== 'undefined') {
        configurationsDataTable.get('data').each(function (record) {
            id = record.get('id');
            if (id.split('_')[2] === 'staging') {
                // store id as ps_x, stripping the '_staging' suffix
                variables[id.substr(0, id.length - 8)] = record.get('selectedOption');
            }
        });
    }

    if (typeof VersionData !== 'undefined' && VersionData.selection) {
        variables.version_selection = VersionData.selection;
    }

    return variables;
};

/**
 * Does a post with the vars in #getPostVariables
 */
this.post = function(path) {
    Y.FormToken.getNewToken(function(id, response) {
        var responseData = Y.JSON.parse(response.responseText);
        var form = Y.Node.create("<form method='post'></form>").setAttribute('action', path);
        var postVars = getPostVariables();
        postVars.formToken = responseData.newToken;
        Y.Object.each(postVars, function(value, key) {
            if (key.substring(0, 2) !== "f_") {
                form.append(Y.Node.create("<input type='hidden'/>")
                        .setAttribute("name", key)
                        .setAttribute("value", value)
                        );
            }
        });
        Y.one(document.body).append(form);
        form.submit();
    }, this);
};

/**
 * Displays a modal 'wait' dialog during an ajax call to the specified target.
 * @param {object} args with the following props:
 * - confirmLabel {string} The confirm dialog's body text
 * - proceedLabel {string} The confirm dialog's continue button text
 * - divId [string]
 * - loadingLabel [string]
 * - loadingBody [string]
 * - target [string]
 * - postData [object] - key/value pair post data
 * - toggleElements [object] - element ids to set various attributes on (disable or enable, generally).
 */
this.makeAjaxRequest = function(args) {
    waitContainer.setOptions(args);

    var confirmLabel = args.confirmLabel,
        originalConfirmLabel = args.confirmLabel,
        errorObtainingLockMsg = '<span class="errorText">' + messages.errorObtainingLockLabel + '</span><br>',
        div = Y.one('#statusContainer'),
        existingLockAccountID = null,
        existingLockCreatedTime = null,
        toToggle = args.toggleElements,
        // yes/no handlers for promptToContinue() call
        handleYes = function(e) {
            e.halt();
            this.destroy();
            confirmLabel = originalConfirmLabel;

            if (existingLockAccountID !== null && existingLockCreatedTime !== null) {
                // createLock() has already been called once and a lock from another account was found. User opted to over-ride.
                div.setHTML(messages.attemptingToRemoveLockLabel + '<br>');
                var lockRemoveAndCreateCallback = {
                    success : function(transactionID, o) {
                        var response = Y.JSON.parse(o.responseText);
                        if (!response || response.lock_obtained !== true) {
                            div.append(errorObtainingLockMsg);
                            toggleElements(toToggle);
                        }
                        else {
                            triggerRequest(args, div, response.created_time);
                        }
                    },
                    failure : function(transactionID, o) {
                        div.append(errorObtainingLockMsg +
                            "HTTP status: " + o.status + "<br>" +
                            "Status code message: " + o.statusText + "<br>"
                        );
                        toggleElements(toToggle);
                    }
                };
                createLock(lockRemoveAndCreateCallback, args.accountID, args.deployType, existingLockAccountID, existingLockCreatedTime);
            }
            else {
                triggerRequest(args, div, existingLockCreatedTime);
            }
        },
        handleNo = function(e) {
            e.halt();
            this.destroy();
            confirmLabel = originalConfirmLabel;
            if (existingLockAccountID === null) {
                removeLock(div, args.accountID, function() {
                    toggleElements(toToggle);
                });
            }
            else {
                toggleElements(toToggle);
            }
        },
        buttons = [ { value: args.proceedLabel, action: handleYes }, { value: messages.cancelLabel, action: handleNo, classNames: 'cancelButton' } ],
        createLockCallback = {
            success : function(transactionID, o) {
                var response = (o.responseText) ? Y.JSON.parse(o.responseText) : null; // example: {"account_id":2,"created_time":1283283555,"deploy_type":"stage","lock_obtained":true}
                if (!response || response.error) {
                    div.append(errorObtainingLockMsg + messages.siteConfigError);
                    toggleElements(toToggle);
                    return;
                }
                else if (response.lock_obtained === true) {
                    div.set("innerHTML", response.message);
                    existingLockCreatedTime = response.created_time;
                }
                else {
                    existingLockAccountID = response.account_id;
                    existingLockCreatedTime = response.created_time;
                    div.set("innerHTML", response.message);
                    confirmLabel += "<div class='info'>" + response.message + "<br>" + response.lock_details + "<br><br>" + messages.deployAlreadyLockedButtonsLabel + "</div>";
                }
                promptToContinue(confirmLabel, messages.continueLabel, buttons);
            },
            failure : function(transactionID, o) {
                div.append(errorObtainingLockMsg +
                    "HTTP status: " + o.status + "<br>" +
                    "Status code message: " + o.statusText + "<br>"
                );
                toggleElements(toToggle);
            },
            customevents : {
                onStart : function() {
                    div.setHTML(messages.attemptingToObtainLockLabel + "<br>");
                }
            }
        };

    // Attempt to obtain deploy lock
    createLock(createLockCallback, args.accountID, args.deployType);
};

/**
 * Called when one of the "action" menus (No action|Copy to staging|Remove from Staging) is toggled and
 * updates the corresponding YUI dataTable to reflect the menu selections.
 * When the 'action' menu is 'selectAll', it will also toggle the other menus accordingly.
 * @param {object} menu The menu that was changed.
 */
this.dropDownChangeEvent = function(menu) {
    var dataTableName = menu.className,
        dataTable = this[dataTableName],
        selectedIndex = menu.selectedIndex,
        action = menu.id,
        recordID, disabled, selected, data, optionSelected, currentSelectedIndex, node,
        headerSelect, singleMatch, prefix, matchOne, recordIdentifier, columnName, attrs;

    if (dataTableName === 'filesDataTable') {
        prefix = 'f_';
        recordIdentifier = 'fileID';
        columnName = 'action';
    }
    else if (dataTableName === 'configurationsDataTable') {
        prefix = 'ps_';
        recordIdentifier = 'id';
        columnName = 'menu';
    }
    else {
        return;
    }

    matchOne = (typeof(action) === 'string' && action.indexOf(prefix) === 0),
    headerSelect = Y.one('#selectAll');
    dataTable.data.some(function (record, recordIndex) {
        recordID = record.get(recordIdentifier);
        singleMatch = (matchOne && recordID === action);
        if ((singleMatch || !matchOne) && (node = Y.one('#' + recordID))) {
            currentSelectedIndex = node.get('selectedIndex');
            if (singleMatch || (currentSelectedIndex !== selectedIndex)) {
                data = {recordID: recordID, dataTableName: dataTableName, options: []};
                attrs = {};
                optionSelected = false;
                node.get('options').each(function (option, i) {
                    disabled = selected = '';
                    if (option.get('disabled')) {
                        disabled = 'disabled';
                    }
                    else if (selectedIndex === i) {
                        optionSelected = true;
                        selected = 'selected';
                        attrs.selectedOption = selectedIndex;
                    }
                    data.options.push({label: option.get('innerHTML'), selected: selected, disabled: disabled});
                });
                if (!optionSelected) {
                    data.options[currentSelectedIndex].selected = 'selected';
                }
                // Using record.setAttrs in conjunction with syncUI below instead of dataTable.modifyRow as
                // modifyRow re-renders the entire table which causes large data sets to freeze up the browser.
                attrs[columnName] = new EJS({text: menuTemplate}).render(data);
                record.setAttrs(attrs, {silent: true});
            }
            return singleMatch; // if true, exit the loop
        }
    });
    dataTable.syncUI();
    if (headerSelect) {
        // The syncUI call above causes the replacement of the header select menu causing it to lose state.
        Y.one('#selectAll').replace(headerSelect);
    }
    // drop down was recreated, so we need to refetch it and focus on it
    Y.one('#' + action).focus();
};

Y.on("domready", function() {
    // Actions for the buttons output in buttons.php and the 'next' buttons at the bottom of ea. page.
    var base = '/ci/admin/deploy/',
        buttonActions = {
        selectFiles:        base + 'selectFiles',
        selectVersions:     base + 'selectVersions',
        selectConfigs:      base + 'selectConfigs',
        stage:              base + 'stage',
        promote:            base + 'promote'
    };
    Y.all('.steps a, #next').on('click', function(e) {
        e.halt();
        post(buttonActions[e.currentTarget.getAttribute('data-next-step')]);
    });

    // Toggle log link
    var responseContainer = Y.one('#responseContainer');
    if (responseContainer) {
        responseContainer.delegate('click', function(){
            Y.one('#entireLog').toggleClass('hide');
            Y.one('a#hideLogDisplay').toggleClass('hide');
            Y.one('a#viewLogDisplay').toggleClass('hide');
        }, '#toggleLogDisplay a');
    }
});
});

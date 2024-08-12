/*global hideShowPanel*/
YUI().use('node', function(Y) {
Y.on("domready", function() {
    function pageSetError(turnOn) {
        var msgFunc,
            disabled;
        if(turnOn) {
            msgFunc = "removeClass";
            disabled = true;
        }
        else {
            msgFunc = "addClass";
            disabled = false;
        }
        Y.one("#itsGoTime").set("disabled", disabled);
        Y.one("#pageSetError")[msgFunc]("hide");
    }

    function handleReferenceMode() {
        var pageSet = Y.one("#pageSetButton").getAttribute("selectedValue") || agentCookie, i;
        for(i in defaultPageSets) {
            if(pageSet.indexOf(i) > -1) {
                pageSetError();
                return;
            }
        }
        pageSetError(referenceMode);
    }
    
    var userAgentCheckbox = Y.one("#userAgent"),
        enableAbuseCheckbox = Y.one('#enableAbuse'),
        abuseDetectionHelp = Y.one('#abuseDetectionHelp');
    userAgentCheckbox.on("click", function(e) {
        var checkbox = e.target;
        Y.one("#pageSetButton").set("disabled", checkbox.get("checked"));
        Y.one("#userAgentHelper")[(checkbox.get("checked")) ? "removeClass" : "addClass"]("hide");
    });

    /*
     * Triggered when an option is selected from either the 'site mode' or the 'page set' menus.
     * Toggles the selected class and selectedValue attribute of the menu items.
     * Also toggles the abuse detection checkbox when an item was selected from the 'site mode' menu.
     */
    Y.all('a[data-value]').on('click', function(e) {
        var selectedItem = e.target,
            selectedValue = selectedItem.getAttribute('data-value'),
            button = selectedItem.ancestor('div').previous('button');
        selectedItem.ancestor('ul').all('li').removeClass('selected');
        selectedItem.get('parentNode').addClass('selected');
        button.setAttribute('selectedValue', selectedValue)
            .set('innerHTML', selectedItem.get('innerHTML') + '<small>▼</small>');

        if (button.get('id') === 'siteModeButton') {
            referenceMode = (selectedValue === 'reference');

            var enableAbuseLabel = Y.one('#enableAbuseLabel');
            if (selectedValue === 'development') {
                enableAbuseCheckbox.set('disabled', false);
                enableAbuseLabel.removeClass('disabled');
            }
            else {
                enableAbuseCheckbox.set('disabled', true);
                enableAbuseCheckbox.set('checked', false);
                enableAbuseLabel.addClass('disabled');
            }

            handleReferenceMode();
        }
    });

    Y.one("#itsGoTime").on("click", function() {
        var pageSetButton = Y.one("#pageSetButton"),
            pageSetValue = (pageSetButton.get("disabled")) ? 'default' : (pageSetButton.getAttribute("selectedValue") || agentCookie),
            siteModeButton = Y.one("#siteModeButton"),
            siteModeValue = siteModeButton.getAttribute("selectedValue") || modeCookie,
            enableAbuse = enableAbuseCheckbox.get('checked'),
            modeUrl = "/ci/admin/overview/set_cookie/" + encodeURIComponent(siteModeValue) + "/" + encodeURIComponent(pageSetValue);
        if (enableAbuse)
            modeUrl += "/true";
        window.location = modeUrl;
        return false;
    });

    Y.one("#siteModeButton").on("click", hideShowPanel, null, Y.one("#modeSelection"));
    Y.one('#pageSetButton').set('disabled', userAgentCheckbox.get('checked')).on("click", hideShowPanel, null, Y.one("#pageSelection"));
    abuseDetectionHelp.on(['mouseover', 'focus'], function() {
        Y.one('#abuseDetectionTooltip').removeClass('hide');
    });
    abuseDetectionHelp.on(['mouseout', 'blur'], function() {
        Y.one('#abuseDetectionTooltip').addClass('hide');
    });
    toggle(["#modeSelection", "#pageSelection"]);
});
});

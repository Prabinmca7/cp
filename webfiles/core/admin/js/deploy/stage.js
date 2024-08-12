//= require admin/js/deploy/deploy.js
//= require admin/js/deploy/filesTable.js
//= require admin/js/deploy/versionsTable.js
//= require admin/js/deploy/pageSetTable.js

/*global stageLabels,config,makeAjaxRequest,getPostVariables*/
YUI().use('node', function(Y) {
Y.on("domready", function() {
    var requestArgs = {
        accountID: config.accountID,
        deployType: config.deployType,
        confirmLabel: stageLabels.confirmLabel,
        proceedLabel: stageLabels.proceedLabel,
        divId: 'responseContainer',
        loadingLabel: stageLabels.loadingLabel,
        loadingBody: stageLabels.loadingBody,
        target: '/ci/admin/deploy/stageSubmit',
        postData: [],
        toggleElements: {
            'stageSubmit':{onStatus: 'success', state: 'disable'},
            'submitArea label': {onStatus: 'success', className: 'inactive'}
        }
    };

    function initializeIsChecked() {
        var checkBox = Y.one('#stageInitialize');
        if(checkBox){
            return checkBox.get('checked');
        }
        return false;
    }

    Y.one("#stageSubmit").on("click", function(e) {
        e.halt();

        var post = getPostVariables();

        for (var key in post) {
            if (post.hasOwnProperty(key)) {
                requestArgs.postData[key] = post[key];
            }
        }

        if (initializeIsChecked()) {
            requestArgs.postData.stageInitialize = 1;
        }

        makeAjaxRequest(requestArgs);
    });

    Y.one("#commentEnter").on("change", function(e) {
        requestArgs.postData.comment = e.target.get("value");
    });

    Y.one("#stageInitialize").on("click", function(e) {
        if (initializeIsChecked()) {
            Y.all("#commentEnter,#stageSubmit").removeAttribute("disabled");
            Y.one("#stageButtonLabel").removeAttribute("disabled").setHTML(stageLabels.copyAllLabel);
            requestArgs.confirmLabel = stageLabels.confirmAllLabel + "<br>" + stageLabels.warningLabel;
            requestArgs.loadingLabel = stageLabels.loadingAllLabel;
        }
        else if (config.changes === 0) {
            Y.all("#commentEnter,#stageSubmit").set("disabled", true);
            Y.one("#stageButtonLabel").setHTML(stageLabels.disabledLabel);
        }
        else if (config.changes === 1) {
            Y.one("#stageButtonLabel").setHTML(stageLabels.stageButtonLabel);
            requestArgs.confirmLabel = stageLabels.confirmLabel;
            requestArgs.loadingLabel = stageLabels.loadingLabel;
        }
    });
});
});

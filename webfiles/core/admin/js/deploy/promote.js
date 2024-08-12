//= require admin/js/deploy/deploy.js
//= require admin/js/deploy/filesTable.js
//= require admin/js/deploy/versionsTable.js

/*global promoteLabels,makeAjaxRequest,getPostVariables*/
YUI().use('node', function(Y) {
    var requestArgs = {
        accountID: promoteLabels.accountID,
        deployType: promoteLabels.promoteLabel,
        confirmLabel: promoteLabels.confirmLabel,
        proceedLabel: promoteLabels.proceedLabel,
        loadingLabel: promoteLabels.loadingLabel,
        loadingBody: promoteLabels.loadingBody,
        postData: {},
        divId: 'responseContainer',
        target: '/ci/admin/deploy/promoteSubmit',
        toggleElements: {
            'promoteSubmit': {onStatus: 'success', state: 'disable', className: ''},
            'submitArea label': {onStatus: 'success', className: 'inactive'},
            'rollbackMenuItem': {onStatus: 'success', href: '/ci/admin/deploy/rollback', className: 'yuimenuitemlabel'}
        }
    };
    Y.on("domready", function() {
        Y.one("#commentEnter").on("change", function(e) {
            requestArgs.postData.comment = Y.one("#commentEnter").get("value");
        });

        Y.one("#promoteSubmit").on("click", function(e) {
            e.halt();

            makeAjaxRequest(requestArgs);
        });
    });
});

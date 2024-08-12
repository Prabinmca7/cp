//= require admin/js/deploy/deploy.js

/*global rollbackLabels,makeAjaxRequest,getPostVariables*/
YUI().use('node', function(Y) {
    var requestArgs = {
        accountID: rollbackLabels.accountID,
        deployType: rollbackLabels.promoteLabel,
        confirmLabel: rollbackLabels.confirmLabel,
        proceedLabel: rollbackLabels.proceedLabel,
        loadingLabel: rollbackLabels.loadingLabel,
        loadingBody: rollbackLabels.loadingBody,
        postData: {},
        divId: 'responseContainer',
        target: '/ci/admin/deploy/rollbackSubmit',
        toggleElements: {
            'rollbackSubmit': {onStatus: 'success', state: 'disable', className: ''},
            'submitArea label': {onStatus: 'success', className: 'inactive'},
            'rollbackMenuItem': {onStatus: 'success', href: 'javascript:void(0);', className: 'disabled'}
        }
    };
    Y.on("domready", function() {
        if (Y.one("#commentEnter") && Y.one("#rollbackSubmit")){
            Y.one("#commentEnter").on("change", function(e) {
                requestArgs.postData.comment = Y.one("#commentEnter").get("value");
            });

            Y.one("#rollbackSubmit").on("click", function(e) {
                e.halt();

                makeAjaxRequest(requestArgs);
            });
        }
    });
});

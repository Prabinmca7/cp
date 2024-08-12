YUI().use('node', function(Y) {
    Y.on("domready", function() {
        Y.one('div.rn_WidgetBreadCrumb').delegate("click", function(e) {
            hideShowPanel(e, e.currentTarget.ancestor().next("div"));
        }, 'img');
    });
});

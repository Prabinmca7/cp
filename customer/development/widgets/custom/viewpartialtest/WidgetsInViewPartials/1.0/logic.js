RightNow.namespace('Custom.Widgets.viewpartialtest.WidgetsInViewPartials');
Custom.Widgets.viewpartialtest.WidgetsInViewPartials = RightNow.Widgets.extend({
    constructor: function() {
        RightNow.Ajax.makeRequest(this.data.attrs.ajax_ajax, {
            w_id: this.data.info.w_id
        }, {
            scope: this,
            successHandler: this.render
        });
    },

    render: function (response) {
        this.Y.one(this.baseSelector).append(response.responseText);
    }
});

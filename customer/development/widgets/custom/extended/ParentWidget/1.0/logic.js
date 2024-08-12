RightNow.namespace('Custom.Widgets.extended.ParentWidget');
Custom.Widgets.extended.ParentWidget = Custom.Widgets.extended.GrandParentWidget.extend({
    overrides: {
        constructor: function() {
            this.parent();
        }
    }
});

RightNow.namespace('Custom.Widgets.extended.ChildWidget');
Custom.Widgets.extended.ChildWidget = Custom.Widgets.extended.ParentWidget.extend({
    overrides: {
        constructor: function() {
            this.parent();
        }
    }
});

RightNow.namespace('Custom.Widgets.sample');
Custom.Widgets.sample.SampleWidget = RightNow.Widgets.SelectionInput.extend({
    overrides: {
        constructor: function() {
            this.parent();
            // Already set as instance variables:
                // this.data
                // this.instanceID
                // this.Y
            // Perform any initial javascript logic here...
        }
    },
    // Define any widget functions here    
    _sampleFunction1: function(parameter) {

    },  // Note the comma here

    _sampleFunction2: function(parameter) {

    }   // no comma after last function in the object
});

UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'SelectionInput_0'
}, function(Y, widget, baseSelector){
    var suite = new Y.Test.Suite({
        name: 'standard/input/SelectionInput',

        setUp: function(){
        }
    });

    suite.add(new Y.Test.Case({
        name: "Check toggling of Incident.Threads when status changes",

        testOnChange: function() {

            var formInstance = new RightNow.Form({
                    attrs: {error_location: 'hereBeErrors'},
                    js: {f_tok: 'filler'}
                }, widget.instanceID + '_Incident\\.StatusWithType\\.Status', Y),
                input = Y.one(widget._inputSelector);

            //Invoke this before we add the Incident.Threads field to make sure it doesn't blow up
            input.set('selectedIndex', 1);
            input.simulate('change');

            formInstance.addField('Incident.Threads', {setConstraints: function(constraints){
                if(input.get('selectedIndex') === 1){
                    Y.Assert.isFalse(constraints.required);
                }
                else{
                    Y.Assert.isTrue(constraints.required);
                }
            }});

            input.set('selectedIndex', 1);
            input.simulate('change');
            input.set('selectedIndex', 0);
            input.simulate('change');
        }
    }));
    return suite;
}).run();

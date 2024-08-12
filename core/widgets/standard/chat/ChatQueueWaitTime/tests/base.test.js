// Polyfill for Function.prototype.bind
if (!Function.prototype.bind) {
    Function.prototype.bind = function(oThis) {
        if (typeof this !== 'function') {
            // closest thing possible to the ECMAScript 5
            // internal IsCallable function
            throw new TypeError('Function.prototype.bind - what is trying to be bound is not callable');
        }
        var aArgs   = Array.prototype.slice.call(arguments, 1),
            fToBind = this,
            fNOP    = function() {},
            fBound  = function() {
                return fToBind.apply(this instanceof fNOP
                    ? this
                    : oThis,
                    aArgs.concat(Array.prototype.slice.call(arguments)));
            };

        if (this.prototype) {
            // Function.prototype doesn't have a prototype property
            fNOP.prototype = this.prototype;
        }
        fBound.prototype = new fNOP();

        return fBound;
    };
}
UnitTest.addSuite({
    type:UnitTest.Type.Widget,
	namespaces: [
                'RightNow.Chat.Communicator',
                'RightNow.Chat.Model',
                'RightNow.Chat.Controller',
                'RightNow.Chat.Controller.ChatCommunicationsController',
                'RightNow.Chat.UI'],
    jsFiles: [
                '/euf/core/debug-js/modules/chat/RightNow.Chat.Communicator.js',
                '/euf/core/debug-js/modules/chat/RightNow.Chat.Model.js',
                '/euf/core/debug-js/modules/chat/RightNow.Chat.Controller.js',
                '/euf/core/debug-js/modules/chat/RightNow.Chat.UI.js'],
    instanceID: 'ChatQueueWaitTime_0'
}, function (Y, widget, baseSelector) {
    var chatQueueWaitTimeTests = new Y.Test.Suite({
        name: "standard/chat/ChatQueueWaitTime",
		setUp:function(){
		this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
		}
    });

    chatQueueWaitTimeTests.add(new Y.Test.Case({
        name: "Reconnect message is shown for network failure",
        "Test Reconnect message is displayed in _QueuePosition": function()
        {
            var data = {
                secondsLeft: 100
            };
            RightNow.Event.fire('evt_chatReconnectUpdateResponse', new RightNow.Event.EventObject(widget, {data: data}));
            var queuePosition = Y.one(baseSelector + '_QueuePosition');
            // Test that the QueuePosition shows correct message on network failure
            Y.Assert.isTrue(queuePosition.get('innerHTML').indexOf(RightNow.Interface.getMessage("COMM_RN_LIVE_SERV_LOST_PLS_WAIT_MSG") + " "+ RightNow.Interface.getMessage("DISCONNECTION_IN_0_SECONDS_MSG").replace("{0}", "100" )) !==-1);
        }
    }));
    return chatQueueWaitTimeTests;
}).run();
// Polyfill for CustomEvent
(function() {

    if (typeof window.CustomEvent === "function") return false;

    function CustomEvent(event, params) {
        params = params || {
            bubbles: false,
            cancelable: false,
            detail: undefined
        };
        var evt = document.createEvent('CustomEvent');
        evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
        return evt;
    }

    CustomEvent.prototype = window.Event.prototype;

    window.CustomEvent = CustomEvent;
})();
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
    type: UnitTest.Type.Widget,
    instanceID: 'ChatServerConnect_0'
}, function(Y, widget, baseSelector){
    var chatServerConnectTests = new Y.Test.Suite({
        name: "standard/chat/ChatServerConnect"
    });

    chatServerConnectTests.add(new Y.Test.Case({
        name: "Functionality",
        
        testInvalidChatRequest: function() {
            if(!widget._validChatRequest)
            {
                //test that the error messages are displayed
                Y.Assert.isTrue(Y.one(baseSelector + '_ErrorLocation').hasClass("rn_MessageBox"));
                Y.Assert.isTrue(Y.one(baseSelector + '_ErrorLocation').hasClass("rn_ErrorMessage"));
                Y.Assert.isFalse(Y.one(baseSelector + '_ErrorLocation').hasClass("rn_Hidden"));
            }
        },
        
        testSuccessfulConnection: function() {
            if(widget._validChatRequest)
            {
                RightNow.Event.fire('evt_chatConnectResponse', new RightNow.Event.EventObject(widget, {data: {connected: true}}));

                //test that the message element is displayed
                Y.Assert.isFalse(Y.one(baseSelector + '_Message').hasClass("rn_Hidden"));
                
                //test that connection success message is displayed
                Y.Assert.areEqual(Y.one(baseSelector + '_Message').get('innerHTML'), widget.data.attrs.label_connection_success);
            }
        },
        
        testFailedConnection: function() {
            if(widget._validChatRequest)
            {
                RightNow.Event.fire('evt_chatConnectResponse', new RightNow.Event.EventObject(widget, {data: {connected: false}}));
                
                //test that the message element is displayed
                Y.Assert.isFalse(Y.one(baseSelector + '_Message').hasClass("rn_Hidden"));
                
                //test the connection failure message is displayed
                Y.Assert.areEqual(Y.one(baseSelector + '_Message').get('innerHTML'), widget.data.attrs.label_connection_fail);
            }
        },

        testExistingSession: function() {
            if(widget._validChatRequest)
            {
                RightNow.Event.fire('evt_chatConnectResponse', new RightNow.Event.EventObject(widget, {data: {connected: true, existingSession: true}}));
                
                //test that the existing session dialog is displayed
                Y.Assert.isFalse(Y.one('#rnDialog1').hasClass("rn_Hidden"));
                
                //test the dialog header and body have the appropriate content
                Y.Assert.areEqual(Y.one('#rnDialog1 .yui3-widget-hd #rn_Dialog_1_Title').get('innerHTML'), RightNow.Interface.getMessage("EXISTING_CHAT_SESSION_LBL"));
                Y.Assert.areEqual(Y.one('#rnDialog1 .yui3-widget-bd div').get('innerHTML'), RightNow.Interface.getMessage("EXISTING_CHAT_SESS_FND_RESUME_SESS_MSG"));
            }
        },
        
        testChatStateChange: function() {
            if(widget._validChatRequest)
            {
                RightNow.Event.fire('evt_chatStateChangeResponse', new RightNow.Event.EventObject(widget));
                
                //test that this widget is hidden
                Y.Assert.isTrue(Y.one(baseSelector).hasClass("rn_Hidden"));
            }
        },

        testExistingPersistentChatSession: function() {
            if(widget._validChatRequest)
            {
                widget.data.attrs.is_persistent_chat = true;
                RightNow.Event.subscribe('evt_chatConnectRequest', function(type, args){
                    args = args[0];
                    Y.Assert.isTrue(args.data.resume);
                }, this);
                RightNow.Event.fire('evt_chatConnectResponse', new RightNow.Event.EventObject(widget, {data: {connected: true, existingSession: true}}));
                widget.data.attrs.is_persistent_chat = false;
            }
        }
    }));
    return chatServerConnectTests;
});
UnitTest.run();
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
    type: UnitTest.Type.Framework,
    jsFiles: [
        '/euf/core/debug-js/RightNow.Text.js',
        '/euf/core/debug-js/RightNow.Event.js',
        '/euf/core/debug-js/modules/chat/RightNow.Chat.Communicator.js',
        '/euf/core/debug-js/modules/chat/RightNow.Chat.Controller.js',
        '/euf/core/debug-js/modules/chat/RightNow.Chat.Model.js',
        '/euf/core/debug-js/modules/chat/RightNow.Chat.UI.js',
        '/euf/core/debug-js/modules/chat/RightNow.Chat.LS.js'
    ],
    namespaces: [
        'RightNow.Event',
        'RightNow.Chat.Communicator',
        'RightNow.Chat.Controller.ChatCommunicationsController',
        'RightNow.Chat.UI',
        'RightNow.Chat.Model',
        'RightNow.Chat.Controller'
    ]
}, function(Y) {
    var rightnowChatLSTests = new Y.Test.Suite("RightNow.Chat.LS");

    rightnowChatLSTests.add(new Y.Test.Case(
    {
        name: 'Local storage test cases for persistent chat',
        testSetItem: function()
        {
            var ls = RightNow.Chat.LS;
            if(ls.isSupported) {
                ls.setItem('key', 'value');
                Y.Assert.areSame(localStorage.getItem('key'), '"value"');
                localStorage.removeItem('key');
            }
        },
        
        testGetItem: function()
        {
            var ls = RightNow.Chat.LS;
            if(ls.isSupported) {
                var value = '{name: "joe"}';
                localStorage.setItem('key', JSON.stringify(value));
                Y.Assert.areSame(ls.getItem('key'), value);
                localStorage.removeItem('key');
            }
        },

        testBufferItem: function()
        {
            var ls = RightNow.Chat.LS;
            if(ls.isSupported) {
                localStorage.removeItem(ls._bufferPrefix + 'key');
                ls.bufferItem('key', 'item1');
                ls.bufferItem('key', 'item2');
                var items = ls.getItem(ls._bufferPrefix + 'key');
                Y.Assert.areSame(items[0], 'item1');
                Y.Assert.areSame(items[1], 'item2');
                ls.removeItem(ls._bufferPrefix + 'key');
            }
        },

        testStoreEvent: function()
        {
            var ls = RightNow.Chat.LS;
            if(ls.isSupported) {
                ls.setItem('key', 'value');
                RightNow.Event.subscribe("evt_addChat", function(type, args){
                    Y.Assert.areSame(args[0].data, 'value');
                }, this);
                var newValue = {chatWindowId: '12345', type: 'CHAT_TRANSCRIPT'};
                newValue = JSON.stringify(newValue);
                var event = {newValue: newValue, key: 'key'};
                ls.receiveStoreEvent(event);
                ls.removeItem('key');
            }
        }
    }));
    return rightnowChatLSTests;
}).run();

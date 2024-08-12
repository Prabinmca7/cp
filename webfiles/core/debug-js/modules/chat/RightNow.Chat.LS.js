RightNow.namespace("RightNow.Chat.LS");
/**
 * Contains local storage operations for persistent chat
 * @namespace
 */
RightNow.Chat.LS = (function() {
        var ls = {};

        /** name of various local storage keys */
        ls._transcriptPrefix = 'pctranscript';
        ls._disconnectPrefix = 'pcdisconnect';
        ls._bufferPrefix = 'pcbuffer';
        ls._windowStateKey = 'pcwinstate';
        ls._connectStatusKey = 'pcconnectstatus';
        ls._testKey = 'pctestkey';
        ls._lastUpdateKey = 'pclastupdated';
        ls._soundButtonSyncKey = 'pcsoundonoff';
        ls._chatLaunchedKey = 'pcchatlaunched';
        ls._udataKey = 'pcudata';

        /* assign unique id to the currnent chat window */
        ls._thisWindowId = new Date().getTime();

        /**
         * Attach handler to storage event
         */
        ls.attachStoreEvent = function() {
            window.addEventListener('storage', ls.receiveStoreEvent, false);
        };  

        /**
         * Handles syncing of messages/states across different tabs.
         * The storage event is captured by the open tabs  .     
         * @param {Event} event
         */
        ls.receiveStoreEvent = function(event) {
            if(event.newValue === null || event.key === ls._testKey) {
                return;
            }
            try {
                var msg = JSON.parse(event.newValue);
            }
            catch (exception) {
                return;
            }
            //Return if the event is raised by window itself.  This is an issue with IE 
            if(msg.hasOwnProperty('chatWindowId') && msg.chatWindowId === ls._thisWindowId) {
                return;
            }
            if(event.key === ls._windowStateKey) {
                RightNow.Event.fire('evt_setWindowState', new RightNow.Event.EventObject(this, {}));
            }

            switch(msg.type) {
                case 'CHAT_TRANSCRIPT':
                    var data = ls.getItem(event.key);
                    if(data === undefined) {
                        data = JSON.parse(event.newValue);
                    }
                    var eo = new RightNow.Event.EventObject(this, {data: data});
                    RightNow.Event.fire('evt_addChat', eo);
                    break;
                case 'CHAT_BUFFER':
                    RightNow.Event.fire('evt_setChatWindow', new RightNow.Event.EventObject(this, {}));
                    break;
                case 'CHAT_CONNECT_STATUS':
                    var data = ls.getItem(event.key);
                    RightNow.Event.fire('evt_syncChatConnectStatus', new RightNow.Event.EventObject(this, {data: data}));
                    break;
                case 'CHAT_SOUND_STATE':
                    var data = ls.getItem(event.key);
                    RightNow.Event.fire('evt_soundButtonSync', new RightNow.Event.EventObject(this, {data: data}));
                    break;
                case 'CHAT_LAUNCHED':
                    var data = ls.getItem(event.key);
                    RightNow.Event.fire('evt_chatLaunchedNotify', new RightNow.Event.EventObject(this, {data: data}));
                    break;
                case 'CHAT_DISCONNECT':
                    var data = ls.getItem(event.key);
                    if(data === undefined) {
                        data = JSON.parse(event.newValue);
                    }
                    RightNow.Event.fire('evt_notifyChatDisconnect', new RightNow.Event.EventObject(this, {data: data}));
                    break;
            }
        };

        /**
         * Determines if local storage is supported by the browser.
         */
        ls.isSupported = (function() {
            var test = 'test';
            try {
                window.localStorage.setItem(ls._testKey, test);
                window.localStorage.removeItem(ls._testKey);
                return true;
            }
            catch (exception) {
                return false;
            }
        })();

        /**
         * Buffer chat transcript in local storage
         * @param {string} key
         * @param {Object} value
         */
        ls.bufferItem = function(key, value) {
            if(!this.isSupported || !key)
            {
                return;
            }
            var item = ls.getItem(this._bufferPrefix + key);
            if(!item) {
                item = [];
            }
            item.push(value);
            ls.setItem(this._bufferPrefix + key, item);  
        };

        /**
         * Store item in local storage
         * @param {string} key
         * @param {Object} value
         */
        ls.setItem = function(key, value) {
            if(!this.isSupported || !key || value === undefined)
            {
                return;
            }
            try {
                window.localStorage.setItem(key, JSON.stringify(value));
            }
            catch (exception) {
                // localstorage may be full
            }
        };

        /**
         * Get item from local storage.
         * @param {string} key
         */
        ls.getItem = function (key) {
            if(!this.isSupported)
            {
                return;
            }
            var value = window.localStorage.getItem(key);
            if(typeof value != 'string')
            {
                return;
            }
            try {
                return JSON.parse(value);
            }
            catch (exception) {
                return;
            }
        };

        /**
         * Wrapper for removing an item from local storage.
         * @param {string} key
         */
        ls.removeItem = function(key) {
            if(!this.isSupported || !key)
            {
                return;
            }
            window.localStorage.removeItem(key);
        };

        /**
         * Remove all persistent chat items.
         */
        ls.removeAllPCItems = function() {
            if(!this.isSupported)
            {
                return;
            }
            var localStorage = window.localStorage,
                keyPrefixesToRemove = [this._transcriptPrefix, this._bufferPrefix, this._connectStatusKey, this._lastUpdateKey, this._chatLaunchedKey, this._udataKey],
                prefixesLength = keyPrefixesToRemove.length,
                lsLength = localStorage.length,
                key;
            for(var i = 0; i < lsLength; i++) {
                key = localStorage.key(i);
                if(typeof key === 'string')
                {
                    for(var j = 0; j < prefixesLength; j++) {
                        if(key.substring(0, keyPrefixesToRemove[j].length) === keyPrefixesToRemove[j]) {
                            localStorage.removeItem(key);
                        }
                    }
                }
            };
        };

        return ls;
}());

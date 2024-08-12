RightNow.Widgets.ChatSoundButton = RightNow.Widgets.extend({
    constructor: function(){
        // Local member variables section
        this._container = this.Y.one(this.baseSelector);
        this._containerOn = this.Y.one(this.baseSelector + '_On');
        this._containerOff = this.Y.one(this.baseSelector + '_Off');
        this._soundButtonOn = this.Y.one(this.baseSelector + "_ButtonOn");
        this._soundButtonOff = this.Y.one(this.baseSelector + "_ButtonOff");

        //this is set when HTML5 audio is supported and browser can play our media types
        this._audioNode = null;

        //this is set when flash is supported but NOT HTML5 audio
        this._flashPlayer = null;

        //variables for testing HTML5 Sound support
        var oggEnabled = false;
        var wavEnabled = false;
        var mp3Enabled = false;
        var html5Test = /^(probably|maybe)$/i;

        //Test whether HTML5 audio is supported
        //iPad does not allow to play audio without an user initiated action; we will just disable audio
        if(typeof Audio !== 'undefined' && typeof new Audio().canPlayType !== 'undefined' && !this.Y.UA.ipad)
        {
            if(new Audio().canPlayType('audio/mpeg').match(html5Test) && this._resourceExists(this.data.attrs.receive_sound_mp3_path))
            {
                mp3Enabled = true;
            }
            else if(new Audio().canPlayType('audio/ogg').match(html5Test) && this._resourceExists(this.data.attrs.receive_sound_ogg_path))
            {
                oggEnabled = true;
            }
            else if(new Audio().canPlayType('audio/x-wav').match(html5Test) && this._resourceExists(this.data.attrs.receive_sound_wav_path))
            {
                wavEnabled = true;
            }
        }

        //create HTML, create a Node that represents the audio element (if enabled) or the flash player container,
        //and append to the container
        if(!this.Y.UA.ipad)
        {
            this._container.appendChild(this.Y.Node.create(new EJS({text: this.getStatic().templates.soundElement}).render({
                attrs: this.data.attrs,
                instanceID: this.instanceID,
                oggEnabled: oggEnabled,
                mp3Enabled: mp3Enabled,
                wavEnabled: wavEnabled})));
        }

        this._audioNode = this.Y.one(this.baseSelector + '_ReceiveSound');

        //check if we are to use flash for delivery of the media
        var flashContainer = this.Y.one(this.baseSelector + '_ReceiveSoundFlashContainer');
        if(!this._audioNode && flashContainer)
        {
            if(this.Y.UA.ie)
            {
                var flashObjectString = '<object id="' + this.instanceID + '_ReceiveSoundFlash" ';
                flashObjectString += 'classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" tabindex="-1" ';
                flashObjectString += 'type="application/x-shockwave-flash" data="' + this.data.attrs.receive_sound_flash_path + '">';
                flashObjectString += '<param name="movie" value="' + this.data.attrs.receive_sound_flash_path + '">';
                flashObjectString += '<param name="allowScriptAccess" value="always">';
                flashObjectString += '<param name="loop" value="false">';
                flashObjectString += '<param name="play" value="false">';
                flashObjectString += '</object>';

                flashContainer.getDOMNode().innerHTML = flashObjectString;
                this._flashObject = document.getElementById(this.instanceID + "_ReceiveSoundFlash");
            }
            else
            {
                //using the SWF utility, we will create a new flash player instance
                this._flashPlayer = new this.Y.SWF(flashContainer,
                        this.data.attrs.receive_sound_flash_path,
                        {fixedAttributes: {allowScriptAccess:"always", play:"false", loop:"false"}});
            }
        }

        if(this.data.attrs.is_persistent_chat)
        {
            this._ls = RightNow.Chat.LS;
            if(this._ls.isSupported)
            {
                this._ls.attachStoreEvent();
            }
            RightNow.Event.subscribe("evt_soundButtonSync", this._onSoundButtonSync, this);
        }

        // if support found, show UI controls and subscribe to events. If no support detected, apply styles to hide the UI elements.
        if(this._audioNode || this._flashPlayer || this._flashObject)
        {
            this._soundEnabled = this.data.attrs.sound_on_by_default;
            if(this.data.attrs.is_persistent_chat && this._ls.isSupported && this._ls.getItem(this._ls._soundButtonSyncKey))
            {
                var soundButtonSync = this._ls.getItem(this._ls._soundButtonSyncKey);
                this._soundEnabled = soundButtonSync.value;
            }
            if(this._containerOn && this._soundButtonOn)
            {
                this._soundButtonOn.on("click", this._onButtonOnClick, this);

                if(this._soundEnabled && RightNow.UI && RightNow.UI.show)
                    RightNow.UI.show(this._containerOn);
            }

            if(this._containerOff && this._soundButtonOff)
            {
                this._soundButtonOff.on("click", this._onButtonOffClick, this);

                if(!this._soundEnabled && RightNow.UI && RightNow.UI.show)
                    RightNow.UI.show(this._containerOff);
            }

            RightNow.Event.subscribe("evt_chatStateChangeResponse", this._onChatStateChangeResponse, this);
            RightNow.Event.subscribe("evt_chatPostResponse", this._onChatPostResponse, this);
        }
        else
        {
            this._hideSoundControls();
        }
    },

    /**
     * Handles when user clicks sound on button. Toggles to turn the sound off.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onButtonOnClick: function(type, args)
    {
        this._soundEnabled = false;
        RightNow.UI.hide(this._containerOn);

        RightNow.UI.show(this._containerOff);
        if(this.data.attrs.is_persistent_chat && this._ls.isSupported)
        {
            var data = {type: 'CHAT_SOUND_STATE', chatWindowId : this._ls._thisWindowId, value: false};
            this._ls.setItem(this._ls._soundButtonSyncKey, data);
        }
    },

    /**
     * Handles when user clicks sound off button. Toggles to turn the sound on.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onButtonOffClick: function(type, args)
    {
        this._soundEnabled = true;
        RightNow.UI.hide(this._containerOff);

        RightNow.UI.show(this._containerOn);
        if(this.data.attrs.is_persistent_chat && this._ls.isSupported)
        {
            var data = {type: 'CHAT_SOUND_STATE', chatWindowId : this._ls._thisWindowId, value: true};
            this._ls.setItem(this._ls._soundButtonSyncKey, data);
        }
    },

    /**
    * Handles the state of the chat has changed.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatStateChangeResponse: function(type, args)
    {
        var currentState = args[0].data.currentState;
        var ChatState = RightNow.Chat.Model.ChatState;

        if(currentState === ChatState.CONNECTED)
            this._playReceive();
        else if(currentState === ChatState.DISCONNECTED || currentState === ChatState.CANCELLED)
            this._hideSoundControls();
    },

    /**
     * Handled playing the receive sound. Checks for whether the post is from the agent.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatPostResponse: function(type, args)
    {
        if(!args[0].data.isEndUserPost)
            this._playReceive();
    },

    /**
     * Plays the receive sound.
     */
    _playReceive: function()
    {
        if(!this._soundEnabled)
            return;

        try
        {
            if(this._audioNode)
            {
                this._audioNode.getDOMNode().play();
            }
            else if(this._flashPlayer)
            {
                this._flashPlayer.callSWF("Play");
            }
            else if(this._flashObject)
            {
                this._flashObject.play();
            }
        }
        catch(e)
        {
        }
    },

    /**
     * Hides the sound controls from the UI, if they exist
     */
    _hideSoundControls: function()
    {
        if(this._containerOn)
            RightNow.UI.hide(this._containerOn);

        if(this._containerOff)
            RightNow.UI.hide(this._containerOn);
    },

    /**
     * Checks whether a resource exists
     */
    _resourceExists: function(filename)
    {
        var http = new XMLHttpRequest();
        http.open('HEAD', filename, false);
        try {
            http.send();
        }
        catch(err) {
            return false;
        }
        return (http.status === 200 || http.status === 304);
    },

    /**
     * Handles syncing of sound button states across multiple tabs
     * @param {type} type
     * @param {type} args
     */
    _onSoundButtonSync: function(type, args)
    {
        var soundEnabled = args[0].data.value;
        if(soundEnabled && this._containerOff && this._soundButtonOff)
        {
            this._soundEnabled = true;
            RightNow.UI.hide(this._containerOff);
            RightNow.UI.show(this._containerOn);
        }
        if(!soundEnabled && this._containerOn && this._soundButtonOn)
        {
            this._soundEnabled = false;
            RightNow.UI.hide(this._containerOn);
            RightNow.UI.show(this._containerOff);
        }
    }
});

RightNow.Widgets.SocialBookmarkLink = RightNow.Widgets.extend({
    constructor: function() {
        this.Y.Event.attach("click", this._onClick, this.baseSelector + "_Link", this);
        this.Y.Event.attach("click", this._togglePanel, this.baseSelector + "_Panel li a", this);

        // subscribe to the event for update in status to a social question
        RightNow.Event.subscribe('evt_inlineModerationStatusUpdate', this._onStatusUpdate, this);
    },

    /**
     * Executed when link is clicked on.
     * @param {Object} event Click Event
     */
    _onClick: function(event) {
        event.halt();

        if (this.data.attrs.object_type === 'answer') {
            this._togglePanel();
            return;
        }

        var eventObject = new RightNow.Event.EventObject(this, {data: {
            qid: RightNow.Url.getParameter('qid'),
        }});
    
        if(this.data.js.f_tok){
            RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
            // Get a new f_tok value on each ajax request
            RightNow.Event.fire("evt_formTokenRequest", new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

            eventObject.data.f_tok = this.data.js.f_tok;
        }

        RightNow.Ajax.makeRequest(this.data.attrs.check_question_exist_ajax, eventObject.data, {
            data:           eventObject,
            json:           true,
            scope:          this,
            successHandler: this._onResponseReceived
        });
    },

    /**
     * Displays the Social Bookmark panel based on whether the question exist
     * @param response Event response
     */
    _onResponseReceived: function(response) {
        if (response.errors) {
            if(!RightNow.Ajax.indicatesSocialUserError(response)) {
                var dialogParameters = {exitCallback: {fn: function() { messageDialog.hide(); }, scope: this}},
                    messageDialog;
                dialogParameters.icon = 'WARN';
                messageDialog = RightNow.UI.Dialog.messageDialog(response.errors[0].externalMessage, dialogParameters).show();
            }
        }
        else {
            this._togglePanel();
        }
    },

    /**
     * Creates a YUI Panel instance for the list of
     * share links.
     * @param  {object} panelElement YUI Node that's
     *                               to be displayed
     *                               as a panel
     * @return {object}              YUI Panel instance
     */
    _createPanel: function (panelElement) {
        var widget = this.Y.one(this.baseSelector);

        RightNow.UI.show(panelElement);

        var panel = new this.Y.Panel({
            srcNode: panelElement,
            align: {
                node: widget,
                points: [ this.Y.WidgetPositionAlign.TC, this.Y.WidgetPositionAlign.BC ]
            },
            render: widget,
            visible: false,
            zIndex: 10,
            buttons: [],
            alignOn: [{ node: 'win', eventName: 'resize' }],
            hideOn: [{
                    eventName: "clickoutside"
                }, {
                    node: panelElement.all("a").slice(-1).item(0),
                    eventName: "keydown",
                    keyCode: RightNow.UI.KeyMap.TAB
                }
            ]
        });

        panel.after('visibleChange', function (e) {
            if (e.newVal) {
                this._focusFirstLink();
            }
        }, this);

        return panel;
    },

    /**
     * Focuses the first link in the panel.
     */
    _focusFirstLink: function() {
        this._panel.get('contentBox').one('a').focus();
    },

    /**
     * Event handler for when social question status update event is received from server.
     * @param {Object} evt Current event object
     * @param {Array} args Data passed from widget which trigger this function call
     */
    _onStatusUpdate: function(evt, args) {
        // if the question's status changes to active then show share link else hide it
        if (args[0].data.object_data.updatedObject.objectType === 'CommunityQuestion' && args[0].data.object_data.updatedObject.ID === parseInt(this.data.js.objectID, 10)) {
            if (parseInt(args[0].data.object_data.updatedObject.statusWithTypeID, 10) !== this.data.js.activeStatusWithTypeID) {
                RightNow.UI.hide(this.baseSelector);
            }
            else {
                RightNow.UI.show(this.baseSelector);
            }
        }
    },

    /**
     * Expand or collapse share panel
     * @return {bool|null} Returns false if panel not found.
     */
    _togglePanel: function() {
        var panelElement = this.Y.one(this.baseSelector + "_Panel");

        if (!panelElement) {
            return false;
        }

        this._panel = this._panel || this._createPanel(panelElement);

        if (this._panel.get("visible")) {
            this._panel.hide();
        }
        else {
            this._panel.show();
        }
    }
});

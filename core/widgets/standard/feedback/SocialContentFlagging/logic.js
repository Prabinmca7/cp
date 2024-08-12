RightNow.Widgets.SocialContentFlagging = RightNow.Widgets.extend({
    constructor: function () {
        if(this.data.js.isFlagged && this.data.js.flags.length === 1)
            return;

        this.Y.one(this.baseSelector + '_Button').on('click', this.flagButtonClicked, this);
        this.Y.one(this.baseSelector).delegate('click', this.onFlagClick, 'a.rn_Flag', this);
    },

    /**
     * Triggers when the flag button is clicked.
     * @param  {object} e Click event
     */
    flagButtonClicked: function (e) {
        e.halt();

        //Content already flagged
        if(!e.currentTarget.one('.rn_Flagged.rn_Hidden') && this.data.js.flags.length === 1)
            return;

        if (this.data.js.flags.length > 1) {
            this.toggleDropdown();
        }
        else {
            this.toggleLoadingOnFlag(e.currentTarget);
            this.submitFlag(this.data.js.flags[0].ID);
        }
    },

    /**
     * Triggers when a flag value is clicked.
     * @param  {Object} e click event
     */
    onFlagClick: function (e) {
        if (e.currentTarget.hasClass('rn_Selected')) {
            this.indicateFlaggedStateOnButton();
            this.toggleDropdown();
            return;
        }

        this.toggleLoadingOnFlag(e.currentTarget);

        this.submitFlag(e.currentTarget.getAttribute('data-id'));
    },

    /**
     * Submits the flag ID to the server.
     * @param  {string|number} flagID Flag type id
     */
    submitFlag: function (flagID) {
        var eo = new RightNow.Event.EventObject(this, {
            data: {
                flagID: flagID,
                questionID: this.data.attrs.question_id,
                commentID: this.data.attrs.comment_id,
                w_id: this.data.info.w_id
            }
        });
        
        if(this.data.js.f_tok){
            RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
            // Get a new f_tok value on each uploads
            RightNow.Event.fire("evt_formTokenRequest", new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

            eo.data.f_tok = this.data.js.f_tok;
        }

        if (RightNow.Event.fire('evt_ContentFlagSubmit', eo)) {
            RightNow.Ajax.makeRequest(this.data.attrs.submit_flag_ajax, eo.data, {
                data:           eo,
                json:           true,
                scope:          this,
                successHandler: this.onFlagSubmitted
            });
        }
    },

    /**
     * Callback for flagging response from the server.
     * @param  {Object} response            Flag
     * @param  {Object} originalEventObject Original event object
     */
    onFlagSubmitted: function (response, originalEventObject) {
        if (RightNow.Event.fire('evt_ContentFlagResponse', response, originalEventObject) && response.type) {
            this.indicateFlaggedStateOnButton();

            if (this.dropdown) {
                this.indicateFlaggedStateInDropdown(response.type);
                this.toggleDropdown();
            }
        }
        else {
            this.toggleLoadingOnFlag(this.Y.one(this.baseSelector + '_Button'), false);
            if(!RightNow.Ajax.indicatesSocialUserError(response)) {
                RightNow.UI.displayBanner(this.data.attrs.label_action_cannot_be_completed, {
                    type: 'ERROR',
                    focusElement: this.Y.one(this.baseSelector + ' .rn_Unflagged')
                });
            }
        }
    },

    /**
     * Removes the selected style from every dropdown item
     * and adds it to the indicated one.
     * @param  {Number|String} flagID flag id
     */
    indicateFlaggedStateInDropdown: function (flagID) {
        var widget = this.Y.one(this.baseSelector),
            className = 'rn_Selected',
            selectedFlag = widget.one('.rn_Flag[data-id="' + flagID + '"]');
        widget.all('.rn_Flag').removeClass(className);
        this.toggleLoadingOnFlag(selectedFlag.addClass(className));
    },

    /**
     * Hides the 'no flag' indicators from the button and shows the flagging ones
     * and focuses the button.
     */
    indicateFlaggedStateOnButton: function () {
        var flagButton = this.Y.one(this.baseSelector + '_Button');
        flagButton.setAttribute('title', this.data.attrs.label_already_flagged_tooltip);
        RightNow.UI.show(flagButton.all('.rn_Flagged'));
        RightNow.UI.hide(flagButton.all('.rn_Unflagged'));
        flagButton.focus();
        this.toggleLoadingOnFlag(flagButton, false);
    },

    /**
     * Toggles the loading state on the given flag element.
     * @param  {Object} flagItem Flag Node
     * @param {bool=} force True to force-add the loading state,
     *                      False to force-remove the loading state
     */
    toggleLoadingOnFlag: function (flagItem, force) {
        flagItem.toggleClass('rn_Loading', force)
                .setAttribute('aria-busy', flagItem.hasClass('rn_Loading'));
    },

    /**
     * Triggers when the flag button is clicked and there's more
     * than one flag option available.
     */
    toggleDropdown: function () {
        this.dropdown || (this.dropdown = this.createDropdown(this.renderDropdown()));

        if (this.dropdown.get('visible')) {
            this.dropdown.hide();
        }
        else {
            this.dropdown.show().get('contentBox').one('a').focus();
        }
    },

    /**
     * Creates a new panel instance.
     * @param  {Object} dropdown Y.Node to use as the source
     * @return {Object}          Y.Panel instance
     */
    createDropdown: function (dropdown) {
        return new this.Y.Panel({
            srcNode: dropdown,
            align: {
                node:   this.baseSelector,
                points: [ this.Y.WidgetPositionAlign.TC, this.Y.WidgetPositionAlign.BC ]
            },
            alignOn: [{ node: this.Y.one('win'), eventName: 'resize' }],
            visible: false,
            zIndex: 1,
            render: this.baseSelector,
            buttons: [],
            constrain: true,
            hideOn: [{
                eventName: 'clickoutside'
            }, {
                node: dropdown.all('a').slice(-1).item(0),
                eventName: 'keydown',
                keyCode: RightNow.UI.KeyMap.TAB
            }]
        });
    },

    /**
     * Renders the view template.
     * @return {Object} Y.Node
     */
    renderDropdown: function () {
        return this.Y.Node.create(new EJS({ text: this.getStatic().templates.view }).render({
            flags: this.data.js.flags
        }));
    }
});

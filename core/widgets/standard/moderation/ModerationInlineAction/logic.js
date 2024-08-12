RightNow.Widgets.ModerationInlineAction = RightNow.Widgets.extend({
    constructor: function() {
        this.Y.one(this.baseSelector).delegate('click', this._toggleDropdown, 'div.rn_ActionMenu', this);
        this.Y.one(this.baseSelector).delegate('click', this._onModeratorActionSubmit, 'a.rn_Action', this);
        RightNow.Event.subscribe('evt_inlineModerationAuthorStatusUpdate', this._authorStatusUpdateEventListener, this);
    },

    /**
     * Used to create and show/hide the action dropdown menu.
     * @param  {Object} e click event
     */
    _toggleDropdown: function (e){
        this._dropdown || (this._dropdown = this._createDropdown(this._renderDropdown()));

        if (this._dropdown.get('visible')) {
            this._dropdown.hide();
        }
        else {
            this._dropdown.show().get('contentBox').one('a').focus();
        }
    },

    /**
     * Creates a new panel instance.
     * @param  {Object} contentNode Y.Node to use as the source
     * @return {Object} Y.Panel instance
     */
    _createDropdown: function (contentNode) {
        return new this.Y.Panel({
            srcNode: contentNode,
            align: {
                node:   this.baseSelector,
                points: [ this.Y.WidgetPositionAlign.TR, this.Y.WidgetPositionAlign.BR ]
            },
            visible: false,
            zIndex: 1,
            render: this.baseSelector,
            buttons: [],
            hideOn: [{
                eventName: 'clickoutside'
            }, {
                node: contentNode.all('a').slice(-1).item(0),
                eventName: 'keydown',
                keyCode: RightNow.UI.KeyMap.TAB
            }]
        });
    },

    /**
     * Callback for moderator action response from the server.
     * @param  {Object} response
     */
    _onModeratorActionSubmitSuccess: function (response) {
        var moderateButton = this.Y.one(this.baseSelector + '_Button');

        this._hideProgressIcon();
        if (response.updatedObject.ID) {
            if(!this.data.attrs.refresh_page_on_moderator_action) {
                RightNow.UI.displayBanner(response.updatedObject.successMessage, { focusElement: moderateButton });
            }
            //general event fired to notify content/user status changes
            this._fireStatusUpdateEvent(response);
            //redirect if delete action was taken on user
            if (this._isUserDeleteAction(response.updatedObject.statusID)) {
                RightNow.Url.navigate(this.data.attrs.deleted_user_redirect_url);
            }
            else {
                if (response.updatedObject.objectType === 'CommunityUser' && this.data.attrs.object_type !== 'CommunityUser') {
                    //internal event fired to notify other instances of the same widget for common author status change
                    this._fireAuthorStatusUpdateEvent(response);
                }
                this.data.js.userActions = response.updatedUserActions;
                this.data.js.contentActions = response.updatedContentActions;
                if (this.data.attrs.refresh_page_on_moderator_action) {
                    RightNow.Url.navigate(window.location.href);
                }
            }
            this._dropdown = null;
        }
        else {
            RightNow.UI.displayBanner(response.error || this.data.attrs.label_on_failure_banner, {
                focusElement: moderateButton,
                type: 'ERROR'
            });
       }
    },

    /**
     * Checks if the moderator action is user deletion or not
     * @param {String} statusID Status to which the user is updated
     * @return {Boolean} True if moderation action is user deletion, false otherwise
     */
    _isUserDeleteAction: function (statusID) {
        return this.data.js.userDeleteStatuses && typeof this.data.js.userDeleteStatuses[parseInt(statusID, 10)] !== 'undefined';
    },

    /**
     * Fire an action event, which will be listened by other widgets to reflect the social content/user status change.
     * @param {Object} response
     */
    _fireStatusUpdateEvent: function(response) {
        var eventObject = new RightNow.Event.EventObject(this, {data: {
            w_id: this.data.info.w_id,
            object_data: {updatedObject: response.updatedObject}
        }});
        RightNow.Event.fire("evt_inlineModerationStatusUpdate", eventObject);
    },

    /**
     * Fire an action event when author status is changed, this event will be listened by same widget instances to show new moderator actions for author.
     * @param {Object} response
     */
    _fireAuthorStatusUpdateEvent: function(response) {
        var eventObject = new RightNow.Event.EventObject(this, {data: {
            w_id: this.data.info.w_id,
            object_data: response
        }});
        RightNow.Event.fire("evt_inlineModerationAuthorStatusUpdate", eventObject);
    },

    /**
     * Listener to 'evt_inlineModerationAuthorStatusUpdate' event by same widget. If moderator acts on same author via another instance of the widget
     * on same page then it updates the author actions for this instance.
     * @param {Object} response
     */
    _authorStatusUpdateEventListener: function(e, eventData) {
        if (this.data.js.authorID === eventData[0].data.object_data.updatedObject.ID) {
            this.data.js.userActions = eventData[0].data.object_data.updatedUserActions;
        }
    },

    /**
     * Show in-progress icon
     */
    _showProgressIcon: function (){
        RightNow.UI.show(this.baseSelector + '_LoadingIcon');
    },

    /**
     * Hide in-progress icon
     */
    _hideProgressIcon: function (){
        RightNow.UI.hide(this.baseSelector + '_LoadingIcon');
    },

    /**
     * Triggers when a moderator menu action item is clicked.
     * @param  {Object} e click event
     */
    _onModeratorActionSubmit: function (e) {
        this._toggleDropdown(e);
        //show confirm delete dialog for user deletion
        if (this._isUserDeleteAction(e.currentTarget.getAttribute('data-action-id'))) {
            this._deleteUserConfirm(e);
            return;
        }
        this._submitRequest(e);
    },

    /**
     * Submits ajax request for moderator action
     * @param  {Object} e click event
     */
    _submitRequest: function (e) {
        var actionID = e.currentTarget.getAttribute('data-action-id');
        var objectType = e.currentTarget.getAttribute('data-object-type');
        var eo = new RightNow.Event.EventObject(this, {
            data: {
                actionID: actionID,
                objectType: objectType,
                w_id: this.data.info.w_id
            }
        });
        
        if(this.data.js.f_tok){
            RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
            // Get a new f_tok value on each ajax request
            RightNow.Event.fire("evt_formTokenRequest", new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

            eo.data.f_tok = this.data.js.f_tok;
        }
        
        this._showProgressIcon();
        RightNow.Ajax.makeRequest(this.data.attrs.submit_moderator_action_ajax, eo.data, {
            data:           eo,
            json:           true,
            scope:          this,
            successHandler: this._onModeratorActionSubmitSuccess
        });
    },

    /**
     * Renders the view template.
     * @return {Object} Y.Node
     */
    _renderDropdown: function () {
        return this.Y.Node.create(this._getDropdownContent());
    },

    /**
     * Displays 'Confirm Delete' dialog box on user delete action.
     * @param {Object} e Event
     */
    _deleteUserConfirm: function(e) {
        var confirmElement = this.Y.Node.create('<p>')
            .addClass('rn_UserDeleteDialog')
            .set('innerHTML', this.data.attrs.label_user_delete_confirm);

        this._deleteDialog = RightNow.UI.Dialog.actionDialog(this.data.attrs.label_user_delete_confirm_title, confirmElement, {buttons: [
            { text: RightNow.Interface.getMessage('YES_LBL'), handler: {fn: function(){
                this._deleteDialog.hide();
                this._submitRequest(e);
            }, scope: this}, isDefault: true},
            { text: RightNow.Interface.getMessage('NO_LBL'), handler: {fn: function(){
                this._deleteDialog.hide();
            }, scope: this}, isDefault: false}
        ]});

        this._deleteDialog.show();
    },

    /**
     * Gets the html of the action dropdown
     * @return String The html for the action dropdown
     */
    _getDropdownContent: function () {
        return new EJS({ text: this.getStatic().templates.view }).render({
            contentActions: this.data.js.contentActions,
            socialContentObjectType: this.data.attrs.object_type,
            userActions: this.data.js.userActions});
    }
});

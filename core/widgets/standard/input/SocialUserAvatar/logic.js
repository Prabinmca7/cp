RightNow.Widgets.SocialUserAvatar = RightNow.Field.extend({
    overrides: {
        constructor: function() {
            this.parent();

            this.element = this.Y.one(this.baseSelector);
            this._chooseButton = this.Y.one(this.baseSelector + "_ChooseAvatar");
            if(this._chooseButton) {
                this._chooseButton.on("click", this._onButtonClick, this);
            }
            this._openLoginButton = this.element.one(".rn_OpenLogin .rn_LoginProvider");
            this.Y.one(this.baseSelector).delegate('click', this._onCloseClick, '.rn_CloseGallery', this);
            this.Y.one(this.baseSelector).delegate('keyup', this._updateImageForLibrary, '.rn_ProfilePictures .rn_UserAvatar img', this);
            this.Y.one(this.baseSelector).delegate('click', this._updateImageForLibrary, '.rn_ProfilePictures .rn_UserAvatar img', this);
            this.Y.one(this.baseSelector).delegate('click', this._onPaginatorClick, '.rn_ImagePaginator a', this);
            this.Y.one(this.baseSelector).delegate('click', this._onTabClick, '.rn_RoleSetTabs a', this);
            this.Y.one(this.baseSelector).delegate('keypress', this._onKeyPress, '.rn_ProfilePictures .rn_UserAvatar img', this);
            this.Y.one(this.baseSelector).delegate('keydown', this._onTabKeyDown, '.rn_ProfilePictures .rn_UserAvatar img', this);
            this.Y.one(this.baseSelector).delegate('keydown', this._onTabKeyDown, '.rn_ImagePaginator a', this);
            this.Y.one(this.baseSelector).delegate('keydown', this._onTabKeyDown, '.rn_ImageDisplay .rn_CloseGallery', this);
            this.Y.one(this.baseSelector).delegate('keydown', this._onTabKeyDown, '.rn_AvatarButtons .rn_SaveButton', this);
            this.submitButton = this.Y.one(this.baseSelector).one(".rn_SaveButton");
            this.Y.one(this.baseSelector).delegate('click', this._saveProfilePicture, '.rn_SaveButton', this, true);
            this.cancelButton = this.Y.one(this.baseSelector).one(".rn_CancelButton");
            this.Y.one(this.baseSelector).delegate('click', this._cancelProfilePicture, '.rn_CancelButton', this);

            this.avatarSelectionType = this.data.js.selectedServiceName || '';
            this.currentAvatarType = '';
            if (this.data.js.socialUser) {
                this._addClickHandlers();
                this.img = this.element.one('.rn_PreviewImage img');
                this.defaultImg = this.element.one('.rn_PreviewImage .rn_Default');
                this._monitorPreviewImage(this.img);
            }
            else {
                if (this.data.attrs.create_user_on_load) {
                    RightNow.Event.subscribe("evt_WidgetInstantiationComplete", function() {
                        RightNow.Event.fire("evt_userInfoRequired");
                    });
                }
                this.element.one('.rn_AddSocialUser').on('click', this._addSocialUser, this);
            }
        },

        getValue: function() {
            return this.img.hasClass('rn_Hidden') ? '' : this._getPreviewImage();
        }
    },

    /**
     * Event handler executed when SAVE button is clicked.
     * @param  {Object} e click event
     * @param boolean checkArchived Event argument
     */
    _saveProfilePicture: function(event, checkArchived) {
        var value, validationURL;
        var hostname = window.location.protocol + "//" + window.location.hostname;
        this.toggleSubmitButton(true);
        switch(this.avatarSelectionType) {
            case "gravatar":
                value = this.img.getAttribute('src');
                validationURL = this.data.js.gravatar.url;
                break;
            case "avatar_library":
                value = hostname + this.img.getAttribute('src');
                validationURL = hostname + this.data.attrs.avatar_library_image_location_display + this.selectedAvatar;
                break;
            case "facebook":
                value = this.img.getAttribute('src');
                break;
            case "default":
                value = validationURL = null;
                break;
            case "":
                value = (this.img.getAttribute('src') === "#") ? null : this.img.getAttribute('src');
                break;    
        }
        var eo = new RightNow.Event.EventObject(this, {
            data: {
            'socialUser': this.data.js.socialUser,
            'value': value,
            'w_id': this.data.info.w_id,
            'avatarSelectionType': this.avatarSelectionType,
            'currentAvatar': this.data.js.currentAvatar,
            'validationUrl': validationURL,
            'checkArchived' : checkArchived
            }
        });
        
        if(this.data.js.f_tok) {
            RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
            // Get a new f_tok value on each request
            RightNow.Event.fire("evt_formTokenRequest", new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

            eo.data.f_tok = this.data.js.f_tok;
        }
        
        RightNow.Ajax.makeRequest(this.data.attrs.save_profile_picture_ajax, eo.data, {
            data:           eo,
            json:           true,
            scope:          this,
            successHandler: this._onSaveSuccess
        });
    },

    /**
     * Event handler executed when cancel button is clicked.
     * @param  {Object} event click event
     */
    _cancelProfilePicture: function(event) {
        if (!this.data.js.ftokenPresent) {
            this._onCancel();
            return;
        }

        var eo = new RightNow.Event.EventObject(this, {
            data: {
                'w_id': this.data.info.w_id
            }
        });
        
        if(this.data.js.f_tok) {
            RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
            // Get a new f_tok value on each request
            RightNow.Event.fire("evt_formTokenRequest", new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

            eo.data.f_tok = this.data.js.f_tok;
        }
        
        RightNow.Ajax.makeRequest(this.data.attrs.cancel_profile_picture_ajax, eo.data, {
            data:           eo,
            json:           true,
            scope:          this,
            successHandler: this._onCancel
        });
    },

    /**
     * Redirects on successful AJAX response
     * @param response Event response
     */
    _onSaveSuccess: function(response) {
        if(response.archivedAvatar) {
            this._displayArchivedAvatarDialog();
            this.toggleSubmitButton(false);
        }
        else if(response.success && !response.errorMessage) {
            var success_url = this.data.js.editingOwnAvatar ? this.data.attrs.success_url_for_own_avatar : (this.data.attrs.success_url_for_another_user_avatar + this.data.js.socialUser);
            RightNow.Url.navigate(success_url, true);
        }
        else {
            var error_span = document.getElementById("rn_ErrorLocation");
            error_span.style.display = 'block';
            var error_message = document.createElement("span");
            error_span.className = "rn_MessageBox rn_ErrorMessage";
            error_message.className = "ErrorSpan";
            error_message.innerHTML = response.errorMessage ? response.errorMessage : RightNow.Interface.getMessage("ERROR_REQUEST_ACTION_COMPLETED_MSG");
            error_span.appendChild(error_message);
            this.toggleSubmitButton(false);
        }
    },

    /**
     * Redirects on cancel button
     */
    _onCancel: function() {
        RightNow.Url.navigate(this.data.js.previousPage, true);
    },

    /**
     * Event handler executed when ENTER key or SPACE key is pressed.
     * @param  {Object} event keyPress event
     */
    _onKeyPress: function(event) {
        var keyPressed = event.keyCode;
        if((keyPressed === RightNow.UI.KeyMap.ENTER) || (keyPressed === RightNow.UI.KeyMap.SPACE)) {
            this._saveProfilePicture(null, true);
        }
    },

    /**
     * Event handler executed when TAB key is pressed and focus is on any one of last avatar image
     * of current page, last pagination link, close gallery buttton or save changes button.
     * If focus is on the last avater image (or last pagination link in case of multiple pages),
     * focus goes to the close gallery button on pressing tab key followed by save changes button.
     * @param  {Object} event keyDown event
     */
    _onTabKeyDown: function(event) {
        var keyPressed = event.keyCode;
        if(keyPressed === RightNow.UI.KeyMap.TAB) {
            var activeElement = this.Y.one(document.activeElement),
                closeGalleryButton = this.Y.one(this.baseSelector + " .rn_ImageDisplay .rn_CloseGallery"),
                saveChangesButton = this.Y.one(this.baseSelector + " .rn_AvatarButtons .rn_SaveButton"),
                avatarsInCurrentPage = this.Y.one(this.baseSelector + " .rn_ImageDisplay .rn_ProfilePictures").get('children'),
                lastAvatarImageInCurrentPage = avatarsInCurrentPage.item(avatarsInCurrentPage.size() - 1).get('children').item(0),
                lastElementOfGallery = lastAvatarImageInCurrentPage,
                paginationDiv = this.Y.one(this.baseSelector + " .rn_ImageDisplay .rn_ImagePaginator");

            if(paginationDiv !== null) {
                var nextPageLink = paginationDiv.get('children').item(2),
                    paginationLinks = paginationDiv.get('children').item(1).get('children'),
                    lastPageLink = paginationLinks.item(paginationLinks.size() - 1);
                lastElementOfGallery = nextPageLink._isHidden() ? lastPageLink : nextPageLink;
            }

            if((!event.shiftKey && activeElement === lastElementOfGallery) || (event.shiftKey && activeElement === saveChangesButton)) {
                closeGalleryButton.focus();
                event.preventDefault();
                return;
            }

            if(activeElement === closeGalleryButton) {
                if(event.shiftKey) {
                    lastElementOfGallery.focus();
                }
                else {
                    saveChangesButton.focus();
                }
                event.preventDefault();
            }
        }
    },

    /*
     * Creates and displays a dialog to the user asking whether they would like
     * to resume existing session
     */
    _displayArchivedAvatarDialog: function() {
        //set up buttons and event handlers
        var buttons = [ { text: this.data.attrs.label_yes_button, handler: {fn: this._resumeSubmit, scope: this}, isDefault: true },
                        { text: this.data.attrs.label_no_button, handler: {fn: this._cancelSubmit, scope: this}, isDefault: false } ];
        var dialogBody = this.Y.Node.create(new EJS({text: this.getStatic().templates.dialogContent}).render({
            currentAvatar: this.data.js.currentAvatar,
            selectedAvatar: (this.avatarSelectionType === 'default') ? null : this.img.getAttribute('src'),
            attrs: this.data.attrs,
            defaultAvatar: this.data.js.defaultAvatar
        }));
        this._archivedAvatarDialog = RightNow.UI.Dialog.actionDialog('', dialogBody, {"buttons": buttons, "width": "600px"});
        RightNow.UI.Dialog.addDialogEnterKeyListener(this._archivedAvatarDialog, this._resumeSubmit, this);
        this._archivedAvatarDialog.show();
        this.Y.one('#' + this._archivedAvatarDialog.id).addClass('rn_SocialUserAvatarDialog');
    },

    /**
     * Event handler executed when Yes button is clicked.
     */
    _resumeSubmit: function() {
        this._archivedAvatarDialog.hide();
        this._saveProfilePicture(null, false);
    },

    /**
     * Event handler executed when No button is clicked.
     */
    _cancelSubmit: function() {
        this._archivedAvatarDialog.hide();
        return false;
    },


    /**
     * Event handler executed when close button is clicked.
     */
    _onCloseClick: function(e) {
        this.Y.all(this.baseSelector + " .rn_AvatarLibraryForm").addClass('rn_Hidden');
        this.Y.all(this.baseSelector + " .rn_AvatarLibraryTabs").addClass('rn_Hidden');
    },

    /**
     * Event handler executed when avatar library tab is clicked.
     */
    _onTabClick: function(e) {
        this.Y.all(this.baseSelector + " .rn_AvatarLibraryForm").setContent(this.Y.Node.create(new EJS({text: this.getStatic().templates.loadingIcon}).render()));
        this.Y.one(this.baseSelector + " .rn_RoleSetTabs").get('children').each(function(child) {
            child.removeClass('rn_SelectedTab');
        }, this);
        var eo = new RightNow.Event.EventObject(this, {
            data: {
                w_id: this.data.info.w_id,
                folder: e.target.getAttribute('data-rel'),
                focusClickToChooseText: true
            }
        });
        
        if(this.data.js.f_tok) {
            RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
            // Get a new f_tok value on each request
            RightNow.Event.fire("evt_formTokenRequest", new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

            eo.data.f_tok = this.data.js.f_tok;
        }
        
        RightNow.Ajax.makeRequest(this.data.attrs.submit_avatar_library_action_ajax, eo.data, {
            data:           eo,
            json:           true,
            scope:          this,
            successHandler: this._onSubmitSuccess
        });
    },

    /**
     * Event handler executed when paginator is clicked.
     */
    _onPaginatorClick: function(e) {
        var clickedElement, firstPageNumber, lastPageNumber, firstChildVal, lastChildVal;
        var firstChild = this.Y.one(this.baseSelector + " .rn_ImagePaginator .rn_CurrentPages").one('a:first-child');
        var lastChild = this.Y.one(this.baseSelector + " .rn_ImagePaginator .rn_CurrentPages").one('a:last-child');
        firstChildVal = parseInt(firstChild.getAttribute('data-rel'), 10);
        lastChildVal = parseInt(lastChild.getAttribute('data-rel'), 10);

        if(e.target.hasClass('rn_PreviousPage')){
            clickedElement = firstChildVal - 1;
            firstPageNumber = firstChildVal - 1;
            lastPageNumber = lastChildVal - 1;
        }
        else if (e.currentTarget.hasClass('rn_NextPage')){
            clickedElement = parseInt(lastChild.getAttribute('data-rel'), 10) + 1;
            firstPageNumber = firstChildVal + 1;
            lastPageNumber = lastChildVal + 1;
        }
        else{
            clickedElement = parseInt(e.currentTarget.getAttribute('data-rel'), 10);
            firstPageNumber = firstChildVal;
            lastPageNumber = lastChildVal;
        }

        this.Y.all(this.baseSelector + " .rn_AvatarLibraryForm").setContent(this.Y.Node.create(new EJS({text: this.getStatic().templates.avatarImages}).render({
            files: this.imageFiles,
            numberOfPages: this.numberOfPages,
            firstPage: firstPageNumber,
            lastPage: lastPageNumber,
            currentPage: clickedElement,
            js: this.data.js,
            attrs: this.data.attrs
        })));

        this.Y.one(this.baseSelector + " .rn_ImagePaginator .rn_CurrentPages").get('children').each(function(child) {
            if(parseInt(child.getAttribute('data-rel'), 10) === clickedElement){
                 child.addClass('rn_Selected');
            }
        }, this);

        if(this.selectedAvatar) {
            this.Y.one(this.baseSelector + " .rn_ProfilePictures").get('children').some(function(child) {
                if(this.selectedAvatar === child.get('children').item(0).getAttribute('data-name')){
                    child.get('children').item(0).addClass('rn_Clicked');
                    return true;
                }
            }, this);
        }

        if(this.Y.one(this.baseSelector + " .rn_ProfilePictures").get('children').size() > 0){
            this.Y.one(this.baseSelector + " .rn_ProfilePictures").get('children').item(0).one('img:first-child').focus();
        }
    },

    /**
     * Submits ajax request for avatar library
     * @param  {Object} e click event
     */
    _onButtonClick: function (e) {
        this.Y.all(this.baseSelector + " .rn_AvatarLibraryForm").removeClass('rn_Hidden');
        this.Y.all(this.baseSelector + " .rn_AvatarLibraryForm").setContent(this.Y.Node.create(new EJS({text: this.getStatic().templates.loadingIcon}).render()));

        var eo = new RightNow.Event.EventObject(this, {
            data: {
                w_id: this.data.info.w_id,
                folder: this.selectedTab || this.data.js.defaultTab,
                focusClickToChooseText: false
            }
        });
        
        if(this.data.js.f_tok) {
            RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
            // Get a new f_tok value on each request
            RightNow.Event.fire("evt_formTokenRequest", new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

            eo.data.f_tok = this.data.js.f_tok;
        }        
        
        RightNow.Ajax.makeRequest(this.data.attrs.submit_avatar_library_action_ajax, eo.data, {
            data:           eo,
            json:           true,
            scope:          this,
            successHandler: this._onSubmitSuccess
        });
    },

    /**
     * Displays the images based on the tab clicked.
     * @param response Event response
     * @param  {Object} e event
     */
    _onSubmitSuccess: function(response, event) {
        var bannerOptions = {},
            message;
        if(response && response.files && !response.errors) {
            this.imageFiles = response.files;
            this.numberOfPages = response.numberOfPages;
            this.tabNames = this.data.js.rolesetsFolderMap;
            if(Object.keys(this.tabNames).length > 1){
                this.Y.all(this.baseSelector + " .rn_AvatarLibraryTabs").setContent(this.Y.Node.create(new EJS({text: this.getStatic().templates.rolesetTabs}).render({
                    tabs: this.tabNames
                })));
                this.Y.all(this.baseSelector + " .rn_AvatarLibraryTabs").removeClass('rn_Hidden');
                this.Y.one(this.baseSelector + " .rn_RoleSetTabs").get('children').each(function(child) {
                    if(child.getAttribute('data-rel') === event.data.folder){
                        child.addClass('rn_SelectedTab');
                        child.focus();
                    }
                }, this);
            }

            this.Y.all(this.baseSelector + " .rn_AvatarLibraryForm").removeClass('rn_Hidden');

            if(Object.keys(this.imageFiles).length > 0){
                this.displayImagesWithPagination(event);
            }
            else {
                this.Y.all(this.baseSelector + " .rn_AvatarLibraryForm").setContent(this.Y.Node.create(new EJS({text: this.getStatic().templates.noImages}).render({
                    attrs: this.data.attrs
                })));
            }
        }
        else {
            message = RightNow.Interface.getMessage("ERROR_REQUEST_ACTION_COMPLETED_MSG");
            bannerOptions.type = 'ERROR';
            RightNow.UI.displayBanner(message, bannerOptions);
        }
    },


    /**
     * Displays the images along with pagination.
     * @param  {Object} e event
     */
    displayImagesWithPagination: function(event) {
        var currentPage = this.selectedCurrentPage || 1;
        var selectedPage = (this.selectedTab && event.data.folder !== this.selectedTab) ? 1 : currentPage;
        this.Y.all(this.baseSelector + " .rn_AvatarLibraryForm").setContent(this.Y.Node.create(new EJS({text: this.getStatic().templates.avatarImages}).render({
            files: this.imageFiles,
            numberOfPages: this.numberOfPages,
            firstPage: (event.data.folder === this.selectedTab || (!this.selectedTab && this.selectedFirstPage)) ? this.selectedFirstPage : 1,
            lastPage: (event.data.folder === this.selectedTab || (!this.selectedTab && this.selectedLastPage)) ? this.selectedLastPage : ((this.numberOfPages <= 5) ? this.numberOfPages : 5),
            currentPage: selectedPage,
            js: this.data.js,
            attrs: this.data.attrs
        })));

        if(this.numberOfPages > 1) {
            this.paginatorElement = this.Y.one(this.baseSelector + " .rn_ImagePaginator .rn_CurrentPages");
            if(event.data.folder === this.selectedTab || (!this.selectedTab && this.selectedFirstPage)){
                this.paginatorElement.get('children').each(function(child) {
                    if(selectedPage === parseInt(child.getAttribute('data-rel'), 10)){
                         child.addClass('rn_Selected');
                    }
                }, this);
            }
            else
            {
                this.paginatorElement.one('a:first-child').addClass('rn_Selected');
            }
        }
        
        if((event.data.focusClickToChooseText || Object.keys(this.tabNames).length === 1) && this.Y.one(this.baseSelector + " .rn_ProfilePictures").get('children').size() > 0){
            this.Y.one(this.baseSelector + " .rn_ClickToChooseText").focus();
        }
             
        if(this.selectedAvatar) {
            this.Y.one(this.baseSelector + " .rn_ProfilePictures").get('children').some(function(child) {
                if(this.selectedAvatar === child.get('children').item(0).getAttribute('data-name')){
                    var selectedImage = child.get('children').item(0);
                    selectedImage.addClass('rn_Clicked');
                    selectedImage.focus();
                    return true;
                }
            }, this);
        }
    },

    /**
     * Labels and class names for each status.
     * @param  {String=} status Status to retrieve; defaults
     *                          to returning all statuses
     * @return {Object}        class name and message of status
     */
    getStatus: function(status) {
        this._statuses || (this._statuses = {
            loading: {
                icon:      'rn_Loading',
                className: 'rn_LoadingStatus',
                message:   this.data.attrs.label_loading_icon_message
            },
            success: {
                icon:      'rn_CheckSquare',
                className: 'rn_Success',
                message:   this.data.attrs.label_success_icon_message
            },
            error: {
                icon:      'rn_ExclamationCircle',
                className: 'rn_Error',
                message:   this.data.attrs.label_error_icon_message
            }
        });

        return status ? this._statuses[status] : this._statuses;
    },

    /**
     * Toggles the disabled attribute on the submit form button, if available
     * @param  {boolean} state State to toggle the button's disabled attribute to
     */
    toggleSubmitButton: function(state) {
        if(!this.submitButton) return;

        this.submitButton.set('disabled', state);
    },

    /**
     * Add click handlers for avatar services
     */
    _addClickHandlers: function() {
        var services = {
            '.rn_DefaultOption': this._updateImageForDefault,
            '.rn_Service':      this._updateImageForService
        }, that = this;

        this.Y.Object.each(services, function(fn, id) {
            this.element.all(id).each(function(node) {
                node.delegate('click', fn, 'button', that);
            });
        }, this);
    },

    /**
     * Handler for adding a social user
     */
    _addSocialUser: function(e) {
        RightNow.Event.fire("evt_userInfoRequired");
        e.halt();
    },

    /**
     * Change handler for default
     * @param  {Object} e Change event
     */
    _updateImageForDefault: function(e) {
        this.avatarSelectionType = 'default';
        this._closeAndClearSelectedAvatar();
        this._showPreviewImage(false);
        this._displayStatusForInput('loading', e.target);
        this._removeAllStatusIcons('rn_Success', 'rn_Error');
        this._replaceLoadingWithStatus('success');
        this._removeCurrent();
    },

    /**
     * Change handler for avatar library
     * @param  {Object} e Change event
     */
    _updateImageForLibrary: function(e) {
        this.currentAvatarType = 'avatar_library';
        this.Y.all(this.baseSelector + " .rn_AvatarOptions .rn_Clicked").removeClass('rn_Clicked');
        e.target.addClass('rn_Clicked');
        this.selectedAvatar = e.target.getAttribute('data-name');
        if(Object.keys(this.tabNames).length > 1){
            this.Y.one(this.baseSelector + " .rn_RoleSetTabs").get('children').some(function(child) {
                if(child.hasClass('rn_SelectedTab')){
                    this.selectedTab = child.getAttribute('data-rel');
                    return true;
                }
            }, this);
        }

        if(this.numberOfPages > 1){
            var paginator = this.Y.one(this.baseSelector + " .rn_ImagePaginator .rn_CurrentPages");
            this.selectedFirstPage = parseInt(paginator.one('a:first-child').getAttribute('data-rel'), 10);
            this.selectedLastPage = parseInt(paginator.one('a:last-child').getAttribute('data-rel'), 10);
            paginator.get('children').each(function(child) {
                if(child.hasClass('rn_Selected')){
                     this.selectedCurrentPage = parseInt(child.getAttribute('data-rel'), 10);
                }
            }, this);
        }
        this.toggleSubmitButton(true);
        this.toggleAllButtons(true);
        this._displayStatusForInput('loading', this._chooseButton);
        this._refreshPreviewImage(this.data.attrs.avatar_library_image_location_display + e.target.getAttribute('data-name'));
    },

    /**
     * Change handler for gravatar
     * @param  {Object} e Change event
     */
    _updateImageForService: function(e) {
        this._closeAndClearSelectedAvatar();
        var serviceName = e.currentTarget.getAttribute('data-service-name');
        this.currentAvatarType = serviceName;
        this.toggleAllButtons(true);
        this._displayStatusForInput('loading', e.currentTarget);
        this._refreshPreviewImage(this._getImageUrlForService(serviceName, this.data.js.email.hash));
    },

    /**
     * Closes the avatar gallery and clears the selected image
     */
    _closeAndClearSelectedAvatar: function() {
        this.Y.all(this.baseSelector + " .rn_AvatarLibraryTabs").addClass('rn_Hidden');
        this.Y.all(this.baseSelector + " .rn_AvatarLibraryForm").addClass('rn_Hidden');
        this.selectedAvatar = this.selectedTab = this.selectedCurrentPage = this.selectedFirstPage = this.selectedLastPage = null;
    },

    /**
     * Click handler for preview buttons.
     * @param  {Object} e Click event
     */
    _previewImageForSocialService: function(e) {
        this._updateImageForInput(e.target.ancestor().one('input'), true);
    },

    /**
     * Updates the preview image for the given input's value and data-service values.
     * @param  {Object} input               Y.Node input element
     * @param  {Boolean=} displayStatusIcon Whether to display a loading icon
     *                                      for the transaction
     */
    _updateImageForInput: function(input, displayStatusIcon) {
        var username = this.Y.Lang.trim(input.get('value')),
            service = input.getAttribute('data-service');

        if (!username) {
            return this._displayStatusForInput('error', input);
        }

        this.toggleSubmitButton(true);
        if (displayStatusIcon) {
            this._displayStatusForInput('loading', input);
        }

        this._refreshPreviewImage(this._getImageUrlForService(service, username));
    },

    /**
     * Inserts a loading status icon after the
     * specified element. If the element already
     * has a status element, then the status element
     * is removed.
     * @param {String} status Status to display
     * @param  {Object} forElement Y.Node
     */
    _displayStatusForInput: function(status, forElement) {
        var currentIcon = forElement.next('.rn_StatusIcon');
        if (currentIcon) {
            currentIcon.remove();
        }
        forElement.insert(this._renderStatusView(status), 'after');
    },

    /**
     * Replaces all currently-loading status icons with
     * the designated status.
     * @param  {string} status Either error or success
     */
    _replaceLoadingWithStatus: function(status) {
        this.element.all('.rn_LoadingStatus').each(function(node){
            if (node.hasClass('rn_Permanent')) {
                node.replaceClass('rn_LoadingStatus', this.getStatus(status).className);
            }
            else {
                node.replace(this.Y.Node.create(this._renderStatusView(status)));
            }
        }, this);
    },

    /**
     * Replaces all icons with the specified status.
     * @param  {string} status error or success or loading
     */
    _replaceAllWithStatus: function(status) {
        this._removeAllStatusIcons('rn_Success', 'rn_Error');
        this._replaceLoadingWithStatus(status);
    },

    /**
     * Removes all status icons or all status icons designated
     * by the specified class names
     * @param {...String} classes Status icons with the classnames to remove.
     *                            If not specified, all status icons are removed.
     *                            If a status icon has a rn_Permanent class, then the
     *                            specified classes are removed.
     */
    _removeAllStatusIcons: function() {
        var classes = arguments.length ? Array.prototype.slice.call(arguments) : ['rn_StatusIcon'],
            YArray = this.Y.Array;

        this.element.all('.' + classes.join(',.')).each(function(node) {
            if (!node.hasClass('rn_Permanent')) {
                node.remove();
            }
            else {
                YArray.each(classes, node.removeClass, node);
            }
        });
    },

    /**
     * Removes the current avatar highlighting and show both social service inputs
     */
    _removeCurrent: function() {
        this.element.all('.rn_ChosenAvatar').each(function(node) {
            RightNow.UI.hide(node.one('.rn_CurrentSocialAvatar'));
            RightNow.UI.show(node.one('.rn_NewSocialInput'));
            node.removeClass('rn_ChosenAvatar');
        });
    },

    /**
     * Renders a status icon element for the given status.
     * @param  {String} forStatus loading, success, error
     * @return {String}           Rendered view
     */
    _renderStatusView: function(forStatus) {
        this._statusView || (this._statusView = new EJS({ text: this.getStatic().templates.statusIcon }));

        return this._statusView.render(this.getStatus(forStatus));
    },

    /**
     * Sets the preview image's `src` attribute to the given url.
     * @param  {String} url image src
     */
    _refreshPreviewImage: function(url) {
        var altData = '';

        if (url === this.img.getAttribute('src')) {
            // load/error callback is expected to be async. So wait a tick.
            return this.Y.Lang.later(100, this, this._onPreviewImageLoaded, [{ target: this.img }, true]);
        }

        this.img.setAttribute('src', url);
        if (url.indexOf('gravatar') > -1) {
            this.img.setAttribute('alt', this.data.js.socialUserDisplayName);
        }
        else {
            altData = url.split('/').pop().split('.')[0] || '';
            this.Y.Lang.later(2500, this, this._refreshPreviewAlt, [altData]);
        }
    },

    /**
     * Sets s.img.setAttribute('alt', altData);
     the preview image's `alt` attribute to the given name.
     * @param  {String} url image alt data
     */
    _refreshPreviewAlt: function(altData) {
        this.img.setAttribute('alt', altData);
    },

    /**
     * Callback for the image's load event.
     * @param  {Object} e load event
     * @param {Boolean} displaySuccess Whether to forcefully display the success status even if
     *                                 a new image isn't copied to the `data-fallback` attribute
     */
    _onPreviewImageLoaded: function(e, displaySuccess) {
        this.toggleAllButtons(false);
        if (this._copyElementAttribute(e.target, 'src', 'data-fallback') || displaySuccess) {
            this._replaceAllWithStatus('success');
            this._showPreviewImage(true);
            this._removeCurrent();
            this.avatarSelectionType = this.currentAvatarType;
        }
    },

    /**
     * Callback for the image's error event.
     * Sets the `src` attribute back to the last successfully-loaded
     * url set in the `data-fallback` attribute.
     * @param  {Object} e error event
     */
    _onPreviewImageError: function(e) {
        this.toggleAllButtons(false);
        this._copyElementAttribute(e.target, 'data-fallback', 'src');
        this._replaceLoadingWithStatus('error');

        if (!this.img.getAttribute('data-fallback')) {
            this._showPreviewImage(false);
        }
    },

    /**
     * Sets up event listeners on the image.
     * @param  {Object} img Y.Node image
     */
    _monitorPreviewImage: function(img) {
        this._copyElementAttribute(img, 'src', 'data-fallback');

        img.on('load', this._onPreviewImageLoaded, this);
        img.on('error',this._onPreviewImageError, this);
    },

    /**
     * Toggle the default and preview images
     * @param {Boolean} turnOn Whether to turn on or off the preview image
     */
    _showPreviewImage: function(turnOn) {
        if (turnOn) {
            RightNow.UI.show(this.img);
            RightNow.UI.hide(this.defaultImg);
        }
        else {
            RightNow.UI.hide(this.img);
            RightNow.UI.show(this.defaultImg);
        }
    },

    /**
     * Copies an attribute value from one attribute to another.
     * @param  {Object} el  Y.Node
     * @param  {String} from From attribute
     * @param  {String} to   To attribute
     * @return {Boolean} False if the values are already the same
     *                         and a copy didn't happen
     */
    _copyElementAttribute: function(el, from, to) {
        var fromValue = el.getAttribute(from),
            toValue = el.getAttribute(to);

        if (fromValue === toValue) return false;

        return !!el.setAttribute(to, fromValue);
    },

    /**
     * Returns the preview image's `src` attribute.
     * @return {String} src
     */
    _getPreviewImage: function() {
        // Does a fresh node query to avoid caching on `this.img`.
        return this.element.one('.rn_PreviewImage img').get('src');
    },

    /**
     * Retrieves the image url for the specified service.
     * @param  {String} service  One of the supported services
     * @param  {String} username The user-entered username
     * @return {String}          Url
     */
    _getImageUrlForService: function(service, username) {
        return this.data.js[service].url;
    },
    /**
     * Toggles the disabled attribute on the all button in the page
     * @param  {boolean} state State to toggle the buttons are disabled attribute to
     */
    toggleAllButtons : function(state) {
        this.Y.one(this.baseSelector).all('button').each(function(node) {
            node.set('disabled', state);
        }, this);
        if (this._openLoginButton) {
            state ? this._openLoginButton.addClass('rn_Disabled') : this._openLoginButton.removeClass('rn_Disabled');
        }
    }
});

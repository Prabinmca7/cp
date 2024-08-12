RightNow.Widgets.FileAttachmentUpload = RightNow.Field.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this.fileCounter = 1;
            this.input = this.Y.one(this.baseSelector + "_FileInput");

            if(!this.input) return;

            this._eo = new RightNow.Event.EventObject(this);
            this._attachmentCount = this.data.js.attachmentCount || 0;
            this._attachments = [];
            this._statusMessage = this.Y.one(this.baseSelector + "_StatusMessage");
            this._ErrorMessage = this.Y.one(this.baseSelector + "_ErrorMessage");
            this._parentFormElement = this.input.ancestor('form');

            if(this._parentFormElement) {
                this._origEncType = this._parentFormElement.get('enctype');
                this.input.on("change", this._onFileAdded, this);
                this.input.on("keypress", this._onKeyPress, this);
                this.input.on("paste", function(){return false;});
                RightNow.Form.find(this.input.get('id'), this.instanceID).on("submit", this._onValidateUpdate, this);
                this.on("constraintChange:min_required_attachments", this.updateMinAttachments, this);
            }
            else {
                RightNow.UI.addDevelopmentHeaderError("FileAttachmentUpload must be placed within a form with a unique ID.");
            }

            this.data.attrs.max_attachments = (this.data.attrs.max_attachments === 0) ? Number.MAX_VALUE : this.data.attrs.max_attachments;
            if(this._attachmentCount === this.data.attrs.max_attachments)
                this.input.set("disabled", true);
        },

        /**
         * Convenience method for dynamic forms.
         * @return {Array} List of file attachment details
         */
        getValue: function() {
            return this._attachments;
        }
    },

     /**
     * Used by Dynamic Forms to switch between a required and a non-required label
     * @param  {Object} container    The DOM node containing the label
     * @param  {Number} minAttachment The minimum number of attachments
     * @param  {String} label        The label text to be inserted
     * @param  {String} template     The template text
     */
    swapLabel: function(container, minAttachments, label, template) {
        this.Y.augment(this, RightNow.RequiredLabel);
        var templateObject = {
            label: label,
            instanceID: this.instanceID,
            fieldName: this._fieldName,
            minAttachments: minAttachments
        };

        container.setHTML('');
        container.append(new EJS({text: template}).render(templateObject));
    },

    /**
     * Triggered whenever a constraint is changed.
     * @param  {String} evt        The event name
     * @param  {Object} constraint The constraint being changed
     */
    updateMinAttachments: function(evt, constraint) {
        var minAttachments = constraint[0].constraint;
        if(minAttachments > this.data.attrs.max_attachments || minAttachments === this.data.attrs.min_required_attachments) return;

        //If the requiredness changed and the form has already validated, clear the messages and highlights
        this.toggleErrorIndicator(false);
        if(this.data.attrs.min_required_attachments > 0 && this.lastErrorLocation) {
            this.Y.one('#' + this.lastErrorLocation).all("[data-field='" + this._fieldName + "']").remove();
        }

        //Replace any old labels with new labels
        if(this.data.attrs.label_input) {
            this.swapLabel(this.Y.one(this.baseSelector + '_LabelContainer'), minAttachments, this.data.attrs.label_input, this.getStatic().templates.label);
        }

        this.data.attrs.min_required_attachments = minAttachments;
    },

    /**
    * Event handler for when user performs a keypress in the file input field.
    * Overrides older browser behavior that allows users to type input, causing upload errors.
    * Allows tabbing and enter keypress to continue through.
    * @param event Event keypress event
    */
    _onKeyPress: function(event)
    {
        var keyPressed = event.keyCode;
        //allow tabbing and enter keypress through
        if(keyPressed &&
            (keyPressed !== RightNow.UI.KeyMap.ENTER && keyPressed !== RightNow.UI.KeyMap.TAB)
                || (keyPressed === RightNow.UI.KeyMap.ENTER && this.Y.UA.ie))
        {
            //IE submits the form when the user hits enter while focused on the input
            //field/button. Manually invoking a click will eventually invoke a security
            //exception in IE for some reason. Therefore, just supress the key and do nothing
            event.halt();
        }
    },

    /**
     * Event handler for when value changes in file attachment input.
     * @param {Object} e Change event
     */
    _onFileAdded: function(e) {
        var value = e.target.get('value');

        if (this._uploading || value === "" || !this._validateFileExtension(value)) return;

        this._sendUploadRequest();
    },

    _validateFileExtension: function(fileName) {
        if(this.data.js.valid_file_extensions == '*')
            return true;
        this._validExtensions || (this._validExtensions = this.data.js.valid_file_extensions.toLowerCase().replace(' ', '').split(','));
        var index = fileName.lastIndexOf('.'),
            fileExtension = (index !== -1 && index !== (fileName.length - 1))
                ? fileName.substring(index + 1).toLowerCase()
                : null,
            extensionIsValid = fileExtension && this.Y.Array.indexOf(this._validExtensions, fileExtension) > -1;

        if (extensionIsValid) {
            this.toggleErrorIndicator(false);
            return true;
        }

        this.toggleErrorIndicator(true);
        this._setStatusMessage(RightNow.Text.sprintf(this.data.attrs.label_invalid_extension, '.' + this._validExtensions.join(", .")), false, true, "alert", null);

        return false;
    },

    /**
     * Sets the content and attributes of the Status Message span element
     * @param {String}  message           Message to set
     * @param {boolean} screenReaderClass True if rn_ScreenReaderOnly class is to be added, false otherwise
     * @param {boolean} errorClass        True if rn_ErrorMessage class is to be added, false otherwise
     * @param {String}  role              The value to be set for "role" attribute
     * @param {number}  tabIndex          The value to be set for "tabIndex" attribute
     */
    _setStatusMessage: function(message, screenReaderClass, errorClass, role, tabIndex) {
        this._statusMessage.set('innerHTML', message);

        if(screenReaderClass && !this._statusMessage.hasClass("rn_ScreenReaderOnly")) {
            this._statusMessage.addClass("rn_ScreenReaderOnly");
        }
        else if(!screenReaderClass && this._statusMessage.hasClass("rn_ScreenReaderOnly")) {
            this._statusMessage.removeClass("rn_ScreenReaderOnly");
        }

        if(errorClass && !this._statusMessage.hasClass("rn_ErrorMessage")) {
            this._statusMessage.addClass("rn_ErrorMessage");
        }
        else if(!errorClass && this._statusMessage.hasClass("rn_ErrorMessage")) {
            this._statusMessage.removeClass("rn_ErrorMessage");
        }

        role ? this._statusMessage.setAttribute("role", role) : this._statusMessage.removeAttribute("role");
        tabIndex ? this._statusMessage.setAttribute("tabIndex", tabIndex) : this._statusMessage.removeAttribute("tabIndex");
    },


    /**
     * Issue request for the file upload. Separated from _onFileAdded because it may be overridden in widgets that extend from this one.
     */
    _sendUploadRequest: function() {
        this._eo.data.filename = this.input.get('value');

        var postData = {
            name: this.data.js.id,
            path: this.data.js.path,
            count: this._attachmentCount,
            max_attach_hash: this.data.js.max_attach_hash,
            max_attachments: this.data.attrs.max_attachments,
            constraints: this.data.js.constraints
        };
        
        if(this.data.js.f_tok){
            RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
            // Get a new f_tok value on each uploads
            RightNow.Event.fire("evt_formTokenRequest",
                new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));
                
            if(postData)    
                postData.f_tok = this.data.js.f_tok;
        }

        // Save off reference to local file before the upload occurs.
        this._localFile = this._getFileFromInput();

        if (RightNow.Event.fire("evt_fileUploadRequest", this._eo)) {
            this._setLoading(true);

            //temporarily set the parent form's encode type for this request
            this._parentFormElement.set('enctype', 'multipart/form-data').set('encoding', 'multipart/form-data');

            this._errorNotified = false;
            this._fileUploadReq = RightNow.Ajax.makeRequest("/ci/fattach/upload", postData, {
                json: true,
                data: this._eo,
                scope: this,
                timeout: (RightNow.Interface.getConfig("CP_FILE_UPLOAD_MAX_TIME") || 300) * 1000,
                upload: this._parentFormElement.get('id'),
                successHandler: this._fileUploadReturn,
                failureHandler: this._fileUploadFailure
            });
            this.input.set("disabled", true);
            //reset parent form's encode type back to it's original
            this._parentFormElement.set('enctype', this._origEncType).set('encoding', this._origEncType);
        }
        else {
            this.resetInput();
        }
    },

    /**
     * Displays an error dialog if the response from the server
     * contains an error condition.
     * @param  {Object} response Response from server
     * @return {boolean}          True if an error occurred, False otherwise
     */
    _processServerError: function(response) {
        var attachmentErrorInfo = this.getAttachmentErrorInfo(response);
        if (attachmentErrorInfo && attachmentErrorInfo.errorMessage) {
            attachmentErrorInfo.focusElement = this.input;
            if(!this._errorNotified) {
                RightNow.UI.Dialog.messageDialog(attachmentErrorInfo.errorMessage, attachmentErrorInfo);
                this._errorNotified = true;
            }
            return true;
        }
        return false;
    },

    /**
     * Disables the input if no more attachments are allowed.
     * @param  {number} count Current number of attachments
     * @return {boolean} True if no more attachments are allowed False otherwise
     */
    _processAttachmentThreshold: function(count) {
        if(count >= this.data.attrs.max_attachments) {
            this.input.set("disabled", true);
            return count > this.data.attrs.max_attachments;
        }
        return false;
    },

    /**
     * Event handler for when server responds with file attachment information
     * @param type String Event name
     * @param response Object Event arguments
     */
    _fileUploadReturn: function(response, originalEventObject) {
        var originalFileName = originalEventObject.data.filename;

        this._setLoading(false);
        this.resetInput();

        if (RightNow.Event.fire("evt_fileUploadResponse", response, originalEventObject)) {
            var attachmentInfo = response;

            if (this._processServerError(attachmentInfo) || this._processAttachmentThreshold(++this._attachmentCount)) return;

            var filename = this._normalizeFilename(attachmentInfo.name, originalFileName);
            this._attachments.push({
                id:             this.fileCounter,
                userName:       filename,
                localName:      attachmentInfo.tmp_name,
                contentType:    attachmentInfo.type || 'application/octet-stream'
            });
            this.fire('change', this);

            this._renderNewAttachmentItem(filename, this._attachments.length);
        }
    },

    /**
     * Gets the File object from the input element
     * @return {Object|null} File object or null if there are none or the browser
     *                            doesn't support it
     */
    _getFileFromInput: function() {
        if (this.data.attrs.display_thumbnail && window.FileReader) {
            var files = this.Y.Node.getDOMNode(this.input).files,
                file;
            if (files && (file = files[0]) && RightNow.Text.beginsWith(file.type, 'image')) {
                return file;
            }
        }
    },

    /**
     * Displays the list item for the attached file.
     * @param  {string} filename Name of the file
     * @param  {number} count    Number of uploaded files
     */
    _renderNewAttachmentItem: function(filename, count) {
        var attachmentItem = this.Y.Node.create(new EJS({text: this.getStatic().templates.attachmentItem}).render({
            id:                 this.baseDomID + '_Item' + count,
            fileId:             this.fileCounter++,
            name:               filename,
            displayThumbnail:   !!this._localFile,
            attrs:              this.data.attrs,
            escapeHtml: this.Y.Escape.html
        }));

        if (this._localFile) {
            this._loadThumbnail(this._localFile, new FileReader(), function(img) {
                attachmentItem.one('.rn_Thumbnail').append(img);
            });
        }
        attachmentItem.one("a.rn_fileRemove").on("click", this.removeClick, this);

        if (!this._attachmentList) {
            this._attachmentList = this.Y.Node.create("<ul>");
            this._attachmentList.setAttribute("role", "alert");
            this._statusMessage.insert(this._attachmentList, "after");
        }
        this._attachmentList.append(attachmentItem);

        if (this.data.attrs.max_attachments === this._attachmentCount) {
            this._ErrorMessage.setHTML(new EJS({text: this.getStatic().templates.maxMessage}).render({
                maxMessage: this.data.attrs.label_max_attachment_limit
            }))
        }
    },

    /**
     * Handles filename masking and filename duplication.
     * @param  {string} filename         Name of the file to inspect from the server
     * @param  {string} originalFileName Name supplied from the input element
     * @return {string}                  Normalized filename
     */
    _normalizeFilename: function (filename, originalFileName) {
        if (filename.lastIndexOf('*') !== -1) {
            // '*' is not a valid filename character, so the file upload would have bombed out unless
            // F5 masking is happening, so let's get the original filename from the input element
            originalFileName = originalFileName.replace(/\\/g, '/');
            var lastIndex = originalFileName.lastIndexOf('/');
            if (lastIndex !== -1) {
                originalFileName = originalFileName.substr(lastIndex + 1);
            }
            filename = originalFileName;
        }

        filename = filename.replace("&amp;", "&");
        return this._renameDuplicateFilename(filename);
    },

    /**
     * Adds a counter to the filename before the file extension if
     * the filename already exists in the `_attachments` array.
     * @param  {string} filename filename to inspect
     * @return {string}          filename untouched if no duplicates are found
     *                                    otherwise renamed file
     */
    _renameDuplicateFilename: function (filename) {
        var duplicateFilename,
            renamedFilename = filename,
            duplicates = 0;

        /**@inner*/
        function filenameIsDuplicate (file) {
            return file.userName === renamedFilename;
        }

        /**@inner*/
        function addCounterToFilename (name, counter) {
            var lastDot = name.lastIndexOf('.');
            if (lastDot === -1) {
                return name + counter;
            }
            else {
                return name.substr(0, lastDot) + counter + name.substr(lastDot);
            }
        }

        do {
            duplicateFilename = this.Y.Array.find(this._attachments, filenameIsDuplicate);
            if (duplicateFilename) {
                renamedFilename = addCounterToFilename(filename, ++duplicates);
            }
        }
        while (duplicateFilename);

        return renamedFilename;
    },

    /**
     * Loads a thumbnail image into the list item span for image type files.
     * Assumes window.FileReader is available.
     * @param {Object} file The file object from the text input element.
     * @param {Object} FileReader Object
     * @param {Function} callback Callback supplied with the Image Object
     */
    _loadThumbnail: function(file, reader, callback) {
        var maxHeight = this.data.attrs.max_thumbnail_height,
            width, height;

        reader.onload = function() {
            var image = new Image();
            image.src = reader.result;
            image.onload = function() {
                width = image.width;
                height = image.height;
                if (width > height && width > maxHeight) {
                    height = Math.round(height * (maxHeight / width));
                    width = maxHeight;
                }
                else if (height > maxHeight) {
                    width = Math.round(width * (maxHeight / height));
                    height = maxHeight;
                }
                image.alt = file.name;
                image.width = width;
                image.height = height;
                callback(image);
            };
        };
        reader.readAsDataURL(file);
    },

    /**
     * Called when the ajax request fails
     * (e.g. timeout, 500, 404, etc. Not a deliberate error code being returned from the fattach controller).
     * Hides the loading indicator but doesn't reset the input control.
     * Displays a generic error message in a dialog.
     */
    _fileUploadFailure: function(response) {
        if(this._fileUploadReq === undefined) return;
        RightNow.Ajax.abortRequest(this._fileUploadReq, null);
        this._setLoading(false, '');
        this.resetInput();
    },

    /**
     * Retrieves error information (if any) from a file upload response
     * @param attachmentInfo Object Typically a response object from a file upload response
     */
    getAttachmentErrorInfo: function(attachmentInfo) {
        //Check for errors produced by php
        if(!attachmentInfo)
        {
            return {errorMessage: RightNow.Interface.getMessage("THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG"), icon: "WARN"};
        }
        //size error
        else if(attachmentInfo.error === 2)
        {
            return {errorMessage: this.data.attrs.label_generic_error, icon: "WARN"};
        }
        //upload error
        else if(attachmentInfo.error === 4 || attachmentInfo.error === 88)
        {
            return {errorMessage: RightNow.Interface.getMessage("FILE_PATH_FOUND_MSG"), icon: "WARN"};
        }
        //Empty file uploaded
        else if(attachmentInfo.error === 10)
        {
            return {errorMessage: RightNow.Interface.getMessage("FILE_ATT_UPLOAD_EMPTY_PLS_ENSURE_MSG")};
        }
        else if(attachmentInfo.error === 20)
        {
            return {errorMessage: RightNow.Interface.getMessage("FILE_UPLOAD_ALLOWED_FILE_MSG"), icon: "WARN"};
        }
        //File name too long
        else if(attachmentInfo.errorMessage)
        {
            return {errorMessage: attachmentInfo.errorMessage, icon: "WARN"};
        }
        else
        {
            return null;
        }
    },

    /**
     * Clears the value and enables the input.
     */
    resetInput: function() {
        this.input.set("value", "").set("disabled", false);
        this.recreateInput();
    },

    /**
     * Deals with specific browsers to recreate the input field
     */
    recreateInput: function() {
        //We'll deal with you yet, donkey browsers!
        // Chrome and Safari are both truthy on this.Y.UA.webkit, but
        // only Chrome is truthy on this.Y.UA.chrome
        if(this.Y.UA.ie || this.Y.UA.webkit) {
            //IE, Chrome, and Safari apparently refuse to fire the change event if you
            //select the same file, so we have to recreate the input field so
            //that it forgets what was previously uploaded
            var inputField = this.input.cloneNode(false);
            this.input.replace(inputField);
            this.input = this.Y.one("#" + inputField.get('id'));
            //IE9, Chrome, and Safari apparently changed so that we need to resubscribe,
            //presumably because now when cloneNode is called, all the events
            //have been unsubscribed
            if(this.Y.UA.ie > 8 || this.Y.UA.webkit)
                this.input.on("change", this._onFileAdded, this);
        }
    },

    /**
     * Event handler for when file attachment item is removed
     * @param event Object DOM click event
     */
    removeClick: function(event) {
        var fileId = this.Y.one('#' + event.target.get('id')).getAttribute ('data-fileid');
        var index = this.getFileIndex(fileId);
        this._attachments.splice(index, 1);
        event.target.get("parentNode").remove();
        if(this._statusMessage) {
            this._setStatusMessage(RightNow.Interface.getMessage("FILE_DELETED_LBL"), true, false, "alert", 0);
            RightNow.UI.updateVirtualBuffer();
            this._statusMessage.focus();
        }

        this._attachmentCount--;
        this.input.set("disabled", false);

        if(this._attachmentCount === this.data.attrs.max_attachments - 1 ) {
            this._ErrorMessage.setHTML("");
        }

        this.fire('change', this);
    },

    /**
     * Returns the file index of file to be removed
     * @param  {String} fileId
     * @return {int} index of file to be removed
     */
    getFileIndex: function(fileId) {
        var index = 0;
        while(index < this._attachments.length) {
            if(this._attachments[index]['id'] === parseInt(fileId, 10)) {
                break;
            }
            index++;
        }
        return index;
    },

    /**
     * Event handler when submitting form. File information for all attachments is sent
     * @param type String Event name
     * @param args Object Event arguments
     */
    _onValidateUpdate: function(type, args) {
        this.toggleErrorIndicator(false);
        var eventObject = this.createEventObject();
        if(this._uploading || this._attachmentCount < this.data.attrs.min_required_attachments) {
            var message = this._uploading ? this.data.attrs.label_still_uploading_error : RightNow.Text.sprintf(this.data.attrs.label_min_required, this.data.attrs.label_input, this.data.attrs.min_required_attachments);
            this.lastErrorLocation = args[0].data.error_location;
            this._displayError(message, this.lastErrorLocation);
            RightNow.Event.fire("evt_formFieldValidateFailure", this._eo);
            return false;
        }
        if(this._attachmentCount > 0) {
            eventObject.data.value = this._attachments;
        }
        RightNow.Event.fire("evt_formFieldValidatePass", eventObject);
        return eventObject;
    },

    /**
     * Shows / hides the error indicators by adding / removing
     * class names from the input and label.
     * @param  {boolean} showOrHide Show = true, Hide = false
     */
    toggleErrorIndicator: function(showOrHide) {
        var method = ((showOrHide) ? "addClass" : "removeClass");
        this.input[method]("rn_ErrorField");
        this.Y.one(this.baseSelector + "_Label")[method]("rn_ErrorLabel");
    },

    /**
     * Toggles the display of the loading indicators.
     * @param {Boolean} turnOn Whether to show or hide the loading indicators
     * @param {String=} statusMessage Status message (optional)
     */
    _setLoading: function(turnOn, statusMessage) {
        this._uploading = turnOn;
        this._loading || (this._loading = this.Y.one(this.baseSelector + "_LoadingIcon"));
        var useMessage = typeof statusMessage === 'string';

        if (turnOn) {
            RightNow.UI.show(this._loading);
            if(this._statusMessage) {
                this._setStatusMessage(useMessage ? statusMessage : RightNow.Interface.getMessage("UPLOADING_ELLIPSIS_MSG"), false, false, "alert", null);
            }
        }
        else {
            RightNow.UI.hide(this._loading);
            if(this._statusMessage) {
                this._setStatusMessage(useMessage ? statusMessage : RightNow.Interface.getMessage("FILE_UPLOAD_COMPLETE_LBL"), true, false, null, null);
                if (!useMessage || statusMessage !== '') {
                    // Don't notify screen readers if there's nothing to report.
                    this._statusMessage.setAttribute("tabIndex", 0);
                    RightNow.UI.updateVirtualBuffer();
                    this._statusMessage.focus();
                    this._statusMessage.setAttribute("role", "alert");
                }
            }
        }
    },

    /**
     * Displays error by appending message above submit button, and changing
     * the class name of the input field and label.
     * @param {String} errorMessage message to display
     * @param {String} errorLocation String id of the element to drop the error into
     */
    _displayError: function(errorMessage, errorLocation) {
        var commonErrorDiv = this.Y.one("#" + errorLocation);
        if(commonErrorDiv) {
            commonErrorDiv.append(new EJS({text: this.getStatic().templates.error}).render({
                errorLink: (this.data.attrs.label_error.indexOf("%s") > -1) ? RightNow.Text.sprintf(this.attrs.label_error, this.data.attrs.label_input) : errorMessage,
                id: this.input.get('id'),
                fieldName: this._fieldName
            }));
        }
        this.toggleErrorIndicator(true);
    }
});

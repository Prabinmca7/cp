RightNow.Widgets.SocialFileAttachmentUpload = RightNow.Widgets.FileAttachmentUpload.extend({ 
    overrides: {
        /**
         * Overrides RightNow.Widgets.FileAttachmentUpload#constructor.
         */
        constructor: function() {
            this.parent();
            this._removedAttachmentIDs = [];
            this._updateFileAttachments = {};
            RightNow.Event.on('evt_editRefreshFileAttachments', this._refreshFileAttachments, this);
            RightNow.Event.on('evt_newCommentRefresh', this._resetNewCommentWidget, this);
        },

        getValue: function() {
            this._updateFileAttachments.newFiles = this._attachments;
            this._updateFileAttachments.removedFiles = this._removedAttachmentIDs;
            return this._updateFileAttachments;
        },

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
            if(this._updateFileAttachments.newFiles.length > 0 || this._updateFileAttachments.removedFiles.length > 0) {
                eventObject.data.value = this._updateFileAttachments;
            }
            RightNow.Event.fire("evt_formFieldValidatePass", eventObject);
            return eventObject;
         }
    },
    
    /*
     * Reset the widget inside the new comment editor
     * @param {string} evt Event name
     * @param {array} origEventObj Original Event Object from the event fired
     */
    _resetNewCommentWidget: function(event, origEvent) {
        if(this.instanceID === origEvent[0].data.instanceID) {
            this._attachmentCount = 0;
            this._attachmentList = null;
            this._attachments = [];
            if(this.Y.one(this.baseSelector + ' ul')) {
                this.Y.one(this.baseSelector + ' ul').remove();
            }
        }
    },

    /*
     * Refreshes the existing file list in the edit mode
     * @param {string} evt Event name
     * @param {array} origEventObj Original Event Object from the event fired
     */
    _refreshFileAttachments: function(event, origEventObj) {
        if(this.instanceID === origEventObj[0].data.instanceID) {
            this._removedAttachmentIDs = [];
            if(origEventObj[0].data.fileAttachments) {
                var fileAttachments = JSON.parse(origEventObj[0].data.fileAttachments);
                //if no existing files, don't render anything
                if(fileAttachments.length > 0) {
                    this._existingData = new EJS({text: this.getStatic().templates.existingAttachments})
                        .render({
                            fileAttachments: fileAttachments,
                            attrs: this.data.attrs,
                            thumbnailAltText: RightNow.Interface.getMessage("THUMBNAIL_FOR_ATTACHED_IMAGE_MSG"),
                            escapeHtml: this.Y.Escape.html
                        });
                    var fileList = this.Y.Node.create(this._existingData);
                    //load thumbnails for image files if display_thumbnail is true
                    if (this.data.attrs.display_thumbnail) {
                        var i;
                        for(i = 0; i < fileAttachments.length; i++) {
                            if(RightNow.Text.beginsWith(fileAttachments[i].ContentType, 'image')) {
                                var image = new Image;
                                image.id = fileAttachments[i].ID;
                                image.onload = function() {
                                        fileList.one('#rn_RemoveExistingFile_' + this.id).one('.rn_Thumbnail').set('src', this.src);
                                }
                                image.src = fileAttachments[i].AttachmentUrl;
                            }
                        }
                    }
                    fileList.appendTo(this.Y.one(this.baseSelector));
                    this.Y.one(this.baseSelector).all(' .rn_ExistingFileRemove').on('click', this._onRemoveExistingFileClick, this);
                }
            }
            //update all attachment data
            this._attachmentCount = fileAttachments ? fileAttachments.length : 0;
            this._attachments = [];
            this._eo = new RightNow.Event.EventObject(this);
            if(this._attachmentCount === this.data.attrs.max_attachments) {
                this.input.set("disabled", true);
            }
            //remove temporarily added file nodes
            if(this._attachmentList) {
                this._attachmentList.remove();
                //nullify this node so that we're able to re-form it when new files are added to be uploaded
                this._attachmentList = null;
            }
        }
    },

    /*
     * Event listener for the 'remove existing file' action
     * @param {string} event Event details
     */
    _onRemoveExistingFileClick: function(event) {
        this._removedAttachmentIDs.push(RightNow.Text.getSubstringAfter(event.target.ancestor().get('id'), 'rn_RemoveExistingFile_'));
        event.target.ancestor().remove();
        this._attachmentCount--;
        this.input.set("disabled", false);
    }
});
RightNow.Widgets.FileListDisplay = RightNow.Widgets.extend({
    constructor: function() {
        this._currentAttachments = this.data.js.attachments;
        RightNow.Event.on('evt_editSuccessFileRefresh', this._renderFileList, this);
    },

    /*
     * Renders the file list again once given latest file details
     * @param {string} evt Event name
     * @param {array} origEventObj Original Event Object from the event fired
     */
    _renderFileList: function(event, origEventObj){
        if(this.instanceID === origEventObj[0].data.instanceID) {
            var fileAttachments = JSON.parse(origEventObj[0].data.fileAttachments);
            if(fileAttachments.length > 0) {
                var updatedFiles = new EJS({text: this.getStatic().templates.refreshedFileList})
                    .render({
                        attrs: this.data.attrs,
                        fileAttachments: fileAttachments,
                        instanceID: this.instanceID,
                        thumbnailAltText: RightNow.Interface.getMessage("THUMBNAIL_FOR_ATTACHED_IMAGE_MSG")
                    });
                this.Y.one(this.baseSelector).insert(updatedFiles, 'replace');
                if (this.data.attrs.display_thumbnail) {
                    var i, widget = this.Y.one(this.baseSelector);
                    for(i = 0; i < fileAttachments.length; i++) {
                        if(RightNow.Text.beginsWith(fileAttachments[i].ContentType, 'image')) {
                            var image = new Image;
                            image.id = fileAttachments[i].ID;
                            image.onload = function() {
                                widget.one('#rn_File_' + this.id).one(' .rn_FileTypeImageThumbnail').set('src', this.src);
                            }
                            image.src = fileAttachments[i].AttachmentUrl;
                        }
                    }
                }
                //reset the current attachments array based on server response
                this._currentAttachments = fileAttachments;
            }
            else {
                this.Y.one(this.baseSelector).setHTML('');
            }
        }
    }
});

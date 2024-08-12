RightNow.Widgets.OkcsRelatedAnswers = RightNow.Widgets.extend({
    constructor: function() {
        this._contentDiv = this.Y.one(this.baseSelector);
        
        //Ajax call to fetch answer details from batch API
        var eventObject = new RightNow.Event.EventObject(this, {data: {
           answerId: this.data.js.answerId,
           contentCount: this.data.attrs.limit,
           displayLinkType: this.data.attrs.display_link_type,
           action: 'OkcsRelatedAnswers'
        }});

        RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {
            successHandler: this._onRelatedContentResponse,
            json: true,
            scope: this
        });
    },

    /**
    * Success handler executed when the response for
    * fetch of related articles is obtained
    *
    * @param {Object} relatedContent Response object
    */
    _onRelatedContentResponse: function(relatedContent) {
        this._contentDiv.set('innerHTML', new EJS({text: this.getStatic().templates.view}).render({
                relatedLabelHeading: this.data.attrs.label_title,
                relatedContent: relatedContent,
                answerViewUrl: this.data.js.cpAnswerView,
                contentCount: this.data.attrs.limit,
                truncateSize: this.data.attrs.truncate_size,
                target: this.data.attrs.target,
                access_type : this.data.attrs.access_type
            })
        );
    }
});

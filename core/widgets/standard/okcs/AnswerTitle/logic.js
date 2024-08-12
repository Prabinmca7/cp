RightNow.Widgets.AnswerTitle = RightNow.SearchConsumer.extend({
    overrides: {
        constructor: function() {
            if(this.data.js.showDocIdMsg){
                this._contentDiv = this.Y.one('#rn_DocIdMsg');
                this._contentDiv.removeClass('rn_Hidden');
                var hideMsg = this.Y.one('.rn_hideBanner');
                if(hideMsg)
                    hideMsg.on('click', this._hideDocIdMsg, this);
            }
        }
    },

    /**
     * Hides document id search message.
     */
    _hideDocIdMsg: function() {
        this._contentDiv.addClass('rn_Hidden');
    }
});

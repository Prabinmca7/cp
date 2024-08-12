RightNow.Widgets.DiscussionPagination = RightNow.Widgets.extend({
    /**
     * Widget constructor.
     */
    constructor: function() {
        this.Y.all(this.baseSelector + " .rn_DiscussionPaginationLinks a").on('click', this._onDiscussionPaginationClick, this);
    },

    /**
     * Handle when a link is clicked
     * @param  {string} e Event from the click event
     */
    _onDiscussionPaginationClick: function(evt) {
        evt.preventDefault();
        var eventObject = new RightNow.Event.EventObject(this, {data: {
                qid: RightNow.Url.getParameter('qid'),
                paginatorLink : evt.target.getAttribute('data-type'),
                prodcat_id : this.data.js.prodcat_id
        }});
    
        if(this.data.js.f_tok){
            RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
            // Get a new f_tok value on each ajax request
            RightNow.Event.fire("evt_formTokenRequest", new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

            eventObject.data.f_tok = this.data.js.f_tok;
        }
        
        RightNow.Ajax.makeRequest(this.data.attrs.get_next_prev_ajax, eventObject.data, {
            data:           eventObject,
            json:           true,
            scope:          this,
            successHandler: this._onResponseReceived
        });
    },

    /**
     * Redirects to the question based on the link clicked.
     * @param response Event response
     */
    _onResponseReceived: function(response)
    {
        if(parseInt(response, 10) !== 0){
            RightNow.Url.navigate(this.data.attrs.redirect_url + response, true);
        }
        else{
            RightNow.Url.navigate('/app/error/error_id/9', true);
        }
    }
});
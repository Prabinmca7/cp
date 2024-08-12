RightNow.Widgets.PerformanceTest = RightNow.SearchConsumer.extend({
    overrides: {
        constructor: function() {
            this.parent();
            RightNow.Event.subscribe("evt_PerfTest", this._handleSaResponse, this);
            if (this.searchSource())
                this.searchSource().on('response', this._handleResponse, this);
        }
    },

    /** 
    * This method is called when response event is fired..
    * @param {object} filter object
    * @param {object} event object
    */
    _handleResponse: function(obj, evt){
        var newContent = "";
        if (evt[0].data) {
            this.Y.one(this.baseSelector + "_Grid").get('childNodes').remove();
            newContent = new EJS(({text: this.getStatic().templates.view})).render({data: evt[0].data});
        }
        if (this.Y.one(this.baseSelector + "_Grid")) {
            this.Y.one(this.baseSelector + "_Grid").set("innerHTML", newContent);
        }
    },
    
    _handleSaResponse: function(obj, evt){
        var newContent = "";
        if (evt[0].data) {
            this.Y.one(this.baseSelector + "_Grid").get('childNodes').remove();
            if(evt[0].data.response)
                newContent = new EJS(({text: this.getStatic().templates.view})).render({data: evt[0].data.response});
            else
                newContent = new EJS(({text: this.getStatic().templates.okcsSaView})).render({data: evt[0].data.data.suggestions[1]});
        }
        if (this.Y.one(this.baseSelector + "_Grid")) {
            this.Y.one(this.baseSelector + "_Grid").set("innerHTML", newContent);
        }
    }
});

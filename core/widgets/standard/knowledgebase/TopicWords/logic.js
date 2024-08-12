RightNow.Widgets.TopicWords = RightNow.SearchConsumer.extend({
    overrides: {
        constructor: function(){
            this.includeTopicWords = true;
            this.parent();
            this.searchSource().on('response', this._onTopicWordsUpdate, this);
        }
    },

    /**
     * Event handler for when topic words have been updated because a
     * search has been performed
     * @param {String} type Event name
     * @param {Object} args Event arguments
     */
    _onTopicWordsUpdate: function(type, args)
    {
        var eventObject = args[0],
            topicWordsDomList = this.Y.one(this.baseSelector + "_List"),
            root = this.Y.one(this.baseSelector),
            topicWordItems,
            linkString;

        if(!topicWordsDomList && root)
        {
            topicWordsDomList = this.Y.Node.create("<dl/>").set('id', this.baseDomID + "_List");
            root.appendChild(topicWordsDomList);
        }

        if(topicWordsDomList)
        {
            if(eventObject && eventObject.data && eventObject.data.topic_words && eventObject.data.topic_words.length)
            {
                topicWordItems = eventObject.data.topic_words;
                if (this.data.attrs.add_params_to_url)
                {
                    linkString = RightNow.Url.buildUrlLinkString(eventObject.filters, this.data.attrs.add_params_to_url);
                }
                topicWordsDomList.set('innerHTML', new EJS({text: this.getStatic().templates.view}).render({
                    attrs: this.data.attrs,
                    topicWordItems: topicWordItems,
                    linkString: linkString || ''
                }));
                RightNow.UI.show(root);
            }
            else
            {
                topicWordsDomList.set('innerHTML', "");
                RightNow.UI.hide(root);
            }
        }
    }
});

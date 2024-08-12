RightNow.Widgets.OkcsRecentlyViewedContent = RightNow.Widgets.extend({
    constructor: function() {
        this._contentDiv = this.Y.one(this.baseSelector);
        if(this._supportsHtml5Storage()) {
            var prevRecentAnswers = localStorage.getItem("okcsRecentAnswers");
            if(this.data.js.previousContent === null || this.data.js.previousContent.length === 0) {
                localStorage.setItem("okcsRecentAnswers","[]");
            }
            else if(prevRecentAnswers !== undefined && prevRecentAnswers !== null && prevRecentAnswers !== "") {
                var prevRecentAnswersObj = JSON.parse(prevRecentAnswers);
                for(i = prevRecentAnswersObj.length - 1; i >= 0; i--) {
                    if(!prevRecentAnswersObj[i].questionId && this.data.js.previousContent.indexOf(prevRecentAnswersObj[i].answerId) < 0) {
                        prevRecentAnswersObj.splice(i, 1);
                    }
                    else if(!prevRecentAnswersObj[i].answerId && this.data.js.previousQuestions.indexOf(prevRecentAnswersObj[i].questionId) < 0) {
                        prevRecentAnswersObj.splice(i, 1);
                    }
                }
                localStorage.setItem("okcsRecentAnswers", JSON.stringify(prevRecentAnswersObj));
            }
            prevRecentAnswers = localStorage.getItem("okcsRecentAnswers");
            if(prevRecentAnswers !== undefined && prevRecentAnswers !== null && prevRecentAnswers !== "") {
                var prevRecentAnswersObj = JSON.parse(prevRecentAnswers);
                this._onRecentContentResponse(prevRecentAnswersObj);
            }
        } 
        else {
            //Ajax call to fetch answer details from batch API
            var eventObject = new RightNow.Event.EventObject(this, {data: {
               contentCount: this.data.attrs.content_count,
               action: 'OkcsRecentAnswers'
            }});
            
            RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {
                successHandler: this._onRecentContentResponse,
                json: true, 
                scope: this
            });
        }

        if(this._supportsHtml5Storage() && this.data.js.questionId) {
            var prevRecentAnswers = localStorage.getItem("okcsRecentAnswers");
            var recentAnswers = Array({"questionId":this.data.js.questionId,"title":this.data.js.questionTitle});

            if(prevRecentAnswers != undefined && prevRecentAnswers != null && prevRecentAnswers !== "") {
                var prevRecentAnswersObj = JSON.parse(prevRecentAnswers);
                for(i = 0; i < prevRecentAnswersObj.length; i++) {
                    if(prevRecentAnswersObj[i].questionId !== this.data.js.questionId) {
                        recentAnswers.push(prevRecentAnswersObj[i]);
                    }
                }
            }
            localStorage.setItem("okcsRecentAnswers", JSON.stringify(recentAnswers));
        }
    },
    
    _onRecentContentResponse: function(recentlyViewedContent) {
        this._contentDiv.set('innerHTML', new EJS({text: this.getStatic().templates.view}).render({
                recentLabelHeading: this.data.attrs.label_heading,
                contentTypes: this.data.attrs.content_type,
                recentlyViewedContent: recentlyViewedContent,
                answerViewUrl: this.data.js.cpAnswerView,
                questionViewUrl: this.data.js.cpQuestionView,
                currentAnswerId: this.data.js.currentAnswerId,
                contentCount: this.data.attrs.content_count,
                truncateSize: this.data.attrs.truncate_size,
                access_type: this.data.attrs.access_type
            })
        );
    },
    
    _supportsHtml5Storage: function() {
        try {
            return 'localStorage' in window && window.localStorage !== null;
        } catch(e) {
            return false;
        }
    }
});

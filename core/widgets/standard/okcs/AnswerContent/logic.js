RightNow.Widgets.AnswerContent = RightNow.Widgets.extend({
    constructor: function() {
        if(RightNow.Url.getParameter('related') !== null) {
            var url = location.pathname.split('/related/')[0];
            if ('replaceState' in window.history) {
                history.replaceState(null, null, url);
            }
        }
        if(RightNow.Url.getParameter('s') !== null) {
            var url = location.pathname.split('/s/')[0] + '#__highlight';
            if ('replaceState' in window.history) {
                history.replaceState(null, null, url);
            }
        }
        if(this._supportsHtml5Storage()){
            var prevRecentAnswers = localStorage.getItem("okcsRecentAnswers");
            var answerId = this.data.js.answerID;
            var answerTitle = this.data.js.answerTitle;
            var recentAnswers = Array({"answerId":answerId,"title":answerTitle});

            if(prevRecentAnswers != undefined && prevRecentAnswers != null && prevRecentAnswers !== ""){
                var prevRecentAnswersObj = JSON.parse(prevRecentAnswers);
                for(i = 0; i < prevRecentAnswersObj.length; i++){
                    if(!prevRecentAnswersObj[i].answerId || prevRecentAnswersObj[i].answerId !== answerId){
                        recentAnswers.push(prevRecentAnswersObj[i]);
                    }
                }
            }
            localStorage.setItem("okcsRecentAnswers", JSON.stringify(recentAnswers));
        }
    },

    _supportsHtml5Storage: function(){
        try {
            return 'localStorage' in window && window.localStorage !== null;
        } catch(e) {
            return false;
        }
    }
});
RightNow.Widgets.AnswerField = RightNow.Widgets.extend({
    constructor: function() {
        var moreLink = this.Y.one(this.baseSelector + ' .rn_userGroupMore');
        if(moreLink !== null){
            moreLink.on('click', this._displayMoreUserGroup, this);
        }
        this.Y.on('domready', function () {
           RightNow.Event.fire("evt_pageLoaded");
        });
},

 _displayMoreUserGroup: function(){
    if(this.Y.one('.rn_userGroupMore').get("innerHTML") == this.data.attrs.label_less){
      this.Y.one('.rn_userGroupMore').set("innerHTML", this.data.attrs.label_more);
      this.Y.one('.rn_userGroupList').setAttribute("style", "display: inline;");
      this.Y.one('.rn_userGroupList2').setAttribute("style", "display:none;");
    }else{
      this.Y.one('.rn_userGroupMore').set("innerHTML",this.data.attrs.label_less);
      this.Y.one('.rn_userGroupList').setAttribute("style", "display: none");
      this.Y.one('.rn_userGroupList2').setAttribute("style", "display: inline;");
    }
  }

});
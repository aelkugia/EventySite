eventy.UserView = Backbone.View.extend({

    events:{
		"click #nextBtn":"nextClick"
    },

	initialize: function (options) {
		this.page = options.page;
		
		this.model = new eventy.EventyCollection({page: this.page});
				
		this.model.on("sync", this.render, this);
		this.model.on("add", this.fetchedDataFunc, this);
		
		this.model.fetch();
		
	},
	
	fetchedDataFunc: function () {
		this["fetchedData"] = true;

		this.render();
	},

    render:function () {
        this.$el.html(this.template({page:this.model.page}));	
		
		if(this.fetchedData == true)
		{
			this.fetchedData = false;
			this.$el.find("#my-events-view").empty();
		}
			
		_.each(this.model.models, function (events) {
			var listItem = new eventy.EventyListItemView({model:events, page: this.page});
			listItem.delegateEvents();
            this.$el.find("#my-events-view").append(listItem.render().el);
		}, this);
		
        return this;
    },
	
	nextClick: function () {
		this.model.page++;
		this.model.fetch();
	},

});

eventy.EventyListItemView = Backbone.View.extend({

    tagName:"div",

    events:{
        "click .deleteBtn":"deleteEventy"
    },

    initialize:function (options) {
        this.model.bind("change", this.render, this);
        this.model.bind("destroy", this.close, this);
    },

    render:function () {
		if( !this.model.toJSON().hasOwnProperty("page") )
		{
			$(this.el).html(this.template(this.model.toJSON()));
        	return this;
		}
		
		return "";
    },
	
	deleteEventy: function() {
		this.model.destroy({ data: $.param({ access_token: eventy.access_token }) });
		$(this.el).hide();
	},

});
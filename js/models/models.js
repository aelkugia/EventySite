
eventy.Eventy = Backbone.Model.extend({

    urlRoot:"api/v1/event",
	
});

eventy.Categories = Backbone.Model.extend({

    urlRoot:"api/v1/category",
	
});

eventy.EventyCollection = Backbone.Collection.extend({

    model: eventy.Eventy,

	initialize: function(options) {
		this.page = options.page;	
		
		if(this.page == null)
		{
			this.page = 1;
		}
	},

    url: function () {
		return "api/v1/event" + "?page=" + this.page;
	},
	
	parse: function(response){
       return response.data;
    }
});

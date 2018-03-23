eventy.EventyView = Backbone.View.extend({

    events:{
		 "click #submitBtn":"submitClick"
    },

	initialize: function (options) {		
		this.model = new eventy.Eventy({ id: options.id });
				
		//this.model.on("change", this.render, this);
		this.model.on("add", this.fetchedDataFunc, this);
		this.model.on("sync", this.render, this);
		
		if(this.model.id != 0)
			this.model.fetch();
		else
			this.render();
			
	},
	
	clicked: function(e){
    	e.preventDefault();
    	// do your stuff here
  	},
	
	fetchedDataFunc: function () {
		this["fetchedData"] = true;

		this.render();
	},

    render:function () {
		if(this.model.id != 0 && this.model.id != undefined)
			var tempEvent = this.model.toJSON();
		else
		{
			var tempEvent = 
			{
				name : "",
				description: "",
				location_lat: "",
				location_long: "",
				location_string: "",
				event_time: "2015-11-11 08:00:00",
				image: "",
				category_id: 0,
			};	
		}

		this.$el.empty();	

		if( tempEvent != undefined && tempEvent["description"] != undefined)
		{
			tempEvent["date"] = tempEvent.event_time;
			this.$el.html(this.template(tempEvent));
			
			if(!eventy.categories)
			{
				eventy.categories = new eventy.Categories();
				eventy.categories.fetch({ async: false });	
				console.log(eventy.categories.toJSON());
			}
			
			this.$el.find("#category-container").html("<label for=\"categories\">Category</label>");
			var s = $("<select id=\"categories\"/>");
			var data = eventy.categories.toJSON();
			
			for(var val in data) {
				var category = data[val];
				var option = $("<option />", {value: category.id, text: category.name});
				
				if( parseInt(category.id) == parseInt(tempEvent.category_id) )
					option.attr("selected","selected");
					
				option.appendTo(s);
			}
			
			this.$el.find("#category-container").append(s);
			
			var sp = $("<select id=\"public_private\"/>");
			var optionPublic = $("<option />", {value: 1, text: "Public"});
			var optionPrivate = $("<option />", {value: 0, text: "Private"});
			
			if( parseInt(tempEvent.public_private) > 0)
				optionPublic.attr("selected","selected");
			else
				optionPrivate.attr("selected","selected");
				
			sp.append(optionPublic);
			sp.append(optionPrivate);
			
			this.$el.find("#public-private-container").html("<label for=\"public_private\">Type</label>");
			this.$el.find("#public-private-container").append(sp);
		}
									
        return this;
    },
	
	submitClick:function () {
		if(this.model.id == 0)
			this.model.unset("id"); // force a POST when a new event is being created
				
		this.model.set({"name":this.$el.find("#name").val()});
		this.model.set({"event_time":this.$el.find("#event_time").val()}); 
		this.model.set({"description":this.$el.find("#description").val()}); 
		this.model.set({"location_lat":this.$el.find("#location_lat").val()}); 
		this.model.set({"location_long":this.$el.find("#location_long").val()}); 
		this.model.set({"location_string":this.$el.find("#location_string").val()}); 
		this.model.set({"image":this.$el.find("#image").val()}); 
		this.model.set({"category_id":this.$el.find("#categories").val()});  
		this.model.set({"public_private":this.$el.find("#public_private").val()});  
		
		this.model.save({}, {wait: false}).done(function() {
			// Success case
			alert("We successfully saved this event.");
		}).fail(function() {
			// Failure case
			alert("Something went wrong while saving this event.");
		});
	}
});
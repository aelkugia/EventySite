// Models
window.Event = Backbone.Model.extend({
    urlRoot:"../api/events",
    defaults:{
        "id":null,
        "name":"",
        "grapes":"",
        "country":"USA",
        "region":"California",
        "year":"",
        "description":"",
        "picture":""
    }
});

window.EventCollection = Backbone.Collection.extend({
    model:Event,
    url:"../api/events"
});


// Views
window.EventListView = Backbone.View.extend({

    tagName:'ul',

    initialize:function () {
        this.model.bind("reset", this.render, this);
        var self = this;
        this.model.bind("add", function (event) {
            $(self.el).append(new EventListItemView({model:event}).render().el);
        });
    },

    render:function (eventName) {
        _.each(this.model.models, function (event) {
            $(this.el).append(new EventListItemView({model:event}).render().el);
        }, this);
        return this;
    }
});

window.EventListItemView = Backbone.View.extend({

    tagName:"li",

    template:_.template($('#tpl-event-list-item').html()),

    initialize:function () {
        this.model.bind("change", this.render, this);
        this.model.bind("destroy", this.close, this);
    },

    render:function (eventName) {
        $(this.el).html(this.template(this.model.toJSON()));
        return this;
    },

    close:function () {
        $(this.el).unbind();
        $(this.el).remove();
    }
});

window.EventView = Backbone.View.extend({

    template:_.template($('#tpl-event-details').html()),

    initialize:function () {
        this.model.bind("change", this.render, this);
    },

    render:function (eventName) {
        $(this.el).html(this.template(this.model.toJSON()));
        return this;
    },

    events:{
        "change input":"change",
        "click .save":"saveEvent",
        "click .delete":"deleteEvent"
    },

    change:function (event) {
        var target = event.target;
        console.log('changing ' + target.id + ' from: ' + target.defaultValue + ' to: ' + target.value);
        // You could change your model on the spot, like this:
        // var change = {};
        // change[target.name] = target.value;
        // this.model.set(change);
    },

    saveEvent:function () {
        this.model.set({
            name:$('#name').val(),
            grapes:$('#grapes').val(),
            country:$('#country').val(),
            region:$('#region').val(),
            year:$('#year').val(),
            description:$('#description').val()
        });
        if (this.model.isNew()) {
            var self = this;
            app.eventList.create(this.model, {
                success:function () {
                    app.navigate('events/' + self.model.id, false);
                }
            });
        } else {
            this.model.save();
        }

        return false;
    },

    deleteEvent:function () {
        this.model.destroy({
            success:function () {
                alert('Event deleted successfully');
                window.history.back();
            }
        });
        return false;
    },

    close:function () {
        $(this.el).unbind();
        $(this.el).empty();
    }
});

window.HeaderView = Backbone.View.extend({

    template:_.template($('#tpl-header').html()),

    initialize:function () {
        this.render();
    },

    render:function (eventName) {
        $(this.el).html(this.template());
        return this;
    },

    events:{
        "click .new":"newEvent"
    },

    newEvent:function (event) {
        app.navigate("events/new", true);
        return false;
    }
});


// Router
var AppRouter = Backbone.Router.extend({

    routes:{
        "":"list",
        "events/new":"newEvent",
        "events/:id":"eventDetails"
    },

    initialize:function () {
        $('#header').html(new HeaderView().render().el);
    },

    list:function () {
        this.eventList = new EventCollection();
        var self = this;
        this.eventList.fetch({
            success:function () {
                self.eventListView = new EventListView({model:self.eventList});
                $('#sidebar').html(self.eventListView.render().el);
                if (self.requestedId) self.eventDetails(self.requestedId);
            }
        });
    },

    eventDetails:function (id) {
        if (this.eventList) {
            this.event = this.eventList.get(id);
            if (this.eventView) this.eventView.close();
            this.eventView = new EventView({model:this.event});
            $('#content').html(this.eventView.render().el);
        } else {
            this.requestedId = id;
            this.list();
        }
    },

    newEvent:function () {
        if (app.eventView) app.eventView.close();
        app.eventView = new EventView({model:new Event()});
        $('#content').html(app.eventView.render().el);
    }

});

var app = new AppRouter();
Backbone.history.start();
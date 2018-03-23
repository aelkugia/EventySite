var eventy = {

    views: {},

    models: {},

	access_token: "",

    loadTemplates: function(views, callback) {

        var deferreds = [];

        $.each(views, function(index, view) {
            if (eventy[view]) {
                deferreds.push($.get('tpl/' + view + '.html', function(data) {
                    eventy[view].prototype.template = _.template(data);
                }, 'html').fail(function(){
                    alert("Failed to get templates.");
                }));
            } else {
                alert(view + " not found");
            }
        });

        $.when.apply(null, deferreds).done(callback);
    }

};

eventy.Router = Backbone.Router.extend({

    routes: {
        "":                 "home",
        "myevents/:page":   "myEvents",
		"event/:id":   		"eventController",
		"home":		   		"myEventsHome",
		"logout":		   	"logoutController"
    },
	
		
	eventController: function(id) {
		if (!eventy.eventyView) {			
            eventy.eventyView = new eventy.EventyView({id:id});
        }
							
		eventy.eventyView.model.set("id",id);	
		eventy.eventyView.model.fetch();		
		eventy.eventyView.render();			
		eventy.eventyView.delegateEvents();
		
        this.$content.html(eventy.eventyView.el);
	},
	
	logoutController: function() {
		document.cookie="access_token=";
		eventy.access_token = "";
		eventy.router.navigate(" ");
	},
	
	
	myEvents: function(currentPage) {
		if (!eventy.userView) {
            eventy.userView = new eventy.UserView({page:currentPage});
            eventy.userView.render();
        }
		
		eventy.userView.page = currentPage;
		eventy.userView.model.fetch();		
		eventy.userView.delegateEvents();
		
        this.$content.html(eventy.userView.el);
	},
	
	myEventsHome: function(currentPage) {
		if (!eventy.userView) {
            eventy.userView = new eventy.UserView({page:1});
            eventy.userView.render();
        }
			
		eventy.userView.model.fetch();		
		eventy.userView.delegateEvents();

        this.$content.html(eventy.userView.el);
	},

    initialize: function () {
        eventy.shellView = new eventy.ShellView();
        $('body').html(eventy.shellView.render().el);
        // Close the search dropdown on click anywhere in the UI
        $('body').click(function () {
            $('.dropdown').removeClass("open");
        });
        this.$content = $("#content");
		this.$signInModalContent = $("#sign-in-modal");
    },

    home: function () {		
        // Since the home view never changes, we instantiate it and render it only once
        if (!eventy.homelView) {
            eventy.homelView = new eventy.HomeView();
            eventy.homelView.render();
        } else {
            console.log('reusing home view');
        }
		
		if(eventy.access_token != "")
		{
			this.myEventsHome();
			return;
		}
		
		this.$content.html(eventy.homelView.el);
    },

    login: function (element) {
        this.home();
		eventy.shellView.showLogin();
    },

	navigate: function(url) {
		Backbone.history.navigate(url, {trigger: true, replace: true});	
	},

    employeeDetails: function (id) {
        var employee = new eventy.Employee({id: id});
        var self = this;
        employee.fetch({
            success: function (data) {
                console.log(data);
                // Note that we could also 'recycle' the same instance of EmployeeFullView
                // instead of creating new instances
                self.$content.html(new eventy.EmployeeView({model: data}).render().el);
            }
        });
        eventy.shellView.selectMenuItem();
    }

});

$(document).on("ready", function () {
	var token = getCookie("access_token");
	
	eventy.access_token = token;
	
	$.ajaxSetup({
        beforeSend: function (jqXHR, settings) {
            if(eventy.access_token)
                jqXHR.setRequestHeader('Authorization', 'Bearer ' + eventy.access_token);
            return true;
        }
		//headers: { 'Authorization' :'Bearer ' + eventy.access_token }
	});
	
    eventy.loadTemplates(["HomeView","ShellView","SignInModal","UserView","EventyListItemView","EventyView"],
        function () {
            eventy.router = new eventy.Router();
            Backbone.history.start();
        });
});

function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i=0; i<ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1);
        if (c.indexOf(name) != -1) return c.substring(name.length,c.length);
    }
    return "";
}
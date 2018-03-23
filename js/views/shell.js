eventy.ShellView = Backbone.View.extend({

    initialize: function () {
		this.setElement( this.template() );
        //this.searchresultsView = new events.EmployeeListView({model: this.searchResults, className: 'dropdown-menu'});
    },

    render: function () {
		if (!eventy.signInModal) {
            eventy.signInModal = new eventy.SignInModal();
            eventy.signInModal.render();
        }
		
		this.$el.find("#sign-in-modal").html(eventy.signInModal.el);
        //$('.navbar-search', this.el).append(this.searchresultsView.render().el);
        return this;
    },
	
    showLogin: function () {
		events.signInModal.showLogin();
    },
	
    events: {
        "keyup .search-query": "search",
        "keypress .search-query": "onkeypress"
    },

    search: function (event) {
        var key = $('#searchText').val();
        this.searchResults.fetch({reset: true, data: {name: key}});
        var self = this;
        setTimeout(function () {
            $('.dropdown').addClass('open');
        });
    },

    onkeypress: function (event) {
        if (event.keyCode === 13) { // enter key pressed
            event.preventDefault();
        }
    },

    selectMenuItem: function(menuItem) {
        $('.navbar .nav li').removeClass('active');
        if (menuItem) {
            $('.' + menuItem).addClass('active');
        }
    }

});
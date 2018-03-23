eventy.SignInModal = Backbone.View.extend({

	initialize: function() {
		this.setElement( this.template() );
	},

    events:{
        "click #sign-in-button":"signInBtnClick"
    },

    render:function () {
        //this.$el.html(this.template());
        return this;
    },
	
	showLogin: function () {
		if(eventy.access_token == "")
		{
			$('#myModal').modal('show');
		}
    },

    signInBtnClick:function () {
		if(eventy.access_token == "")
		{
					$.post( "http://eventy.social/api/v1/oauth/access_token", {
						"grant_type":"password",
						"client_id":"6292a1e7-398e-11e4-ba84-f23c916e077f",
						"client_secret":"6292a98e-398e-11e4-ba84-f23c916e077f",
						"username":this.$el.find("#email").val(),
						"password": sha512(this.$el.find("#password").val()),
						"scope":"user",
						"state":"12345"
					})
					  .done(function(data) {
						// successfully logged in
						eventy.access_token = data.access_token;
						var expires = new Date(data.expires*1000);
						document.cookie="access_token=" + data.access_token + ";expires=" + expires.toUTCString();
						$('#myModal').modal('hide');
						
							$.ajaxSetup({
								headers: { 'Authorization' :'Bearer ' + eventy.access_token }
							});
						
						eventy.router.navigate("home");
					  })
					  .fail(function(data) {
						alert( data.responseJSON.error_description );
					  });
		}
		else
		{
			alert("Already logged in with access_token: " + eventy.access_token);
			$('#myModal').modal('hide');	
		}

    }

});
(function () {
    'use strict';
    var app = angular.module("Eventy", ['ngRoute', 'eventyControllers']);

    app.controller('EventyController', ['$http',
        function ($http) 
        {
            this.getEventy = function () 
            {
                $http({
                    method: 'GET',
                    url: 'http://eventy.social/api/v1/event/4'
                }).then(function (response) {
                    alert(response);
                });
            };
        }]);

    app.config(['$routeProvider',
        function ($routeProvider) {
            $routeProvider.
			when('/login', {
            	template: '<a href="#/Page1">Page1</>',
                controller: 'LoginCtrl'
            }).
			otherwise({
				redirectTo: '/phones'
			 });
        }]);

    app.config(['$httpProvider',
        function ($httpProvider) 
        {
			$httpProvider.interceptors.push(function($q, $location) {
				return {
				   'request': function(config) {
					   return config;
					},

					'response': function(response) {
				   		return response;
					},
					
					'responseError': function(rejection) {
						// error - was it 401 or something else?
                        if (rejection.status === 401 && rejection.data.error /*&& response.data.error === "invalid_token"*/ ) 
						{
							$location.path('/login');
							return rejection;
						}
						return $q.reject(rejection); // not a recoverable error
					}  
			};
		});
            /* intercept for oauth tokens
            $httpProvider.responseInterceptors.push(['$rootScope', '$q', '$injector', '$location',
                function ($rootScope, $q, $injector, $location) 
                {
                    return function (promise) {
                        return promise.then(function (response) {
                            return response; // no action, was successful
                        }, function (response) {
                            // error - was it 401 or something else?
                            if (response.status === 401 && response.data.error /*&& response.data.error === "invalid_token" ) 
                            {
                                $location.path('/login');
                                return response;
                            }
                            return $q.reject(response); // not a recoverable error
                        });
                    };
                }]);*/
        }]);

    app.run(['$rootScope', '$injector',
                function ($rootScope, $injector) {
            $injector.get("$http").defaults.transformRequest = function (data, headersGetter) {
                if ($rootScope.oauth) {
                    headersGetter().Authorization = "Bearer " + $rootScope.oauth.access_token;
                }
                if (data) {
                    return angular.toJson(data);
                }
            };

            $rootScope.$on('$routeChangeSuccess', function (e, current, pre) {
                console.log(current.$$route.originalPath);
            });
                    }]);

    app.directive('testDirective', function () {
        return {
            restrict: 'A',
            templateURL: 'templates/test.html',
            controller: function () {
                alert("test");
            }
        };
    });
}());
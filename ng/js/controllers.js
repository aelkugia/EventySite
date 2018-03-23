var eventyControllers = angular.module('eventyControllers', []);

eventyControllers.controller('LoginCtrl', ['$scope', '$http',
  function ($scope, $http) {
    console.log("Loaded login controller.");
  }]);

eventyControllers.controller('PhoneDetailCtrl', ['$scope', '$routeParams',
  function($scope, $routeParams) {
    $scope.phoneId = $routeParams.phoneId;
  }]);
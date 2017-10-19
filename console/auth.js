var page = require('webpage').create();
var fs = require('fs');
var system = require('system');
page.onConsoleMessage = function (msg){
	system.stdout.write(msg);
};


var casper = require('casper').create({
    pageSettings: {
		userAgent: "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.11; rv:43.0) Gecko/20100101 Firefox/43.0",
	}
});

var cookieFileName = casper.cli.get("cookie");
var cookies = fs.read(cookieFileName);
phantom.cookies = JSON.parse(cookies);

var login = casper.cli.get("login");

var password = casper.cli.get("password");

casper.on("resource.error", function(resourceError){
    console.log('Unable to load resource (#' + resourceError.id + 'URL:' + resourceError.url + ')');
    console.log('Error code: ' + resourceError.errorCode + '. Description: ' + resourceError.errorString);
});

casper.start('https://www.ganjawars.ru/login.php').thenEvaluate(function(ll, pp) {

	$("input[type=text][name=login]").val(ll);

	$("input[name=pass]").val(pp);
	$("#gotobutton").trigger("click");
	

}, login, password).then(function(){
	this.wait(1000, function(){

	});
});

casper.run(function() {
	var cookies = JSON.stringify(phantom.cookies);
	fs.write(cookieFileName, cookies, "w");
	this.exit();
});
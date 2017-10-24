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

var war_id = casper.cli.get("war_id");


casper.on("resource.error", function(resourceError){
    // console.log('Unable to load resource (#' + resourceError.id + 'URL:' + resourceError.url + ')');
    // console.log('Error code: ' + resourceError.errorCode + '. Description: ' + resourceError.errorString);
});


casper.start('http://www.ganjawars.ru/warlog.php?bid='+war_id).then(function() {
    var r = this.evaluate(function(){
    	var str = $("span.txt").html();
		return str;
	});
	answer = {};
	answer.title = this.getTitle();
	answer.data = r;
	this.echo(JSON.stringify(answer));
	
});

casper.run(function() {
	var cookies = JSON.stringify(phantom.cookies);
	fs.write(cookieFileName, cookies, "w");
	this.exit();
});
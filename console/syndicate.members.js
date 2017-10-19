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


var synd = casper.cli.get("synd");


casper.on("resource.error", function(resourceError){
    console.log('Unable to load resource (#' + resourceError.id + 'URL:' + resourceError.url + ')');
    console.log('Error code: ' + resourceError.errorCode + '. Description: ' + resourceError.errorString);
});


casper.start('http://www.ganjawars.ru/syndicate.php?id='+synd+'&page=members').then(function() {
    var r = this.evaluate(function(){
    	var str;
		ln = Array();
		$("table.wb > tbody > tr > td.wb > nobr > a[href^='/info.php']").each(function(i, row){
			var row = {};
			row.id = $(this).attr("href").replace("/info.php?id=", "");
			row.name = $(this).find("b").text();
			ln.push(row);
		});
		return ln;
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
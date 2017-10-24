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

var page_id = casper.cli.get("page_id");


casper.on("resource.error", function(resourceError){
    // console.log('Unable to load resource (#' + resourceError.id + 'URL:' + resourceError.url + ')');
    // console.log('Error code: ' + resourceError.errorCode + '. Description: ' + resourceError.errorString);
});


casper.start('http://www.ganjawars.ru/syndicate.log.php?id='+synd+'&warstats=1&page_id='+page_id).then(function() {
    var r = this.evaluate(function(){
    	var str;
		w = Array();
		fi = {};
		var started = false;
		$("nobr").each(function(i, row){
			if ($(this).find("font").length > 0){
				if (!started) started = true;
				if (started){
					var txt = $(this).find("font").text();
					str += $(this).text();
					str +=  "\n===\n";
					fi.date = txt;
					fi.war = $(this).html();
				}
			}else{
				if (started){
					fi.act = $(this).html();
					w.push(fi);
					fi = {};
				}
			}
		});
		return w;
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
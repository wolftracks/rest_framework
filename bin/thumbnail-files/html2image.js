var system = require('system'); 
// console.log('args = ' + system.args.length); 
if (system.args.length < 3) {
   console.log('ERROR: Expecting 2 Arguments: URL  OutputFile'); 
   phantom.exit(); 
}
var this_File = system.args[0];
var url  = system.args[1];
var file = system.args[2]; 

//console.log('url = ' + url);
//console.log('file = ' + file);
//phantom.exit(); 
   
var page = require('webpage').create();
page.open(url, function () {
    page.render(file);
    phantom.exit();
});


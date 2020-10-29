var http = require('http');
var fs = require('fs');
var path = require('path');

http.createServer(function (request, response) {
    var filePath = '.' + request.url;
    if (filePath == './') {
        filePath = './index.html';
    }
    fs.readFile(filePath, function(error, content) {
       response.end(content, 'utf-8');
    });

}).listen(3000);
console.log('Server running at http://127.0.0.1:3000/');
"use strict";

let app = require('express')();

app.get('/', function (request, response) {
    response.end('<a href="/link1">Link1</a><a href="/link2">Link2</a><a href="dir/link4">Link4</a><a href="mailto:test@example.com">Email</a>');
});

app.get('/link1', function (request, response) {
    response.end('You are on link1<a href="http://example.com/">External Link</a>');
});

app.get('/link2', function (request, response) {
    response.end('You are on link2<a href="/link3">Link3</a>');
});

app.get('/link3', function (request, response) {
    response.end('You are on link3<a href="/notExists">not exists</a>');
});

app.get('/dir/link4', function (request, response) {
    response.end('You are on /dir/link4<a href="link5">link 5</a>');
});

app.get('/dir/link5', function (request, response) {
    response.end('You are on /dir/link5<a href="subdir/link6">link 6</a>');
});

app.get('/dir/subdir/link6', function (request, response) {
    response.end('You are on /dir/subdir/link6<a href="/link1">link 1</a>');
});

let server = app.listen(8080, function () {
    const host = 'localhost';
    const port = server.address().port;

    console.log('Testing server listening at http://%s:%s', host, port);
});

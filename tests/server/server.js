"use strict";

let app = require('express')();

app.get('/', function (request, response) {
    response.end('<a href="/not-allowed">Not allowed</a><a href="/link1">Link1</a><a href="/link2">Link2</a><a href="dir/link4">Link4</a><a href="mailto:test@example.com">Email</a><a href="tel:123">Telephone</a><a href="/nofollow" rel="nofollow">No follow</a>');
});

app.get('/link1', function (request, response) {
    response.end('<html><body><script>var url = \'/javascript\';document.body.innerHTML = document.body.innerHTML + "<a href=\'" + url + "\'>Javascript Link</a>"</script>You are on link1<a href="http://example.com/">External Link</a></body></html>');
});

app.get('/javascript', function (request, response) {
    response.end('This page can only be reached if JavaScript is being executed');
});

app.get('/nofollow', function (request, response) {
    response.end('This page should not be crawled');
});

app.get('/link2', function (request, response) {
    response.end('You are on link2<a href="/link3">Link3</a><a href="http://sub.localhost:8080/subdomainpage">Subdomain</a><a href="http://subdomain.sub.localhost:8080/subdomainpage">Subdomain2</a>');
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

app.get('/invalid-url', function (request, response) {
    response.end('There is an <a href="https:///AfyaVzw">invalid</a> url');
});

app.get('/not-allowed', function (request, response) {
    console.log('Visit on /not-allowed');

    response.end('Not allowed');
});

app.get('/robots.txt', function (req, res) {
    var html = 'User-agent: *\n' +
        'Disallow: /not-allowed';

    console.log('Visited robots.txt');

    res.writeHead(200, { 'Content-Type': 'text/html' });
    res.end(html);
});

let server = app.listen(8080, function () {
    const host = 'localhost';
    const port = server.address().port;

    console.log('Testing server listening at http://%s:%s', host, port);
});

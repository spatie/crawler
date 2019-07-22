"use strict";

let app = require('express')();

app.get('/', function (request, response) {
    response.end('<a href="/txt-disallow">txt disallowed</a><a href="/meta-follow">meta disallowed</a><a href="/header-disallow">header disallowed</a><a href="/link1">Link1</a><a href="/link2">Link2</a><a href="dir/link4">Link4</a><a href="mailto:test@example.com">Email</a><a href="tel:123">Telephone</a><a href="/nofollow" rel="nofollow">No follow</a><a href="/txt-disallow-custom-user-agent">Disallow Custom User Agent</a>');
});

app.get('/link1', function (request, response) {
    response.end('<html><head><link rel="next" href="/link1-next"><link rel="prev" href="/link1-prev"></head><body><script>var url = \'/javascript\';document.body.innerHTML = document.body.innerHTML + "<a href=\'" + url + "\'>Javascript Link</a>"</script>You are on link1<a href="http://example.com/">External Link</a></body></html>');
});

app.get('/javascript', function (request, response) {
    response.end('This page can only be reached if JavaScript is being executed');
});

app.get('/link1-next', function (request, response) {
    response.end('You are on link1-next. Next page of link1');
});

app.get('/link1-prev', function (request, response) {
    response.end('You are on link1-prev. Previous page of link1');
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

app.get('/txt-disallow', function (request, response) {
    response.end('Not allowed');
});

app.get('/txt-disallow-custom-user-agent', function (request, response) {
    response.end('Not allowed for Custom User Agent');
});

app.get('/meta-follow', function (request, response) {
    response.end('<html><head>\n<meta name="robots" content="noindex, follow">\n</head><body><a href="/meta-nofollow">No follow</a></body></html>');
});

app.get('/meta-nofollow', function (request, response) {
    response.end('<html><head>\n<meta name="robots" content="index, nofollow">\n</head><body><a href="/meta-nofollow-target">no follow it</a></body></html>');
});

app.get('/dir1/internal-redirect-entry/', function (request, response) {
    response.end('<a href="../loop-generator/internal-redirect/trapped/">trapped</a> <a href="../../dir1/internal-redirect/trap/">trap-start</a>');
});

app.get('/dir1/internal-redirect/trap/', function (request, response) {
    response.redirect(301, '/dir1/internal-redirect-entry/');
});

app.get('/dir1/loop-generator/internal-redirect/trapped/', function (request, response) {
    response.end('It should be crawled once');
});

app.get('/meta-nofollow-target', function (request, response) {
    response.end('No followable');
});

app.get('/header-disallow', function (request, response) {
    response.set({'X-Robots-Tag': '*: noindex'});

    response.end('disallow by header');
});

app.get('/robots.txt', function (req, res) {
    var html = 'User-agent: *\n' +
        'Disallow: /txt-disallow\n' +
        'User-agent: my-agent\n' +
        'Disallow: /txt-disallow\n' +
        'Disallow: /txt-disallow-custom-user-agent';

    res.end(html);
});

let server = app.listen(8080, function () {
    const host = 'localhost';
    const port = server.address().port;

    console.log('Testing server listening at http://%s:%s', host, port);
});

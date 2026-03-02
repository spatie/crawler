<?php

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

switch ($uri) {
    case '/':
        header('Content-Type: text/html');
        echo '<html><body>';
        echo '<a href="/page1">Page 1</a>';
        echo '<a href="/page2">Page 2</a>';
        echo '<a href="/page3">Page 3</a>';
        echo '<a href="/slow">Slow</a>';
        echo '<a href="/page1/">Page 1 trailing slash</a>';
        echo '</body></html>';
        break;

    case '/page1':
        header('Content-Type: text/html');
        echo '<html><body><h1>Page 1</h1><a href="/page2">Page 2</a></body></html>';
        break;

    case '/page2':
        header('Content-Type: text/html');
        echo '<html><body><h1>Page 2</h1><a href="/page3">Page 3</a></body></html>';
        break;

    case '/page3':
        header('Content-Type: text/html');
        echo '<html><body><h1>Page 3</h1><a href="/page1">Page 1</a></body></html>';
        break;

    case '/slow':
        usleep(300_000);
        header('Content-Type: text/html');
        echo '<html><body><h1>Slow page</h1></body></html>';
        break;

    case '/link-to-404':
        header('Content-Type: text/html');
        echo '<html><body><a href="/not-found">Broken link</a></body></html>';
        break;

    case '/link-to-secret':
        header('Content-Type: text/html');
        echo '<html><body><a href="/secret">Secret link</a></body></html>';
        break;

    case '/secret':
        header('Content-Type: text/html');
        echo '<html><body><h1>Secret page</h1></body></html>';
        break;

    case '/deep/1':
        header('Content-Type: text/html');
        echo '<html><body><h1>Deep 1</h1><a href="/deep/2">Next</a></body></html>';
        break;

    case '/deep/2':
        header('Content-Type: text/html');
        echo '<html><body><h1>Deep 2</h1><a href="/deep/3">Next</a></body></html>';
        break;

    case '/deep/3':
        header('Content-Type: text/html');
        echo '<html><body><h1>Deep 3</h1><a href="/deep/4">Next</a></body></html>';
        break;

    case '/deep/4':
        header('Content-Type: text/html');
        echo '<html><body><h1>Deep 4</h1></body></html>';
        break;

    case '/robots.txt':
        header('Content-Type: text/plain');
        echo "User-agent: *\nDisallow: /secret\n";
        break;

    default:
        http_response_code(404);
        header('Content-Type: text/html');
        echo '<html><body><h1>Not Found</h1></body></html>';
        break;
}

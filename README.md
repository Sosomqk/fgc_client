# fgc_client
Wrapper HTTP Client utilising file_get_contents in PHP

# Requirements
- PHP >= 8.0

# Usage

```
$o = new FGCClient('https://some-url');

$o->setHeaders([
    'Authorization' => 'Basic w231313331',
    'x-api-key' => 'rand',
    'Content-Type' => 'application/json',
]);

$payload = ["message" => "test"];

$o->setTimeout(60);

$o->post('', $payload);

if ($ec = $o->getErrorCode()) {
    var_dump($ec);
}
if ($em = $o->getErrorMessage()) {
    var_dump($em);
}

var_dump($o->getResponseHeaders());
var_dump($o->getHttpStatusCode());
var_dumo($o->getRawResponse());
```

# Notes
Using this client instead of regular curl will be faster IF you destroy the curl handle on each request. However, reusing the curl handle resource will yield better results.

<b>Strongly suggest using this client in Swoole's coroutines - it yields 92% better performance than regular curl WITH reusing the curl handle resource.</b>

# fgc_client
Wrapper HTTP Client utilising file_get_contents in PHP

# Requirements
- PHP >= 8.0

# Installation

`composer require isikiyski/fgc_client`

# Usage

```
$o = new \Isikiyski\Client\FGCClient('https://some-url');

$o->setHeaders([
    'Authorization' => 'Basic w231313331',
    'x-api-key' => 'rand',
    'Content-Type' => 'application/json',
]);

$payload = ["message" => "test"];

$o->setTimeout(60);

$o->post('', $payload);

if ($ec = $o->getErrorCode()) {
    echo $ec . PHP_EOL;
}
if ($em = $o->getErrorMessage()) {
    echo $em . PHP_EOL;
}

echo $o->getResponseHeaders() . PHP_EOL;
echo $o->getHttpStatusCode() . PHP_EOL;
echo $o->getRawResponse() . PHP_EOL;
//
// ....
//
```

# Notes
Using this client instead of regular curl will be faster IF you destroy the curl handle on each request. However, reusing the curl handle resource will yield better results.

<b>Strongly suggest using this client in Swoole's coroutines - it yields 92% better performance than regular curl WITH reusing the curl handle resource.</b>

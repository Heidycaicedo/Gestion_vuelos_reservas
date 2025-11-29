<?php
// Simple PHP test client to POST to auth/login and print response
$url = $argv[1] ?? 'http://localhost:8001/api/auth/login';
$data = $argv[2] ?? json_encode(['email' => 'admin@system.com', 'password' => 'admin123']);

$opts = [
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\n",
        'content' => $data,
        'ignore_errors' => true,
    ]
];

$context  = stream_context_create($opts);
$result = @file_get_contents($url, false, $context);

echo "Request URL: $url\n";
echo "Request Body: $data\n\n";
if ($result === false) {
    echo "No response or error.\n";
    if (!empty($http_response_header)) {
        echo "Response headers:\n" . implode("\n", $http_response_header) . "\n";
    }
} else {
    echo "Response:\n";
    echo $result . "\n";
    if (!empty($http_response_header)) {
        echo "\nResponse headers:\n" . implode("\n", $http_response_header) . "\n";
    }
}

?>
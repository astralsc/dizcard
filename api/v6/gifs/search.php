<?php

$url = 'https://discord.com/api/v6/gifs/search?' . http_build_query([
    'q' => $_GET['q'] ?? '',
    'media_format' => $_GET['media_format'] ?? '',
    'provider' => $_GET['provider'] ?? '',
    'locale' => $_GET['locale'] ?? ''
]);

header('Location: ' . $url);
exit;
<?php

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'REQUEST_URI'  => $_SERVER['REQUEST_URI'] ?? null,
    'PATH_INFO'    => $_SERVER['PATH_INFO'] ?? null,
    'SCRIPT_NAME'  => $_SERVER['SCRIPT_NAME'] ?? null,
    'PHP_SELF'     => $_SERVER['PHP_SELF'] ?? null,
    'QUERY_STRING' => $_SERVER['QUERY_STRING'] ?? null,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Simple Rate Limiting implementation using $_SESSION.
 * In a high-traffic production app, you would use Redis or Memcached.
 */
function checkRateLimit() {
    $limit = 100; // max requests
    $timeWindow = 60; // per 60 seconds
    $currentTime = time();
    
    if (!isset($_SESSION['rate_limit_requests'])) {
        $_SESSION['rate_limit_requests'] = [];
    }
    
    // Filter out requests older than the time window
    $validRequests = array_filter($_SESSION['rate_limit_requests'], function($timestamp) use ($currentTime, $timeWindow) {
        return ($currentTime - $timestamp) < $timeWindow;
    });
    
    $_SESSION['rate_limit_requests'] = $validRequests;
    
    if (count($_SESSION['rate_limit_requests']) >= $limit) {
        http_response_code(429);
        die("429 Too Many Requests. Please try again later.");
    }
    
    $_SESSION['rate_limit_requests'][] = $currentTime;
}

// Call checkRateLimit on inclusion
checkRateLimit();
?>

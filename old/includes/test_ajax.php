<?php
// Test script to check AJAX handler functionality
header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'AJAX test successful',
    'time' => date('Y-m-d H:i:s')
]);
?> 
<?php
/**
 * submit_report.php
 * Receives a JSON report from support.html and appends it to reports.json
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = file_get_contents('php://input');
$report = json_decode($input, true);

if (!$report || empty($report['name']) || empty($report['email']) || empty($report['category']) || empty($report['description'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Sanitize
$report['name']        = htmlspecialchars($report['name'], ENT_QUOTES, 'UTF-8');
$report['email']       = filter_var($report['email'], FILTER_SANITIZE_EMAIL);
$report['category']    = htmlspecialchars($report['category'], ENT_QUOTES, 'UTF-8');
$report['description'] = htmlspecialchars($report['description'], ENT_QUOTES, 'UTF-8');

// Validate email
if (!filter_var($report['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email address']);
    exit;
}

$reportsFile = __DIR__ . '/reports.json';

// Load existing reports
$reports = [];
if (file_exists($reportsFile)) {
    $existing = file_get_contents($reportsFile);
    $reports = json_decode($existing, true) ?? [];
}

// Append new report
$reports[] = $report;

// Save
if (file_put_contents($reportsFile, json_encode($reports, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Could not save report. Check file permissions.']);
    exit;
}

http_response_code(200);
echo json_encode(['success' => true, 'id' => $report['id']]);

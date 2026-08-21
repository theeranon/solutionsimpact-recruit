<?php
require_once 'auth.php';
requireLogin();

$id = $_GET['id'] ?? null;

if ($id === null || !is_numeric($id)) {
    die("Invalid request. <a href='index.php'>Go back</a>");
}

function getCandidateDetails($rowIndex) {
    $url = GSHEET_CSV_URL;
    $context = stream_context_create([
        "http" => [
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)"
        ]
    ]);
    
    $csvContent = @file_get_contents($url, false, $context);
    if ($csvContent === false) {
        return false;
    }
    
    $lines = explode("\n", $csvContent);
    
    if (!isset($lines[0]) || !isset($lines[$rowIndex])) {
        return null;
    }
    
    $headers = str_getcsv($lines[0]);
    $data = str_getcsv($lines[$rowIndex]);
    
    return ['headers' => $headers, 'data' => $data];
}

$details = getCandidateDetails($id);
$error = false;

if ($details === false) {
    $error = "Failed to load data from Google Sheets.";
} elseif ($details === null) {
    $error = "Candidate not found.";
} else {
    $headers = $details['headers'];
    $data = $details['data'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Details - SolutionsIMPACT Recruit</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <h1>SolutionsIMPACT Recruit</h1>
        <div class="nav-links">
            <a href="index.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <a href="index.php" class="back-link">&larr; Back to Dashboard</a>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php else: ?>
            <div class="details-card">
                <h2 style="margin-bottom: 1.5rem; color: var(--primary-color);">Candidate Details</h2>
                
                <?php 
                $maxCols = max(count($headers), count($data));
                for ($i = 0; $i < $maxCols; $i++): 
                    $headerName = isset($headers[$i]) && trim($headers[$i]) !== '' ? $headers[$i] : "Column " . ($i + 1);
                    
                    // Fallback header name if empty
                    if (!isset($headers[$i]) || trim($headers[$i]) === '') {
                        // Just generate A, B, C etc roughly or Column number
                        $headerName = "Column " . ($i + 1);
                    }
                    
                    $value = $data[$i] ?? '';
                    if (trim($value) === '') continue; // Skip empty answers to make it clean
                ?>
                    <div class="detail-row">
                        <div class="detail-label"><?php echo htmlspecialchars($headerName); ?></div>
                        <div class="detail-value"><?php echo nl2br(htmlspecialchars($value)); ?></div>
                    </div>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

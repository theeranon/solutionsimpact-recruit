<?php
require_once 'auth.php';
requireLogin();

// Function to fetch and parse CSV
function getSheetData() {
    $url = GSHEET_CSV_URL;
    $data = [];
    
    // Attempt to fetch the CSV content
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
    $headers = [];
    
    foreach ($lines as $index => $line) {
        if (trim($line) === '') continue;
        
        $row = str_getcsv($line);
        if ($index === 0) {
            $headers = $row;
        } else {
            // Append original row index so we can link to details
            $row['original_index'] = $index;
            $data[] = $row;
        }
    }
    
    return ['headers' => $headers, 'data' => $data];
}

$sheetData = getSheetData();
$error = false;
$headers = [];
$candidates = [];

if ($sheetData === false) {
    $error = "Failed to load data. Ensure the Google Sheet is published to the web as a CSV.";
} else {
    $headers = $sheetData['headers'];
    $candidates = $sheetData['data'];
    
    // Sort by Column A (Timestamp), assuming Column A is index 0
    usort($candidates, function($a, $b) {
        $timeA = strtotime($a[0] ?? 0);
        $timeB = strtotime($b[0] ?? 0);
        return $timeB <=> $timeA; // Descending
    });
    
    // Basic search filtering
    $search = $_GET['q'] ?? '';
    if ($search !== '') {
        $searchLower = strtolower($search);
        $candidates = array_filter($candidates, function($row) use ($searchLower) {
            foreach ($row as $col) {
                if (is_string($col) && strpos(strtolower($col), $searchLower) !== false) {
                    return true;
                }
            }
            return false;
        });
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SolutionsIMPACT Recruit</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <h1>SolutionsIMPACT Recruit</h1>
        <div class="nav-links">
            <span>Welcome, <?php echo htmlspecialchars(APP_USERNAME); ?></span>
            <a href="logout.php">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php else: ?>
            <div class="search-bar">
                <form method="GET" action="" style="display: flex; width: 100%; gap: 1rem;">
                    <input type="text" name="q" placeholder="Search by name, email, phone, etc..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn" style="width: auto;">Search</button>
                    <?php if ($search): ?>
                        <a href="index.php" class="btn" style="width: auto; background: #6c757d;">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <!-- Adjust these based on the actual columns in your sheet -->
                            <th>Name / Info 1</th>
                            <th>Contact / Info 2</th>
                            <th>Info 3</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($candidates) > 0): ?>
                            <?php foreach ($candidates as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row[0] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row[1] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row[2] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row[3] ?? 'N/A'); ?></td>
                                    <td>
                                        <a href="details.php?id=<?php echo $row['original_index']; ?>" class="action-link">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No candidates found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

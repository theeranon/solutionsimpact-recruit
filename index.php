<?php
require_once 'auth.php';
require_once 'helper.php';
requireLogin();

// Function to fetch and parse CSV
function getSheetData() {
    $url = GSHEET_CSV_URL;
    $data = [];
    
    $csvContent = fetchCsvUrl($url);
    if ($csvContent === false) {
        return false;
    }
    
    if ($csvContent === 'auth_required') {
        return 'auth_required';
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
    $error = "Failed to load data. The Google Sheet URL might be incorrect or unreachable.";
} elseif ($sheetData === 'auth_required') {
    $error = "Google Sheet is NOT public! <br><br>Please go to your Google Sheet -> Share -> change to 'Anyone with the link' can view.<br>Alternatively, use File -> Share -> Publish to web as CSV.";
} else {
    $headers = $sheetData['headers'];
    $candidates = $sheetData['data'];
    
    // Sort by Column C (Timestamp), assuming Column C is index 2
    usort($candidates, function($a, $b) {
        $timeA = strtotime($a[2] ?? 0);
        $timeB = strtotime($b[2] ?? 0);
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
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
            <a href="logout.php">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
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
                            <th>Name</th>
                            <th>Position Interested</th>
                            <th>Contact</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($candidates) > 0): ?>
                            <?php foreach ($candidates as $row): ?>
                                <tr>
                                    <!-- [2] = Timestamp, [23] = Name, [3] = Position, [25] = Email, [24] = Phone -->
                                    <td><?php echo htmlspecialchars($row[2] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($row[23] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row[3] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($row[25] ?? ''); ?><br>
                                        <small style="color: #666;"><?php echo htmlspecialchars($row[24] ?? ''); ?></small>
                                    </td>
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

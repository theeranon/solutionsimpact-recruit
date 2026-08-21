<?php
require_once 'auth.php';
require_once 'helper.php';
requireLogin();

function getSheetData() {
    $url = GSHEET_CSV_URL;
    $csvContent = fetchCsvUrl($url);
    if ($csvContent === false) return false;
    if ($csvContent === 'auth_required') return 'auth_required';
    
    $lines = explode("\n", $csvContent);
    $headers = [];
    $data = [];
    
    foreach ($lines as $index => $line) {
        if (trim($line) === '') continue;
        $row = str_getcsv($line);
        if ($index === 0) {
            $headers = $row;
        } else {
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
}

// Ensure headers are clean
$cleanHeaders = array_map(function($h, $i) {
    return trim($h) !== '' ? trim($h) : "Column " . ($i + 1);
}, $headers, array_keys($headers));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SolutionsIMPACT Recruit</title>
    <link rel="stylesheet" href="style.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    
    <!-- jQuery and DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    
    <style>
        .dataTables_wrapper .dataTables_filter input {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid var(--border-color);
            margin-bottom: 10px;
        }
        .dataTables_wrapper .dataTables_length select {
            padding: 4px;
            border-radius: 4px;
        }
        /* Custom Button Style */
        div.dt-buttons { margin-bottom: 15px; }
        .dt-button {
            background: var(--primary-color) !important;
            color: white !important;
            border: none !important;
            border-radius: 4px !important;
            padding: 6px 12px !important;
        }
        .dt-button:hover { background: var(--primary-hover) !important; }
        table.dataTable thead th { background-color: #f1f3f5; font-weight: 600; }
        
        /* Ensure table is readable */
        table.dataTable tbody td {
            vertical-align: top;
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
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
            
            <div class="table-responsive" style="background: var(--card-bg); padding: 1rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <table id="recruitTable" class="display responsive nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <?php foreach ($cleanHeaders as $colIndex => $th): ?>
                                <th><?php echo htmlspecialchars($th); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($candidates as $row): ?>
                            <tr>
                                <td>
                                    <a href="details.php?id=<?php echo $row['original_index']; ?>" class="action-link" style="background: var(--primary-color); color: white; padding: 4px 10px; border-radius: 4px; text-decoration: none; font-size: 0.9rem;">View</a>
                                </td>
                                <?php foreach ($cleanHeaders as $colIndex => $th): ?>
                                    <td title="<?php echo htmlspecialchars($row[$colIndex] ?? ''); ?>">
                                        <?php echo htmlspecialchars($row[$colIndex] ?? ''); ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <script>
            $(document).ready(function() {
                var table = $('#recruitTable').DataTable({
                    dom: 'Bfrtip',
                    responsive: false, // Turn off responsive collapsing so scrollX works better for many columns
                    scrollX: true,
                    pageLength: 25,
                    order: [[3, 'desc']], // Column 3 is 'Submitted at' (Index 2 in CSV + 1 for Action col)
                    buttons: [
                        {
                            extend: 'colvis',
                            text: 'Columns 👁️',
                            className: 'btn-colvis'
                        }
                    ],
                    columnDefs: [
                        { targets: 0, orderable: false }, // Action
                        // Hide most columns by default, show only essential ones
                        // Col 0: Action, Col 1: Submission ID, Col 2: Respondent ID, Col 3: Submitted at, Col 4: ตำแหน่ง..., Col 23: ชื่อเล่น, Col 24: ชื่อ-นามสกุล, Col 25: เบอร์โทร, Col 26: Email
                        // Let's set targets that we WANT visible, and hide the rest
                        {
                            targets: '_all',
                            visible: false
                        },
                        {
                            targets: [0, 3, 23, 24, 25, 26, 28, 29, 32, 42, 43], // Based on user's header structure (adding 1 because of Action column at index 0)
                            // 0=Action, 3=Submitted at, 23=ชื่อเล่น, 24=ชื่อ-นามสกุล, 25=เบอร์โทร, 26=Email, 28=วุฒิการศึกษา, 32=เป้าหมาย 3 ปี, 42=คาดหวัง, 43=เงินเดือนที่รับได้
                            visible: true
                        }
                    ],
                    language: {
                        search: "Search all columns:",
                    }
                });
            });
            </script>
        <?php endif; ?>
    </div>
</body>
</html>

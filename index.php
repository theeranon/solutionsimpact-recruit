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
    
    <!-- jQuery and DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    
    <style>
        .container {
            max-width: 98%;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .table-responsive {
            background: var(--card-bg);
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            overflow-x: auto;
        }

        /* Excel-like Table Styling */
        table.dataTable {
            border-collapse: collapse !important;
            width: 100% !important;
        }
        table.dataTable thead th {
            background-color: #f1f3f5;
            font-weight: 600;
            border: 1px solid #dee2e6;
            padding: 10px;
            white-space: nowrap;
        }
        table.dataTable tbody td {
            border: 1px solid #dee2e6;
            padding: 8px 10px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 250px; /* Prevent columns from getting too wide */
            vertical-align: middle;
            height: 40px; /* Lock row height */
        }
        table.dataTable tbody tr:hover {
            background-color: #f8f9fa;
        }

        .dataTables_wrapper .dataTables_filter input {
            padding: 6px;
            border-radius: 4px;
            border: 1px solid var(--border-color);
            margin-bottom: 10px;
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
            <div class="table-responsive">
                <table id="recruitTable" class="display nowrap">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Action</th>
                            <th>Submitted at</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>ชื่อเล่น</th>
                            <th>Email</th>
                            <th>เบอร์โทร</th>
                            <th>วุฒิการศึกษา</th>
                            <th>เงินเดือนที่คาดหวัง</th>
                            <th>เงินเดือนรับได้ต่ำสุด</th>
                            <th>เป้าหมายใน 3 ปี</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($candidates as $row): ?>
                            <tr>
                                <td>
                                    <a href="details.php?id=<?php echo $row['original_index']; ?>" class="action-link" style="background: var(--primary-color); color: white; padding: 4px 10px; border-radius: 4px; text-decoration: none; font-size: 0.85rem; display: inline-block; text-align: center; width: 100%;">View</a>
                                </td>
                                <td><?php echo htmlspecialchars($row[2] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row[23] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row[22] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row[25] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row[24] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row[27] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row[42] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row[43] ?? '-'); ?></td>
                                <td title="<?php echo htmlspecialchars($row[31] ?? ''); ?>"><?php echo htmlspecialchars($row[31] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <script>
            $(document).ready(function() {
                $('#recruitTable').DataTable({
                    scrollX: true, // Allow horizontal scroll if needed
                    pageLength: 25,
                    order: [[1, 'desc']], // Sort by Submitted at (Column index 1)
                    language: {
                        search: "ค้นหาข้อมูลทั้งหมด:",
                    },
                    columnDefs: [
                        { targets: 0, orderable: false } // Disable sorting on Action column
                    ]
                });
            });
            </script>
        <?php endif; ?>
    </div>
</body>
</html>

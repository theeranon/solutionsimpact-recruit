<?php
require_once 'auth.php';
require_once 'helper.php';
requireLogin();

$id = $_GET['id'] ?? null;

if ($id === null || !is_numeric($id)) {
    die("Invalid request. <a href='index.php'>Go back</a>");
}

function getCandidateDetails($rowIndex) {
    $url = GSHEET_CSV_URL;
    $csvContent = fetchCsvUrl($url);
    if ($csvContent === false || $csvContent === 'auth_required') {
        return false;
    }
    
    $stream = fopen('php://memory', 'r+');
    fwrite($stream, $csvContent);
    rewind($stream);
    
    $headers = [];
    $targetData = null;
    $currentIndex = 0;
    
    while (($row = fgetcsv($stream)) !== false) {
        if (count($row) === 1 && $row[0] === null) continue;
        
        if ($currentIndex === 0) {
            $headers = $row;
        } elseif ($currentIndex == $rowIndex) {
            $targetData = $row;
            break; 
        }
        $currentIndex++;
    }
    
    fclose($stream);
    
    if ($targetData === null) {
        return null;
    }
    
    return ['headers' => $headers, 'data' => $targetData];
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
    
    $name = trim($data[23] ?? 'Candidate Details');
    $nickname = trim($data[22] ?? '');
    if ($nickname !== '') {
        $name .= " ($nickname)";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Details - SolutionsIMPACT</title>
    
    <!-- Google Fonts: IBM Plex Sans Thai -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'IBM Plex Sans Thai', sans-serif;
            background-color: #f4f6f9;
            color: #333;
        }
        .navbar-brand {
            font-weight: 700;
            color: #0d6efd !important;
            letter-spacing: -0.5px;
        }
        .info-label {
            font-size: 0.85rem;
            color: #8c98a4;
            font-weight: 600;
            margin-bottom: 0.3rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-value {
            font-size: 1.05rem;
            color: #1e2022;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #e7eaf3;
            font-weight: 700;
            font-size: 1.15rem;
            padding: 1.25rem 1.5rem;
            color: #1e2022;
        }
        .card {
            border: 1px solid #e7eaf3;
            box-shadow: 0 0.5rem 1.2rem rgba(189,197,209,.2);
            margin-bottom: 2rem;
            border-radius: 0.75rem;
        }
        .badge-position {
            background-color: #e1f0ff;
            color: #0d6efd;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 0.5rem 0.85rem;
            border-radius: 8px;
            margin: 0.25rem;
            display: inline-block;
            border: 1px solid #b6d4fe;
        }
        .qa-block {
            margin-bottom: 2rem;
        }
        .qa-question {
            font-weight: 700;
            color: #0d6efd;
            font-size: 1.05rem;
            margin-bottom: 0.75rem;
            line-height: 1.5;
            display: flex;
            align-items: flex-start;
        }
        .qa-question::before {
            content: "Q:";
            background: #0d6efd;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            margin-right: 10px;
            font-size: 0.9rem;
        }
        .qa-answer {
            background-color: #f8fafd;
            border-left: 4px solid #0d6efd;
            padding: 1.25rem;
            border-radius: 0 0.5rem 0.5rem 0;
            font-size: 1.05rem;
            color: #3b434f;
            line-height: 1.7;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
        <div class="container">
            <span class="navbar-brand">SolutionsIMPACT Recruit</span>
            <div class="d-flex align-items-center">
                <a href="index.php" class="btn btn-outline-secondary btn-sm me-2">Back to Dashboard</a>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container pb-5">
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php else: ?>
            
            <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-4 rounded shadow-sm border">
                <div>
                    <h2 class="fw-bold mb-1 text-primary"><?php echo htmlspecialchars($name); ?></h2>
                    <p class="text-muted mb-0" style="font-size: 0.95rem;">Submitted at: <span class="text-dark fw-medium"><?php echo htmlspecialchars($data[2] ?? '-'); ?></span></p>
                </div>
                <div class="d-flex gap-2">
                    <?php if(!empty($data[28])): ?>
                        <a href="<?php echo htmlspecialchars($data[28]); ?>" target="_blank" class="btn btn-primary px-4 fw-medium shadow-sm">📄 View Resume</a>
                    <?php endif; ?>
                    <?php if(!empty($data[29])): ?>
                        <a href="<?php echo htmlspecialchars($data[29]); ?>" target="_blank" class="btn btn-dark px-4 fw-medium shadow-sm">🔗 View Portfolio</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row">
                <!-- Left Column -->
                <div class="col-lg-4">
                    
                    <div class="card">
                        <div class="card-header">
                            ข้อมูลส่วนตัว (Personal Info)
                        </div>
                        <div class="card-body p-4">
                            <div class="info-label">เบอร์โทรศัพท์</div>
                            <div class="info-value fw-bold text-primary"><a href="tel:<?php echo htmlspecialchars($data[24] ?? ''); ?>" class="text-decoration-none"><?php echo htmlspecialchars($data[24] ?? '-'); ?></a></div>
                            
                            <div class="info-label">อีเมล</div>
                            <div class="info-value"><a href="mailto:<?php echo htmlspecialchars($data[25] ?? ''); ?>" class="text-decoration-none text-dark"><?php echo htmlspecialchars($data[25] ?? '-'); ?></a></div>
                            
                            <div class="info-label">วันเดือนปีเกิด</div>
                            <div class="info-value"><?php echo htmlspecialchars($data[26] ?? '-'); ?></div>
                            
                            <div class="info-label">วุฒิการศึกษา</div>
                            <div class="info-value mb-0"><?php echo htmlspecialchars($data[27] ?? '-'); ?></div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            การสมัครงาน (Job Application)
                        </div>
                        <div class="card-body p-4">
                            <div class="info-label">ตำแหน่งที่สนใจ</div>
                            <div class="mb-4 mt-2">
                                <?php 
                                $hasPosition = false;
                                for ($i = 3; $i <= 20; $i++) {
                                    $val = trim($data[$i] ?? '');
                                    if ($val === 'TRUE' || ($val !== '' && $val !== 'FALSE')) {
                                        $posName = $headers[$i] ?? "Position $i";
                                        $posName = str_replace('ตำแหน่งที่สนใจ (สามารถสนใจหลายตำแหน่งได้) (', '', $posName);
                                        $posName = str_replace('ตำแหน่งที่สนใจ (สามารถสนใจหลายตำแหน่งได้)', '', $posName);
                                        $posName = str_replace(')', '', $posName);
                                        $posName = trim($posName);
                                        if ($posName === '') {
                                            $posName = $val; // fallback to the value itself if header was just the root question
                                        }
                                        if ($posName !== 'TRUE') {
                                            echo '<span class="badge-position">' . htmlspecialchars($posName) . '</span>';
                                            $hasPosition = true;
                                        }
                                    }
                                }
                                if (!$hasPosition) {
                                    echo "-";
                                }
                                ?>
                            </div>
                            
                            <div class="info-label">ทำไมถึงสนใจตำแหน่งที่เลือก</div>
                            <div class="info-value bg-light p-3 rounded" style="font-size: 0.95rem; white-space: pre-wrap;"><?php echo htmlspecialchars($data[21] ?? '-'); ?></div>
                            
                            <div class="info-label">เงินเดือนที่คาดหวัง</div>
                            <div class="info-value text-success fw-bold fs-5"><?php echo htmlspecialchars($data[42] ?? '-'); ?></div>
                            
                            <div class="info-label">เงินเดือนเริ่มต้นที่รับได้</div>
                            <div class="info-value text-danger fw-bold fs-5 mb-0"><?php echo htmlspecialchars($data[43] ?? '-'); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>ทัศนคติและเป้าหมาย (Attitude & Mindset)</span>
                            <span class="badge bg-primary rounded-pill px-3 py-2">Interview Q&A</span>
                        </div>
                        <div class="card-body p-4 p-md-5">
                            <?php 
                            $mindsetCols = [
                                30 => "คุณคิดว่าคุณเก่งอะไร หรือ ภูมิใจอะไร 3 เรื่อง เล่าเหตุการณ์ให้ฟังหน่อย",
                                37 => "ข้อดีของคุณในมุมมองของคุณคือ",
                                32 => "ข้อเสียของคุณในมุมมองของคุณคือ",
                                31 => "เป้าหมายของคุณในอีก 3 ปีข้างหน้า",
                                33 => "นิยามของคำว่า WORK LIFE BALANCE ใกล้เคียงข้อใดที่สุด",
                                34 => "คุณรู้สึกอย่างไร เมื่อหัวหน้าที่ให้งานคุณเยอะเกินไป",
                                35 => "สถานการณ์แบบใดมีโอกาสที่อาจทำให้คุณ Burnout",
                                36 => "ชอบ Lifestyle การทำงานแบบไหน เพราะอะไร",
                                38 => "เมื่อทำงานกับทีม คุณจะรู้สึกสนุกที่จะทำหน้าที่อะไรมากที่สุด",
                                39 => "คุณทำอย่างไรเมื่อต้องตัดสินใจในเรื่องสำคัญ",
                                40 => "ถ้าเลือกได้ คุณอยากได้หัวหน้าที่มีความเชื่อแบบไหน",
                                41 => "คาดหวังอะไรจากการทำงานที่นี่"
                            ];
                            
                            foreach ($mindsetCols as $idx => $title): 
                                $val = trim($data[$idx] ?? '');
                                if ($val !== ''):
                            ?>
                                <div class="qa-block">
                                    <div class="qa-question"><?php echo htmlspecialchars($title); ?></div>
                                    <div class="qa-answer"><?php echo htmlspecialchars($val); ?></div>
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                    
                    <!-- Fallback for any missed columns -->
                    <?php
                    $handledCols = array_merge(range(0,2), range(3,20), range(21, 29), array_keys($mindsetCols), [42, 43]);
                    $unhandledCols = [];
                    $maxCols = max(count($headers), count($data));
                    for ($i = 0; $i < $maxCols; $i++) {
                        if (!in_array($i, $handledCols) && trim($data[$i] ?? '') !== '') {
                            $unhandledCols[$i] = [
                                'header' => $headers[$i] ?? "Column " . ($i + 1),
                                'val' => $data[$i]
                            ];
                        }
                    }
                    if (count($unhandledCols) > 0):
                    ?>
                        <div class="card mt-4">
                            <div class="card-header text-muted">
                                ข้อมูลเพิ่มเติม (Additional Data)
                            </div>
                            <div class="card-body p-4">
                                <?php foreach ($unhandledCols as $col): ?>
                                    <div class="qa-block">
                                        <div class="qa-question text-secondary"><?php echo htmlspecialchars($col['header']); ?></div>
                                        <div class="qa-answer border-secondary text-muted"><?php echo htmlspecialchars($col['val']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php endif; ?>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

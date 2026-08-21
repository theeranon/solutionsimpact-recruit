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
    <link rel="stylesheet" href="style.css">
    <style>
        .details-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }
        @media (min-width: 900px) {
            .details-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        .section-card {
            background: #fff;
            padding: 2rem;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            margin-bottom: 2rem;
        }
        .section-card h3 {
            margin-bottom: 1.5rem;
            color: #212529;
            font-size: 1.25rem;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0.75rem;
        }
        .info-group {
            margin-bottom: 1.25rem;
        }
        .info-label {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 600;
            margin-bottom: 0.4rem;
        }
        .info-value {
            font-size: 1rem;
            color: #212529;
            word-break: break-word;
            line-height: 1.6;
        }
        .info-value p {
            margin-bottom: 0;
        }
        .header-banner {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #212529;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-banner h2 { 
            margin: 0 0 0.5rem 0; 
            font-size: 1.75rem; 
        }
        .header-banner p { 
            margin: 0; 
            color: #6c757d; 
        }
        .link-button {
            display: inline-block;
            background: #e9ecef;
            color: #212529;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9rem;
            border: 1px solid #ced4da;
            margin-right: 0.5rem;
            margin-top: 0.5rem;
        }
        .link-button:hover { 
            background: #dde2e6; 
        }
        .position-tag {
            display: inline-block;
            background: #f1f3f5;
            color: #495057;
            padding: 6px 12px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            margin: 4px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>SolutionsIMPACT Recruit</h1>
        <div class="nav-links">
            <a href="index.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>
    
    <div class="container" style="max-width: 1200px;">
        <a href="index.php" class="back-link" style="margin-bottom: 1.5rem; display:inline-block;">&larr; Back to Dashboard</a>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php else: ?>
            
            <div class="header-banner">
                <div>
                    <h2><?php echo htmlspecialchars($name); ?></h2>
                    <p>Submitted at: <?php echo htmlspecialchars($data[2] ?? '-'); ?> | ID: <?php echo htmlspecialchars($data[0] ?? '-'); ?></p>
                </div>
                <div>
                    <?php if(!empty($data[28])): ?>
                        <a href="<?php echo htmlspecialchars($data[28]); ?>" target="_blank" class="link-button">View Resume/File</a>
                    <?php endif; ?>
                    <?php if(!empty($data[29])): ?>
                        <a href="<?php echo htmlspecialchars($data[29]); ?>" target="_blank" class="link-button">View Portfolio Link</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="details-grid">
                <!-- Left Column -->
                <div>
                    <div class="section-card">
                        <h3>ข้อมูลส่วนตัว (Personal Info)</h3>
                        <div class="info-group">
                            <div class="info-label">เบอร์โทรศัพท์</div>
                            <div class="info-value"><?php echo htmlspecialchars($data[24] ?? '-'); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">อีเมล</div>
                            <div class="info-value"><?php echo htmlspecialchars($data[25] ?? '-'); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">วันเดือนปีเกิด</div>
                            <div class="info-value"><?php echo htmlspecialchars($data[26] ?? '-'); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">วุฒิการศึกษา</div>
                            <div class="info-value"><?php echo htmlspecialchars($data[27] ?? '-'); ?></div>
                        </div>
                    </div>

                    <div class="section-card">
                        <h3>การสมัครงาน (Job Application)</h3>
                        <div class="info-group">
                            <div class="info-label">ตำแหน่งที่สนใจ</div>
                            <div class="info-value" style="margin-top: 0.5rem;">
                                <?php 
                                $hasPosition = false;
                                for ($i = 3; $i <= 20; $i++) {
                                    $val = trim($data[$i] ?? '');
                                    if ($val === 'TRUE' || ($val !== '' && $val !== 'FALSE')) {
                                        $posName = $headers[$i] ?? "Position $i";
                                        $posName = str_replace('ตำแหน่งที่สนใจ (สามารถสนใจหลายตำแหน่งได้) (', '', $posName);
                                        $posName = str_replace(')', '', $posName);
                                        echo '<span class="position-tag">' . htmlspecialchars(trim($posName)) . '</span>';
                                        $hasPosition = true;
                                    }
                                }
                                if (!$hasPosition) {
                                    echo "-";
                                }
                                ?>
                            </div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">ทำไมถึงสนใจตำแหน่งที่เลือก</div>
                            <div class="info-value"><?php echo nl2br(htmlspecialchars($data[21] ?? '-')); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">เงินเดือนที่คาดหวัง</div>
                            <div class="info-value"><?php echo htmlspecialchars($data[42] ?? '-'); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">เงินเดือนเริ่มต้นที่รับได้</div>
                            <div class="info-value" style="font-weight: 600;"><?php echo htmlspecialchars($data[43] ?? '-'); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div>
                    <div class="section-card">
                        <h3>ทัศนคติและเป้าหมาย (Attitude & Mindset)</h3>
                        <?php 
                        $mindsetCols = [
                            30 => "คุณคิดว่าคุณเก่งอะไร หรือ ภูมิใจอะไร 3 เรื่อง เล่าเหตุการณ์ให้ฟังหน่อย",
                            37 => "ข้อดีของคุณในมุมมองของคุณคือ",
                            32 => "ข้อเสียของคุณในมุมมองของคุณคือ",
                            31 => "เป้าหมายของคุณในอีก 3 ปีข้างหน้า",
                            33 => "นิยามของคำว่า WORK LIFE BALANCE ใกล้เคียงข้อใดที่สุด",
                            34 => "คุณรู้สึกอย่างไร เมื่อหัวหน้าที่ให้งานคุณเยอะเกินไป",
                            35 => "สถานการณ์แบบใดมีโอกาสที่อาจทำให้คุณ Burnout",
                            36 => "ชอบ Lifestyle การทำงานแบบไหน (WFA 100% / Hybrid / เข้า Office) เพราะอะไร",
                            38 => "เมื่อทำงานกับทีม คุณจะรู้สึกสนุกที่จะทำหน้าที่อะไรมากที่สุด",
                            39 => "คุณทำอย่างไรเมื่อต้องตัดสินใจในเรื่องสำคัญ",
                            40 => "ถ้าเลือกได้ คุณอยากได้หัวหน้าที่มีความเชื่อแบบไหน",
                            41 => "คาดหวังอะไรจากการทำงานที่นี่"
                        ];
                        
                        foreach ($mindsetCols as $idx => $title): 
                            $val = trim($data[$idx] ?? '');
                            if ($val !== ''):
                        ?>
                            <div class="info-group">
                                <div class="info-label"><?php echo htmlspecialchars($title); ?></div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($val)); ?></div>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
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
                <div class="section-card">
                    <h3>ข้อมูลเพิ่มเติม (Additional Data)</h3>
                    <?php foreach ($unhandledCols as $col): ?>
                        <div class="info-group">
                            <div class="info-label"><?php echo htmlspecialchars($col['header']); ?></div>
                            <div class="info-value"><?php echo nl2br(htmlspecialchars($col['val'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</body>
</html>

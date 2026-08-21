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
            break; // Found our target row
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
    
    $name = $data[23] ?? 'Candidate Details'; // ชื่อ-นามสกุล
    $nickname = $data[22] ?? '';
    if ($nickname) {
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
        @media (min-width: 768px) {
            .details-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        .section-card {
            background: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            border-top: 4px solid var(--primary-color);
        }
        .section-card h3 {
            margin-bottom: 1rem;
            color: var(--primary-color);
            font-size: 1.2rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 0.5rem;
        }
        .info-group {
            margin-bottom: 1rem;
        }
        .info-label {
            font-size: 0.85rem;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.2rem;
        }
        .info-value {
            font-size: 1rem;
            color: #212529;
            word-break: break-word;
        }
        .badge {
            display: inline-block;
            background: #e9ecef;
            color: #495057;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.85rem;
            margin: 2px;
        }
        .badge-primary {
            background: #cfe2ff;
            color: #084298;
        }
        .header-banner {
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            color: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
        }
        .header-banner h2 { margin: 0 0 0.5rem 0; font-size: 2rem; }
        .header-banner p { margin: 0; opacity: 0.9; }
        .link-button {
            display: inline-block;
            background: var(--primary-color);
            color: white;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        .link-button:hover { background: var(--primary-hover); }
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
    
    <div class="container">
        <a href="index.php" class="back-link" style="margin-bottom: 1.5rem; display:inline-block;">&larr; Back to Dashboard</a>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php else: ?>
            
            <div class="header-banner">
                <h2><?php echo htmlspecialchars($name); ?></h2>
                <p>Submitted: <?php echo htmlspecialchars($data[2] ?? '-'); ?></p>
            </div>

            <div class="details-grid">
                <!-- Column 1 -->
                <div>
                    <div class="section-card">
                        <h3>Personal & Contact Info</h3>
                        <div class="info-group">
                            <div class="info-label">Phone</div>
                            <div class="info-value"><a href="tel:<?php echo htmlspecialchars($data[24] ?? ''); ?>"><?php echo htmlspecialchars($data[24] ?? '-'); ?></a></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Email</div>
                            <div class="info-value"><a href="mailto:<?php echo htmlspecialchars($data[25] ?? ''); ?>"><?php echo htmlspecialchars($data[25] ?? '-'); ?></a></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Date of Birth</div>
                            <div class="info-value"><?php echo htmlspecialchars($data[26] ?? '-'); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Education</div>
                            <div class="info-value"><?php echo htmlspecialchars($data[27] ?? '-'); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Salary Expectation</div>
                            <div class="info-value">
                                Expected: <?php echo htmlspecialchars($data[42] ?? '-'); ?> <br>
                                Minimum: <span style="color: #dc3545; font-weight: bold;"><?php echo htmlspecialchars($data[43] ?? '-'); ?></span>
                            </div>
                        </div>
                        
                        <?php if(!empty($data[28]) || !empty($data[29])): ?>
                        <div class="info-group">
                            <div class="info-label">Resume / Portfolio</div>
                            <div class="info-value">
                                <?php if(!empty($data[28])): ?>
                                    <a href="<?php echo htmlspecialchars($data[28]); ?>" target="_blank" class="link-button">View File</a>
                                <?php endif; ?>
                                <?php if(!empty($data[29])): ?>
                                    <a href="<?php echo htmlspecialchars($data[29]); ?>" target="_blank" class="link-button" style="background:#198754;">View Link</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="section-card">
                        <h3>Positions Interested</h3>
                        <div class="info-value">
                            <?php 
                            // Render positions (Col 3 to 20)
                            for ($i = 3; $i <= 20; $i++) {
                                if (isset($data[$i]) && (trim($data[$i]) === 'TRUE' || (trim($data[$i]) !== '' && trim($data[$i]) !== 'FALSE'))) {
                                    $posName = $headers[$i] ?? "Position $i";
                                    $posName = str_replace('ตำแหน่งที่สนใจ (สามารถสนใจหลายตำแหน่งได้) (', '', $posName);
                                    $posName = str_replace(')', '', $posName);
                                    echo '<span class="badge badge-primary">' . htmlspecialchars(trim($posName)) . '</span> ';
                                }
                            }
                            ?>
                        </div>
                        <div class="info-group" style="margin-top: 1rem;">
                            <div class="info-label">Why interested?</div>
                            <div class="info-value"><?php echo nl2br(htmlspecialchars($data[21] ?? '-')); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Column 2 -->
                <div>
                    <div class="section-card">
                        <h3>Attitude & Mindset</h3>
                        
                        <?php 
                        $mindsetCols = [
                            30 => "คุณคิดว่าคุณเก่งอะไร หรือ ภูมิใจอะไร 3 เรื่อง",
                            31 => "เป้าหมายของคุณในอีก 3 ปีข้างหน้า",
                            32 => "ข้อเสียของคุณในมุมมองของคุณคือ",
                            37 => "ข้อดีของคุณในมุมมองของคุณคือ",
                            33 => "นิยามของคำว่า WORK LIFE BALANCE ใกล้เคียงข้อใดที่สุด",
                            34 => "คุณรู้สึกอย่างไร เมื่อหัวหน้าที่ให้งานคุณเยอะเกินไป",
                            35 => "สถานการณ์แบบใดมีโอกาสที่อาจทำให้คุณ Burnout",
                            36 => "ชอบ Lifestyle การทำงานแบบไหน เพราะอะไร",
                            38 => "เมื่อทำงานกับทีม รู้สึกสนุกที่จะทำหน้าที่อะไรมากที่สุด",
                            39 => "คุณทำอย่างไรเมื่อต้องตัดสินใจในเรื่องสำคัญ",
                            40 => "อยากได้หัวหน้าที่มีความเชื่อแบบไหน",
                            41 => "คาดหวังอะไรจากการทำงานที่นี่"
                        ];
                        
                        foreach ($mindsetCols as $idx => $title): 
                            if (!empty($data[$idx])):
                        ?>
                            <div class="info-group">
                                <div class="info-label"><?php echo htmlspecialchars($title); ?></div>
                                <div class="info-value"><?php echo nl2br(htmlspecialchars($data[$idx])); ?></div>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- Raw Dump Section -->
            <div class="section-card" style="margin-top: 2rem;">
                <details>
                    <summary style="cursor: pointer; color: var(--primary-color); font-weight: 600;">Show All Raw Data (Advanced)</summary>
                    <div style="margin-top: 1rem;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                            <?php 
                            $maxCols = max(count($headers), count($data));
                            for ($i = 0; $i < $maxCols; $i++): 
                                $headerName = isset($headers[$i]) && trim($headers[$i]) !== '' ? $headers[$i] : "Column " . ($i + 1);
                                $value = $data[$i] ?? '';
                                if (trim($value) === '') continue;
                            ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 8px; width: 40%; font-weight: 500; color: #555;"><?php echo htmlspecialchars($headerName); ?></td>
                                    <td style="padding: 8px;"><?php echo nl2br(htmlspecialchars($value)); ?></td>
                                </tr>
                            <?php endfor; ?>
                        </table>
                    </div>
                </details>
            </div>

        <?php endif; ?>
    </div>
</body>
</html>

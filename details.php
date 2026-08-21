<?php
require_once 'auth.php';
require_once 'helper.php';
requireLogin();

$id = $_GET['id'] ?? null;
if ($id === null || !is_numeric($id)) die("Invalid request.");

function getCandidateDetails($rowIndex) {
    $url = GSHEET_CSV_URL;
    $csvContent = fetchCsvUrl($url);
    if ($csvContent === false || $csvContent === 'auth_required') return false;
    
    $stream = fopen('php://memory', 'r+');
    fwrite($stream, $csvContent);
    rewind($stream);
    
    $headers = []; $targetData = null; $currentIndex = 0;
    while (($row = fgetcsv($stream)) !== false) {
        if (count($row) === 1 && $row[0] === null) continue;
        if ($currentIndex === 0) $headers = $row;
        elseif ($currentIndex == $rowIndex) { $targetData = $row; break; }
        $currentIndex++;
    }
    fclose($stream);
    return $targetData === null ? null : ['headers' => $headers, 'data' => $targetData];
}

$details = getCandidateDetails($id);
$error = false;

if ($details === false) $error = "Failed to load data.";
elseif ($details === null) $error = "Candidate not found.";
else {
    $headers = $details['headers'];
    $data = $details['data'];
    $name = trim($data[23] ?? 'Candidate Details');
    $nickname = trim($data[22] ?? '');
    if ($nickname !== '') $name .= " ($nickname)";
}

$choiceQuestions = [
    33 => [
        "title" => "นิยามของคำว่า WORK LIFE BALANCE ใกล้เคียงข้อใดที่สุด",
        "choices" => [
            "ขึ้นอยู่กับวัฒนธรรมองค์กรและหัวหน้าที่เข้าใจ หากสภาพแวดล้อมเอื้อ ก็จะทำได้ง่ายขึ้น",
            "ทำงานตามกรอบเวลาแน่นอน เพื่อให้มีเวลาหลังเลิกงาน",
            "อดทนวันนี้เพื่อวันหน้า",
            "WORK = LIFE งานคือชีวิต"
        ]
    ],
    34 => [
        "title" => "คุณรู้สึกอย่างไร เมื่อหัวหน้าที่ให้งานคุณเยอะเกินไป",
        "choices" => [
            "ฉันทำให้ดีที่สุดเสมอ และหัวหน้าควรเข้าใจข้อจำกัดและช่วยจัดลำดับความสำคัญให้",
            "พร้อมทำงานที่ได้รับมอบหมายเต็มที่ และขอคำแนะนำจากหัวหน้า",
            "ตั้งเป้าจะทำงานให้เสร็จอย่างมีประสิทธิภาพ และเรียนรู้จากประสบการณ์ครั้งนี้",
            "ขอนัดหัวหน้าคุยเพื่อทำความเข้าใจ"
        ]
    ],
    35 => [
        "title" => "สถานการณ์แบบใดมีโอกาสที่อาจทำให้คุณ Burnout",
        "choices" => [
            "ไม่น่าจะเกิดขึ้นกับฉัน เพราะฉันสามารถจัดการตัวเองได้ดี",
            "อยู่ในสถานการณ์ที่มีแรงกดดันจากรอบด้าน",
            "เมื่อพยายามทำดีที่สุดแล้ว แต่มันไม่ก้าวหน้าไปไหนซักที",
            "เมื่อร่างกายต่อต้าน ไม่ไหวแล้ว"
        ]
    ],
    37 => [
        "title" => "ข้อดีของคุณในมุมมองของคุณคือ",
        "choices" => [
            "เป็นเสียงหัวเราะให้ผู้คน",
            "เป็นคนจริงจัง และละเอียดรอบคอบ",
            "เป็นคนช่างคิด ไอเดียบรรเจิด",
            "เป็นคนใจดี ใส่ใจดูแลผู้คน"
        ]
    ],
    38 => [
        "title" => "เมื่อทำงานกับทีม คุณจะรู้สึกสนุกที่จะทำหน้าที่อะไรมากที่สุด",
        "choices" => [
            "คนออกไอเดีย เสนอแนวคิด",
            "ประสานงานติดต่อผู้คน",
            "พร้อมสนับสนุน ว่าไงว่าตามกัน",
            "เก็บสถิติข้อมูล ออกกฎ"
        ]
    ],
    39 => [
        "title" => "คุณทำอย่างไรเมื่อต้องตัดสินใจในเรื่องสำคัญ",
        "choices" => [
            "ขอคำแนะนำจากเพื่อน ปรึกษาเพื่อน",
            "ดูว่าคนอื่นตัดสินใจอย่างไร",
            "พิจารณาอย่างละเอียดในทุกแง่มุม",
            "ใช้สัญชาตญาณของคุณ"
        ]
    ],
    40 => [
        "title" => "ถ้าเลือกได้ คุณอยากได้หัวหน้าที่มีความเชื่อแบบไหน",
        "choices" => [
            "ฝันให้ไกล ไปให้ถึง",
            "ทุกคนสำราญ งานสำเร็จ งานจบต้องฉลอง",
            "เรื่องงานกับเรื่องส่วนตัวแยกกันอย่างชัดเจน",
            "ใส่ใจ ดูแลทุกคน"
        ]
    ]
];

$textQuestions = [
    30 => "คุณคิดว่าคุณเก่งอะไร หรือ ภูมิใจอะไร 3 เรื่อง เล่าเหตุการณ์ให้ฟังหน่อย",
    32 => "ข้อเสียของคุณในมุมมองของคุณคือ",
    31 => "เป้าหมายของคุณในอีก 3 ปีข้างหน้า",
    36 => "ชอบ Lifestyle การทำงานแบบไหน เพราะอะไร",
    41 => "คาดหวังอะไรจากการทำงานที่นี่"
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Details - SolutionsIMPACT</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'IBM Plex Sans Thai', sans-serif; background: #f8f9fa; color: #212529; font-size: 0.95rem; }
        .card { border: 1px solid #dee2e6; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 1rem; border-radius: 6px; }
        .card-header { background: #fff; border-bottom: 1px solid #dee2e6; font-weight: 600; padding: 0.75rem 1rem; font-size: 1rem; }
        .card-body { padding: 1rem; }
        .label { font-size: 0.8rem; color: #6c757d; font-weight: 600; margin-bottom: 0.1rem; text-transform: uppercase; }
        .val { font-size: 0.95rem; margin-bottom: 0.75rem; font-weight: 500; }
        .badge-pos { background: #e9ecef; color: #212529; font-size: 0.85rem; padding: 4px 8px; border-radius: 4px; margin: 2px 2px 2px 0; display: inline-block; border: 1px solid #ced4da; }
        
        .choice-container { margin-bottom: 1.25rem; }
        .choice-q { font-weight: 600; margin-bottom: 0.5rem; font-size: 0.95rem; color: #0d6efd; }
        .choice-item { 
            padding: 6px 12px; 
            margin-bottom: 4px; 
            border-radius: 4px; 
            border: 1px solid #e9ecef; 
            background: #fff; 
            color: #adb5bd; 
            font-size: 0.9rem;
            display: flex;
            align-items: flex-start;
        }
        .choice-item.selected {
            background: #e8f4fd;
            border-color: #0d6efd;
            color: #0d6efd;
            font-weight: 600;
        }
        .choice-item .alpha { width: 24px; font-weight: 700; flex-shrink: 0; }
        
        .text-q { font-weight: 600; margin-bottom: 0.25rem; font-size: 0.95rem; color: #0d6efd; }
        .text-a { background: #fff; border: 1px solid #dee2e6; padding: 0.5rem 0.75rem; border-radius: 4px; font-size: 0.9rem; white-space: pre-wrap; margin-bottom: 1rem; }
        
        .compact-row { margin-bottom: 0; }
    </style>
</head>
<body>
    <div class="bg-white border-bottom mb-3 py-2 shadow-sm sticky-top">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <span class="fw-bold text-primary fs-5">SolutionsIMPACT</span>
            <div>
                <a href="index.php" class="btn btn-sm btn-outline-secondary">Back</a>
            </div>
        </div>
    </div>
    
    <div class="container-fluid pb-4">
        <?php if ($error): echo "<div class='alert alert-danger'>$error</div>"; else: ?>
            
            <div class="d-flex flex-wrap justify-content-between align-items-end mb-3">
                <div>
                    <h3 class="fw-bold mb-0"><?php echo htmlspecialchars($name); ?></h3>
                    <small class="text-muted">Submitted: <?php echo htmlspecialchars($data[2] ?? '-'); ?></small>
                </div>
                <div class="mt-2">
                    <?php if(!empty($data[28])): ?><a href="<?php echo htmlspecialchars($data[28]); ?>" target="_blank" class="btn btn-sm btn-primary">📄 Resume</a><?php endif; ?>
                    <?php if(!empty($data[29])): ?><a href="<?php echo htmlspecialchars($data[29]); ?>" target="_blank" class="btn btn-sm btn-dark">🔗 Portfolio</a><?php endif; ?>
                </div>
            </div>

            <div class="row g-3">
                <!-- Left: Info -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">ข้อมูลส่วนตัว & สมัครงาน</div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6"><div class="label">เบอร์โทร</div><div class="val"><?php echo htmlspecialchars($data[24] ?? '-'); ?></div></div>
                                <div class="col-6"><div class="label">อีเมล</div><div class="val text-truncate" title="<?php echo htmlspecialchars($data[25] ?? ''); ?>"><?php echo htmlspecialchars($data[25] ?? '-'); ?></div></div>
                                <div class="col-6"><div class="label">วันเกิด</div><div class="val"><?php echo htmlspecialchars($data[26] ?? '-'); ?></div></div>
                                <div class="col-6"><div class="label">วุฒิ</div><div class="val text-truncate"><?php echo htmlspecialchars($data[27] ?? '-'); ?></div></div>
                            </div>
                            <hr class="my-2">
                            <div class="label">ตำแหน่งที่สนใจ</div>
                            <div class="mb-2">
                                <?php 
                                $hasPos = false;
                                for ($i = 3; $i <= 20; $i++) {
                                    $val = trim($data[$i] ?? '');
                                    if ($val === 'TRUE' || ($val !== '' && $val !== 'FALSE')) {
                                        $p = $headers[$i] ?? "";
                                        $p = trim(str_replace(['ตำแหน่งที่สนใจ (สามารถสนใจหลายตำแหน่งได้)', '(', ')'], '', $p));
                                        if ($p === '') $p = $val;
                                        if ($p !== 'TRUE') { echo "<span class='badge-pos'>".htmlspecialchars($p)."</span>"; $hasPos = true; }
                                    }
                                }
                                if (!$hasPos) echo "-";
                                ?>
                            </div>
                            <div class="label">ทำไมถึงสนใจ</div>
                            <div class="val fs-6 text-muted" style="white-space: pre-wrap;"><?php echo htmlspecialchars($data[21] ?? '-'); ?></div>
                            <div class="row mt-2">
                                <div class="col-6"><div class="label">เงินเดือนคาดหวัง</div><div class="val text-success fw-bold"><?php echo htmlspecialchars($data[42] ?? '-'); ?></div></div>
                                <div class="col-6"><div class="label">รับได้ต่ำสุด</div><div class="val text-danger fw-bold"><?php echo htmlspecialchars($data[43] ?? '-'); ?></div></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Middle: Choice Mindset -->
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header">ทัศนคติ (Choice)</div>
                        <div class="card-body p-3" style="max-height: 80vh; overflow-y: auto;">
                            <?php foreach ($choiceQuestions as $idx => $q): 
                                $answer = trim($data[$idx] ?? '');
                                if ($answer === '') continue;
                            ?>
                                <div class="choice-container">
                                    <div class="choice-q"><?php echo htmlspecialchars($q['title']); ?></div>
                                    <?php 
                                    $alphas = ['A', 'B', 'C', 'D'];
                                    $matched = false;
                                    foreach ($q['choices'] as $cIdx => $choiceText) {
                                        $isSelected = (strpos($answer, $choiceText) !== false || strpos($choiceText, $answer) !== false);
                                        if ($isSelected) $matched = true;
                                        $cssClass = $isSelected ? 'selected' : '';
                                        echo "<div class='choice-item $cssClass'><span class='alpha'>".$alphas[$cIdx]."</span> <span>".htmlspecialchars($choiceText)."</span></div>";
                                    }
                                    // If they typed a custom answer or old form version
                                    if (!$matched && $answer !== '') {
                                        echo "<div class='choice-item selected'><span class='alpha'>*</span> <span>".htmlspecialchars($answer)."</span></div>";
                                    }
                                    ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Right: Text Mindset -->
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header">ทัศนคติ (Text)</div>
                        <div class="card-body p-3" style="max-height: 80vh; overflow-y: auto;">
                            <?php foreach ($textQuestions as $idx => $title): 
                                $answer = trim($data[$idx] ?? '');
                                if ($answer === '') continue;
                            ?>
                                <div class="text-q"><?php echo htmlspecialchars($title); ?></div>
                                <div class="text-a"><?php echo htmlspecialchars($answer); ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php endif; ?>
    </div>
</body>
</html>

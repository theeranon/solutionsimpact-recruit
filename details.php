<?php
require_once 'auth.php';
require_once 'helper.php';
requireLogin();

$id = $_GET['id'] ?? null;
if ($id === null || !is_numeric($id)) {
    die("Invalid request.");
}

$url = GSHEET_CSV_URL;
$csvData = fetchCsvUrl($url);
if ($csvData === 'auth_required') {
    die("Authentication required for Google Sheet. Please check the sheet sharing settings.");
} elseif ($csvData === false) {
    die("Failed to fetch Google Sheet data.");
}

$lines = [];
$fp = fopen("php://memory", "r+");
fwrite($fp, $csvData);
rewind($fp);
while (($row = fgetcsv($fp)) !== false) {
    if (count($row) === 1 && $row[0] === null) continue;
    $lines[] = $row;
}
fclose($fp);

$headers = $lines[0] ?? [];
$data = null;

if (isset($lines[(int)$id])) {
    $data = $lines[(int)$id];
}

$questions = [
    ['id' => 30, 'title' => 'คุณคิดว่าคุณเก่งอะไร หรือ ภูมิใจอะไร 3 เรื่อง เล่าเหตุการณ์ให้ฟังหน่อย'],
    ['id' => 31, 'title' => 'เป้าหมายของคุณในอีก 3 ปีข้างหน้า คืออะไร?'],
    ['id' => 32, 'title' => 'ข้อเสียของคุณในมุมมองของคุณคือ (ไม่เอาคำตอบดารา)'],
    ['id' => 33, 'title' => 'นิยามของคำว่า WORK LIFE BALANCE ใกล้เคียงกับข้อใดมากที่สุด?', 'choices' => [
        'ทำงานให้เสร็จตามเวลาที่กำหนด หลังจากนั้นเป็นเวลาส่วนตัว ห้ามเรื่องงานมารบกวน',
        'ทำงานหนักได้ตามความจำเป็น แต่ต้องมีเวลาพักผ่อนและดูแลตัวเองอย่างเพียงพอ',
        'งานและชีวิตส่วนตัวผสมผสานกันได้ สามารถตอบไลน์งานนอกเวลาได้ ถ้าไม่ได้ทำให้ชีวิตพัง',
        'โฟกัสเรื่องงานเป็นหลัก พร้อมทุ่มเทเวลาส่วนตัวให้งานเพื่อให้ถึงเป้าหมายและเติบโตเร็วที่สุด'
    ]],
    ['id' => 34, 'title' => 'ถ้าได้รับมอบหมายโปรเจกต์ใหม่ที่ไม่เคยทำมาก่อน คุณจะเริ่มอย่างไร?', 'choices' => [
        'ศึกษาข้อมูลเองทั้งหมด วางแผนเงียบๆ แล้วค่อยส่งผลงานทีเดียวเมื่อมั่นใจว่าดีพอ',
        'ขอคำแนะนำจากหัวหน้าหรือผู้มีประสบการณ์ก่อนเริ่มลงมือทำ เพื่อความชัวร์',
        'ลองผิดลองถูกด้วยตัวเอง ลงมือทำไปก่อนแก้ปัญหาไปทีละเปลาะ',
        'ร่างแผนคร่าวๆ เสนอไอเดียให้ทีมหรือหัวหน้าช่วยกันปรับแต่ง แล้วค่อยลงมือ'
    ]],
    ['id' => 35, 'title' => 'คุณคิดว่าอะไรคือคุณสมบัติที่สำคัญที่สุดของการเป็นผู้นำ?', 'choices' => [
        'ความสามารถในการสั่งการและควบคุมให้ทีมทำตามเป้าหมาย',
        'ความเข้าอกเข้าใจ (Empathy) และพร้อมรับฟังปัญหาของลูกทีม',
        'การเป็นแบบอย่างที่ดี ลงมือทำให้เห็น มากกว่าแค่สั่งงาน',
        'วิสัยทัศน์ที่กว้างไกล และสามารถสร้างแรงบันดาลใจให้ผู้อื่น'
    ]],
    ['id' => 36, 'title' => 'ชอบ Lifestyle การทำงานแบบไหน เพราะอะไร'],
    ['id' => 37, 'title' => 'คำอธิบายใดที่สะท้อนสไตล์การทำงานของคุณได้ดีที่สุด?', 'choices' => [
        'ชอบคิดไอเดียใหม่ๆ มองเห็นภาพรวมและโอกาสใหม่ๆ เสมอ แต่มักจะเบื่อง่ายถ้าต้องทำอะไรซ้ำๆ',
        'ชอบพูดคุย สื่อสารเก่ง นำเสนอเก่ง มีพลังในการโน้มน้าวผู้คน แต่มักจะตกหล่นเรื่องรายละเอียด',
        'ชอบทำงานเป็นทีม ดูแลจังหวะการทำงาน ใส่ใจความรู้สึกคนรอบข้าง แต่อาจตัดสินใจช้าในสถานการณ์กดดัน',
        'ชอบวิเคราะห์ข้อมูล เน้นความถูกต้อง ชัดเจน มีระบบระเบียบ แต่มักจะอึดอัดกับความไม่แน่นอน'
    ]],
    ['id' => 38, 'title' => 'เมื่อต้องตัดสินใจเรื่องสำคัญ คุณมักจะใช้อะไรเป็นหลัก?', 'choices' => [
        'วิสัยทัศน์และสัญชาตญาณ (มองภาพใหญ่ อนาคต)',
        'ความรู้สึกและการมีส่วนร่วม (นึกถึงผลกระทบต่อคนรอบข้าง)',
        'ประสบการณ์และจังหวะเวลา (ดูความพร้อมของสถานการณ์)',
        'ข้อมูลและข้อเท็จจริง (วิเคราะห์ตัวเลข หลักฐาน)'
    ]],
    ['id' => 39, 'title' => 'บทบาทไหนในทีมที่คุณทำแล้วรู้สึกมีพลังมากที่สุด?', 'choices' => [
        'คนริเริ่มโปรเจกต์ใหม่ เสนอไอเดียสร้างสรรค์',
        'คนสร้างเครือข่าย นำเสนอ และกระตุ้นให้ทีมตื่นตัว',
        'คนลงมือทำ คอยสนับสนุน และรักษาความสัมพันธ์ในทีม',
        'คนตรวจสอบความถูกต้อง วางระบบ และวิเคราะห์ผลลัพธ์'
    ]],
    ['id' => 40, 'title' => 'จุดอ่อนในการทำงานที่คุณมักจะได้รับฟีดแบ็ก (หรือรู้ตัว) คืออะไร?', 'choices' => [
        'คิดเร็วไปจนคนอื่นตามไม่ทัน หรือสนใจแต่เรื่องใหม่จนทิ้งงานเดิม',
        'พูดเก่งแต่มักจะสมาธิสั้นกับงานเอกสาร หรือมองโลกในแง่ดีเกินไป',
        'ประนีประนอมมากไปจนงานล่าช้า หรือไม่กล้าปฏิเสธคนอื่น',
        'ละเอียดเกินไปจนช้า หรือยึดติดกับกฎเกณฑ์มากเกินไป'
    ]],
    ['id' => 41, 'title' => 'คาดหวังอะไรจากการทำงานที่นี่']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'IBM Plex Sans Thai', sans-serif; background: #f8f9fa; color: #212529; font-size: 0.95rem; }
        .card { border: 1px solid #dee2e6; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 1rem; border-radius: 6px; }
        .card-header { background: #fff !important; border-bottom: 1px solid #dee2e6; font-weight: 600; padding: 0.6rem 1rem; font-size: 1rem; color: #212529 !important; }
        .card-body { padding: 1rem; }
        .label { font-size: 0.8rem; color: #6c757d; font-weight: 600; margin-bottom: 0.1rem; text-transform: uppercase; }
        .val { font-size: 0.95rem; margin-bottom: 0.75rem; font-weight: 500; }
        .badge-pos { background: #e9ecef; color: #212529; font-size: 0.85rem; padding: 4px 8px; border-radius: 4px; margin: 2px 2px 2px 0; display: inline-block; border: 1px solid #ced4da; }
        
        .q-box { margin-bottom: 1.25rem; }
        .q-title { font-weight: 600; margin-bottom: 0.4rem; font-size: 0.95rem; color: #0d6efd; line-height: 1.4; }
        
        .choice-item { 
            padding: 4px 10px; 
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
        .choice-item .alpha { width: 22px; font-weight: 700; flex-shrink: 0; }
        
        .text-a { background: #fff; border: 1px solid #dee2e6; padding: 0.5rem 0.75rem; border-radius: 4px; font-size: 0.9rem; white-space: pre-wrap; line-height: 1.5; }
        .print-card { page-break-inside: avoid; }
        @media print {
            .col-print-4 { flex: 0 0 33.333333% !important; max-width: 33.333333% !important; padding: 0 10px !important; }
            .col-print-6 { flex: 0 0 50% !important; max-width: 50% !important; padding: 0 10px !important; }
            .col-print-12 { flex: 0 0 100% !important; max-width: 100% !important; padding: 0 10px !important; }
            .print-row { display: flex !important; flex-wrap: nowrap !important; flex-direction: row !important; width: 100% !important; }
            .print-wrap-row { display: flex !important; flex-wrap: wrap !important; width: 100% !important; }
        }
    </style>
</head>
<body class="bg-light">
    
    <div class="bg-white border-bottom mb-3 py-2 shadow-sm sticky-top">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <span class="fw-bold text-primary fs-5">SolutionsIMPACT</span>
            <div>
                <a href="index.php" class="btn btn-sm btn-outline-secondary">Back</a>
            </div>
        </div>
    </div>
    
    <div class="container-fluid pb-4">
        <?php if (!$data): ?>
            <div class="alert alert-danger">Candidate not found.</div>
        <?php else: ?>
            
            <div class="d-flex flex-wrap justify-content-between align-items-end mb-3">
                <div>
                    <h4 class="mb-1 text-truncate"><?php echo htmlspecialchars($data[1] ?? 'Unknown'); ?> (<?php echo htmlspecialchars($data[2] ?? '-'); ?>)</h4>
                    <div class="text-muted small">User: <?php echo htmlspecialchars($_SESSION['username']); ?></div>
                </div>
                <div>
                    <button class="btn btn-primary btn-sm" onclick="window.print()">Print Report</button>
                </div>
            </div>

            <!-- Row 1: 3 Columns -->
            <div class="row g-3 mb-3 print-row">
                <!-- Col 1: ข้อมูลส่วนตัว & BaZi -->
                <div class="col-lg-4 col-print-4">
                    <div class="card h-100 shadow-sm border-0 print-card">
                        <div class="card-header bg-dark text-white rounded-top border-0">ข้อมูลส่วนตัว & BaZi</div>
                        <div class="card-body bg-white rounded-bottom p-3">
                            <div class="row g-2 mb-3">
                                <div class="col-12"><div class="label">เบอร์โทร</div><div class="val"><?php echo htmlspecialchars($data[24] ?? '-'); ?></div></div>
                                <div class="col-12"><div class="label">อีเมล</div><div class="val text-truncate" title="<?php echo htmlspecialchars($data[25] ?? ''); ?>"><?php echo htmlspecialchars($data[25] ?? '-'); ?></div></div>
                                <div class="col-12"><div class="label">วันเกิด</div><div class="val"><?php echo htmlspecialchars($data[26] ?? '-'); ?></div></div>
                                <div class="col-12"><div class="label">วุฒิ</div><div class="val text-truncate"><?php echo htmlspecialchars($data[27] ?? '-'); ?></div></div>
                            </div>
                            
                            <?php 
                            $bazi = getFullBazi($data[26] ?? '');
                            if ($bazi): 
                            ?>
                            <div class="mt-2 pt-2 border-top">
                                <div class="label mb-2 text-dark">วิเคราะห์ดวงจีน (BaZi) 3 เสา</div>
                                <table class="table table-bordered text-center mb-0" style="font-size: 0.75rem;">
                                    <thead class="table-light">
                                        <tr>
                                            <th>เสาวัน(Day)</th>
                                            <th>เสาเดือน(Month)</th>
                                            <th>เสาปี(Year)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="text-primary fw-bold text-truncate" style="max-width:60px;"><?php echo htmlspecialchars($bazi['chart']['day']['stem10']); ?></td>
                                            <td class="text-muted text-truncate" style="max-width:60px;"><?php echo htmlspecialchars($bazi['chart']['month']['stem10']); ?></td>
                                            <td class="text-muted text-truncate" style="max-width:60px;"><?php echo htmlspecialchars($bazi['chart']['year']['stem10']); ?></td>
                                        </tr>
                                        <tr class="fw-bold fs-6">
                                            <td class="text-danger"><?php echo htmlspecialchars($bazi['chart']['day']['stem']); ?></td>
                                            <td><?php echo htmlspecialchars($bazi['chart']['month']['stem']); ?></td>
                                            <td><?php echo htmlspecialchars($bazi['chart']['year']['stem']); ?></td>
                                        </tr>
                                        <tr class="fw-bold fs-6">
                                            <td class="text-danger"><?php echo htmlspecialchars($bazi['chart']['day']['branch']); ?></td>
                                            <td><?php echo htmlspecialchars($bazi['chart']['month']['branch']); ?></td>
                                            <td><?php echo htmlspecialchars($bazi['chart']['year']['branch']); ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted text-truncate" style="max-width:60px;"><?php echo htmlspecialchars($bazi['chart']['day']['branch10']); ?></td>
                                            <td class="text-muted text-truncate" style="max-width:60px;"><?php echo htmlspecialchars($bazi['chart']['month']['branch10']); ?></td>
                                            <td class="text-muted text-truncate" style="max-width:60px;"><?php echo htmlspecialchars($bazi['chart']['year']['branch10']); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Col 2: LeaderShift™ Level -->
                <div class="col-lg-4 col-print-4">
                    <div class="card h-100 shadow-sm border-0 print-card">
                        <div class="card-header bg-dark text-white rounded-top border-0">LeaderShift™ Level</div>
                        <div class="card-body bg-white rounded-bottom p-3">
                            <?php 
                            $leaderShiftQs = [33, 34, 35];
                            foreach ($questions as $q):
                                if (!in_array($q['id'], $leaderShiftQs)) continue;
                                $answer = trim($data[$q['id']] ?? '');
                                if ($answer === '') continue;
                            ?>
                                <div class="q-box border-bottom pb-2 mb-2">
                                    <div class="q-title text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($q['title']); ?></div>
                                    <?php 
                                    $alphas = ['A', 'B', 'C', 'D'];
                                    $matched = false;
                                    foreach ($q['choices'] as $cIdx => $choiceText) {
                                        $isSelected = (strpos($answer, $choiceText) !== false || strpos($choiceText, $answer) !== false);
                                        if ($isSelected) $matched = true;
                                        $cssClass = $isSelected ? 'selected' : '';
                                        echo "<div class='choice-item $cssClass'><span class='alpha'>".$alphas[$cIdx]."</span> <span>".htmlspecialchars($choiceText)."</span></div>";
                                    }
                                    if (!$matched && $answer !== '') {
                                        echo "<div class='choice-item selected'><span class='alpha'>*</span> <span>".htmlspecialchars($answer)."</span></div>";
                                    }
                                    ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Col 3: Talent Profile -->
                <div class="col-lg-4 col-print-4">
                    <div class="card h-100 shadow-sm border-0 print-card">
                        <div class="card-header bg-dark text-white rounded-top border-0">Talent Profile</div>
                        <div class="card-body bg-white rounded-bottom p-3">
                            <?php 
                            $talentQs = [37, 38, 39, 40];
                            foreach ($questions as $q):
                                if (!in_array($q['id'], $talentQs)) continue;
                                $answer = trim($data[$q['id']] ?? '');
                                if ($answer === '') continue;
                            ?>
                                <div class="q-box border-bottom pb-2 mb-2">
                                    <div class="q-title text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($q['title']); ?></div>
                                    <?php 
                                    $alphas = ['A', 'B', 'C', 'D'];
                                    $matched = false;
                                    foreach ($q['choices'] as $cIdx => $choiceText) {
                                        $isSelected = (strpos($answer, $choiceText) !== false || strpos($choiceText, $answer) !== false);
                                        if ($isSelected) $matched = true;
                                        $cssClass = $isSelected ? 'selected' : '';
                                        echo "<div class='choice-item $cssClass'><span class='alpha'>".$alphas[$cIdx]."</span> <span>".htmlspecialchars($choiceText)."</span></div>";
                                    }
                                    if (!$matched && $answer !== '') {
                                        echo "<div class='choice-item selected'><span class='alpha'>*</span> <span>".htmlspecialchars($answer)."</span></div>";
                                    }
                                    ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 2: สมัครงาน (50 / 50) -->
            <div class="card shadow-sm border-0 mb-3 print-card">
                <div class="card-header bg-dark text-white rounded-top border-0">การสมัครงาน และความคาดหวัง</div>
                <div class="card-body bg-white rounded-bottom p-4">
                    <div class="row g-4 print-row">
                        
                        <!-- Left 50% -->
                        <div class="col-md-6 col-print-6">
                            <div class="label fs-6 mb-1 text-dark">ตำแหน่งที่สนใจ</div>
                            <div class="mb-3">
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
                            
                            <div class="label fs-6 mb-1 text-dark">ทำไมถึงสนใจ</div>
                            <div class="val text-muted mb-3" style="white-space: pre-wrap; line-height: 1.5;"><?php echo htmlspecialchars($data[21] ?? '-'); ?></div>
                            
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="label text-dark">เงินเดือนที่คาดหวัง</div>
                                    <div class="val text-success fw-bold fs-5"><?php echo htmlspecialchars($data[42] ?? '-'); ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="label text-dark">ขั้นต่ำสุดที่รับได้</div>
                                    <div class="val text-danger fw-bold fs-5"><?php echo htmlspecialchars($data[43] ?? '-'); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Right 50% -->
                        <div class="col-md-6 col-print-6 border-start">
                            <?php 
                            $expectQs = [36, 41];
                            foreach ($questions as $q):
                                if (!in_array($q['id'], $expectQs)) continue;
                                $answer = trim($data[$q['id']] ?? '');
                                if ($answer === '') continue;
                            ?>
                                <div class="mb-4">
                                    <div class="q-title text-dark fw-bold fs-6 mb-2"><?php echo htmlspecialchars($q['title']); ?></div>
                                    <div class="text-a border-0 p-3 mb-0 bg-light text-dark" style="border-radius: 8px; font-size: 0.9rem; line-height: 1.5; white-space: pre-wrap;"><?php echo htmlspecialchars($answer); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Row 3: ทัศนคติและเป้าหมาย (Redesigned as columns to avoid horizontal stretching) -->
            <div class="card shadow-sm border-0 mb-3 print-card">
                <div class="card-header bg-dark text-white rounded-top border-0">ทัศนคติและเป้าหมาย (Attitude & Mindset)</div>
                <div class="card-body bg-white rounded-bottom p-4">
                    <div class="row g-4 print-row">
                        <?php 
                        $attitudeQs = [30, 31, 32];
                        foreach ($questions as $q):
                            if (!in_array($q['id'], $attitudeQs)) continue;
                            $answer = trim($data[$q['id']] ?? '');
                            if ($answer === '') continue;
                        ?>
                            <div class="col-md-4 col-print-4">
                                <div class="q-title text-dark fw-bold mb-2" style="font-size: 0.9rem; min-height: 2.5rem;"><?php echo htmlspecialchars($q['title']); ?></div>
                                <div class="text-a border-0 p-3 mb-0 bg-light text-dark" style="border-radius: 8px; font-size: 0.85rem; line-height: 1.6; white-space: pre-wrap; height: calc(100% - 3rem); overflow-y: auto;"><?php echo htmlspecialchars($answer); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>
</body>
</html>

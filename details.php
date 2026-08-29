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

$questions = [
    [ 'id' => 30, 'type' => 'text', 'title' => 'คุณคิดว่าคุณเก่งอะไร หรือ ภูมิใจอะไร 3 เรื่อง เล่าเหตุการณ์ให้ฟังหน่อย' ],
    [ 'id' => 31, 'type' => 'text', 'title' => 'เป้าหมายของคุณในอีก 3 ปีข้างหน้า' ],
    [ 'id' => 32, 'type' => 'text', 'title' => 'ข้อเสียของคุณในมุมมองของคุณคือ' ],
    [ 'id' => 33, 'type' => 'choice', 'title' => 'นิยามของคำว่า WORK LIFE BALANCE ใกล้เคียงข้อใดที่สุด', 
      'choices' => [ "ขึ้นอยู่กับวัฒนธรรมองค์กรและหัวหน้าที่เข้าใจ หากสภาพแวดล้อมเอื้อ ก็จะทำได้ง่ายขึ้น", "ทำงานตามกรอบเวลาแน่นอน เพื่อให้มีเวลาหลังเลิกงาน", "อดทนวันนี้เพื่อวันหน้า", "WORK = LIFE งานคือชีวิต" ] ],
    [ 'id' => 34, 'type' => 'choice', 'title' => 'คุณรู้สึกอย่างไร เมื่อหัวหน้าที่ให้งานคุณเยอะเกินไป', 
      'choices' => [ "ฉันทำให้ดีที่สุดเสมอ และหัวหน้าควรเข้าใจข้อจำกัดและช่วยจัดลำดับความสำคัญให้", "พร้อมทำงานที่ได้รับมอบหมายเต็มที่ และขอคำแนะนำจากหัวหน้า", "ตั้งเป้าจะทำงานให้เสร็จอย่างมีประสิทธิภาพ และเรียนรู้จากประสบการณ์ครั้งนี้", "ขอนัดหัวหน้าคุยเพื่อทำความเข้าใจ" ] ],
    [ 'id' => 35, 'type' => 'choice', 'title' => 'สถานการณ์แบบใดมีโอกาสที่อาจทำให้คุณ Burnout', 
      'choices' => [ "ไม่น่าจะเกิดขึ้นกับฉัน เพราะฉันสามารถจัดการตัวเองได้ดี", "อยู่ในสถานการณ์ที่มีแรงกดดันจากรอบด้าน", "เมื่อพยายามทำดีที่สุดแล้ว แต่มันไม่ก้าวหน้าไปไหนซักที", "เมื่อร่างกายต่อต้าน ไม่ไหวแล้ว" ] ],
    [ 'id' => 36, 'type' => 'text', 'title' => 'ชอบ Lifestyle การทำงานแบบไหน เพราะอะไร' ],
    [ 'id' => 37, 'type' => 'choice', 'title' => 'ข้อดีของคุณในมุมมองของคุณคือ', 
      'choices' => [ "เป็นเสียงหัวเราะให้ผู้คน", "เป็นคนจริงจัง และละเอียดรอบคอบ", "เป็นคนช่างคิด ไอเดียบรรเจิด", "เป็นคนใจดี ใส่ใจดูแลผู้คน" ] ],
    [ 'id' => 38, 'type' => 'choice', 'title' => 'เมื่อทำงานกับทีม คุณจะรู้สึกสนุกที่จะทำหน้าที่อะไรมากที่สุด', 
      'choices' => [ "คนออกไอเดีย เสนอแนวคิด", "ประสานงานติดต่อผู้คน", "พร้อมสนับสนุน ว่าไงว่าตามกัน", "เก็บสถิติข้อมูล ออกกฎ" ] ],
    [ 'id' => 39, 'type' => 'choice', 'title' => 'คุณทำอย่างไรเมื่อต้องตัดสินใจในเรื่องสำคัญ', 
      'choices' => [ "ขอคำแนะนำจากเพื่อน ปรึกษาเพื่อน", "ดูว่าคนอื่นตัดสินใจอย่างไร", "พิจารณาอย่างละเอียดในทุกแง่มุม", "ใช้สัญชาตญาณของคุณ" ] ],
    [ 'id' => 40, 'type' => 'choice', 'title' => 'ถ้าเลือกได้ คุณอยากได้หัวหน้าที่มีความเชื่อแบบไหน', 
      'choices' => [ "ฝันให้ไกล ไปให้ถึง", "ทุกคนสำราญ งานสำเร็จ งานจบต้องฉลอง", "เรื่องงานกับเรื่องส่วนตัวแยกกันอย่างชัดเจน", "ใส่ใจ ดูแลทุกคน" ] ],
    [ 'id' => 41, 'type' => 'text', 'title' => 'คาดหวังอะไรจากการทำงานที่นี่' ]
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
        .card-header { background: #fff; border-bottom: 1px solid #dee2e6; font-weight: 600; padding: 0.6rem 1rem; font-size: 1rem; }
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
                    <?php if(!empty($data[28])): ?><a href="<?php echo htmlspecialchars($data[28]); ?>" target="_blank" class="btn btn-sm btn-primary">View Resume</a><?php endif; ?>
                    <?php if(!empty($data[29])): ?><a href="<?php echo htmlspecialchars($data[29]); ?>" target="_blank" class="btn btn-sm btn-dark">View Portfolio</a><?php endif; ?>
                </div>
            </div>

            <div class="row g-3">
                
                <!-- Left Column (30%) -->
                <div class="col-lg-4">
                    
                    <!-- Card 1: ข้อมูลส่วนตัว & สมัครงาน -->
                    <div class="card mb-3 shadow-sm border-0">
                        <div class="card-header bg-dark text-white rounded-top border-0">ข้อมูลส่วนตัว & สมัครงาน</div>
                        <div class="card-body bg-white rounded-bottom">
                            <div class="row">
                                <div class="col-12"><div class="label">เบอร์โทร</div><div class="val"><?php echo htmlspecialchars($data[24] ?? '-'); ?></div></div>
                                <div class="col-12"><div class="label">อีเมล</div><div class="val text-truncate" title="<?php echo htmlspecialchars($data[25] ?? ''); ?>"><?php echo htmlspecialchars($data[25] ?? '-'); ?></div></div>
                                <div class="col-12"><div class="label">วันเกิด</div><div class="val"><?php echo htmlspecialchars($data[26] ?? '-'); ?> 
                                    <span style="background: #eef2f5; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; color: #495057; border: 1px solid #dee2e6; margin-left: 5px;">
                                        <?php echo htmlspecialchars(getBaziYearInfo($data[26] ?? '')); ?>
                                    </span>
                                </div></div>
                                <div class="col-12"><div class="label">วุฒิ</div><div class="val text-truncate"><?php echo htmlspecialchars($data[27] ?? '-'); ?></div></div>
                            </div>
                            <hr class="my-2 text-muted">
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
                            <div class="val fs-6 text-muted" style="white-space: pre-wrap; line-height: 1.4;"><?php echo htmlspecialchars($data[21] ?? '-'); ?></div>
                            <div class="row mt-2">
                                <div class="col-6"><div class="label">คาดหวัง</div><div class="val text-success fw-bold"><?php echo htmlspecialchars($data[42] ?? '-'); ?></div></div>
                                <div class="col-6"><div class="label">ต่ำสุด</div><div class="val text-danger fw-bold"><?php echo htmlspecialchars($data[43] ?? '-'); ?></div></div>
                            </div>
                            <hr class="my-3 text-muted">
                            
                            <?php 
                            $personalQs = [41, 36];
                            foreach ($questions as $q):
                                if (!in_array($q['id'], $personalQs)) continue;
                                $answer = trim($data[$q['id']] ?? '');
                                if ($answer === '') continue;
                            ?>
                                <div class="q-box border-bottom pb-2 mb-2">
                                    <div class="q-title text-dark"><?php echo htmlspecialchars($q['title']); ?></div>
                                    <div class="text-a border-0 p-0 mb-0"><?php echo htmlspecialchars($answer); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Card BaZi Chart -->
                    <?php 
                    $bazi = getFullBazi($data[26] ?? '');
                    if ($bazi): 
                    ?>
                    <div class="card mb-3 shadow-sm border-0">
                        <div class="card-header bg-dark text-white rounded-top border-0">วิเคราะห์ดวงจีน (BaZi) 3 เสา</div>
                        <div class="card-body bg-white rounded-bottom p-3">
                            <table class="table table-bordered text-center mb-0" style="font-size: 0.85rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>เสาเวลา (Hour)</th>
                                        <th>เสาวัน (Day)</th>
                                        <th>เสาเดือน (Month)</th>
                                        <th>เสาปี (Year)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="text-muted">ไม่ระบุเวลา</td>
                                        <td class="text-primary fw-bold"><?php echo htmlspecialchars($bazi['chart']['day']['stem10']); ?></td>
                                        <td class="text-muted"><?php echo htmlspecialchars($bazi['chart']['month']['stem10']); ?></td>
                                        <td class="text-muted"><?php echo htmlspecialchars($bazi['chart']['year']['stem10']); ?></td>
                                    </tr>
                                    <tr class="fw-bold fs-6">
                                        <td>???</td>
                                        <td class="text-danger"><?php echo htmlspecialchars($bazi['chart']['day']['stem']); ?></td>
                                        <td><?php echo htmlspecialchars($bazi['chart']['month']['stem']); ?></td>
                                        <td><?php echo htmlspecialchars($bazi['chart']['year']['stem']); ?></td>
                                    </tr>
                                    <tr class="fw-bold fs-6">
                                        <td>???</td>
                                        <td class="text-danger"><?php echo htmlspecialchars($bazi['chart']['day']['branch']); ?></td>
                                        <td><?php echo htmlspecialchars($bazi['chart']['month']['branch']); ?></td>
                                        <td><?php echo htmlspecialchars($bazi['chart']['year']['branch']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">ไม่ระบุเวลา</td>
                                        <td class="text-muted"><?php echo htmlspecialchars($bazi['chart']['day']['branch10']); ?></td>
                                        <td class="text-muted"><?php echo htmlspecialchars($bazi['chart']['month']['branch10']); ?></td>
                                        <td class="text-muted"><?php echo htmlspecialchars($bazi['chart']['year']['branch10']); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="mt-2 text-muted" style="font-size: 0.75rem;">* ผูกดวง 3 เสาจากวันที่เกิด (ไม่มีเวลาเกิด) คำนวณ Ten Gods จากดิถี (Day Master) โดยตรง ไม่พึ่งพา API ภายนอก</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Card 3: LeaderShift™ Level -->
                    <div class="card mb-3 shadow-sm border-0">
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
                                    <div class="q-title text-dark"><?php echo htmlspecialchars($q['title']); ?></div>
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

                    <!-- Card 4: Talent Profile -->
                    <div class="card mb-3 shadow-sm border-0">
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
                                    <div class="q-title text-dark"><?php echo htmlspecialchars($q['title']); ?></div>
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

                <!-- Right Column (70%) -->
                <div class="col-lg-8">
                    
                    <!-- Card 2: ทัศนคติและเป้าหมาย (Attitude & Mindset) -->
                    <div class="card mb-3 shadow-sm border-0 h-100">
                        <div class="card-header bg-dark text-white rounded-top border-0">ทัศนคติและเป้าหมาย (Attitude & Mindset)</div>
                        <div class="card-body bg-white rounded-bottom p-4">
                            <?php 
                            $attitudeQs = [30, 31, 32];
                            foreach ($questions as $q):
                                if (!in_array($q['id'], $attitudeQs)) continue;
                                $answer = trim($data[$q['id']] ?? '');
                                if ($answer === '') continue;
                            ?>
                                <div class="q-box border-bottom pb-4 mb-4">
                                    <div class="q-title text-dark fs-5 mb-3"><?php echo htmlspecialchars($q['title']); ?></div>
                                    <div class="text-a border-0 p-3 mb-0 fs-6 bg-light text-dark" style="border-radius: 8px;"><?php echo htmlspecialchars($answer); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>
            </div>
            
        <?php endif; ?>
    </div>
</body>
</html>

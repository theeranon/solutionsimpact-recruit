<?php
function fetchCsvUrl($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // Pretend to be a browser
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return false;
        }
        
        // If content contains an HTML tag early on, it's likely a login page
        if (stripos(substr($content, 0, 1000), '<html') !== false) {
            return 'auth_required';
        }
        
        return $content;
    } else {
        // Fallback to file_get_contents
        $context = stream_context_create([
            "http" => [
                "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n"
            ]
        ]);
        $content = @file_get_contents($url, false, $context);
        
        if ($content === false) {
            return false;
        }
        
        if (stripos(substr($content, 0, 1000), '<html') !== false) {
            return 'auth_required';
        }
        
        return $content;
    }
}

function getBaziYearInfo($dateStr) {
    if (empty(trim($dateStr))) return '-';
    if (preg_match('/\b(19\d\d|20\d\d|25\d\d)\b/', $dateStr, $matches)) {
        $year = (int)$matches[1];
        if ($year > 2400) {
            $year -= 543;
        }
        $stems = [
            0 => 'ทองหยาง', 1 => 'ทองหยิน',
            2 => 'น้ำหยาง',   3 => 'น้ำหยิน',
            4 => 'ไม้หยาง',   5 => 'ไม้หยิน',
            6 => 'ไฟหยาง',    7 => 'ไฟหยิน',
            8 => 'ดินหยาง',   9 => 'ดินหยิน'
        ];
        $branches = [
            0 => 'วอก', 1 => 'ระกา', 2 => 'จอ', 3 => 'กุน',
            4 => 'ชวด', 5 => 'ฉลู', 6 => 'ขาล', 7 => 'เถาะ',
            8 => 'มะโรง', 9 => 'มะเส็ง', 10 => 'มะเมีย', 11 => 'มะแม'
        ];
        $stem = $stems[$year % 10];
        $branch = $branches[$year % 12];
        return "ธาตุ{$stem} / ปี{$branch}";
    }
    return '-';
}

function getFullBazi($dateStr) {
    if (empty(trim($dateStr))) return false;
    $ts = strtotime($dateStr . ' 12:00:00');
    if (!$ts) return false;
    
    $y = (int)date('Y', $ts);
    $m = (int)date('n', $ts);
    $d = (int)date('j', $ts);
    if ($y > 2400) $y -= 543;
    
    $stems = ['甲 ไม้+','乙 ไม้-','丙 ไฟ+','丁 ไฟ-','戊 ดิน+','己 ดิน-','庚 ทอง+','辛 ทอง-','壬 น้ำ+','癸 น้ำ-'];
    $branches = ['子 ชวด(น้ำ)','丑 ฉลู(ดิน)','寅 ขาล(ไม้)','卯 เถาะ(ไม้)','辰 มะโรง(ดิน)','巳 มะเส็ง(ไฟ)','午 มะเมีย(ไฟ)','未 มะแม(ดิน)','申 วอก(ทอง)','酉 ระกา(ทอง)','戌 จอ(ดิน)','亥 กุน(น้ำ)'];
    $branchMainStems = [9, 5, 0, 1, 4, 2, 3, 5, 6, 7, 4, 8];

    $isPrev = ($m == 1 || ($m == 2 && $d < 4));
    $baziYear = $isPrev ? $y - 1 : $y;
    $yS = ($baziYear - 4) % 10; if ($yS < 0) $yS += 10;
    $yB = ($baziYear - 4) % 12; if ($yB < 0) $yB += 12;
    
    $trans = [1=>6, 2=>4, 3=>6, 4=>5, 5=>6, 6=>6, 7=>7, 8=>8, 9=>8, 10=>8, 11=>8, 12=>7];
    $bMonth = $m; if ($d < $trans[$m]) { $bMonth--; if ($bMonth < 1) $bMonth = 12; }
    $monthToBranch = [1=>1, 2=>2, 3=>3, 4=>4, 5=>5, 6=>6, 7=>7, 8=>8, 9=>9, 10=>10, 11=>11, 12=>0];
    $mB = $monthToBranch[$bMonth];
    
    $monthStartStem = 0;
    switch ($yS) {
        case 0: case 5: $monthStartStem = 2; break;
        case 1: case 6: $monthStartStem = 4; break;
        case 2: case 7: $monthStartStem = 6; break;
        case 3: case 8: $monthStartStem = 8; break;
        case 4: case 9: $monthStartStem = 0; break;
    }
    $bMonthOffset = $bMonth - 2; if ($bMonthOffset < 0) $bMonthOffset += 12;
    $mS = ($monthStartStem + $bMonthOffset) % 10;
    
    $knownDate = strtotime('2024-01-01 12:00:00');
    $diffDays = (int)round(($ts - $knownDate) / 86400);
    $dS = $diffDays % 10; if ($dS < 0) $dS += 10;
    $dB = $diffDays % 12; if ($dB < 0) $dB += 12;
    
    $dm = $dS;
    
    $get10God = function($dmIdx, $targetIdx) {
        if ($targetIdx === -1) return '-';
        $dmEle = (int)($dmIdx / 2); $tEle = (int)($targetIdx / 2);
        $diff = ($tEle - $dmEle + 5) % 5;
        $same = (($dmIdx % 2) === ($targetIdx % 2));
        if ($diff == 0) return $same ? 'Friend (เทียบก่า)' : 'Rob Wealth (เกียบใช้)';
        if ($diff == 1) return $same ? 'Eating God (เจี๊ยะซิ้ง)' : 'Hurting Officer (เซียกัว)';
        if ($diff == 2) return $same ? 'Indirect Wealth (เพี่ยงใช้)' : 'Direct Wealth (เจี้ยใช้)';
        if ($diff == 3) return $same ? '7 Killings (ชิกสัวะ)' : 'Direct Officer (เจี้ยกัว)';
        if ($diff == 4) return $same ? 'Indirect Resource (เพี่ยงอิ่ง)' : 'Direct Resource (เจี้ยอิ่ง)';
    };

    return [
        'dayMaster' => $stems[$dm],
        'chart' => [
            'year' => ['stem' => $stems[$yS], 'stem10' => $get10God($dm, $yS), 'branch' => $branches[$yB], 'branch10' => $get10God($dm, $branchMainStems[$yB])],
            'month' => ['stem' => $stems[$mS], 'stem10' => $get10God($dm, $mS), 'branch' => $branches[$mB], 'branch10' => $get10God($dm, $branchMainStems[$mB])],
            'day' => ['stem' => $stems[$dS], 'stem10' => 'Day Master (ดิถี)', 'branch' => $branches[$dB], 'branch10' => $get10God($dm, $branchMainStems[$dB])]
        ]
    ];
}

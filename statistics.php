<?php
include 'dbconnect.php';
include 'auth.php';
requireRole(['admin','executive']);

$officerName = "ไม่ทราบชื่อ";
$officerID = $_SESSION['OfficerID'];

$sqlOfficer = "SELECT Officer_Name FROM officer WHERE OfficerID = ?";
$stmt = $con->prepare($sqlOfficer);
$stmt->bind_param("s", $officerID);
$stmt->execute();
$resultOfficer = $stmt->get_result();

if ($row = $resultOfficer->fetch_assoc()) {
    $officerName = $row['Officer_Name'];
}
$stmt->close();


// --- รับค่าเดือน/ปี ---
$month = null;
$year = null;
$monthText = ''; // สำหรับแสดงข้อความเดือน

if (!empty($_GET['monthyear'])) {
    $parts = explode('-', $_GET['monthyear']); // YYYY-MM
    if (count($parts) == 2) {
        $year = (int)$parts[0];
        $month = (int)$parts[1];
        $monthText = date('F', mktime(0,0,0,$month,1,$year)); // English full month
        // ถ้าต้องการภาษาไทย:
        $thaiMonths = ['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
        $monthText = $thaiMonths[$month-1];
    }
}

/* หัวข้อรายงาน */
$rows = [

    // ===== รายงานการบันทึกความผิดระเบียบจราจร =====
    // บังคับล้อรถยนต์
    ['section'=>'รายงานการบันทึกความผิดระเบียบจราจร','sub'=>'บังคับล้อรถยนต์','label'=>'นักศึกษา','group'=>'lock','vehicle'=>'รถยนต์','offender'=>'นักศึกษา'],
    ['section'=>'','sub'=>'','label'=>'บุคลากร','group'=>'lock','vehicle'=>'รถยนต์','offender'=>'บุคลากร'],
    ['section'=>'','sub'=>'','label'=>'บุคคลทั่วไป','group'=>'lock','vehicle'=>'รถยนต์','offender'=>'บุคคลทั่วไป'],

    // บังคับล้อรถจักรยานยนต์
    ['section'=>'','sub'=>'บังคับล้อรถจักรยานยนต์','label'=>'นักศึกษา','group'=>'lock','vehicle'=>'รถจักรยานยนต์','offender'=>'นักศึกษา'],
    ['section'=>'','sub'=>'','label'=>'บุคลากร','group'=>'lock','vehicle'=>'รถจักรยานยนต์','offender'=>'บุคลากร'],
    ['section'=>'','sub'=>'','label'=>'บุคคลทั่วไป','group'=>'lock','vehicle'=>'รถจักรยานยนต์','offender'=>'บุคคลทั่วไป'],

    // ===== ระเบียบจราจร บริการปลดล็อคล้อ =====
    // ปลดล็อคล้อรถยนต์
    ['section'=>'ระเบียบจราจร บริการปลดล็อคล้อ','sub'=>'ปลดล็อคล้อรถยนต์','label'=>'นักศึกษา','group'=>'unlock','vehicle'=>'รถยนต์','offender'=>'นักศึกษา'],
    ['section'=>'','sub'=>'','label'=>'บุคลากร','group'=>'unlock','vehicle'=>'รถยนต์','offender'=>'บุคลากร'],
    ['section'=>'','sub'=>'','label'=>'บุคคลทั่วไป','group'=>'unlock','vehicle'=>'รถยนต์','offender'=>'บุคคลทั่วไป'],

    // ปลดล็อคล้อรถจักรยานยนต์
    ['section'=>'','sub'=>'ปลดล็อคล้อรถจักรยานยนต์','label'=>'นักศึกษา','group'=>'unlock','vehicle'=>'รถจักรยานยนต์','offender'=>'นักศึกษา'],
    ['section'=>'','sub'=>'','label'=>'บุคลากร','group'=>'unlock','vehicle'=>'รถจักรยานยนต์','offender'=>'บุคลากร'],
    ['section'=>'','sub'=>'','label'=>'บุคคลทั่วไป','group'=>'unlock','vehicle'=>'รถจักรยานยนต์','offender'=>'บุคคลทั่วไป'],

 // ===== หมวกนิรภัย =====
['section'=>'ผู้กระทำความผิดระเบียบจราจร','sub'=>'หมวกนิรภัย','label'=>'นักศึกษา','group'=>'helmet','vehicle'=>'','offender'=>'นักศึกษา'],
['section'=>'','sub'=>'','label'=>'บุคลากร','group'=>'helmet','vehicle'=>'','offender'=>'บุคลากร'],
['section'=>'','sub'=>'','label'=>'บุคคลทั่วไป','group'=>'helmet','vehicle'=>'','offender'=>'บุคคลทั่วไป'],

];

$subGroups = [];
foreach ($rows as $k => $r) {
    if (!empty($r['sub'])) {
        $currentSub = $r['sub'];
        $subGroups[$currentSub] = [];
    }
    if (isset($currentSub)) {
        $subGroups[$currentSub][] = $k;
    }
}


/* เตรียม array วัน 1–31 */
$data = [];
foreach ($rows as $k => $r) {
    $data[$k] = array_fill(1, 31, 0);
}

/* SQL ดึงข้อมูล */
$sql = "SELECT 
    DAY(o.date) AS d,
    o.Status,
    o2.type_offender,
    c.Type_Vehicle,
    GROUP_CONCAT(
        DISTINCT coo.Category_Name 
        ORDER BY coo.Category_Name 
        SEPARATOR ' , '
    ) AS CategoryNames,
    COUNT(DISTINCT 
        o.date,
        o.Status,
        o2.OffenderID,
        c.Type_Vehicle
    ) AS total
FROM offense o
JOIN offender o2 ON o.OffenderID = o2.OffenderID
JOIN car c ON o2.`Vehicle _Num` = c.Vehicle_Num
LEFT JOIN `category _of_offense` coo
    ON FIND_IN_SET(coo.CategoryID, o.CategoryID)
WHERE MONTH(o.date)=?
  AND YEAR(o.date)=?
GROUP BY 
    DAY(o.date),
    o.Status,
    o2.type_offender,
    c.Type_Vehicle
";

$stmt = $con->prepare($sql);
$stmt->bind_param("ii", $month, $year);
$stmt->execute();
$result = $stmt->get_result();

/* จัดกลุ่มข้อมูล */
while ($row = $result->fetch_assoc()) {

    foreach ($rows as $k => $cfg) {

        /* ================= หมวกนิรภัย ================= */
        if (
            $cfg['group'] == 'helmet' &&
            strpos($row['CategoryNames'], 'ไม่สวมหมวกนิรภัย') === false
        ) continue;

        /* ========== บังคับล้อ / ปลดล็อค ห้ามเป็นหมวก ========== */
        if (
            in_array($cfg['group'], ['lock','unlock']) &&
            strpos($row['CategoryNames'], 'ไม่สวมหมวกนิรภัย') !== false
        ) continue;

        /* ================= ปลดล็อค ================= */
        if ($cfg['group'] == 'unlock') {

            // ต้องปลดล็อคแล้วเท่านั้น
            if ($row['Status'] != 'ปลดล็อคแล้ว') continue;

            // ปลดล็อค "รถจักรยานยนต์" เท่านั้น
            if ($cfg['vehicle'] == 'รถจักรยานยนต์'
                && $row['Type_Vehicle'] != 'รถจักรยานยนต์') continue;
        }

        /* ================= เช็คประเภทรถทั่วไป ================= */
        if ($cfg['vehicle'] != '' && $row['Type_Vehicle'] != $cfg['vehicle']) continue;

        /* ================= เช็คผู้กระทำผิด ================= */
        if ($row['type_offender'] != $cfg['offender']) continue;

        $data[$k][(int)$row['d']] += $row['total'];
    }
}

// export excel
if (isset($_GET['export']) && $_GET['export']==1) {

    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=statistics_$month-$year.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "<table border='1'>";
    echo "<tr><th>รายการ</th>";
    for($i=1;$i<=31;$i++) echo "<th>$i</th>";
    echo "<th>รวม</th></tr>";

    $curSection = '';
    $curSub = '';

    foreach ($rows as $k => $cfg) {

        /* ===== หัวข้อใหญ่ ===== */
        if ($cfg['section'] && $cfg['section'] != $curSection) {
            $curSection = $cfg['section'];
            echo "<tr><td colspan='33'><b>$curSection</b></td></tr>";
            $curSub = '';
        }

        /* ===== หัวข้อย่อย + แถวรวม ===== */
        if ($cfg['sub'] && $cfg['sub'] != $curSub) {
            $curSub = $cfg['sub'];

            echo "<tr><td><b>▸ $curSub</b></td>";

            // รวมรายวัน
            for ($d=1; $d<=31; $d++) {
                $sumDay = 0;
                foreach ($subGroups[$curSub] as $idx) {
                    $sumDay += $data[$idx][$d];
                }
                echo "<td>".($sumDay ?: '')."</td>";
            }

            // รวมทั้งหมด
            $sumAll = 0;
            foreach ($subGroups[$curSub] as $idx) {
                $sumAll += array_sum($data[$idx]);
            }
            echo "<td><b>$sumAll</b></td></tr>";
        }

        /* ===== แถวข้อมูลย่อย ===== */
        echo "<tr>";
        echo "<td style='padding-left:20px;'>".$cfg['label']."</td>";
        for($i=1;$i<=31;$i++){
            echo "<td>".$data[$k][$i]."</td>";
        }
        echo "<td>".array_sum($data[$k])."</td>";
        echo "</tr>";
    }

    echo "</table>";
    exit;
}


?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สถิติผู้กระทำผิดจราจร</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Mitr:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
    * {
        font-family: "Mitr", sans-serif;
    }

    body {
        background-color: #f8f9fa;
        overflow-x: hidden;
    }

    .sidebar {
        width: 250px;
        height: 100vh;
        background: #1f3a5f;
        position: fixed;
        top: 0;
        left: 0;
        padding-top: 60px;
        display: flex;
        flex-direction: column;
        transition: all 0.3s;
    }

    .sidebar.collapsed {
        width: 65px;
    }

    .sidebar .nav-link {
        color: #fff;
        padding: 12px 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 16px;
        white-space: nowrap;
        overflow: hidden;
    }

    .sidebar.collapsed .nav-link span {
        display: none;
    }

    .sidebar .nav-link.active {
        background-color: #355f90;
        border-radius: 8px;
    }

    .sidebar .nav-link:hover {
        background-color: #2b4d74;
        border-radius: 8px;
    }

    .sidebar .bottom-links {
        margin-top: auto;
    }

    .content {
        margin-left: 250px;
        padding: 20px;
        transition: all 0.3s;
    }

    .content.expanded {
        margin-left: 70px;
    }

    .toggle-btn {
        position: fixed;
        top: 10px;
        left: 10px;
        z-index: 1000;
        font-size: 24px;
        color: white;
        background: #1f3a5f;
        border: none;
        border-radius: 5px;
        padding: 5px 10px;
    }

    .sidebar i {
        font-size: 22px;
    }

    header h3 {
        font-weight: 600;
        color: #1f3a5f;
        margin-bottom: 20px;
        padding-top: 20px;
    }

    th,
    td {
        font-size: 14px;
        white-space: nowrap;
    }

    @media print {
        body * {
            visibility: hidden;
        }

        #printArea,
        #printArea * {
            visibility: visible;
        }

        #printArea {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
        }

        #printArea table {
            border-collapse: collapse;
            width: 100%;
        }

        #printArea th,
        #printArea td {
            border: 1px solid #000 !important;
            font-size: 12px;
            padding: 3px;
        }

        /* ป้องกัน table header หายเมื่อพิมพ์หลายหน้า */
        #printArea thead {
            display: table-header-group;
        }
    }

    /* ===== ตารางรายงาน ===== */
    #reportTable {
        background: #ffffff;
        border-color: #cbd6e2;
    }

    #reportTable th {
        background: #1f3a5f !important;
        color: #ffffff;
        font-weight: 500;
        border-color: #1f3a5f;
    }

    #reportTable td {
        border-color: #cbdae2;
    }

    /* ===== หัวข้อใหญ่ ===== */
    .table-secondary {
        background-color: #355f90 !important;
        color: #ffffff;
    }

    /* ===== หัวข้อย่อย ===== */
    .table-light {
        background-color: #e8f0fa !important;
        color: #1f3a5f;
    }

    /* ===== ช่องรวม ===== */
    #reportTable td.bg-warning,
    #reportTable td.bg-info {
        background-color: #eef4fb !important;
        color: #1f3a5f;
        font-weight: 600;
    }

    /* ===== hover ===== */
    #reportTable tbody tr:hover td {
        background-color: #dde8f6;
    }

    /* ===== แยกสีตามประเภท ===== */

    /*  บังคับล้อ */
    .row-lock td {
        background-color: #e3f2fd;
    }

    /*  ปลดล็อค */
    .row-unlock td {
        background-color: #f5f2e8;
    }

    /*  หมวกนิรภัย */
    .row-helmet td {
        background-color: #ffe1e1;
    }

    /* hover ยังเห็นสีเดิม */
    #reportTable tbody tr:hover td {
        filter: brightness(0.95);
    }

    /* ===== หัวข้อใหญ่ (รายงานการบันทึกความผิดระเบียบจราจร) ===== */
    #reportTable tr.table td {
        font-size: 14px;
        padding: 10px;
        background-color: #e9e2f2;
    }

    /* ===== หัวข้อย่อย (บังคับล้อรถยนต์ / บังคับล้อรถจักรยานยนต์) ===== */
    #reportTable tr.table-light td {
        font-size: 14px;
        /* ปรับขนาดได้ */
        font-weight: 500;
        padding: 8px;
    }
    </style>
</head>


<!-- ปุ่ม toggle -->
<button class="toggle-btn" id="toggleBtn"><i class="bi bi-list"></i></button>

<!-- Sidebar -->
<div class="sidebar collapsed" id="sidebar">
    <ul class="nav flex-column">
        <li><a href="dashboard.php" class="nav-link "><i class="bi bi-house-fill"></i></i> <span>หน้าแรก
                    Dashboard</span></a></li>
        <?php if (in_array($_SESSION['role'], ['admin','officer'])): ?>

        <li>
            <a href="helmet.php" class="nav-link">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                    fill="#FFFFFF">
                    <path
                        d="M504-501Zm48 237q100 0 170-70t70-170q0-100-70-170t-170-70q-53.91 0-104.46 12.5Q397-719 350-690l139 58q41 17 64 51.89t23 78.14q0 58.97-41.5 100.47T434-360H168v96h384ZM179-432h255.18q28.82 0 49.32-20.06T504-501.4q0-20.6-11.5-38.1T461-565l-174-73q-44 41-68.5 95T179-432Zm373 240H168q-29.7 0-50.85-21.15Q96-234.3 96-264v-72q0-105 33.5-193.5t94-152Q284-745 368-780.5T552-816q65 0 121.56 24.37 56.57 24.38 98.99 66.79 42.43 42.42 66.94 98.96Q864-569.33 864-504q0 64.29-24.45 121.27-24.46 56.99-67 99.36Q730-241 673.5-216.5 617-192 552-192Z" />
                </svg>
                <span>หมวกนิรภัย</span>
            </a>
        </li>

        <li>
            <a href="wheel.php" class="nav-link">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                    fill="#FFFFFF">
                    <path
                        d="M428-520h-70 150-80ZM200-200q-83 0-141.5-58.5T0-400q0-83 58.5-141.5T200-600h464l-80-80H440v-80h143q16 0 30.5 6t25.5 17l139 139q78 6 130 63t52 135q0 83-58.5 141.5T760-200q-83 0-141.5-58.5T560-400q0-18 2.5-35.5T572-470L462-360h-66q-14 70-69 115t-127 45Zm560-80q50 0 85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35Zm-560 0q38 0 68.5-22t43.5-58H200v-80h112q-13-36-43.5-58T200-520q-50 0-85 35t-35 85q0 50 35 85t85 35Zm198-160h30l80-80H358q15 17 25 37t15 43Z" />
                </svg>
                <span>บังคับล้อ</span>
            </a>
        </li>

        <?php endif; ?>

        <li><a href="report.php" class="nav-link "><svg xmlns="http://www.w3.org/2000/svg" height="24px"
                    viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
                    <path
                        d="M216-144q-29.7 0-50.85-21.15Q144-186.3 144-216v-528q0-29.7 21.15-50.85Q186.3-816 216-816h171q8-31 33.5-51.5T480-888q34 0 59.5 20.5T573-816h171q29.7 0 50.85 21.15Q816-773.7 816-744v528q0 29.7-21.15 50.85Q773.7-144 744-144H216Zm0-72h528v-528H216v528Zm72-72h288v-72H288v72Zm0-156h384v-72H288v72Zm0-156h384v-72H288v72Zm192-168q10.4 0 17.2-6.8 6.8-6.8 6.8-17.2 0-10.4-6.8-17.2-6.8-6.8-17.2-6.8-10.4 0-17.2 6.8-6.8 6.8-6.8 17.2 0 10.4 6.8 17.2 6.8 6.8 17.2 6.8ZM216-216v-528 528Z" />
                </svg> <span>รายงาน</span></a></li>
        <li><a href="statistics.php" class="nav-link active"><svg xmlns="http://www.w3.org/2000/svg" width="24px"
                    fill="#FFFFFF" class="bi bi-bar-chart-line" viewBox="0 0 16 16">
                    <path
                        d="M11 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h1V7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7h1zm1 12h2V2h-2zm-3 0V7H7v7zm-5 0v-3H2v3z" />
                </svg><span>สถิติการให้บริการ</span></a></li>
    </ul>
    <div class="bottom-links">
        <a href="profile.php" class="nav-link">
            <i class="bi bi-person-circle"></i>
            <span><?php echo htmlspecialchars($officerName); ?></span>
        </a>

        <a href="logout.php" class="nav-link"><svg xmlns="http://www.w3.org/2000/svg" height="24px"
                viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
                <path
                    d="M216-144q-29.7 0-50.85-21.15Q144-186.3 144-216v-528q0-29.7 21.15-50.85Q186.3-816 216-816h264v72H216v528h264v72H216Zm432-168-51-51 81-81H384v-72h294l-81-81 51-51 168 168-168 168Z" />
            </svg> <span>ออกจากระบบ</span></a>
    </div>
</div>


<div class="content expanded" id="content">
    <header class="text-center mb-4">
        <h3>สถิติการให้บริการด้านต่างๆ</h3>
        <h5><?php if($month && $year) echo "ประจำเดือน $monthText $year"; else echo "ประจำเดือน -"; ?></h5>
    </header>

    <!-- เลือกเดือน -->
    <form method="get" class="d-flex justify-content-center mb-3 align-items-end gap-2">
        <div class="col-md-3">
            <label for="monthPicker" class="form-label">เลือกเดือน-ปี</label>
            <input type="month" name="monthyear" id="monthPicker" class="form-control"
                value="<?= sprintf('%04d-%02d', $year, $month) ?>">
        </div>
        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> แสดงข้อมูล</button>
    </form>

    <!-- Export / Print -->
    <div class="d-flex justify-content-start mb-3 gap-2">
        <form method="get">
            <input type="hidden" name="monthyear" value="<?= sprintf('%04d-%02d', $year, $month) ?>">
            <button class="btn btn-outline-success" type="submit" name="export" value="1"><i
                    class="bi bi-file-earmark-excel"></i> Excel</button>
        </form>
        <button class="btn btn-outline-primary" onclick="printTable();"><i class="bi bi-printer"></i> Print</button>
    </div>

    <!-- ตารางเว็บ -->
    <div class="table-responsive">
        <table id="reportTable" class="table table-bordered text-center align-middle">
            <thead class="table-primary">
                <tr>
                    <th>รายการ</th>
                    <?php for($i=1;$i<=31;$i++): ?><th><?= $i ?></th><?php endfor; ?>
                    <th>รวม</th>
                </tr>
            </thead>

            <tbody>
                <?php
                $curSection=''; $curSub='';
                foreach($rows as $k=>$cfg):
                    if($cfg['section'] && $cfg['section']!=$curSection): $curSection=$cfg['section']; $curSub=''; ?>
                <tr class="table">
                    <td colspan="33" class="fw-semibold text-start"><?= $curSection ?></td>
                </tr>
                <?php endif;
                    if($cfg['sub'] && $cfg['sub']!=$curSub): $curSub=$cfg['sub']; ?>
                <tr class="table-light fw-semibold">
                    <td class="text-start ps-3">▸ <?= $curSub ?></td>

                    <?php
    for ($d=1; $d<=31; $d++) {
        $sumDay = 0;
        foreach ($subGroups[$curSub] as $idx) {
            $sumDay += $data[$idx][$d];
        }
        echo "<td>".($sumDay ?: '')."</td>";
    }

    $sumAll = 0;
    foreach ($subGroups[$curSub] as $idx) {
        $sumAll += array_sum($data[$idx]);
    }
    ?>
                    <td class="fw-bold bg-info"><?= $sumAll ?></td>
                </tr>

                <?php endif;
                    $class=($cfg['group']=='lock')?'row-lock':(($cfg['group']=='unlock')?'row-unlock':'row-helmet'); ?>
                <tr class="<?= $class ?>">
                    <td class="text-start ps-5"><?= $cfg['label'] ?></td>
                    <?php for($i=1;$i<=31;$i++): ?><td><?= $data[$k][$i]?:'' ?></td><?php endfor; ?>
                    <td class="fw-bold bg-warning"><?= array_sum($data[$k]) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Print Area (hidden) -->
<div id="printArea" style="display:none;">
    <div id="printHeader" style="text-align:center; margin-bottom:20px;">
        <h3>สถิติการให้บริการด้านต่างๆ</h3>
        <h5><?php if($month && $year) echo "ประจำเดือน $monthText $year"; else echo "ประจำเดือน -"; ?></h5>
    </div>
    <table id="printTable" class="table table-bordered text-center align-middle"></table>
</div>


<script>
// Print Table
function printTable() {
    const printArea = document.getElementById('printArea');
    const reportTable = document.getElementById('reportTable');
    document.getElementById('printTable').innerHTML = reportTable.innerHTML;
    printArea.style.display = 'block';
    window.print();
    printArea.style.display = 'none';
}

// Toggle Sidebar
const toggleBtn = document.getElementById('toggleBtn');
const sidebar = document.getElementById('sidebar');
const content = document.getElementById('content');
toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    content.classList.toggle('expanded');
});
</script>

</body>

</html>
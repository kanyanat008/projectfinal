<?php
session_start();
include_once 'dbconnect.php';

//เช็คuserid
if (!isset($_SESSION['OfficerID'])) {
  header("Location:login.php");
  exit;
}


//ดึงชื่อuser
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

// ตั้งค่า startdate / enddate
$today = date('Y-m-d');
$startdate = isset($_GET['startdate']) && $_GET['startdate'] !== '' ? $_GET['startdate'] : $today;
$enddate   = isset($_GET['enddate']) && $_GET['enddate'] !== '' ? $_GET['enddate'] : $today;

// กำหนดค่าเริ่มต้น
$helmetCount = 0;
$mccWheelCount = 0;
$carWheelCount = 0;

// ดึงข้อมูลหมวกนิรภัย (กรองตามวันที่)
$sql = "SELECT COUNT(*) AS cnt 
        FROM offense 
        WHERE CategoryID = 'C001' 
          AND DATE(date) BETWEEN ? AND ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("ss", $startdate, $enddate);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $helmetCount = $row['cnt'];
}
$stmt->close();

// ดึงข้อมูลบังคับล้อรถจักรยานยนต์
$sql = "SELECT COUNT(*) AS cnt
        FROM offense o
        LEFT JOIN offender o2 ON o2.OffenderID = o.OffenderID
        LEFT JOIN car c ON c.Vehicle_Num = o2.`Vehicle _Num`
        LEFT JOIN (
                                SELECT o3.OffenderID, GROUP_CONCAT(coo.Category_Name ORDER BY coo.Category_Name SEPARATOR ' , ') AS CategoryNames
                                FROM offense o3
                                JOIN `category _of_offense` coo ON FIND_IN_SET(coo.CategoryID, o3.CategoryID)
                                GROUP BY o3.OffenderID
                            ) AS cat ON cat.OffenderID = o2.OffenderID
        WHERE c.Type_Vehicle = 'รถจักรยานยนต์'
          AND CategoryNames <> 'ไม่สวมหมวกนิรภัย'
          AND DATE(o.date) BETWEEN ? AND ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("ss", $startdate, $enddate);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$mccWheelCount = $result['cnt'];
$stmt->close();

// ดึงข้อมูลบังคับล้อรถยนต์
$sql = "  SELECT COUNT(*) AS cnt
        FROM offense o
        LEFT JOIN offender o2 ON o2.OffenderID = o.OffenderID
        LEFT JOIN car c ON c.Vehicle_Num = o2.`Vehicle _Num`
        LEFT JOIN (
                                SELECT o3.OffenderID, GROUP_CONCAT(coo.Category_Name ORDER BY coo.Category_Name SEPARATOR ' , ') AS CategoryNames
                                FROM offense o3
                                JOIN `category _of_offense` coo ON FIND_IN_SET(coo.CategoryID, o3.CategoryID)
                                GROUP BY o3.OffenderID
                            ) AS cat ON cat.OffenderID = o2.OffenderID
        WHERE c.Type_Vehicle = 'รถยนต์'
          AND CategoryNames <> 'ไม่สวมหมวกนิรภัย'
          AND DATE(o.date) BETWEEN ? AND ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("ss", $startdate, $enddate);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$carWheelCount = $result['cnt'];
$stmt->close();

// แบ่งช่วงเวลากราฟ
$timeSlots = [
    'helmet' => [
        '09:00-10:00' => 0,
        '10:01-11:00' => 0,
        '11:01-12:00' => 0,
        '12:01-13:00' => 0,
        '13:01-14:00' => 0,
        '14:01-15:00' => 0,
        '15:01-16:00' => 0
    ],
    'mcc' => [
        '09:00-10:00' => 0,
        '10:01-11:00' => 0,
        '11:01-12:00' => 0,
        '12:01-13:00' => 0,
        '13:01-14:00' => 0,
        '14:01-15:00' => 0,
        '15:01-16:00' => 0
    ],
    'car' => [
        '09:00-10:00' => 0,
        '10:01-11:00' => 0,
        '11:01-12:00' => 0,
        '12:01-13:00' => 0,
        '13:01-14:00' => 0,
        '14:01-15:00' => 0,
        '15:01-16:00' => 0
    ]
];


$sql = "SELECT 
            TIME(o.time) AS t,
            o.CategoryID,
            c.Type_Vehicle,
            coo.Category_Name
        FROM offense o
        LEFT JOIN offender o2 ON o2.OffenderID = o.OffenderID
        LEFT JOIN car c ON c.Vehicle_Num = o2.`Vehicle _Num`
        LEFT JOIN `category _of_offense` coo ON coo.CategoryID = o.CategoryID
        WHERE DATE(o.date) BETWEEN ? AND ?";

$stmt = $con->prepare($sql);
$stmt->bind_param("ss", $startdate, $enddate);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    $time = strtotime($row['t']);

    // ระบุช่วงเวลา
    foreach ($timeSlots['helmet'] as $slot => $v) {
        [$start, $end] = explode('-', $slot);
        if ($time >= strtotime($start) && $time <= strtotime($end)) {

            // 🟥 หมวกนิรภัย
            if ($row['CategoryID'] === 'C001') {
                $timeSlots['helmet'][$slot]++;
            }

            // 🟦 บังคับล้อรถจักรยานยนต์
            elseif ($row['Type_Vehicle'] === 'รถจักรยานยนต์'
                && $row['Category_Name'] !== 'ไม่สวมหมวกนิรภัย') {
                $timeSlots['mcc'][$slot]++;
            }

            // 🟩 บังคับล้อรถยนต์
            elseif ($row['Type_Vehicle'] === 'รถยนต์'
                && $row['Category_Name'] !== 'ไม่สวมหมวกนิรภัย') {
                $timeSlots['car'][$slot]++;
            }

            break;
        }
    }
}

$stmt->close();

// กราฟพื้นที่

$sql = "SELECT
    l.`Location _Name` AS location,
    CASE
        WHEN o.CategoryID = 'C001'
            THEN 'ไม่สวมหมวกนิรภัย'
        WHEN c.Type_Vehicle = 'รถจักรยานยนต์'
            AND coo.Category_Name <> 'ไม่สวมหมวกนิรภัย'
            THEN 'บังคับล้อรถจักรยานยนต์'
        WHEN c.Type_Vehicle = 'รถยนต์'
            AND coo.Category_Name <> 'ไม่สวมหมวกนิรภัย'
            THEN 'บังคับล้อรถยนต์'
    END AS offense_type,
    COUNT(*) AS cnt
FROM offense o
LEFT JOIN offender o2 ON o2.OffenderID = o.OffenderID
LEFT JOIN car c ON c.Vehicle_Num = o2.`Vehicle _Num`
LEFT JOIN location l ON l.Location_ID = o.Location_ID
LEFT JOIN `category _of_offense` coo ON coo.CategoryID = o.CategoryID
WHERE DATE(o.date) BETWEEN ? AND ?
GROUP BY l.`Location _Name`, offense_type
HAVING offense_type IS NOT NULL
ORDER BY l.`Location _Name`, offense_type";

$locations = [];
$dataMatrix = [
    'ไม่สวมหมวกนิรภัย' => [],
    'บังคับล้อรถจักรยานยนต์' => [],
    'บังคับล้อรถยนต์' => []
];

$stmt = $con->prepare($sql);
$stmt->bind_param("ss", $startdate, $enddate);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $loc = $row['location'];
    $type = $row['offense_type'];
    $cnt = (int)$row['cnt'];

    $locations[$loc] = true;
    $dataMatrix[$type][$loc] = $cnt;
}
$stmt->close();

$locationLabels = array_keys($locations);

// เตรียม dataset สำหรับ Chart.js
$datasets = [
    [
        'label' => 'ไม่สวมหมวกนิรภัย',
        'data' => array_map(fn($loc) => $dataMatrix['ไม่สวมหมวกนิรภัย'][$loc] ?? 0, $locationLabels),
        'backgroundColor' => '#c72c2cff'
    ],
    [
        'label' => 'บังคับล้อรถจักรยานยนต์',
        'data' => array_map(fn($loc) => $dataMatrix['บังคับล้อรถจักรยานยนต์'][$loc] ?? 0, $locationLabels),
        'backgroundColor' => '#2987c1ff'
    ],
    [
        'label' => 'บังคับล้อรถยนต์',
        'data' => array_map(fn($loc) => $dataMatrix['บังคับล้อรถยนต์'][$loc] ?? 0, $locationLabels),
        'backgroundColor' => '#e3cc2c'
    ]
];



//กราฟประเภทความผิด
$labels = [];
$values = [];

$sql = "SELECT 
            coo.Category_Name,
            COUNT(*) AS total
        FROM offense o
        JOIN `category _of_offense` coo 
            ON FIND_IN_SET(coo.CategoryID, o.CategoryID)
        WHERE DATE(o.date) BETWEEN ? AND ?
        GROUP BY coo.Category_Name
        ORDER BY total DESC";


$stmt = $con->prepare($sql);
$stmt->bind_param("ss", $startdate, $enddate);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $labels[] = $row['Category_Name'];
    $values[] = $row['total'];
}
$stmt->close();

$colors = ['#2987c1ff','rgba(255, 58, 58, 1)','#51cf66','#ffa94d','#845ef7'];
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการและวิเคราะห์ข้อมูลการกระทำผิดกฎจราจร</title>
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

    .card-custom {
        background: #e6f0fa;
        border: 1px solid #d1e3f0;
        border-radius: 12px;
        padding: 20px;
        position: relative;
        text-align: center;
        height: 100%;
    }

    .card-custom p {
        font-size: 50px;
        font-weight: 600;
        margin: 0;
        color: #1f3a5f;
        padding-bottom: 10px;
    }

    .card-custom h5 {
        font-size: 22px;
        font-weight: 600;
    }

    .card-custom button {
        position: absolute;
        right: 10px;
        font-size: 13px;
        bottom: 10px;
        color: rgb(134, 172, 223);
    }

    .chart-box {
        background: #ffffff;
        border: 1px solid #dee2e6;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 20px;
    }

    .pichelmet {
        width: 40%;
    }

    .info-link {
        font-size: 13px;
        color: #0d6efd;
        position: absolute;
        top: 8px;
        right: 12px;
        text-decoration: none;
    }

    .info-link:hover {
        text-decoration: underline;
    }

    header h3 {
        font-weight: 600;
        color: #1f3a5f;
        margin-bottom: 20px;
        padding-top: 20px;
    }

    header h5 {
        color: #495057;
        font-weight: 400;
    }


    .buttonsearch {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 8px 12px 8px 16px;
        gap: 8px;
        height: 40px;
        width: 128px;
        border: none;
        background: #0043a0c3;
        border-radius: 20px;
        cursor: pointer;
        color: #ffffffff;
    }

    .buttonsearch:hover {
        background: #1f3a5f;
    }

    .buttonsearch:hover .svg-icon {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        25% {
            transform: rotate(-8deg);
        }

        50% {
            transform: rotate(0deg);
        }

        75% {
            transform: rotate(8deg);
        }

        100% {
            transform: rotate(0deg);
        }
    }

    .sidebar i {
        font-size: 22px;
    }
    </style>
</head>

<body>

    <!-- ปุ่ม toggle -->
    <button class="toggle-btn" id="toggleBtn"><i class="bi bi-list"></i></button>

    <!-- Sidebar -->
    <div class="sidebar collapsed" id="sidebar">
        <ul class="nav flex-column">
            <li><a href="#" class="nav-link active"><i class="bi bi-house-fill"></i></i> <span>หน้าแรก
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
            <?php if (in_array($_SESSION['role'], ['admin','executive'])): ?>
            <li><a href="report.php" class="nav-link"><svg xmlns="http://www.w3.org/2000/svg" height="24px"
                        viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
                        <path
                            d="M216-144q-29.7 0-50.85-21.15Q144-186.3 144-216v-528q0-29.7 21.15-50.85Q186.3-816 216-816h171q8-31 33.5-51.5T480-888q34 0 59.5 20.5T573-816h171q29.7 0 50.85 21.15Q816-773.7 816-744v528q0 29.7-21.15 50.85Q773.7-144 744-144H216Zm0-72h528v-528H216v528Zm72-72h288v-72H288v72Zm0-156h384v-72H288v72Zm0-156h384v-72H288v72Zm192-168q10.4 0 17.2-6.8 6.8-6.8 6.8-17.2 0-10.4-6.8-17.2-6.8-6.8-17.2-6.8-10.4 0-17.2 6.8-6.8 6.8-6.8 17.2 0 10.4 6.8 17.2 6.8 6.8 17.2 6.8ZM216-216v-528 528Z" />
                    </svg> <span>รายงาน</span></a></li>
            <li><a href="statistics.php" class="nav-link"><svg xmlns="http://www.w3.org/2000/svg" width="24px"
                        fill="#FFFFFF" class="bi bi-bar-chart-line" viewBox="0 0 16 16">
                        <path
                            d="M11 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h1V7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7h1zm1 12h2V2h-2zm-3 0V7H7v7zm-5 0v-3H2v3z" />
                    </svg><span>สถิติการให้บริการ</span></a></li>
            <?php endif; ?>
        </ul>
        <div class="bottom-links">
            <a href="profile.php" class="nav-link"><i class="bi bi-person-circle"></i>
                <span><?php echo htmlspecialchars($officerName); ?></span></a>
            <a href="logout.php" class="nav-link"><svg xmlns="http://www.w3.org/2000/svg" height="24px"
                    viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
                    <path
                        d="M216-144q-29.7 0-50.85-21.15Q144-186.3 144-216v-528q0-29.7 21.15-50.85Q186.3-816 216-816h264v72H216v528h264v72H216Zm432-168-51-51 81-81H384v-72h294l-81-81 51-51 168 168-168 168Z" />
                </svg> <span>ออกจากระบบ</span></a>
        </div>
    </div>

    <!-- Content -->
    <div class="content expanded" id="content">
        <header class="text-center mb-4">
            <h3>ระบบจัดการและวิเคราะห์ข้อมูลการกระทำผิดกฎจราจรในพื้นที่มหาวิทยาลัยสงขลานครินทร์</h3>
            <h5 id="currentDate"></h5>
        </header>

        <!-- Filter bar -->
        <form method="get" action="" class="d-flex justify-content-center align-items-center mb-4 flex-wrap gap-2">
            <span>ตั้งแต่วันที่</span>
            <input type="date" name="startdate" class="form-control w-auto" value="<?= htmlspecialchars($startdate) ?>">
            <span>ถึง</span>
            <input type="date" name="enddate" class="form-control w-auto" value="<?= htmlspecialchars($enddate) ?>">
            <button type="submit" class="buttonsearch">
                <span class="lable">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                        class="bi bi-search" viewBox="0 0 16 16">
                        <path
                            d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0" />
                    </svg> ค้นหา
                </span>
            </button>
        </form>

        <!-- Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card-custom d-flex flex-row align-items-center p-3 position-relative">
                    <img src="img/helmet.png" alt="helmet" class="pichelmet me-3">
                    <div class="flex-grow-1">
                        <h5>หมวกนิรภัย</h5>
                        <h6>จำนวน (ครั้ง)</h6>
                        <p><?= $helmetCount ?></p>
                    </div>
                    <a href="morehelmet.php?startdate=<?= $startdate ?>&enddate=<?= $enddate ?>"><button
                            class="btn btn-link rounded-pill px-3">ข้อมูลเพิ่มเติม <i
                                class="bi bi-arrow-right"></i></button></a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-custom d-flex flex-row align-items-center p-3 position-relative">
                    <img src="img/motorcycle.png" alt="motorcycle" class="pichelmet me-3">
                    <div>
                        <h5>บังคับล้อรถจักรยานยนต์</h5>
                        <h6>จำนวน (ครั้ง)</h6>
                        <p><?= $mccWheelCount ?></p>
                    </div>
                    <a href="moremccwheel.php?startdate=<?= $startdate ?>&enddate=<?= $enddate ?>"><button
                            class="btn btn-link rounded-pill px-3">ข้อมูลเพิ่มเติม <i
                                class="bi bi-arrow-right"></i></button></a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-custom d-flex flex-row align-items-center p-3 position-relative">
                    <img src="img/car.png" alt="car" class="pichelmet me-3">
                    <div>
                        <h5>บังคับล้อรถยนต์</h5>
                        <h6>จำนวน (ครั้ง)</h6>
                        <p><?= $carWheelCount?></p>
                    </div>
                    <a href="morecarwheel.php?startdate=<?= $startdate ?>&enddate=<?= $enddate ?>"><button
                            class="btn btn-link rounded-pill px-3">ข้อมูลเพิ่มเติม <i
                                class="bi bi-arrow-right"></i></button></a>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row">
            <div class="col-md-6">
                <div class="chart-box ">
                    <h6>กราฟแสดงเปอร์เซ็นต์การกระทำผิดที่เกิดขึ้น</h6>
                    <div style="max-width:330px; margin:auto;">
                        <canvas id="percentChart"></canvas>
                    </div>
                </div>

            </div>
            <div class="col-md-6">
                <div class="chart-box">
                    <h6>กราฟเส้นแสดงจำนวนการกระทำผิดตามช่วงเวลา</h6>
                    <canvas id="timeChart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-box">
                    <h6>กราฟแสดงพื้นที่การฝ่าฝืน แยกตามสถานที่และประเภทความผิด</h6>
                    <canvas id="locationOffenseChart"></canvas>
                </div>
            </div>

            <div class="col-md-6">
                <div class="chart-box">
                    <h6>กราฟแสดงประเภทการกระทำผิดทั้งหมด</h6>
                    <canvas id="offenseCategoryChart"></canvas>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- ปุ่มเมนูsidebar -->
    <script>
    const toggleBtn = document.getElementById('toggleBtn');
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');

    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        content.classList.toggle('expanded');
    });

    // วันที่จากเครื่อง
    const currentDate = new Date();
    const options = {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };
    document.getElementById('currentDate').textContent =
        currentDate.toLocaleDateString('th-TH', options);
    </script>
    <!-- คำนวณค่ามาที่js -->
    <script>
    const helmetCount = <?= $helmetCount ?>;
    const mccWheelCount = <?= $mccWheelCount ?>;
    const carWheelCount = <?= $carWheelCount ?>;
    </script>

    <script>
    const timeLabels = <?= json_encode(array_keys($timeSlots['helmet'])) ?>;
    const helmetTime = <?= json_encode(array_values($timeSlots['helmet'])) ?>;
    const mccTime = <?= json_encode(array_values($timeSlots['mcc'])) ?>;
    const carTime = <?= json_encode(array_values($timeSlots['car'])) ?>;
    </script>

    <script>
    const locationLabels = <?= json_encode($locationLabels) ?>;
    const offenseDatasets = <?= json_encode($datasets) ?>;
    </script>

    <script>
    const offenseLabels = <?= json_encode($labels) ?>;
    const offenseValues = <?= json_encode($values) ?>;
    </script>
    <!-- สี -->
    <script>
    const offenseColors = [
        '#dd3668',
        '#8131b6',
        '#ddb040',
        '#3dc199',
        '#ef7c1e',
        '#2d65b3',
        '#bf1515',
        '#adb5bd'
    ];
    </script>

    <!-- กราฟ -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

    <!-- กราฟแสดงเปอร์เซ็นต์การกระทำผิดที่เกิดขึ้น -->
    <script>
    const total =
        helmetCount + mccWheelCount + carWheelCount;

    new Chart(document.getElementById('percentChart'), {
        type: 'pie',
        data: {
            labels: [
                'ไม่สวมหมวกนิรภัย',
                'บังคับล้อรถจักรยานยนต์',
                'บังคับล้อรถยนต์'
            ],
            datasets: [{
                data: [
                    helmetCount,
                    mccWheelCount,
                    carWheelCount
                ],
                backgroundColor: [
                    '#c72c2cff',
                    '#2987c1ff',
                    '#e3cc2c'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                datalabels: {
                    color: '#fff',
                    font: {
                        weight: 'bold',
                        size: 14
                    },
                    formatter: (value) => {
                        if (total === 0) return '0%';
                        const percent = (value / total * 100).toFixed(1);
                        return percent + '%';
                    }
                }
            }
        },
        plugins: [ChartDataLabels]
    });
    </script>


    <!-- กราฟแสดงประเภทการกระทำผิดที่พบบ่อย -->
    <script>
    new Chart(document.getElementById('offenseCategoryChart'), {
        type: 'bar',
        data: {
            labels: offenseLabels,
            datasets: [{
                label: 'จำนวนการกระทำผิด (ครั้ง)',
                data: offenseValues,
                backgroundColor: offenseColors,
                barThickness: 22
            }]
        },
        options: {
            indexAxis: 'y', // ⭐ แนวนอน
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'กราฟแสดงจำนวนการกระทำผิด จำแนกตามประเภทความผิด'
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'จำนวนครั้ง'
                    }
                },
                y: {
                    ticks: {
                        autoSkip: false
                    }
                }
            }
        }
    });
    </script>

    <!-- กราฟเส้นแสดงจำนวนการกระทำผิดตามช่วงเวลา -->
    <script>
    new Chart(document.getElementById('timeChart'), {
        type: 'line',
        data: {
            labels: timeLabels,
            datasets: [{
                    label: 'ไม่สวมหมวกนิรภัย',
                    data: helmetTime,
                    borderColor: '#c72c2cff',
                    backgroundColor: 'rgba(255,107,107,0.2)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'บังคับล้อรถจักรยานยนต์',
                    data: mccTime,
                    borderColor: '#2987c1ff',
                    backgroundColor: 'rgba(77,171,247,0.2)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'บังคับล้อรถยนต์',
                    data: carTime,
                    borderColor: '#e3cc2c',
                    backgroundColor: 'rgba(207, 182, 81, 0.27)',
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'จำนวนครั้ง'
                    }
                }
            }
        }
    });
    </script>
    <!-- กราฟแสดงพื้นที่การฝ่าฝืน -->
    <script>
    new Chart(document.getElementById('locationOffenseChart'), {
        type: 'bar',
        data: {
            labels: locationLabels,
            datasets: offenseDatasets
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    stacked: true
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'จำนวนครั้ง'
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top'
                }
            }
        }
    });
    </script>

</body>

</html>
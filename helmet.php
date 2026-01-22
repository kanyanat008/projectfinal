<?php
include_once 'dbconnect.php';
include 'auth.php';
requireRole(['admin','officer']);

// ดึงชื่อ Officer ที่ล็อกอินอยู่
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

$location = [];
$sql = "SELECT l.`Location _Name` FROM location l where l.Location_ID like 'L1%'"; 
$result = $con->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $location[] = $row['Location _Name'];
    }
}

// ตรวจสอบว่ามีการกดบันทึก
if (isset($_POST['save'])) {
    $locationName = $_POST['location'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $card_id = trim($_POST['card_id']);
    $vehicle_num = $_POST['vehicle_num'] ?? '';
    $province = $_POST['province'] ?? '';
    $brand = $_POST['brand'] ?? '';
    $color = $_POST['color'] ?? '';
    $motorcycle = $_POST['motorcycle'] ?? 'รถจักรยานยนต์';
    $status = "";
    $officer_id = $_SESSION['OfficerID']; 
    $category_id = 'C001';
   

    // -----------------------------
    // ดึง Location_ID จากชื่อ
    // -----------------------------
    $stmtLoc = $con->prepare("SELECT Location_ID FROM location WHERE `Location _Name` = ?");
    $stmtLoc->bind_param("s", $locationName);
    $stmtLoc->execute();
    $resultLoc = $stmtLoc->get_result();
    $location_id = $resultLoc->num_rows > 0 ? $resultLoc->fetch_assoc()['Location_ID'] : null;
    $stmtLoc->close();

    // -----------------------------
    // สร้างค่า OffenderID ใหม่
    // -----------------------------
    $sqlLast = "SELECT OffenderID FROM offense ORDER BY OffenderID DESC LIMIT 1";
    $result = $con->query($sqlLast);
    if ($row = $result->fetch_assoc()) {
        $lastID = $row['OffenderID'];
        $num = intval(substr($lastID, 1));
        $newID = "F" . str_pad($num + 1, 3, "0", STR_PAD_LEFT);
    } else {
        $newID = "F001";
    }

    // -----------------------------
    // กำหนดประเภทผู้กระทำผิด และดึงชื่อ/หน่วยงานถ้าเป็นบุคลากรหรือนักศึกษา
    // -----------------------------
    $len = strlen($card_id);
$type_offender = "";
$personnel_id = null;
$student_id = null;
$name_offender = "";
$org_offender = "";

/* ===============================
   บุคลากร (7 หลัก)
================================ */
if ($len == 7) {

    $type_offender = "บุคลากร";
    $personnel_id = $card_id;

    // เช็คว่ามีอยู่แล้วหรือยัง
    $stmt = $con->prepare("SELECT Personnel_Name, Department 
                           FROM personnel 
                           WHERE Personnel_ID = ?");
    $stmt->bind_param("s", $personnel_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        // มีอยู่แล้ว
        $name_offender = $row['Personnel_Name'];
        $org_offender  = $row['Department'];
    } else {
        // ยังไม่มี → เพิ่มใหม่
        $name_offender = $_POST['fullname'];
        $org_offender  = $_POST['org'];

        $stmtIns = $con->prepare(
            "INSERT INTO personnel (Personnel_ID, Personnel_Name, Department)
             VALUES (?, ?, ?)"
        );
        $stmtIns->bind_param("sss", 
            $personnel_id, 
            $name_offender, 
            $org_offender
        );
        $stmtIns->execute();
        $stmtIns->close();
    }
    $stmt->close();
}

/* ===============================
   นักศึกษา (10 หลัก)
================================ */
elseif ($len == 10) {

    $type_offender = "นักศึกษา";
    $student_id = $card_id;

    // เช็คว่ามีอยู่แล้วหรือยัง
    $stmt = $con->prepare("SELECT Student_Name, Faculty 
                           FROM student 
                           WHERE Student_ID = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        // มีอยู่แล้ว
        $name_offender = $row['Student_Name'];
        $org_offender  = $row['Faculty'];
    } else {
        // ยังไม่มี → เพิ่มใหม่
        $name_offender = $_POST['fullname'];
        $org_offender  = $_POST['org'];

        $stmtIns = $con->prepare(
            "INSERT INTO student (Student_ID, Student_Name, Faculty)
             VALUES (?, ?, ?)"
        );
        $stmtIns->bind_param("sss", 
            $student_id, 
            $name_offender, 
            $org_offender
        );
        $stmtIns->execute();
        $stmtIns->close();
    }
    $stmt->close();
}

/* ===============================
   บุคคลภายนอก (13 หลัก)
================================ */
elseif ($len == 13) {

    $type_offender = "บุคคลภายนอก";
    $name_offender = $_POST['fullname'];
    $org_offender  = $_POST['org'];
}


    // -----------------------------
    // Insert offense
    // -----------------------------
    $stmt = $con->prepare("INSERT INTO offense 
        (OffenderID, CategoryID, Location_ID, OfficerID, date, time, Status,timestamp)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssssss", 
        $newID, $category_id, $location_id, $officer_id, $date, $time, $status
    );
    $stmt->execute();
    $stmt->close();

    // -----------------------------
    // Insert offender
    // -----------------------------
    $id_num = ($len == 13) ? $card_id : null; // เก็บเฉพาะบุคคลภายนอก
$stmt = $con->prepare("INSERT INTO offender 
    (OffenderID, Name, Identification_Num, type_offender, `Vehicle _Num`, Personnel_ID, Student_ID)
    VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", 
    $newID, $name_offender, $id_num, $type_offender, $vehicle_num, $personnel_id, $student_id
);
$stmt->execute();
$stmt->close();

    // -----------------------------
// Insert / Update car
// -----------------------------
$sqlCar = "SELECT 1 FROM car WHERE Vehicle_Num=?";
$stmt = $con->prepare($sqlCar);
$stmt->bind_param("s", $vehicle_num);
$stmt->execute();
$resultCar = $stmt->get_result();
$stmt->close();

if ($resultCar->num_rows == 0) {

    // === รถยังไม่เคยมี → INSERT ===
    $stmtIns = $con->prepare("
        INSERT INTO car (Vehicle_Num, Province, Type_Vehicle, Brand, Color) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmtIns->bind_param(
        "sssss",
        $vehicle_num,
        $province,
        $motorcycle,   // <-- Type_Vehicle จะไม่ว่างแล้ว
        $brand,
        $color
    );
    $stmtIns->execute();
    $stmtIns->close();

} else {

    // === รถมีอยู่แล้ว → UPDATE ===
    $stmtUpd = $con->prepare("
        UPDATE car
        SET Province = ?,
            Type_Vehicle = ?,
            Brand = ?,
            Color = ?
        WHERE Vehicle_Num = ?
    ");
    $stmtUpd->bind_param(
        "sssss",
        $province,
        $motorcycle,   // <-- แก้รถเก่าที่เคยว่าง
        $brand,
        $color,
        $vehicle_num
    );
    $stmtUpd->execute();
    $stmtUpd->close();
}


    header("Location: " . $_SERVER['PHP_SELF']);
exit;
}

// ตรวจสอบการค้นหาแบบ AJAX
if(isset($_GET['action']) && $_GET['action'] === 'search_card' && !empty($_GET['term'])){
    $term = $_GET['term']."%"; // พิมพ์แค่บางตัว
    $stmt = $con->prepare("WITH AllPeople AS (
    SELECT 
        s.Student_ID AS ID,
        s.Student_Name AS Fullname,
        s.Faculty AS Org
    FROM student s

    UNION ALL

    SELECT 
        p.Personnel_ID AS ID,
        p.Personnel_Name AS Fullname,
        p.Department AS Org
    FROM personnel p
)

SELECT 
    a.ID AS Card_ID,
    a.Fullname,
    a.Org,
    MAX(c.Vehicle_Num) AS Vehicle_Num,
    MAX(c.Province) AS Province,
    MAX(c.Brand) AS Brand,
    MAX(c.Color) AS Color
FROM AllPeople a
LEFT JOIN offender o 
    ON o.Student_ID = a.ID 
    OR o.Personnel_ID = a.ID
LEFT JOIN car c 
    ON c.Vehicle_Num = o.`Vehicle _Num`
WHERE a.ID LIKE ?
GROUP BY a.ID, a.Fullname, a.Org
LIMIT 10;
    ");
    $stmt->bind_param("s",$term);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while($row = $result->fetch_assoc()){
        $data[] = $row;
    }
    echo json_encode($data);
    exit;
}

//ดึงข้อมูล รายการบันทึก

$sqlRecords = "SELECT o2.OffenderID,
    COALESCE(s.Student_ID, p.Personnel_ID, o.Identification_Num) AS Card_ID,
    COALESCE(s.Faculty, p.Department) AS Org,
    o.Name,
    coo.Category_Name,
    c.Type_Vehicle,
    c.Vehicle_Num,
    c.Province,
    c.Brand,
    c.Color,
    l.`Location _Name`,
    o2.`date`,
    o2.`time`
FROM offender o
LEFT JOIN student s ON s.Student_ID = o.Student_ID
LEFT JOIN personnel p ON p.Personnel_ID = o.Personnel_ID
LEFT JOIN offense o2 ON o2.OffenderID = o.OffenderID
LEFT JOIN `category _of_offense` coo ON coo.CategoryID = o2.CategoryID
LEFT JOIN car c ON c.Vehicle_Num = o.`Vehicle _Num`
LEFT JOIN location l ON l.Location_ID = o2.Location_ID
WHERE (s.Student_ID IS NOT NULL
   OR p.Personnel_ID IS NOT NULL
   OR o.Identification_Num IS NOT NULL)
   AND c.Type_Vehicle = 'รถจักรยานยนต์'
   AND o2.CategoryID = 'C001'
   AND o2.timestamp >= CURDATE()
AND o2.timestamp < CURDATE() + INTERVAL 1 DAY";

$params = [];
$types  = "";

// ถ้าไม่ใช่ admin → เห็นเฉพาะของตัวเอง
if ($_SESSION['role'] !== 'admin') {
    $sqlRecords .= " AND o2.OfficerID = ?";
    $params[] = $_SESSION['OfficerID'];
    $types .= "s";
}
$sqlRecords .= " ORDER BY o2.timestamp DESC";

$stmtRecords = $con->prepare($sqlRecords);

if (!empty($params)) {
    $stmtRecords->bind_param($types, ...$params);
}

$stmtRecords->execute();
$resultRecords = $stmtRecords->get_result();

// ===== ลบข้อมูลผู้กระทำผิด (AJAX) =====
if (isset($_POST['action']) && $_POST['action'] === 'delete_offense') {

    if ($_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo "permission_denied";
        exit;
    }

    $offenderID = $_POST['offenderID'];

    // ลบ offense ก่อน (FK)
    $stmt = $con->prepare("DELETE FROM offense WHERE OffenderID = ?");
    $stmt->bind_param("s", $offenderID);
    $stmt->execute();
    $stmt->close();

    // ลบ offender
    $stmt = $con->prepare("DELETE FROM offender WHERE OffenderID = ?");
    $stmt->bind_param("s", $offenderID);
    $stmt->execute();
    $stmt->close();

    echo "OK";
    exit;
}

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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.12.4/dist/sweetalert2.min.css"
        integrity="sha256-KIZHD6c6Nkk0tgsncHeNNwvNU1TX8YzPrYn01ltQwFg=" crossorigin="anonymous">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">


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

    .sidebar i {
        font-size: 22px;
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

    header h3 {
        font-weight: 600;
        color: #1f3a5f;
        margin-bottom: 20px;
        padding-top: 20px;
    }

    .form-box {
        background: #e6f0fa;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    /*menuเลขบัตร*/
    .dropdown-menu.show {
        display: block;
        max-height: 200px;
        overflow-y: auto;
    }

    /* ===== สีตารางให้เหมือนหน้า report ===== */
    #recordTableDT thead th {
        color: #ffffff;
        background-color: #1f3a5f;
        font-weight: 500;
        text-align: center;
    }

    /* hover แถว */
    #recordTableDT tbody tr:hover td {
        background-color: #f6f3cdff !important;
        cursor: pointer;
    }
    </style>
</head>

<body>

    <!-- ปุ่ม toggle -->
    <button class="toggle-btn" id="toggleBtn"><i class="bi bi-list"></i></button>

    <!-- Sidebar -->
    <div class="sidebar collapsed" id="sidebar">
        <ul class="nav flex-column">
            <li><a href="dashboard.php" class="nav-link "><i class="bi bi-house-fill"></i></i> <span>หน้าแรก
                        Dashboard</span></a></li>
            <li><a href="#" class="nav-link active"><svg xmlns="http://www.w3.org/2000/svg" height="24px"
                        viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
                        <path
                            d="M504-501Zm48 237q100 0 170-70t70-170q0-100-70-170t-170-70q-53.91 0-104.46 12.5Q397-719 350-690l139 58q41 17 64 51.89t23 78.14q0 58.97-41.5 100.47T434-360H168v96h384ZM179-432h255.18q28.82 0 49.32-20.06T504-501.4q0-20.6-11.5-38.1T461-565l-174-73q-44 41-68.5 95T179-432Zm373 240H168q-29.7 0-50.85-21.15Q96-234.3 96-264v-72q0-105 33.5-193.5t94-152Q284-745 368-780.5T552-816q65 0 121.56 24.37 56.57 24.38 98.99 66.79 42.43 42.42 66.94 98.96Q864-569.33 864-504q0 64.29-24.45 121.27-24.46 56.99-67 99.36Q730-241 673.5-216.5 617-192 552-192Z" />
                    </svg><span>หมวกนิรภัย</span></a></li>
            <li><a href="wheel.php" class="nav-link"><svg xmlns="http://www.w3.org/2000/svg" height="24px"
                        viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
                        <path
                            d="M428-520h-70 150-80ZM200-200q-83 0-141.5-58.5T0-400q0-83 58.5-141.5T200-600h464l-80-80H440v-80h143q16 0 30.5 6t25.5 17l139 139q78 6 130 63t52 135q0 83-58.5 141.5T760-200q-83 0-141.5-58.5T560-400q0-18 2.5-35.5T572-470L462-360h-66q-14 70-69 115t-127 45Zm560-80q50 0 85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35Zm-560 0q38 0 68.5-22t43.5-58H200v-80h112q-13-36-43.5-58T200-520q-50 0-85 35t-35 85q0 50 35 85t85 35Zm198-160h30l80-80H358q15 17 25 37t15 43Z" />
                    </svg> <span>บังคับล้อ</span></a></li>
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

    <!-- Content -->
    <div class="content expanded" id="content">
        <header class="text-center mb-4">
            <h3>การบันทึกผู้กระทำความผิดระเบียบวินัยจราจร (หมวกนิรภัย)</h3>
        </header>

        <!-- ฟอร์ม -->
        <div class="form-box">
            <form id="offenseForm" method="post">
                <input type="hidden" name="save" value="1"> <!-- เพื่อให้ฟอร์มส่งไปจริง -->

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">สถานที่ตั้ง</label>
                        <select class="form-select" name="location">
                            <option value="" disabled selected hidden></option>
                            <?php
                            if (!empty($location)) {
                                foreach ($location as $locations) {
                                    echo '<option value="'.htmlspecialchars($locations).'">'.htmlspecialchars($locations).'</option>';
                                }
                            } else {
                                echo '<option>ไม่มีข้อมูลสถานที่</option>';
                              }
                             ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">วันที่</label>
                        <input type="date" id="date" name="date" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">เวลา</label>
                        <input type="time" id="time" name="time" class="form-control">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">เลขบัตร</label>
                        <input type="text" class="form-control" id="card_id" name="card_id" autocomplete="off">
                        <ul class="dropdown-menu w-100" id="card_suggestions"></ul>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">ชื่อ-สกุล</label>
                        <input type="text" class="form-control" id="fullname" name="fullname">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">หน่วยงาน/คณะ</label>
                        <input type="text" class="form-control" id="org" name="org">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">ประเภทความผิด</label>
                        <input type="hidden" name="violation" value="ไม่สวมหมวกนิรภัย">
                        <input type="text" class="form-control" value="ไม่สวมหมวกนิรภัย" readonly>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">ประเภทรถ</label>
                        <input type="hidden" name="motorcycle" value="รถจักรยานยนต์">
                        <input type="text" class="form-control" value="รถจักรยานยนต์" readonly>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-2">
                        <label class="form-label">เลขทะเบียนรถ</label>
                        <input type="text" class="form-control" id="vehicle_num" name="vehicle_num">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">จังหวัด</label>
                        <input class="form-control" list="provinceList" id="province" name="province">
                        <datalist id="provinceList">
                            <option value="กรุงเทพมหานคร">
                            <option value="กระบี่">
                            <option value="กาญจนบุรี">
                            <option value="กาฬสินธุ์">
                            <option value="กำแพงเพชร">
                            <option value="ขอนแก่น">
                            <option value="จันทบุรี">
                            <option value="ฉะเชิงเทรา">
                            <option value="ชลบุรี">
                            <option value="ชัยนาท">
                            <option value="ชัยภูมิ">
                            <option value="ชุมพร">
                            <option value="เชียงราย">
                            <option value="เชียงใหม่">
                            <option value="ตรัง">
                            <option value="ตราด">
                            <option value="ตาก">
                            <option value="นครนายก">
                            <option value="นครปฐม">
                            <option value="นครพนม">
                            <option value="นครราชสีมา">
                            <option value="นครศรีธรรมราช">
                            <option value="นครสวรรค์">
                            <option value="นนทบุรี">
                            <option value="นราธิวาส">
                            <option value="น่าน">
                            <option value="บึงกาฬ">
                            <option value="บุรีรัมย์">
                            <option value="เบตง">
                            <option value="ปทุมธานี">
                            <option value="ประจวบคีรีขันธ์">
                            <option value="ปราจีนบุรี">
                            <option value="ปัตตานี">
                            <option value="พระนครศรีอยุธยา">
                            <option value="พังงา">
                            <option value="พัทลุง">
                            <option value="พิจิตร">
                            <option value="พิษณุโลก">
                            <option value="เพชรบุรี">
                            <option value="เพชรบูรณ์">
                            <option value="แพร่">
                            <option value="พะเยา">
                            <option value="ภูเก็ต">
                            <option value="มหาสารคาม">
                            <option value="มุกดาหาร">
                            <option value="แม่ฮ่องสอน">
                            <option value="ยโสธร">
                            <option value="ยะลา">
                            <option value="ร้อยเอ็ด">
                            <option value="ระนอง">
                            <option value="ระยอง">
                            <option value="ราชบุรี">
                            <option value="ลพบุรี">
                            <option value="ลำปาง">
                            <option value="ลำพูน">
                            <option value="เลย">
                            <option value="ศรีสะเกษ">
                            <option value="สกลนคร">
                            <option value="สงขลา">
                            <option value="สตูล">
                            <option value="สมุทรปราการ">
                            <option value="สมุทรสงคราม">
                            <option value="สมุทรสาคร">
                            <option value="สระแก้ว">
                            <option value="สระบุรี">
                            <option value="สิงห์บุรี">
                            <option value="สุโขทัย">
                            <option value="สุพรรณบุรี">
                            <option value="สุราษฎร์ธานี">
                            <option value="สุรินทร์">
                            <option value="หนองคาย">
                            <option value="หนองบัวลำภู">
                            <option value="อ่างทอง">
                            <option value="อำนาจเจริญ">
                            <option value="อุดรธานี">
                            <option value="อุตรดิตถ์">
                            <option value="อุทัยธานี">
                            <option value="อุบลราชธานี">
                        </datalist>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">ยี่ห้อรถ</label>
                        <select class="form-select" id="brand" name="brand">
                            <option value="" disabled selected hidden></option>
                            <option value="Honda">Honda</option>
                            <option value="Yamaha">Yamaha</option>
                            <option value="Suzuki">Suzuki</option>
                            <option value="Vespa">Vespa</option>
                            <option value="LION">LION</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">สี</label>
                        <select class="form-select" id="color" name="color">
                            <option value="" disabled selected hidden></option>
                            <option value="ดำ">ดำ</option>
                            <option value="ขาว">ขาว</option>
                            <option value="เทา">เทา</option>
                            <option value="แดง">แดง</option>
                            <option value="เหลือง">เหลือง</option>
                            <option value="น้ำเงิน">น้ำเงิน</option>
                            <option value="ครีม">ครีม</option>
                            <option value="ฟ้า">ฟ้า</option>
                            <option value="ชมพู">ชมพู</option>
                            <option value="ม่วง">ม่วง</option>
                            <option value="เขียว">เขียว</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" name="save" class="btn btn-primary" id="confirm">
                        <i class="bi bi-save"></i> บันทึก
                    </button>
                </div>
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.12.4/dist/sweetalert2.all.min.js"
                    integrity="sha256-rTq0xiLu1Njw5mB3ky3DZhpI5WhYdkNlQbGXUc0Si6E=" crossorigin="anonymous">
                </script>
                <script>
                document.querySelector('#confirm').addEventListener('click', function(e) {
                    e.preventDefault(); // หยุด form submit ชั่วคราว

                    Swal.fire({
                        title: 'บันทึกข้อมูล',
                        text: "คุณต้องการบันทึกข้อมูลนี้หรือไม่?",
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: 'ตกลง',
                        cancelButtonText: 'ยกเลิก',
                        reverseButtons: true,
                        customClass: {
                            confirmButton: 'btn btn-success', // เพิ่ม class ของ Bootstrap
                            cancelButton: 'btn btn-danger me-4'
                        },
                        buttonsStyling: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // ผู้ใช้กดตกลง จึงส่งฟอร์ม
                            document.getElementById('offenseForm').submit();
                        }
                    });
                });
                </script>

            </form>
        </div>

        <!-- การ์ดสำหรับรายการบันทึก -->
        <div class="form-box mt-4">
            <header class="mb-3">
                <h4>รายการบันทึก</h4>
            </header>


            <!-- ตาราง -->
            <div class="table-responsive">
                <table class="table table-bordered" id="recordTableDT">
                    <thead class="table-primary text-center">
                        <tr>
                            <th>ที่</th>
                            <th>เลขบัตร</th>
                            <th>หน่วยงาน/คณะ</th>
                            <th>ชื่อ-สกุล</th>
                            <th>ประเภทความผิด</th>
                            <th>ประเภทรถ</th>
                            <th>ทะเบียน</th>
                            <th>จังหวัด</th>
                            <th>ยี่ห้อ</th>
                            <th>สี</th>
                            <th>สถานที่</th>
                            <th>วันที่</th>
                            <th>เวลา</th>
                            <th></th>
                            <th></th>


                        </tr>
                    </thead>
                    <tbody id="recordTable">
                        <?php 
            $no = 1;
            while($row = $resultRecords->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td>
                                <?php
    $card = $row['Card_ID'];

    if ($card !== null && $card !== '') {
        $len = strlen($card);

        if ($len > 4) {
            // แสดงตัวหน้า 6 ตัว ที่เหลือเป็น xxxx
            $masked = substr($card, 0, 6) . str_repeat('x', $len - 6);
        } else {
            $masked = $card;
        }

        echo htmlspecialchars($masked);
    }
?>
                            </td>
                            <td><?= htmlspecialchars($row['Org'] ?? ''); ?></td>
                            <td>
                                <?php
$name = trim($row['Name']);

if (!empty($name)) {

    // แยกชื่อ - นามสกุล
    $parts = preg_split('/\s+/', $name);

    $maskedParts = [];

    foreach ($parts as $part) {
        $len = mb_strlen($part, 'UTF-8');

        if ($len > 3) {
            // แสดง 3 ตัวแรก ที่เหลือเป็น x
            $masked = mb_substr($part, 0, 3, 'UTF-8') . str_repeat('x', $len - 3);
        } else {
            $masked = $part;
        }

        $maskedParts[] = $masked;
    }

    echo htmlspecialchars(implode(' ', $maskedParts));
}
?>
                            </td>
                            <td><?= htmlspecialchars($row['Category_Name']); ?></td>
                            <td><?= htmlspecialchars($row['Type_Vehicle']); ?></td>
                            <td><?= htmlspecialchars($row['Vehicle_Num']); ?></td>
                            <td><?= htmlspecialchars($row['Province']); ?></td>
                            <td><?= htmlspecialchars($row['Brand']); ?></td>
                            <td><?= htmlspecialchars($row['Color']); ?></td>
                            <td><?= htmlspecialchars($row['Location _Name']); ?></td>
                            <td>
                                <?php
echo htmlspecialchars(date('d-m-Y', strtotime($row['date'])));
?>
                            </td>
                            <td><?php echo htmlspecialchars(date('H:i', strtotime($row['time']))); ?></td>
                            <td class="text-center">
                                <a href="edithelmet.php?id=<?= $row['OffenderID']; ?>" target="_blank"
                                    class="text-secondary me-2">
                                    <i class="bi bi-pencil-square fs-5"></i>
                                </a>
                            </td>


                            <td class="text-center">
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                <button class="btn btn-link text-secondary p-0 btn-delete"
                                    data-id="<?= $row['OffenderID']; ?>">
                                    <i class="bi bi-trash3 fs-5"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- menu sidebar -->
    <script>
    const toggleBtn = document.getElementById('toggleBtn');
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');

    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        content.classList.toggle('expanded');
    });
    </script>

    <script>
    // ตั้งค่า วันที่/เวลา เริ่มต้น = ตอนเปิดหน้า //
    window.onload = function() {
        const now = new Date();
        document.getElementById("date").value = now.toISOString().split("T")[0];
        document.getElementById("time").value =
            now.getHours().toString().padStart(2, "0") + ":" +
            now.getMinutes().toString().padStart(2, "0");
    };
    </script>

    <!-- พิมเลขบัตรแล้วขึ้นข้อมูลอื่นๆ -->
    <script>
    const cardInput = document.getElementById('card_id');
    const suggestions = document.getElementById('card_suggestions');
    let currentFocus = -1;

    cardInput.addEventListener('input', function() {
        const query = this.value;
        suggestions.innerHTML = '';
        if (query.length < 2) {
            suggestions.classList.remove('show');
            return;
        }

        fetch("?action=search_card&term=" + encodeURIComponent(query))
            .then(res => res.json())
            .then(data => {
                if (data.length === 0) {
                    suggestions.classList.remove('show');
                    return;
                }
                data.forEach(item => {
                    let li = document.createElement('li');
                    li.className = 'dropdown-item';
                    li.textContent = item.Card_ID + ' - ' + item.Fullname;
                    li.onclick = function() {
                        cardInput.value = item.Card_ID;
                        document.getElementById('fullname').value = item.Fullname;
                        document.getElementById('org').value = item.Org;
                        document.getElementById('vehicle_num').value = item.Vehicle_Num;
                        document.getElementById('province').value = item.Province;
                        document.getElementById('brand').value = item.Brand;
                        document.getElementById('color').value = item.Color;
                        suggestions.classList.remove('show');
                    };
                    suggestions.appendChild(li);
                });
                suggestions.classList.add('show');
            });
    });

    document.addEventListener('click', function(e) {
        if (!cardInput.contains(e.target)) {
            suggestions.classList.remove('show');
        }
    });

    // Keyboard navigation
    cardInput.addEventListener("keydown", function(e) {
        let items = suggestions.querySelectorAll(".dropdown-item");
        if (items.length === 0) return;

        if (e.keyCode === 40) { // down
            currentFocus++;
            addActive(items);
        } else if (e.keyCode === 38) { // up
            currentFocus--;
            addActive(items);
        } else if (e.keyCode === 13) { // enter
            e.preventDefault();
            if (currentFocus > -1) {
                items[currentFocus].click();
            }
        }
    });

    function addActive(items) {
        if (!items) return;
        removeActive(items);
        if (currentFocus >= items.length) currentFocus = 0;
        if (currentFocus < 0) currentFocus = items.length - 1;
        items[currentFocus].classList.add("active");
    }

    function removeActive(items) {
        items.forEach(i => i.classList.remove("active"));
    }
    </script>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#recordTableDT').DataTable({
            language: {
                search: "ค้นหา:",
                lengthMenu: "แสดง _MENU_ แถวต่อหน้า",
                info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                infoEmpty: "ไม่มีข้อมูล",
                zeroRecords: "ไม่พบข้อมูลที่ค้นหา",
                paginate: {
                    previous: "ก่อนหน้า",
                    next: "ถัดไป"
                }
            },
            order: [
                [11, 'desc']
            ], // เรียงตามวันที่ล่าสุด
            pageLength: 10
        });
    });
    </script>
    <!-- ลบข้อมูล -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {

        document.querySelectorAll('.btn-delete').forEach(btn => {

            btn.addEventListener('click', function() {

                const offenderID = this.dataset.id;
                const row = this.closest('tr');

                Swal.fire({
                    title: 'ยืนยันการลบ?',
                    text: 'ข้อมูลนี้จะถูกลบถาวร ไม่สามารถกู้คืนได้',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'ลบข้อมูล',
                    cancelButtonText: 'ยกเลิก'
                }).then((result) => {

                    if (!result.isConfirmed) return;

                    fetch(window.location.href, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `action=delete_offense&offenderID=${encodeURIComponent(offenderID)}`
                        })
                        .then(res => res.text())
                        .then(data => {

                            if (data === 'OK') {

                                // ลบแถวออกจาก DataTable
                                const table = $('#recordTableDT').DataTable();
                                table.row(row).remove().draw();

                                Swal.fire('สำเร็จ!', 'ลบข้อมูลเรียบร้อยแล้ว',
                                    'success');
                            } else {
                                Swal.fire('ผิดพลาด!', data, 'error');
                            }
                        });

                });
            });

        });

    });
    </script>



</body>

</html>
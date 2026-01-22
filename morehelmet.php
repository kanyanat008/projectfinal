<?php
session_start();
include 'dbconnect.php'; // เชื่อมต่อฐานข้อมูล

if (!isset($_SESSION['OfficerID'])) {
  header("Location:login.php");
  exit;
}

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
// ปิดข้อมูล
function maskText($text, $show = 3) {
    if (!$text) return "-";
    $len = mb_strlen($text, 'UTF-8');
    if ($len <= $show) return $text;
    return mb_substr($text, 0, $show, 'UTF-8') . str_repeat('x', $len - $show);
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานผู้กระทำผิดจราจร</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Mitr:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

    .sidebar i {
        font-size: 22px;
    }

    #reportTable thead th {
        color: #ffffff;
        vertical-align: middle;
        border-color: #dee2e6;
        background-color: #1f3a5f;
        font-weight: 500;
    }

    /* สลับสีแถว: แถวคี่ = ขาว, แถวคู่ = ฟ้าอ่อน */
    #reportTable tbody tr:nth-of-type(odd) td {
        background-color: #ffffff !important;
    }

    #reportTable tbody tr:nth-of-type(even) td {
        background-color: #e6f0fa !important;
    }

    /* hover effect */
    #reportTable tbody tr:hover td {
        background-color: #f6f3cdff !important;
        cursor: pointer;
    }
    </style>
</head>

<body>

    <!-- ปุ่ม toggle -->
    <button class="toggle-btn" id="toggleBtn"><i class="bi bi-list"></i></button>

    <div class="sidebar collapsed" id="sidebar">
        <ul class="nav flex-column">
            <li><a href="dashboard.php" class="nav-link active"><i class="bi bi-house-fill"></i></i> <span>หน้าแรก
                        Dashboard</span></a></li>
            <li><a href="helmet.php" class="nav-link"><svg xmlns="http://www.w3.org/2000/svg" height="24px"
                        viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
                        <path
                            d="M504-501Zm48 237q100 0 170-70t70-170q0-100-70-170t-170-70q-53.91 0-104.46 12.5Q397-719 350-690l139 58q41 17 64 51.89t23 78.14q0 58.97-41.5 100.47T434-360H168v96h384ZM179-432h255.18q28.82 0 49.32-20.06T504-501.4q0-20.6-11.5-38.1T461-565l-174-73q-44 41-68.5 95T179-432Zm373 240H168q-29.7 0-50.85-21.15Q96-234.3 96-264v-72q0-105 33.5-193.5t94-152Q284-745 368-780.5T552-816q65 0 121.56 24.37 56.57 24.38 98.99 66.79 42.43 42.42 66.94 98.96Q864-569.33 864-504q0 64.29-24.45 121.27-24.46 56.99-67 99.36Q730-241 673.5-216.5 617-192 552-192Z" />
                    </svg><span>หมวกนิรภัย</span></a></li>
            <li><a href="wheel.php" class="nav-link"><svg xmlns="http://www.w3.org/2000/svg" height="24px"
                        viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
                        <path
                            d="M428-520h-70 150-80ZM200-200q-83 0-141.5-58.5T0-400q0-83 58.5-141.5T200-600h464l-80-80H440v-80h143q16 0 30.5 6t25.5 17l139 139q78 6 130 63t52 135q0 83-58.5 141.5T760-200q-83 0-141.5-58.5T560-400q0-18 2.5-35.5T572-470L462-360h-66q-14 70-69 115t-127 45Zm560-80q50 0 85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35Zm-560 0q38 0 68.5-22t43.5-58H200v-80h112q-13-36-43.5-58T200-520q-50 0-85 35t-35 85q0 50 35 85t85 35Zm198-160h30l80-80H358q15 17 25 37t15 43Z" />
                    </svg> <span>บังคับล้อ</span></a></li>
            <li><a href="report.php" class="nav-link "><svg xmlns="http://www.w3.org/2000/svg" height="24px"
                        viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
                        <path
                            d="M216-144q-29.7 0-50.85-21.15Q144-186.3 144-216v-528q0-29.7 21.15-50.85Q186.3-816 216-816h171q8-31 33.5-51.5T480-888q34 0 59.5 20.5T573-816h171q29.7 0 50.85 21.15Q816-773.7 816-744v528q0 29.7-21.15 50.85Q773.7-144 744-144H216Zm0-72h528v-528H216v528Zm72-72h288v-72H288v72Zm0-156h384v-72H288v72Zm0-156h384v-72H288v72Zm192-168q10.4 0 17.2-6.8 6.8-6.8 6.8-17.2 0-10.4-6.8-17.2-6.8-6.8-17.2-6.8-10.4 0-17.2 6.8-6.8 6.8-6.8 17.2 0 10.4 6.8 17.2 6.8 6.8 17.2 6.8ZM216-216v-528 528Z" />
                    </svg> <span>รายงาน</span></a></li>
            <li><a href="statistics.php" class="nav-link"><svg xmlns="http://www.w3.org/2000/svg" width="24px"
                        fill="#FFFFFF" class="bi bi-bar-chart-line" viewBox="0 0 16 16">
                        <path
                            d="M11 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h1V7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7h1zm1 12h2V2h-2zm-3 0V7H7v7zm-5 0v-3H2v3z" />
                    </svg><span>สถิติการให้บริการ</span></a></li>
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
            <h3>รายการบันทึกผู้กระทำผิดระเบียบวินัยจราจร (หมวกนิรภัย)</h3>
            <?php

            $today = date('Y-m-d');
            $startdate = isset($_GET['startdate']) && $_GET['startdate'] !== '' ? $_GET['startdate'] : $today;
            $enddate   = isset($_GET['enddate']) && $_GET['enddate'] !== '' ? $_GET['enddate'] : $today;

        function formatDate($date) {
            if (!$date || $date == '0000-00-00') return "-";
            $ts = strtotime($date);
            return date("d/m/Y", $ts);
        }

        echo "<p style='font-size:18px;'>";
        echo "ตั้งแต่วันที่ <b>" . formatDate($startdate) . "</b> ถึงวันที่ <b>" . formatDate($enddate) . "</b>";
        echo "</p>";
    ?>
        </header>

        <!-- ตารางข้อมูล -->
        <div class="table-responsive">
            <table class="table table-bordered" id="reportTable">
                <thead>
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
                    </tr>
                </thead>
                <tbody>
                    <?php

$today = date('Y-m-d');
$startdate = isset($_GET['startdate']) && $_GET['startdate'] !== '' ? $_GET['startdate'] : $today;
$enddate   = isset($_GET['enddate']) && $_GET['enddate'] !== '' ? $_GET['enddate'] : $today;

                    $sql = "SELECT 
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
    o2.date, o2.time
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
   AND DATE(o2.date) BETWEEN ? AND ?
   order by o2.date DESC";
   
$stmt = $con->prepare($sql);
$stmt->bind_param("ss", $startdate, $enddate);
$stmt->execute();
$result = $stmt->get_result();
                    $i = 1;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>".$i++."</td>";
        echo "<td>".htmlspecialchars(maskText($row['Card_ID'], 6))."</td>";
        echo "<td>".htmlspecialchars($row['Org'] ?? '')."</td>";
        $nameParts = explode(" ", $row['Name']);
$maskedName = [];

foreach ($nameParts as $part) {
    $maskedName[] = maskText($part, 3);
}

echo "<td>".htmlspecialchars(implode(" ", $maskedName))."</td>";

        echo "<td>".htmlspecialchars($row['Category_Name'])."</td>";
        echo "<td>".htmlspecialchars($row['Type_Vehicle'])."</td>";
        echo "<td>".htmlspecialchars($row['Vehicle_Num'])."</td>";
        echo "<td>".htmlspecialchars($row['Province'])."</td>";
        echo "<td>".htmlspecialchars($row['Brand'])."</td>";
        echo "<td>".htmlspecialchars($row['Color'])."</td>";
        echo "<td>".htmlspecialchars($row['Location _Name'])."</td>";
        echo "<td>".htmlspecialchars($row['date'])."</td>";
        echo "<td>".htmlspecialchars($row['time'])."</td>";
        echo "</tr>";
    }
}
                    ?>
                </tbody>
            </table>
        </div>

    </div>

    <!-- JS DataTables & Buttons -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>


    <script>
    $(document).ready(function() {
        // เริ่มใช้งาน DataTable
        $('#reportTable').DataTable({
            dom: 'Bfltip', // ตำแหน่งปุ่ม
            buttons: [{
                    extend: 'excelHtml5',
                    text: '<i class="bi bi-file-earmark-excel"></i> Excel',
                    className: 'btn btn-outline-success',
                    title: 'รายการบันทึกผู้กระทำผิดระเบียบวินัยจราจร (หมวกนิรภัย)',
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="bi bi-printer"></i> Print',
                    className: 'btn btn-outline-primary',
                    title: 'รายงานผู้กระทำผิดระเบียบวินัยจราจร (หมวกนิรภัย)',
                    exportOptions: {
                        columns: ':visible'
                    }
                }
            ],
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
            }
        });


        // Toggle Sidebar
        const toggleBtn = document.getElementById('toggleBtn');
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            content.classList.toggle('expanded');
        });
    });
    </script>

</body>

</html>
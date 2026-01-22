<?php
session_start();
include_once 'dbconnect.php';

if (!isset($_SESSION['OfficerID'])) {
    header("Location:login.php");
    exit;
}

$officerID = $_SESSION['OfficerID'];
$sql = "SELECT Officer_Name, Position, Email, phoneNum, role FROM officer WHERE OfficerID = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("s", $officerID);
$stmt->execute();
$result = $stmt->get_result();
$officer = $result->fetch_assoc();
$stmt->close();

// กำหนดชื่อสำหรับเมนู
$officerName = $officer ? $officer['Officer_Name'] : "ไม่ทราบชื่อ";

// อัปเดตข้อมูลเมื่อ POST ผ่าน AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'updateProfile') {
    $Officer_Name = $_POST['Officer_Name'];
    $Position = $_POST['Position'];
    $Email = $_POST['Email'];
    $phoneNum = $_POST['phoneNum'];
    $role = $_POST['role'];

    $sql = "UPDATE officer SET Officer_Name=?, Position=?, Email=?, phoneNum=?, role=? WHERE OfficerID=?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ssssss", $Officer_Name, $Position, $Email, $phoneNum, $role, $officerID);
    
    if ($stmt->execute()) {
        echo json_encode(['status'=>'success']);
    } else {
        echo json_encode(['status'=>'error']);
    }
    $stmt->close();
    $con->close();
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
        margin-bottom: 25px;
        padding-top: 20px;
    }

    .form-box {
        background: #e6f0fa;
        padding: 35px;
        border-radius: 15px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .custom-btn {
        background-color: #5b95d5;
        color: #fff;
        border: none;
    }

    .custom-btn:hover {
        background-color: #265ea3;
        color: #fff;
    }

    @media (max-width: 768px) {
        .form-box {
            padding: 25px;
        }
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
                    </svg><span>สถิติการให้บริการ</span></a></li><?php endif; ?>
        </ul>
        <div class="bottom-links">
            <a href="profile.php" class="nav-link active">
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
    <div class="content expanded d-flex justify-content-center align-items-center" id="content"
        style="min-height: 100vh;">
        <div class="form-box mt-4 mx-auto" style="max-width: 700px; width: 100%;">
            <header class="mb-4 text-center">
                <h3 class="fw-bold">ข้อมูลโปรไฟล์</h3>
            </header>

            <div class="row align-items-center g-4 flex-column flex-md-row text-md-start text-center ps-md-5">

                <!-- ไอคอน -->
                <div class="col-md-3 col-12 mb-3 mb-md-0 text-md-start text-center">
                    <i class="bi bi-person-circle" style="font-size: 5rem; color: #1f3a5f;"></i>
                </div>

                <!-- รายละเอียด -->
                <div class="col-md-9 col-12 text-md-start text-center" style="padding-left: 40px;">
                    <p><strong>ชื่อ-สกุล :</strong> <?= htmlspecialchars($officer['Officer_Name']); ?></p>
                    <p><strong>ตำแหน่ง :</strong> <?= htmlspecialchars($officer['Position']); ?></p>
                    <p><strong>Email :</strong> <?= htmlspecialchars($officer['Email']); ?></p>
                    <p><strong>เบอร์โทร :</strong> <?= htmlspecialchars($officer['phoneNum']); ?></p>
                    <p><strong>บทบาท :</strong> <?= htmlspecialchars($officer['role']); ?></p>
                </div>

                <!-- ปุ่ม -->
                <div class="text-center mt-4">
                    <button class="btn custom-btn px-4" id="editProfileBtn">
                        <i class="bi bi-pencil-square"></i> แก้ไขข้อมูล
                    </button>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    const toggleBtn = document.getElementById('toggleBtn');
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');

    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        content.classList.toggle('expanded');
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.getElementById('editProfileBtn').addEventListener('click', function() {
        Swal.fire({
            title: 'แก้ไขข้อมูลโปรไฟล์',
            html: `
            <input id="name" class="swal2-input" placeholder="ชื่อ-สกุล" value="<?= htmlspecialchars($officer['Officer_Name']); ?>">
            <input id="position" class="swal2-input" placeholder="ตำแหน่ง" value="<?= htmlspecialchars($officer['Position']); ?>">
            <input id="email" class="swal2-input" placeholder="Email" value="<?= htmlspecialchars($officer['Email']); ?>">
            <input id="phone" class="swal2-input" placeholder="เบอร์โทร" value="<?= htmlspecialchars($officer['phoneNum']); ?>">
            <input id="role" class="swal2-input" placeholder="บทบาท" value="<?= htmlspecialchars($officer['role']); ?>">
        `,
            confirmButtonText: 'บันทึก',
            showCancelButton: true,
            cancelButtonText: 'ยกเลิก',
            focusConfirm: false,
            preConfirm: () => {
                const name = document.getElementById('name').value.trim();
                const position = document.getElementById('position').value.trim();
                const email = document.getElementById('email').value.trim();
                const phone = document.getElementById('phone').value.trim();
                const role = document.getElementById('role').value.trim();

                if (!name || !position || !email || !phone || !role) {
                    Swal.showValidationMessage('กรุณากรอกข้อมูลให้ครบทุกช่อง');
                    return false;
                }
                return {
                    name,
                    position,
                    email,
                    phone,
                    role
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'updateProfile',
                            Officer_Name: result.value.name,
                            Position: result.value.position,
                            Email: result.value.email,
                            phoneNum: result.value.phone,
                            role: result.value.role
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'อัปเดตข้อมูลสำเร็จ',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => location
                                .reload());
                        } else {
                            Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถอัปเดตข้อมูลได้', 'error');
                        }
                    })
                    .catch(err => {
                        Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถอัปเดตข้อมูลได้', 'error');
                        console.error(err);
                    });
            }
        });
    });
    </script>

</body>

</html>
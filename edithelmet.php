<?php
session_start();
include_once 'dbconnect.php';

if (!isset($_SESSION['OfficerID'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("ไม่พบรหัสผู้กระทำผิด");
}

$offenderID = $_GET['id'];

/* =========================
   ดึงข้อมูลเดิม
========================= */
$sql = "
SELECT 
    o2.OffenderID,
    o2.CategoryID,
    o2.Location_ID,
    o2.date,
    o2.time,
    o.Name,
    o.Identification_Num,
    o.type_offender,
    o.`Vehicle _Num`,
    s.Student_ID,
    p.Personnel_ID,
    COALESCE(s.Faculty, p.Department) AS Org,
    c.Type_Vehicle,
    c.Province,
    c.Brand,
    c.Color,
    l.`Location _Name`
FROM offense o2
JOIN offender o ON o2.OffenderID = o.OffenderID
LEFT JOIN student s ON s.Student_ID = o.Student_ID
LEFT JOIN personnel p ON p.Personnel_ID = o.Personnel_ID
LEFT JOIN car c ON c.Vehicle_Num = o.`Vehicle _Num`
LEFT JOIN location l ON l.Location_ID = o2.Location_ID
WHERE o2.OffenderID = ?
";

$stmt = $con->prepare($sql);
$stmt->bind_param("s", $offenderID);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("ไม่พบข้อมูล");
}

/* แยก category */
$selectedCategory = explode(",", $data['CategoryID']);

/* =========================
   ดึงสถานที่
========================= */
$location = [];
$resLoc = $con->query("SELECT `Location _Name` FROM location WHERE Location_ID LIKE 'L1%'");
while ($row = $resLoc->fetch_assoc()) {
    $location[] = $row['Location _Name'];
}

/* =========================
   กดบันทึก (UPDATE)
========================= */
if (isset($_POST['save'])) {

    $category_id = $data['CategoryID'];

    /* หา Location_ID */
    $stmtLoc = $con->prepare("SELECT Location_ID FROM location WHERE `Location _Name`=?");
    $stmtLoc->bind_param("s", $_POST['location']);
    $stmtLoc->execute();
    $loc = $stmtLoc->get_result()->fetch_assoc();
    $location_id = $loc['Location_ID'];

    /* UPDATE offense */
    $stmt = $con->prepare("
        UPDATE offense 
        SET 
            CategoryID = ?,
            Location_ID = ?,
            date = ?,
            time = ?
        WHERE OffenderID = ?
    ");
    $stmt->bind_param(
        "sssss",
        $category_id,
        $location_id,
        $_POST['date'],
        $_POST['time'],
        $offenderID
    );
    $stmt->execute();

    /* UPDATE offender */
    $stmt = $con->prepare("
        UPDATE offender 
        SET 
            Name = ?,
            `Vehicle _Num` = ?
        WHERE OffenderID = ?
    ");
    $stmt->bind_param(
        "sss",
        $_POST['fullname'],
        $_POST['vehicle_num'],
        $offenderID
    );
    $stmt->execute();

    /* UPDATE car */
    $stmt = $con->prepare("
    UPDATE car 
    SET 
        Province=?,
        Brand=?,
        Color=?
    WHERE Vehicle_Num=?
");
$stmt->bind_param(
    "ssss",
    $_POST['province'],
    $_POST['brand'],
    $_POST['color'],
    $_POST['vehicle_num']
);

    $stmt->execute();

     $updateSuccess = true;
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>แก้ไขข้อมูลผู้กระทำผิด(หมวกนิรภัย)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Mitr:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.12.4/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.12.4/dist/sweetalert2.all.min.js"></script>


    <style>
    * {
        font-family: "Mitr", sans-serif;
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

    .form-check-input {
        transform: scale(1.5);
        /* ขยาย 1.5 เท่า */
        margin-right: 10px;
        /* เว้นช่องว่างระหว่างกล่องกับ label */
    }
    </style>
</head>

<body class="bg-light">

    <div class="container mt-4">
        <header class="text-center mb-4">
            <h3>แก้ไขข้อมูลผู้กระทำผิด(หมวกนิรภัย)</h3>
        </header>
        <div class="form-box">
            <form method="post">

                <input type="hidden" name="save" value="1">

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">สถานที่</label>
                        <select class="form-select" name="location">
                            <?php foreach ($location as $loc): ?>
                            <option value="<?= $loc ?>" <?= $loc==$data['Location _Name']?'selected':'' ?>>
                                <?= $loc ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">วันที่</label>
                        <input type="date" name="date" class="form-control" value="<?= $data['date'] ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">เวลา</label>
                        <input type="time" name="time" class="form-control" value="<?= substr($data['time'],0,5) ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">ชื่อ-สกุล</label>
                        <input type="text" name="fullname" class="form-control bg-secondary bg-opacity-10 text-dark"
                            value="<?= htmlspecialchars($data['Name']) ?>" readonly>


                    </div>

                    <div class="col-md-4">
                        <label class="form-label">เลขทะเบียน</label>
                        <input type="text" name="vehicle_num" class="form-control" value="<?= $data['Vehicle _Num'] ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">ประเภทรถ</label>
                        <input type="text" class="form-control bg-secondary bg-opacity-10 text-dark"
                            value="<?= htmlspecialchars($data['Type_Vehicle']) ?>" readonly>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">จังหวัด</label>
                        <input class="form-control" name="province" value="<?= $data['Province'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">ยี่ห้อ</label>
                        <input class="form-control" name="brand" value="<?= $data['Brand'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">สี</label>
                        <input class="form-control" name="color" value="<?= $data['Color'] ?>">
                    </div>
                </div>


                <div class="text-end mt-4">
                    <button class="btn btn-success">
                        <i class="bi bi-save"></i> บันทึกการแก้ไข
                    </button>
                </div>

            </form>
        </div>
    </div>
    <?php if (!empty($updateSuccess)): ?>
    <script>
    Swal.fire({
        title: 'สำเร็จ',
        text: 'แก้ไขข้อมูลเรียบร้อยแล้ว',
        icon: 'success',
        timer: 1000,
        showConfirmButton: false,
        timerProgressBar: true
    }).then(() => {
        // ปิดหน้าต่าง popup
        window.close();

        // refresh หน้าแม่ (ถ้าเปิดจาก target=_blank)
        if (window.opener) {
            window.opener.location.reload();
        }
    });
    </script>
    <?php endif; ?>


</body>

</html>
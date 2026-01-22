<?php
session_start();
include_once "dbconnect.php";

$error = false;

if (isset($_POST['login'])) {
    $user_name = $_POST['txtname'];
    $user_passwd = $_POST['txtpassword'];

    // SQL command
    $sql = "SELECT * FROM officer 
            WHERE username = '$user_name' 
            AND userpassword = '$user_passwd'";

    $result = mysqli_query($con, $sql);

    if ($row = mysqli_fetch_array($result)) {
        // login success
        $_SESSION['OfficerID'] = $row['OfficerID'];
        $_SESSION['user_name'] = $row['userName'];
$_SESSION['role'] = $row['role']; //เก็บ role ลง Session

        header("Location: dashboard.php");
        exit();
    } else {
        $error = true;
        $login_err_msg = "Username หรือ Password ไม่ถูกต้อง !";
    }
}
?>
<!doctype html>
<html lang="th">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Mitr:wght@200;300;400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
    * {
        box-sizing: border-box;
        font-family: "Mitr", sans-serif;
        font-weight: 400;

    }

    html,
    body {
        height: 100%
    }

    body {
        margin: 0;
        min-height: 100vh;
        display: grid;
        grid-template-columns: 1fr 620px;
        background: linear-gradient(to bottom, #f3f6fc, #c6d9f1, #94bae5);
        align-items: stretch;
        color: #0b305c;
    }

    /* LEFT: image / marketing area */
    .left {
        padding: 20px 0px 0px 68px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    /* RIGHT: login form */
    .right {
        padding: 0px 78px 0px 0px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        box-shadow: -40px 0 60px rgba(124, 108, 252, 0.05) inset;
        gap: 16px;
    }

    .card {
        width: 100%;
        max-width: 360px;
        background: rgba(255, 255, 255, 0.85);
        border-radius: 14px;
        padding: 28px;
        box-shadow: 0 6px 30px rgba(17, 24, 39, 0.08);
        backdrop-filter: blur(6px);
        display: flex;
        flex-direction: column;
    }

    .card h2 {
        margin: 0 0 15px 0;
        font-size: 25px;
        text-align: center;
        font-weight: 500;
    }

    .card p.help {
        margin: 0 0 18px 0;
        color: #6b7280;
    }

    .police-img {
        width: 90%;
        height: auto;
    }

    .system-title {
        font-size: 28px;
        font-weight: 600;
        margin-bottom: 35px;
        text-align: center;
        color: #0b305c;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 14px
    }

    label {
        font-size: 18px;
        color: #0b305c;
    }

    input[type="text"],
    input[type="password"] {
        padding: 12px 14px;
        border-radius: 10px;
        border: 1px solid rgba(15, 23, 42, 0.06);
        outline: none;
        font-size: 16px;
        background: rgba(255, 255, 255, 0.7);
    }

    input:focus {
        box-shadow: 0 6px 18px rgba(124, 108, 252, 0.12);
        border-color: #7C6CFC
    }

    .actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 10px
    }

    .row {
        display: flex;
        gap: 10px
    }

    .checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #6b7280;
        font-size: 14px
    }

    .divider {
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(0, 0, 0, 0.06), transparent);
        margin: 18px 0
    }

    .footer-note {
        font-size: 13px;
        color: #6b7280;
        text-align: center
    }

    /* small screens: stack vertically, form centered */
    @media (max-width:880px) {
        body {
            grid-template-columns: 1fr;
        }

        .left {
            padding: 28px 28px 0px 28px;
            text-align: center
        }

        .right {
            padding: 0px 28px 28px 28px;
            justify-content: center;
        }

    }

    /* From Uiverse.io by adeladel522 */
    .button {
        position: relative;
        transition: all 0.3s ease-in-out;
        box-shadow: 0px 10px 20px rgba(0, 0, 0, 0.2);
        padding-block: 0.5rem;
        padding-inline: 1.25rem;
        background-color: rgb(0 107 179);
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #ffff;
        gap: 10px;
        font-weight: bold;
        border: 3px solid #ffffff4d;
        outline: none;
        overflow: hidden;
        font-size: 14px;
        font-weight: 400;
    }

    .icon {
        width: 20px;
        height: 20px;
        transition: all 0.3s ease-in-out;
    }

    .button:hover {
        transform: scale(1.05);
        border-color: #fff9;
    }

    .button:hover .icon {
        transform: translate(4px);
    }

    .button:hover::before {
        animation: shine 1.5s ease-out infinite;
    }

    .button::before {
        content: "";
        position: absolute;
        width: 100px;
        height: 100%;
        background-image: linear-gradient(120deg,
                rgba(255, 255, 255, 0) 30%,
                rgba(255, 255, 255, 0.8),
                rgba(255, 255, 255, 0) 70%);
        top: 0;
        left: -100px;
        opacity: 0.6;
    }

    .text-danger {
        color: #870606ff;
        padding-top: 20px;
        text-align: center;

    }

    @keyframes shine {
        0% {
            left: -100px;
        }

        60% {
            left: 100%;
        }

        to {
            left: 100%;
        }
    }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mitr:wght@200;300;400;500;600;700&display=swap"
        rel="stylesheet">
</head>

<body>
    <main class="left">
        <img src="img/logo.png" alt="police" class="police-img">
    </main>

    <aside class="right">
        <h1 class="system-title">ระบบจัดการและวิเคราะห์ข้อมูลการกระทำผิดกฎจราจรในพื้นที่มหาวิทยาลัยสงขลานครินทร์</h1>
        <section class="card" aria-labelledby="login-heading">
            <h2 id="login-heading">Log in</h2>

            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name="loginform">
                <div class="form-group">
                    <label for="username">Username :</label>
                    <input id="username" name="txtname" type="text" required />
                </div>

                <div class="form-group">
                    <label for="password">Password :</label>
                    <div style="position:relative;">
                        <input id="password" name="txtpassword" type="password" required />
                        <button type="button" aria-label="Toggle password" onclick="togglePassword()"
                            style="position:absolute; right:8px; top:8px; background:transparent; border:none; cursor:pointer; font-size:13px; color:#6b7280;">แสดง</button>
                    </div>
                </div>

                <div class="actions">
                    <button class="button" type="submit" name="login">
                        เข้าสู่ระบบ
                        <svg fill="currentColor" viewBox="0 0 24 24" class="icon">
                            <path clip-rule="evenodd"
                                d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm4.28 10.28a.75.75 0 000-1.06l-3-3a.75.75 0 10-1.06 1.06l1.72 1.72H8.25a.75.75 0 000 1.5h5.69l-1.72 1.72a.75.75 0 101.06 1.06l3-3z"
                                fill-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            </form>

            <span class="text-danger">
                <?php if ($error) { echo $login_err_msg; } ?>
            </span>
        </section>
    </aside>

    <script>
    function togglePassword() {
        const p = document.getElementById('password');
        const btn = event.currentTarget;
        if (p.type === 'password') {
            p.type = 'text';
            btn.textContent = 'ซ่อน';
        } else {
            p.type = 'password';
            btn.textContent = 'แสดง';
        }
    }
    </script>
</body>

</html>
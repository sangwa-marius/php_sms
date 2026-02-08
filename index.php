<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background: #ffffff;
            width: 350px;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .logo {
            text-align: center;
            margin-bottom: 15px;
        }

        .logo img {
            width: 80px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        input, select {
            width: 100%;
            padding: 10px;
            margin-bottom: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing:border-box;
        }

        label {
            font-size: 14px;
            color: #555;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #007bff;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #0056b3;
        }

        .link {
            display: block;
            text-align: center;
            margin-top: 10px;
            text-decoration: none;
            font-size: 14px;
            color: #007bff;
        }

        .link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

<div class="login-container">
    <div class="logo">
        <img src="assets/logo.jpg" alt="Logo">
    </div>

    <h2>Login with Email</h2>

    <form method="POST">
        <input type="email" name="email" placeholder="youraddress@gmail.com" required>
        <input type="password" name="password" placeholder="Your password" required>

        <label>Login as</label>
        <select name="role" required>
            <option value="student">Student</option>
            <option value="teacher">Teacher</option>
            <option value="principle">Principal</option>
        </select>

        <button type="submit" name="submit">Login</button>

        <a href="signUp.php" class="link">No account? Create one</a>

        <?php
        session_start();
        require './db/db.php';

        if (isset($_POST['submit'])) {
            $email = $_POST['email'];
            $password = $_POST['password'];
            $role = $_POST['role'];

            $sql = 'select * from users where email = ? and role = ?';
            $stmt = mysqli_prepare($conn,$sql);
            $stmt ->bind_param('ss', $email,$role);
            $stmt ->execute();
            $result = $stmt ->get_result();

            $user = $result ->fetch_assoc();

            if($user && password_verify($password,$user['password'])){
                $_SESSION['id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];

                if($user['role']=='student'){
                    header('Location: ./student_portal/student_dashboard.php');
                    exit;
                }else if($user['role']==='teacher'){
                     header('Location: ./teacher_portal/teacher_dashboard.php');
                    exit;
                }else{
                     header('Location: ./principle_portal/principle_dashboard.php');
                    exit;
                }
            }else{
                echo 'Invalid credentials';
            }
        }
        ?>
    </form>
</div>

</body>
</html>

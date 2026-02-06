<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .signup-container {
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

<div class="signup-container">
    <div class="logo">
        <img src="assets/logo.jpg" alt="Logo">
    </div>

    <h2>Sign Up</h2>
    <form method="POST" enctype="multipart/form-data">
    <input type="text" name="name" placeholder ="Enter your full names"required>
    <input type="email" name="email" placeholder ="Enter your email" required>
    <input type="password" name="password" placeholder ="Password" required>

    <select name="role" required>
        <option value="student">Student</option>
        <option value="teacher">Teacher</option>
        <option value="principle">Principal</option>
    </select>

    <label>Profile Picture</label>
    <input type="file" name="profile_image" accept="image/*">
    <button type="submit" name="submit">Create Account</button>
    <a href ='index.php' class ='link'>Already have an account? Login</a>

        <?php
        require 'db.php';

        if (isset($_POST['submit'])) {
            $name = $_POST['name'];
            $email = $_POST['email'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $role = $_POST['role'];
            $imageName = 'default.png';

           if (!empty($_FILES['profile_image']['name'])) {
                $tmp = $_FILES['profile_image']['tmp_name'];
                $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);

                $imageName = uniqid() . '.' . $ext;
                move_uploaded_file($tmp, "uploads/profiles/" . $imageName);
            }


            if (!in_array($role, ['student','teacher','principle'])) {
                die('Invalid role');
            }

            $sql = "INSERT INTO users (name,email,password,role,profile_image)
            VALUES (?,?,?,?,?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $name,$email,$password,$role,$imageName);
            $stmt->execute();


            header("Location: index.php");
            exit;
        }
        ?>
    </form>
</div>

</body>
</html>

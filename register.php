<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
require "db.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register here</title>
    <link rel="icon" href="sms.webp">
    <style>

        body{
            font-family: Arial, sans-serif;
            background-color: black;
            margin: 0;
            padding: 0;
        }
        form{
            background-color: black;
            width: 400px;
            margin: 100px auto;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 1px 1px 10px rgba(0,0,0,0.1);
            border: none;
        }
        h2{
            text-align: center;
            color: green;
            margin-top: 0;
        }
        
        input[type="text"], input[type="number"], input[type="email"]{
            width: 100%;
            box-sizing: border-box;
            padding: 10px;
        
            border: 2px solid #a8a8b3ff;
            border-radius: 8px;
            font-size: 16px;
            outline: none;
            background-color: #f3f4f6;
            color: black;
        }

       
        input[type="number"]:hover{
            border: 2px solid #1ae81aff;
        }

         input[type="email"]:hover{
        border: 2px solid #1ae81aff;
        }

         

        input[type="text"]:hover{
            border: 2px solid #1ae81aff;
        }


        input[type="submit"]{
            width: 100%;
            padding: 10px;
            background-color: #1ae81aff;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s ease;
            margin-top: 20px;
        }
        input[type="submit"]:hover{
            background-color: darkgreen;
            transform: scale(1.05);
        }

    </style>
</head>
<body>
    



<form action="register.php" method="post">
    <h2>Register Here</h2>
    <input type="text" id="names" name="names" placeholder="Enter your full names" required><br><br>
    <input type="number" id="age" name="age" placeholder="Your age" required><br><br>
    <input type="email" id="email" name="email" placeholder="Your email" required><br><br>
     <input type="submit" value="Register" name ="register">
    <a href="search.php" style="text-decoration:none; color:darkgreen; display:block; text-align:center; margin-top:15px;">Back to Search</a>
   
</form>

<?php
if (isset($_POST['register'])) {

    $names = $_POST['names'];
    $age   = (int) $_POST['age'];
    $email = $_POST['email'];

    $query = "INSERT INTO students (names, age, email) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);

    if (!$stmt) {
        die("Prepare failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "sis", $names, $age, $email);

    if (!mysqli_stmt_execute($stmt)) {
        die("Execution failed: " . mysqli_stmt_error($stmt));
    }

    echo "<p style='text-align:center; color:green; font-weight:bold;'>Registration successful!</p>";

    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}
?>

</body>
</html>
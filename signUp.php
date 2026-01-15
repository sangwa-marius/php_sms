<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="sms.webp">
    <title>SignUp</title>
    <style>
    *{box-sizing:border-box;}
    body{background:black;}
    form{width:12cm; box-sizing: border-box; padding:20px; margin:50px auto;}
    input[type='text'], input[type='password']{width:100%; margin-top:4px; padding-left:5px; box-sizing: border-box; height:0.7cm; margin-bottom:10px; background:black; color:white; border: 1px solid grey; }
    input[type='submit']{ width: 100%; background:blue; border:none; margin-top: 20px; height:0.7cm; color:white; font-weight: 800; }
    input[type='text']:hover, input[type='password']:hover{ border:1px solid blue;}
    .fancy-fieldset legend {opacity: 0; transition: opacity 2s ease;}
    .fancy-fieldset:focus-within legend {opacity: 1; }
    legend{ color: rgba(49, 78, 209, 1); font-weight: 800;display:block;}
    fieldset{ border: 2px solid transparent; padding: 30px;transition: border-color 0.3s ease; border-radius: 12px; }
    fieldset:focus-within{ border-color: skyblue; }

    label{color: skyblue}
    </style>
</head>
<body>
    
<form action = "" method = 'POST'>
  
    <fieldset class="fancy-fieldset">
        <legend id="legend">Sign up now</legend>
    <label for ='username'>Enter your username</label><br>
    <input type="text" name ='username' value ='' id ='username' required><br>
    <label for ='password'>Password</label><br>
    <input type ='password' name ='password' id="password" value =''required><br>
    <input type="submit" name ='signUp' value="SignUp">
    <a href="index.php" style="text-decoration:none; color:blue; display:block; text-align:center; margin-top:15px;">Already have an account? Login</a>
    <?php
if (isset($_POST['signUp'])){
    include 'db.php';
    $username = htmlspecialchars(trim($_POST['username']));
    $password = htmlspecialchars($_POST['password']);
    $hash =password_hash($password,PASSWORD_DEFAULT);

    $sql = "INSERT INTO users(username,password) VALUES(?,?)";
    $stmt = mysqli_prepare($conn,$sql);
    
    if(!$stmt){
        die("Failed to prepare: ". mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt,"ss",$username,$hash);

    if (! mysqli_stmt_execute($stmt)){
        die("Execution failed: ". mysqli_stmt_error($stmt));
    }

    echo"<p style='text-align:center; color:green; font-weight:bold;'>SignUp successful! </p>";
    echo"<p style='text-align:center; color:green; font-weight:bold;'>Redirecting to login page.... </p>";
    header ("Refresh: 3 ; URL=index.php");
    exit();
    
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}

?>
</fieldset>
</form>
</body>
</html>

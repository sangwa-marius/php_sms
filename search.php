<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}
require "db.php";

$search = $_POST['query'] ?? '';
$result = null;

if (!empty($search)) {
    $sql = "SELECT * FROM students WHERE names LIKE '%$search%'";
    $result = mysqli_query($conn, $sql);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Users</title>
    <link rel="icon" href="sms.webp">
    <style>
        body { font-family: Arial, sans-serif; background-color:black; margin: 0; padding: 0; }
        .container { width: 90%; box-sizing:border-box; margin: 50px auto; background-color: black; padding: 70px; border-radius: 12px; box-shadow: 1px 1px 10px rgba(0,0,0,0.1); justify-items:center; color:blue;  }
        h2 {text-align: center; margin-top: 0; color:#1ae83cff; }
        .search-box { display: flex; justify-content: center; margin-bottom: 20px;}
        input[type="search"] { width: 100%; padding: 10px; border-radius: 12px; border: 2px solid #a8a8b3ff; font-size: 16px; outline: none; background-color: #f3f4f6; color:black; }
        input[type="submit"] { padding: 10px 15px; margin-left: 10px; transition:0.3s ease;  background:linear-gradient(135deg,blue,green); border: none; color:white;  border-radius: 8px; font-size: 16px; cursor: pointer; }
        input[type="submit"]:hover { background: linear-gradient(135deg,blue,green);transform: scale(1.09)  }  
        table { width: 90%; border-collapse: collapse; margin-top: 15px; box-sizing:border-box; }
        th { background: #05420fff; color: white; padding: 10px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #ddd; color:green; }
        tr{ transition: 0.3s ease;}
        tr:hover { background: linear-gradient(135deg,whitesmoke,skyblue); transform: translateY(-10px); border-radius: 12px;}
        input:-webkit-autofill { -webkit-text-fill-color: #000 !important; background-color: #fafbfc !important; box-shadow: 0 0 0 1000px #fafbfc inset !important; }
        .no-results { text-align: center; padding: 15px; color: red; font-weight: bold; }
        a{ text-decoration:none; text-align: center; margin-top:20px; color:#0fd83aff; font-weight:1000; display:block }
    </style>
</head>
<body>
 
<div class="container">
    
      <h1>Welcome, <?php echo $_SESSION["username"]; ?></h1>
    <h2>User Search</h2>

    <form method="post" class="search-box">
        <input type="search" name="query"  placeholder="Search by name..." value="<?php echo htmlspecialchars($search); ?>">
        <input type="submit" value="Search">
    </form>
    

    <?php if ($result !== null):?>

    <table>
        <tr>
            <th>ID</th>
            <th>Names</th>
            <th>Age</th>
            <th>Email</th>
        </tr>

        <?php
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr><td>{$row['id']}</td><td>{$row['names']}</td> <td>{$row['age']}</td> <td>{$row['email']}</td> </tr>";
            }
        } 
        else {
            echo "<tr><td colspan='4' class='no-results'>No results found</td></tr>";
        }
        ?>
    </table>
   
    <?php endif; ?>
     <a href ="register.php">Register now</a>
     <a href="logout.php" title="Logout">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" 
     viewBox="0 0 24 24" fill="none" stroke="currentColor" 
     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
  <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
  <polyline points="16 17 21 12 16 7"/>
  <line x1="21" y1="12" x2="9" y2="12"/>
</svg>

</a>


</div>

</body>
</html>

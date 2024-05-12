<?php

include('db.php');


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone_number = $_POST["phone_number"];
    $password = $_POST["password"];

    
    $stmt = $conn->prepare("SELECT * FROM users WHERE phone_number = ?");
    $stmt->bind_param("s", $phone_number);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo "Phone number already registered";
    } else {
        
        $stmt = $conn->prepare("INSERT INTO users (phone_number, password, amount) VALUES (?, ?, '1000')");
        $stmt->bind_param("ss", $phone_number, password_hash($password, PASSWORD_DEFAULT));
        if ($stmt->execute()) {
            echo "Registration successful";
            header("Location: login.php");
            exit;
        } else {
            echo "Error: " . $conn->error;
        }
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registration</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" >
  <style>
    body {
      background-color: #e0ffff;
    }
    .container {
      border-radius: 10px;
      background-color: #ffffff;
      padding: 20px;
    }
    .btn-primary {
      background-color: #007bff;
      border-color: #007bff;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Registration</h1>

    <form method="post" action="register.php">
      <div class="mb-3">
        <label for="phone_number" class="form-label">Phone Number:</label>
        <input type="text" class="form-control" id="phone_number" name="phone_number" required>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password:</label>
        <input type="password" class="form-control" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary">Register</button>

      <a href="#">Already Registered?</a>
    <a href="login.php">Login</a>
    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

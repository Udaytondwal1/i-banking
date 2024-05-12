<?php
session_start();

include('db.php');


if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}



$user_id = $_SESSION["user_id"];


$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();


$stmt = $conn->prepare("
SELECT t.*,
       u1.phone_number AS sender_phone,
       u2.phone_number AS receiver_phone
FROM transactions AS t
LEFT JOIN users AS u1 ON t.sender_id = u1.id
LEFT JOIN users AS u2 ON t.receiver_id = u2.id
WHERE t.sender_id = ? OR t.receiver_id = ?
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$transactions = $stmt->get_result();


// $stmt->close();
// $conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paytm Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    <style>
        body {
            background-color: #f0f9ff;
        }
        .nav-link {
            color: #007bff;
        }
        .nav-link.active {
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
        }
        .table td,
        .table th {
            color: #333;
        }
    </style>
</head>

<body>
    <div class="container mt-3">
        <h2>Welcome <?php echo $user["phone_number"]; ?></h2>
        <p>Your amount: <?php echo $user["amount"]; ?></p>

        <ul class="nav nav-tabs">
            <li class="nav-item active"><a data-toggle="tab" href="#send-money">Send Mone</a></li>
            <li class="nav-item"><a data-toggle="tab" href="#add-money">Add Money</a></li>
            <li class="nav-item"><a data-toggle="tab" href="#transactions">Transaction History</a></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane active" id="send-money">
                <form method="post" action="dashboard.php">
                    <div class="mb-3">
                        <label for="recipient_phone" class="form-label">Recipient Phone Number:</label>
                        <input type="text" class="form-control" id="recipient_phone" name="recipient_phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount:</label>
                        <input type="number" class="form-control" id="amount" name="amount" min="1" required>
                    </div>
                    <button type="submit" name="send-money" class="btn btn-primary">Send Money</button>
                </form>
            </div>

            <div class="tab-pane" id="add-money">
                <p>Add money to your Account</p>
                <form method="post" action="dashboard.php">
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount:</label>
                        <input type="number" class="form-control" id="amount" name="amount" min="1" required>
                    </div>
                    <button type="submit" name="add-money" class="btn btn-primary">Add Money</button>
                </form>
            </div>

            <div class="tab-pane" id="transactions">
                        <h2>Transaction History</h2>
                        <table class='table table-bordered'>
                          <thead>
                            <tr>
                              <th>Date</th>
                              <th>Transaction</th>
                              <th>Amount</th>
                              <th>Type</th>
                            </tr>
                          </thead>
                          <tbody><?php
                            while ($row = $transactions->fetch_assoc()) {
                                $sender_phone = $row['sender_phone'] ?? 'N/A'; 
                                $receiver_phone = $row['receiver_phone'] ?? 'N/A'; 

                              
                                echo "<tr>
                                        <td>" . $row["timestamp"] . "</td>
                                        <td> Debit From: '.$sender_phone.'Credit To:'. $receiver_phone. '</td>
                                        <td>" . $row["amount"] . "</td>
                                        <td>" . $row["type"] . "</td>
                                      </tr>";
                              }?>
                              
                        
                </tbody>
                        </table>
                

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
</body>

</html>
<?php


// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$sender_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['send-money'])) {
        $recipient_phone = $_POST["recipient_phone"];
        $amount = $_POST["amount"];

        if (!is_numeric($amount) || $amount <= 0) {
            echo "Invalid amount. Please enter a positive number.";
            exit();
        }

       
        $stmt = $conn->prepare("SELECT amount FROM users WHERE id = ?");
        $stmt->bind_param("i", $sender_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $sender_amount = $result->fetch_assoc()["amount"];
        $stmt->close();

        if ($sender_amount < $amount) {
            echo "Insufficient amount. Please add money to your wallet.";
            exit();
        }


        $conn->begin_transaction();

     
        $stmt = $conn->prepare("UPDATE users SET amount = amount - ? WHERE id = ?");
        $stmt->bind_param("di", $amount, $sender_id);
        $stmt->execute();
        $update_sender = $stmt->affected_rows;
        $stmt->close();

        $stmt = $conn->prepare("SELECT id FROM users WHERE phone_number = ?");
        $stmt->bind_param("s", $recipient_phone);
        $stmt->execute();
        $result = $stmt->get_result();

        $recipient_id = null;
        if ($result->num_rows > 0) {
            $recipient_id = $result->fetch_assoc()["id"];
        }

        $stmt->close();

        
        if ($recipient_id !== null) {
            $stmt = $conn->prepare("UPDATE users SET amount = amount + ? WHERE id = ?");
            $stmt->bind_param("di", $amount, $recipient_id);
            $stmt->execute();
            $update_recipient = $stmt->affected_rows;
            $stmt->close();
        }

        
        $stmt = $conn->prepare("INSERT INTO transactions (sender_id, receiver_id, amount, type) VALUES (?, ?, ?, ?)");
        $type = 'debit';
        $stmt->bind_param("iids", $sender_id, $recipient_id, $amount, $type);
        $stmt->execute();
        $insert_transaction = $stmt->affected_rows;
        $stmt->close();

       
        if ($update_sender === 1 && ($update_recipient === 1 || $recipient_id === null) && $insert_transaction === 1) {
            $conn->commit();
            echo "Money sent successfully!";
        } else {
            $conn->rollback();
            echo "An error occurred. Please try again later.";
        }

        $conn->close();
    } else {
        
        $amount = $_POST["amount"];

      
        if (!is_numeric($amount) || $amount <= 0) {
            echo "Invalid amount. Please enter a positive number.";
            exit();
        }

        // Update user amount
        $stmt = $conn->prepare("UPDATE users SET amount = amount + ? WHERE id = ?");
        $stmt->bind_param("di", $amount, $user_id);
        $stmt->execute();

        if ($stmt->affected_rows === 1) {
            echo "Money added successfully! Your new amount is: $";
            $stmt = $conn->prepare("SELECT amount FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $new_amount = $result->fetch_assoc()["amount"];
            echo number_format($new_amount, 2); 
        } else {
            echo "An error occurred. Please try again later.";
        }

                // Insert transaction record
                $stmt = $conn->prepare("INSERT INTO transactions (sender_id, receiver_id, amount, type) VALUES (?, ?, ?, ?)");
                $type = 'credit';
                $stmt->bind_param("iids", $sender_id, $sender_id, $amount, $type);
                $stmt->execute();
                $insert_transaction = $stmt->affected_rows;
                $stmt->close();

        // $stmt->close();
        // $conn->close();
    }
}
?>
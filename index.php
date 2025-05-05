<?php
$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "userdb";


$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->select_db($dbname);


function sanitize($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}


$msg = "";
$edit_id = 0;
$edit_name = "";
$edit_email = "";
$edit_phone = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password_raw = $_POST['password'];
    $phone = sanitize($_POST['phone']);
    
    if (empty($name) || empty($email) || ($edit_id == 0 && empty($password_raw)) || empty($phone)) {
        $msg = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "Invalid email format.";
    } else {
        $hashed_password = password_hash($password_raw, PASSWORD_DEFAULT);

        if (!empty($_POST['edit_id'])) {
            $edit_id = intval($_POST['edit_id']);
            if (empty($password_raw)) {
                $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=? WHERE id=?");
                $stmt->bind_param("sssi", $name, $email, $phone, $edit_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET name=?, email=?, password=?, phone=? WHERE id=?");
                $stmt->bind_param("ssssi", $name, $email, $hashed_password, $phone, $edit_id);
            }
            if ($stmt->execute()) {
                $msg = "User updated successfully.";
            } else {
                $msg = "Error updating user: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $phone);
            if ($stmt->execute()) {
                $msg = "User registered successfully.";
            } else {
                if (strpos($stmt->error, "Duplicate entry") !== false) {
                    $msg = "Email already exists. Use another email.";
                } else {
                    $msg = "Error: " . $stmt->error;
                }
            }
            $stmt->close();
        }
    }
}

if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        $msg = "User deleted successfully.";
    } else {
        $msg = "Error deleting user: " . $stmt->error;
    }
    $stmt->close();
}

if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT id, name, email, phone FROM users WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $stmt->bind_result($eid, $ename, $eemail, $ephone);
    if ($stmt->fetch()) {
        $edit_name = $ename;
        $edit_email = $eemail;
        $edit_phone = $ephone;
    }
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
<title>User Signup Form and CRUD</title>
<style>
  
  * {
    box-sizing: border-box;
  }
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f4f7f8;
    color: #333;
    padding: 20px 10px 40px 10px;
    margin: 0;
  }
  h1, h2 {
    text-align: center;
    color: #2c3e50;
    margin-bottom: 15px;
  }

  .form-container {
    max-width: 350px;
    margin-left: auto;
    margin-right: auto;
    background: #fff;
    padding: 20px 20px 25px 20px;
    border-radius: 8px;
    box-shadow: 0 0 6px rgba(0,0,0,0.1);
    margin-bottom: 30px;
  }
  
  form {
    width: 100%;
  }
  
  label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
  }
  
  input[type="text"],
  input[type="email"],
  input[type="password"],
  input[type="tel"] {
    width: 100%;
    padding: 10px 8px;
    border: 1.5px solid #ccc;
    border-radius: 5px;
    margin-bottom: 15px;
    font-size: 14px;
    transition: border-color 0.3s ease;
  }
  
  input[type="text"]:focus,
  input[type="email"]:focus,
  input[type="password"]:focus,
  input[type="tel"]:focus {
    border-color: #2980b9;
    outline: none;
  }
  
  button[type="submit"] {
    background-color: #2980b9;
    border: none;
    color: white;
    font-weight: 700;
    padding: 12px;
    width: 100%;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s ease;
  }
  
  button[type="submit"]:hover {
    background-color: #21618c;
  }
  
  .message {
    text-align: center;
    margin-bottom: 15px;
    color: #e74c3c;
    font-weight: 600;
  }

  .table-container {
    max-width: 100%;
    overflow-x: auto;
  }

  table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 8px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    font-size: 14px;
  }
  
  th, td {
    text-align: left;
    padding: 14px 16px;
    vertical-align: middle;
  }

  th {
    background-color: #2980b9;
    color: white;
    font-weight: 600;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }

  tbody tr:last-child td:first-child {
    border-bottom-left-radius: 8px;
  }
  tbody tr:last-child td:last-child {
    border-bottom-right-radius: 8px;
  }

  tbody tr {
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    background: #fafafa;
  }
  
  tbody tr:not(:last-child) {
    margin-bottom: 8px;
  }
  
  tbody tr:hover {
    background: #ecf6fd;
  }
  
  /* Border for cells - subtle */
  td:not(:last-child) {
    border-right: 1px solid #e1e4e8;
  }
  
  /* Action buttons */
  .action-btn {
    background-color: #3498db;
    border: none;
    color: white;
    padding: 6px 12px;
    margin-right: 8px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    text-decoration: none;
    display: inline-block;
    transition: background-color 0.3s ease;
    user-select: none;
  }
  
  .action-btn.delete {
    background-color: #e74c3c;
  }
  
  .action-btn:hover {
    opacity: 0.85;
  }

  @media (max-width: 480px) {
    body {
      padding: 15px 10px 40px 10px;
      font-size: 14px;
    }
    .form-container {
      max-width: 100%;
      padding: 15px 15px 20px 15px;
    }
    button[type="submit"] {
      font-size: 14px;
      padding: 10px;
    }
    table {
      font-size: 13px;
    }
    th, td {
      padding: 12px 10px;
    }
    .action-btn {
      padding: 5px 8px;
      font-size: 12px;
      margin-right: 5px;
    }
  }
</style>
</head>
<body>

<h1>User Signup</h1>

<div class="form-container">
<?php if ($msg): ?>
    <div class="message"><?php echo $msg; ?></div>
<?php endif; ?>

<form method="POST" action="">
    <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>" />
    
    <label for="name">Name</label>
    <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($edit_name); ?>" />

    <label for="email">Email</label>
    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($edit_email); ?>" />

    <label for="password">Password <?php echo $edit_id ? "(leave blank to keep current)" : "(min 6)"; ?></label>
    <input type="password" id="password" name="password" <?php echo $edit_id ? '' : 'required'; ?> minlength="6" autocomplete="new-password" />

    <label for="phone">Phone</label>
    <input type="tel" id="phone" name="phone" required pattern="[0-9\-\+\s]+" value="<?php echo htmlspecialchars($edit_phone); ?>" />

    <button type="submit"><?php echo $edit_id ? 'Update User' : 'Sign Up'; ?></button>
</form>
</div>

<h2>Registered Users</h2>

<div class="table-container">
<?php
$result = $conn->query("SELECT id, name, email, phone, created_at FROM users ORDER BY created_at DESC");
if ($result->num_rows > 0):
?>

<table>
    <thead>
        <tr>
            <th>Name</th><th>Email</th><th>Phone</th><th>Created At</th><th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo htmlspecialchars($row['phone']); ?></td>
            <td><?php echo $row['created_at']; ?></td>
            <td>
                <a class="action-btn" href="?edit=<?php echo $row['id']; ?>">Edit</a>
                <a class="action-btn delete" href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure to delete this user?');">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php else: ?>
<p>No users found.</p>
<?php endif; ?>
</div>

</body>
</html>

<?php
$conn->close();
?>

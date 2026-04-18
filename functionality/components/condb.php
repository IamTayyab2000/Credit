<?php
$conn = new mysqli("localhost","root","","cred-test");
$conn->set_charset("utf8mb4");

// Check connection
if ($conn -> connect_errno) {
  echo "Failed to connect to MySQL: " . $mysqli -> connect_error;
  exit();
}
?>
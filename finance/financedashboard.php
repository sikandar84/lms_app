<?php
session_start();
require_once("../config/db.php");
require_once("../includes/auth.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'finance') {
    header("Location: ../auth/login.php");
    exit();
}

$finance_name = $_SESSION['full_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Finance Dashboard</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #eef2f3;
            margin: 0;
            padding: 0;
        }

        .header {
            background: #005f73;
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h2 {
            margin: 0;
        }

        .header a {
            color: #ffd;
            text-decoration: none;
            font-size: 16px;
            margin-left: 15px;
        }

        .dashboard {
            padding: 40px;
        }

        .card-container {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            justify-content: center;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            width: 260px;
            padding: 30px 20px;
            text-align: center;
            transition: 0.3s;
        }

        .card:hover {
            transform: scale(1.03);
            box-shadow: 0 6px 14px rgba(0,0,0,0.15);
        }

        .card h3 {
            margin-bottom: 15px;
            color: #005f73;
        }

        .card a {
            text-decoration: none;
            background: #008891;
            color: white;
            padding: 10px 18px;
            border-radius: 6px;
            display: inline-block;
            margin-top: 10px;
        }

        .footer {
            margin-top: 60px;
            text-align: center;
            color: #777;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="header">
    <h2>Welcome, <?= htmlspecialchars($finance_name) ?> (Finance)</h2>
    <div>
        <a href="profile.php">Profile</a> |
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="dashboard">
    <h2 style="text-align:center; margin-bottom: 40px;">Finance Dashboard</h2>

    <div class="card-container">
        <div class="card">
            <h3>Generate Invoice</h3>
            <p>Create invoice</p>
            <a href="generate_invoice.php">Generate</a>
        </div>

        <div class="card">
            <h3>All Invoices</h3>
            <p>View all student invoices</p>
            <a href="view_invoices.php">View</a>
        </div>

        <div class="card">
            <h3>Confirm Payments</h3>
            <p>Accept or reject student payments</p>
            <a href="confirm_payments.php">Manage</a>
        </div>

        <div class="card">
            <h3>Approved Visas</h3>
            <p>See students with visa approval</p>
            <a href="approved_visa_students.php">View</a>
        </div>

        <div class="card">
            <h3>Offer Letters</h3>
            <p>View & upload offer letters</p>
            <a href="offer_letter_students.php">Manage</a>
        </div>
    </div>
</div>

<div class="footer">
    &copy; <?= date("Y") ?> LMS Finance Panel | Designed by Sikandar Khan
</div>

</body>
</html>

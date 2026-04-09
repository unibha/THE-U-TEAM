<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Check if a specific page title is set, otherwise use a default -->
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Academic Management System'; ?></title>
    
    <!-- Link to the centralized CSS (Paths are relative to the public/ folder where pages live) -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

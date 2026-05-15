<?php

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Check if a specific page title is set, otherwise use a default -->
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Academic Management System'; ?></title>
    
    <!-- Link to FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Link to the centralized CSS -->
    <link rel="stylesheet" href="../assets/style.css?v=<?php echo time(); ?>">
</head>
<body>

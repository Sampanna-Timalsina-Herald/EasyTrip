<?php
// A simple debugging script to check what's being submitted from your form

echo "<h1>Form Data Debug</h1>";
echo "<h2>GET Data</h2>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

echo "<h2>POST Data</h2>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<h2>All Form Data</h2>";
echo "<pre>";
print_r($_REQUEST);
echo "</pre>";

echo "<p>Go back to <a href='index.php'>homepage</a></p>";
?>

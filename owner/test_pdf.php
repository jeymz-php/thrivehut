<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h2>PDF Generation Test</h2>";

// Test 1: Check if user is logged in
echo "<h3>Test 1: Session Check</h3>";
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    echo "✓ User logged in: " . $_SESSION['user_id'] . " (Role: " . $_SESSION['role'] . ")<br>";
} else {
    echo "✗ User not logged in<br>";
    echo "Session data: " . print_r($_SESSION, true) . "<br>";
}

// Test 2: Check if database connection works
echo "<h3>Test 2: Database Connection</h3>";
try {
    require('../config/database.php');
    echo "✓ Database connection successful<br>";
    
    // Test a simple query
    $stmt = $db->query("SELECT COUNT(*) as count FROM sales");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Sales table accessible. Total records: " . $result['count'] . "<br>";
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "<br>";
}

// Test 3: Check if FPDF library is accessible
echo "<h3>Test 3: FPDF Library</h3>";
try {
    require('../libs/fpdf/fpdf.php');
    echo "✓ FPDF library loaded successfully<br>";
    
    // Test creating a simple PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(40, 10, 'Test PDF');
    echo "✓ FPDF object created successfully<br>";
} catch (Exception $e) {
    echo "✗ FPDF error: " . $e->getMessage() . "<br>";
}

// Test 4: Check if logo file exists
echo "<h3>Test 4: Logo File</h3>";
$logo_path = '../images/thrivehut logo png.png';
if (file_exists($logo_path)) {
    echo "✓ Logo file exists: " . $logo_path . "<br>";
    echo "File size: " . filesize($logo_path) . " bytes<br>";
} else {
    echo "✗ Logo file not found: " . $logo_path . "<br>";
}

// Test 5: Check PHP version and extensions
echo "<h3>Test 5: PHP Environment</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "GD Extension: " . (extension_loaded('gd') ? '✓ Loaded' : '✗ Not loaded') . "<br>";
echo "MBString Extension: " . (extension_loaded('mbstring') ? '✓ Loaded' : '✗ Not loaded') . "<br>";

// Test 6: Check file permissions
echo "<h3>Test 6: File Permissions</h3>";
$test_dir = '../libs/fpdf';
if (is_readable($test_dir)) {
    echo "✓ FPDF directory is readable<br>";
} else {
    echo "✗ FPDF directory is not readable<br>";
}

echo "<br><a href='sales.php'>Back to Sales Page</a>";
?> 
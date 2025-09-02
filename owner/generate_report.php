<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check if user is logged in and is owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    ob_end_clean();
    die('Access denied. Please log in as owner.');
}

try {
    require('../config/database.php');
    require('../libs/fpdf/fpdf.php');
} catch (Exception $e) {
    ob_end_clean();
    die('Error loading required files: ' . $e->getMessage());
}

class PDF extends FPDF
{
    // Page header
    function Header()
    {
        try {
            // Logo - check if file exists first
            $logo_path = '../images/thrivehut logo png.png';
            if (file_exists($logo_path)) {
                $this->Image($logo_path, 10, 6, 30);
            }
            // Set font
            $this->SetFont('Arial', 'B', 15);
            // Company Name
            $this->Cell(0, 6, 'THRIVEHUT MOTORWORKS', 0, 1, 'C');
            // Address
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 6, 'Blk 4 Lot 1 Queensville, Bagumbong, North Caloocan City', 0, 1, 'C');
            
            // Line break
            $this->Ln(15);
        } catch (Exception $e) {
            // If logo fails, continue without it
            $this->SetFont('Arial', 'B', 15);
            $this->Cell(0, 6, 'THRIVEHUT MOTORWORKS', 0, 1, 'C');
            $this->SetFont('Arial', '', 10);
            $this->Cell(0, 6, 'Blk 4 Lot 1 Queensville, Bagumbong, North Caloocan City', 0, 1, 'C');
            $this->Ln(15);
        }
    }

    // Page footer
    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // Sales table
    function SalesTable($header, $data)
    {
        // Colors, line width and bold font
        $this->SetFillColor(255, 0, 0);
        $this->SetTextColor(255);
        $this->SetDrawColor(128, 0, 0);
        $this->SetLineWidth(.3);
        $this->SetFont('', 'B');
        // Header
        $w = array(35, 40, 35, 30, 45); // Column widths
        for ($i = 0; $i < count($header); $i++)
            $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
        $this->Ln();
        // Color and font restoration
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('');
        // Data
        $fill = false;
        $totalSales = 0;
        
        if (empty($data)) {
            $this->Cell(array_sum($w), 6, 'No sales data found for the selected period', 1, 1, 'C');
        } else {
            foreach ($data as $row) {
                $this->Cell($w[0], 6, $row['transaction_number'], 'LR', 0, 'L', $fill);
                $this->Cell($w[1], 6, date('Y-m-d', strtotime($row['created_at'])), 'LR', 0, 'L', $fill);
                $this->Cell($w[2], 6, 'PHP ' . number_format($row['price'], 2), 'LR', 0, 'R', $fill);
                $this->Cell($w[3], 6, $row['discount'] . '%', 'LR', 0, 'R', $fill);
                $this->Cell($w[4], 6, $row['payment_method'], 'LR', 0, 'L', $fill);
                $this->Ln();
                $fill = !$fill;
                $totalSales += $row['price'];
            }
        }
        // Closing line
        $this->Cell(array_sum($w), 0, '', 'T');
        $this->Ln();
        // Total
        $this->SetFont('', 'B');
        $this->Cell($w[0] + $w[1], 8, 'Total Sales:', 0, 0, 'R');
        $this->Cell($w[2], 8, 'PHP ' . number_format($totalSales, 2), 'T', 1, 'R');
    }
}

try {
    // Get filter parameters
    $filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'monthly';
    $month = isset($_GET['month']) ? $_GET['month'] : date('m');
    $year = isset($_GET['year']) ? $_GET['year'] : date('Y');

    // Validate parameters
    if (!in_array($filter_type, ['monthly', 'quarterly', 'annually'])) {
        throw new Exception('Invalid filter type');
    }

    $sql = "SELECT transaction_number, created_at, price, discount, payment_method FROM sales WHERE 1=1";
    $params = [];

    if ($filter_type == 'monthly') {
        $sql .= " AND MONTH(created_at) = :month AND YEAR(created_at) = :year";
        $params[':month'] = $month;
        $params[':year'] = $year;
    } elseif ($filter_type == 'quarterly') {
        $quarter = ceil($month / 3);
        $start_month = ($quarter - 1) * 3 + 1;
        $end_month = $start_month + 2;
        $sql .= " AND MONTH(created_at) BETWEEN :start_month AND :end_month AND YEAR(created_at) = :year";
        $params[':start_month'] = $start_month;
        $params[':end_month'] = $end_month;
        $params[':year'] = $year;
    } elseif ($filter_type == 'annually') {
        $sql .= " AND YEAR(created_at) = :year";
        $params[':year'] = $year;
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $salesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Clear any output that might have been sent
    ob_end_clean();
    
    // Set proper headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Thrivehut_Sales_Report.pdf"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // PDF generation
    $pdf = new PDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 12);

    // Column headings
    $header = array('Transaction ID', 'Date', 'Total Amount', 'Discount', 'Payment Mode');

    // Report Title
    $report_title = "Sales Report for ";
    if ($filter_type == 'monthly') {
        $report_title .= date('F', mktime(0, 0, 0, $month, 10)) . " " . $year;
    } elseif ($filter_type == 'quarterly') {
         $quarter = ceil($month / 3);
        $report_title .= "Q" . $quarter . " " . $year;
    } elseif ($filter_type == 'annually') {
        $report_title .= $year;
    }
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, $report_title, 0, 1, 'C');
    $pdf->Ln(5);

    $pdf->SalesTable($header, $salesData);
    $pdf->Output('D', 'Thrivehut_Sales_Report.pdf');
    
} catch (Exception $e) {
    // Clear any output
    ob_end_clean();
    
    // Log error for debugging
    error_log('PDF Generation Error: ' . $e->getMessage());
    
    // Show user-friendly error
    echo '<html><body>';
    echo '<h2>Error Generating PDF Report</h2>';
    echo '<p>Sorry, there was an error generating the PDF report. Please try again.</p>';
    echo '<p><a href="javascript:history.back()">Go Back</a></p>';
    echo '</body></html>';
}
?> 
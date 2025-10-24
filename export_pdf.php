<?php
require('includes/fpdf/fpdf.php');
include 'includes/db.php';

$pdf = new FPDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(280, 10, 'Motor Dealer Sales Report', 0, 1, 'C');
$pdf->Ln(5);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(10, 10, 'ID', 1);
$pdf->Cell(60, 10, 'Vehicle', 1);
$pdf->Cell(50, 10, 'Customer', 1);
$pdf->Cell(40, 10, 'Sold By', 1);
$pdf->Cell(40, 10, 'Amount (₵)', 1);
$pdf->Cell(40, 10, 'Date', 1);
$pdf->Ln();

$pdf->SetFont('Arial', '', 11);
$query = "
    SELECT s.id, v.make, v.model, s.customer_name, s.amount, s.sale_date, u.username
    FROM sales s
    JOIN vehicles v ON s.vehicle_id = v.id
    JOIN users u ON s.sold_by = u.id
";
$result = $conn->query($query);

while($row = $result->fetch_assoc()){
    $pdf->Cell(10, 10, $row['id'], 1);
    $pdf->Cell(60, 10, $row['make'].' '.$row['model'], 1);
    $pdf->Cell(50, 10, $row['customer_name'], 1);
    $pdf->Cell(40, 10, $row['username'], 1);
    $pdf->Cell(40, 10, number_format($row['amount'], 2), 1);
    $pdf->Cell(40, 10, $row['sale_date'], 1);
    $pdf->Ln();
}

$pdf->Output('D', 'sales_report.pdf');
?>

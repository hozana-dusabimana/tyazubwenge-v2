<?php
// Simple PDF Generator using TCPDF
// Note: You'll need to download TCPDF and place it in a 'tcpdf' folder
// Download from: https://github.com/tecnickcom/TCPDF

class PDFGenerator {
    private $pdf;
    private $siteName;
    private $branchName;
    
    public function __construct($siteName = 'Tyazubwenge Management System', $branchName = '') {
        $this->siteName = $siteName;
        $this->branchName = $branchName;
        
        // Try to load TCPDF if available
        $tcpdfPath = __DIR__ . '/../tcpdf/tcpdf.php';
        if (file_exists($tcpdfPath)) {
            require_once($tcpdfPath);
            $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        } else {
            // Fallback: Create a simple HTML-based PDF generator
            $this->pdf = null;
        }
    }
    
    public function generateReport($reportType, $data, $title, $dateFrom = null, $dateTo = null) {
        if ($this->pdf === null) {
            // Use HTML-based PDF generation
            return $this->generateHTMLPDF($reportType, $data, $title, $dateFrom, $dateTo);
        }
        
        // Use TCPDF
        return $this->generateTCPDFReport($reportType, $data, $title, $dateFrom, $dateTo);
    }
    
    private function generateHTMLPDF($reportType, $data, $title, $dateFrom, $dateTo) {
        // Generate HTML that can be printed as PDF using browser's print to PDF
        $html = $this->getReportHTML($reportType, $data, $title, $dateFrom, $dateTo);
        
        // For now, return HTML that can be converted to PDF
        // In production, you'd use a library like wkhtmltopdf or similar
        return $html;
    }
    
    private function generateTCPDFReport($reportType, $data, $title, $dateFrom, $dateTo) {
        // Set document information
        $this->pdf->SetCreator('Tyazubwenge Management System');
        $this->pdf->SetAuthor($this->siteName);
        $this->pdf->SetTitle($title);
        $this->pdf->SetSubject($title);
        
        // Remove default header/footer
        $this->pdf->setPrintHeader(true);
        $this->pdf->setPrintFooter(true);
        
        // Set margins
        $this->pdf->SetMargins(15, 25, 15);
        $this->pdf->SetHeaderMargin(5);
        $this->pdf->SetFooterMargin(10);
        
        // Set auto page breaks
        $this->pdf->SetAutoPageBreak(TRUE, 25);
        
        // Add a page
        $this->pdf->AddPage();
        
        // Set font
        $this->pdf->SetFont('helvetica', '', 10);
        
        // Generate report content
        $html = $this->getReportContent($reportType, $data, $title, $dateFrom, $dateTo);
        
        // Write HTML content
        $this->pdf->writeHTML($html, true, false, true, false, '');
        
        // Close and output PDF document
        $filename = $this->sanitizeFilename($title) . '_' . date('Y-m-d') . '.pdf';
        $this->pdf->Output($filename, 'D'); // 'D' for download
    }
    
    private function getReportHTML($reportType, $data, $title, $dateFrom, $dateTo) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php echo htmlspecialchars($title); ?></title>
            <style>
                @page { margin: 20mm; }
                body { font-family: Arial, sans-serif; font-size: 11px; }
                .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
                .header h1 { margin: 0; font-size: 18px; color: #333; }
                .header p { margin: 5px 0; color: #666; }
                .report-info { margin-bottom: 15px; }
                .report-info table { width: 100%; }
                .report-info td { padding: 3px 0; }
                .section-title { background-color: #f0f0f0; padding: 8px; font-weight: bold; margin-top: 15px; margin-bottom: 10px; border-left: 4px solid #007bff; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
                table th { background-color: #007bff; color: white; padding: 8px; text-align: left; font-weight: bold; }
                table td { padding: 6px; border-bottom: 1px solid #ddd; }
                table tr:nth-child(even) { background-color: #f9f9f9; }
                .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; text-align: center; font-size: 9px; color: #666; }
                .total-row { font-weight: bold; background-color: #e9ecef !important; }
                .text-right { text-align: right; }
                .text-center { text-align: center; }
            </style>
        </head>
        <body>
            <?php echo $this->getReportContent($reportType, $data, $title, $dateFrom, $dateTo); ?>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    private function getReportContent($reportType, $data, $title, $dateFrom, $dateTo) {
        $html = $this->getHeader($title, $dateFrom, $dateTo);
        $html .= $this->getReportBody($reportType, $data);
        $html .= $this->getFooter();
        return $html;
    }
    
    private function getHeader($title, $dateFrom, $dateTo) {
        $html = '<div class="header">';
        $html .= '<h1>' . htmlspecialchars($this->siteName) . '</h1>';
        $html .= '<p>Laboratory Products Management</p>';
        if ($this->branchName) {
            $html .= '<p>' . htmlspecialchars($this->branchName) . '</p>';
        }
        $html .= '</div>';
        
        $html .= '<div class="report-info">';
        $html .= '<table>';
        $html .= '<tr><td><strong>Report:</strong></td><td>' . htmlspecialchars($title) . '</td></tr>';
        $html .= '<tr><td><strong>Generated:</strong></td><td>' . date('d M Y H:i:s') . '</td></tr>';
        if ($dateFrom || $dateTo) {
            $html .= '<tr><td><strong>Period:</strong></td><td>';
            if ($dateFrom && $dateTo) {
                $html .= date('d M Y', strtotime($dateFrom)) . ' to ' . date('d M Y', strtotime($dateTo));
            } elseif ($dateFrom) {
                $html .= 'From ' . date('d M Y', strtotime($dateFrom));
            } elseif ($dateTo) {
                $html .= 'Until ' . date('d M Y', strtotime($dateTo));
            }
            $html .= '</td></tr>';
        }
        $html .= '</table>';
        $html .= '</div>';
        
        return $html;
    }
    
    private function getReportBody($reportType, $data) {
        $html = '';
        
        switch ($reportType) {
            case 'sales':
                $html = $this->getSalesReportHTML($data);
                break;
            case 'top_products':
                $html = $this->getTopProductsReportHTML($data);
                break;
            case 'stock_valuation':
                $html = $this->getStockValuationReportHTML($data);
                break;
            case 'profit_loss':
                $html = $this->getProfitLossReportHTML($data);
                break;
            case 'sales_by_customer':
                $html = $this->getSalesByCustomerReportHTML($data);
                break;
            case 'sales_by_category':
                $html = $this->getSalesByCategoryReportHTML($data);
                break;
            case 'slow_moving':
                $html = $this->getSlowMovingReportHTML($data);
                break;
            default:
                $html = '<p>Report type not supported.</p>';
        }
        
        return $html;
    }
    
    private function getSalesReportHTML($data) {
        if (empty($data)) {
            return '<p>No sales data available for the selected period.</p>';
        }
        
        $html = '<div class="section-title">Sales Summary</div>';
        $html .= '<table>';
        $html .= '<thead><tr>';
        $html .= '<th>Period</th>';
        $html .= '<th class="text-right">Total Sales</th>';
        $html .= '<th class="text-right">Revenue</th>';
        $html .= '<th class="text-right">Discount</th>';
        $html .= '<th class="text-right">Tax</th>';
        $html .= '</tr></thead><tbody>';
        
        $totalSales = 0;
        $totalRevenue = 0;
        $totalDiscount = 0;
        $totalTax = 0;
        
        foreach ($data as $row) {
            $totalSales += $row['total_sales'];
            $totalRevenue += $row['total_revenue'];
            $totalDiscount += $row['total_discount'];
            $totalTax += $row['total_tax'];
            
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($row['period']) . '</td>';
            $html .= '<td class="text-right">' . number_format($row['total_sales']) . '</td>';
            $html .= '<td class="text-right">' . number_format($row['total_revenue'], 2) . '</td>';
            $html .= '<td class="text-right">' . number_format($row['total_discount'], 2) . '</td>';
            $html .= '<td class="text-right">' . number_format($row['total_tax'], 2) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '<tr class="total-row">';
        $html .= '<td><strong>Total</strong></td>';
        $html .= '<td class="text-right"><strong>' . number_format($totalSales) . '</strong></td>';
        $html .= '<td class="text-right"><strong>' . number_format($totalRevenue, 2) . '</strong></td>';
        $html .= '<td class="text-right"><strong>' . number_format($totalDiscount, 2) . '</strong></td>';
        $html .= '<td class="text-right"><strong>' . number_format($totalTax, 2) . '</strong></td>';
        $html .= '</tr>';
        $html .= '</tbody></table>';
        
        return $html;
    }
    
    private function getTopProductsReportHTML($data) {
        if (empty($data)) {
            return '<p>No product data available for the selected period.</p>';
        }
        
        $html = '<div class="section-title">Top Selling Products</div>';
        $html .= '<table>';
        $html .= '<thead><tr>';
        $html .= '<th>#</th>';
        $html .= '<th>Product Name</th>';
        $html .= '<th>SKU</th>';
        $html .= '<th class="text-right">Quantity Sold</th>';
        $html .= '<th class="text-right">Revenue</th>';
        $html .= '<th class="text-right">Times Sold</th>';
        $html .= '</tr></thead><tbody>';
        
        $rank = 1;
        $totalRevenue = 0;
        $totalQuantity = 0;
        
        foreach ($data as $row) {
            $totalRevenue += $row['total_revenue'];
            $totalQuantity += $row['total_quantity_sold'];
            
            $html .= '<tr>';
            $html .= '<td>' . $rank++ . '</td>';
            $html .= '<td>' . htmlspecialchars($row['name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['sku']) . '</td>';
            $html .= '<td class="text-right">' . number_format($row['total_quantity_sold'], 2) . '</td>';
            $html .= '<td class="text-right">' . number_format($row['total_revenue'], 2) . '</td>';
            $html .= '<td class="text-right">' . number_format($row['times_sold']) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '<tr class="total-row">';
        $html .= '<td colspan="3"><strong>Total</strong></td>';
        $html .= '<td class="text-right"><strong>' . number_format($totalQuantity, 2) . '</strong></td>';
        $html .= '<td class="text-right"><strong>' . number_format($totalRevenue, 2) . '</strong></td>';
        $html .= '<td></td>';
        $html .= '</tr>';
        $html .= '</tbody></table>';
        
        return $html;
    }
    
    private function getStockValuationReportHTML($data) {
        if (empty($data)) {
            return '<p>No stock data available.</p>';
        }
        
        $html = '<div class="section-title">Stock Valuation</div>';
        $html .= '<table>';
        $html .= '<thead><tr>';
        $html .= '<th>Product Name</th>';
        $html .= '<th>SKU</th>';
        $html .= '<th class="text-right">Quantity</th>';
        $html .= '<th class="text-right">Unit Cost</th>';
        $html .= '<th class="text-right">Stock Value</th>';
        $html .= '</tr></thead><tbody>';
        
        $totalValue = 0;
        
        foreach ($data as $row) {
            $totalValue += $row['stock_value'];
            
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($row['product_name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['sku']) . '</td>';
            $html .= '<td class="text-right">' . number_format($row['quantity'], 2) . ' ' . htmlspecialchars($row['unit']) . '</td>';
            $html .= '<td class="text-right">' . number_format($row['cost_price'], 2) . '</td>';
            $html .= '<td class="text-right">' . number_format($row['stock_value'], 2) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '<tr class="total-row">';
        $html .= '<td colspan="4"><strong>Total Stock Value</strong></td>';
        $html .= '<td class="text-right"><strong>' . number_format($totalValue, 2) . '</strong></td>';
        $html .= '</tr>';
        $html .= '</tbody></table>';
        
        return $html;
    }
    
    private function getProfitLossReportHTML($data) {
        if (empty($data) || !isset($data['total_revenue'])) {
            return '<p>No financial data available for the selected period.</p>';
        }
        
        $html = '<div class="section-title">Profit & Loss Statement</div>';
        $html .= '<table>';
        $html .= '<tr><td><strong>Total Revenue:</strong></td><td class="text-right">' . number_format($data['total_revenue'], 2) . '</td></tr>';
        $html .= '<tr><td><strong>Total Cost:</strong></td><td class="text-right">' . number_format($data['total_cost'], 2) . '</td></tr>';
        $html .= '<tr class="total-row"><td><strong>Net Profit:</strong></td><td class="text-right"><strong>' . number_format($data['profit'], 2) . '</strong></td></tr>';
        $html .= '<tr><td><strong>Profit Margin:</strong></td><td class="text-right">' . number_format($data['profit_margin'], 2) . '%</td></tr>';
        $html .= '</table>';
        
        return $html;
    }
    
    private function getSalesByCustomerReportHTML($data) {
        if (empty($data)) {
            return '<p>No customer sales data available for the selected period.</p>';
        }
        
        $html = '<div class="section-title">Sales by Customer</div>';
        $html .= '<table>';
        $html .= '<thead><tr>';
        $html .= '<th>Customer Name</th>';
        $html .= '<th>Phone</th>';
        $html .= '<th class="text-right">Total Orders</th>';
        $html .= '<th class="text-right">Total Spent</th>';
        $html .= '</tr></thead><tbody>';
        
        $totalSpent = 0;
        $totalOrders = 0;
        
        foreach ($data as $row) {
            $totalSpent += $row['total_spent'];
            $totalOrders += $row['total_orders'];
            
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($row['name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['phone'] ?? '-') . '</td>';
            $html .= '<td class="text-right">' . number_format($row['total_orders']) . '</td>';
            $html .= '<td class="text-right">' . number_format($row['total_spent'], 2) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '<tr class="total-row">';
        $html .= '<td colspan="2"><strong>Total</strong></td>';
        $html .= '<td class="text-right"><strong>' . number_format($totalOrders) . '</strong></td>';
        $html .= '<td class="text-right"><strong>' . number_format($totalSpent, 2) . '</strong></td>';
        $html .= '</tr>';
        $html .= '</tbody></table>';
        
        return $html;
    }
    
    private function getSalesByCategoryReportHTML($data) {
        if (empty($data)) {
            return '<p>No category sales data available for the selected period.</p>';
        }
        
        $html = '<div class="section-title">Sales by Category</div>';
        $html .= '<table>';
        $html .= '<thead><tr>';
        $html .= '<th>Category</th>';
        $html .= '<th class="text-right">Quantity Sold</th>';
        $html .= '<th class="text-right">Total Revenue</th>';
        $html .= '</tr></thead><tbody>';
        
        $totalRevenue = 0;
        $totalQuantity = 0;
        
        foreach ($data as $row) {
            $totalRevenue += $row['total_revenue'];
            $totalQuantity += $row['total_quantity'];
            
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($row['name'] ?? 'Uncategorized') . '</td>';
            $html .= '<td class="text-right">' . number_format($row['total_quantity'], 2) . '</td>';
            $html .= '<td class="text-right">' . number_format($row['total_revenue'], 2) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '<tr class="total-row">';
        $html .= '<td><strong>Total</strong></td>';
        $html .= '<td class="text-right"><strong>' . number_format($totalQuantity, 2) . '</strong></td>';
        $html .= '<td class="text-right"><strong>' . number_format($totalRevenue, 2) . '</strong></td>';
        $html .= '</tr>';
        $html .= '</tbody></table>';
        
        return $html;
    }
    
    private function getSlowMovingReportHTML($data) {
        if (empty($data)) {
            return '<p>No slow-moving products found.</p>';
        }
        
        $html = '<div class="section-title">Slow Moving Products</div>';
        $html .= '<table>';
        $html .= '<thead><tr>';
        $html .= '<th>Product Name</th>';
        $html .= '<th>SKU</th>';
        $html .= '<th class="text-right">Current Stock</th>';
        $html .= '<th class="text-right">Quantity Sold</th>';
        $html .= '<th class="text-right">Days Since Last Sale</th>';
        $html .= '</tr></thead><tbody>';
        
        foreach ($data as $row) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($row['name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['sku']) . '</td>';
            $html .= '<td class="text-right">' . number_format($row['current_stock'], 2) . '</td>';
            $html .= '<td class="text-right">' . number_format($row['sold_quantity'], 2) . '</td>';
            $html .= '<td class="text-right">' . number_format($row['days_since_last_sale'] ?? 0) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        
        return $html;
    }
    
    private function getFooter() {
        $html = '<div class="footer">';
        $html .= '<p>Generated by ' . htmlspecialchars($this->siteName) . ' on ' . date('d M Y H:i:s') . '</p>';
        $html .= '<p>This is a computer-generated report. No signature required.</p>';
        $html .= '</div>';
        return $html;
    }
    
    private function sanitizeFilename($filename) {
        return preg_replace('/[^A-Za-z0-9_-]/', '_', $filename);
    }
}
?>


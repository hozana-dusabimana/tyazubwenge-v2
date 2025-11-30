<?php
if (empty($data)) {
    echo '<div class="section-title">No Data Available</div>';
    echo '<p>No sales data found for the selected period.</p>';
    exit;
}

$totalSales = 0;
$totalRevenue = 0;
$totalDiscount = 0;
$totalTax = 0;

foreach ($data as $row) {
    $totalSales += $row['total_sales'];
    $totalRevenue += $row['total_revenue'];
    $totalDiscount += $row['total_discount'];
    $totalTax += $row['total_tax'];
}
?>

<div class="summary-cards">
    <div class="summary-card">
        <p>Total Sales</p>
        <h3><?php echo number_format($totalSales); ?></h3>
    </div>
    <div class="summary-card success">
        <p>Total Revenue</p>
        <h3><?php echo formatCurrency($totalRevenue); ?></h3>
    </div>
    <div class="summary-card info">
        <p>Total Discount</p>
        <h3><?php echo formatCurrency($totalDiscount); ?></h3>
    </div>
    <div class="summary-card">
        <p>Total Tax</p>
        <h3><?php echo formatCurrency($totalTax); ?></h3>
    </div>
</div>

<div class="section-title">Sales Details by Period</div>

<table>
    <thead>
        <tr>
            <th>Period</th>
            <th class="text-right">Total Sales</th>
            <th class="text-right">Revenue</th>
            <th class="text-right">Discount</th>
            <th class="text-right">Tax</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $row): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['period']); ?></td>
            <td class="text-right"><?php echo number_format($row['total_sales']); ?></td>
            <td class="text-right"><?php echo formatCurrency($row['total_revenue']); ?></td>
            <td class="text-right"><?php echo formatCurrency($row['total_discount']); ?></td>
            <td class="text-right"><?php echo formatCurrency($row['total_tax']); ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="total-row">
            <td><strong>Total</strong></td>
            <td class="text-right"><strong><?php echo number_format($totalSales); ?></strong></td>
            <td class="text-right"><strong><?php echo formatCurrency($totalRevenue); ?></strong></td>
            <td class="text-right"><strong><?php echo formatCurrency($totalDiscount); ?></strong></td>
            <td class="text-right"><strong><?php echo formatCurrency($totalTax); ?></strong></td>
        </tr>
    </tbody>
</table>


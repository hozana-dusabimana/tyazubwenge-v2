<?php
if (empty($data)) {
    echo '<div class="section-title">No Data Available</div>';
    echo '<p>No stock data available.</p>';
    exit;
}

$totalValue = 0;
foreach ($data as $row) {
    $totalValue += $row['stock_value'];
}
?>

<div class="summary-cards">
    <div class="summary-card success">
        <p>Total Stock Value</p>
        <h3><?php echo formatCurrency($totalValue); ?></h3>
    </div>
    <div class="summary-card">
        <p>Total Items</p>
        <h3><?php echo count($data); ?></h3>
    </div>
</div>

<div class="section-title">Stock Valuation Details</div>

<table>
    <thead>
        <tr>
            <th>Product Name</th>
            <th>SKU</th>
            <th class="text-right">Quantity</th>
            <th>Unit</th>
            <th class="text-right">Unit Cost</th>
            <th class="text-right">Stock Value</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $row): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['product_name']); ?></td>
            <td><?php echo htmlspecialchars($row['sku']); ?></td>
            <td class="text-right"><?php echo number_format($row['quantity'], 2); ?></td>
            <td><?php echo htmlspecialchars($row['unit']); ?></td>
            <td class="text-right"><?php echo formatCurrency($row['cost_price']); ?></td>
            <td class="text-right"><strong><?php echo formatCurrency($row['stock_value']); ?></strong></td>
        </tr>
        <?php endforeach; ?>
        <tr class="total-row">
            <td colspan="5"><strong>Total Stock Value</strong></td>
            <td class="text-right"><strong><?php echo formatCurrency($totalValue); ?></strong></td>
        </tr>
    </tbody>
</table>


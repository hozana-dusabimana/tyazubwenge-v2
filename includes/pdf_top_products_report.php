<?php
if (empty($data)) {
    echo '<div class="section-title">No Data Available</div>';
    echo '<p>No product sales data found for the selected period.</p>';
    exit;
}

$totalRevenue = 0;
$totalQuantity = 0;
$rank = 1;
?>

<div class="section-title">Top Selling Products</div>

<table>
    <thead>
        <tr>
            <th>Rank</th>
            <th>Product Name</th>
            <th>SKU</th>
            <th class="text-right">Quantity Sold</th>
            <th class="text-right">Revenue</th>
            <th class="text-right">Times Sold</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $row): 
            $totalRevenue += $row['total_revenue'];
            $totalQuantity += $row['total_quantity_sold'];
        ?>
        <tr>
            <td class="text-center"><?php echo $rank++; ?></td>
            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
            <td><?php echo htmlspecialchars($row['sku']); ?></td>
            <td class="text-right"><?php echo number_format($row['total_quantity_sold'], 2); ?></td>
            <td class="text-right"><strong><?php echo formatCurrency($row['total_revenue']); ?></strong></td>
            <td class="text-right"><?php echo number_format($row['times_sold']); ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="total-row">
            <td colspan="3"><strong>Total</strong></td>
            <td class="text-right"><strong><?php echo number_format($totalQuantity, 2); ?></strong></td>
            <td class="text-right"><strong><?php echo formatCurrency($totalRevenue); ?></strong></td>
            <td></td>
        </tr>
    </tbody>
</table>


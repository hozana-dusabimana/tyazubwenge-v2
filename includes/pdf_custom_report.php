<?php
if (empty($data)) {
    echo '<div class="section-title">No Data Available</div>';
    echo '<p>No data found for the selected report type and period.</p>';
    exit;
}

if ($customType === 'sales_by_customer') {
    $totalSpent = 0;
    $totalOrders = 0;
    ?>
    <div class="section-title">Sales by Customer</div>
    <table>
        <thead>
            <tr>
                <th>Customer Name</th>
                <th>Phone</th>
                <th class="text-right">Total Orders</th>
                <th class="text-right">Total Spent</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row): 
                $totalSpent += $row['total_spent'];
                $totalOrders += $row['total_orders'];
            ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                <td><?php echo htmlspecialchars($row['phone'] ?? '-'); ?></td>
                <td class="text-right"><?php echo number_format($row['total_orders']); ?></td>
                <td class="text-right"><strong><?php echo formatCurrency($row['total_spent']); ?></strong></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="2"><strong>Total</strong></td>
                <td class="text-right"><strong><?php echo number_format($totalOrders); ?></strong></td>
                <td class="text-right"><strong><?php echo formatCurrency($totalSpent); ?></strong></td>
            </tr>
        </tbody>
    </table>
    <?php
} elseif ($customType === 'sales_by_category') {
    $totalRevenue = 0;
    $totalQuantity = 0;
    ?>
    <div class="section-title">Sales by Category</div>
    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th class="text-right">Quantity Sold</th>
                <th class="text-right">Total Revenue</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row): 
                $totalRevenue += $row['total_revenue'];
                $totalQuantity += $row['total_quantity'];
            ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($row['name'] ?? 'Uncategorized'); ?></strong></td>
                <td class="text-right"><?php echo number_format($row['total_quantity'], 2); ?></td>
                <td class="text-right"><strong><?php echo formatCurrency($row['total_revenue']); ?></strong></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td><strong>Total</strong></td>
                <td class="text-right"><strong><?php echo number_format($totalQuantity, 2); ?></strong></td>
                <td class="text-right"><strong><?php echo formatCurrency($totalRevenue); ?></strong></td>
            </tr>
        </tbody>
    </table>
    <?php
} elseif ($customType === 'slow_moving') {
    ?>
    <div class="section-title">Slow Moving Products</div>
    <table>
        <thead>
            <tr>
                <th>Product Name</th>
                <th>SKU</th>
                <th class="text-right">Current Stock</th>
                <th class="text-right">Quantity Sold (Last 90 Days)</th>
                <th class="text-right">Days Since Last Sale</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                <td><?php echo htmlspecialchars($row['sku']); ?></td>
                <td class="text-right"><?php echo number_format($row['current_stock'], 2); ?></td>
                <td class="text-right"><?php echo number_format($row['sold_quantity'], 2); ?></td>
                <td class="text-right"><?php echo number_format($row['days_since_last_sale'] ?? 0); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}
?>


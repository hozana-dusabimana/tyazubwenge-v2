<?php
if (empty($data) || !isset($data['total_revenue'])) {
    echo '<div class="section-title">No Data Available</div>';
    echo '<p>No financial data available for the selected period.</p>';
    exit;
}

$revenue = $data['total_revenue'] ?? 0;
$cost = $data['total_cost'] ?? 0;
$profit = $data['profit'] ?? 0;
$margin = $data['profit_margin'] ?? 0;
?>

<div class="summary-cards">
    <div class="summary-card success">
        <p>Total Revenue</p>
        <h3><?php echo formatCurrency($revenue); ?></h3>
    </div>
    <div class="summary-card danger">
        <p>Total Cost</p>
        <h3><?php echo formatCurrency($cost); ?></h3>
    </div>
    <div class="summary-card <?php echo $profit >= 0 ? 'success' : 'danger'; ?>">
        <p>Net Profit</p>
        <h3><?php echo formatCurrency($profit); ?></h3>
    </div>
    <div class="summary-card info">
        <p>Profit Margin</p>
        <h3><?php echo number_format($margin, 2); ?>%</h3>
    </div>
</div>

<div class="section-title">Profit & Loss Statement</div>

<table>
    <tbody>
        <tr>
            <td style="width: 30%;"><strong>Total Revenue</strong></td>
            <td class="text-right" style="width: 70%;"><?php echo formatCurrency($revenue); ?></td>
        </tr>
        <tr>
            <td><strong>Total Cost of Goods Sold</strong></td>
            <td class="text-right"><?php echo formatCurrency($cost); ?></td>
        </tr>
        <tr class="total-row">
            <td><strong>Net Profit</strong></td>
            <td class="text-right"><strong><?php echo formatCurrency($profit); ?></strong></td>
        </tr>
        <tr>
            <td><strong>Profit Margin</strong></td>
            <td class="text-right"><?php echo number_format($margin, 2); ?>%</td>
        </tr>
    </tbody>
</table>

<?php if ($revenue > 0): ?>
<div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
    <p><strong>Analysis:</strong></p>
    <p>
        <?php if ($profit >= 0): ?>
            The business is operating at a <strong>profit</strong> with a margin of <?php echo number_format($margin, 2); ?>%.
            This indicates healthy financial performance.
        <?php else: ?>
            The business is operating at a <strong>loss</strong> of <?php echo formatCurrency(abs($profit)); ?>.
            Immediate attention is required to improve profitability.
        <?php endif; ?>
    </p>
</div>
<?php endif; ?>


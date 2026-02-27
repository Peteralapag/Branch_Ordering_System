<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);    

if ($db->connect_error) die("Connection failed: " . $db->connect_error);

$status = $_POST['status'] ?? '';
$limit  = (int)($_POST['limit'] ?? 50);
$branch = $_SESSION['branch_branch'] ?? '';

// SQL with pre-aggregated received qty
$sql = "
SELECT 
    po.id,
    po.po_number,
    po.pr_number,
    po.supplier_id,
    po.source,
    s.name AS supplier_name,
    po.order_date,
    po.expected_delivery,
    SUM(poi.qty) AS total_ordered,
    IFNULL(SUM(pri.received_sum),0) AS total_received,
    (SUM(poi.qty) - IFNULL(SUM(pri.received_sum),0)) AS balance,
    po.status
FROM purchase_orders po
JOIN purchase_order_items poi ON po.id = poi.po_id
JOIN suppliers s ON po.supplier_id = s.id
LEFT JOIN (
    SELECT po_item_id, SUM(received_qty) AS received_sum
    FROM purchase_receipt_items
    GROUP BY po_item_id
) pri ON pri.po_item_id = poi.id
WHERE po.source = 'BRANCH' AND po.branch = ?
";

// Status filter
if($status !== ''){
    $sql .= " AND po.status = ?";
    $params = [$branch, $status];
    $types = "ss";
}else{
    $sql .= " AND po.status IN ('approved','partial_received')";
    $params = [$branch];
    $types = "s";
}

$sql .= " GROUP BY po.id ORDER BY po.order_date DESC LIMIT ?";
$params[] = $limit;
$types .= "i";

$stmt = $db->prepare($sql);
if(!empty($params)){
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$status_badge = [
    'approved' => 'badge bg-success-subtle text-success-emphasis border border-success-subtle',
    'partial_received' => 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle',
    'received' => 'badge bg-primary-subtle text-primary-emphasis border border-primary-subtle',
    'cancelled' => 'badge bg-danger-subtle text-danger-emphasis border border-danger-subtle'
];
?>

<style>
#potable{margin-bottom:0;}
#potable thead th{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;}
#potable tbody td{vertical-align:middle;}
#potable .status-text{text-transform:capitalize;}
#potable .qty-cell{text-align:right;font-variant-numeric:tabular-nums;}
#potable .action-cell{text-align:center;}
</style>

<table class="table table-bordered table-striped table-hover align-middle" id="potable">
<thead>
<tr>
    <th>#</th>
    <th>PR Number</th>
    <th>PO Number</th>
    <th>Supplier</th>
    <th>Order Date</th>
    <th>Expected Delivery</th>
    <th>Total Qty</th>
    <th>Received Qty</th>
    <th>Balance</th>
    <th>Status</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php 
$i = 1;
while($row = $result->fetch_assoc()): 

    // Update status permanently if balance = 0
    if(floatval($row['balance']) <= 0 && $row['status'] !== 'received'){
        $stmt_update = $db->prepare("UPDATE purchase_orders SET status='received', updated_at=NOW() WHERE id=?");
        $stmt_update->bind_param("i", $row['id']);
        $stmt_update->execute();
        $stmt_update->close();
        $row['status'] = 'received';
    }

?>
<tr>
    <td><?= htmlspecialchars($i) ?></td>
    <td><?= htmlspecialchars($row['pr_number']) ?></td>
    <td><?= htmlspecialchars($row['po_number']) ?></td>
    <td><?= htmlspecialchars($row['supplier_name']) ?></td>
    <td><?= htmlspecialchars(date('M d, Y', strtotime($row['order_date']))) ?></td>
    <td><?= htmlspecialchars(date('M d, Y', strtotime($row['expected_delivery']))) ?></td>
    <td class="qty-cell"><?= htmlspecialchars($row['total_ordered']) ?></td>
    <td class="qty-cell"><?= htmlspecialchars($row['total_received']) ?></td>
    <td class="qty-cell"><?= htmlspecialchars($row['balance']) ?></td>
    <td>
        <span class="<?= $status_badge[$row['status']] ?? 'badge bg-secondary' ?> status-text"><?= ucwords(str_replace('_',' ',$row['status'])) ?></span>
    </td>
    <td class="action-cell">
        <button class="btn btn-primary btn-sm"
            onclick="viewgr('<?= $row['id'] ?>','<?= $row['pr_number'] ?>','<?= $row['po_number'] ?>','<?= $row['supplier_name']?>','<?= $row['status'] ?>')">
            <i class="fa-solid fa-eye"></i> View
        </button>
    </td>
</tr>
<?php $i++; endwhile; ?>

<?php if($result->num_rows === 0): ?>
<tr>
    <td colspan="11" class="text-center text-muted">
        <i class="fa fa-info-circle"></i> No goods received found.
    </td>
</tr>
<?php endif; ?>
</tbody>
</table>



<script>

function viewgr(rowid, prnumber, ponumber, suppliername, status){
		
	$.post("./Modules/Branch_Ordering_System/includes/goods_received_view.php", { rowid: rowid, prnumber: prnumber, ponumber: ponumber, suppliername: suppliername, status: status },
	function(data) {
		$('#contents').html(data);
	});
}

</script>


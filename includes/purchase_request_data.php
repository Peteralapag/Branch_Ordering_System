<?php 
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);	

// Check connection
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$status = isset($_POST['status']) ? trim((string)$_POST['status']) : 'pending';
if ($status === '') {
	$status = 'pending';
}
$limit  = (int)($_POST['limit'] ?? 50);

// Fetch warehouse purchase requests
$sql = "SELECT * FROM purchase_request WHERE source='BRANCH'";
$params = [];
$types  = "";

// STATUS FILTER
if ($status !== '') {
    $sql .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

// ORDER + LIMIT
$sql .= " ORDER BY id DESC LIMIT ?";
$params[] = $limit;
$types .= "i";


// =======================
// EXECUTE
// =======================
$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}
$stmt->close();


$i = 0;

$status_badge = [
	'returned' => 'badge bg-danger-subtle text-danger-emphasis border border-danger-subtle',
	'pending' => 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle',
	'approved' => 'badge bg-success-subtle text-success-emphasis border border-success-subtle',
	'rejected' => 'badge bg-danger-subtle text-danger-emphasis border border-danger-subtle',
   	'for_canvassing' => 'badge bg-info-subtle text-info-emphasis border border-info-subtle',
   	'canvassing_reviewed' => 'badge bg-primary-subtle text-primary-emphasis border border-primary-subtle',
   	'canvassing_approved' => 'badge bg-success-subtle text-success-emphasis border border-success-subtle',
   	'for_canvassing_rejected' => 'badge bg-danger-subtle text-danger-emphasis border border-danger-subtle',
	'partial_conversion' => 'badge bg-primary-subtle text-primary-emphasis border border-primary-subtle',
	'partial_received' => 'badge bg-primary-subtle text-primary-emphasis border border-primary-subtle',
	'converted' => 'badge bg-success-subtle text-success-emphasis border border-success-subtle',
	'convert_rejected' => 'badge bg-danger-subtle text-danger-emphasis border border-danger-subtle',
];

?>

<style>
#purchaserequesttable{margin-bottom:0;}
#purchaserequesttable thead th{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;}
#purchaserequesttable tbody td{vertical-align:middle;}
#purchaserequesttable .status-text{text-transform:capitalize;}
#purchaserequesttable .btn{white-space:nowrap;}
</style>

<table class="table table-bordered table-striped table-hover align-middle" id="purchaserequesttable">
    <thead>
        <tr>
            <th>#</th>
            <th>PR Number</th>
            <th>Requested By</th>
            <th>Branch</th>
            <th>Destination</th>
            <th>Request Date</th>
            <th>Aging</th>
            <th>Remarks</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if(!empty($requests)): ?>
            <?php foreach($requests as $req): $i++;
            	
            		$prnumber = $req['pr_number'];
            		$status = $req['status'];
            		$remarks = $req['remarks'];
            		$destination = $req['destination_branch'];
            		$max = 15;
            		          		
					$statusbtn = 'primary';
					
					
					
					$today = new DateTime();

					$aging_allowed_status = [
					    'approved',
					    'for_canvassing',
					    'canvassing_reviewed',
					    'canvassing_approved',
					    'partial_conversion',
					    'partial_received'
					];
					
					$aging_display = '-';
					$aging_color = '#000';
					
					// check allowed status PER ROW
					if (in_array($status, $aging_allowed_status)) {
					
					    $closed_po = 0;
					
					    $poStmt = $db->prepare("
					        SELECT closed_po 
					        FROM purchase_orders 
					        WHERE pr_number = ? 
					        LIMIT 1
					    ");
					    $poStmt->bind_param("s", $prnumber);
					    $poStmt->execute();
					    $poStmt->bind_result($closed_po);
					    $poStmt->fetch();
					    $poStmt->close();
					
					    // walay PO or open pa
					    if ($closed_po == 0 && !empty($req['approved_at'])) {
					
					        $approved_date = new DateTime($req['approved_at']);
					        $aging_days = $approved_date->diff($today)->days;
					
					        $aging_display = $aging_days . ' day(s)';
					
					        if ($aging_days > 7) {
					            $aging_color = 'red';
					        } elseif ($aging_days > 3) {
					            $aging_color = 'orange';
					        } else {
					            $aging_color = 'green';
					        }
					    }
					}

            ?>
                <tr>
                    <td><?= htmlspecialchars($i) ?></td>
                    <td><?= htmlspecialchars($prnumber) ?></td>
                    <td><?= htmlspecialchars($req['requested_by']) ?></td>
                    <td><?= htmlspecialchars($req['source']) ?></td>
                    <td><?= htmlspecialchars($destination) ?></td>
					<td><?= date('M d, Y', strtotime($req['request_date'])) ?></td>
                    
					<td style="text-align:center;color:<?= htmlspecialchars($aging_color) ?>;font-weight:600;">
					    <?= $aging_display ?>
					</td>           
					         
					<td title="<?= htmlspecialchars($remarks) ?>"><?= htmlspecialchars(strlen($remarks) > $max ? substr($remarks, 0, $max) . '...' : $remarks)?></td>
                    <td>
	                    <span class="<?= $status_badge[$status] ?? 'badge bg-secondary' ?> status-text"><?= ucwords(str_replace('_', ' ', $status)) ?></span>
                    </td>

					<td style="text-align:center"><button type="button" onclick="vieviapr('<?= $prnumber?>','<?= $status?>','<?= $destination?>')" class="btn btn-<?= $statusbtn?> btn-sm"><i class="fa-solid fa-eye"></i> View</button></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8" class="text-center">No purchase requests found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<script>

function vieviapr(prnumber,status,destination) {

	
	$.post("./Modules/Branch_Ordering_System/includes/purchase_request_view.php", { prnumber: prnumber, status: status, destination: destination },
	function(data) {
		$('#contents').html(data);
	});

}

</script>

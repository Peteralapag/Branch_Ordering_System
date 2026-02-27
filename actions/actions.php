<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
require $_SERVER['DOCUMENT_ROOT']."/Modules/Branch_Ordering_System/class/Class.functions.php";
$function = new WMSFunctions;
if(isset($_POST['mode']))
{
	$mode = $_POST['mode'];
} else {
	print_r('
		<script>
			app_alert("Warning"," The Mode you are trying to pass does not exist","warning","Ok","","no");
		</script>
	');
	exit();
}
$date_user = date("Y-m-d H:i:s");
$trans_date = date("Y-m-d");

if(isset($_SESSION['branch_appnameuser']))
{
	$app_user = $_SESSION['branch_appnameuser'];
}


if ($mode === 'approvepurchaserequest') {

    $prnumber = trim($_POST['prnumber'] ?? '');
    $approver = trim($_SESSION['branch_appnameuser'] ?? '');

    if ($prnumber === '') {
        echo json_encode(['success'=>false,'message'=>'PR number is required']);
        exit;
    }

    if ($approver === '') {
        echo json_encode(['success'=>false,'message'=>'Approver not found']);
        exit;
    }

    // 1️CHECK PR
    $stmt = $db->prepare("
        SELECT id, status 
        FROM purchase_request 
        WHERE pr_number = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $prnumber);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        echo json_encode(['success'=>false,'message'=>'PR not found']);
        exit;
    }

    $pr = $res->fetch_assoc();
    $pr_id = $pr['id'];

    
    if ($pr['status'] !== 'pending' && $pr['status'] !== 'returned') {
	    echo json_encode([
	        'success' => false,
	        'message' => 'Only PENDING or RETURNED PR can be approved'
	    ]);
	    exit;
	}

    

    // 2️UPDATE PR (NOTE: approved_at)
    $stmt = $db->prepare("
        UPDATE purchase_request
        SET status = 'approved',
            approved_by = ?,
            approved_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("si", $approver, $pr_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        echo json_encode(['success'=>false,'message'=>'Nothing updated']);
        exit;
    }

    // 3️INSERT LOG
    // action_by is INT → use user ID or 0 if wala pa
    $user_id = $_SESSION['user_id'] ?? 0;

    $stmt = $db->prepare("
        INSERT INTO purchasing_logs
            (reference_type, reference_id, action, action_by, action_date)
        VALUES
            ('PR', ?, 'APPROVED', ?, NOW())
    ");
    $stmt->bind_param("is", $pr_id, $approver);
    $stmt->execute();

    echo json_encode([
        'success'=>true,
        'message'=>'Purchase Request approved successfully'
    ]);
    exit;
}


if ($mode === 'submitprform') {

    $prnumber = $_POST['prnumber'] ?? '';
    $isRevise = !empty($prnumber);

    $items       = $_POST['items'] ?? [];
    $grandTotal  = floatval($_POST['grandTotal'] ?? 0);
    $remarks     = $_POST['remarks'] ?? '';
    $date_time   = date("Y-m-d H:i:s"); 
    $user        = $_SESSION['branch_appnameuser'] ?? '';
    $destination_branch = $_POST['destination_branch'] ?? '';
	$branch = $_SESSION['branch_branch'] ?? '';

    if (empty($destination_branch)) {
        echo json_encode(['success'=>false,'message'=>'Destination Branch is required.']);
        exit;
    }

    if (empty($items)) {
        echo json_encode(['success'=>false,'message'=>'No items to submit.']);
        exit;
    }

    if (empty($remarks)) {
        echo json_encode(['success'=>false,'message'=>'No remarks.']);
        exit;
    }

    try {

        $db->begin_transaction();

        /*
        |--------------------------------------------------------------------------
        | ADD MODE (INSERT)  â€“ EXISTING BEHAVIOR
        |--------------------------------------------------------------------------
        */
        if (!$isRevise) {

            $pr_number = $function->generateUniquePrNumber($db);
            $source = 'BRANCH';
            $department = 'OPERATIONS';

            $stmt = $db->prepare("
                INSERT INTO purchase_request 
                (pr_number, request_date, source, department, destination_branch, requested_by, remarks, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "ssssssss",
                $pr_number,
                $date_time,
                $source,
                $department,
                $destination_branch,
                $user,
                $remarks,
                $date_time
            );

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            $pr_id = $db->insert_id;
            $stmt->close();
        }

        /*
        |--------------------------------------------------------------------------
        | REVISE MODE (UPDATE)
        |--------------------------------------------------------------------------
        */
        else {

            // get PR id + status
            $stmt = $db->prepare("SELECT id, status FROM purchase_request WHERE pr_number=?");
            $stmt->bind_param("s", $prnumber);
            $stmt->execute();
            $stmt->bind_result($pr_id, $pr_status);
            $stmt->fetch();
            $stmt->close();

            if (!$pr_id) {
                throw new Exception("PR not found.");
            }

            $allowed_status = ['pending', 'returned'];
			if (!in_array($pr_status, $allowed_status)) {
			    throw new Exception("Only pending or returned PR can be revised.");
			}

            // update header only
            $stmt = $db->prepare("
                UPDATE purchase_request
                SET destination_branch = ?,
                    remarks = ?,
                    updated_at = ?
                WHERE id = ?
            ");
            $stmt->bind_param(
                "sssi",
                $destination_branch,
                $remarks,
                $date_time,
                $pr_id
            );

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }
            $stmt->close();

            // remove old items
            $stmt = $db->prepare("DELETE FROM purchase_request_items WHERE pr_id=?");
            $stmt->bind_param("i", $pr_id);
            $stmt->execute();
            $stmt->close();

            $pr_number = $prnumber; // return same PR number
        }

        /*
        |--------------------------------------------------------------------------
        | INSERT ITEMS (USED BY BOTH ADD & REVISE)
        |--------------------------------------------------------------------------
        */
        $stmt2 = $db->prepare("
            INSERT INTO purchase_request_items
            (pr_id, item_type, item_code, item_description, quantity, unit, estimated_cost, total_estimated)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($items as $item) {

            $item_id   = intval($item['item_id']);
            $item_type = $item['item_type'];
            $qty       = floatval($item['qty']);
            $cost      = floatval($item['cost']);
            $total     = floatval($item['total']);

            // trusted item data
            $res = $db->prepare("
                SELECT item_code, item_description, uom
                FROM wms_itemlist
                WHERE id = ?
                LIMIT 1
            ");
            $res->bind_param("i", $item_id);
            $res->execute();
            $res->bind_result($item_code, $item_description, $unit);

            if (!$res->fetch()) {
                throw new Exception("Item ID {$item_id} not found.");
            }
            $res->close();

            $stmt2->bind_param(
                "isssdsdd",
                $pr_id,
                $item_type,
                $item_code,
                $item_description,
                $qty,
                $unit,
                $cost,
                $total
            );

            if (!$stmt2->execute()) {
                throw new Exception($stmt2->error);
            }
        }

        $stmt2->close();

        $db->commit();

        echo json_encode([
            'success'   => true,
            'pr_number' => $pr_number
        ]);

    } catch (Exception $e) {

        $db->rollback();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }

    exit;
}


if($mode == 'getiteminformation')
{
	$item_code = $_POST['item_code'];
	$QUERY = "SELECT * FROM wms_itemlist WHERE item_code='$item_code'";
	$RESULTS = mysqli_query($db, $QUERY);    
	if ( $RESULTS->num_rows > 0 ) 
	{
		while($ROW = mysqli_fetch_array($RESULTS))  
		{
			$item = $ROW['item_description'];
			$item_code = $ROW['item_code'];
			$uom = $ROW['uom'];					
			$unit_price = $ROW['unit_price'];
			echo '
				<script>
					$("#item_description").val("' . $item . '");
					$("#item_code").val("' . $item_code . '");
					$("#uom").val("' . $uom . '");
					$("#unit_price").val("' . $unit_price . '");
					$("#inv_ending_uom").val("' . $uom . '");
					document.getElementById("quantity").focus();					
				</script>					
			';
		}
	} else {
		echo "";
	}
	mysqli_close($db);
}
if($mode == 'voidorderrequest')
{
	$rowid = $_POST['rowid'];
	$queryDataUpdate = "UPDATE wms_order_request SET status='Void' WHERE request_id='$rowid'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		print_r('
			<script>
				swal("Success", "Order request has been Void", "success");
				$("#" + sessionStorage.navwmsbos).trigger("click");
				$("#formmodal").fadeOut();
			</script>
		');		
	} else {
		print_r('
			<script>
				swal("Warning", "'.$db->error.'", "warning");
			</script>
		');
	}
	mysqli_close($db);
}
if($mode == 'sendingbackrequest')
{
	$control_no = $_POST['control_no'];
	$queryDataUpdate = "UPDATE wms_order_request SET status='Open', checked=NULL, checked_by=NULL, checked_date=NULL WHERE control_no='$control_no'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		print_r('
			<script>
				swal("Success", "Order request has been sent back to Requester", "success");
				$("#" + sessionStorage.navwmsbos).trigger("click");
				$("#formmodal").fadeOut();
			</script>
		');		
	} else {
		print_r('
			<script>
				swal("Warning", "'.$db->error.'", "warning");
			</script>
		');
	}
	mysqli_close($db);
}
if($mode == 'getformtype')
{
	$form_type = $_POST['formType'];
	echo $function->LoadRecipient($form_type,$db);
}
if($mode == 'itemreceiptsave')
{
	$rowid = $_POST['rowid'];
	$actual_qty = $_POST['actual_qty'];
	$queryDataUpdate = "UPDATE wms_branch_order SET item_receipt_by='$app_user', item_receipt=1, actual_quantity='$actual_qty', item_receipt_date='$date_user' WHERE id='$rowid'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		echo '
			<script>
				reloaderKo();
			</script>
		';
	} else {
		print_r('
			<script>
				swal("Warning", "'.$db->error.'", "warning");
			</script>
		');
	}
	mysqli_close($db);
}
if($mode == 'recieveddelivery')
{
	$control_no = $_POST['control_no'];
	$queryDataUpdate = "UPDATE wms_order_request SET order_accepted_by='$app_user', order_delivered=1, order_delivered_date='$date_user', status='Closed' WHERE control_no='$control_no'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		print_r('
			<script>
				swal("Success", "You have Received the Deliveries.", "success");
				$("#" + sessionStorage.navwmsbos).trigger("click");
				reloaderKo();			
			</script>
		');		
	} else {
		print_r('
			<script>
				swal("Warning", "'.$db->error.'", "warning");
			</script>
		');
	}
	mysqli_close($db);
}
if($mode == 'approveorder')
{
	$control_no = $_POST['control_no'];
	$order_type = $_POST['order_type'];
	$queryDataUpdate = "UPDATE wms_order_request SET status='Submitted', approved='Approved', approved_by='$app_user', approved_date='$trans_date' WHERE control_no='$control_no'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		print_r('
			<script>
				var controlno = "'.$control_no.'";
				var ordertype = "'.$order_type.'";
				swal("Success", "You have checked and review this Order", "success");
				orderApproval(controlno,ordertype);
				$("#" + sessionStorage.navwmsbos).trigger("click");
			</script>
		');		
	} else {
		print_r('
			<script>
				swal("Warning", "'.$db->error.'", "warning");
			</script>
		');
	}
	mysqli_close($db);
}
if($mode == 'approvecheckreview')
{
	$control_no = $_POST['control_no'];
	$order_type = $_POST['order_type'];
	$queryDataUpdate = "UPDATE wms_order_request SET checked='Approved', checked_by='$app_user', checked_date='$trans_date' WHERE control_no='$control_no'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		print_r('
			<script>
				var controlno = "'.$control_no.'";
				var ordertype = "'.$order_type.'";
				swal("Success", "You have checked and review this Order", "success");
				orderApproval(controlno,ordertype);
			</script>
		');		
	} else {
		print_r('
			<script>
				swal("Warning", "'.$db->error.'", "warning");
			</script>
		');
	}
	mysqli_close($db);
}
if($mode == 'submitorderunlisted')
{
	$control_no = $_POST['control_no'];
	$queryDataUpdate = "UPDATE wms_order_request SET status='Approval' WHERE control_no='$control_no'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		print_r('
			<script>				
				swal("Success", "Order has been submitted for approval", "success");
				$("#" + sessionStorage.navwmsbos).trigger("click");				
			</script>
		');		
	} else {
		print_r('
			<script>
				swal("Warning", "'.$db->error.'", "warning");
			</script>
		');
	}
	mysqli_close($db);
}
if($mode == 'submitorder')
{
	$control_no = $_POST['control_no'];
	$queryDataUpdate = "UPDATE wms_order_request SET status='Approval' WHERE control_no='$control_no'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		echo '
			<script>
				swal("Success", "Order has been submitted for approval", "success");
				$("#" + sessionStorage.navwmsbos).trigger("click");
				$("#formmodal").fadeOut();
			</script>
		';		
	} else {
		echo '
			<script>
				swal("Warning", "'.$db->error.'", "warning");
			</script>
		';
	}
	mysqli_close($db);
}
if($mode == 'deleteorderitem')
{
	$rowid = $_POST['editid'];
	$control_no = $_POST['control_no'];
	$queryDataDelete = "DELETE FROM wms_branch_order WHERE id='$rowid' ";
	if ($db->query($queryDataDelete) === TRUE)
	{ 
		print_r('
			<script>
				var controlno = "'.$control_no.'";
				swal("Success","Item has been removed", "success");
				loadWarehouse("new","");
			</script>
		');
	} else {
		print_r('
			<script>
				swal("Warning", "'.$db->error.'", "warning");
			</script>
		');
	}
	mysqli_close($db);
}
if($mode == 'displaybranch')
{
	
	if(isset($_POST['search']))
	{
		$search = $_POST['search'];
		$q = "WHERE branch LIKE '%$search%'";
	} else {
		$q = "";
	}
	$sqlQuery = "SELECT * FROM tbl_branch $q LIMIT 100";
	$results = mysqli_query($db, $sqlQuery);    
	echo '<ul class="searchlist">';
    if ( $results->num_rows > 0 ) 
    {
    	while($ITEMSROW = mysqli_fetch_array($results))  
		{
			$branch = $ITEMSROW['branch'];
			
?>
			<li onclick="setSearch('<?php echo $branch; ?>')"><?php echo $branch; ?></li>
<?php
		}
	} else {
		echo "<li>No Record.</li>";
	}
	
	mysqli_close($db);
}
if($mode == 'recevingstocks')
{
	$recipient = $_POST['recipient'];
	if(isset($_POST['search']))
	{
		$search = $_POST['search'];
		$q = "WHERE recipient='$recipient' AND active=1 AND item_description LIKE '%$search%' OR item_code LIKE '%$search%' OR qr_code LIKE '%$search%'";
	} else {
		$q = "WHERE recipient='$recipient' AND active=1";
	}
	$sqlQuery = "SELECT * FROM wms_itemlist $q LIMIT 100";
	$results = mysqli_query($db, $sqlQuery);    
	echo '<ul class="searchlist">';
    if ( $results->num_rows > 0 ) 
    {
    	while($ITEMSROW = mysqli_fetch_array($results))  
		{
			$item_code = $ITEMSROW['item_code'];
			$item = $ITEMSROW['item_description'];
			$uom = $ITEMSROW['uom'];	
			$unitprice = $ITEMSROW['unit_price'];	
			$supplier_id = $ITEMSROW['supplier_id'];	
			
?>
			<li onclick="setSearch('<?php echo $item; ?>','<?php echo $item_code; ?>','<?php echo $unitprice; ?>','<?php echo $uom; ?>')"><?php echo $item; ?></li>
<?php
		}
	} else {
		echo "<li>No Record.</li>";
	}
	mysqli_close($db);
}
?>
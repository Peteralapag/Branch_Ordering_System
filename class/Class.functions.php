<?php
class WMSFunctions
{

	public function generateUniquePrNumber($db)
	{
	    $datePrefix = date('Ymd');
	    $source = 'BR';
	
	    try {
	        $db->begin_transaction();
	
	        $sql = "SELECT pr_number
	                FROM purchase_request
	                WHERE pr_number LIKE ?
	                ORDER BY pr_number DESC
	                LIMIT 1
	                FOR UPDATE";
	
	        $like = "$source-$datePrefix-%";
	        $stmt = $db->prepare($sql);
	        $stmt->bind_param("s", $like);
	        $stmt->execute();
	        $stmt->bind_result($lastPr);
	        $stmt->fetch();
	        $stmt->close();
	
	        $lastNumber = $lastPr
	            ? (int) substr($lastPr, -4)
	            : 0;
	
	        $newNumber = $lastNumber + 1;
	
	        $newPr = "$source-$datePrefix-" . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
	
	        $db->commit();
	        return $newPr;
	
	    } catch (Exception $e) {
	        $db->rollback();
	        throw $e;
	    }
	}

	public function GetMyRealName($username,$db)
	{
		$sqlLogin = "SELECT * FROM tbl_system_user WHERE username='$username'";
		$result = mysqli_query($db, $sqlLogin);    
	    while($listrow = mysqli_fetch_array($result))  
		{
			return $listrow['firstname']." ".$listrow['lastname'];
		}
	}
	public function GetOrderCreatorName($control_no,$db)
	{
		$QUERY = "SELECT * FROM wms_order_request WHERE control_no='$control_no'";
        $RESULTS = mysqli_query($db, $QUERY);    
		
		if ($RESULTS) {
	   		$row = mysqli_fetch_assoc($RESULTS);
			return $row['created_by'];
		} else {
			return "No results found";
		}

	}
	public function GetOrderStatusData($status,$db)
	{
		$ot = array(
			"Open" => "Open",
	        "Closed" => "Closed",
	        "Approval" => "Approval",
	        "Submitted" => "Submitted",
            "In-Transit" => 'In-Transit',            
            "Void" => 'Void'
        );
        $return = "";
        foreach ( $ot as $key => $value )
        {
        	$selected = "";
        	if($value == $status)
        	{
        		$selected = "selected";
        	}
            $return .= '<option '.$selected.' value="'.$value.'">'.$key.'</option>';
        }
        return $return;
	}
	public function GetOrderType($order_type)
	{
		$ot = array(
	        "Listed Items" => 0,
            "Unlisted Items" => 1
        );
        $return = "";
        foreach ( $ot as $key => $value )
        {
        	$selected = "";
        	if($value == $order_type)
        	{
        		$selected = "selected";
        	}
            $return .= '<option '.$selected.' value="'.$value.'">'.$key.'</option>';
        }
        return $return;
	}
	public function getSubmittedOrder($branch,$db)
	{
		$QUERY = "SELECT COUNT(request_id) AS status_count FROM wms_order_request WHERE $branch status = 'Submitted'";
		$RESULTS = mysqli_query($db, $QUERY);    
		if ($RESULTS) {
	   		$row = mysqli_fetch_assoc($RESULTS);
			return $row['status_count'];
		} else {
			return 0;
		}
	}
	public function getForApproval($branch,$db)
	{
		$QUERY = "SELECT COUNT(request_id) AS status_count FROM wms_order_request WHERE $branch status = 'Approval'";
		$RESULTS = mysqli_query($db, $QUERY);    
		if ($RESULTS) {
	   		$row = mysqli_fetch_assoc($RESULTS);
			return $row['status_count'];
		} else {
			return 0;
		}
	}
	public function getOpenOrders($kwiri,$db)
	{
		$QUERY = "SELECT COUNT(request_id) AS status_count FROM wms_order_request WHERE $branch status = 'Open'";
		$RESULTS = mysqli_query($db, $QUERY);    
		if ($RESULTS) {
	   		$row = mysqli_fetch_assoc($RESULTS);
			return $row['status_count'];
		} else {
			return 0;
		}
	}
	/* ###################### TESTING #####################
	public function getPendingOrder($kwiri,$db)
	{
		$QUERY = "SELECT COUNT(*) AS status_count 
        FROM wms_order_request 
        WHERE $kwiri 
        status = 'In-Transit' 
        AND delivery_date < CURDATE() 
        AND YEAR(delivery_date) >= 2025";
        $RESULTS = mysqli_query($db, $QUERY);    
		
		if ($RESULTS) {
	   		$row = mysqli_fetch_assoc($RESULTS);
			return $row['status_count'];
		} else {
			return 0;
		}
	}
	###################### TESTING ##################### */
	public function getPendingOrder($kwiri,$db)
	{
		$QUERY = "SELECT COUNT(*) AS status_count FROM wms_order_request WHERE $kwiri status = 'In-Transit'";
        $RESULTS = mysqli_query($db, $QUERY);    
		
		if ($RESULTS) {
	   		$row = mysqli_fetch_assoc($RESULTS);
			return $row['status_count'];
		} else {
			return 0;
		}
	}
	public function getOrderCreator($creator,$control_no,$db)
	{
		$QUERY = "SELECT * FROM wms_order_request WHERE control_no='$control_no' AND created_by='$creator'";
		$RESULTS = mysqli_query($db, $QUERY);    
		if ( $RESULTS->num_rows > 0 ) 
		{
			return 1;
		} else {
			return 0;
		}
	}
	public function GetItemStock($itemcode,$db)
	{
		$QUERY = "SELECT * FROM wms_inventory_stock WHERE item_code='$itemcode'";
		$RESULTS = mysqli_query($db, $QUERY);    
		if ( $RESULTS->num_rows > 0 ) 
		{
			while($ROW = mysqli_fetch_array($RESULTS))  
			{
				$return = $ROW['stock_in_hand'];
			}
			return $return;
		} else {
			return 0;
		}
	}
	public function LoadBranch($cluster,$db)
	{
		$QUERY = "SELECT * FROM tbl_branch WHERE location='$cluster'";
		$RESULTS = mysqli_query($db, $QUERY);    
		if ( $RESULTS->num_rows > 0 ) 
		{
			$return = '<option value=""> --- SELECT BRANCH --- </option>';
			while($ROW = mysqli_fetch_array($RESULTS))  
			{
				$branch = $ROW['branch'];
				$return .= '<option value="'.$branch.'">'.$branch.'</option>';
			}
			return $return;
		} else {
			return '<option value="">--- No Branch ---</option>';
		}
	}
	public function GetOrderStatus($control_no,$column,$db)
	{
		$query = "SELECT * FROM wms_order_request WHERE control_no='$control_no'";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
		    while($ROW = mysqli_fetch_array($results))  
			{
				$retVal = $ROW[$column];
			}
			return $retVal;
		} 
	}

	public function GetChecked($control_no,$column,$db)
	{
		$query = "SELECT * FROM wms_order_request WHERE control_no='$control_no'";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
		    while($ROW = mysqli_fetch_array($results))  
			{
				$retVal = $ROW[$column];
			}
			return $retVal;
		}
	}
	public function GetControlNo($branch,$db)
	{
		$query = "SELECT * FROM wms_order_request WHERE branch='$branch'";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			$return = "";
		    while($ROW = mysqli_fetch_array($results))  
			{
				$controlno = $ROW['control_no'];
				$form_type = $ROW['form_type'];
				$return .= '<option value="'.$controlno.'">'.$controlno.' - '.$form_type.'</option>';
			}
			return $return;
		} else {
			return '<option value="">--- Recipient Not Configured ---</option>';
		}
	}
	public function GetRequestType($form_type)
	{
		$rt = array(
	        "MRS" => "MRS",
            "POF" => "POF"
        );
        $return = "";
        foreach ( $rt as $key => $value )
        {
        	$selected = "";
        	if($value == $form_type)
        	{
        		$selected = "selected";
        	}
            $return .= '<option '.$selected.' value="'.$value.'">'.$key.'</option>';
        }
        return $return;
	}
	public function getOrderRemarksExistence($control_no,$db)
	{
		$QUERY = "SELECT * FROM wms_branch_order_remarks WHERE control_no='$control_no'";
		$RESULTS = mysqli_query($db, $QUERY);    
		if ( $RESULTS->num_rows > 0 ) 
		{
			return 'edit';
		} else {
			return "add";
		}
	}
	public function getOrderRemarks($mrs_no,$db)
	{
		$QUERY = "SELECT * FROM wms_branch_order_remarks WHERE control_no='$mrs_no'";
		$RESULTS = mysqli_query($db, $QUERY);    
		if ( $RESULTS->num_rows > 0 ) 
		{
			while($ROW = mysqli_fetch_array($RESULTS))  
			{
				return $ROW['remarks'];
			}
		} else {
			return "";
		}
	}
	public function shortenText($text, $maxLength)
	{
		if (strlen($text) <= $maxLength)
		{
			return $text;
		} else {
		    $shortenedText = substr($text, 0, $maxLength);
		    $shortenedText .= '...';
		    return $shortenedText;
		}
	}
	public function GetRequestStatus($status)
	{
		$stats = array(
	        "Open" => "Open",
            "Closed" => "Closed",
            "Cancel" => "Cancel"
        );
        $return = "";
        foreach ( $stats as $key => $value )
        {
        	$selected = "";
        	if($value == $status)
        	{
        		$selected = "selected";
        	}
            $return .= '<option '.$selected.' value="'.$value.'">'.$key.'</option>';                        
        }
        return $return;
	}
	public function GetPriority($priorititi)
	{
		$return = '';
        $priority = array(
	        "Normal" => "Normal",
            "Urgent" => "Urgent"                        
        );
        foreach ( $priority as $key => $value )
        {
        	$selected = "";
        	if($value == $priorititi)
        	{
        		$selected = "selected";
        	}
            $return .= '<option value="'.$value.'" '.$selected.'>'.$key.'</option>';                        
        }
        return $return;
	}
	public function LoadRecipient($form_type,$db)
	{
		$query = "SELECT * FROM wms_recipient WHERE form_type='$form_type'";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
//			$return = '<option value="">--- SELECT RECIPIENT ---</option>';
			$return = '';
		    while($ROW = mysqli_fetch_array($results))  
			{
				$homopient = $ROW['recipient'];
				$selected = '';
				if($homopient == $form_type)
				{
					$selected = "selected";
				}
				$return .= '<option '.$selected.' value="'.$homopient.'">'.$homopient.'</option>';
			}
			return $return;
		} else {
			return '<option value="">--- Recipient Not Configured ---</option>';
		} 
	}	
	public function GetRecipient($recipient, $form_type, $db)
	{
	    $query = "SELECT * FROM wms_recipient WHERE form_type='$form_type' AND active=1";
	    $results = mysqli_query($db, $query);
	
	    if ($results->num_rows > 0) {
	        $return = '';
	        while ($ROW = mysqli_fetch_array($results)) {
	            $homopient = trim($ROW['recipient']);
	            $selected = ($homopient === trim($recipient)) ? 'selected="selected"' : '';	
	            $return .= '<option value="' . $homopient . '" ' . $selected . '>' . $homopient . '</option>';
	        }
	        return $return;
	    } else {
	        return '<option value="">--- Recipient Not Configured ---</option>';
	    }
	}
	public function GetMRSNumber($db)
	{
		$query = "SELECT * FROM wms_form_numbering WHERE id=1";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
		    while($ROW = mysqli_fetch_array($results))  
			{
				$numbering = $ROW['mrs_number'];
			}
			return $numbering;
		} else {
			return 'Numbering is not configured';
		} 
	}
	public function GetTransType($transtype)
	{
        $transaction = array(
	        "Receiving" => "Receiving",
            "Return" => "Return"                        
        );
        foreach ( $transaction as $key => $value )
        {
        	$selected = "";
        	if($value == $transtype)
        	{
        		$selected = "selected";
        	}
            $return .= '<option '.$selected.' value="'.$value.'">'.$key.'</option>';                        
        }
        return $return;
	}

	public function GetDeliverytatust($status)
	{
        $delstatus = array(
	        "Full" => "Full",
            "Partial" => "Partial"                        
        );
        foreach ( $delstatus as $key => $value )
        {
            $return .= '<option value="'.$value.'">'.$key.'</option>';                        
        }
        return $return;
	}
	public function GetOrderStatust($status)
	{
        $ordstatus = array(
	        "Open" => "Open",
            "Closed" => "Closed"                        
        );
        foreach ( $ordstatus as $key => $value )
        {
            $return .= '<option value="'.$value.'">'.$key.'</option>';                        
        }
        return $return;
	}
	public function GetSupplierName($supplier_id,$db)
	{
		$query = "SELECT * FROM wms_supplier WHERE id='$supplier_id'";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
		    while($ROW = mysqli_fetch_array($results))  
			{
				$name = $ROW['name'];
			}
			return $name;
		} else {
			return 'Supplier not exists';
		} 
	}
	public function GetSupplier($supplier_id,$db)
	{
		$query = "SELECT * FROM wms_supplier";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			$return = '<option value="">--- SELECT SUPPLIER ---</option>';
		    while($ROW = mysqli_fetch_array($results))  
			{
				$name = $ROW['name'];
				$value = $ROW['id'];
				$selected = '';
				if($value == $supplier_id)
				{
					$selected = "selected";
				}
				$return .= '<option '.$selected.' value="'.$value.'">'.$name.'</option>';
			}
			return $return;
		} else {
			return '<option value="">--- NO SUPPLIER ---</option>';
		} 
	}
	public function GetUOM($uom,$db)
	{
		$query = "SELECT * FROM wms_units_measures";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			$return = '<option value="">- UOM -</option>';
		    while($ROW = mysqli_fetch_array($results))  
			{
				$units = $ROW['unit_name'];
				$selected = '';
				if($units == $uom)
				{
					$selected = "selected";
				}
				$return .= '<option '.$selected.' value="'.$units.'">'.$units.'</option>';
			}
			return $return;
		} else {
			return '<option value="">--- NO UOM ---</option>';
		} 
	}
	public function GetItemCategory($cat,$db)
	{
		$query = "SELECT * FROM wms_item_category WHERE active=1";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			$return = '<option value="">--- SELECT CATEGORY ---</option>';
		    while($ROW = mysqli_fetch_array($results))  
			{
				$category = $ROW['category'];
				$selected = '';
				if($category == $cat)
				{
					$selected = "selected";
				}
				$return .= '<option '.$selected.' value="'.$category.'">'.$category.'</option>';
			}
			return $return;
		} else {
			return '<option value="">--- NO CATEGORY ---</option>';
		} 
	}
	public function GetRowLimit($showlimit)
	{
        $limit = array(            
	        "25" => "25",            
            "50" => "50",            
            "100" => "100",
			"250" => "250",
			"500" => "500",
			"1000" => "1000"
        );
        $return = '';
        foreach ( $limit as $key => $value )
        {
        	$selected = "";
        	if($value == $showlimit)
        	{
        		$selected = "selected";
        	}
            $return .= '<option '.$selected.' value="'.$value.'">'.$key.'</option>';                        
        }
        return $return;
	}
}
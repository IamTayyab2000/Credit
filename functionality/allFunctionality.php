<?php
# I know this is not a good move but i want it to work fastly
# This is the most Important document after database... So Dont ever Delete it
# Yes! You read the file name correctly this file will contain all the functionality to this aplication
session_start();
include_once('components/crud.php');
switch ($function_to_call) {
    case 'login':
        $username = postData('username');
        $password = postData('password');
        $responce = (int) auth($username, $password, 'admin');
        if ($responce == 1) {
            $_SESSION['admin'] = $username;
            echo 1;
        } else {
            echo $responce;
        }
        break;
    case 'update_customer_info':
        $id = postData('id');
        $name = postData('name');
        $query = "update customer set customer_name='{$name}' where customer_id='{$id}'";
        $responce = create_update_delete($query);
        if ($responce) {
            echo '1';
        } else {
            echo $responce;
        }
        break;
    case 'Load_customer_table':
        $query = "select a.customer_id,a.customer_name,b.sector_name,b.saleman_name from customer as a left join route_saleman_relation b on a.customer_route=b.route_id";
        $responce = read($query);
        //echo $query;
        echo $responce;
        break;
    case 'save_saleman_data':
        $saleman_name = postData('saleman_name');
        $query = "INSERT INTO `salesman`(`saleman_name`) VALUES ('{$saleman_name}')";
        $responce = create_update_delete($query);
        if ($responce) {
            echo 1;
        } else {
            echo $responce;
        }
        break;
    case 'load_saleman_data':
        $query = "SELECT `saleman_id` As 'ID', `saleman_name` AS 'NAME' FROM `salesman`";
        $responce = read($query);
        echo $responce;
        break;
    case 'load_sectors_options':
        $query = "Select sector_id As 'id',sector_name As 'text' from sector";
        $responce = read($query);
        echo $responce;
        break;
    case "load_sector_data":
        $query = "SELECT `sector_id` AS 'ID', `sector_name` AS 'NAME' FROM sector";
        $responce = read($query);
        echo $responce;
        break;
    case "load_route_data":
        $query = "SELECT s.sector_name As `sector_name`, m.saleman_name As 'saleman_name',t.day As 'day'
        FROM routes AS t
        LEFT JOIN sector AS s ON t.sector_id = s.sector_id
        LEFT JOIN salesman AS m ON t.saleman_id = m.saleman_id";
        $responce = read($query);
        echo $responce;
        break;
    case 'save_sector_data':
        $sector_name = postData('sector_name');
        $query = "INSERT into sector (sector_name) values ('{$sector_name}')";
        //echo $query;
        $responce = create_update_delete($query);
        if ($responce) {
            echo 1;
        } else {
            echo $responce;
        }
        break;
    case 'save_route_data':
        $sector_id = postData('sector_id');
        $saleman_id = postData('saleman_id');
        $day = postData('day');
        $query = "Select Count(*) As 'Existence' from routes where sector_id={$sector_id}";
        $responce = json_decode(read($query));
        if ((int) ($responce[0]->Existence)) {
            $query = "Update routes set saleman_id={$saleman_id},day='{$day}' where sector_id={$sector_id}";
            $responce = create_update_delete($query);
            if ($responce) {
                echo 2;
            } else {
                echo $responce;
            }
        } else {
            $query = "Insert into routes (sector_id,saleman_id,day) values('{$sector_id}','{$saleman_id}','{$day}')";
            // echo $query;
            $responce = create_update_delete($query);
            if ($responce) {
                echo 1;
            } else {
                echo $responce;
            }
        }
        break;
    case 'update_sector':
        $selected_sector = postData('selected_sector');
        $updated_sector_name = postData('updated_sector_name');
        $query = "Update sector set sector_name='{$updated_sector_name}' where sector_id={$selected_sector}";
        //echo $query;
        $responce = create_update_delete($query);
        if ($responce) {
            echo 1;
        } else {
            echo $responce;
        }
        break;
    case 'save_customer_data':
        $customer_id = postData('customer_id');
        $customer_name = postData('customer_name');
        $customer_route_id = postData('customer_route_id');
        $query = "INSERT INTO `customer` (`customer_id`, `customer_name`, `customer_route`) VALUES ('{$customer_id}', '{$customer_name}', (SELECT `route_id` FROM `routes` WHERE `sector_id` = '{$customer_route_id}'))";
        
        $responce = create_update_delete($query);
        if ($responce) {
            echo 1;
        } else {
            echo $query;
        }
        break;
    case 'Load_customer_table_by_saleman':
        $saleman_id = postData('saleman_id');
        $query = "SELECT customer_id,customer_name from customer where customer_route in (SELECT route_id from routes)";
        // echo $query;
        $responce = read($query);
        echo $responce;
        break;
    case 'store_bill_data':
        $values = "";
        $query = 'INSERT INTO `bill`(`bill_id`, `cutomer_id`, `picklist_id`, `bill_amount`, `bill_date`) VALUES';
        $customer_bills = postData('customer_bills');

        if (!empty($customer_bills)) { // Check if the JSON data is not empty
            $customer_bills = json_decode(urldecode($customer_bills), true); // Decode as an associative array

            if (is_array($customer_bills)) { // Check if JSON decoding was successful
                foreach ($customer_bills as $bill) {
                    $customer_id = $bill['customer_id'];
                    $credit_amount = $bill['credit_amount'];
                    $invoice_date = $bill['invoice_date'];
                    $invoice_id = $bill['invoice_id'];
                    $picklist_id = $bill['picklist_id'];

                    // Concatenate the values for the INSERT statement
                    $values .= "('$invoice_id', '$customer_id', '$picklist_id', '$credit_amount', '$invoice_date'),";
                }

                // Remove the trailing comma from $values
                $values = rtrim($values, ',');

                // Combine the query and values
                $query .= $values;
                $responce = create_update_delete($query);
                if ($responce == 1) {
                    echo 1;
                } else {
                    echo 0;
                }
                // echo 'store_bill_data:/n'.$query.'/n';
            } else {
                // Handle JSON decoding error
                echo "Error: Failed to decode JSON data.";
            }
        } else {
            // Handle case where $customer_bills is empty or null
            echo "Error: JSON data is empty or null.";
        }

        break;
    case 'store_data_picklist':
        $picklist_id = postData('picklist_id');
        $picklist_saleman=postData('picklist_saleman');
        $picklist_date = postData('picklist_date');
        $picklist_amount = postData('picklist_amount');
        $picklist_recovery = postData('picklist_recovery');
        $picklist_credit = postData('picklist_credit');
        $picklist_scheme = postData('picklist_scheme');
        $picklist_return = postData('picklist_return');
        $query = "INSERT INTO `picklist`(`picklist_id`,`picklist_saleman`, `picklist_date`, `picklist_amount`, `picklist_recovery`, `picklist_credit`, `picklist_sceheme_amount`, `picklist_return`,`picklist_date_processed`) VALUES ('{$picklist_id}','$picklist_saleman','{$picklist_date}','{$picklist_amount}','{$picklist_recovery}','{$picklist_credit}','{$picklist_scheme}','{$picklist_return}',CURRENT_DATE)";
        $responce = create_update_delete($query);
        if ($responce) {
            echo 1;
        } else {
            echo $responce;
        }
        break;
    case 'store_bill_ledger':
        $values = "";
        $query = "INSERT INTO `bill_ledger`(`ledger_date`, `customer_id`, `bill_id`, `bill_amount`, `recived_amount`, `remaining_amount`) VALUES";
        $customer_bills = postData('customer_bills');

        if (!empty($customer_bills)) { // Check if the JSON data is not empty
            $customer_bills = json_decode(urldecode($customer_bills), true); // Decode as an associative array

            if (is_array($customer_bills)) {
                foreach ($customer_bills as $bill) {
                    $customer_id = $bill['customer_id'];
                    $credit_amount = $bill['credit_amount'];
                    $invoice_date = $bill['invoice_date'];
                    $invoice_id = $bill['invoice_id'];
                    $picklist_id = $bill['picklist_id'];

                    // Concatenate the values for the INSERT statement
                    $values .= "('{$invoice_date}','{$customer_id}','{$invoice_id}',{$credit_amount},0,bill_amount-recived_amount),";
                }
                // Remove the trailing comma from $values
                $values = rtrim($values, ',');

                // Combine the query and values
                $query .= $values;

                //echo 'store_bill_ledger:\n'.$query.'\n';
                $responce = create_update_delete($query);
                if ($responce) {
                    echo 1;
                } else {
                    echo $responce;
                }
            } else {
                // Handle JSON decoding error
                echo "Error: Failed to decode JSON data.";
            }
        } else {
            // Handle case where $customer_bills is empty or null
            echo "Error: JSON data is empty or null.";
        }
        break;
    case 'getBillsData':
        $query = "SELECT a.bill_id,b.customer_name,d.saleman_name,e.sector_name,a.bill_date,a.bill_amount from bill as a join customer as b on a.cutomer_id=b.customer_id join picklist as c on a.picklist_id=c.picklist_id join salesman as d on c.picklist_saleman=d.saleman_id join route_saleman_relation as e on b.customer_route=e.route_id where a.Bill_status='INFILE';";
        $responce = read($query);
        echo $responce;
        break;
    case 'store_recovery_sheet':
        $saleman_id = postData('saleman');
        $query = "INSERT INTO `recovery_sheet`(`recovery_date`,`recovery_sheet_saleman_id`, `recovery_sheet_amount`, `recovery_sheet_recovery`) VALUES (CURRENT_DATE,'{$saleman_id}',0,0)";
        $responce = return_last_entered_record_id($query);
        if ($responce) {
            echo $responce;
        } else {
            echo $query;
        }
        break;
    case 'save_rec_details':
        $value_for_recovery_sheet = "";
        $value_for_bill_status = "";
        $my_recovery_sheet_id = "";
        $query_for_recovery_sheet = "INSERT INTO `recovery_sheet_detail`(`recovery_sheet_id`, `recovery_sheet_bill_id`, `recovery_sheet_bill_amount`, `recovery_sheet_bill_recovered`) VALUES";
        $query_for_Bill_status = "UPDATE `bill` SET `Bill_status`='ISSUED' WHERE `bill_id` in (";
        $customer_issued_bills = postData('recovery_sheet_detail');
        if (!empty($customer_issued_bills)) {
            $customer_issued_bills = json_decode(urldecode($customer_issued_bills), true);
            if (is_array($customer_issued_bills)) {
                foreach ($customer_issued_bills as $bill) {
                    $my_recovery_sheet_id = $bill['recovery_sheet_id'];
                    $recovery_sheet_id = $bill['recovery_sheet_id'];
                    $bill_id = $bill['bill_id'];
                    $bill_amount = $bill['bill_amount'];
                    $value_for_recovery_sheet .= "('{$recovery_sheet_id}','{$bill_id}','{$bill_amount}',0),";
                    $value_for_bill_status .= "'{$bill_id}',";
                }
                $value_for_recovery_sheet = rtrim($value_for_recovery_sheet, ',');
                $value_for_bill_status = rtrim($value_for_bill_status, ',') . ')';
            }
            $query_for_recovery_sheet .= $value_for_recovery_sheet;
            $query_for_Bill_status .= $value_for_bill_status;
            $query_for_recovery_sheet_total = "UPDATE `recovery_sheet` SET `recovery_sheet_amount`=(Select SUM(recovery_sheet_bill_amount) from recovery_sheet_detail where recovery_sheet_id='{$my_recovery_sheet_id}'),`sheet_status`='0' WHERE recovery_id='{$my_recovery_sheet_id}'";
            if (create_update_delete($query_for_recovery_sheet)) {
                if (create_update_delete($query_for_Bill_status)) {
                    if (create_update_delete($query_for_recovery_sheet_total)) {
                        echo 1;
                    } else {
                        echo $responce;
                    }
                } else {
                    echo $responce;
                }
            } else {
                echo $responce;
            }
        } else {
            echo 'JSON is Empty';
        }
        break;
    case "get_rec_sheet":
        $query = 'SELECT a.recovery_id As "ID",b.saleman_name AS "Saleman",a.recovery_date As "Date",a.recovery_sheet_amount As "Amount",a.recovery_sheet_recovery As "Recoverd",a.sheet_status As "Status" from recovery_sheet as a left join salesman as b on a.recovery_sheet_saleman_id=b.saleman_id;';
        $responce = read($query);
        echo $responce;
        break;
    case 'process_nill_bills':
        $nill_bills = postData('customer_bills');
        if (!empty($nill_bills)) {
            $bills = json_decode(urldecode($nill_bills), true);
            if (is_array($bills)) {
                foreach ($bills as $bill) {
                    $rec_id = $bill["recID"];
                    $bill_id = $bill["ID"];
                    $bill_amount = $bill["Recovered"];
                    $bill_Status = $bill["Status"];
                    $query = "Update bill set bill_amount=bill_amount-{$bill_amount},`Bill_status` = 'Nill' where `bill`.`bill_id`='$bill_id'";
                    //echo $query;
                    if (create_update_delete($query)) {
                        $myquery = "update recovery_sheet_detail set recovery_sheet_bill_recovered={$bill_amount} where recovery_sheet_bill_id='{$bill_id}' and recovery_sheet_id={$rec_id}";
                        
                        if (create_update_delete($myquery)) {

                            continue;
                        } else {
                            echo $myquery;
                            break;
                        }
                    } else {
                        echo $query;
                        break;
                    }
                }
                echo true;
            } else {
                echo 'Not An Array';
            }
        } else {
            echo 'Array is Empty';
        }
        break;
    case 'process_return_bills':
        $return_bills = postData('customer_bills');
        if (!empty($return_bills)) {
            $bills = json_decode(urldecode($return_bills), true);
            if (is_array($bills)) {
                foreach ($bills as $bill) {
                    $rec_id = $bill["recID"];
                    $bill_id = $bill["ID"];
                    $bill_amount = $bill["Recovered"];
                    $bill_Status = $bill["Status"];
                    $query = "Update bill set bill_amount=bill_amount-{$bill_amount},`Bill_status` = 'INFILE' where `bill`.`bill_id`='{$bill_id}'";
                    //echo $query;
                    if (create_update_delete($query)) {
                        $myquery = "update recovery_sheet_detail set recovery_sheet_bill_recovered={$bill_amount} where recovery_sheet_bill_id='{$bill_id}' and recovery_sheet_id='{$rec_id}'";
                        $responce=create_update_delete($myquery);
                        if ($responce) {
                            continue;
                        } else {
                            echo $myquery;
                            break;
                        }
                    } else {
                        echo $query;
                        break;
                    }
                }
                echo true;
            } else {
                echo 'Not An Array';
            }
        } else {
            echo 'Array is Empty';
        }
        break;
    case 'process_BF_bills':
        $return_bills = postData('customer_bills');
        if (!empty($return_bills)) {
            $bills = json_decode(urldecode($return_bills), true);
            if (is_array($bills)) {
                foreach ($bills as $bill) {
                    $rec_id = $bill["recID"];
                    $bill_id = $bill["ID"];
                    $bill_amount = $bill["Recovered"];
                    $bill_Status = $bill["Status"];
                    $query = "Update bill set bill_amount=bill_amount-{$bill_amount} where Bill_id='{$bill_id}'";
                    //echo '\n'.$query;
                    if (create_update_delete($query)) {
                        $myquery = "update recovery_sheet_detail set recovery_sheet_bill_recovered={$bill_amount} where recovery_sheet_bill_id='{$bill_id}' and recovery_sheet_id={$rec_id}";
                        if (create_update_delete($myquery)) {
                            
                            continue;
                        } else {
                            echo $myquery;
                            break;
                        }
                    } else {
                        echo $query;
                        break;
                    }
                }
                echo true;
            } else {
                echo 'Not An Array';
            }
        } else {
            echo 'Array is Empty';
        }
        break;
    case 'get_saleman_from_recovery_sheet':
        $recId = postData('recID');
        $query = "Select recovery_sheet_saleman_id from recovery_sheet where recovery_id=" . $recId;
        echo read($query);
        break;
    case 'update_recovery_sheet_header':
        $recID = postData('recID');
        $query = "Update recovery_sheet set recovery_sheet_recovery=(Select SUM(recovery_sheet_bill_recovered) from recovery_sheet_detail where recovery_sheet_id='{$recID}'),sheet_status='1' where recovery_id={$recID}";
        //echo $query;
        echo create_update_delete($query);
        break;
    case 'save_bill_ledger':
        $bill_ledger = postData('bill_ledger');
        if (!empty($bill_ledger)) {
            $bill_ledger = json_decode(urldecode($bill_ledger), true);
            if (is_array($bill_ledger)) {
                foreach ($bill_ledger as $bill) {
                    $ref_id = $bill["ref_id"];
                    $bill_id = $bill["bill_id"];
                    $bill_amount = $bill["bill_amount"];
                    $recived_amount = $bill["recived_amount"];
                    $remaining_amount = $bill["remaining_amount"];
                    $bill_status = $bill["bill_status"];
                    $query = "INSERT INTO `bill_ledger`(`ref_id`, `ledger_date`, `customer_id`, `bill_id`, `bill_amount`, `recived_amount`, `remaining_amount`, `bill_status`) VALUES ({$ref_id},CURRENT_DATE,(Select cutomer_id from bill where bill_id='{$bill_id}'),'{$bill_id}','{$bill_amount}','{$recived_amount}','{$remaining_amount}','{$bill_status}')";
                    if (create_update_delete($query)) {
                        continue;
                    } else {
                        echo $query;
                        break;
                    }
                }
                echo true;
            } else {
                echo 'This is not an array';
            }
        } else {
            echo 'Array is Empty';
        }
        break;
    case 'get_credit_by_saleman':
        // Return salesman id, name and outstanding credit (sum of remaining amounts)
        $query = "SELECT sl.saleman_id, sl.saleman_name, IFNULL(SUM(COALESCE(bl.remaining_amount, b.bill_amount)),0) AS Credit
                  FROM salesman sl
                  LEFT JOIN routes r ON r.saleman_id = sl.saleman_id
                  LEFT JOIN customer c ON c.customer_route = r.route_id
                  LEFT JOIN bill b ON b.cutomer_id = c.customer_id
                  LEFT JOIN bill_ledger bl ON bl.bill_id = b.bill_id
                  GROUP BY sl.saleman_id, sl.saleman_name;";
        $responce = read($query);
        echo $responce;
        break;
    case 'get_full_credit_report':
        $saleman_id = postData('saleman_id');
        if(empty($saleman_id)){
            echo json_encode(['error'=>'saleman_id missing']);
            break;
        }
        $query = "SELECT COALESCE(s.sector_name,'Unknown') AS area, c.customer_name AS shop_name, b.bill_id, b.bill_date, b.bill_amount, COALESCE(bl.remaining_amount, b.bill_amount) AS remaining_amount, DATEDIFF(CURRENT_DATE, b.bill_date) AS bill_age
                  FROM bill b
                  LEFT JOIN bill_ledger bl ON bl.bill_id = b.bill_id
                  LEFT JOIN customer c ON b.cutomer_id = c.customer_id
                  LEFT JOIN routes r ON c.customer_route = r.route_id
                  LEFT JOIN sector s ON r.sector_id = s.sector_id
                  LEFT JOIN picklist p ON b.picklist_id = p.picklist_id
                  WHERE p.picklist_saleman = '{$saleman_id}'
                  ORDER BY s.sector_name, c.customer_name, b.bill_date DESC;";
        $responce = read($query);
        echo $responce;
        break;
    case 'today_sales':
            $query="SELECT a.picklist_id,b.saleman_name,a.picklist_amount, a.picklist_credit,a.picklist_sceheme_amount,a.picklist_return,a.picklist_recovery from picklist as a join salesman as b on a.picklist_saleman=b.saleman_id where a.picklist_date_processed=CURRENT_DATE;";
            $responce=read($query);
            echo $responce;
    break;
    case 'picklist_report':
        $query="SELECT 
        pl.picklist_id,
        sl.saleman_name,
        pl.picklist_date,
        pl.picklist_amount,
        pl.picklist_amount - IFNULL(SUM(bill.bill_amount), 0) AS 'Recovered Amount',
        IFNULL(SUM(bill.bill_amount), 0) AS 'Remaining Amount',
        (1 - IFNULL(SUM(bill.bill_amount), 0) / pl.picklist_amount) * 100 AS 'Recovery %'
    FROM `picklist` AS pl
    JOIN `salesman` AS sl ON pl.picklist_saleman = sl.saleman_id
    LEFT JOIN `bill` AS bill ON pl.picklist_id = bill.picklist_id
    GROUP BY pl.picklist_id, sl.saleman_name, pl.picklist_date, pl.picklist_amount
    ORDER BY pl.picklist_date DESC;
    ";
    $responce=read($query);
    echo $responce;
        break;
    case 'get_bills_for_picklist':
        $picklist_id=postData('picklist_id');
        $query="SELECT a.bill_id,a.cutomer_id,b.customer_name,a.bill_date,a.bill_amount,a.Bill_status FROM `bill` as a left join `customer` as b on a.cutomer_id=b.customer_id where a.picklist_id='{$picklist_id}';";
        //echo $query;
        $responce=read($query);
        echo $responce;
        break;
    case 'get_bills_by_route':
        $saleman_id = postData('saleman_id');
        $day = postData('day');
        $filter_saleman_id = postData('filter_saleman_id');
        
        // Base query: Get bills for sectors this salesman visits
        $day_filter = !empty($day) ? " AND r.day='{$day}'" : "";
        
        $query = "SELECT DISTINCT a.bill_id, b.customer_name, d.saleman_name, e.sector_name, a.bill_date, a.bill_amount 
                  FROM bill as a 
                  JOIN customer as b ON a.cutomer_id = b.customer_id 
                  JOIN picklist as c ON a.picklist_id = c.picklist_id 
                  JOIN salesman as d ON c.picklist_saleman = d.saleman_id 
                  JOIN route_saleman_relation as e ON b.customer_route = e.route_id 
                  JOIN routes as r ON r.sector_id = e.sector_id 
                  WHERE r.saleman_id = '{$saleman_id}' 
                  {$day_filter} 
                  AND a.Bill_status = 'INFILE'";
        
        // Add filter by selected salesman in the filter dropdown if provided
        if (!empty($filter_saleman_id)) {
            $query .= " AND d.saleman_id = '{$filter_saleman_id}'";
        }
        
        $responce = read($query);
        echo $responce;
        break;
    case 'get_dashboard_analytics':
        $response = [];

        // 1. Key Metrics
        // Total Outstanding
        $q1 = "SELECT IFNULL(SUM(COALESCE(bl.remaining_amount, b.bill_amount)),0) as total FROM bill b LEFT JOIN bill_ledger bl ON b.bill_id = bl.bill_id";
        $r1 = json_decode(read($q1), true);
        $response['outstanding'] = $r1[0]['total'] ?? 0;

        // Sales This Month
        $q2 = "SELECT IFNULL(SUM(bill_amount),0) as total FROM bill WHERE bill_date >= DATE_FORMAT(NOW(), '%Y-%m-01')";
        $r2 = json_decode(read($q2), true);
        $response['sales_month'] = $r2[0]['total'] ?? 0;

        // Recovery This Month (Using recovery_sheet for actual collections)
        $q3 = "SELECT IFNULL(SUM(recovery_sheet_recovery),0) as total FROM recovery_sheet WHERE recovery_date >= DATE_FORMAT(NOW(), '%Y-%m-01')";
        $r3 = json_decode(read($q3), true);
        $response['recovery_month'] = $r3[0]['total'] ?? 0;

        // 2. Trends (Last 30 Days)
        // Service should merge these in PHP or JS, but sending separate arrays is easier
        $q4 = "SELECT bill_date as date, SUM(bill_amount) as amount FROM bill WHERE bill_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY bill_date ORDER BY bill_date ASC";
        $response['sales_trend'] = json_decode(read($q4), true);

        $q5 = "SELECT recovery_date as date, SUM(recovery_sheet_recovery) as amount FROM recovery_sheet WHERE recovery_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY recovery_date ORDER BY recovery_date ASC";
        $response['recovery_trend'] = json_decode(read($q5), true);

        // 3. Top 5 Salesmen (By Recovery)
        $q6 = "SELECT s.saleman_name as name, SUM(rs.recovery_sheet_recovery) as total 
               FROM recovery_sheet rs 
               JOIN salesman s ON rs.recovery_sheet_saleman_id = s.saleman_id 
               GROUP BY rs.recovery_sheet_saleman_id 
               ORDER BY total DESC LIMIT 5";
        $response['top_salesmen'] = json_decode(read($q6), true);

        // 4. Sector Performance (By Outstanding)
        $q7 = "SELECT s.sector_name, SUM(COALESCE(bl.remaining_amount, b.bill_amount)) as outstanding
               FROM bill b 
               LEFT JOIN bill_ledger bl ON b.bill_id = bl.bill_id
               JOIN customer c ON b.cutomer_id = c.customer_id
               JOIN route_saleman_relation r ON c.customer_route = r.route_id
               JOIN sector s ON r.sector_id = s.sector_id
               GROUP BY s.sector_name
               ORDER BY outstanding DESC LIMIT 5";
        $response['top_sectors'] = json_decode(read($q7), true);

        echo json_encode($response);
        break;

    default:
        echo 'No function Found';
        break;
}

<?php
/*
    API Demo
 
    This script provides a RESTful API interface for a web application
 
    Input:
 
        $_GET['format'] = [ json | html | xml ]
        $_GET['method'] = []
 
    Output: A formatted HTTP response
 
    Author: Mark Roland
 
    History:
        11/13/2012 - Created
 
*/
 
// --- Step 1: Initialize variables and functions
 
/**
 * Deliver HTTP Response
 * @param string $format The desired HTTP response content type: [json, html, xml]
 * @param string $api_response The desired HTTP response data
 * @return void
 **/

#include_once('config.php');

function getTimeOut($mac){
    $con=mysqli_connect("localhost","root","bitnami","speedtest");

    // Check connection
    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }

    $mac = mysqli_real_escape_string($con, $mac);

    $sql = "SELECT TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(rqcreated, INTERVAL 300 second)) FROM request WHERE rqmac = '$mac' AND rqstatus < 3 AND TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(rqcreated, INTERVAL 300 second)) > 0 ORDER BY rqid DESC LIMIT 1";

    $result = mysqli_query($con, $sql);

    if (mysqli_num_rows($result) > 0){
        $row = mysqli_fetch_row($result);
        $json = json_encode(array(
            "timeoutseconds" => $row[0]
        ));
        mysqli_close($con);
        return $json;
    }else{
        mysqli_close($con);
        return null;
    }
}


function saveResult($deviceId, $sessionId, $timeCreated, $msg, $rqid){
    $con=mysqli_connect("localhost","root","bitnami","speedtest");

    // Check connection
    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }

    // escape variables for security
    $deviceId = mysqli_real_escape_string($con, $deviceId);
    $sessionId = mysqli_real_escape_string($con, $sessionId);
    $timeCreated = mysqli_real_escape_string($con, $timeCreated);
    $msg = mysqli_real_escape_string($con, $msg);
    $rqid = mysqli_real_escape_string($con, $rqid);

    //Save speed test bandwidth reading on request
    if (preg_match('/^Download:.?(\d*\.?\d*).*/', $msg, $matches)){
        $sql = "UPDATE request SET rqbwdownmbit = $matches[1] WHERE rqid = ".$rqid;
        mysqli_query($con, $sql);
    }elseif (preg_match('/^Upload:.?(\d*\.?\d*).*/', $msg, $matches)){
        $sql = "UPDATE request SET rqbwupmbit = $matches[1] WHERE rqid = ".$rqid;
        mysqli_query($con, $sql);
    }

    //Save speed test share URL
    if (preg_match('/^Share results:\s(http.*png)/', $msg, $matches)){
        $sql = "UPDATE request SET rqshareurl = '$matches[1]' WHERE rqid = ".$rqid;
        mysqli_query($con, $sql);
    }

    $sql = "INSERT INTO result(deviceid, sessionid, msg, created, requestid) VALUES('$deviceId', '$sessionId', '$msg', '$timeCreated', $rqid)";
    
    if (!mysqli_query($con,$sql)) {
        return false;
        die('Error: ' . mysqli_error($con));
    }

    mysqli_close($con);

    return true;
}


function getSpeed($rqId){
    $con=mysqli_connect("localhost","root","bitnami","speedtest");

    // Check connection
    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }

    // escape variables for security
    $rqId = mysqli_real_escape_string($con, $rqId);

    $sql = "SELECT rqstatus, rqbwdownmbit, rqbwupmbit, rqbwdown, rqbwup, rqshareurl FROM request WHERE rqid = $rqId";
    
    $result = mysqli_query($con, $sql);

    if (mysqli_num_rows($result) > 0){
        $row = mysqli_fetch_row($result);
        $json = json_encode(array(
            "rqstatus" => $row[0],
            "rqbwdownmbit" => $row[1],
            "rqbwupmbit" => $row[2],
            "rqbwdown" => $row[3],
            "rqbwup" => $row[4],
            "rqshareurl" => $row[5],
        ));
        mysqli_close($con);
        return $json;
    }else{
        mysqli_close($con);
        return null;
    }
}

function timeOutRequest($deviceId = 0, $deviceMac){
    $con=mysqli_connect("localhost","root","bitnami","speedtest");

    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
        return false;
    }

    $requestTime = date("Y-m-d H:i:s");
    //Get request older than 4 minutes
    if (!$deviceId){
        $sql = "SELECT rqid FROM request WHERE rqmac = '$deviceMac'";
    }else{
        $sql = "SELECT rqid FROM request WHERE rqdeviceid = '$deviceId'";
    }
    
    #$sql .= " AND EXTRACT(MINUTE FROM TIMEDIFF('$requestTime',rqcreated)) > 4 AND rqstatus < 4";
    $sql .= " AND  TIMESTAMPDIFF(SECOND, '$requestTime', rqcreated) < -300 AND rqstatus < 4";
    $result = mysqli_query($con, $sql);
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            //Time out request
            $rqid = $row['rqid'];
            $sql = "UPDATE request SET rqstatus = 4 WHERE rqid = ".$rqid;
            if (mysqli_query($con, $sql)){
                 //Log timeout
                $sql = "INSERT INTO request_history(rqhrqid, rqhlog) VALUES('$rqid','Request timed out')";
                 if (!mysqli_query($con, $sql)){
                     echo "Failed to log request time out!";
                     return false;
                 }
            }else{
                mysqli_close($con);
                echo "Failed to time out request.";
                return false;
            }
        }
    }   
    mysqli_close($con);
    return true;
}

function updateRequestStatus($rqId, $rqStatus){
    $con=mysqli_connect("localhost","root","bitnami","speedtest");

    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
        return false;
    }

    $sql = "SELECT rqsname FROM request_status WHERE rqsid = ".$rqStatus;
    
    $result = mysqli_query($con, $sql);

    if (mysqli_num_rows($result) > 0) {
        //Get status name
        $result = $result->fetch_assoc();
        $rqStatusName = $result['rqsname'];
    }else{
        mysqli_close($con);
        echo "Failed to get request status name.";
        return false;
    }


    $sql = "UPDATE request SET rqstatus = $rqStatus WHERE rqid = ".$rqId;
    if (mysqli_query($con, $sql)){
        //Complete request
        $sql = "INSERT INTO request_history(rqhrqid, rqhlog) VALUES('$rqId','Request status set to $rqStatusName')";
        if (!mysqli_query($con, $sql)){
            echo "Failed to log completed request!";
            return false;
        }
    }else{
        mysqli_close($con);
        echo "Failed to mark request as completed.";
        return false;
    }
    mysqli_close($con);
    return true;
}

function saveRequest($data){
    $con=mysqli_connect("localhost","root","bitnami","speedtest");

    // Check connection
    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }

    //Hard code to simulate the real use case where the parameters is picked up from the service order
    $rsp = mysqli_real_escape_string($con, $data['rsp']);
    $mac = mysqli_real_escape_string($con, $data['mac']);
    $name = mysqli_real_escape_string($con, $data['name']);
    $serviceorder = mysqli_real_escape_string($con, $data['serviceorder']);
    $status = mysqli_real_escape_string($con, $data['status']);
    $bwdown = mysqli_real_escape_string($con, $data['bwdown']);
    $bwup = mysqli_real_escape_string($con, $data['bwup']);

    if (isset($data['created'])){
        $created = $data['created'];
    }else{
        $created = date('Y-m-d H:i:s');
    }

    if (!(isset($rsp) && isset($mac) && isset($name) && isset($serviceorder) && isset($status) && isset($bwdown) && isset($bwup))){
        return null;
    }

    //Check if any existing request before creating a new one
    $sql = "SELECT rqid FROM request WHERE rqmac = '$mac' AND rqstatus < 3 AND TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(rqcreated, INTERVAL 300 second)) > 0 ORDER BY rqid DESC LIMIT 1";
    $result = mysqli_query($con, $sql);

    if (mysqli_num_rows($result) > 0){
        $row = mysqli_fetch_row($result);
        return json_encode(array("rqId" => $row[0]));
    }

    $sql = "INSERT INTO request (rqmac, rqserviceorderid, rqrspid, rqstatus, rqbwdown, rqbwup, rqcreated, rqname) VALUES('$mac', '$serviceorder', '$rsp', '$status', '$bwdown', '$bwup', '$created', '$name')";
    if (!mysqli_query($con,$sql)) {
        return null;
    }else{
        $rqId = mysqli_insert_id($con);
        return json_encode(array("rqId" => $rqId));
    }
    mysqli_close($con);
}


function getResult($rqId){
    $con=mysqli_connect("localhost","root","bitnami","speedtest");

    // Check connection
    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
        return false;
    }

    // escape variables for security
    $rqId = mysqli_real_escape_string($con, $rqId);

    if($rqId > 0){
        //$sql = "SELECT msg FROM result INNER JOIN request ON rqid = requestid WHERE requestid = '$rqId' AND rqstatus < 3 ORDER BY id DESC LIMIT 3";
        $sql = "SELECT msg FROM result INNER JOIN request ON rqid = requestid WHERE requestid = '$rqId' AND rqstatus < 5 ORDER BY id DESC LIMIT 1";
    }else{
        return false;
    }

    $result = mysqli_query($con, $sql);
    if (mysqli_num_rows($result) > 0) {
        while($row = $result->fetch_row()) {
            $resultlist[]=$row[0];
        }
    }else{
        $resultlist = null;
    }
    $json = json_encode(array("result" => $resultlist));

    return $json;
}

function getSpeedServerList($deviceId = 0, $deviceMac){
    $con=mysqli_connect("localhost","root","bitnami","speedtest");

    // Check connection
    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
        return false;
    }

    // escape variables for security
    $deviceId = mysqli_real_escape_string($con, $deviceId);
    $deviceMac = mysqli_real_escape_string($con, $deviceMac);
    timeOutRequest($deviceId, $deviceMac);

    //Get requests in status new or in progress, newest first
    if (!$deviceId){
        $sql = "SELECT * FROM request WHERE rqstatus < 3 AND rqmac = '$deviceMac' ORDER BY rqid DESC LIMIT 1";
    }else{
        $sql = "SELECT * FROM request WHERE rqstatus < 3 AND rqdeviceid = '$deviceId' ORDER BY rqid DESC LIMIT 1";
    }

    $result = mysqli_query($con, $sql);
    if (mysqli_num_rows($result) > 0) {
        //Store service order ID, bandwidth up/down of service
        $result = $result->fetch_assoc();
        $rqid = $result['rqid'];
        $serviceOrderId = $result['rqserviceorderid'];
        $bandwidthDown = $result['rqbwdown'];
        $bandwidthUp = $result['rqbwup'];
        $rqstatus = $result['rqstatus'];
    }else{
        echo "Failed to fetch request information";
        return false;
    }

    //If existing request in progress, reset speed test results to collect new data
    /*
    if ($rqstatus == 2){
        $sql = "DELETE FROM result WHERE requestid = ".$rqid;
        if (!mysqli_query($con, $sql)){
            echo "Unable to delete speed test result for request $rqid";
            return false;
        }
    }
    */
    //Get server list for current request
    $sql = "SELECT srvid FROM request INNER JOIN rsp_serverlist ON rqrspid = rsp_serverlist.rspslrspid INNER JOIN serverlist ON rsp_serverlist.rspslsrvid = serverlist.srvid WHERE rqid = '$rqid' ORDER BY serverlist.srvdistance ASC";

    $result = mysqli_query($con, $sql);
    if (mysqli_num_rows($result) > 0) {
        while($row = $result->fetch_row()) {
            $serverlist[]=$row[0];
        }
    }else{
        $serverlist = null;
    }

    $json = json_encode(array(
    "request" => array(
        "rqid" => $rqid,
        "serviceorderid" => $serviceOrderId,
        "bandwidthdown" => $bandwidthDown,
        "bandwidthup" => $bandwidthUp,
        "rqstatus" => $rqstatus,
        "serverlist" => $serverlist
    )));

    //Set request status to in progress
    $sql = "UPDATE request SET rqstatus = 2 WHERE rqid = $rqid";
    if (mysqli_query($con, $sql)){
        //Log action, if request status update succeeded         
        $sql = "INSERT INTO request_history (rqhrqid, rqhlog) VALUES ($rqid, 'Speed Test configuration sent to device $deviceId $deviceMac for service order $serviceOrderId')";
        if (!mysqli_query($con, $sql)){
            mysqli_close($con);
            return false;
            die('Error: ' . mysqli_error($con));
        }
    }else{
        mysqli_close($con);
        return false;
        die('Error: ' . mysqli_error($con));
    }
    return $json;
}

function deliver_response($format, $api_response){
 
    // Define HTTP responses
    $http_response_code = array(
        200 => 'OK',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found'
    );
 
    // Set HTTP Response
    header('HTTP/1.1 '.$api_response['status'].' '.$http_response_code[ $api_response['status'] ]);
 
    // Process different content types
    if( strcasecmp($format,'json') == 0 ){
 
        // Set HTTP Response Content Type
        header('Content-Type: application/json; charset=utf-8');
 
        // Format data into a JSON response
        $json_response = json_encode($api_response);
 
        // Deliver formatted data
        echo $json_response;
 
    }elseif( strcasecmp($format,'xml') == 0 ){
 
        // Set HTTP Response Content Type
        header('Content-Type: application/xml; charset=utf-8');
 
        // Format data into an XML response (This is only good at handling string data, not arrays)
        $xml_response = '<?xml version="1.0" encoding="UTF-8"?>'."\n".
            '<response>'."\n".
            "\t".'<code>'.$api_response['code'].'</code>'."\n".
            "\t".'<data>'.$api_response['data'].'</data>'."\n".
            '</response>';
 
        // Deliver formatted data
        echo $xml_response;
 
    }else{
 
        // Set HTTP Response Content Type (This is only good at handling string data, not arrays)
        header('Content-Type: text/html; charset=utf-8');
 
        // Deliver formatted data
        echo $api_response['data'];
 
    }
 
    // End script process
    exit;
 
}
 
// Define whether an HTTPS connection is required
$HTTPS_required = FALSE;
 
// Define whether user authentication is required
$authentication_required = FALSE;
 
// Define API response codes and their related HTTP response
$api_response_code = array(
    0 => array('HTTP Response' => 400, 'Message' => 'Unknown Error'),
    1 => array('HTTP Response' => 200, 'Message' => 'Success'),
    2 => array('HTTP Response' => 403, 'Message' => 'HTTPS Required'),
    3 => array('HTTP Response' => 401, 'Message' => 'Authentication Required'),
    4 => array('HTTP Response' => 401, 'Message' => 'Authentication Failed'),
    5 => array('HTTP Response' => 404, 'Message' => 'Invalid Request'),
    6 => array('HTTP Response' => 400, 'Message' => 'Invalid Response Format')
);
 
// Set default HTTP response of 'ok'
$response['code'] = 0;
$response['status'] = 404;
$response['data'] = NULL;
 
// --- Step 2: Authorization
 
// Optionally require connections to be made via HTTPS
if( $HTTPS_required && $_SERVER['HTTPS'] != 'on' ){
    $response['code'] = 2;
    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
    $response['data'] = $api_response_code[ $response['code'] ]['Message'];
 
    // Return Response to browser. This will exit the script.
    deliver_response($_GET['format'], $response);
}
 
// Optionally require user authentication
if( $authentication_required ){
 
    if( empty($_POST['username']) || empty($_POST['password']) ){
        $response['code'] = 3;
        $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
        $response['data'] = $api_response_code[ $response['code'] ]['Message'];
 
        // Return Response to browser
        deliver_response($_GET['format'], $response);
 
    }
 
    // Return an error response if user fails authentication. This is a very simplistic example
    // that should be modified for security in a production environment
    elseif( $_POST['username'] != 'foo' && $_POST['password'] != 'bar' ){
        $response['code'] = 4;
        $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
        $response['data'] = $api_response_code[ $response['code'] ]['Message'];
 
        // Return Response to browser
        deliver_response($_GET['format'], $response);
 
    }
 
}
 
// --- Step 3: Process Request
 
// Method A: Say Hello to the API
if( strcasecmp($_GET['method'],'hello') == 0){
    $response['code'] = 1;
    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
    $response['data'] = 'Hello World';
}


if($_SERVER['REQUEST_METHOD'] == "GET"){
    switch($_GET['method']){
        case "getResult":
            if(isset($_GET['getresult'])){
                $data = json_decode($_GET['getresult'],true);
                $rqId = $data['rqId'];
                if(isset($rqId)){
                    $response['code'] = 1;
                    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
                    $response['data'] = getResult($rqId);
                }else{
                    $response['code'] = 5;
                    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
                    $response['data'] .= 'Insufficient data provided to provide speed test results.'.$_GET['getresult'];
                }
            }
        case "saveRequest":
            if(isset($_GET['data'])){
                $data = json_decode($_GET['data'],true);
                $result = saveRequest($data);

                if(!is_null($result)){
                    $response['code'] = 1;
                    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
                    $response['data'] = $result;
                }else{
                    $response['code'] = 5;
                    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
                    $response['data'] .= 'Insufficient data provided to save speed test request.'.$_GET['data'];
                }
            }
        case "getSpeed":
            if(isset($_GET['params'])){
                $data = json_decode($_GET['params'],true);
                $rqId = $data['rqId'];

                if(isset($rqId)){
                    $response['code'] = 1;
                    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
                    $response['data'] = getSpeed($rqId);
                }else{
                    $response['code'] = 5;
                    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
                    $response['data'] .= 'Insufficient data provided to save get final speed test result.'.$_GET['params'];
                }
            }

        case "getTimeOut":
            if(isset($_GET['macparam'])){
                $data = json_decode($_GET['macparam'],true);
                $mac = $data['mac'];

                if(isset($mac)){
                    $response['code'] = 1;
                    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
                    $response['data'] = getTimeOut($mac);
                }else{
                    $response['code'] = 5;
                    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
                    $response['data'] .= 'Insufficient data provided to get timeout for the given request.'.$_GET['macparam'];
                }
            }
    }
}
          


if($_SERVER['REQUEST_METHOD'] == "POST"){
    switch($_GET['method']){
        case "postResult":
            $data = json_decode($_POST['speedtest'],true);
            $deviceId = $data['deviceId'];
	        $timeCreated = $data['timeCreated'];
            $msg = $data['msg'];
            $sessionId = $data['sessionId'];
            $rqid = $data['rqid'];
            
            if((isset($deviceId) && isset($timeCreated) && isset($msg) && isset($sessionId) && isset($rqid)) && saveResult($deviceId, $sessionId, $timeCreated, $msg, $rqid)){
                $response['code'] = 1;
                $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
                $response['data'] = "Message created $timeCreated based on request=$rqid from device=$devicdId has been saved";
            }else{
                $response['code'] = 5;
                $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
                $response['data'] .= 'Insufficient data provided to save the result.'.$_POST['speedtest'];           
	        }
        case "getSessionConfiguration":
            $data = json_decode($_POST['getconfiguration'],true);

            $deviceId = $data['deviceId'];
            $deviceMac = $data['deviceMac'];
            $requestTime = date("Y-m-d H:i:s"); #Time when the function was called
            //Log on request history that new config sent to device X
            if(isset($deviceId) || isset($deviceMac)){
                $serverList = getSpeedServerList($deviceId, $deviceMac, $requestTime);
                if(!$serverList ){
                    $response['code'] = 5;
                    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
                    $response['data'] .= 'Unable to get server list due to an internal server error.'.$_POST['getconfiguration']; 
                }else{
                    $response['code'] = 1;
                    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
                    $response['data'] = $serverList; #"Message created $requestTime from $devicdId $deviceMac has been saved";
                }
            }else{
                $response['code'] = 5;
                $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
                $response['data'] .= 'Unable to get server list due to insufficient data.'.$_POST['getconfiguration'];
            }
        case "updateStatus":
            if(isset($_POST['updaterequeststatus'])){
                $data = json_decode($_POST['updaterequeststatus'],true);
                $rqId = $data['rqId'];
                $rqStatus = $data['rqStatus'];
            
                if((isset($rqId) && isset($rqStatus)) && updateRequestStatus($rqId, $rqStatus)){
                    $response['code'] = 1;
                    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
                    $response['data'] = "Set response status $rqStatus on request=$rqId.";
                }else{
                    $response['code'] = 5;
                    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
                    $response['data'] .= 'Insufficient data provided to save the result.'.$_POST['updaterequeststatus'];           
                }
            }
        case "getResult":
            if(isset($_POST['getresult'])){
                $data = json_decode($_POST['getresult'],true);
                $rqId = $data['rqId'];

                if(isset($rqId)){
                    $response['code'] = 1;
                    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
                    $response['data'] = getResult($rqId);
                }else{
                    $response['code'] = 5;
                    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
                    $response['data'] .= 'Insufficient data provided to provide speed test results.'.$_POST['getresult'];
                }
            }
        case "saveRequest":
            if(isset($_POST['data'])){
                $data = json_decode($_POST['data'],true);
                $result = saveRequest($data);

                if(!is_null($result)){
                    $response['code'] = 1;
                    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
                    $response['data'] = $result;
                }else{
                    $response['code'] = 5;
                    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
                    $response['data'] .= 'Insufficient data provided to save speed test request.'.$_POST['data'];
                }
            }

        case "getSpeed":
            if(isset($_POST['params'])){
                $data = json_decode($_POST['params'],true);
                $rqId = $data['rqId'];

                if(isset($rqId)){
                    $response['code'] = 1;
                    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
                    $response['data'] = getSpeed($rqId);
                }else{
                    $response['code'] = 5;
                    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
                    $response['data'] .= 'Insufficient data provided to save get final speed test result.'.$_POST['params'];
                }
            }

        case "getTimeOut":
            if(isset($_POST['macparam'])){
                $data = json_decode($_POST['macparam'],true);

                if(isset($data['mac'])){
                    $mac = $data['mac'];
                    $response['code'] = 1;
                    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
                    $response['data'] = getTimeOut($mac);
                }else{
                    $response['code'] = 5;
                    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
                    $response['data'] .= 'Insufficient data provided to get timeout for the given request.'.$_POST['macparam'];
                }
            }

        case "timeOut":
            if(isset($_POST['timeoutmac'])){
                $data = json_decode($_POST['timeoutmac'],true);

                if(isset($data['mac'])){
                    $mac = $data['mac'];
                    $response['code'] = 1;
                    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
                    $response['data'] = timeOutRequest(0, $mac);
                }else{
                    $response['code'] = 5;
                    $response['status'] = $api_response_code[ $response['code'] ]['HTTP Response'];
                    $response['data'] .= 'Insufficient data provided to timeout old requests.'.$_POST['timeoutmac'];
                }
            }
            
    }
}

// --- Step 4: Deliver Response

// Return Response to browser
deliver_response($_GET['format'], $response);

?>

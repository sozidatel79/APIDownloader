<?php
header("Access-Control-Allow-Origin: *");

require_once("./src/Cooladata.php");
require_once("./src/Curl.php");
require_once("./src/Constant.php");


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])){
    if($_POST['id'] == 'terminate') {
        $response = [
            'status' => 'terminated',
        ];
        echo json_encode($response);
        return false;
    } else {
        (new CoolaDataToBigQuery( new Curl(), $_POST))->getEventsData(Constant::CHUNK_SIZE);
    }
}

?>

<?php if ($_SERVER['REQUEST_METHOD'] == 'GET'): ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Coola Data to Big Query</title>
    <script src="js/jq.js"></script>
    <script type="text/javascript" src="js/clbq.js"></script>
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="card card-1">
            <div class="card-header">
                <h2>Coola Data to Big Query | Disk space free: <b class="df"></b></h2>
                <h3 class="remaining">Remaining: <b style="color: cornsilk" class="remaining"></b> events | From <b style="color:cyan" class="total"></b> Total | Data between dates <b style="color: springgreen" class="days"></b> </h3>
                <div class="bottom_header_info">
                    <button id="1" class="btn btn-primary">Start</button>
                    <h2 class="process_title" style='color: darkred;'>Process Stopped...</h2>
                    <img src="img/loading.gif" alt="" class="loader" id="loader">
                    <h2 class="time_passed">Time Spent: <b class="time_p"></b></h2>
                </div>
            </div>
            <div class="card-body">
                <div class="progress progress-1">
                    <table class="pr_tbl">
                        <thead>
                        <th>Request #</th>
                        <th>Processed with offset</th>
                        <th>Events per request</th>
                        <th>Request time</th>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
       <!-- <div class="card card-2">
            <div class="card-header">
                <h2>Coola Data to Big Query</h2>
                <h3 class="remaining">Remaining: <b style="color: cornsilk" class="remaining"></b> events | From <b style="color:cyan" class="total"></b> Total | Data From Last <b style="color: springgreen" class="days"></b> Days</h3>
                <button id="2" class="btn btn-primary">Start</button>
                <img src="img/loading.gif" alt="" class="loader" id="loader">
            </div>
            <div class="card-body">
                <h2 class="process_title" style='color: darkred; position: fixed'>Process Stopped...</h2>
                <div class="progress progress-2">
                    <table class="pr_tbl">
                        <thead>
                        <th>Request #</th>
                        <th>Processed with offset</th>
                        <th>Events per request</th>
                        <th>Request time</th>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>-->
    </div>
</body>
</html>

<?php endif; ?>





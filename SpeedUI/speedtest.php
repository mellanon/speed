<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
<meta name="description" content="">
<meta name="author" content="">
<link rel="icon" href="../../favicon.ico">

<title>Speed Test Request</title>

<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">

<!-- jQuery library -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>

<!-- Latest compiled JavaScript -->
<script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>

<!-- Custom styles for this template -->
<link href="css/signin.css" rel="stylesheet">

<script src="js/spin.min.js"></script>
<script src="js/jquery.spin.js"></script>

<style>
.listener {
    z-index: 1002;
    display: inline-block;
    white-space: nowrap;
    position: fixed;
    top: 50%;
    left: 50%;
    /* bring your own prefixes */
    transform: translate(-50%, -50%);
    -webkit-transform: translate(-50%, -50%);
    -ms-transform: translate(-50%, -50%);
}

.time {
    font-weight: bold; 
    margin: auto;
    width: 20%;
    margin: 0 auto; 
    display:inherit;
}

.alert-z {
    z-index: 1002;
    white-space: nowrap;
}

html, body, .container {
    height: 100%;
}
.container {
    display: table;
    vertical-align: middle;
}
.container2 {
    z-index:1111;
    position: absolute;
    top: 50%;
    left: 50%;
    /* bring your own prefixes */
    transform: translate(-50%, -50%);
    -webkit-transform: translate(-50%, -50%);
    -ms-transform: translate(-50%, -50%);
    display: block;
    vertical-align: middle;
}
.vertical-center-row {
    display: table-cell;
    vertical-align: middle;
}

</style>

<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
<!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
<![endif]-->
</head>

<body id="bod">
<div class="container" id="cont">
    <div class="row vertical-center-row">
        <div class="col-lg-12">
            <div class="row ">
                <form role="form" class="form-signin" action="#" id="speedtest">
                    <div class="form-group" id="formheading">
                        <h2>Speed Test Request</h2>
                        <p>Use this form to create speed test requests which is required before you can initiate a speed test using the Northpower Speed Test Device!</p>
                    </div>
                    <div class="form-group">
                        <label for="mac">MAC address (will be scanned from barcode):</label>
                        <select class="form-control" id="mac" name="mac">
                            <option value="00:1e:06:10:2d:70" selected>ODROID C1+</option>
                            <option value="02:5b:47:a3:57:eb">Banana Pi</option>
                            <option value="00:0c:29:59:59:7b">VM Andreas Laptop</option>
                            <option value="b8:27:eb:46:0f:4d">Raspberry Pi</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="sel1">Service Provider (will be selected from service order):</label>
                        <select class="form-control" id="rsp" name="rsp">
                            <option value="0">None</option>
                            <option value="1" selected>Spark</option>
                            <option value="2">Vodafone</option>
                            <option value="3">CallPlus</option>
                            <option value="4">Tele2 (Sweden)</option>
                        </select>
                    </div>
                    <button type="button" id="btnSubmit" class="btn btn-lg btn-default">Submit</button>
                </form>
            </div> <!-- /row -->
        </div> <!-- /col-lg -->
    </div> <!-- /row vertical -->
</div> <!-- /container -->
<div class="container2" id="cont-result" style="display: none;">
    <div class="row vertical-center-row">
        <div class="col-lg-12">
            <div class="row ">
                <div class="form-signin" id="speed-result">
                    <div class="form-group" id="speed-result-heading">
                        <h2>Speed Test Result</h2>
                    </div>
                    <div class="form-group" id="speed-result-heading">
                        <button type="button" id="btnRestart" class="btn btn-lg btn-default">Restart</button>
                    </div>
                </div>
            </div> <!-- /row -->
        </div> <!-- /col-lg -->
    </div> <!-- /row vertical -->
</div> <!-- /container -->
<script>
var timeOutInterval = null;
var refresher = null;

function startTimer(duration, display) {
        var start = Date.now(),
        diff,
        minutes,
        seconds;

        function timer() {
            // get the number of seconds that have elapsed since 
            // startTimer() was called
            diff = duration - (((Date.now() - start) / 1000) | 0);

            // does the same job as parseInt truncates the float
            minutes = (diff / 60) | 0;
            seconds = (diff % 60) | 0;

            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            display.textContent = minutes + ":" + seconds; 
            
            if (diff <= 0) {
                // add one second so that the count down starts at the full duration
                // example 05:00 not 04:59
                start = Date.now() + 1000;
                $('#bod').spin(false);
                $('#listener').hide();
                $("#speedtest").show();
                timeOutRequests($("#mac").val());
                location.reload();
            }
        };
        // we don't want to wait a full second before the timer starts
        timer();
        timeOutInterval = setInterval(timer, 1000);
}

function resetUI(){
    clearInterval(refresher);
    clearInterval(timeOutInterval);
    rqId = 0;
    showDown = false;
    showUp = false;
    rqstatus = 0;
    rqbwdownmbit = 0;
    rqbwupmbit = 0;
    rqbwdown = 0;
    rqbwup = 0;
    timeOutTimer = null;
    timeOutInterval = null;
}

function timeOutRequests(mac){
    $.ajax({
        url: "../SpeedService/timeOut.json",
            type: "POST",
            dataType: "json",
            data: { timeoutmac: "{\"mac\":\"" + mac + "\"}" },
            success: function(json) {
                return true;
            }
    });
    return false;
}
var opts = {
    lines: 17 // The number of lines to draw
        , length: 45 // The length of each line
        , width: 16 // The line thickness
        , radius: 84 // The radius of the inner circle
        , scale: 1 // Scales overall size of the spinner
        , corners: 1 // Corner roundness (0..1)
        , color: '#000' // #rgb or #rrggbb or array of colors
        , opacity: 0.25 // Opacity of the lines
        , rotate: 0 // The rotation offset
        , direction: 1 // 1: clockwise, -1: counterclockwise
        , speed: 1 // Rounds per second
        , trail: 60 // Afterglow percentage
        , fps: 20 // Frames per second when using setTimeout() as a fallback for CSS
        , zIndex: 1000 // The z-index (defaults to 2000000000)
        , className: 'spinner' // The CSS class to assign to the spinner
        , top: '50%' // Top position relative to parent
        , left: '50%' // Left position relative to parent
        , shadow: false // Whether to render a shadow
        , hwaccel: false // Whether to use hardware acceleration
        , position: 'absolute' // Element positioning
}
var rqId = 0;
var showDown = false;
var showUp = false;
var rqstatus = 0;
var rqbwdownmbit = -1;
var rqbwupmbit = -1;
var rqbwdown = 0;
var rqbwup = 0;
var timeOutTimer = null;

$('#btnRestart').click(function() {
    $("#cont-result").hide();
    $("#speedtest").show();
    location.reload();
});


$('#btnSubmit').click(function () {
    $.ajax({
        url: "../SpeedService/saveRequest.json",
            type: "POST",
            dataType: 'json',
            data: { data: "{\"rsp\":" + $("#rsp").val() + ",\"mac\":\"" + $("#mac").val() + "\",\"name\":\"Andreas\",\"serviceorder\": 200074, \"status\":1,\"bwdown\":10,\"bwup\":0.5}" },
            success: function(json) {
                var json2 = jQuery.parseJSON(json.data);
                rqId = json2.rqId;
                $('#bod').spin(opts) // Creates a 'large' white Spinner
                    $("#speedtest").hide();
                $( "<div class='listener' id='listener'><div class='alert alert-info' role='alert' id='listener-alert'><strong>Listening for Speed Test Results...</strong></div></div>" ).insertAfter( ".spinner" );
                $( "<br/><span id='time' class='time'></span></div>" ).appendTo( "#listener-alert" );
                //clearInterval(refresher);
                refresher = setInterval(function(){
                    $.ajax({
                        url: "../SpeedService/getResult.json",
                            type: "POST",
                            dataType: 'json',
                            data: { getresult: "{\"rqId\":" + rqId + "}" },
                            success: function(json) {

                                var resp = jQuery.parseJSON(json.data);
                                if (resp.result !== null){
                                    jQuery.each(resp.result, function(index, item) {
                                        $("#listener-alert").html("<strong>" + item + "</strong>");
                                        if (item === 'Speed Test Completed!'){
                                            $.ajax({
                                                url: "../SpeedService/getSpeed.json",
                                                    type: "POST",
                                                    dataType: "json",
                                                    data: { params: "{\"rqId\":" + rqId + "}" },
                                                    success: function(json) {
                                                        var speedr = jQuery.parseJSON(json.data);
                                                        if (speedr.rqstatus !== null){
                                                            var rqstatus = speedr.rqstatus;
                                                            var rqbwdownmbit = speedr.rqbwdownmbit;
                                                            var rqbwupmbit = speedr.rqbwupmbit;
                                                            var rqbwdown = speedr.rqbwdown;
                                                            var rqbwup = speedr.rqbwup;
                                                            var rqshareurl = speedr.rqshareurl;

                                                            if (parseFloat(rqbwdownmbit) > 0 && !showDown){
                                                                $("<div class='alert alert-z' id='alert-down' role='alert'><span class='glyphicon glyphicon-cloud-download' aria-hidden='true'></span><span class='sr-only'>Download:</span><strong> Download:</strong> " + rqbwdownmbit + " Mbit/s</div>").insertAfter("#speed-result-heading");
                                                                $("#alert-down").fadeTo("slow", 1);
                                                                showDown = true;
                                                            }
                                                            if (parseFloat(rqbwupmbit) > 0 && !showUp){
                                                                $("<div class='alert alert-z' id='alert-up' role='alert'><span class='glyphicon glyphicon-cloud-upload' aria-hidden='true'></span><span class='sr-only'>Upload:</span><strong> Upload:</strong> " + rqbwupmbit + " Mbit/s</div>").insertAfter("#speed-result-heading");
                                                                $("#alert-up").fadeTo("slow", 1);
                                                                showUp = true;
                                                            }
                                                            if (parseFloat(rqbwdownmbit) < parseFloat(rqbwdown)){
                                                                $("#alert-down").addClass("alert-danger");
                                                            }else{
                                                                $("#alert-down").addClass("alert-success");
                                                            }
                                                            if (parseFloat(rqbwupmbit) < parseFloat(rqbwup)){
                                                                $("#alert-up").addClass("alert-danger");
                                                            }else{
                                                                $("#alert-up").addClass("alert-success");
                                                            }
                                                            if (rqshareurl !== null){
                                                                $("<div class='alert alert-z' id='alert-speed' role='alert'><img src='" + rqshareurl + "'/></div>").insertAfter("#speed-result-heading");
                                                            }
                                                        }
                                                    }
                                            });
                                            $('#bod').spin(false);
                                            $('#listener').hide();
                                            $("#speedtest").hide();
                                            clearInterval(refresher);
                                            $("#cont-result").show();
                                        }
                                    });
                                }
                            }
                    }); 
                    if (timeOutTimer == null){
                        $.ajax({
                            url: "../SpeedService/getTimeOut.json",
                                type: "POST",
                                dataType: "json",
                                data: { macparam: "{\"mac\":\"" + $("#mac").val() + "\"}" },
                                success: function(json) {
                                    var timer = jQuery.parseJSON(json.data);
                                    timeoutseconds = timer.timeoutseconds;
                                    if (timeoutseconds > 0){
                                        console.log(timeoutseconds);
                                        timeOutTimer = timeoutseconds;
                                        display = document.querySelector('#time');//display = $('#time');
                                        startTimer(timeOutTimer, display);
                                    }
                                }
                        });
                    }
                },1000);
            }
    });


    //timeOutTimer = setInterval(function(){
    //}, 1000);
});

</script>

</body>
</html>



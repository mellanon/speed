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
    max-width: 600px;
    padding: 15px;
    margin: 0 auto;
    position:relative;top:300px;
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
<div class="alert alert-success text-center" role="alert">
    <strong>Speed Test Request Saved!</strong>
</div>
<form role="form" class="form-signin" action="#" id="speedtest">
    <div class="form-group">
        <h2>Speed Test Request</h2>
        <p>Use this form to create speed test requests which is required before you can initiate a speed test using the Northpower Speed Test Device!</p>
    </div>
  <div class="form-group">
    <label for="mac">MAC address (00:0c:29:59:59:7b):</label>
    <input type="text" class="form-control" id="mac" value="00:0c:29:59:59:7b" autofocus required name="mac">
  </div>
<div class="form-group">
  <label for="sel1">Service Provider (will be selected from service order):</label>
  <select class="form-control" id="rsp" name="rsp">
    <option value="0">None</option>
    <option value="1" selected>Spark</option>
    <option value="2">Vodafone</option>
    <option value="3">CallPlu</option>
  </select>
</div>
  <button type="button" id="btnSubmit" class="btn btn-default">Submit</button>
</form>

<div class="alert alert-success text-center" role="alert">
    <strong>Speed Test Request Saved!</strong>
</div>


<!--      <iframe src="index.php<?php if(isset($mac)){ echo '?mac=00:0c:29:59:59:7b'; }?>" style="zoom:0.60" width="100%" height="350" frameborder="0"></iframe>-->

</div> <!-- /container -->
<script>
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
        , zIndex: 2e9 // The z-index (defaults to 2000000000)
        , className: 'spinner' // The CSS class to assign to the spinner
        , top: '20%' // Top position relative to parent
        , left: '50%' // Left position relative to parent
        , shadow: false // Whether to render a shadow
        , hwaccel: false // Whether to use hardware acceleration
        , position: 'absolute' // Element positioning
}
/*$('#bod').spin(opts) // Creates a 'large' white Spinner
$( "<div class='listener' id='listener'><div class='alert alert-info' role='alert' id='listener-alert'><strong>Heads up!</strong> This alert needs your attention, but it's not super important.</div></div>" ).insertAfter( ".spinner" );
$("#speedtest").fadeTo("slow", 0.1);
 */
$("#btnSubmit1").click(function(){
    $.post("../SpeedService/saveRequest.json",
        {
            data: "{\"rsp\":" + $("#rsp").val() + ",\"mac\":\"" + $("#mac").val() + "\",\"name\":\"Andreas\",\"serviceorder\": 200074, \"status\":1,\"bwdown\":30,\"bwup\":10}"
        },
        function(response, status){
            var obj = jQuery.parseJSON( '{"code":1,"status":200,"data":"{"rqId":49}"}' );
            //var obj = $.parseJSON(response);
            //$("#mac").val('hej'+obj.data.rqId);
            $("#mac").val(obj.data.rqId);
            //alert("Data: " + response + "\nStatus: " + status);
        });

});

$('#btnSubmit').click(function () {
    $.ajax({
        url: "../SpeedService/saveRequest.json",
        type: "POST",
        dataType: 'json',
        data: { data: "{\"rsp\":" + $("#rsp").val() + ",\"mac\":\"" + $("#mac").val() + "\",\"name\":\"Andreas\",\"serviceorder\": 200074, \"status\":1,\"bwdown\":30,\"bwup\":10}" },
        success: function(json) {
            console.log(JSON.stringify(json.topics));
            var json2 = jQuery.parseJSON(json.data);
            console.log(json2.rqId);
            /*$.each(json.topics, function(idx, topic){
                $("#bod").html('<a href="' + topic.link_src + '">' + topic.link_text + "</a>");
        });*/
        }
    });
});

$("#btnGetResult").click(function(){
    $.post("http://scottf.ddns.net/SpeedService/getResult.json",
        {
            getresult: "<?php if(isset($rqId)){ echo '{\"rqId\":'.$rqId.'}'; }else{ echo '{\"rqId\":43}';}?>",
        },
        function(data, status){
            alert("Data: " + data + "\nStatus: " + status);
        });
});
</script>

</body>
</html>



<!DOCTYPE html>
<html>
<head>
<title>Rendering Unit Test Results</title>
<style type="text/css">
h2{
    margin:8px 0px 5px;
}
h4{
    margin:0px;
}
li{
    margin-bottom:10px;
    background-color: #EBEFF5;
    border: 1px solid #DEDEDE;
    margin-top: 8px;
    padding: 6px;
    list-style-type:none;
}
label{
    font-weight:bold;
    display: inline-block;
    min-width:100px;
}
</style>
</head>
<body>
<h1><?=$passCount?> of <?=$testCount?> tests passed. <?=$failureCount?> tests failed.</h1>
<?
if ($failureCount > 0) {
    if ($verbose) {
        echo "<h2>Passed Tests:</h2><ul>\n";
        foreach ($testResults as $message => $passed) {
            if ($passed) {
                echo "<li>\n$message\n</li>\n";
            }
        }
        echo "</ul>\n";
    }
    echo "<h2>Failed Tests:</h2><ul>\n";
    foreach ($testResults as $message => $passed) {
        if (!$passed) {
            echo "<li>\n$message\n</li>\n";
        }
    }
    echo "</ul>\n";
}
?>
<script type='text/javascript'>
function hideShow(element) {
    element = document.getElementById(element);
    element.style.display = (element.style.display === 'none') ? '' : 'none';
}
</script>
</body>
</html>
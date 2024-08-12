<!DOCTYPE html>
<html>
<head>
<title>Documentation Unit Test Results</title>
<style type="text/css">
h1{
    padding:8px;
}
.hidden{
    display: none;
}
.fail{ 
    background-color: firebrick;
    color:white;
}
.pass{ 
    background-color: forestgreen;
    color:white;
}
dt{
    padding:10px;
    border-radius: 10px 10px 0 0;
    cursor:pointer;
    font-weight:bold;
    font-size:1.5em;
    background-color:#EBEFF5;
    border:1.5px solid #CCC;
}
dd{
    margin-left:0px;
    padding: 5px 40px;
    font-size:1.1em;
    border-bottom:1px solid #AAA;
}
dd.info{
    background-color:lightsteelblue;
}
dd.error{
    background-color: firebrick;
    color:white;
}
</style>
</head>
<body>
<?if($results['failureCount']):?>
<h1 class="fail"><?=$results['failureCount'];?> Test <?=$results['failureCount'] === 1 ? 'Failure' : 'Failures';?></h1>
<?else:?>
<h1 class="pass">0 Test Failures</h1>
<?endif;?>
<?if(count($results['output'])):?>
    <?foreach($results['output'] as $fileName => $errorDetails):?>
        <?if(count($errorDetails) > 1):?>
            <dl>
                <dt onclick="hideShow(this);"><?=$fileName;?></dt>
                <?foreach($errorDetails as $errorMessage => $type):?>
                    <?if($errorMessage !== 'errorCount'):?>
                    <dd class="<?=$type;?>" style="<?=(($errorDetails['errorCount']) ? '' : 'display:none');?>">
                        <?=$errorMessage;?>
                    </dd>
                    <?endif;?>                
                <?endforeach;?>
            </dl>
        <?endif;?>
    <?endforeach;?>
<?endif;?>
<script>
function hideShow(element) {
   var nodeList = element.parentNode.childNodes, i;
   for(i=0; i<nodeList.length; i++){
       if(nodeList[i].nodeType !== 1 || nodeList[i] === element){
           continue;
       }
       nodeList[i].style.display = (nodeList[i].style.display === 'none') ? '' : 'none';
   }
}

</script>
</body>
</html>
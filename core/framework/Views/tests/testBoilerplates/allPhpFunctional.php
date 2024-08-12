<html>
<head>
    <title>0 of <?= $total ?></title>
    <style type="text/css">
    iframe{display:block;width:99%;height:100%;border:0;}
    #controls{position:fixed;bottom:0;right:0;left:0;background:rgba(0,0,0,.6);padding:8px;}
    #controls button{float:right;}
    #controls label {color:#FFF;}
    #controls a {padding-left:20px;}
    #failures{margin:10px;background:#ccc;border:1px solid #999;padding:6px;}
    .test{margin-top:8px;}
    .red{color:red;}
    .green{color:green;}
    h1{font-size:1.3em;}
    body{margin-bottom:3em;}
    </style>
</head>
<body>

    <? $count = 0; ?>
    <? foreach ($tests as $test): ?>
        <div class="test" data-num="<?= $count++ ?>" data-src="<?= $test ?>">
            <b>[<?= $count ?> / <?= $total ?>]</b> <a target="_blank" href="/ci/unitTest/<?=$type?>Functional/testAll/<?= $test ?>"><?= $test ?></a>
        </div>
    <? endforeach; ?>

    <div id="failures">
        <h3>Errors</h3>
        <ul></ul>
    </div>

    <div id="controls">
        <label>
            <input type="checkbox" checked onclick="updateScroll(this)"/>
            Scroll to currently-running test
        </label>
        <a href="#failures" class="red"><span class="count">0</span> failures</a>
        <button type="button" onclick="return stop(this)">Wait, stop!</button>
    </div>
<script>
var keepScrolling = true,
    failures = [],
    stopTesting = false;

function stop (el) {
    stopTesting = true;
    el.disabled = true;
    return false;
}
function updateTitle (newTitle) {
    document.title = newTitle;
}
function updateScroll (el) {
    keepScrolling = el.checked;
}
function addError (link, count) {
    document.querySelector('#failures ul').innerHTML += '<li><a href="' + link + '" target="_blank">' + link + '</a></li>';
    document.querySelector('#controls .count').innerHTML = count;
}
function runTest (num) {
    var testDiv = document.querySelector('.test[data-num="' + num + '"]');

    if (!testDiv || stopTesting) return;

    var frame = document.createElement('iframe');
    frame.onload = (function (nextIndex) {
        return function(e) {
            var doc = e.target.contentDocument.body.innerText,
                parent = e.target.parentNode,
                statusIcon;
            if (doc.indexOf('0 fails and 0 exceptions.') === -1) {
                failures.push(e.target.src);
                addError(e.target.src, failures.length);
                statusIcon = "<span class='red'>×</span>";
            }
            else {
                statusIcon = "<span class='green'>✔</span>";
                parent.removeChild(e.target);
            }
            parent.firstElementChild.innerHTML = statusIcon + " " +
                parent.firstElementChild.innerHTML;

            runTest(nextIndex);
        };
    })(++num);
    frame.src = "/ci/unitTest/<?=$type?>Functional/testAll/" + testDiv.getAttribute('data-src');
    frame.scrollbars = "no";
    frame.seamless = true;
    testDiv.appendChild(frame);
    if (keepScrolling) {
        window.scroll(0, testDiv.offsetTop)
    }
    updateTitle("Running " + num + " / " + <?= $count ?> + " | " + failures.length + " failures");
}
runTest(0);
</script>
</body>
</html>

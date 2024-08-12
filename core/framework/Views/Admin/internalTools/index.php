<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <title>Available Internal Tools</title>
    <style type="text/css">
        html {background:grey url(http://placekitten.com/<?= rand(100, 1000) ?>/<?= rand(100, 1000) ?>);font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;text-align:center;}
        body{background:rgba(255, 255, 255, 0.9);width:60%;min-width:200px;margin:2em auto;padding:1em;border-radius:6px;box-shadow:0 2px 3px rgba(0, 0, 0, .3);font-size:1.3em;}
        h1{margin-bottom:1em;padding-bottom:1em;border-bottom:1px solid #000;}
        .params,code,form{padding:4px;background:#EFEFEF;border:1px solid #CCC;margin:1em;display:block;word-wrap:break-word;}
        .params{text-align:left;width:50%;margin:auto;}
        dt{margin-top:.8em;background:#77E97C;border-top-right-radius: 3px;border-top-left-radius: 3px;}
        dt a{color:#111;text-shadow:0 1px 0 #FFF;display:block;padding:.3em;}
        dt a:hover{background:#13E91D;}
        dd{margin:0;font-size:smaller;color:#666;border: 1px solid #CCC;padding:1em;background:#FFF;border-bottom-right-radius: 3px;border-bottom-left-radius: 3px;}
        a{text-decoration:none;}a:focus,a:hover{text-decoration:underline;}
        form{margin-top:2em;background:rgb(158, 179, 161);padding:8px;color:#222;}
    </style>
</head>
<body>
<h1>Available Internal Tools</h1>
<dl>
<? foreach ($methods as $name => $desc): ?>
    <dt><a href="/ci/internalTools/<?= $name ?>"><?= $name ?></a></dt>
    <?
    // Wrap "e.g." line in code and param section in div
    if ($index = strpos($desc, 'e.g.')) {
        $newline = strpos($desc, "\n", $index);
        $desc = substr($desc, 0, $index) . '<code>' . substr($desc, $index, $newline - $index) . '</code>' . substr($desc, $newline);
    }
    if ($index = strpos($desc, '@param')) {
        $desc = substr($desc, 0, $index) . "<div class='params' data-href='/ci/internalTools/$name'>" . (substr($desc, $index)) . '</div>';
    }
    ?>
    <dd><?= nl2br($desc) ?></dd>
<? endforeach; ?>
</dl>
<script>
!function() {
    // Cancel the form's native submission, and navigate to
    // the form endpoint w/ the input values as segment params.
    function submitHandler(e) {
        e.preventDefault();
        var form = e.target,
            inputs = form.querySelectorAll('input'),
            params = '', i;
        for (i = 0; i < inputs.length; i++) {
            params += '/' + inputs[i].value;
        }
        window.location = form.getAttribute('action') + params;
    }

    // For all endpoints that require params: jam in a form w/ an input
    // for each param.
    for (var
            html, toInsert, element, params, dest,
            elements = document.querySelectorAll('.params'),
            len = elements.length,
            re = /@param [A-Za-z|=?]* (\$[a-zA-Z0-9]+)/gm,
            i = 0;
         i < len;
         i++) {

        element = elements[i];
        html = element.innerHTML;
        toInsert = '';

        while (params = re.exec(html)) {
            toInsert += '<input type="text" placeholder="' + params[1] + '"/>/';
        }
        if (toInsert) {
            dest = element.getAttribute('data-href');
            toInsert = '<form method="GET" action="' + dest + '">' + dest + '/' + toInsert + ' <button type="submit">Hit it!</button></form>';
            element.insertAdjacentHTML('afterend', toInsert);
        }
    }

    // Attach the form handler onto all newly-created forms.
    for (i = 0, elements = document.querySelectorAll('form'), len = elements.length; i < len; i++) {
        element = elements[i];
        element.addEventListener('submit', submitHandler);
    }
}();
</script>
</body>
</html>
<!-- ヾ(⌐■_■)ノ♪ -->

<div>
<h2><? printf(\RightNow\Utils\Config::getMessage(THE_PAGE_PCT_S_WAS_NOT_FOUND_MSG), $page) ?></h2>

<? if ($suggestions): ?>
<h3><?= \RightNow\Utils\Config::getMessage(DID_YOU_MEAN_ELLIPSIS_MSG) ?></h3>
<ul>
<? foreach ($suggestions as $path): ?>
<li><a href="/ci/admin/<?= $path ?>"><?= $path ?></a></li>
<? endforeach; ?>
</ul>
<? endif; ?>
</div>
<br><br><br>
<div id='container' style='opacity:0.1;margin:auto;width:740px;' onmouseover='this.style.opacity=0.3;' onmouseout='this.style.opacity=0.1;'></div>
<script>!function(){var a=document.getElementById("container"),b=document.createElement("canvas");b.setAttribute("width","740px"),b.setAttribute("height","414px"),b.setAttribute("id","canvas"),a.appendChild(b);if(!b.getContext)return;var c=b.getContext("2d");c.fillStyle=c.strokeStyle="#e00024",c.beginPath(),c.moveTo(50,140);var d=10;c.lineTo(160-d,20),c.arcTo(160,20,160,20+d,d),c.lineTo(160,220),c.lineWidth=36,c.stroke(),c.closePath(),c.fillRect(35,130,110,36),c.strokeStyle=c.fillStyle="#FFF",c.beginPath(),c.moveTo(109,166),c.lineTo(142,130),c.lineTo(142,166),c.lineTo(109,166),c.lineWidth=1,c.stroke(),c.fill(),c.closePath(),c.fillStyle="#e00024",c.fillRect(305,2,135,36),c.fillRect(305,178,135,36),c.strokeStyle="#e00024",c.beginPath(),c.lineWidth=36,c.arc(320,108,89,0,Math.PI*2,!0),c.closePath(),c.stroke(),c.beginPath(),c.arc(420,108,89,0,Math.PI*2,!0),c.closePath(),c.stroke(),c.fillStyle="#FFF",c.fillRect(305,0,135,2),c.fillRect(305,214,135,2),c.fillRect(305,38,132,140),c.strokeStyle="#000",c.beginPath(),c.lineWidth=2,c.arc(340,87,5,0,Math.PI*2,!0),c.closePath(),c.stroke(),c.beginPath(),c.fillStyle="#000",c.arc(340,85,1,0,Math.PI*2,!0),c.stroke(),c.closePath(),c.fill(),c.beginPath(),c.fillStyle="#000",c.arc(414,74,5,0,Math.PI*2,!0),c.closePath(),c.stroke(),c.beginPath(),c.arc(412,72,1,0,Math.PI*2,!0),c.stroke(),c.closePath(),c.fill(),c.beginPath(),c.moveTo(324,144),c.lineTo(410,150),c.lineWidth=5,c.stroke(),c.closePath(),c.fillStyle=c.strokeStyle="#000",c.beginPath(),c.moveTo(324,84),c.lineTo(360,68),c.lineWidth=6,c.stroke(),c.closePath(),c.fillStyle=c.strokeStyle="#000",c.beginPath(),c.moveTo(400,58),c.lineTo(430,72),c.lineWidth=6,c.stroke(),c.closePath(),c.fillStyle=c.strokeStyle="#e00024",c.beginPath(),c.moveTo(565,140);var d=10;c.lineTo(675-d,20),c.arcTo(675,20,675,20+d,d),c.lineTo(675,220),c.lineWidth=36,c.stroke(),c.closePath(),c.fillRect(551,130,110,36),c.strokeStyle=c.fillStyle="#FFF",c.beginPath(),c.moveTo(624,166),c.lineTo(657,130),c.lineTo(657,166),c.lineTo(624,166),c.lineWidth=1,c.stroke(),c.fill(),c.closePath()}();</script>
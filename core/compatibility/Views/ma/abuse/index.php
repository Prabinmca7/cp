<?php
$publicKey = '6LcHf0wUAAAAAFxRHKequHkHUGyUWA5dy24ZaB5s';
$server = (isRequestHttps() ? "https:" : "http:") . "//www.google.com/recaptcha/api.js";
?>
<!DOCTYPE html>
<html>
  <head>
    <title>
    <?=getMessage(COMP_SUBMISSION_PLS_SATISFY_REQ_MSG)?>
    </title>
    <style type="text/css">
    #submitDiv
    {
      text-align:right;
      width:304px;
    }
    </style>
    <script src="<?=$server?>"></script>
  </head>
  <body>
    <p><?=getMessage(COMP_SUBMISSION_PLS_SATISFY_REQ_MSG)?></p>
    <form method="post">
      <?$fields = $_SERVER['REQUEST_METHOD'] === "POST" ? $_POST : $_GET;?>
      <?foreach ($fields as $key => $value):?>
          <?if (is_array($value)): ?>
              <?foreach ($value as $subkey => $subvalue):?>
                  <input name="<?=htmlspecialchars($key)?>[]" type="hidden" value="<?=htmlspecialchars($subvalue)?>" />
              <?endforeach;?>
          <?else: ?>
              <input name="<?=htmlspecialchars($key)?>" type="hidden" value="<?=htmlspecialchars($value)?>" />
          <?endif; ?>
      <?endforeach;?>
      <div class="g-recaptcha" data-sitekey="<?=$publicKey?>"></div>
      <noscript>
        <div>
          <div style="width: 302px; height: 422px; position: relative;">
            <div style="width: 302px; height: 422px; position: absolute;">
              <iframe src="https://www.google.com/recaptcha/api/fallback?k=<?=$publicKey?>"
                      frameborder="0" scrolling="no"
                      style="width: 302px; height:422px; border-style: none;">
              </iframe>
            </div>
          </div>
          <div style="width: 300px; height: 60px; border-style: none;
                         bottom: 12px; left: 25px; margin: 0px; padding: 0px; right: 25px;
                         background: #f9f9f9; border: 1px solid #c1c1c1; border-radius: 3px;">
            <textarea id="g-recaptcha-response" name="g-recaptcha-response"
                         class="g-recaptcha-response"
                         style="width: 250px; height: 40px; border: 1px solid #c1c1c1;
                                margin: 10px 25px; padding: 0px; resize: none;" >
            </textarea>
          </div>
        </div>
      </noscript>
      <div id="submitDiv">
        <input type="submit" value="<?=getMessage(OK_LBL) ?>" />
      </div>
    </form>
  </body>
</html>

<div  id="rn_chatVideoChatWrapper">
    <rn:block id="top"/>
    <?= $this->render('mediaHeader', array('videoChatScriptLocation' => \RightNow\Utils\Config::getConfig(VIDEO_CHAT_CLIENT_SCRIPT), 'useHttps' => ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443))) ?>

    <div  id="rn_<?=$this->instanceID;?>" class="<?= $this->classList ?>">
    </div>
    <rn:block id="bottom"/>
</div>

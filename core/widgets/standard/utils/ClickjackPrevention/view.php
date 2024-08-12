<?
// frame busting code added here to support widget use across all page sets
if ($this->data['attrs']['frame_options'] === "DENY" && empty($this->data['attrs']['allow_from'])):
?>
    <script type="text/javascript">
    <!--
    if (parent !== self) {
        top.location.href = location.href;
    }
    else if (top !== self) {
        top.location.href = self.document.location;
    }
    //-->
    </script>
<? elseif (!empty($this->data['attrs']['allow_from']) && ((!isset($this->data['fullUrlPath']['host']) || !in_array($this->data['fullUrlPath']['host'], $this->data['allowFromArray'])) && (in_array($this->data['wildCardFullUrl'], $this->data['allowFromArray']) && count($this->data['subDomain']) < 3))):
?>
        <script type="text/javascript">
        <!--
        if (parent !== self) {
            top.location.href = location.href;
        }
        else if (top !== self) {
            top.location.href = self.document.location;
        }
        //-->
        </script>
<? endif;?>
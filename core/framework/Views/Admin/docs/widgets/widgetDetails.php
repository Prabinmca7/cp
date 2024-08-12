<? use \RightNow\Utils\Config, \RightNow\Utils\Text; ?>
<? if(isset($previewFiles) && is_array($previewFiles) && count($previewFiles)): ?>
<section name="preview">
    <div class="rn_TagGalleryPreview">
        <h2><?= Config::getMessage(PREVIEW_LBL) ?></h2>
        <div class="content">
            <ul>
            <? foreach($previewFiles as $filename): ?>
                <li>
                    <img src='/ci/admin/docs/widgets/previewFile/<?= $filename ?>' />
                </li>
            <? endforeach; ?>
            </ul>
        </div>
    </div>
</section>
<? endif; ?>

<section name="info">
    <h2><?= Config::getMessage(INFORMATION_LBL) ?></h2>
    <div class="content">
        <? if (isset($info) && $info && $info['notes']): ?>
            <p><?= nl2br(htmlspecialchars($info['notes'], ENT_COMPAT, 'UTF-8', false)) ?></p>
        <? endif; ?>
        <pre>&lt;rn:widget path="<?= (Text::beginsWith($widgetLocation, 'custom/')) ? $widgetLocation : Text::getSubstringAfter($widgetLocation, 'standard/') ?>" /&gt;</pre>
        <div class="subSectionContainer">
            <? if ((isset($logicExtends) && is_array($logicExtends) && $logicExtends) || (isset($controllerExtends) && is_array($controllerExtends) && $controllerExtends)): ?>
            <div class="subSection">
                <h3><?= Config::getMessage(EXTENDS_FROM_LBL) ?></h3>
                <div class="content">
                    <? $controllerExtendsInfo = isset($controllerExtends) && $controllerExtends && is_array($controllerExtends) ? $controllerExtends : array(); ?>
                    <? $logicExtendsInfo = isset($logicExtends) && $logicExtends && is_array($logicExtends) ? $logicExtends : array(); ?>
                    <? $extendsInfo = (count($logicExtendsInfo) > count($controllerExtendsInfo)) ? $logicExtendsInfo : $controllerExtendsInfo; ?>
                    <? for ($i = 0; $i < count($extendsInfo); $i++): ?>
                        <? $name = dirname($extendsInfo[$i]); ?>
                        <a href="/ci/admin/versions/manage#widget=<?= urlencode($name) ?>"><?= $name ?></a>
                        <? if ($i < count($extendsInfo) - 1): ?> : <? endif; ?>
                    <? endfor; ?>
                </div>
            </div>
            <? endif; ?>
            <div class="subSection">
                <h3><?= Config::getMessage(WIDGET_PATH_LBL) ?></h3>
                <div class="content">
                    <?= $widgetLocation ?>
                </div>
            </div>
            <div class="minor subSection">
                <h3><?= Config::getMessage(CONTROLLER_LBL) ?></h3>
                <div class="content">
                    <?= $controllerLocation ?>
                    <? if (isset($controllerExtends) && $controllerExtends && $controllerExtends[0] !== $controllerLocation): ?>
                    <ul class="extendInfo">
                    <? foreach ($controllerExtends as $widget): ?>
                        <li><?= $widget ?></li>
                    <? endforeach; ?>
                    </ul>
                    <? endif; ?>
                </div>
            </div>
            <? if ((isset($jsPath) && $jsPath) || (isset($logicExtends) && $logicExtends)): ?>
            <div class="minor subSection">
                <h3><?= Config::getMessage(JAVASCRIPT_LBL) ?></h3>
                <div class="content">
                    <?= isset($jsPath) ? $jsPath : $logicExtends[0] ?>
                    <? if (isset($logicExtends) && $logicExtends && isset($jsPath) && $jsPath): ?>
                    <ul class="extendInfo">
                    <? foreach($logicExtends as $widget): ?>
                        <li><?= $widget ?></li>
                    <? endforeach; ?>
                    </ul>
                    <? endif; ?>
                </div>
            </div>
            <? endif; ?>
            <? if ((isset($viewPath) && $viewPath) || (isset($viewExtends) && $viewExtends)): ?>
            <div class="minor subSection">
                <h3><?= Config::getMessage(VIEW_CMD) ?></h3>
                <div class="content">
                    <?= isset($viewPath) ? $viewPath : $viewExtends[0] ?>
                    <? if (isset($viewExtends) && $viewExtends && isset($viewPath) && $viewPath): ?>
                    <ul class="extendInfo">
                    <? foreach($viewExtends as $widget): ?>
                        <li><?= $widget ?></li>
                    <? endforeach; ?>
                    </ul>
                    <? endif; ?>
                </div>
            </div>
            <? endif; ?>
            <? if (isset($jsTemplates) && is_array($jsTemplates) && count($jsTemplates)): ?>
            <div class="minor subSection">
                <h3><?= Config::getMessage(JAVASCRIPT_TEMPLATE_LBL) ?></h3>
                <div class="content">
                <? foreach($jsTemplates as $templateFile): ?>
                    <?= $templateFile ?><br />
                <? endforeach; ?>
                </div>
            </div>
            <? endif; ?>
            <? if ((isset($baseCss) && count($baseCss)) || (isset($baseCssExtends) && count($baseCssExtends))): ?>
            <div class="minor subSection">
                <h3><?= Config::getMessage(BASE_CSS_LBL) ?></h3>
                <div class="content">
                <? if (isset($baseCss) && is_array($baseCss) && count($baseCss)): ?>
                    <? foreach ($baseCss as $cssFile): ?>
                        <?= $cssFile ?><br />
                    <? endforeach; ?>
                <? else: ?>
                    <em><?= Config::getMessage(NO_BASE_CSS_FILE_SPECIFIED_WIDGET_MSG) ?></em>
                <? endif; ?>
                <? if (isset($baseCssExtends) && is_array($baseCssExtends) && count($baseCssExtends)): ?>
                    <ul class="extendInfo">
                    <? foreach ($baseCssExtends as $parentCssFile): ?>
                        <li><?= $parentCssFile ?></li>
                    <? endforeach; ?>
                    </ul>
                <? endif; ?>
                </div>
            </div>
            <? endif; ?>
            <? if ((isset($presentationCss) && count($presentationCss)) || (isset($presentationCssExtends) && count($presentationCssExtends))): ?>
            <div class="minor subSection">
                <h3><?= Config::getMessage(PRESENTATION_CSS_LBL) ?></h3>
                <div class="content">
                <? if (count($presentationCss)): ?>
                    <? foreach ($presentationCss as $cssFile): ?>
                        <?= $cssFile ?><br />
                    <? endforeach; ?>
                <? else: ?>
                    <em><?= Config::getMessage(PRESENTATION_CSS_FILE_WIDGET_MSG) ?></em>
                <? endif; ?>
                <? if (isset($presentationCssExtends) && is_array($presentationCssExtends) && count($presentationCssExtends)): ?>
                    <ul class="extendInfo">
                    <? foreach ($presentationCssExtends as $parentCssFile): ?>
                        <li><?= $parentCssFile ?></li>
                    <? endforeach; ?>
                    </ul>
                <? endif; ?>
                </div>
            </div>
            <? endif; ?>
            <? if (isset($cssPath) && $cssPath): ?>
            <div class="minor subSection">
                <h3><?= Config::getMessage(CSS_PATH_LBL) ?></h3>
                <div class="content">
                    <?= $cssPath ?>
                </div>
            </div>
            <? endif; ?>
            <div class="subSection">
                <h3><?= Config::getMessage(CONTROLLER_CLASS_LBL) ?></h3>
                <div class="content">
                <? if (Text::beginsWith($widgetLocation, 'custom/') && strtolower(basename($widgetLocation)) === strtolower($controllerClass)): ?>
                    \Custom\Widgets\<?= implode('\\', array_slice(explode('/', $widgetLocation), 1, -1)) . "\\$controllerClass" ?>
                <? else: ?>
                    \RightNow\Widgets\<?= $controllerClass ?>
                <? endif; ?>
                </div>
            </div>
            <? if (count($containingWidgets)): ?>
            <div class="containingWidgets subSection">
                <h3><?= Config::getMessage(CONTAINING_WIDGETS_LBL) ?></h3>
                <div class="content">
                    <p><?= Config::getMessage(FOLLOWING_WIDGETS_WIDGET_VIEW_SEL_MSG) ?></p>
                    <div class="detailedDescription">
                        <p><?= sprintf(Config::getMessage(SET_CONTAINED_WIDGETS_ATTRIB_PCT_S_MSG), 'sub_id') ?></p>
                        <div class="codeExample">sub:{sub_id}:{contained_widget_attribute}="Value being set"</div>
                        <p><?= Config::getMessage(EX_ADVANCEDSEARCHDIALOG_WIDGET_SET_LBL) ?></p>
                        <div class="codeExample">sub:prod:label_input="Custom Product Label"</div>
                    </div>
                <? foreach ($containingWidgets as $widget): ?>
                    <div>
                        <?
                            $widgetTag = htmlspecialchars(str_replace("\n", "", $widget['match']), ENT_QUOTES, 'UTF-8');
                            foreach ($widget['attributes'] as $attr) {
                                $widgetTag = str_replace(htmlspecialchars($attr, ENT_QUOTES, 'UTF-8'), "\n\t   <strong>{$attr}</strong>", $widgetTag);
                            }
                            $widgetTag = str_replace($widget['matchedPath'], "<a href='/ci/admin/versions/manage#widget={$widget['path']}'>{$widget['matchedPath']}</a>", $widgetTag);
                        ?>
                        <div class="filename"><?= $widget['file'] ?></div>
                        <pre><?= $widgetTag ?></pre>
                        <? if ($widget['description']): ?>
                            <div class="containingWidgetsDescription"><?= $widget['description'] ?></div>
                        <? endif; ?>
                    </div>
                <? endforeach; ?>
                </div>
            </div>
            <? endif; ?>
        </div>
    </div>
</section>
<?
    $attributesExist = false;
    foreach($attributes as $bucket):
        foreach($bucket['values'] as $val):
            $attributesExist = true;
            break;
        endforeach;
        if($attributesExist):
            break;
        endif;
    endforeach;
?>
<? if($attributesExist || count($containingWidgets)): ?>
<section name="attrs">
    <h2><?= Config::getMessage(ATTRIBUTES_LBL) ?></h2>
    <div class="content">
        <? if($attributesExist): ?>
            <? foreach($attributes as $type => $bucket): ?>
                <? if (!$bucket['values']) continue; ?>
            <h3 class="bucketName">
                <?= $bucket['label'] ?>
            </h3>
            <div class="bucket">
                <? if($type === 'labels'): ?>
                <div class="labelsAccessibilityWarning">
                    <?= Config::getMessage(NOTE_MAINTAIN_ACCESSIBILITY_MSG) ?>
                </div>
                <? endif; ?>
                <? foreach($bucket['values'] as $name => $val): ?>
                <? $val->type = strtoupper($val->type); ?>
                <b>
                    <?= $name ?>
                    <? if ($val->inherited): ?>
                        <small>(<?= Config::getMessage(INHERITED_LBL)?>)</small>
                    <? endif; ?>
                </b>
                <ul>
                    <li><b><?= Config::getMessage(NAME_LBL) ?>:</b> <?= $val->name ?></li>
                    <li><b><?= Config::getMessage(TYPE_LBL) ?>:</b> <?= $val->type ?></li>
                    <li class="widgetDescription"><b><?= Config::getMessage(DESCRIPTION_LBL) ?>:</b> <?= $val->displaySpecialCharsInTagGallery ? $val->tooltip : htmlspecialchars($val->tooltip, ENT_COMPAT, 'UTF-8', false) ?></li>
                    <li><b><?= Config::getMessage(DEFAULT_LBL) ?>:</b>
                    <? if($val->default === true): ?>
                        true
                    <? elseif($val->default === false): ?>
                        false
                    <? elseif(is_array($val->default)): ?>
                        <? for($i = 0; $i < count($val->default); $i++): ?>
                            <? if($val->default[$i] === null || $val->default[$i] === ''): ?><span class="italic"><?= Config::GetMessage(NOT_SET_UC_LBL);?></span><? else: ?><?= $val->default[$i] ?><? endif; ?><? if($i + 1 < count($val->default)): ?>, <? endif; ?>
                        <? endfor; ?>
                    <? else: ?>
                        <?= htmlspecialchars(isset($val->default) ? $val->default : '', ENT_COMPAT, 'UTF-8', false) ?>
                    <? endif; ?>
                    </li>
                <? if(count($val->options)): ?>
                    <li>
                        <b><?= Config::getMessage(POSSIBLE_VALUES_LBL) ?>:</b>
                        <? if($val->type === 'MULTIOPTION'): ?>
                            <small><?= Config::getMessage(SPECIFY_MULTIPLE_OPTIONS_COMM_S_LIST_LBL); ?></small>
                        <? endif; ?>
                    </li>
                    <ul>
                    <? foreach($val->options as $option): ?>
                        <li>
                        <?if($option === null):?>
                            <span class="italic"><?= Config::GetMessage(NOT_SET_UC_LBL);?></span>
                        <?else:?>
                            <?= (Text::beginsWith($option, 'rn:msg:') ? Config::getMessage(constant(Text::getSubstringAfter($option, 'rn:msg:'))) : (Text::beginsWith($option, 'rn:astr:') ? Text::getSubstringAfter($option, 'rn:astr:') : $option)) ?>
                        <?endif;?>
                        </li>
                    <? endforeach; ?>
                    </ul>
                <? endif; ?>
                <? if ($val->required): ?>
                    <li><span class="highlight"><?= Config::getMessage(VALUE_IS_REQUIRED_ATTRIBUTE_MSG) ?></span></li>
                <? endif; ?>
                <? if(isset($val->min)): ?>
                    <li><b><?= Config::getMessage(MN_LBL) ?>:</b> <?= $val->min ?></li>
                <? endif; ?>
                <? if(isset($val->max)): ?>
                    <li><b><?= Config::getMessage(MAX_LBL) ?>:</b> <?= $val->max ?></li>
                <? endif; ?>
                <? if(isset($val->length)): ?>
                    <li><b><?= Config::getMessage(LENGTH_LBL) ?>:</b> <?= $val->length ?></li>
                <? endif; ?>
                </ul>
                <? endforeach; ?>
            </div>
            <? endforeach; ?>
        <? else: ?>
            <?= Config::getMessage(NOTE_ATTRIB_DEFINED_WIDGET_CONT_MSG) ?>
        <? endif; ?>
    </div>
</section>
<? endif; ?>

<? if(isset($urlParameters) && is_array($urlParameters) && count($urlParameters)): ?>
<section name="urlParams">
    <h2><?= Config::getMessage(URL_PARAMETERS_LBL) ?></h2>
    <div class="content">
    <? foreach($urlParameters as $parameter): ?>
        <h3><?= $parameter->name ?></h3>
        <ul>
            <li><?= $parameter->description ?></li>
        <? if (isset($parameter->required) && $parameter->required): ?>
            <li><span class="highlight"><?= Config::getMessage(VAL_REQD_URL_PARAM_ORDER_WIDGET_MSG) ?></span></li>
        <? endif; ?>
            <li><b><?= Config::getMessage(EXAMPLE_UC_LBL) ?>:</b> <?= $parameter->example ?></li>
        </ul>
    <? endforeach; ?>
    </div>
</section>
<? endif; ?>

<? if (is_array($events) && (count($events['fire']) || count($events['subscribe']))): ?>
<section name="events">
    <h2><?= Config::getMessage(JAVASCRIPT_EVENTS_LBL) ?></h2>
    <div class="content">
    <? if(count($events['fire'])): ?>
        <h3><?= Config::getMessage(FIRED_EVENTS_LBL) ?></h3>
        <ul>
            <? foreach($events['fire'] as $fire): ?>
                <li><?= $fire?></li>
            <? endforeach; ?>
        </ul>
    <? endif; ?>

    <? if(count($events['subscribe'])): ?>
        <h3><?= Config::getMessage(SUBSCRIBED_EVENTS_LBL) ?></h3>
        <ul>
        <? foreach($events['subscribe'] as $subscribe): ?>
            <li><?= $subscribe?></li>
        <? endforeach; ?>
        </ul>
    <? endif; ?>
    </div>
</section>
<? endif; ?>

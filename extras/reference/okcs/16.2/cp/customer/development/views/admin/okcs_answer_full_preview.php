<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <title><?=\RightNow\Utils\Config::getMessage(ANSWER_PRINT_PAGE_LBL);?></title>
    <style type='text/css'>
        .rn_ScreenReaderOnly{position:absolute; height:1px; left:-10000px; overflow:hidden; top:auto; width:1px;}
        .rn_Hidden{display:none;}
        .rn_AnswerInfo {color:#666;margin:6px 0 20px;}
        .rn_Info {width: 30%;display: inline-block;margin-top: 10px;}
        .rn_SchemaAttribute {margin-bottom: 5px;font-weight: bold;}
        .rn_SchemaAttributeValue {margin-bottom: 10px;}
        .rn_Indent {margin-left: 20px;}
        .rn_SectionTitle {border-top: 1px solid #DDD;clear: both;padding: 10px 0;text-align: right;}
        .rn_AnswerHeader section {display: inline-block;padding: 3px;width: 40%;word-wrap: break-word;}
        .rn_AnswerHeader h1 {font-size: 1.7em;margin: 15px 0;}
        .rn_AnswerView {margin: 0 10px;}
    </style>
    <link href="/euf/assets/themes/standard/site.css" rel="stylesheet" type="text/css" media="all" />
</head>

<body>
<div id="rn_Container">
    <div id="rn_Header"></div>
    <div id="rn_Navigation"></div>
    <div id="rn_Body">
        <div id="rn_MainColumn">
            <div class="rn_AnswerView">
                <div class="rn_AnswerDetail rn_AnswerHeader">
                    <h1 id="rn_Summary"><?=$data['title']?></h1>
                    <div class="rn_AnswerInfo">
                        <span id="docIdHeader">
                            <section>
                                <span class="rn_Info rn_Bold"><?= \RightNow\Utils\Config::getMessage(DOCUMENT_ID_LBL);?></span>
                                <span><?= $data['docID']?></span>
                            </section>
                        </span>
                        <span id="versionHeader">
                            <section>
                                <span class="rn_Info rn_Bold"><?= \RightNow\Utils\Config::getMessage(VERSION_LBL);?></span>
                                <span><?= $data['version']?></span>
                            </section>
                        </span>
                        <span id="statusHeader">
                            <section>
                                <span class="rn_Info rn_Bold"><?= \RightNow\Utils\Config::getMessage(STATUS_LBL);?></span>
                                <span><?= $data['published']?></span>
                            </section>
                        </span>
                        <span id="publishedDateHeader">
                            <section>
                                <span class="rn_Info rn_Bold"><?= \RightNow\Utils\Config::getMessage(PUBLISHED_DATE_LBL);?></span>
                                <span><?= $data['publishedDate']?></span>
                            </section>
                        </span>
                    </div>
                </div>
                <div class="rn_SectionTitle"></div>
                <div id="content">
                <? $previousDepth = 1; ?>
                <? $currentIndex = 1; ?>
                <? foreach ($data['data'] as $data): ?>
                    <? if ($data['type'] === 'META'): ?>
                        <div class="rn_SectionTitle"></div>
                    <? endif; ?>
                    <? foreach ($data['contentSchema'] as $schemaPath): ?>
                        <? $schemaXpath = str_replace('//', '', $schemaPath); ?>
                        <? $attribute = $data['content'][$schemaXpath]; ?>
                        <? if ($attribute['depth'] > 0) : ?>
                            <? $value = $attribute['value']; ?>
                            <? if($attribute['depth'] > $previousDepth ) : ?>
                                <div class="rn_Indent">
                            <? endif; ?>
                            <? if($attribute['depth'] < $previousDepth) : ?>
                                <? $diff = $previousDepth - $attribute['depth']; ?>
                                <? for($i = $diff; $i > 0; $i--) : ?>
                                    </div>
                                <? endfor; ?>
                            <? endif; ?>

                            <? if($attribute['xPath'] !== '') : ?>
                                <?$className = ucwords(strtolower($attribute['xPath']));?>
                                <?$index = 0;?>
                                <? foreach(array('_', '/') as $delimiter) : ?>
                                    <? if(strpos($className, $delimiter)) : ?>
                                        <?$className = implode('_', array_map('ucfirst', explode($delimiter, $className)));?>
                                    <? endif; ?>
                                    <? $index++;?>
                                <? endforeach; ?>
                                <? $className = 'rn_AnswerField_' . $className; ?>
                            <? endif; ?>

                            <div class="<?= $className?>">
                                <div class='rn_SchemaAttribute'>
                                    <? if ($type === 'CHECKBOX') : ?>
                                        <? $checkboxID = $className . $index;
                                            $checked = $value === 'Y' ? 'checked' : '';
                                        ?>
                                        <label for="<?= $checkboxID ?>" class="rn_AttributeCheckboxLabel"><?= $attribute['name'] ?></label>
                                        <input id="<?= $checkboxID ?>" type="checkbox" disabled <?= $checked ?> class="rn_AttributeCheckbox"/>
                                    <? else: ?>
                                        <?= $attribute['name'] ?>
                                    <? endif; ?>
                                </div>
                                <? if($this->data['attrs']['type'] !== 'NODE') : ?>
                                    <div class='rn_SchemaAttributeValue'>
                                        <? if ($type === 'FILE') : ?>
                                            <a target='_blank' href="/ci/okcsFattach/get/<?=$attribute['encryptedPath'];?>"><?=$attribute['value'];?></a>
                                        <? elseif ($type !== 'CHECKBOX') : ?>
                                            <?= $value ?>
                                        <? endif; ?>
                                    </div>
                                <? endif; ?>
                            </div>
                            
                            <? if (count($data['contentSchema']) === $currentIndex) : ?>
                                <? for($i = $attribute['depth']; $i > 1; $i--) : ?>
                                    </div>
                                <? endfor; ?>
                            <? endif; ?>
                            <? $previousDepth = $attribute['depth']; ?>
                        <? endif; ?>
                        <? $currentIndex++; ?>
                    <? endforeach; ?>
                <? endforeach; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
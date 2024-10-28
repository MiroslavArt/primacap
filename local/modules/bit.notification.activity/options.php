<?php
global $APPLICATION;

use Bitrix\Main\Localization\Loc as Loc;
use Bitrix\Main\Application;
use Bitrix\Main\Loader as Loader;
use Bitrix\Main\Config\Option as Option;

$module_id = 'bit.notification.activity';
Loader::includeModule($module_id);
Loader::includeModule('crm');

$request = Application::getInstance()->getContext()->getRequest();
$request = $request->getPostList();

foreach ($request as $key => $arParams)
{
    if($key === 'note_act')
    {
        $pathParamName = 'note_act_';

        foreach ($arParams as $key => $data)
        {
            $paramKey = $pathParamName . $data['type'];

            if($data['type'] == 'N')
            {
                continue;
            }

            if($data['del'] == 'Y')
            {
                Option::set($module_id, $paramKey, '');
                continue;
            }


            $paramValue = [
                $data['type'],
                $data['time'],
                $data['text'],
            ];
            $paramValue = serialize($paramValue);
            Option::set($module_id, $paramKey, $paramValue);
        }
    }
    else if($key == 'main')
    {
        foreach ($arParams as $key => $data)
        {
            Option::set($module_id, $key, $data);
        }
    }
}

$arProvider = \Bitrix\Crm\Activity\Provider\ProviderManager::getCompletableProviderList();
$jsProviderData = [];
$jsIssetData = [];

foreach ($arProvider as $keyProvider => $providerData)
{
    $providerEntity = \CCrmActivity::GetProviderById($providerData['ID']);
    $providerClass = new $providerEntity();
    $method = 'canKeepReassignedInCalendar';

    if($providerClass->$method())
    {
        $jsProviderData[$providerData['ID']] = $providerData;

        $paramName = 'note_act_' . $providerData['ID'];
        $jsData = Option::get($module_id, $paramName, '');
        $jsData = unserialize($jsData);
        if(!empty($jsData))
        {
            $jsIssetData[$providerData['ID']] = [
                'type' => $providerData['ID'],
                'time' => $jsData[1],
                'text' => $jsData[2],
            ];
        }

    }
    //*/
}

$aTabResult = '<tr>
    <td></td>
    <td></td>
    <td><input class="add-new-note-rule" type="button" value="Add new rule"></td>
    <td></td>
    <td></td>
</tr>';

$optionsMain[] = 'Setting';
$optionsMain[] = array(
    "main[enabled]",
    'Enable',
    Option::get($module_id, 'enabled', 'N'),
    array("selectbox", ['N'=> 'No', 'Y' => 'Yes'])
);
$aTabs[] = [
    "DIV"       => "note_act_main",
    "TAB"       => 'Main setting',
    "TITLE"     => 'Main setting',
    "OPTIONS"   => $optionsMain,
];
$aTabs[] = [
    "DIV"       => "note_act",
    "TAB"       => 'Notification activity',
    "TITLE"     => 'Setting note',
    "HTML"   => $aTabResult,
];



$jsPattern = \Bit\Notification\Activity\Main::getNoteActPatterns();

$tabControl = new CAdminTabControl(
    "tabControl",
    $aTabs
);

$tabControl->Begin(); ?>
    <script type="text/javascript">
        BX.ready(function (){

            var BitNoteAct = {

                data : {
                    countID : 0,
                    provider : <?= count($jsProviderData) > 0 ? json_encode($jsProviderData) : '{}' ?>,
                    pattern : <?= count($jsPattern) > 0 ? json_encode($jsPattern) : '{}' ?>,
                    issetData : <?= count($jsIssetData) > 0 ? json_encode($jsIssetData) : '{}' ?>,
                },

                init : function (){

                    BX.bindDelegate(document.body, 'click', { className : 'add-new-note-rule' } ,function()
                    {
                        BX.BitNoteAct.createRow(this.parentElement.parentElement);
                    });

                    BX.bindDelegate(document.body, 'click', { className : 'delete-row' } ,function(){

                        let nodeTr = this.parentElement.parentElement.parentElement;
                        nodeTr.remove();
                    });

                    BX.bindDelegate(document.body, 'click', { className : 'hide-row' } ,function(){

                        let nodeTr = this.parentElement.parentElement.parentElement;

                        nodeTr.classList.toggle('remove');
                        let tabContainer = document.querySelector('#note_act_edit_table');
                        let name = this.dataset['path_name'] + '[del]';

                        if(nodeTr.classList.contains('remove'))
                        {
                            let delNode = document.createElement('input');
                            delNode.name = name;
                            delNode.value = 'Y';
                            delNode.type = 'hidden';
                            tabContainer.prepend(delNode);

                            this.value = 'Restore';
                        }
                        else
                        {
                            if(tabContainer.querySelector('[name="'+name+'"]'))
                            {
                                tabContainer.querySelector('[name="'+name+'"]').remove();
                            }
                            this.value = 'Delete';
                        }
                    });
                    BX.bindDelegate(document.body, 'click', { className : 'add-note-act-pattern' } ,function(){

                        let text = this.dataset['insert_text'];
                        let nameNode = this.dataset['insert_node'];

                        let textNode = document.querySelector('[name="'+nameNode+'"]');
                        if(textNode)
                        {
                            let indexInsert = textNode.selectionStart ? textNode.selectionStart : 0;
                            textNode.value = textNode.value.substring(0,indexInsert) + text + textNode.value.substring(indexInsert);
                        }

                    });
                    BX.bindDelegate(document.body, 'change', { className : 'fixed-select-value' } ,function()
                    {
                        let selectedItem = this.selectedOptions[0];

                        let value = selectedItem.value;
                        let name = selectedItem.innerText;
                        let nameElem = this.dataset['name'];

                        let hiddenNode = document.createElement('input');
                        hiddenNode.type = 'hidden';
                        hiddenNode.name = nameElem;
                        hiddenNode.value = value;

                        let titleNode = document.createElement('span');
                        titleNode.innerText = name;


                        let parentContainer = this.parentElement;
                        parentContainer.append(hiddenNode);
                        parentContainer.append(titleNode);

                        this.remove();
                    });

                    this.createIsset();

                    //
                },

                createIsset : function ()
                {
                    let node = document.querySelector('.add-new-note-rule');
                    node = node.parentElement.parentElement;

                    for (let index in this.data.issetData)
                    {
                        let item = this.data.issetData[index];

                        this.createRow(node, item);


                    }
                },

                getNewRowID : function ()
                {
                    this.data.countID++;
                    return this.data.countID;
                },

                createRow : function (insertNode, data = {})
                {
                    let rowID = this.getNewRowID();

                    if(!insertNode.parentElement.querySelector('.header_table'))
                    {
                        this.createHeaderTable(insertNode);
                    }

                    data['id'] = rowID;

                    let container = document.createElement('tr');
                    let containerType = this.createTypeNode(data);
                    let containerTime = this.createTimeNode(data);
                    let containerContent = this.createContentNode(data);
                    let containerPattern  = this.createPatternNode(data);
                    let containerButton  = this.createButtonNode(data);


                    container.append(containerType);
                    container.append(containerTime);
                    container.append(containerContent);
                    container.append(containerPattern);
                    container.append(containerButton);

                    insertNode.insertAdjacentElement('beforeBegin', container);
                },

                createHeaderTable : function (insertNode)
                {
                    let container = document.createElement('tr');
                    container.classList.add('header_table');

                    container.innerHTML = '' +
                        '<td>Type activity</td>' +
                        '<td>Note time (minutes)</td>' +
                        '<td>Note content</td>' +
                        '<td>Patterns</td>' +
                        '<td>Actions</td>';

                    insertNode.insertAdjacentElement('beforeBegin', container);
                },

                createTypeNode : function (data = {})
                {
                    let container = document.createElement('td');

                    let containerContent = document.createElement('div');
                    let rowID = data['id'];

                    if(data['type'] === undefined)
                    {
                        let selectNode = document.createElement('select');
                        selectNode.dataset['name'] = 'note_act['+rowID+'][type]';
                        selectNode.name = 'note_act['+rowID+'][type]';
                        selectNode.classList.add('fixed-select-value');

                        let optionDef = document.createElement('option');
                        optionDef.value = 'N';
                        optionDef.innerText = ' - Select activity - ';
                        selectNode.append(optionDef);

                        for (let index in this.data.provider)
                        {
                            let item = this.data.provider[index];
                            let option = document.createElement('option');
                            option.value = item['ID'];
                            option.innerText = item['NAME'];
                            selectNode.append(option);
                        }

                        containerContent.append(selectNode);
                    }
                    else
                    {
                        let nodeInput = document.createElement('input');
                        nodeInput.value = data['type'];
                        nodeInput.type = 'hidden';
                        nodeInput.name = 'note_act['+rowID+'][type]';

                        let nodeTitle = document.createElement('span');
                        nodeTitle.innerText = this.data.provider[data['type']]['NAME'];

                        containerContent.append(nodeInput);
                        containerContent.append(nodeTitle);
                    }

                    container.append(containerContent);

                    return container;

                },
                createTimeNode : function (data ={})
                {
                    let container = document.createElement('td');

                    let containerContent = document.createElement('div');
                    let rowID = data['id'];

                    let defValue = 5;

                    if(data['time'] != undefined)
                    {
                        defValue = data['time'];
                    }

                    let inputNode = document.createElement('input');
                    inputNode.type = 'number';
                    inputNode.classList.add('note_act_number')
                    inputNode.name = 'note_act['+rowID+'][time]';
                    inputNode.min = 1;
                    inputNode.max = 1440;
                    inputNode.value = defValue;
                    containerContent.append(inputNode);

                    container.append(containerContent);
                    return container;
                },
                createContentNode : function (data = {})
                {
                    let container = document.createElement('td');

                    let containerContent = document.createElement('div');
                    let rowID = data['id'];

                    let defValue = '';

                    if(data['text'] != undefined)
                    {
                        defValue = data['text'];
                    }

                    let textNode = document.createElement('textarea');
                    textNode.classList.add('note_act_textarea')
                    textNode.name = 'note_act['+rowID+'][text]';
                    textNode.rows = 8;
                    textNode.value = defValue;


                    containerContent.append(textNode);

                    container.append(containerContent);

                    return container;
                },
                createPatternNode : function (data = {})
                {
                    let container = document.createElement('td');

                    let rowID = data['id'];

                    let containerContent = document.createElement('div');
                    containerContent.classList.add('container_pattern');

                    for(let index in this.data.pattern)
                    {
                        let item = this.data.pattern[index];

                        let patternNode = document.createElement('div');
                        patternNode.innerText = item['PATTERN'];
                        patternNode.title = item['TITLE'];
                        patternNode.dataset['insert_text'] = item['INSERT'];
                        patternNode.dataset['insert_node'] = 'note_act['+rowID+'][text]';

                        patternNode.classList.add('add-note-act-pattern', 'pattern_item');

                        containerContent.append(patternNode);
                    }

                    container.append(containerContent);

                    return container;
                },
                createButtonNode : function (data = {})
                {
                    let container = document.createElement('td');

                    let containerContent = document.createElement('div');
                    let rowID = data['id'];

                    let deleteButtonNode = document.createElement('input');
                    if(data['type'] != undefined)
                    {
                        deleteButtonNode.value = 'Delete';
                        deleteButtonNode.classList.add('hide-row');
                    }
                    else
                    {
                        deleteButtonNode.classList.add('delete-row');
                        deleteButtonNode.value = 'Clear';
                    }

                    deleteButtonNode.title = 'Delete row';
                    deleteButtonNode.type = 'button';

                    deleteButtonNode.dataset['path_name'] = 'note_act['+ rowID+']';
                    containerContent.append(deleteButtonNode);

                    container.append(containerContent);

                    return container;
                },
            };


            BX.BitNoteAct = BitNoteAct;
            BX.BitNoteAct.init();

        });

    </script>
    <style type="text/css">
        #note_act_edit_table td{
            text-align: center;
        }

        #note_act_edit_table .remove td > div{
            display: none;
        }
        #note_act_edit_table .remove td:last-child > div{
            display: block;
        }

        #note_act_edit_table tr td:nth-child(1),
        #note_act_edit_table tr td:nth-child(2){
            width: 150px;
        }
        #note_act_edit_table tr td:nth-child(4){
            width: 200px;
        }
        #note_act_edit_table tr td:last-child{
            width: 150px;
        }
        .note_act_number{
            padding: 5px;
        }
        .note_act_textarea{
            width: calc(100% - 30px);
            resize: vertical;
        }
        .header_table td{
            padding: 10px;
            /*text-align: center;*/
            font-weight: bold;
        }
        .header_table{
            /*background: lightgrey;*/
        }
        .container_pattern{
            text-align: left;
        }
        .container_pattern .pattern_item{
            margin-bottom: 0.3em;
            cursor: pointer;
        }
        .container_pattern .pattern_item:hover{
            font-weight: bold;
        }
        #note_act_edit_table td > div{
            margin-bottom: 1.5em;
        }
    </style>
    <form class="" action="<? echo($APPLICATION->GetCurPage()); ?>?mid=<? echo($module_id); ?>&lang=<? echo(LANG); ?>" method="post">
        <? foreach($aTabs as $aTab) :

            if($aTab["HTML"])
            {
                $tabControl->BeginNextTab();
                echo $aTab['HTML'];
            }
            else if($aTab["OPTIONS"])
            {
                $tabControl->BeginNextTab();
                __AdmSettingsDrawList($module_id, $aTab["OPTIONS"]);
            }
        endforeach;
        $tabControl->Buttons();
        ?>
        <input type="submit" value="Сохранить" class="adm-btn-save" />
        <? echo(bitrix_sessid_post()); ?>
    </form>
<?php $tabControl->End(); ?>



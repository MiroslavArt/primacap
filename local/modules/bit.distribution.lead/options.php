<?php
global $APPLICATION;

use Bitrix\Main\Localization\Loc as Loc;
use Bitrix\Main\Application;
use Bitrix\Main\Loader as Loader;
use Bitrix\Main\Config\Option as Option;

$module_id = 'bit.distribution.lead';
Loader::includeModule($module_id);
Loader::includeModule('crm');

$request = Application::getInstance()->getContext()->getRequest();
$request = $request->getPostList();

if(isset($request['main']))
{
    $rs = \Bit\Distribution\Lead\DistributionTable::getList();
    while ($ar = $rs->fetch())
    {
        \Bit\Distribution\Lead\DistributionTable::delete($ar['ID']);
    }
}
foreach ($request as $key => $arParams)
{
    if($key === 'main')
    {
        foreach ($arParams as $paramKey => $paramValue)
        {
            switch ($paramKey)
            {
                case 'count_per_user':
                case 'count_per_project':
                case 'agent_workflow_id':
                    $paramValue = (int)$paramValue;
                    if($paramValue <=0)
                    {
                        $paramValue = 0;
                    }
                    break;
                default:
                    break;
            }
            Option::set($module_id, $paramKey, $paramValue);
        }
    }
    else if($key === 'project')
    {
        $issetData = [];
        foreach ($arParams as $rowData)
        {
            if (!isset($rowData['project_name']))
            {
                continue;
            }

            if(isset($issetData[$rowData['project_name']][$rowData['user_id']]))
            {
                continue;
            }
            $issetData[$rowData['project_name']][$rowData['user_id']] = 'Y';

            $data = [
                'TYPE' => \Bit\Distribution\Lead\DistributionTable::TYPE_PROJECT,
                'VALUE'=> $rowData['project_name'],
                'USER_ID'=> $rowData['user_id'],
                'COUNT' => $rowData['count'],
            ];
            \Bit\Distribution\Lead\DistributionTable::add($data);
        }
    }
    else if($key === 'user')
    {
        $issetData = [];
        foreach ($arParams as $rowData)
        {
            if (!isset($rowData['user_id']))
            {
                continue;
            }

            if(isset($issetData[$rowData['user_id']]))
            {
                continue;
            }
            $issetData[$rowData['user_id']] = 'Y';

            $data = [
                'TYPE' => \Bit\Distribution\Lead\DistributionTable::TYPE_USER,
                'VALUE'=> $rowData['user_id'],
                'USER_ID'=> $rowData['user_id'],
                'COUNT' => $rowData['count'],
            ];
            \Bit\Distribution\Lead\DistributionTable::add($data);
        }
    }
    else if($key === 'user_source')
    {
        $issetData = [];
        foreach ($arParams as $rowData)
        {
            if (!isset($rowData['user_id']))
            {
                continue;
            }

            if(isset($issetData[$rowData['user_id']][$rowData['source']]))
            {
                continue;
            }
            $issetData[$rowData['user_id']][$rowData['source']] = 'Y';

            $data = [
                'TYPE' => \Bit\Distribution\Lead\DistributionTable::TYPE_SOURCE,
                'VALUE'=> $rowData['source'],
                'USER_ID'=> $rowData['user_id'],
                'COUNT' => $rowData['count'],
            ];
            \Bit\Distribution\Lead\DistributionTable::add($data);
        }
    }
}

$arLeadSource = [];
$rs = CCrmStatus::GetList(
    [
        'SORT' => 'ASC'
    ],
    [
        'ENTITY_ID' => 'SOURCE'
    ]
);
while($ar = $rs->fetch())
{
    $arLeadSource[$ar['STATUS_ID']] = $ar['NAME'];
}

$arLeadStatus = [];
$rs = CCrmStatus::GetList(
    [
        'SORT' => 'ASC'
    ],
    [
        'ENTITY_ID' => 'STATUS'
    ]
);
while($ar = $rs->fetch())
{
    $arLeadStatus[$ar['STATUS_ID']] = $ar['NAME'];
}

$optionsMain[] = 'Setting';
$optionsMain[] = array(
    "main[enabled]",
    'Enable',
    Option::get($module_id, 'enabled', 'N'),
    array("selectbox", ['N'=> 'No', 'Y' => 'Yes'])
);
$optionsMain[] = 'Default number of leads';
$optionsMain[] = array(
    "main[count_per_user]",
    'Count per user',
    Option::get($module_id, 'count_per_user', '10'),
    array("text", 5)
);
$optionsMain[] = array(
    "main[count_per_project]",
    'Count per project',
    Option::get($module_id, 'count_per_project', '5'),
    array("text", 5)
);
$optionsMain[] = 'Agent setting';
$optionsMain[] = array(
    "main[check_lead_status]",
    'Check lead status',
    Option::get($module_id, 'check_lead_status', 'NEW'),
    array("selectbox", $arLeadStatus)
);
$optionsMain[] = array(
    "main[agent_workflow_id]",
    'Business process number for distribution',
    Option::get($module_id, 'agent_workflow_id', '0'),
    array("text", 5)
);
$aTabs = [
    array(
        "DIV"       => "main",
        "TAB"       => 'Main settings',
        "TITLE"     => 'Main settings',
        "OPTIONS"   => $optionsMain,
    ),
];

$arAllUserData = [];
$arAllProjectData = [];
$iblock_id = 29;
$rs = CIBlockElement::GetList(
    [
        'name' => 'ASC'
    ],
    [
        'IBLOCK_ID' => $iblock_id,
    ],
    false,
    false,
    ['ID','NAME', 'PROPERTY_RESPONSIBLE_PERSONS']
);
while($ar = $rs->Fetch())
{
    $userID = $ar['PROPERTY_RESPONSIBLE_PERSONS_VALUE'];
    if($userID > 0)
    {
        $arAllUserData[$userID] = [];

        $projectID = 'project_' . $ar['ID'];
        if (!isset($arAllProjectData[$projectID]))
        {
            $arAllProjectData[$projectID] = [
                'ID' => $projectID,
                'NAME' => $ar['NAME'],

            ];
        }
        $arAllProjectData[$projectID]['USERS'][] = $userID;
    }
}

$filter = [
    'ID' => implode('|', array_keys($arAllUserData)),
];
$arAllUserData = [];
$rsUsers = CUser::GetList(($by="name"), ($order="asc"), $filter);
while($arUsers = $rsUsers->Fetch())
{
    $id = $arUsers['ID'];
    $name = trim($arUsers['NAME'] . ' ' . $arUsers['LAST_NAME']);
    if(strlen($name) == 0)
    {
        $name = $arUsers['LOGIN'];
    }
    $name = '['.$id.'] ' . $name;

    $key = 'U' . $id;
    $arAllUserData[$key] = [
        'ID' => $id,
        'NAME' => $name,
    ];
}

$projectData = [];
$projectUserData = [];
$userData = [];
$userSourceData = [];
$rs = \Bit\Distribution\Lead\DistributionTable::getList();
while ($ar = $rs->fetch())
{
    if($ar['TYPE'] == \Bit\Distribution\Lead\DistributionTable::TYPE_PROJECT)
    {
        if ($ar['USER_ID'] == 0)
        {
            $projectData[] = [
                'VALUE' => $ar['VALUE'],
                'COUNT' => $ar['COUNT'],
            ];
        }
        else
        {
            $projectUserData[] = [
                'VALUE' => $ar['VALUE'],
                'USER_ID' => $ar['USER_ID'],
                'COUNT' => $ar['COUNT'],
            ];
        }
    }
    elseif ($ar['TYPE'] == \Bit\Distribution\Lead\DistributionTable::TYPE_USER)
    {
        $userData[] = [
            'USER_ID' => $ar['USER_ID'],
            'COUNT' => $ar['COUNT'],
        ];
    }
    elseif ($ar['TYPE'] == \Bit\Distribution\Lead\DistributionTable::TYPE_SOURCE)
    {
        $userSourceData[] = [
            'VALUE' => $ar['VALUE'],
            'USER_ID' => $ar['USER_ID'],
            'COUNT' => $ar['COUNT'],
        ];
    }

    $userKey = 'U' . $ar['USER_ID'];
    if($ar['USER_ID'] > 0 && !isset($arAllUserData[$userKey]))
    {
        $rs = CUser::GetByID($ar['USER_ID']);
        if($arUser = $rs->Fetch())
        {
            $name = trim($arUsers['NAME'] . ' ' . $arUsers['LAST_NAME']);
            if(strlen($name) == 0)
            {
                $name = $arUsers['LOGIN'];
            }
            $name = '['. $ar['USER_ID'] .'] ' . $name;
            $arAllUserData[$userKey] = [
                'ID' => $ar['USER_ID'],
                'NAME' => $name,
            ];
        }
        else
        {
            $name = '[' . $ar['USER_ID'] . '] Not found user';
            $arAllUserData[$userKey] = [
                'ID' => $ar['USER_ID'],
                'NAME' => $name,
            ];
        }
    }

}
/*------RROJECT----------------*/


$aTabProjectHtml = '<tr class="heading"><td colspan="2">Setting for Project</td></tr>';
$aTabProjectHtml .= '<tr>
    <td width="50%"></td>
    <td width="50%">
        <input class="add-new-project-limit" type="button" value="Add new Project limit">
    </td>
</tr>';
$aTabProjectHtml .= '<tr class="heading"><td colspan="2">Setting for Project user\'s</td></tr>';
$aTabProjectHtml .= '<tr>
    <td width="50%"></td>
    <td width="50%">
        <input class="add-new-project-user-limit" type="button" value="Add new Project limit for User">
    </td>
</tr>';
$aTabs[] = [
        "DIV"       => "project",
        "TAB"       => 'Project settings',
        "TITLE"     => 'Project settings',
        "HTML"   => $aTabProjectHtml,
];
/*-------USERS--------------------------------*/

$aTabUserHtml = '<tr class="heading"><td colspan="2">Setting for User</td></tr>';
$aTabUserHtml .= '<tr>
    <td width="50%"></td>
    <td width="50%">
        <input class="add-new-user-limit" type="button" value="Add new User limit">
    </td>
</tr>';
$aTabUserHtml .= '<tr class="heading"><td colspan="2">Setting for User by source</td></tr>';
$aTabUserHtml .= '<tr>
    <td width="50%"></td>
    <td width="50%">
        <input class="add-new-source-user-limit" type="button" value="Add new source limit">
    </td>
</tr>';
$aTabs[] = [
    "DIV"       => "user",
    "TAB"       => 'User\'s settings',
    "TITLE"     => 'User\'s settings',
    "HTML"   => $aTabUserHtml,
];
/*----RESULT---------------------*/

$arDistributionLead = [];
$rs = \Bit\Distribution\Lead\DistributionLeadTable::query()
    ->addSelect(Bitrix\Main\ORM\Query\Query::expr()->sum("COUNT"), 'CNT')
    ->addSelect('USER_ID')
    ->addGroup('USER_ID')
    ->exec();
while ($ar = $rs->fetch())
{
    $arDistributionLead[] = $ar;
}

$aTabResult = '<tr class="heading"><td colspan="2">Distribution lead</td></tr>';
foreach ($arDistributionLead as $row)
{
    $userKey = 'U' . $row['USER_ID'];
    $aTabResult .= '<tr><td width="50%">'. $arAllUserData[$userKey]['NAME'] .' : </td><td><b>'. $row['CNT'] .'</b></td></tr>';
}
if(count($arDistributionLead) == 0)
{
    $aTabResult .= '<tr><td width="50%">No distributed leads</td><td><b></b></td></tr>';
}

$aTabs[] = [
    "DIV"       => "today_distribution",
    "TAB"       => 'Today distribution lead',
    "TITLE"     => 'Distribution lead',
    "HTML"   => $aTabResult,
];





$tabControl = new CAdminTabControl(
    "tabControl",
    $aTabs
);

$tabControl->Begin(); ?>
    <script type="text/javascript">
        BX.ready(function (){

            var BitDistribution = {

                allUser : <?= count($arAllUserData) > 0 ? json_encode($arAllUserData) : '{}' ?>,
                allProject : <?= count($arAllProjectData) > 0 ? json_encode($arAllProjectData) : '{}' ?>,
                allLeadSource : <?= count($arLeadSource) > 0 ? json_encode($arLeadSource) : '{}' ?>,

                defaultProjectCount : 5,
                countProject : 1,
                issetProjectLimit : <?= count($projectData) > 0 ? json_encode($projectData) : '[]' ?>,
                issetProjectUserLimit : <?= count($projectUserData) > 0 ? json_encode($projectUserData) : '[]' ?>,
                issetUserLimit : <?= count($userData) > 0 ? json_encode($userData) : '[]' ?>,
                issetUserSourceLimit : <?= count($userSourceData) > 0 ? json_encode($userSourceData) : '[]' ?>,



                init : function (){

                    BX.bindDelegate(document.body, 'change', { className : 'select-of-values' } ,function()
                    {
                        let value = this.selectedOptions[0].value;
                        let title = this.selectedOptions[0].text;

                        let parent = this.parentElement;

                        let inputHidden = document.createElement('input');
                        inputHidden.type = 'hidden';
                        inputHidden.value = value;
                        inputHidden.name = this.dataset['name'];

                        let titleNode = document.createElement('span');
                        titleNode.innerText = title;

                        parent.append(inputHidden);
                        parent.append(titleNode);

                        if(this.dataset['callback_value'] != undefined)
                        {
                            let callback = this.dataset['callback_value'];
                            switch (callback)
                            {
                                case 'replaceProjectUserSelect':
                                    BX.BitDistribution.replaceProjectUserSelect(this.parentElement.parentElement.parentElement, value);
                                    break;
                                default:
                                        break;
                            }
                        }

                        this.remove();
                    });
                    BX.bindDelegate(document.body, 'click', { className : 'delete-of-row' } ,function()
                    {
                        //       div            td           tr
                        this.parentElement.parentElement.parentElement.remove();
                    });
                    BX.bindDelegate(document.body, 'click', { className : 'add-new-project-limit' } ,function()
                    {
                        BX.BitDistribution.addNewProjectRow(this.parentElement.parentElement);
                    });
                    BX.bindDelegate(document.body, 'click', { className : 'add-new-project-user-limit' } ,function()
                    {
                        BX.BitDistribution.addNewProjectUserRow(this.parentElement.parentElement);
                    });
                    BX.bindDelegate(document.body, 'click', { className : 'add-new-user-limit' } ,function()
                    {
                        BX.BitDistribution.addNewUserRow(this.parentElement.parentElement);
                    });

                    BX.bindDelegate(document.body, 'click', { className : 'add-new-source-user-limit' } ,function()
                    {
                        BX.BitDistribution.addNewUserSourceRow(this.parentElement.parentElement);
                    });


                    //

                    this.createIssetProject();
                    this.createIssetProjectUser();
                    this.createIssetUser();
                    this.createIssetUserSource();
                },
                addNewProjectRow : function (buttonNodeRow, project_name = '', project_count = 0)
                {
                    if(Object.keys(this.allProject).length === 0)
                    {
                        return;
                    }

                    let indexRow = this.countProject;
                    this.countProject++;

                    let container = document.createElement('tr');
                    let projectContainer = document.createElement('td');
                    projectContainer.append(this.createProjectTitle(project_name, indexRow));

                    let projectValue = document.createElement('td');
                    projectValue.append(this.createProjectValue(project_count, indexRow));

                    container.append(projectContainer);
                    container.append(projectValue);

                    buttonNodeRow.insertAdjacentElement('beforeBegin', container);
                },
                createProjectTitle : function (project_name = '', indexRow = 1)
                {
                    let container = document.createElement('div');
                    container.classList.add('title_row');

                    if(project_name.length > 0)
                    {
                        let value = project_name;
                        let title = project_name;

                        let inputHidden = document.createElement('input');
                        inputHidden.type = 'hidden';
                        inputHidden.value = value;
                        inputHidden.name = 'project[row_' + indexRow + '][project_name]';

                        let titleNode = document.createElement('span');
                        titleNode.innerText = title;

                        container.append(inputHidden);
                        container.append(titleNode);
                    }
                    else
                    {
                        let selector = document.createElement('select');
                        selector.classList.add('select-of-values');
                        selector.dataset['name'] = 'project[row_' + indexRow + '][project_name]';

                        let option = document.createElement('option');
                        option.value = '-';
                        option.innerHTML = '--- Select value---';
                        selector.append(option);

                        for (let key in this.allProject) {
                            let item = this.allProject[key];

                            let optionValue = document.createElement('option');
                            optionValue.value = item['NAME'];
                            optionValue.innerHTML = item['NAME'];
                            selector.append(optionValue);
                        }

                        container.append(selector);
                    }

                    return container;
                },
                createProjectValue : function (project_count = 0, indexRow)
                {
                    let container = document.createElement('div');
                    container.classList.add('container_row');

                    if (project_count < 0)
                    {
                        project_count = this.defaultProjectCount;
                    }

                    let input = document.createElement('input');
                    input.name = 'project[row_'+indexRow+'][count]';
                    input.type = 'number';
                    input.value = project_count;
                    input.min = 0;

                    let inputHidden = document.createElement('input');
                    inputHidden.name = 'project[row_'+indexRow+'][user_id]';
                    inputHidden.type = 'hidden';
                    inputHidden.value = '0';

                    container.append(input);
                    container.append(inputHidden);

                    let inputDelete = document.createElement('input');
                    inputDelete.classList.add('delete-of-row');
                    inputDelete.type = 'button';
                    inputDelete.value = 'Delete row';
                    container.append(inputDelete);

                    return container;
                },
                createIssetProject : function ()
                {
                    let buttonNodeRow = document.querySelector('.add-new-project-limit').parentElement.parentElement;
                    for (let index in this.issetProjectLimit)
                    {
                        let item = this.issetProjectLimit[index];
                        this.addNewProjectRow(buttonNodeRow, item['VALUE'], item['COUNT']);
                    }
                },

                addNewProjectUserRow : function (buttonNodeRow, project_name = '', user_id = 0, user_count = 0)
                {
                    if(Object.keys(this.allProject).length === 0)
                    {
                        return;
                    }

                    let indexRow = this.countProject;
                    this.countProject++;

                    let container = document.createElement('tr');
                    let projectContainer = document.createElement('td');
                    projectContainer.append(this.createProjectUserTitle(project_name, indexRow));

                    let projectValue = document.createElement('td');
                    projectValue.append(this.createProjectUserValue(project_name, user_id , user_count, indexRow));

                    container.append(projectContainer);
                    container.append(projectValue);

                    buttonNodeRow.insertAdjacentElement('beforeBegin', container);
                },
                createProjectUserTitle : function (project_name = '', indexRow = 1)
                {let container = document.createElement('div');
                    container.classList.add('title_row');

                    if(project_name.length > 0)
                    {
                        let value = project_name;
                        let title = project_name;

                        let inputHidden = document.createElement('input');
                        inputHidden.type = 'hidden';
                        inputHidden.value = value;
                        inputHidden.name = 'project[row_' + indexRow + '][project_name]';

                        let titleNode = document.createElement('span');
                        titleNode.innerText = title;

                        container.append(inputHidden);
                        container.append(titleNode);
                    }
                    else
                    {
                        let selector = document.createElement('select');
                        selector.classList.add('select-of-values');
                        selector.dataset['name'] = 'project[row_' + indexRow + '][project_name]';
                        selector.dataset['callback_value'] = 'replaceProjectUserSelect';

                        let option = document.createElement('option');
                        option.value = '-';
                        option.innerHTML = '--- Select value---';
                        selector.append(option);

                        for (let key in this.allProject) {
                            let item = this.allProject[key];

                            let optionValue = document.createElement('option');
                            optionValue.value = item['NAME'];
                            optionValue.innerHTML = item['NAME'];
                            selector.append(optionValue);
                        }

                        container.append(selector);
                    }


                    return container;
                },
                createProjectUserValue : function (project_name = '', user_id = 0 , user_count = 0, indexRow = 1)
                {
                    let container = document.createElement('div');
                    container.classList.add('container_row');

                    if (user_count < 0)
                    {
                        user_count = this.defaultProjectCount;
                    }

                    let selector = this.createProjectUserSelect(indexRow, project_name, user_id);

                    let input = document.createElement('input');
                    input.name = 'project[row_'+indexRow+'][count]';
                    input.type = 'number';
                    input.value = user_count;
                    input.min = 0;

                    container.append(selector);
                    container.append(input);

                    let inputDelete = document.createElement('input');
                    inputDelete.classList.add('delete-of-row');
                    inputDelete.type = 'button';
                    inputDelete.value = 'Delete row';
                    container.append(inputDelete);

                    return container;
                },
                replaceProjectUserSelect : function (node, value)
                {
                    let select = node.querySelector('.select_users');

                    let newSelect = this.createProjectUserSelect(0,value);

                    select.innerHTML = '';
                    select.innerHTML = newSelect.innerHTML;
                },
                createProjectUserSelect : function (indexRow = 1, project_name = '', user_id = '')
                {
                    let selector = document.createElement('select');
                    selector.classList.add('select_users');
                    selector.name = 'project[row_' + indexRow + '][user_id]';

                    if(project_name.length != 0)
                    {
                        for(let index in this.allProject)
                        {
                            let item = this.allProject[index];
                            if(item['NAME'] == project_name)
                            {
                                if(item['USERS'].length > 0)
                                {
                                    for(let userKey in item['USERS'])
                                    {
                                        let userID = item['USERS'][userKey];
                                        let key = 'U' + userID;
                                        if(this.allUser[key])
                                        {
                                            let user = this.allUser[key];

                                            let optionValue = document.createElement('option');
                                            optionValue.value = user['ID'];
                                            optionValue.innerHTML = user['NAME'];

                                            if (user_id == userID)
                                            {
                                                optionValue.selected = 'select';
                                            }

                                            selector.append(optionValue);
                                        }
                                    }
                                }
                                break;
                            }
                        }
                    }
                    else
                    {
                        let option = document.createElement('option');
                        option.value = '-';
                        option.innerHTML = '--- Select project---';
                        selector.append(option);
                    }

                    return selector;
                },
                createIssetProjectUser : function (){
                    let buttonNodeRow = document.querySelector('.add-new-project-user-limit').parentElement.parentElement;
                    for (let index in this.issetProjectUserLimit)
                    {
                        let item = this.issetProjectUserLimit[index];
                        this.addNewProjectUserRow(buttonNodeRow, item['VALUE'], item['USER_ID'], item['COUNT']);
                    }
                },

                addNewUserRow : function (buttonNodeRow, user_id = '', user_count = 0)
                {
                    if(Object.keys(this.allUser).length === 0)
                    {
                        return;
                    }

                    let indexRow = this.countProject;
                    this.countProject++;

                    let container = document.createElement('tr');
                    let userContainer = document.createElement('td');
                    userContainer.append(this.createUserTitle(user_id, indexRow));

                    let userValue = document.createElement('td');
                    userValue.append(this.createUserValue(user_count, indexRow));

                    container.append(userContainer);
                    container.append(userValue);

                    buttonNodeRow.insertAdjacentElement('beforeBegin', container);
                },
                createUserTitle : function (user_id = '', indexRow = 1)
                {
                    let container = document.createElement('div');
                    container.classList.add('title_row');

                    if(user_id > 0)
                    {
                        let key = 'U' + user_id;
                        let title = this.allUser[key]['NAME'];

                        let inputHidden = document.createElement('input');
                        inputHidden.type = 'hidden';
                        inputHidden.value = user_id;
                        inputHidden.name = 'user[row_' + indexRow + '][user_id]';

                        let titleNode = document.createElement('span');
                        titleNode.innerText = title;

                        container.append(inputHidden);
                        container.append(titleNode);
                    }
                    else
                    {
                        let selector = document.createElement('select');
                        selector.classList.add('select-of-values');
                        selector.dataset['name'] = 'user[row_' + indexRow + '][user_id]';

                        let option = document.createElement('option');
                        option.value = '-';
                        option.innerHTML = '--- Select value---';
                        selector.append(option);

                        for (let key in this.allUser)
                        {
                            let item = this.allUser[key];

                            let optionValue = document.createElement('option');
                            optionValue.value = item['ID'];
                            optionValue.innerHTML = item['NAME'];
                            selector.append(optionValue);
                        }

                        container.append(selector);
                    }

                    return container;
                },
                createUserValue : function (user_count = 0, indexRow = 1)
                {
                    let container = document.createElement('div');
                    container.classList.add('container_row');

                    if (user_count < 0)
                    {
                        user_count = this.defaultProjectCount;
                    }

                    let input = document.createElement('input');
                    input.name = 'user[row_'+indexRow+'][count]';
                    input.type = 'number';
                    input.value = user_count;
                    input.min = 0;

                    container.append(input);

                    let inputDelete = document.createElement('input');
                    inputDelete.classList.add('delete-of-row');
                    inputDelete.type = 'button';
                    inputDelete.value = 'Delete row';
                    container.append(inputDelete);

                    return container;

                },
                createIssetUser : function ()
                {
                    let buttonNodeRow = document.querySelector('.add-new-user-limit').parentElement.parentElement;
                    for (let index in this.issetUserLimit)
                    {
                        let item = this.issetUserLimit[index];
                        this.addNewUserRow(buttonNodeRow, item['USER_ID'], item['COUNT']);
                    }
                },

                addNewUserSourceRow : function (buttonNodeRow, user_id = 0, source = '', user_count = 0)
                {
                    if(Object.keys(this.allUser).length === 0)
                    {
                        return;
                    }

                    let indexRow = this.countProject;
                    this.countProject++;

                    let container = document.createElement('tr');
                    let projectContainer = document.createElement('td');
                    projectContainer.append(this.createUserSourceTitle(user_id, indexRow));

                    let projectValue = document.createElement('td');
                    projectValue.append(this.createUserSourceValue(source, user_count, indexRow));

                    container.append(projectContainer);
                    container.append(projectValue);

                    buttonNodeRow.insertAdjacentElement('beforeBegin', container);
                },
                createUserSourceTitle : function (user_id = 0, indexRow = 1)
                {
                    let container = document.createElement('div');
                    container.classList.add('title_row');

                    let keyUser = 'U' + user_id;

                    if(user_id > 0)
                    {
                        let title = this.allUser[keyUser]['NAME'];

                        let inputHidden = document.createElement('input');
                        inputHidden.type = 'hidden';
                        inputHidden.value = user_id;
                        inputHidden.name = 'user_source[row_' + indexRow + '][user_id]';

                        let titleNode = document.createElement('span');
                        titleNode.innerText = title;

                        container.append(inputHidden);
                        container.append(titleNode);
                    }
                    else
                    {
                        let selector = document.createElement('select');
                        selector.classList.add('select-of-values');
                        selector.dataset['name'] = 'user_source[row_' + indexRow + '][user_id]';

                        let option = document.createElement('option');
                        option.value = '-';
                        option.innerHTML = '--- Select value---';
                        selector.append(option);

                        for (let key in this.allUser)
                        {
                            let item = this.allUser[key];

                            let optionValue = document.createElement('option');
                            optionValue.value = item['ID'];
                            optionValue.innerHTML = item['NAME'];
                            selector.append(optionValue);
                        }

                        container.append(selector);
                    }

                    return container;
                },
                createUserSourceValue : function (source = '', user_count = 0, indexRow = 1)
                {
                    let container = document.createElement('div');
                    container.classList.add('container_row');

                    if (user_count < 0)
                    {
                        user_count = this.defaultProjectCount;
                    }

                    let selector = this.createLeadSourceSelect(indexRow, source);

                    let input = document.createElement('input');
                    input.name = 'user_source[row_'+indexRow+'][count]';
                    input.type = 'number';
                    input.value = user_count;
                    input.min = 0;

                    container.append(selector);
                    container.append(input);

                    let inputDelete = document.createElement('input');
                    inputDelete.classList.add('delete-of-row');
                    inputDelete.type = 'button';
                    inputDelete.value = 'Delete row';
                    container.append(inputDelete);

                    return container;
                },
                createLeadSourceSelect : function (indexRow = 1, source = '')
                {
                    let selector = document.createElement('select');
                    selector.classList.add('select_lead_source');
                    selector.name = 'user_source[row_' + indexRow + '][source]';

                    for(let index in this.allLeadSource)
                    {
                        let item = this.allLeadSource[index];

                        let optionValue = document.createElement('option');
                        optionValue.value = index;
                        optionValue.innerHTML = item;

                        if (index == source)
                        {
                            optionValue.selected = 'select';
                        }
                        selector.append(optionValue);
                    }

                    return selector;
                },
                createIssetUserSource : function ()
                {
                    let buttonNodeRow = document.querySelector('.add-new-source-user-limit').parentElement.parentElement;
                    for (let index in this.issetUserSourceLimit)
                    {
                        let item = this.issetUserSourceLimit[index];
                        this.addNewUserSourceRow(buttonNodeRow, item['USER_ID'], item['VALUE'] , item['COUNT']);
                    }
                },
            };


            BX.BitDistribution = BitDistribution;
            BX.BitDistribution.init();

        });

    </script>
    <style type="text/css">
        form .title_row{
            text-align: right;
            margin-right: 1em;
        }
        .title_row select{
            padding: 5px;
        }
        .container_row{
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .container_row input[type="number"]{
            text-align: center;
            padding: 5px;
        }
        .container_row select {
            width: 300px;
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



<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\UI\Toolbar\Facade\Toolbar;

/** @var CBitrixComponentTemplate $this */
/** @var string $templateFolder */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

$this->setFrameMode(true);

\Bitrix\Main\UI\Extension::load(['ui.design-tokens', 'ui.fonts.opensans']);

$filter = Toolbar::getFilter();
$afterTitleButtons = Toolbar::renderAfterTitleButtons();
$rightButtons = Toolbar::renderRightButtons();
$filterButtons = Toolbar::renderAfterFilterButtons();
$beforeTitleHtml = Toolbar::getBeforeTitleHtml();
$afterTitleHtml = Toolbar::getAfterTitleHtml();
$rightCustomHtml = Toolbar::getRightCustomHtml();
$underTitleHtml = Toolbar::getUnderTitleHtml();

$favoriteTitleTemplate = (!empty($arParams['~FAVORITES_TITLE_TEMPLATE']) ? $arParams['~FAVORITES_TITLE_TEMPLATE'] : '');
if (mb_strlen($favoriteTitleTemplate) <= 0)
{
	$favoriteTitleTemplate = $APPLICATION->getProperty('FavoriteTitleTemplate', '');
}

$favoriteUrl = (!empty($arParams['~FAVORITES_URL']) ? $arParams['~FAVORITES_URL'] : '');
if (mb_strlen($favoriteUrl) <= 0)
{
	$favoriteUrl = $APPLICATION->getProperty('FavoriteUrl', '');
}

$favoriteStar = Toolbar::hasFavoriteStar()? '<span class="ui-toolbar-star" id="uiToolbarStar" data-bx-title-template="' . htmlspecialcharsbx($favoriteTitleTemplate) . '" data-bx-url="' . htmlspecialcharsbx($favoriteUrl) . '"></span>' : '';

$titleProps = "";
if (Toolbar::getTitleMinWidth() !== null)
{
	$titleProps .= 'min-width:'.Toolbar::getTitleMinWidth().'px'.';';
}

if (Toolbar::getTitleMaxWidth() !== null)
{
	$titleProps .= 'max-width:'.Toolbar::getTitleMaxWidth().'px';
}

$titleStyles = !empty($titleProps) ? ' style="'.$titleProps.'"' : "";

//Get current user
$USER_ID = $USER->GetID();

$rsUser = CUser::GetByID($USER_ID);
$arUser = $rsUser->Fetch();

$CurDateTime = new \Bitrix\Main\Type\DateTime();
$CurDateTime = $CurDateTime->format("m/d/Y h:i:s a");

?>
<div id="boost-info">
	<h1>LEADS POLICY TERMS AND CONDITIONS</h1>
    <ol>
        <li>1. New employees will receive support from the company for a duration of two months to procure 3 to 4 leads per day to meet their targets. Failure to achieve this may result in a reduced allocation of leads.</li>
        <li>2. Leads must be managed professionally, including:
            <ul>
                <li>Prompt follow-up</li>
                <li>Writing a fair feedback</li>
                <li>Scheduling of lead activities such as meetings and calls</li>
            </ul>
            Failing to do so may negatively impact an agent's scoring report and lead allocation.
        </li>
        <li>3. Agents who consistently close a high number of deals will be granted access to exclusive campaigns for a designated period, rewarding their performance with increased lead opportunities.</li>
        <li>4. Criteria for Boost Button, Stages & Eligibility are as follows:
            <ul>
				<li><strong style="background-color:green;color:white;padding:0px 5px;">Green Stage Criteria:</strong> Agents adhering to scheduled activities, providing comprehensive feedback on leads, and closing 3 to 4 deals per month will receive the highest volume of leads.</li>
				<li><strong style="background-color:yellow;color:whitepadding:0px 5px;">Yellow Stage Criteria:</strong> Agents closing 1 or 2 deals within a 3-month period will receive a minimal allocation of leads. Failure to provide feedback on leads or not engage in scheduled activities may adversely affect their scoring report and leads quantity.</li>
				<li><strong style="background-color:red;color:whitepadding:0px 5px;">Red Stage Criteria:</strong> Agents who have not closed any deals are eligible only for reshuffled leads.</li>
            </ul>
			<p><b>Note: In order to activate the boost you need to have at least 85% or 90% score.</b></p>
        </li>
    </ol>
</div>
<div id="uiToolbarContainer" class="ui-toolbar"><?php

	?><div id="pagetitleContainer" class="ui-toolbar-title-box"<?=$titleStyles?>><?php
		?>
		<div class="ui-toolbar-title-inner">
			<div class="ui-toolbar-title-item-box">
				<?php
				if (!empty($beforeTitleHtml)):
					?><div class="ui-toolbar-before-title"><?=$beforeTitleHtml?></div><?
				endif;
				?>
				<span id="pagetitle" class="ui-toolbar-title-item"><?=$APPLICATION->getTitle(false, true)?></span>
				<?= $favoriteStar ?>
			</div><?php
			?>
			<?php
			if (!empty($underTitleHtml)):
				?><div class="ui-toolbar-subtitle"><?=$underTitleHtml?></div><?
			endif;
			?>
		</div>
		<?php
	?></div>
	<script>
	function onClickButton()
	{
		window.location.href = '/crm/deal/kanban/category/1/';

	}
	</script>
	<?
	$requestUri = $_SERVER['REQUEST_URI'];
	if(strpos($requestUri,"crm/lead")!= false)
    { ?>
	    <button class='ui-btn' onclick="onClickButton()">Secondary Market Leads</button>
	<? } ?>
	<?php

	if($afterTitleButtons <> ''):
		?>
		<div class="ui-toolbar-after-title-buttons"><?= $afterTitleButtons ?></div><?php
	endif;

	if (!empty($afterTitleHtml)):
		?><div class="ui-toolbar-after-title"><?=$afterTitleHtml?></div><?
	endif;

	if($filter <> ''):
		?>
		<div class="ui-toolbar-filter-box"><?= $filter ?><?php
		if($filterButtons <> ''): ?><?php
			?>
			<div class="ui-toolbar-filter-buttons"><?= $filterButtons ?></div><?php
		endif
		?></div>
	<?

if(strpos($requestUri,"crm/lead") != false)
{


	if($metrics['lead_conversion']>='3'){?>
	<a class="ui-btn css_popup" style ="background:green;" title="Boost">Boost</a>
	<? } elseif($metrics['lead_conversion']>='1'){ ?>
<a class="ui-btn css_popup" style ="background:yellow;color:#000" title="Boost">Boost</a>
	<?} else { ?>
<a class="ui-btn css_popup" style ="background:red;" title="Boost">Boost</a>
	<? } } ?>
<?php
	endif;

	if($rightButtons <> ''):
		?>
		<div class="ui-toolbar-right-buttons"><?= $rightButtons ?></div><?php
	endif;

	if (!empty($rightCustomHtml)):
		?><div class="ui-toolbar-after-title"><?=$rightCustomHtml?></div><?
	endif;
?></div>

<script>

	BX.message({
		UI_TOOLBAR_ADD_PAGE_TO_LEFT_MENU: '<?= GetMessageJS('UI_TOOLBAR_ADD_PAGE_TO_LEFT_MENU') ?>',
		UI_TOOLBAR_DELETE_PAGE_FROM_LEFT_MENU: '<?= GetMessageJS('UI_TOOLBAR_DELETE_PAGE_FROM_LEFT_MENU') ?>',
		UI_TOOLBAR_ITEM_WAS_ADDED_TO_LEFT: '<?= GetMessageJS('UI_TOOLBAR_ITEM_WAS_ADDED_TO_LEFT') ?>',
		UI_TOOLBAR_ITEM_WAS_DELETED_FROM_LEFT: '<?= GetMessageJS('UI_TOOLBAR_ITEM_WAS_DELETED_FROM_LEFT') ?>',
		UI_TOOLBAR_STAR_TITLE_DEFAULT_PAGE: '<?= GetMessageJS('UI_TOOLBAR_STAR_TITLE_DEFAULT_PAGE') ?>',
		UI_TOOLBAR_STAR_TITLE_DEFAULT_PAGE_DELETE_ERROR: '<?= GetMessageJS('UI_TOOLBAR_STAR_TITLE_DEFAULT_PAGE_DELETE_ERROR') ?>',
	});

	BX.UI.ToolbarManager.create(Object.assign(<?=\Bitrix\Main\Web\Json::encode([
		"id" => Toolbar::getId(),
		"titleMinWidth" => Toolbar::getTitleMinWidth(),
		"titleMaxWidth" => Toolbar::getTitleMaxWidth(),
		"buttonIds" => array_map(function(\Bitrix\UI\Buttons\BaseButton $button){
			return $button->getUniqId();
		}, Toolbar::getButtons()),
	])?>,
		{
			target: document.getElementById('uiToolbarContainer')
		}
	));
	new BX.UI.Toolbar.Star();
</script>
<script>
	window.BXDEBUG = true;
BX.ready(function(){
	var oPopup = new BX.PopupWindow('call_feedback', window.body, {
		autoHide : true,
		offsetTop : 1,
		offsetLeft : 0,
		lightShadow : true,
		closeIcon : true,
		closeByEsc : true,
		overlay: {
			backgroundColor: 'black', opacity: '100'
		}
	});
	oPopup.setContent(BX('boost-info'));
	BX.bindDelegate(
		document.body, 'click', {className: 'css_popup' },
			BX.proxy(function(e){
				if(!e)
					e = window.event;
				oPopup.show();
				return BX.PreventDefault(e);
		}, oPopup)
	);
   
   
});
</script>
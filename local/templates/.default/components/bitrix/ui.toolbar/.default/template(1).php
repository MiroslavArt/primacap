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
	<h1>Criteria for boost button</h1>
	<ol>
		<li><b>1. Follow Up with the Client Properly:</b> Consistent and effective communication with clients is crucial. Ensure timely follow-ups to maintain engagement and address their needs.</li>

<li><b>2. Proper Comments on Every Lead:</b> lnteractions and updates on each lead is essential. Make detailed and accurate comments to track progress and maintain clarity.</li>

<li><b>3. Incentives for Closing Deals:</b> If an employee successfully closes a deal per month, they will be rewarded with additional leads. This encourages better performance and sales efficiency.</li>

<li><b>4. Value Company Leads:</b> Treat every lead as an opportunity and handle them with utmost care and professionalism. Recognize the potential value each lead brings to the company.</li>

<li><b>5. Initial Lead Allocation for New Agents:</b> If you are new to the company, you will receive a higher number of leads for a certain period (2 or 3 months).</li>

<li><b>6. Reduction of Leads for Non-Closure:</b> If a new agent does not close any deals within this initial 2 or 3 month period, the number of leads allocated to them will be reduced.</li>

<li><b>7. No Leads for Inactive Agents:</b> Any agent who does not close a deal within a 2-month timeframe will not receive any new leads.</li>

<li><b>8. Limited-Time Activation of Boost Button:</b> The boost button will be activated for a limited duration, typically 1 or 2 hours.</li>

<li><b>9. Eligibility for Boost Button:</b> The boost button feature is exclusively available for agents who are actively working and successfully closing deals.</li>
	</ol>
	<p><b>Overall, implementing rules for sales agents is a strategic move to optimize their performance, ensure clients satisfaction, and achieve sales target effectively.</b></p>
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
 $requestUri = $_SERVER['REQUEST_URI'];
if(strpos($requestUri,"crm/lead") != false)
{
	if($arUser['UF_BOOST_LEAD']=='675' && ( $arUser['UF_BOOST_VALID'] =='' || strtotime($arUser['UF_BOOST_VALID']) < strtotime($CurDateTime)) && ( $arUser['UF_BOOST_RETRY'] =='' || strtotime($arUser['UF_BOOST_RETRY']) < strtotime($CurDateTime))){ 
?>
<a class="ui-btn ui-btn-success boost" onclick="return confirm('Are you sure you want to submit for boost?')" href="/local/boost-approval.php?id=<?=$USER_ID?>" title="Boost">Boost</a>
	<?} elseif($arUser['UF_BOOST_LEAD']=='675' && strtotime($arUser['UF_BOOST_VALID']) > strtotime($CurDateTime)){ ?>
	<button class="ui-btn ui-btn-success boost hover-text" >Boosted <span class="tooltip-text" id="top">Boost valid until <?=$arUser['UF_BOOST_VALID'];?></span></button>
<?} elseif($arUser['UF_BOOST_LEAD']=='675' && strtotime($arUser['UF_BOOST_RETRY']) > strtotime($CurDateTime)){ ?>
	<button class="ui-btn ui-btn-success reboost hover-text">Boost<span class="tooltip-text" id="top">Re-apply after <?=$arUser['UF_BOOST_RETRY'];?></span> </button> 
	<?} else { ?>

<a class="ui-btn css_popup" title="Boost">Boost</a>
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
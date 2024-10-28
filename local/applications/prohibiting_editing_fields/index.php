<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

//Connecting files with access
if(file_exists('fieldsLead.php')) require_once 'fieldsLead.php';

$APPLICATION->SetTitle("Fields editing prohibition");
function dump($mess) {echo '<pre>'.print_r($mess,true).'</pre>';}
CModule::IncludeModule('crm');
global $USER;

//We get the employee id
$idsEmployees = [];
$arUsers = [];
$employees = Bitrix\Im\Department::getEmployees();
foreach($employees as $key=>$value){
	foreach($value as $key2=>$value2){
		$idsEmployees[] = $value2;
	}
}
//Getting employee data
$userResult = \Bitrix\Main\UserTable::getList([
	'filter' => ['ID'=>$idsEmployees],
	'select'=>['ID','LAST_NAME','NAME','SECOND_NAME'],
	'order' => ['LAST_NAME'=>'ASC'],
]);
while($user = $userResult->fetch()){
	$user['FULL_NAME'] = trim($user['LAST_NAME'].' '.$user['NAME'].' '.$user['SECOND_NAME']);
	$arUsers[$user['ID']] = $user;
}

//Standart fild
$fieldsLead = [
	'TITLE'=>[
		'FIELD_NAME' => 'TITLE',
        'NAME_EN' => 'Lead Name',
	],
	'COMMENTS'=>[
		'FIELD_NAME' => 'COMMENTS',
        'NAME_EN' => 'Comments',
	],

	'NAME'=>[
		'FIELD_NAME' => 'NAME',
        'NAME_EN' => 'Name',
	],
	'SECOND_NAME'=>[
		'FIELD_NAME' => 'SECOND_NAME',
        'NAME_EN' => 'Second name',
	],
	'LAST_NAME'=>[
		'FIELD_NAME' => 'LAST_NAME',
        'NAME_EN' => 'Last name',
	],
	'ASSIGNED_BY_ID'=>[
		'FIELD_NAME' => 'ASSIGNED_BY_ID',
        'NAME_EN' => 'Responsible person',
	],
	'PHONE'=>[
		'FIELD_NAME' => 'PHONE',
        'NAME_EN' => 'Phone',
	],
	'EMAIL'=>[
		'FIELD_NAME' => 'EMAIL',
        'NAME_EN' => 'Email',
	],
];

//User field
$userFieldsLead = CCrmLead::GetUserFields();
foreach($userFieldsLead as $key=>$value){
	//Find out the name of the field
	$value['NAME_EN'] = \Bitrix\Main\UserFieldLangTable::getList(array(
		'filter' => array('USER_FIELD_ID' => $value['ID'], 'LANGUAGE_ID'=>'en'),
	))->fetch()['EDIT_FORM_LABEL'];
	$fieldsLead[$key] = [
		'FIELD_NAME'=>$value['FIELD_NAME'],
		'NAME_EN'=>$value['NAME_EN'],
	];
}
?>
<script src="js/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="plugin/bootstrap/bootstrap.min.css">
<link rel="stylesheet" href="plugin/bootstrap/bootstrap-select.min.css">
<script src="plugin/bootstrap/bootstrap.min.js"></script>
<script src="plugin/bootstrap/bootstrap-select.min.js"></script>
<link rel="stylesheet" href="css/style.css">
<div class="wrapper_control_file_extensions">
<? 
//We check whether the user is an administrator
if($USER->IsAdmin()){ ?>
	<section class="control_file_extensions_description">
	</section>
	<section class="control_file_extensions_deal">
		<h2>Lead</h2>
		<div class="control_access_fields_option">
			<div class="control_access_fields_option_column">
				<label class="main_label">Select read-only field</label>
				<select id="field" class="selectpicker" data-style="btn-primary" data-live-search="true" data-size="15" data-actions-box="true">
					<?foreach($fieldsLead as $key=>$value){ ?>
					<option value="<?=$value['FIELD_NAME']?>"><?=$value['NAME_EN']?></option>
					<? } ?>
				</select>
			</div>
			<div class="control_access_fields_option_column">
				<label class="main_label">Select users with edit access</label>
				<select id="users" class="selectpicker" data-style="btn-primary" data-live-search="true" data-size="15" data-actions-box="true" multiple>
					<?foreach($arUsers as $key=>$value){ ?>
					<option value="<?=$value['ID']?>"><?=$value['FULL_NAME']?></option>
					<? } ?>
				</select>
			</div>
			<!-- Statuses with edit ability -->
			<button class="button_access" id="add_access">Apply</button>
		</div>
		<div class="control_file_extensions_fields">
			<?foreach($arFieldLeadAccess as $key=>$value){?>
				<div class="control_file_extensions_fields_row">
					<div class="control_file_extensions_field_title">
						<span><?=$fieldsLead[$key]['NAME_EN']?></span>
					</div>
					<div class="control_file_extensions_field_input">
						<span>
						<?foreach($value as $key2=>$value2){?>
							<?if($key2>0) {
								echo ', '.$arUsers[$value2]['FULL_NAME'];
							}else{
								echo $arUsers[$value2]['FULL_NAME'];
							}?>
						<?}?>
						</span>
					</div>
					<div class="control_file_extensions_field_delete">
						<span class="control_file_extensions_field_delete_row" id="<?=$key?>">&#10006;</span>
					</div>
				</div>
			<?}?>
		</div>
	</section>
<script>
$(document).ready(function() {
	let arChangeData = <? echo json_encode($arFieldLeadAccess); ?>;
	function write_data(){
		let dataSend = {
			GET_FIELD:{
				TEST:'TEST',
			}
		}
		$.ajax({
			url: 'ajax.php',
			method: 'post',
			dataType: 'json',
			data: dataSend,
			success: function(data){
				$('.control_file_extensions_fields').empty();
				$.each(data, function(key,value){
					$('.control_file_extensions_fields').append(
						'<div class="control_file_extensions_fields_row">'+
							'<div class="control_file_extensions_field_title">'+
								'<span>'+value.NAME_FIELD+'</span>'+
							'</div>'+
							'<div class="control_file_extensions_field_input">'+
								'<span>'+value.NAME_USER+'</span>'+
							'</div>'+
							'<div class="control_file_extensions_field_delete">'+
								'<span class="control_file_extensions_field_delete_row" id="'+value.ID+'">&#10006;</span>'+
							'</div>'+
						'</div>'
					);
				});
			}
		});
	}
	$('#add_access').on('click', function(){
		let dataSend = {
			ADD_FIELD:{
				FIELD:$('#field').val(),
				USERS:$('#users').val(),
			}
		}
		//Send ajax
		$.ajax({
			url: 'ajax.php',
			method: 'post',
			dataType: 'json',
			data: dataSend,
			success: function(data){
				write_data();
			}
		});
	});
	$('.control_file_extensions_fields').on('click', '.control_file_extensions_field_delete_row', function(){
		let dataSend = {
			DEL_FIELD:{
				FIELD:$(this).attr('id'),
			}
		}
		//Send ajax
		$.ajax({
			url: 'ajax.php',
			method: 'post',
			dataType: 'json',
			data: dataSend,
			success: function(data){
				write_data();
			}
		});
	});
});
</script>	
<?}else{?>
	<h2 class="control_file_extensions_error">Only users with administrator rights can edit options</h2>
<?}?>
</div>
<? require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
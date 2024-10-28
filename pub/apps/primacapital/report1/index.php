<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$result = $this->obB24App->call('user.get', ['ACTIVE'=>true]);
$commands = [];
$names = [];
foreach ($result['result'] as $item) {
    $names[$item['ID']] = $item['NAME'].' '.$item['LAST_NAME'];
    $commands['timeman_'.$item['ID']] = 'timeman.status?'
        .http_build_query(array(
            'USER_ID' => $item['ID']
        ));
}
$result = $this->obB24App->call('batch', ['halt' => 0, 'cmd' => $commands]);

echo "Availability status at ".date('l jS \of F Y h:i:s A');
echo "<br/>";
?>
    <table style="text-align: left; border: black 1px solid;">
        <th>Agent</th>
        <th>Status</th>
<?php
foreach ($result['result']['result'] as $key => $item) {
    ?>
        <tr style="border: darkslateblue 1px">
            <td><?= $names[preg_replace("/[^0-9]/", '', $key)]?></td>
            <td><?= $item['STATUS']?></td>
        </tr>
    <?
}
?>
    </table>

<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->SetTitle("Custom Reports");

?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<style>

.dashboard {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); /* Responsive grid layout */
    grid-gap: 20px; /* Spacing between widgets */
}

.widget {
background: #fbfbfb;
    border: 1px solid #E0E0E0;

    padding: 15px;
    border-radius: 5px;
    box-shadow: 0 0 5px rgba(0, 0, 0, 0.1); /* Adding a shadow effect */
}

.widget h2 {
    margin-top: 0;
    font-size: 1.25rem;
}

.widget p {
    margin-bottom: 0;
}
.widget .fa-cog {
    float: right;
    color: #fff;
    margin: 11px 11px 0 0;
    font-size: 20px;
}
.widget i{
color: #000;
    padding: 10px 12px;
    font-family: 'FontAwesome', sans-serif;
    background: #bbed21;
    float: left;
    width: 93%;
    text-align: center;
    font-size: 13px;
	}
</style>
<div class="dashboard">
    <div class="widget">
        <h2>Agent Score</h2>
		<p><a href="/local/custom-reports/agent-score/"><i class="fa fa-file"> View Report</i></a></p>
    </div>
    <div class="widget">
        <h2>Agent Active Leads (With Stages)</h2>
<p><a href="/local/custom-reports/agent-active-leads/"><i class="fa fa-file"> View Report</i></a></p>
    </div>
    <div class="widget">
        <h2>Agent Junk Leads (With Stages)</h2>
<p><a href="/local/custom-reports/agent-junk-leads/"><i class="fa fa-file"> View Report</i></a></p>
    </div>
 <div class="widget">
        <h2>Agent Leads Call, Conversation & Activity Report</h2>
<p><a href="/local/custom-reports/agent-leads-call-report/"><i class="fa fa-file"> View Report</i></a></p>
    </div>
</div>




<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
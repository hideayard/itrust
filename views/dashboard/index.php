<?php

use yii\web\View;
use yii\helpers\Url;
use kartik\helpers\Html;

$this->title = "Dashboard";

$now = (new \DateTime())->format('Y-m-d');

$local = false;
$url = 'https://itrustcare.id/itrust/web/close-order';
if($local)
{
  $url = 'http://project.local/itrust/web/close-order';
}
$account = Yii::$app->user->identity->user_account;
$dashboardJS = <<<DASHBOARD_JS

$('#outlook-btn').click(function(){
    console.log('outlook-btn triggered');
    $.post('$url/outlook', {
        id : $account,
    }, function(response){
            console.log(response);
          if (response.success) {
                alert('Data saved successfully.');
            } else {
                alert('Failed to save data.');
            }
    });
});

$('#close-order-btn').click(function(){
    console.log('close-order-btn triggered');
    $.post('$url/close', {
        id : $account,
    }, function(response){
            console.log(response);
          if (response.success) {
                alert('Data saved successfully.');
            } else {
                alert('Failed to save data.');
            }
    });
});

DASHBOARD_JS;

$this->registerJs($dashboardJS);

?>

<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Hai <?= Yii::$app->user->identity->user_nama ?> (<?= Yii::$app->user->identity->user_account ?>)</h3>
    </div>

    <div class="card-body">
    <p>ITrust Trading Platform </p>
    <p> <button class="btn btn-info" id="outlook-btn"><span class="fa fa-image"></span> GET OUTLOOK</button> </p>
    <p> <button class="btn btn-danger" id="close-order-btn"><span class="fa fa-trash"></span> CLOSE ALL</button> </p>
    <!-- <p> <button class="btn btn-primary" onclick="addItem()"><span class="fa fa-trash"></span> CLOSE ALL</button> </p> -->
      
    </div>

</div>
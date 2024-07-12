<?php

use yii\web\View;
use yii\helpers\Url;
use kartik\helpers\Html;

$this->title = "Dashboard";
$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->csrfToken;

$now = (new \DateTime())->format('Y-m-d');
$baseUrl = Url::base() . '/';
$outlookUrl = Url::to(['site/outlook']);
$closeOrderUrl = Url::to(['site/close']);
$maxOPUrl = Url::to(['site/maxop']);

$local = true;
$url = 'https://itrustcare.id/itrust/web/site/create-order';
if ($local) {
    $url = 'http://project.local/itrust/web/site/create-order';
}
$account = Yii::$app->user->identity->user_account;
$dashboardJS = <<<DASHBOARD_JS

$('#outlook-btn').click(function(){
    console.log('outlook-btn triggered');
    $.post('$outlookUrl', {
        $csrfParam: '$csrfToken',
        id : $account,
    }, function(response){
            console.log(response);
        if (response.success) {
            alert('Outlook Command sent.');
        } else {
            alert('Failed to save data.');
        }
    });
});

$('#close-order-btn').click(function(){
    console.log('close-order-btn triggered');
    $.post('$closeOrderUrl', {
        $csrfParam: '$csrfToken',
        id : $account,
    }, function(response){
            console.log(response);
        if (response.success) {
            alert('Close command sent.');
        } else {
            alert('Failed to save data.');
        }
    });
});

$('#minus-btn').click(function(){
    let maxop = ( ( parseInt($('#maxop').val()) - 5 ) >= 5 )? ( parseInt($('#maxop').val()) - 5 ) : $('#maxop').val();
    console.log("maxop",maxop);
    $('#maxop').val(maxop);
});

$('#plus-btn').click(function(){
    let maxop = ( ( parseInt($('#maxop').val()) + 5 ) <= 100 )? ( parseInt($('#maxop').val()) + 5 ) : $('#maxop').val();
    console.log("maxop",maxop);
    $('#maxop').val(maxop);
});

$('#maxop-btn').click(function(){
    let maxop = parseInt($('#maxop').val());
    if(maxop >= 5 && maxop <= 100) {
        //maxOPUrl
         $.post('$maxOPUrl', {
            $csrfParam: '$csrfToken',
            id : $account,
            maxop : maxop,
        }, function(response){
                console.log(response);
            if (response.success) {
                alert('Set MAX OP command sent.');
            } else {
                alert('Failed to save Set MAX OP.');
            }
        });
    } else {
        alert('MAX Order not valid');
    }   
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
        <p>Your License : <?= $model->user_license ?></p>
        <hr>
        <div class="row">
            <div class="col-lg-1 col-md-1 col-sm-12 col-xs-12">
                <input type="number" pattern="[0-9]*" id="maxop" name="maxop" class="form-control" value="5" min="5" max="100" />
            </div>
            <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
                <p> <button class="btn btn-success" id="maxop-btn"><span class="fa fa-check-square"></span> SET MAX OP</button> </p>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-1 col-md-1 col-sm-12 col-xs-12">
                <button class="btn btn-danger" id="minus-btn"><span class="fa fa-minus"></span> 5</button>
                <button class="btn btn-success" id="plus-btn"><span class="fa fa-plus"></span> 5</button>

            </div>
        </div>
        <br>

        <p> <button class="btn btn-info" id="outlook-btn"><span class="fa fa-image"></span> GET OUTLOOK</button> </p>
        <p> <button class="btn btn-danger" id="close-order-btn"><span class="fa fa-dollar-sign"></span> CLOSE ALL</button> </p>
        <!-- <p> <button class="btn btn-primary" onclick="addItem()"><span class="fa fa-trash"></span> CLOSE ALL</button> </p> -->

    </div>

</div>
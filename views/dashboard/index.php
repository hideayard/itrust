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
?>

<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Hai <?= Yii::$app->user->identity->user_nama ?> (<?= Yii::$app->user->identity->user_account ?>)</h3>
    </div>

    <div class="card-body">
    <p>ITrust Trading Platform </p>
    <p> <button class="btn btn-info" onclick="getOutlook()"><span class="fa fa-image"></span> GET OUTLOOK</button> </p>
    <p> <button class="btn btn-danger" onclick="closeOrder()"><span class="fa fa-trash"></span> CLOSE ALL</button> </p>
    <!-- <p> <button class="btn btn-primary" onclick="addItem()"><span class="fa fa-trash"></span> CLOSE ALL</button> </p> -->
      
    </div>

</div>

<script>

  function getOutlook()
  {
    // Example JavaScript/jQuery code to send POST request
    $.ajax({
      url: <?=$url.'/outlook'?>, // Replace with your controller route
      // url: 'http://project.local/itrust/web/close-order/close', // Replace with your controller route
        type: 'POST',
        // dataType: 'json',
        data: {
            id: '<?= Yii::$app->user->identity->user_account ?>',
        },
        success: function(response) {
            // Handle the success response
            console.log(response.message);
            if (response.success) {
                alert('Data saved successfully.');
            } else {
                alert('Failed to save data.');
            }
        },
        error: function(xhr, status, error) {
            // Handle errors
            console.error('Error:', error);
            alert('Error occurred while saving data.');
        }
    });
  }

  function closeOrder()
  {
    // Example JavaScript/jQuery code to send POST request
    $.ajax({
        // url: 'https://itrustcare.id/itrust/web/close-order/close', // Replace with your controller route
        url: <?=$url.'/close-order'?>, // Replace with your controller route

        type: 'POST',
        // dataType: 'json',
        data: {
            id: '<?= Yii::$app->user->identity->user_account ?>',
        },
        success: function(response) {
            // Handle the success response
            console.log(response.message);
            if (response.success) {
                alert('Data saved successfully.');
            } else {
                alert('Failed to save data.');
            }
        },
        error: function(xhr, status, error) {
            // Handle errors
            console.error('Error:', error);
            alert('Error occurred while saving data.');
        }
    });
  }
  

</script>
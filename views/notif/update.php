<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var app\models\Notif $model
 */

$this->title = 'Update Notification';
$this->params['breadcrumbs'][] = ['label' => 'Notifs', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->notif_id, 'url' => ['view', 'id' => $model->notif_id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="notif-update">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>

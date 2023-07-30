<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var app\models\Notif $model
 */

$this->title = 'Create Notification';
$this->params['breadcrumbs'][] = ['label' => 'Notifs', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="notif-create">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>

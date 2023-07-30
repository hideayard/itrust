<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var app\models\DataSensors $model
 */

$this->title = 'Update Data Sensors: ' . ' ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Data Sensors', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="data-sensors-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>

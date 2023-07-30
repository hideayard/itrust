<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var app\models\DataSensors $model
 */

$this->title = 'Create Data Sensors';
$this->params['breadcrumbs'][] = ['label' => 'Data Sensors', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="data-sensors-create">
    <div class="page-header">
        <h1><?= Html::encode($this->title) ?></h1>
    </div>
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>

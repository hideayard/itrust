<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var app\models\DataSensorsSearch $model
 * @var yii\widgets\ActiveForm $form
 */
?>

<div class="data-sensors-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 's1') ?>

    <?= $form->field($model, 's2') ?>

    <?= $form->field($model, 's3') ?>

    <?= $form->field($model, 's4') ?>

    <?php // echo $form->field($model, 's5') ?>

    <?php // echo $form->field($model, 's6') ?>

    <?php // echo $form->field($model, 's7') ?>

    <?php // echo $form->field($model, 's8') ?>

    <?php // echo $form->field($model, 's9') ?>

    <?php // echo $form->field($model, 'ip') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'created_by') ?>

    <?php // echo $form->field($model, 'modified_at') ?>

    <?php // echo $form->field($model, 'modified_by') ?>

    <?php // echo $form->field($model, 'status') ?>

    <?php // echo $form->field($model, 'remark') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

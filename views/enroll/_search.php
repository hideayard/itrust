<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var app\models\EnrollSearch $model
 * @var yii\widgets\ActiveForm $form
 */
?>

<div class="enroll-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'enroll_id') ?>

    <?= $form->field($model, 'enroll_userid') ?>

    <?= $form->field($model, 'enroll_courseid') ?>

    <?= $form->field($model, 'enroll_cs') ?>

    <?= $form->field($model, 'enroll_remark') ?>

    <?php // echo $form->field($model, 'enroll_created_at') ?>

    <?php // echo $form->field($model, 'enroll_created_by') ?>

    <?php // echo $form->field($model, 'enroll_modified_at') ?>

    <?php // echo $form->field($model, 'enroll_modified_by') ?>

    <?php // echo $form->field($model, 'enroll_status') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var app\models\BannerSearch $model
 * @var yii\widgets\ActiveForm $form
 */
?>

<div class="banner-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'b_id') ?>

    <?= $form->field($model, 'b_title') ?>

    <?= $form->field($model, 'b_desc') ?>

    <?= $form->field($model, 'b_link') ?>

    <?= $form->field($model, 'b_foto') ?>

    <?php // echo $form->field($model, 'b_created_by') ?>

    <?php // echo $form->field($model, 'b_created_at') ?>

    <?php // echo $form->field($model, 'b_modified_by') ?>

    <?php // echo $form->field($model, 'b_modified_at') ?>

    <?php // echo $form->field($model, 'b_status') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

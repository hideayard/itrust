<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var app\models\NodeSearch $model
 * @var yii\widgets\ActiveForm $form
 */
?>

<div class="node-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'node_id') ?>

    <?= $form->field($model, 'node_name') ?>

    <?= $form->field($model, 'node_remark') ?>

    <?= $form->field($model, 'node_created_at') ?>

    <?= $form->field($model, 'node_created_by') ?>

    <?php // echo $form->field($model, 'node_modified_at') ?>

    <?php // echo $form->field($model, 'node_modified_by') ?>

    <?php // echo $form->field($model, 'node_status') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

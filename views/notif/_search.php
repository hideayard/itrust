<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var app\models\NotifSearch $model
 * @var yii\widgets\ActiveForm $form
 */
?>

<div class="notif-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'notif_id') ?>

    <?= $form->field($model, 'notif_from') ?>

    <?= $form->field($model, 'notif_title') ?>

    <?= $form->field($model, 'notif_text') ?>

    <?= $form->field($model, 'notif_date') ?>

    <?php // echo $form->field($model, 'notif_processed') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

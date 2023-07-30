<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var app\models\CourseSessionSearch $model
 * @var yii\widgets\ActiveForm $form
 */
?>

<div class="course-session-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'cs_id') ?>

    <?= $form->field($model, 'cs_course_id') ?>

    <?= $form->field($model, 'cs_remark') ?>

    <?= $form->field($model, 'cs_teacher') ?>

    <?= $form->field($model, 'cs_teacher_id') ?>

    <?php // echo $form->field($model, 'cs_date_start') ?>

    <?php // echo $form->field($model, 'cs_date_end') ?>

    <?php // echo $form->field($model, 'cs_hour_start') ?>

    <?php // echo $form->field($model, 'cs_hour_end') ?>

    <?php // echo $form->field($model, 'cs_dateline') ?>

    <?php // echo $form->field($model, 'cs_email') ?>

    <?php // echo $form->field($model, 'cs_next_course') ?>

    <?php // echo $form->field($model, 'cs_price') ?>

    <?php // echo $form->field($model, 'cs_code') ?>

    <?php // echo $form->field($model, 'cs_doc') ?>

    <?php // echo $form->field($model, 'cs_desc') ?>

    <?php // echo $form->field($model, 'cs_created_by') ?>

    <?php // echo $form->field($model, 'cs_created_at') ?>

    <?php // echo $form->field($model, 'cs_modified_by') ?>

    <?php // echo $form->field($model, 'cs_modified_at') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

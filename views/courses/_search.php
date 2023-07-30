<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var app\models\CoursesSearch $model
 * @var yii\widgets\ActiveForm $form
 */
?>

<div class="courses-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'course_id') ?>

    <?= $form->field($model, 'course_title') ?>

    <?= $form->field($model, 'course_desc') ?>

    <?= $form->field($model, 'course_content') ?>

    <?= $form->field($model, 'course_type') ?>

    <?php // echo $form->field($model, 'course_is_online') ?>

    <?php // echo $form->field($model, 'course_price') ?>

    <?php // echo $form->field($model, 'course_star') ?>

    <?php // echo $form->field($model, 'course_created_by') ?>

    <?php // echo $form->field($model, 'course_created_at') ?>

    <?php // echo $form->field($model, 'course_modified_by') ?>

    <?php // echo $form->field($model, 'course_modified_at') ?>

    <?php // echo $form->field($model, 'course_status') ?>

    <?php // echo $form->field($model, 'course_is_deleted') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

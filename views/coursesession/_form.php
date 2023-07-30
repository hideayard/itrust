<?php

use yii\helpers\Html;
use kartik\widgets\ActiveForm;
use kartik\builder\Form;
use kartik\datecontrol\DateControl;

/**
 * @var yii\web\View $this
 * @var app\models\CourseSession $model
 * @var yii\widgets\ActiveForm $form
 */
?>

<div class="course-session-form">

    <?php $form = ActiveForm::begin(['type' => ActiveForm::TYPE_HORIZONTAL, 'options' => ['enctype' => 'multipart/form-data']]); ?>

    <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">

                <div class="form-group highlight-addon field-course_session-cs_course_id required">
                    <label class="control-label has-star col-md-2" for="course_session-cs_course_id">Course</label>
                    <div class="col-md-10">
                        <select class="form-control" id="cs_course_id" name="CourseSession[cs_course_id]">
                            <?php foreach ($course as $k => $v) : ?>
                                <option value="<?= $k ?>"><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="help-block"></div>
                    </div>
                </div>

                <?php 
                echo $form->field($model, 'cs_date_start')->widget(DateControl::classname(), [
                    'displayFormat' => 'php:d-F-Y',
                    'value'=> date('y-M-d'),
                    'type'=>DateControl::FORMAT_DATE
                ]); 
                
                ?>

                <?php echo $form->field($model, 'cs_hour_start')->widget(DateControl::classname(), [
                    'displayFormat' => 'H:i:s',
                    'value'=>date('H:i:s'),
                    'type'=>DateControl::FORMAT_TIME
                ]); ?>

                <?php echo $form->field($model, 'cs_dateline')->widget(DateControl::classname(), [
                    'displayFormat' => 'php:d-F-Y',
                    'value'=> date('y-M-d'),
                    'type'=>DateControl::FORMAT_DATE
                ]); ?>

                <?= $form->field($model, 'cs_desc')->textarea(['placeholder' => "Enter Desc"]) ?>
                <?= $form->field($model, 'cs_email')->textInput(['placeholder' => "Enter Email", 'maxlength' => 50]) ?>
                <?= $form->field($model, 'cs_next_course')->textInput(['placeholder' => "Enter Next Course", 'maxlength' => 50]) ?>

            </div>
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">

                <div class="form-group highlight-addon field-course_session-cs_trainer_id required">
                    <label class="control-label has-star col-md-2" for="course_session-cs_trainer_id">Trainer</label>
                    <div class="col-md-10">
                        <select class="form-control" id="cs_teacher" name="CourseSession[cs_teacher]">
                            <?php foreach ($users as $k => $v) : ?>
                                <option value="<?= $v ?>"><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="help-block"></div>
                    </div>
                </div>

                <?php echo $form->field($model, 'cs_date_end')->widget(DateControl::classname(), [
                    'displayFormat' => 'php:d-F-Y',
                    'value'=> date('y-M-d'),
                    'type'=>DateControl::FORMAT_DATE
                ]); ?>

                <?php echo $form->field($model, 'cs_hour_end')->widget(DateControl::classname(), [
                    'displayFormat' => 'H:i:s',
                    'value'=>date('H:i:s'),
                    'type'=>DateControl::FORMAT_TIME
                ]); ?>

                <?= $form->field($model, 'cs_remark')->textInput(['placeholder' => "Enter Remark", 'maxlength' => 100]) ?>

                <?= $form->field($model, 'cs_code')->textInput(['placeholder' => "Enter Code",'maxlength' => 50]) ?>
                <?= $form->field($model, 'cs_price')->textInput(['placeholder' => "Enter Price", 'maxlength' => 25]) ?>
                <?= $form->field($model, 'cs_doc')->textInput(['placeholder' => "Enter Doc"]) ?>
            </div>
    </div>
    
<?php
    echo Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'),
        ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']
    );
    ActiveForm::end(); ?>

</div>

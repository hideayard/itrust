<?php

use yii\helpers\Html;
use kartik\widgets\ActiveForm;
use kartik\builder\Form;
use kartik\datecontrol\DateControl;

/**
 * @var yii\web\View $this
 * @var app\models\Enroll $model
 * @var yii\widgets\ActiveForm $form
 */
?>

<div class="enroll-form">

    <?php $form = ActiveForm::begin(['type' => ActiveForm::TYPE_HORIZONTAL, 'options' => ['enctype' => 'multipart/form-data']]); ?>

        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">

                <div class="form-group highlight-addon field-enroll-enroll_userid required">
                    <label class="control-label has-star col-md-2" for="enroll-enroll_userid">Participant</label>
                    <div class="col-md-10">
                        <select class="form-control" id="enroll-participant" name="Enroll[enroll_userid]">
                            <?php foreach ($users as $k => $v) : ?>
                                <option value="<?= $k ?>"><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="help-block"></div>
                    </div>
                </div>

                <div class="form-group highlight-addon field-enroll-enroll_courseid required">
                    <label class="control-label has-star col-md-2" for="enroll-enroll_courseid">Course</label>
                    <div class="col-md-10">
                        <select class="form-control" id="enroll-course" name="Enroll[enroll_courseid]">
                            <?php foreach ($courses as $k => $v) : ?>
                                <option value="<?= $k ?>"><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="help-block"></div>
                    </div>
                </div>

                <div class="form-group highlight-addon field-enroll-enroll_cs required">
                    <label class="control-label has-star col-md-2" for="enroll-enroll_cs">Teacher</label>
                    <div class="col-md-10">
                        <select class="form-control" id="enroll-course" name="Enroll[enroll_cs]">
                            <?php foreach ($cs as $k => $v) : ?>
                                <option value="<?= $k ?>"><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="help-block"></div>
                    </div>
                </div>

            </div>
            <div class="col-lg-6 col-md-6">

                <?= $form->field($model, 'enroll_status')->textInput(['placeholder' => "Enter Status",'required' => true]) ?>

                <?= $form->field($model, 'enroll_remark')->textarea(['placeholder' => "Enter Remark", 'rows' => 6,'required' => true]) ?>

            </div>
        </div>

    <?= Html::submitButton(
        $model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'),
        ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']
    );
    ActiveForm::end(); ?>

</div>

<?php

use yii\helpers\Html;
use kartik\widgets\ActiveForm;
use kartik\builder\Form;
use kartik\datecontrol\DateControl;
use dosamigos\ckeditor\CKEditor;
use kartik\widgets\StarRating;

/**
 * @var yii\web\View $this
 * @var app\models\Courses $model
 * @var yii\widgets\ActiveForm $form
 */
?>

<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Courses</h3>
    </div>

    <div class="card-body">


        <?php $form = ActiveForm::begin(['type' => ActiveForm::TYPE_HORIZONTAL, 'options' => ['enctype' => 'multipart/form-data']]); ?>

        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">

                <div class="form-group highlight-addon field-courses-course_title required">
                    <label class="control-label has-star col-md-2" for="courses-course_title">Template</label>
                    <div class="col-md-10">
                        <select class="form-control" id="template" name="template">
                            <?php foreach ($courses as $k => $v) : ?>
                                <option value="<?= $k ?>"><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="help-block"></div>
                    </div>
                </div>

            </div>
            <div class="col-lg-6 col-md-6">

                <div class="form-group highlight-addon field-courses-course_trainer required">
                    <label class="control-label has-star col-md-2" for="courses-course_trainer">Trainer</label>
                    <div class="col-md-10">
                        <select class="form-control" id="trainer" name="trainer">
                            <?php foreach ($users as $k => $v) : ?>
                                <option value="<?= $k ?>"><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="help-block"></div>
                    </div>
                </div>

            </div>
        </div>

        <?= Html::submitButton(
            $model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'),
            ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']
        );
        ActiveForm::end(); ?>

    </div>

</div>
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
            <div class="col-4">
                <?= $form->field($model, 'course_title')->textInput(['placeholder' => "Enter Title"]) ?>

                <?= $form->field($model, 'course_desc')->textInput(['placeholder' => "Enter Description"]) ?>

                <div class="form-group highlight-addon field-courses-course_trainer required">
                    <label class="control-label has-star col-md-2" for="courses-course_trainer">Trainer</label>
                    <div class="col-md-10">
                        <select class="form-control" id="course-trainer" name="Courses[course_trainer]">
                            <?php foreach ($users as $k => $v) : ?>
                                <option value="<?= $k ?>"><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="help-block"></div>
                    </div>
                </div>

            </div>
            <div class="col-4">

                <?= $form->field($model, 'course_status')->dropDownList([0 => 'Non-active', 1 => 'Active']) ?>

                <?= $form->field($model, 'course_type')->textInput(['placeholder' => "Enter Type"]) ?>

                <?= $form->field($model, 'course_photo')->fileInput(['placeholder' => "Enter link"]) ?>


            </div>

            <div class="col-4">
                <?= $form->field($model, 'course_price')->textInput(['placeholder' => "Enter Price"]) ?>

                <?= $form->field($model, 'course_star')->dropDownList([
                    0 => '0',
                    1 => '1',
                    2 => '2',
                    3 => '3',
                    4 => '4',
                    5 => '5'
                ]) ?>

            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <?= $form->field($model, 'course_content')->widget(CKEditor::className(), [
                    'options' => ['rows' => 6],
                    'preset' => 'basic'
                ]) ?>
            </div>
        </div>

        <?= Html::submitButton(
            $model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'),
            ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']
        );
        ActiveForm::end(); ?>

    </div>

</div>
<?php

use yii\helpers\Html;
use kartik\widgets\ActiveForm;
use kartik\builder\Form;
use kartik\datecontrol\DateControl;

/**
 * @var yii\web\View $this
 * @var app\models\DataSensors $model
 * @var yii\widgets\ActiveForm $form
 */
?>

<div class="data-sensors-form">

    <?php $form = ActiveForm::begin(['type' => ActiveForm::TYPE_HORIZONTAL]); echo Form::widget([

        'model' => $model,
        'form' => $form,
        'columns' => 1,
        'attributes' => [

            'created_at' => ['type' => Form::INPUT_WIDGET, 'widgetClass' => DateControl::classname(),'options' => ['type' => DateControl::FORMAT_DATETIME]],

            'modified_at' => ['type' => Form::INPUT_WIDGET, 'widgetClass' => DateControl::classname(),'options' => ['type' => DateControl::FORMAT_DATETIME]],

            'created_by' => ['type' => Form::INPUT_TEXT, 'options' => ['placeholder' => 'Enter Created By...']],

            'modified_by' => ['type' => Form::INPUT_TEXT, 'options' => ['placeholder' => 'Enter Modified By...']],

            's1' => ['type' => Form::INPUT_TEXT, 'options' => ['placeholder' => 'Enter S1...', 'maxlength' => 10]],

            's2' => ['type' => Form::INPUT_TEXT, 'options' => ['placeholder' => 'Enter S2...', 'maxlength' => 10]],

            's3' => ['type' => Form::INPUT_TEXT, 'options' => ['placeholder' => 'Enter S3...', 'maxlength' => 10]],

            's4' => ['type' => Form::INPUT_TEXT, 'options' => ['placeholder' => 'Enter S4...', 'maxlength' => 10]],

            's5' => ['type' => Form::INPUT_TEXT, 'options' => ['placeholder' => 'Enter S5...', 'maxlength' => 10]],

            's6' => ['type' => Form::INPUT_TEXT, 'options' => ['placeholder' => 'Enter S6...', 'maxlength' => 10]],

            's7' => ['type' => Form::INPUT_TEXT, 'options' => ['placeholder' => 'Enter S7...', 'maxlength' => 10]],

            's8' => ['type' => Form::INPUT_TEXT, 'options' => ['placeholder' => 'Enter S8...', 'maxlength' => 10]],

            's9' => ['type' => Form::INPUT_TEXT, 'options' => ['placeholder' => 'Enter S9...', 'maxlength' => 10]],

            'ip' => ['type' => Form::INPUT_TEXT, 'options' => ['placeholder' => 'Enter Ip...', 'maxlength' => 16]],

            'status' => ['type' => Form::INPUT_TEXT, 'options' => ['placeholder' => 'Enter Status...', 'maxlength' => 100]],

            'remark' => ['type' => Form::INPUT_TEXT, 'options' => ['placeholder' => 'Enter Remark...', 'maxlength' => 255]],

        ]

    ]);

    echo Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'),
        ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']
    );
    ActiveForm::end(); ?>

</div>

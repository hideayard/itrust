<?php

use yii\helpers\Html;
use kartik\widgets\ActiveForm;
use kartik\builder\Form;
use kartik\datecontrol\DateControl;

/**
 * @var yii\web\View $this
 * @var app\models\Node $model
 * @var yii\widgets\ActiveForm $form
 */
?>

<div class="node-form">

    <?php $form = ActiveForm::begin(['type' => ActiveForm::TYPE_HORIZONTAL]); echo Form::widget([

        'model' => $model,
        'form' => $form,
        'columns' => 1,
        'attributes' => [

            'node_name' => ['type' => Form::INPUT_TEXT, 'options' => ['placeholder' => 'Enter Node Name...', 'maxlength' => 255]],

            'node_created_by' => ['type' => Form::INPUT_TEXT, 'options' => ['placeholder' => 'Enter Node Created By...']],

            'node_modified_by' => ['type' => Form::INPUT_TEXT, 'options' => ['placeholder' => 'Enter Node Modified By...']],

            'node_created_at' => ['type' => Form::INPUT_WIDGET, 'widgetClass' => DateControl::classname(),'options' => ['type' => DateControl::FORMAT_DATETIME]],

            'node_modified_at' => ['type' => Form::INPUT_WIDGET, 'widgetClass' => DateControl::classname(),'options' => ['type' => DateControl::FORMAT_DATETIME]],

            'node_status' => ['type' => Form::INPUT_TEXT, 'options' => ['placeholder' => 'Enter Node Status...']],

            'node_remark' => ['type' => Form::INPUT_TEXT, 'options' => ['placeholder' => 'Enter Node Remark...', 'maxlength' => 255]],

        ]

    ]);

    echo Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'),
        ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']
    );
    ActiveForm::end(); ?>

</div>

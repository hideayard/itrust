<?php

use app\helpers\ImageHelper;
use yii\helpers\Html;
use kartik\widgets\ActiveForm;
use kartik\builder\Form;
use kartik\datecontrol\DateControl;

use function PHPSTORM_META\map;
use yii\helpers\Url;

/**
 * @var yii\web\View $this
 * @var app\models\Users $model
 * @var yii\widgets\ActiveForm $form
 */
?>

<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">User</h3>
    </div>

    <div class="card-body">

        <?php $form = ActiveForm::begin(['type' => ActiveForm::TYPE_HORIZONTAL, 'options' => ['enctype' => 'multipart/form-data']]); ?>

        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                <?= $form->field($model, 'user_name')->textInput(['placeholder' => "Enter link"]) ?>

                <?= $form->field($model, 'user_nama')->textInput(['placeholder' => "Enter title", 'rows' => 6]) ?>

                <?php if (!empty($model->user_foto)) : ?>
                    <img src="<?= ImageHelper::viewImage($model->user_foto) ?>" class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                <?php endif; ?>

                <?= $form->field($model, 'imageFile')->fileInput(['placeholder' => "Enter link"]) ?>
            </div>
            <div class="col-lg-6 col-md-6">
                <?= $form->field($model, 'user_pass')->passwordInput(['placeholder' => "Enter title", 'value' => '']) ?>

                <?= $form->field($model, 'user_tipe')->dropDownList([
                    'SUPERADMIN' => 'SUPERADMIN',
                    'ADMIN' => 'ADMIN',
                    'MAINTAINER' => 'MAINTAINER',
                    'USER' => 'USER',
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
<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use yii\helpers\Url;
use yii\widgets\Pjax;

$css = <<<css
.dropdown-menu {
    z-index: 99999;
}
css;

$this->registerCss($css);

$buttons = [];

if (Yii::$app->user->identity->user_tipe == 'ADMIN') {
    $buttons = [
        'view' => function ($url, $model) {
            return Html::a(
                '<span class="fa fa-search"></span>',
                Yii::$app->urlManager->createUrl(['courses/view', 'id' => $model->course_id, 'view' => 't']),
                ['title' => Yii::t('yii', 'View'),]
            );
        },
        'update' => function ($url, $model) {
            return Html::a(
                '<span class="fa fa-edit"></span>',
                Yii::$app->urlManager->createUrl(['courses/update', 'id' => $model->course_id, 'edit' => 't']),
                ['title' => Yii::t('yii', 'Edit'),]
            );
        },
        'delete' => function ($url, $model) {
            return Html::a(
                '<span class="fa fa-trash"></span>',
                Yii::$app->urlManager->createUrl(['courses/delete', 'id' => $model->course_id, 'delete' => 't']),
                ['title' => Yii::t('yii', 'Delete'),]
            );
        }
    ];
} elseif (Yii::$app->user->identity->user_tipe == 'TRAINER') {
    $buttons = [
        'view' => function ($url, $model) {
            return Html::a(
                '<span class="fa fa-users"></span>',
                Yii::$app->urlManager->createUrl(['courses/view', 'id' => $model->course_id, 'view' => 't']),
                ['title' => Yii::t('yii', 'Participants'),]
            );
        },
        'update' => function ($url, $model) {
            return Html::a(
                '<span class="fa fa-edit"></span>',
                Yii::$app->urlManager->createUrl(['courses/sections', 'id' => $model->course_id, 'edit' => 't']),
                ['title' => Yii::t('yii', 'Edit'),]
            );
        },
    ];
}

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\CoursesSearch $searchModel
 */

$this->title = 'Courses';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="courses-index">

    <?php if (Yii::$app->user->identity->user_tipe == 'ADMIN') : ?>
        <div style="margin-bottom: 10px">
            <div class="btn-group">
                <button type="button" class="btn btn-default">Create</button>
                <button type="button" class="btn btn-default dropdown-toggle dropdown-icon" data-toggle="dropdown" aria-expanded="true">
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <div class="dropdown-menu" role="menu" x-placement="top-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(68px, -165px, 0px);">
                    <a class="dropdown-item" href="<?= Url::to(['create']) ?>">New</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?= Url::to(['create-from-template']) ?>">From Template</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php Pjax::begin();
    echo GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'course_title',
            'course_desc',
            'course_type',
            [
                'label' => 'Status',
                'attribute' => 'course_status',
                'value' => function ($input) {
                    if($input['course_status'] == 1)
                    {
                        return "Active";
                    }
                    else {
                        return "Non Active";
                    }
                    
                }
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'buttons' => $buttons
            ],
        ],
        'responsive' => true,
        'hover' => true,
        'condensed' => true,
        'floatHeader' => true,
        'pager' => [
            'activePageCssClass' => 'active',
            'pageCssClass' => 'page-item',
            'disabledPageCssClass' => 'page-item disabled',
            'disabledListItemSubTagOptions' =>  [
                'class' => 'page-link',
            ],
            'linkOptions' => [
                'class' => 'page-link',
            ],
            'options' => [
                'class' => 'pagination justify-content-center m-0'
            ],
            'firstPageLabel' => '<span class="fa fa-fast-backward"></span>',
            'lastPageLabel' => '<span class="fa fa-fast-forward"></span>',
            'nextPageLabel' => '<span class="fa fa-step-forward"></span>',
            'prevPageLabel' => '<span class="fa fa-step-backward"></span>',
        ],
    ]);
    Pjax::end(); ?>

</div>
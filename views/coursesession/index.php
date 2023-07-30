<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use yii\widgets\Pjax;
use kartik\date\DatePicker;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\CourseSessionSearch $searchModel
 */

$this->title = 'Course Sessions';
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="course-session-index">

    <p>
        <?php echo Html::a('<span class="fa fa-plus"></span> Add', ['create'], ['class' => 'btn btn-success'])  ?>
    </p>

    <?php Pjax::begin();
    echo GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'class' => 'yii\grid\DataColumn',
                'label' => 'Nama Course',
                'attribute' => 'courseTitle',
                'value' => 'course.course_title',
                'filter' => Html::activeDropDownList($searchModel, 'cs_course_id', $courses, ['class' => 'form-control'])
            ],
            [
                'class' => 'yii\grid\DataColumn',
                'label' => 'Teacher Name',
                'attribute' => 'courseTeacher',
                'value' => 'cs_teacher',
                'filter' => Html::activeDropDownList($searchModel, 'cs_teacher', $teacher, ['class' => 'form-control'])
            ],
            'cs_date_start',
            'cs_date_end',
            // [
            //     'attribute'=>'cs_date_start',
            //     'value'=>'cs_date_start',
            //     'format'=>'raw',
            //     'filter'=>DatePicker::widget([
            //         'model' => $searchModel,
            //         'attribute' => 'cs_date_start',
            //             'clientOptions' => [
            //                 'autoclose' => true,
            //                 'format' => 'yyyy-m-d'
            //             ]
            //     ])
            // ],
            [
                'class' => 'yii\grid\ActionColumn',
                'buttons' => [
                    'view' => function ($url, $model) {
                        return Html::a(
                            '<span class="fa fa-search"></span>',
                            Yii::$app->urlManager->createUrl(['coursesession/view', 'id' => $model->cs_id, 'view' => 't']),
                            ['title' => Yii::t('yii', 'View'),]
                        );
                    },
                    'update' => function ($url, $model) {
                        return Html::a(
                            '<span class="fa fa-edit"></span>',
                            Yii::$app->urlManager->createUrl(['coursesession/update', 'id' => $model->cs_id, 'edit' => 't']),
                            ['title' => Yii::t('yii', 'Edit'),]
                        );
                    },
                    'delete' => function ($url, $model) {
                        return Html::a(
                            '<span class="fa fa-trash"></span>',
                            Yii::$app->urlManager->createUrl(['coursesession/delete', 'id' => $model->cs_id, 'delete' => 't']),
                            ['title' => Yii::t('yii', 'Delete'),]
                        );
                    }
                ],
            ],
        ],
        'responsive' => true,
        'hover' => true,
        'condensed' => true,
        'floatHeader' => true,
        'pjax' => true,
        'pjaxSettings' => [
            'neverTimeout' => true,
        ],
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
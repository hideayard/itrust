<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use yii\widgets\Pjax;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var app\models\EnrollSearch $searchModel
 */

$this->title = 'Enrollments';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="enroll-index">

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
                'label' => 'Course Name',
                'attribute' => 'courseTitle',
                'value' => 'course.course_title'
                //'filter' => Html::activeDropDownList($searchModel, 'enroll_courseid',$courses, ['class'=>'form-control'])
            ],
            [
                'class' => 'yii\grid\DataColumn',
                'label' => 'Participant Name',
                'attribute' => 'participantName',
                'value' => 'users.user_nama',
                'filter' => Html::activeDropDownList($searchModel, 'enroll_userid', $users, ['class' => 'form-control'])
            ],
            [
                'class' => 'yii\grid\DataColumn',
                'label' => 'Teacher',
                'attribute' => 'teacherName',
                'value' => 'enrollCs.cs_teacher',
                'filter' => Html::activeDropDownList($searchModel, 'enroll_cs', $course_session, ['class' => 'form-control'])
            ],
            'enroll_remark',


            [
                'class' => 'yii\grid\ActionColumn',
                'buttons' => [
                    'view' => function ($url, $model) {
                        return Html::a(
                            '<span class="fa fa-search"></span>',
                            Yii::$app->urlManager->createUrl(['enroll/view', 'id' => $model->enroll_id, 'view' => 't']),
                            ['title' => Yii::t('yii', 'View'),]
                        );
                    },
                    'update' => function ($url, $model) {
                        return Html::a(
                            '<span class="fa fa-edit"></span>',
                            Yii::$app->urlManager->createUrl(['enroll/update', 'id' => $model->enroll_id, 'edit' => 't']),
                            ['title' => Yii::t('yii', 'Edit'),]
                        );
                    },
                    'delete' => function ($url, $model) {
                        return Html::a(
                            '<span class="fa fa-trash"></span>',
                            Yii::$app->urlManager->createUrl(['enroll/delete', 'id' => $model->enroll_id, 'delete' => 't']),
                            ['title' => Yii::t('yii', 'Delete'),]
                        );
                    }
                ],
            ],
        ],
        'responsive' => true,
        'hover' => true,
        'condensed' => true,
        'floatHeader' => false,
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
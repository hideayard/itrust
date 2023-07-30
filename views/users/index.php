<?php

use app\helpers\ImageHelper;
use yii\helpers\Url;
use yii\helpers\Html;

$this->title = "Users";
?>

<form>
    <div class="input-group mb-3" id="course-search">
        <input type="text" class="form-control" placeholder="Search user" aria-label="Search user" aria-describedby="basic-addon2" name="UsersSearch[user_nama]" value="<?= Html::encode($searchModel->user_nama) ?>"">
        <div class=" input-group-append">
        <span class="input-group-text" id="basic-addon2"><span class="fa fa-search"></span></span>
    </div>
    </div>
</form>

<div class="card card-solid">
    <div class="card-body pb-0">
        <div class="row d-flex align-items-stretch">
            <?php
            foreach ($dataProvider->models as $model) : ?>
                <div class="col-12 col-sm-6 col-md-4 d-flex align-items-stretch">
                    <div class="card bg-light" style="min-width: 100%">
                        <div class="card-header text-muted border-bottom-0">
                            <?= $model->user_name ?>
                        </div>
                        <div class="card-body pt-0">
                            <div class="row">
                                <div class="col-7">
                                    <h2 class="lead"><b><?= $model->user_nama ?></b></h2>
                                    <p class="text-muted text-sm"><b>User Type: </b> <?= $model->user_tipe ?> </p>
                                    <ul class="ml-4 mb-0 fa-ul text-muted">
                                        <li class="small"><span class="fa-li"><i class="fas fa-lg fa-envelope"></i></span> Email: <?= $model->user_email ?></li>
                                        <li class="small"><span class="fa-li"><i class="fas fa-lg fa-phone"></i></span> Phone : <?= $model->user_hp ?></li>
                                    </ul>
                                </div>
                                <div class="col-5 text-center">
                                    <img src="<?= ImageHelper::viewImage($model->user_foto) ?>" alt="user-avatar" class="img-circle img-fluid">
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="text-right">
                                <?php if (Yii::$app->user->identity->user_tipe == 'ADMIN') : ?>
                                    <a href="<?= Url::to(['users/update', 'id' => $model->user_id]) ?>" class="btn btn-sm bg-teal">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                <?php endif; ?>
                                <a href="<?= Url::to(['users/view', 'id' => $model->user_id]) ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-user"></i> View Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    </div>
    <!-- /.card-body -->
    <div class="card-footer">
        <nav aria-label="Contacts Page Navigation">
            <?php
            echo \yii\widgets\LinkPager::widget([
                'pagination' => $dataProvider->pagination,
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
            ]);
            ?>
        </nav>
    </div>
    <!-- /.card-footer -->


</div>
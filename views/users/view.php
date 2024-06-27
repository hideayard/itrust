<?php

use app\helpers\ImageHelper;
use yii\base\View;
use yii\helpers\Url;

$this->title = "User Details"
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">

            <!-- Profile Image -->
            <div class="card card-primary card-outline">
                <div class="card-body box-profile">
                    <div class="text-center">
                        <img class="profile-user-img img-fluid img-circle" src="<?= ImageHelper::viewImage($model->user_foto) ?>" alt="User profile picture">
                    </div>

                    <h3 class="profile-username text-center"><?= $model->user_nama ?></h3>

                    <p class="text-muted text-center"><?= $model->user_tipe ?></p>

                    <ul class="list-group list-group-unbordered mb-3">
                        <li class="list-group-item">
                            <b>Join Date</b> <a class="float-right"><?= date("Y-m-d", strtotime($model->created_at)) ?></a>
                        </li>
                    </ul>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->

            <!-- About Me Box -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Contact</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <strong><i class="fas fa-envelope mr-1"></i> Email</strong>

                    <?= $model->user_email ?>

                    <hr>

                    <strong><i class="fas fa-phone mr-1"></i> Phone</strong>

                    <?= $model->user_hp ?>

                    <hr>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->
        </div>
        <!-- /.col -->
        <div class="col-md-9">
            
            <!-- /.card -->
            <div class="users-update">

                <?= $this->render('_form', [
                    'model' => $model,
                ]) ?>

            </div>
        </div>
        <!-- /.col -->
    </div>
    <!-- /.row -->
</div>
<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Auth */
/* @var $query_model app\models\AuthLdapQueryForm */
/* @var $searchModel app\models\UserSearch */

$this->title = \Yii::t('auth', 'Edit Authentication Method Nr. {id} of type {type}', [
	'id' => $model->id,
	'type' => $model->obj->type,
]);

$this->params['breadcrumbs'][] = ['label' => \Yii::t('auth', 'Authentication Methods'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = \Yii::t('auth', 'Edit');
?>
<div class="auth-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render($model->obj->form, [
        'model' => $model,
        'query_model' => $query_model,
        'searchModel' => $searchModel,
    ]) ?>

</div>
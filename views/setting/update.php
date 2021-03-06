<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $model app\models\Setting */
/* @var $contents string rendered previews/key.php contents */

$this->title = \Yii::t('setting', 'Edit Setting: {setting}', [
    'setting' => \Yii::t('setting', $model->name)
]);
$this->params['breadcrumbs'][] = ['label' => \Yii::t('setting', 'Settings'), 'url' => ['index']];
$this->params['breadcrumbs'][] = \Yii::t('setting', 'Edit');

$js = <<< 'SCRIPT'
/* To initialize BS3 popovers set this below */
$(function () { 
    $("[data-toggle='popover']").popover(); 
});

$('.hint-block').each(function () {
    var $hint = $(this);

    if (!$hint.hasClass('leave')) {

        $hint.parent().find('label').after('&nbsp<a tabindex="0" role="button" class="hint glyphicon glyphicon-question-sign"></a>');

        $hint.parent().find('a.hint').popover({
            html: true,
            trigger: 'focus',
            placement: 'right',
            title:  $hint.parent().find('label').html(),
            //title:  'Description',
            toggle: 'popover',
            container: 'body',
            content: $hint.html()
        });

        $hint.remove()
    }
});
SCRIPT;
// Register tooltip/popover initialization javascript
$this->registerJs($js);

$js = <<< 'SCRIPT'
// @param object jquery object
// @return the value
function get_val (object) {
    if (object.is(':checkbox')) {
        return object.is(':checked');
    } else {
        return object.val();
    }
}

// @param object jquery object
// @param object the value
// @return void
function set_val (object, value) {
    if (object.is(':checkbox')) {
        object.prop('checked', value);
    } else {
        object.val(value);
    }
}

var pre;
$("input[name='Setting[null]']").click(function(){
    if ($(this).is(':checked')) {
        pre = get_val($('#setting-value'));
        set_val($('#setting-value'), get_val($('#setting-default_value')));
    } else {
        set_val($('#setting-value'), pre);
    }
});

$("#setting-value").on('change keyup paste', function(){
    if (get_val($(this)) == get_val($("input[name='Setting[default_value]']"))) {
        $("input[name='Setting[null]']").prop( "checked", true );
    } else {
        $("input[name='Setting[null]']").prop( "checked", false );        
    }
});

function reload() {
    if ($("#preview").length > 0) {
        $.pjax.reload({
            container: "#preview",
            fragment: "body",
            type: 'POST',
            data: {
                'preview[value]': get_val($("#setting-value")),
                'preview[key]': get_val($("#setting-key")),
                '_csrf': get_val($("input[name='_csrf']"))
            },
            async:true
        });
    }
}

$("#preview-button").click(function(event) {
    $("#setting-form").on('beforeSubmit', function(event) {
        $('#preview-button').attr("disabled", true);
        $("#setting-form").off('beforeSubmit');
        reload();
        return false;
    });

    $("#setting-form").on('afterValidate', function(event, messages, errorAttributes) {
        $("#setting-form").off('afterValidate');
        if (errorAttributes.length > 0) {
            $('#preview-button').removeAttr("disabled");
        }
    });
});

$("#preview").on('pjax:send', function() {
    $('#preview-div').toggleClass('loading');
})

$("#preview").on('pjax:complete', function() {
    $('#preview-div').toggleClass('loading');

    // trigger animation again, see https://stackoverflow.com/a/45036752/2768341
    var el = $('#preview-div').parent()[0]; /* native DOM element */
    el.style.animation = 'none';
    el.offsetHeight; /* trigger reflow */
    el.style.animation = null;

    setTimeout(function(){
        $('#preview-button').removeAttr("disabled");
    }, 500);
})

SCRIPT;
$this->registerJs($js);

if ($model->value === null) {
    $model->null = true;
}
$model->value = $model->value === null ? $model->default_value : $model->value;

?>
<div class="exam-update">
    <h1><?= Html::encode($this->title) ?></h1>
    <div class="setting-form">
        <div class="row">
            <div class="col-md-6">
                <?php $form = ActiveForm::begin([
                    'id' => 'setting-form',
                    'enableClientValidation' => true,
                ]); ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <?= Html::label(\Yii::t('setting', $model->name)); ?>
                        <div class="hint-block"><?= $model->description; ?></div>
                    </div>
                    <div class="panel-body">

                        <div class="form-group col-md-12">
                            <?= $form->field($model, 'key')->hiddenInput()->label(false)->hint(false); ?>
                            <?= $form->field($model, 'default_value')->hiddenInput()->label(false)->hint(false); ?>

                            <?= call_user_func(array($form->field($model, 'value'), $model->typeMapping()[$model->type][0]), $model->typeMapping()[$model->type][1])->label(false)->hint(false); ?>

                            <div class="hint-block leave"><?= array_key_exists(2, $model->typeMapping()[$model->type]) ? $model->typeMapping()[$model->type][2] : null; ?></div>

                        </div>

                        <div class="col-md-12">
                            <?= $form->field($model, 'null')->checkbox() ?>
                        </div>

                        <div class="col-md-12">
                            <?= Html::label($model->getAttributeLabel('default_value')); ?>
                            <div class="hint-block"><?= $model->getAttributeHint('default_value'); ?></div>
                            <div class="hint-block leave">
                                <?= $model->renderSetting($model->default_value, $model->type); ?>
                            </div>
                        </div>

                    </div>
                    <ul class="list-group">
                        <li class="list-group-item">
                            <?= Html::submitButton($model->isNewRecord ? \Yii::t('setting', 'Create') : \Yii::t('setting', 'Apply'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
                            <?= Html::submitButton(\Yii::t('setting', 'Reload Preview'), [
                                'id' => 'preview-button',
                                'class' => 'btn btn-primary pull-right',
                                'disabled' => $contents === null,
                            ]) ?>
                        </li>
                    </ul>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
            <div class="col-md-6">
                <div class="panel panel-default highlight">
                    <div class="panel-heading"><?= Html::label(Yii::t('setting', 'Preview')); ?></div>
                    <div id="preview-div" class="panel-body preview">
                        <?php Pjax::begin(['id' => 'preview']); ?>
                            <?= $contents === null ? Yii::t('setting', 'There is no preview for this setting.') : $contents ?>
                        <?php Pjax::end(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
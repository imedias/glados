<?php

use kartik\switchinput\SwitchInput;

/* @var $id integer */
/* @var $label string */
/* @var $hint string */
/* @var $form yii\widgets\ActiveForm */
/* @var $setting app\models\ExamSetting */
/* @var $members app\models\ExamSetting[] */

$libre_createbackup_path = $members['libre_createbackup_path'];
$id2 = $libre_createbackup_path->id === null ? $id . "a" : $libre_createbackup_path->id;

$js = <<< SCRIPT
$("#ExamSettings_{$id}_value").on("switchChange.bootstrapSwitch change", function(){
    if ($(this).is(':checked')) {
        $('#ExamSettings_{$id2}_value').attr("readonly", false);
    } else if ($(this).not(':checked')) {
        $('#ExamSettings_{$id2}_value').attr("readonly", true);
    }
});
SCRIPT;
$this->registerJs($js);

?>

<div class="panel panel-default">
    <div class="panel-heading">
        <?= $form->field($setting, 'value', [
            'options' => [
                'class' => ''
            ],
        ])->widget(SwitchInput::classname(), [
            'pluginOptions' => [
                'size' => 'mini',
                'onText' => \Yii::t('app', 'ON'),
                'offText' => \Yii::t('app', 'OFF'),
            ],
            'options' => [
                'id' => "ExamSettings_{$id}_value",
                'name' => "ExamSettings[$id][value]",
                'label' => $label
            ],
        ])->label(false)->hint($hint); ?>
    </div>
    <div class="panel-body">
        <div style="display:none;">
            <?= $form->field($libre_createbackup_path, 'key')->hiddenInput([
                'id' => "ExamSettings_{$id2}_key",
                'name' => "ExamSettings[$id2][key]",
                'data-id' => $id2,
            ])->label(false)->hint(false); ?>
            <?= $form->field($libre_createbackup_path, 'belongs_to')->hiddenInput([
                'id' => "ExamSettings_{$id2}_belongs_to",
                'name' => "ExamSettings[$id2][belongs_to]",
                'value' => $id,
            ])->label(false)->hint(false); ?>
        </div>
        <?= $form->field($libre_createbackup_path, 'value', [
            'template' => '{label}<div class="input-group"><div class="input-group-addon">' . \Yii::t('exams', '...to the directory') . '</div>{input}</div>{hint}{error}'
        ])->textInput([
            'id' => "ExamSettings_{$id2}_value",
            'name' => "ExamSettings[$id2][value]",
            'readonly' => !$setting->value,
        ])->label(false); ?>
    </div>
</div>
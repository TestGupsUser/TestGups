<div class="row">
    <div class="col-12">
        <div class="alert alert-danger" role="alert">
            <?php echo Yii::t(
                'sandbox/moderate/all', 
                'Текст о невозможности подтвердить контрагента: `В поданном заявлении присутствуют записи с неподтверждёнными контрагентами, текущие настройки приёмной кампании не позволяют вам подтвердить контрагента. Необходимо отклонить заявление с просьбой заменить контрагента для документа.`'
            ); ?>
        </div>
    </div>
</div>
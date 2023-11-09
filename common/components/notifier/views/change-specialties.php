<div>
    <h4>
        <?= Yii::t(
            'abiturient/notifier/change-specialities',
            'Приветствие; для письма при изменении НП заявления модератором в менеджере оповещений: `Здравствуйте, {fio}!`',
            ['fio' => $fio]
        ) ?>
    </h4>

    <p>
        <?= Yii::t(
            'abiturient/notifier/change-specialities',
            'Текст письма; для письма при изменении НП заявления модератором в менеджере оповещений: `В вашем заявление, для приёмной кампании "{campaignName}", модератором, были внесены изменения в состав направлений подготовки.`',
            ['campaignName' => $campaignName]
        ) ?>
    </p>
    <p>
        <b>
            <?= Yii::t(
                'abiturient/notifier/common',
                'Текст письма об отсутствии необходимости отвечать: `Пожалуйста, не отвечайте на это письмо, так как оно сгенерировано автоматически.`'
            ) ?>
        </b>
    </p>
</div>
<div>
    <h4>
        <?= Yii::t(
            'abiturient/notifier/application-declined',
            'Приветствие; для письма при отклонения заявления модератором в менеджере оповещений: `Здравствуйте, {fio}!`',
            ['fio' => $fio]
        ) ?>
    </h4>

    <p>
        <?= Yii::t(
            'abiturient/notifier/application-declined',
            'Текст письма; для письма при отклонения заявления модератором в менеджере оповещений: `Ваше заявление отклонено по причине: {comment}. Вы можете отредактировать старое или подать новое заявление в личном кабинете.`',
            ['comment' => $comment]
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
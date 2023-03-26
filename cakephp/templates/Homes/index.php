<?= $this->Form->create($entity, ['type' => 'file', 'novalidate' => true, 'name' => 'myform']); ?>
<?= $this->Form->hidden('action', ['id' => 'action']); ?>

<div>

    <?php foreach(range(0, 2) as $cnt): ?>
    <?= $this->Form->text("password.{$cnt}", ['maxLength' => 1]); ?>
    <?php endforeach; ?>

</div>

<div>
    <?= $this->Form->button('開ける', ['id' => 'open']) ?>
    <?= $this->Form->button('閉じる', ['id' => 'lock']) ?>
</div>

<?= $this->Form->end() ?>

<script>
    var submitBtns = ["open", "lock"];
    submitBtns.forEach(_id => {
        var btnOpen = document.getElementById(_id);
        btnOpen.addEventListener('click', function() {
            document.myform.action.value = _id;
            document.myform.submit();
        })
    });
</script>

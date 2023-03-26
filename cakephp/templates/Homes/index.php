<meta name="viewport" content="width=device-width,initial-scale=1">

<?= $this->Form->create($entity, ['type' => 'file', 'name' => 'myform']); ?>
<?= $this->Form->hidden('action', ['id' => 'action']); ?>

<div class="passwords-container">

    <?php foreach(range(0, 2) as $cnt): ?>
    <div class="password-container">
        <?= $this->Form->number("password.{$cnt}", ['class' => 'password', 'min' => '0', 'max' => '9', 'required' => true, 'placeholder' => '0']); ?>
    </div>
    <?php endforeach; ?>

</div>

<div class="button03">
    <a href="#" id="open" class="btn btn--yellow btn--cubic btn--shadow">開ける</a>
    <a href="#" id="lock" class="btn btn--yellow btn--cubic btn--shadow">閉じる</a>
</div>

<?= $this->Form->end() ?>

<script type="text/javascript">
    // 
    const elems = document.querySelectorAll(".password");
    elems.forEach(function(elem) {
        elem.addEventListener("input", function(event) {
            const val = elem.value;
            if (val.length == 0) {
                return false;
            }
            if (val.length > 1) {
                elem.value = val.charAt(1);
            }

            focusNextPw();
        });
    });

    focusNextPw();

    // 
    function focusNextPw() {
        const elems = document.querySelectorAll(".password");
        for (let i = 0; i < elems.length; i++) {
            const elem = elems[i];
            if (!elem.value) {
                elem.focus();
                break;
            }
        }
    }



    // 
    var submitBtns = ["open", "lock"];
    submitBtns.forEach(_id => {
        const elem = document.querySelector("#" + _id);
        elem.addEventListener("click", function(event) {
            event.preventDefault();
            document.myform.action.value = _id;

            const pws = [...document.querySelectorAll('.password')].map(elm => elm.value).join("");
            if (pws.length == 3) {
                document.myform.submit();
            } else {
                focusNextPw();
            }
        });
    });
</script>

<style>
    .passwords-container {
        display: flex;

        justify-content: space-between;
        flex-flow: row wrap;
        align-items: stretch;
    }

    .password-container {
        display: flex;
        width: 30%;
        height: 5em;
    }

    .password {
        text-align: center;
        font-size: 18px;
        font-weight: 700;

        width: 100%;
        height: 100%;
    }

    .password-container:not(:last-child):after {
        /* content: "→";
        top: 0;
        margin: auto; */
        /* padding: 0 1em; */
    }

    input:invalid {
        border-color: red;
    }

    input:valid {
        border-color: blue;
    }


    .button03 a {
        text-decoration: none;

        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 1em auto;
        padding: 1em 2em;
        width: 70%;
        color: #2285b1;
        font-size: 18px;
        font-weight: 700;
        border: 2px solid #2285b1;
    }

    .button03 a::after {
        content: '';
        width: 5px;
        height: 5px;
        border-top: 3px solid #2285b1;
        border-right: 3px solid #2285b1;
        transform: rotate(45deg);
    }

    .button03 a:hover {
        color: #333333;
        text-decoration: none;
        background-color: #a0c4d3;
    }

    .button03 a:hover::after {
        border-top: 3px solid #333333;
        border-right: 3px solid #333333;
    }

    form:has(input:invalid)>.button03>a {
        border-color: #aaa;
        color: #aaa;
    }
</style>
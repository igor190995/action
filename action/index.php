<?

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

global $USER, $APPLICATION;

$APPLICATION->SetTitle("Скидки для Вас");
if($USER->IsAuthorized() == true){
    $userId = $USER->GetID();
    ?>
    <div class="action">
        <label id="errorLabel"></label>
        <div class="get-action form-group">
            <button class="btn btn-success" onclick="getAction('<?=$userId?>')">Получить скидку</button>
            <br>
            <label id="discount_name"></label>
            <br>
            <label id="coupon"></label>
        </div>
        <div style="margin-top: 20px" class="check-action form-group">
            <input type="text" value="" id="inputcheckaction">
            <button class="btn btn-primary" onclick="checkAction('<?=$userId?>')">Проверить скидку</button>
            <br>
            <label id="discount_name_check"></label>
        </div>
    </div>

    <script>

        window.ajaxPath = '/action/ajax.php';

        function getAction(userId) {

            var labelError = document.getElementById('errorLabel');

            if(userId) {

                labelError.innerText = '';

                BX.ajax({
                    url: window.ajaxPath,
                    data: {
                        'OPTION': 'GET_ACTION',
                        'USER_ID': userId,
                    },
                    method: 'POST',
                    dataType: 'json',
                    timeout: 30,
                    async: true,
                    processData: true,
                    scriptsRunFirst: true,
                    emulateOnload: true,
                    start: true,
                    cache: false,
                    onsuccess: function (data) {
                        console.log('success');
                        console.log('data', data);

                        if(data.COUPON.toString().length > 0){
                            document.getElementById('coupon').innerText = "Код скидки: " + data.COUPON;
                        }else if(data.ERROR_COUPON_CREATE.toString().length > 0){
                            document.getElementById('coupon').innerText = "";
                            labelError.innerText = "Ошибка создания купона: " + data.ERROR_COUPON_CREATE;
                        }

                        if(data.DISCOUNT_NAME.toString().length > 0){
                            document.getElementById('discount_name').innerText = data.DISCOUNT_NAME;
                        }
                        else{
                            labelError.innerText += "Ошибка получения скидки";
                        }
                    },
                    onfailure: function () {
                        console.log("error data");
                    }
                });
            }
            else{
                labelError.innerText = "Ошибка! Id Вашего пользователя не определен";
            }
        }

        function checkAction(userId) {
            var inputCheckValue = document.getElementById('inputcheckaction').value.toString().trim();

            var labelError = document.getElementById('errorLabel');

            if(inputCheckValue.length > 0){
                labelError.innerText = '';

                BX.ajax({
                    url: window.ajaxPath,
                    data: {
                        'OPTION': 'CHECK_ACTION',
                        'USER_ID': userId,
                        'COUPON': inputCheckValue
                    },
                    method: 'POST',
                    dataType: 'json',
                    timeout: 30,
                    async: true,
                    processData: true,
                    scriptsRunFirst: true,
                    emulateOnload: true,
                    start: true,
                    cache: false,
                    onsuccess: function (data) {
                        console.log('success');
                        console.log('data', data);

                        if(data.res === 'coupon not found'){
                            labelError.innerText = 'Введенный купон не найден';
                        }
                        else if(data.res === 'discount not available'){
                            document.getElementById('discount_name_check').innerText = "Введенный купон не доступен";
                        }else {
                            document.getElementById('discount_name_check').innerText = "Введенный купон доступен для: " + data.DISCOUNT_NAME;
                        }

                    },
                    onfailure: function () {
                        console.log("error data");
                    }
                });
            }
        }
    </script>
    <?
}
else{
    echo "Вы не авторизованы, авторизуйтесь для получения кода скидки";
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");

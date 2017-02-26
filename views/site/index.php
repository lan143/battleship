<?php

/* @var $this yii\web\View */

$this->title = 'Морской бой';
?>
<div>
    Игроков онлайн: <span id="online">0</span>
</div>

<div id="fields">
    <table id="my-field" cellspacing="0">
        <caption>Ваше поле</caption>
        <tbody>
        <?php
        for ($x = 0; $x < 10; $x++)
        {
            echo "<tr>";

            for ($y = 0; $y < 10; $y++)
            {
                echo '<td class="cell" data-x="'.$x.'" data-y="'.$y.'"></td>';
            }

            echo "</tr>";
        }
        ?>
        <tr>
            <td colspan="10" style="text-align: center;"><a href="#" onClick="conn.send(JSON.stringify({opcode: 'cmsg_request_field', data: {}}));">Сгенерировать новое поле</a></td>
        </tr>
        </tbody>
    </table>

    <div id="join-block">
        <span style="display: none;">Вы находитесь в очереди</span><br />
        <a id="join-queue" class="button">Найти противника</a>
    </div>

    <table id="opponent-field">
        <caption>Поле противника</caption>
        <tbody>
        <?php
        for ($x = 0; $x < 10; $x++)
        {
            echo "<tr>";

            for ($y = 0; $y < 10; $y++)
            {
                echo '<td class="cell" data-x="'.$x.'" data-y="'.$y.'"></td>';
            }

            echo "</tr>";
        }
        ?>
        </tbody>
    </table>

    <div id="info">
        <span id="current-move"></span>
        <a class="button" id="leave-btn">Покинуть игру</a>
    </div>

    <div class="clear"></div>

    <div class="chat">
        <h2>Чат</h2>
        <div class="chat-wrapper">
            <div id="chat"><p>Вы подключены к чату</p></div>
        </div>

        <form id="chat-form">
            <div class="input-group">
                <input type="text" name="message" id="chat_message" />
                <span class="input-group-btn">
                    <button id="send_message" type="button">Отправить</button>
                </span>
            </div>
        </form>
    </div>
</div>
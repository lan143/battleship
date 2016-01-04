<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Морской бой</title>
        
        <link href="css/jquery-ui.min.css" type="text/css" rel="stylesheet" />
        <link href="css/styles.css" type="text/css" rel="stylesheet" />
        <link href="css/styles.css" type="text/css" rel="stylesheet" />

        <!--[if lt IE 9]>
          <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
          <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>

    <body>
        <div id="page">
            <div id="header">
                <img src="images/logo.jpg">
            </div>
            
            <div id="content">
                <div>
                    Игроков онлайн: <span id="online">0</span>
                </div>
                
                <div id="fields">
                    <table id="my-field" cellspacing="0">
                        <caption>Ваше поле</caption>
                        <tbody>
                            <?
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
                            <?
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
                
                
                <script src="js/jquery-2.1.3.min.js"></script>
                <script src="js/jquery-ui.min.js"></script>
                <script src="js/websocket.js"></script>
                <script src="js/scripts.js"></script>
            </div>
        </div>
    </body>
</html>
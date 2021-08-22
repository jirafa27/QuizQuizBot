<?php

$data = json_decode(file_get_contents('php://input'), TRUE);
$servername = "localhost";
$database = "quiz";
$username = "root";
$password = "";
$conn = mysqli_connect($servername, $username, $password, $database);



const TOKEN = '1944998700:AAEl1EqoeGTOZvUhYQvs4_Vn_2SQc_P92to';

$chat_id = $data['message']['chat'] ['id'];
if(isset($data['callback_query']))
{
    $user_ans = $data['callback_query']['data'];
    $sql = 'UPDATE questions SET user_ans = "' . $user_ans . '" WHERE question = "' . $data['callback_query']['message']['text']  .'";';
    mysqli_query($conn, $sql);
    $sql = 'SELECT right_ans FROM questions WHERE question = "' . $data['callback_query']['message']['text'] . '";';
    $right_ans = mysqli_fetch_array(mysqli_query($conn, $sql))['right_ans'];
    if ($user_ans == $right_ans) {
        $sql = 'UPDATE info SET points = points+1 WHERE user_name = (SELECT user_name FROM info ORDER BY id DESC LIMIT 1);';
        mysqli_query($conn, $sql);
    }
    $sql = 'SELECT points FROM info WHERE user_name = (SELECT user_name FROM info ORDER BY id DESC LIMIT 1);';
    $points = mysqli_fetch_array(mysqli_query($conn, $sql))['points'];
    $send_data = [
        'text'=> $user_ans == $right_ans? 'УРЯЯЯ! Правильно=) Количество очков равно ' . $points : 'Неа=(. Количество очков равно ' . $points,
        'chat_id'=> $data['callback_query']['from']['id'],
        'reply_markup' => [
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
            'keyboard' => [
                [
                    ['text' => 'Следующий вопрос']
                ]
            ]
        ]
    ];
    $res = sendTelegram('sendMessage', $send_data);
}

else {
    switch ($data['message']['text']) {
        case '/start':
        case 'Заново':
            $send_data = [
                'text' => 'Здравствуйте, как Вас зовут?'
            ];
            break;
        case 'Гарри Поттер':
        case 'География':
        case 'Разное':
        case 'Следующий вопрос':
            if ($data['message']['text'] == 'Гарри Поттер' or $data['message']['text'] == 'География' or $data['message']['text'] == 'Разное') {
                $sql = 'UPDATE info SET test_name="' . $data['message']['text'] . '" WHERE id = (SELECT MAX(id) FROM info);';
                mysqli_query($conn, $sql);
            }
            $sql = 'SELECT * FROM questions WHERE user_ans is null AND test_name = (SELECT test_name FROM info ORDER BY id DESC LIMIT 1) ORDER BY id LIMIT 1;';

            $arr = mysqli_fetch_array(mysqli_query($conn, $sql));
            $sql = 'SELECT points FROM info WHERE user_name = (SELECT user_name FROM info ORDER BY id DESC LIMIT 1);';
            $points = mysqli_fetch_array(mysqli_query($conn, $sql))['points'];
            if ($arr == null)
            {

                $send_data =
                    [

                    'text' => 'Вы прошли тест. Ваш результат: ' . $points . "\n",
                    'reply_markup' => [
                        'resize_keyboard' => true,
                        'one_time_keyboard' => true,
                        'keyboard' => [
                            [
                                ['text' => 'Заново'],
                            ]
                        ]
                    ]
                ];
            }
            else {
                $keyboard = [
                    "inline_keyboard" => [[['text' => $arr['ansA'], 'callback_data' => $arr['ansA']]],
                        [['text' => $arr['ansB'], 'callback_data' => $arr['ansB']]],
                        [['text' => $arr['ansC'], 'callback_data' => $arr['ansC']]],
                        [['text' => $arr['ansD'], 'callback_data' => $arr['ansD']]]
                    ]];
                $keyboard = json_encode($keyboard, true);
                $send_data = [
                    'text' => $arr['question'],
                    'reply_markup' => $keyboard
                ];

            }
            break;
        default:
            $sql = 'UPDATE questions SET user_ans = NULL;';
            mysqli_query($conn, $sql);
            $send_data = [
                'text' => 'Хорошо, ' . $data['message']['text'] . '! Выберите тест',
                'reply_markup' => [
                    'resize_keyboard' => true,
                    'one_time_keyboard' => true,
                    'keyboard' => [
                        [
                            ['text' => 'Гарри Поттер'],
                            ['text' => 'География'],
                            ['text' => 'Разное'],
                        ]
                    ]
                ]
            ];
            $sql = 'INSERT INTO info (user_name, test_name, points) VALUES ("' . $data['message']['text'] . '", "",0)';
            mysqli_query($conn, $sql);
    }

    $send_data['chat_id'] = $chat_id;

    $res = sendTelegram('sendMessage', $send_data);
    mysqli_close($conn);

}

function sendTelegram($method, $data, $headers = [])
{
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => 'https://api.telegram.org/bot' . TOKEN . '/' . $method,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array_merge(array("Content-Type: application/json"))
    ]);
    $result = curl_exec($curl);
    curl_close($curl);
    return (json_decode($result, 1) ? json_decode($result, 1) : $result);
}

?>
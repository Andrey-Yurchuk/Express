<?php

declare(strict_types=1);

namespace Express;

use PDO;

class MyApi
{
    private string $api_url;

    public function __construct($api_url)
    {
        $this->api_url = $api_url;
    }

    public function list_vacancies(int $vid = 0): object|array
    {
        $params = [
            'status' => 'all',
            'id_user' => $this->self_get_option('superjob_user_id'),
            'with_new_response' => 0,
            'order_field' => 'date',
            'order_direction' => 'desc',
            'page' => 0,
            'count' => 100,
        ];

        $vacancies = [];
        $found = false;

        do {
            $params['page']++;
            $res = $this->api_send($this->api_url . '/hr/vacancies/?' . http_build_query($params));
            $res_o = json_decode($res);

            if ($res !== false && is_object($res_o) && isset($res_o->objects)) {
                $vacancies = array_merge($vacancies, $res_o->objects);

                // Для конкретной вакансии, иначе возвращаем все
                if ($vid > 0) {
                    foreach ($res_o->objects as $value) {
                        if ($value->id == $vid) {
                            $found = $value;
                            break;
                        }
                    }
                }
            }
        }
        while ($found === false && $res_o->more);

        return is_object($found) ? $found : $vacancies;
    }

    private function api_send($url): bool|string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    private function self_get_option(string $option_name): string|bool
    {
        $dsn = 'mysql:host=localhost;dbname=db';
        $username = 'admin';
        $password = 'StrongPassword123!';

        try {
            $pdo = new PDO($dsn, $username, $password);
            $stmt = $pdo->prepare('SELECT option_value FROM options_table WHERE option_name = :option_name');
            $stmt->bindParam(':option_name', $option_name);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                return $result['option_value'];
            } else {
                return false;
            }
        } catch (PDOException $e) {
            echo 'Ошибка при работе с базой данных: ' . $e->getMessage();
            return false;
        }
    }
}

// Пример использования
$api_url = 'https://example.com/api';
$my_api = new MyApi($api_url);
$vacancies = $my_api->list_vacancies();
var_dump($vacancies);
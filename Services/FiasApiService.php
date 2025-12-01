<?php

namespace Modules\NobilikBranches\Services;

use GuzzleHttp\Client; // Используем Guzzle HTTP Client
use GuzzleHttp\Exception\GuzzleException; // Для обработки исключений Guzzle
use Exception; // Для обработки отсутствующих ключей

// NOTE: Удаляем 'use Illuminate\Support\Facades\Http;' и 'use Illuminate\Http\Client\PendingRequest;'

class FiasApiService // Вероятно, класс следует переименовать в DadataApiService
{
    // Адрес конечной точки DaData для подсказок по адресу
    protected string $baseUrl = 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/address';
    
    protected string $apiKey;
    protected string $secretKey; 
    protected Client $client; // Свойство для экземпляра Guzzle Client

    public function __construct()
    {
        $moduleName = "nobilikbranches"; 
        
        $this->apiKey = config("{$moduleName}.dadata.key");
        $this->secretKey = config("{$moduleName}.dadata.secret");

        // Если ключи не найдены, выбрасываем исключение
        if (empty($this->apiKey) || empty($this->secretKey)) {
             throw new Exception("DADATA API keys are missing in config '{$moduleName}.dadata'.");
        }

        // Инициализируем Guzzle Client с базовыми заголовками
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Token ' . $this->apiKey,
                'X-Secret' => $this->secretKey,
            ],
            // Установка таймаута для предотвращения зависания
            'timeout'  => 5.0, 
        ]);
    }

    /**
     * Поиск адресов по тексту с использованием DaData.
     *
     * @param string $query Текст для поиска.
     * @param int $limit Максимальное количество результатов.
     * @return array
     */
    public function search(string $query, int $limit = 10): array
    {
        // Тело POST-запроса, которое DaData ожидает в формате JSON
        $postData = [
            'query' => $query,
            'count' => $limit,
            "locations_boost" => [
                [
                    "kladr_id" => "54"
                ]
            ],
        ];

        try {
            // DaData использует POST-запрос. Guzzle автоматически сериализует 'json' в тело.
            $response = $this->client->request('POST', '', [ // Пустая строка, т.к. base_uri уже установлен
                'json' => $postData,
            ]);

            // Проверяем HTTP-статус ответа (200 OK)
            if ($response->getStatusCode() !== 200) {
                return [];
            }
            
            // Получаем тело ответа и декодируем JSON
            $data = json_decode($response->getBody()->getContents(), true);

        } catch (GuzzleException $e) {
            // Обработка ошибок соединения, таймаутов
            \Log::error('Dadata API Guzzle Error: ' . $e->getMessage());
            return [];
        }

        if (!isset($data['suggestions'])) {
            return [];
        }

        // Приводим результат к удобному виду для вашего фронтенда
        return array_map(function($item) {
            $fias_id = $item['data']['fias_id'] ?? null;
            
            return [
                'guid' => $fias_id, 
                'address' => $item['value'] ?? null,
                'meta' => $item['data'] ?? null,
            ];
        }, $data['suggestions']);
    }
}
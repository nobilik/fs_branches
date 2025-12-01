<?php

namespace Modules\NobilikBranches\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\NobilikBranches\Entities\Address;
use Modules\NobilikBranches\Services\FiasApiService; 

use Illuminate\Support\Facades\Log;

class BranchAddressController extends Controller
{
    protected FiasApiService $dadata;

    public function __construct(FiasApiService $dadata)
    {
        $this->dadata = $dadata;
    }

    /**
     * Поиск адресов для автозаполнения.
     * Сначала ищем в локальной базе, затем обращаемся к DaData API.
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $limit = (int) $request->get('limit', 10);

        if (!$query || mb_strlen($query) < 3) {
            return response()->json([]);
        }

        // 1. Поиск в локальной базе
        $localResults = Address::query()
            ->where('full_address', 'ILIKE', "%{$query}%") // Ищем по полному адресу
            ->limit($limit)
            ->get(['id', 'guid', 'full_address as address', 'meta']); // Адаптируем выбор полей

        // 2. Если найдено меньше лимита, дополняем через DaData API
        $remaining = $limit - $localResults->count();
        $apiResults = [];

        if ($remaining > 0) {
            // Вызываем DadataApiService.search()
            $apiResults = $this->dadata->search($query, $remaining);
        }

        return response()->json([
            'local' => $localResults,
            'remote' => $apiResults,
        ]);
    }

    /**
     * Создание нового адреса.
     * Ожидает 'address_data' - JSON-строку с полным объектом DaData.
     */
    public function store(Request $request)
    {
        // 1. Валидация
        $request->validate([
            'address_data' => 'required|json', // Полный JSON-объект от DaData
        ]);

        // 2. Декодирование и подготовка данных
        $item = json_decode($request->address_data, true);
        
        // DaData использует 'value' для полного адреса и 'fias_id' для GUID
        $guid = $item['data']['fias_id'] ?? null;
        $fullAddress = $item['value'] ?? null;
        
        if (empty($fullAddress)) {
             return response()->json(['message' => 'Не удалось извлечь полный адрес из данных.'], 422);
        }

        $metaData = $item['data']; 
        $metaJson = json_encode($metaData);

        // 3. Создание или поиск существующего адреса по GUID (если он есть)
        $address = Address::firstOrCreate(
            ['guid' => $guid], // Ищем по GUID
            [
                'full_address' => $fullAddress,
                'guid' => $guid, // На случай, если guid был null, сохраняем его
                'meta' => $metaJson, // Сохраняем весь объект 'data'
            ]
        );

        return response()->json($address, 201);
    }

    /**
     * Получение адреса
     */
    public function show(Address $address)
    {
        return response()->json($address);
    }

    /**
     * Обновление адреса
     * Ожидает 'address_data' - JSON-строку с полным объектом DaData.
     */
    public function update(Request $request, Address $address)
    {
        $request->validate([
            'address_data' => 'required|json',
        ]);
        
        $item = json_decode($request->address_data, true);

        $guid = $item['data']['fias_id'] ?? null;
        $fullAddress = $item['value'] ?? null;
        
        if (empty($fullAddress)) {
             return response()->json(['message' => 'Не удалось извлечь полный адрес из данных.'], 422);
        }

        $address->update([
            'full_address' => $fullAddress,
            'guid' => $guid,
            'meta' => $item['data'],
        ]);

        return response()->json($address);
    }

    /**
     * Удаление адреса
     */
    public function destroy(Address $address)
    {
        // Добавьте проверку на привязку адреса к филиалам, если необходимо
        $address->delete();
        return response()->json(['deleted' => true]);
    }
}
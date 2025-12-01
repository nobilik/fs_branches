<?php

namespace Modules\NobilikBranches\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Conversation;
use Modules\NobilikBranches\Entities\Branch;
use Modules\NobilikBranches\Entities\ConversationBranch;
use Modules\NobilikBranches\Entities\Address;
use Modules\Tags\Entities\Tag;
use Modules\Tags\Entities\ConversationTag;
use Modules\NobilikGroupedTags\Entities\TagGroup;
use Modules\NobilikGroupedTags\Entities\TagGroupTag;

use Modules\NobilikBranches\Http\Requests\StoreBranchRequest;
use Modules\NobilikBranches\Http\Requests\UpdateBranchRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;

class BranchController extends Controller
{
    /**
     * Список филиалов с фильтрацией.
     */
        public function index(Request $request)
    {
        $query = Branch::query()->with('address', 'tags');
        // Получаем общий поисковый запрос (например, из поля ввода 'q')
        $q = $request->get('q'); 

        // Если запрос не пустой, используем scope searchAll для поиска по всем полям (название, тег, адрес).
        if ($q) {
            $query->searchAll($q);
        }

        $branches = $query->paginate(10);
        
        // Явно прикрепляем все текущие параметры запроса к ссылкам пагинации, включая 'q'.
        $branches->appends($request->query()); 

        // Получаем все теги для выпадающего списка фильтрации
        $tags = Tag::all();

        // Передаем и филиалы, и теги в представление.
        return view('nobilikbranches::index', compact('branches', 'tags'));
    }
    // public function index(Request $request)
    // {
    //     $query = Branch::query()->with('address', 'tags');

    //     if ($request->filled('name')) {
    //         $query->where('name', 'like', '%'.$request->name.'%');
    //     }

    //     if ($request->filled('full_address')) {
    //         $query->whereHas('address', function ($q) use ($request) {
    //             // Проверка на корректность поля 'full_address' в модели Address
    //             $q->where('full_address', 'like', '%'.$request->full_address.'%');
    //         });
    //     }

    //     if ($request->filled('tag_id')) {
    //         $query->whereHas('tags', function ($q) use ($request) {
    //             // Использование полного имени таблицы для избежания конфликтов
    //             $q->where('tags.id', $request->tag_id); 
    //         });
    //     }

    //     $branches = $query->paginate(10);
        
    //     // Получаем все теги для выпадающего списка фильтрации
    //     $tags = Tag::all(); // Предполагая, что Tag находится в Modules\Tags\Entities\Tag

    //     // Передаем и филиалы, и теги в представление
    //     return view('nobilikbranches::index', compact('branches', 'tags'));
    // }

    /**
     * Форма создания филиала.
     */
    public function create()
    {
        return view('nobilikbranches::create');
    }

    /**
     * Сохранение нового филиала с поддержкой AJAX и обычной формы.
     *
     * @param  \App\Http\Requests\StoreBranchRequest  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store(StoreBranchRequest $request)
    {
        // Вся логика создания филиала остается внутри транзакции
        $branch = DB::transaction(function () use ($request) {
            
            // 1. Создание или поиск адреса
            $metaJson = json_encode($request->address_meta);
            $address = Address::firstOrCreate(
                ['guid' => $request->address_guid],
                [
                    'full_address' => $request->full_address,
                    'meta' => $metaJson 
                ]
            );

            // 2. Создание филиала
            $branch = Branch::create([
                'name' => $request->name,
                'address_id' => $address->id,
                'comment' => $request->comment,
            ]);

            // 3. Синхронизация тегов
            if ($request->filled('tags')) {
                $tags = $request->tags;
                
                // Предполагаем, что $this->filterTagsByMaxGroup() существует
                $tags = $this->filterTagsByMaxGroup($request->tags); 
                $branch->tags()->sync($tags);
            }
            
            // Добавляем адрес в объект, если нужно вернуть его в JSON
            $branch->load('address', 'tags'); 
            
            return $branch; // Возвращаем созданный объект Branch из транзакции
        });

        // 4. Определение типа ответа
        if ($request->wantsJson()) {
            // AJAX-ответ: возвращаем JSON с созданным объектом и статусом 201
            return response()->json([
                'message' => 'Филиал успешно создан.',
                'branch' => $branch,
            ], 201); // 201 Created
        }

        // Обычный POST-запрос: возвращаем редирект
        return redirect()->route('branches.index')->with('success', 'Филиал создан');
    }

    /**
     * Форма редактирования.
     */
    public function edit(Request $request, $branchId)
    {
        $branch = Branch::findOrFail($branchId);
        $branch->load('address', 'tags');
        return view('nobilikbranches::edit', compact('branch'));
    }

    /**
     * Обновление филиала.
     */
    public function update(UpdateBranchRequest $request, $branchId)
    {
        $branch = Branch::findOrFail($branchId);
        $branch = DB::transaction(function () use ($request, $branch) {
            
            // 1. Создание или обновление (firstOrCreate) Адреса
            // Теперь учитываем поле meta
            $address = Address::firstOrCreate(
                ['guid' => $request->address_guid],
                [
                    'full_address' => $request->full_address,
                    // Добавляем сохранение метаданных
                    'meta' => $request->filled('address_meta') ? json_decode($request->address_meta, true) : null,
                ]
            );

            // 2. Обновление филиала
            $branch->update([
                'name' => $request->name,
                'address_id' => $address->id,
                'comment' => $request->comment,
            ]);

            // 3. Синхронизация тегов
            if ($request->filled('tags')) {
                $tags = $request->tags;
                
                if (!empty($tags)) {
                    // Предполагаем, что $this->filterTagsByMaxGroup() существует
                    $tags = $this->filterTagsByMaxGroup($tags); 
                    $branch->tags()->sync($tags);
                }
            }
            
            // Загружаем обновленные отношения для JSON-ответа
            $branch->load('address', 'tags'); 
            
            return $branch; // Возвращаем обновленный объект Branch из транзакции
        });

        // 4. Определение типа ответа (как в store)
        if ($request->wantsJson()) {
            // AJAX-ответ: возвращаем JSON с обновленным объектом
            return response()->json([
                'message' => 'Филиал успешно обновлен.',
                'branch' => $branch,
            ], 200); // 200 OK
        }

        // Обычный POST-запрос: возвращаем редирект
        return redirect()->route('branches.index')->with('success', 'Филиал обновлён');
    }

    /**
     * Удаление филиала.
     * * @param \Modules\NobilikBranches\Entities\Branch $branch
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $branchId)
    {
        // Находим филиал вручную. Если не найден, Laravel бросит 404.
        $branch = Branch::findOrFail($branchId);
        
        // Используем транзакцию, чтобы обеспечить атомарность операций удаления
        DB::transaction(function () use ($branch) {
            
            // 1. Удаление связи с тегами (если используется many-to-many)
            // Detach удаляет записи из pivot-таблицы (branch_tag)
            $branch->tags()->detach();
            
            // 2. Сохраняем ID адреса, чтобы проверить его позже
            $addressId = $branch->address_id;

            // 3. Удаление самого филиала
            // Eloquent автоматически обработает удаление связей в таблице tickets (если связь настроена на CASCADE)
            // Если нет, тикеты, связанные с этим филиалом, должны быть обработаны до удаления.
            // Если тикеты связаны с адресом, это не влияет на их удаление.
            $branch->delete();
            
            // 4. Опциональное удаление адреса (Address)
            // Внимание: Этот блок закомментирован. Читайте примечание ниже!
            /*
            if ($addressId) {
                $isAddressUsed = Branch::where('address_id', $addressId)->exists();
                
                // Удаляем адрес, только если он не используется другими филиалами
                if (!$isAddressUsed) {
                    $address = \Modules\Addresses\Entities\Address::find($addressId); // Замените на фактическую модель Address
                    if ($address) {
                        $address->delete();
                    }
                }
            }
            */
            
            // Если тикеты (tickets) были связаны непосредственно с этим филиалом (one-to-many),
            // необходимо убедиться, что их foreign key (branch_id) имеет значение ON DELETE CASCADE в миграции,
            // иначе они должны быть удалены или обновлены здесь.
            // Предполагаем, что CASCADE настроен.

        });

        // 5. Определение типа ответа
        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Филиал удален.'
            ], 200);
        }

        return redirect()->route('branches.index')->with('success', 'Филиал удален.');
    }


    /**
     * Прикрепление филиала к тикету.
     */
    public function attachToConversation(Request $request, $branchId)
    {
        // --- 1. Инициализация и получение данных ---
        
        $conversation = Conversation::findOrFail($request->conversation_id);
        $branch = Branch::findOrFail($branchId);

        // Теги филиала (только ID)
        $branchTags = $branch->tagIds(); 
        
        // Теги, которые УЖЕ привязаны к заявке (только ID)
        $conversationTags = ConversationTag::where('conversation_id', $conversation->id)
                                        ->pluck('tag_id')
                                        ->toArray();
        
        // Набор тегов для финальной синхронизации
        $finalTags = $conversationTags;
        
        // Получаем все группы с их тегами
        $groups = TagGroup::with('tags')->get();
        
        // --- 2. Обработка тегов, не входящих в группы ---

        // Все теги, принадлежащие хотя бы одной группе
        $allGroupedTagIds = $groups->pluck('tags.*.id')->flatten()->unique()->toArray();
        
        // Теги филиала, не принадлежащие ни одной группе
        $ungroupedBranchTags = array_diff($branchTags, $allGroupedTagIds);
        
        // Просто добавляем эти теги в финальный список (без проверок)
        $finalTags = array_unique(array_merge($finalTags, $ungroupedBranchTags));

        // --- 3. Логика замены/добавления тегов по группам ---

        foreach ($groups as $group) {
            $maxTags = (int) $group->max_tags_for_conversation; 
            
            // Теги этой группы у филиала
            $branchGroupTags = array_intersect($branchTags, $group->tags->pluck('id')->toArray());
            
            // Теги этой группы, которые УЖЕ есть в финальном списке (включая те, что были изначально)
            $convGroupTags = array_intersect($finalTags, $group->tags->pluck('id')->toArray());
            
            $newTagsCount = count($branchGroupTags);
            
            // Если у филиала есть теги этой группы и есть лимит (> 0)
            if ($maxTags > 0 && $newTagsCount > 0) {
                
                // СЛУЧАЙ: ЗАМЕНА или ДОБАВЛЕНИЕ С ЛИМИТОМ
                
                // 1. Удаляем существующие теги этой группы из финального списка
                $finalTags = array_diff($finalTags, $convGroupTags);
                
                // 2. Добавляем теги филиала, но только в пределах лимита
                $tagsToAdd = array_slice($branchGroupTags, 0, $maxTags);
                $finalTags = array_merge($finalTags, $tagsToAdd);
                
            } else if ($maxTags == 0 && $newTagsCount > 0) {
                
                // СЛУЧАЙ: ЛИМИТА НЕТ (просто добавляем все теги филиала этой группы)
                $finalTags = array_unique(array_merge($finalTags, $branchGroupTags));
                
            }
        }
        
        // Окончательная очистка от дубликатов
        $finalTags = array_unique($finalTags);
        
        // --- 4. Синхронизация тегов заявки (удаление старых, добавление новых) ---
        
        // Удаляем все существующие записи в conversation_tag для текущей заявки
        ConversationTag::where('conversation_id', $conversation->id)->delete();

        // Затем привязываем только теги из финального списка
        foreach ($finalTags as $tagId) {
            $tag = Tag::find($tagId);
            if ($tag) {
                // Используем метод модуля для создания связи
                $tag->attachToConversation($conversation->id);
            }
        }

        // --- 5. Привязка филиала к заявке ---
        
        $conversationBranch = ConversationBranch::firstOrNew(
            ['conversation_id' => $conversation->id]
        );

        $conversationBranch->branch_id = $branch->id;
        $conversationBranch->attached_by = auth()->id(); 
        
        $conversationBranch->save();

        return response()->json(['success' => true]);
    }

// // no free tags allowed
//     public function attachToConversation(Request $request, $branchId)
//     {
//         // --- 1. Инициализация и получение данных ---
        
//         $conversation = Conversation::findOrFail($request->conversation_id);
//         $branch = Branch::findOrFail($branchId);

//         // Теги филиала (только ID)
//         $branchTags = $branch->tagIds(); 
        
//         // Теги, которые УЖЕ привязаны к заявке (только ID)
//         // Используем ConversationTag, чтобы избежать проблем с отношениями
//         $conversationTags = ConversationTag::where('conversation_id', $conversation->id)
//                                         ->pluck('tag_id')
//                                         ->toArray();
        
//         // Набор тегов для финальной синхронизации
//         $finalTags = $conversationTags;
        
//         // Получаем все группы с их тегами
//         $groups = TagGroup::with('tags')->get();

//         // --- 2. Логика замены/добавления тегов по лимиту ---

//         foreach ($groups as $group) {
//             $maxTags = (int) $group->max_tags_for_conversation; 
            
//             // Теги этой группы у филиала
//             $branchGroupTags = array_intersect($branchTags, $group->tags->pluck('id')->toArray());
            
//             // Теги этой группы, которые УЖЕ есть в заявке
//             $convGroupTags = array_intersect($finalTags, $group->tags->pluck('id')->toArray());
            
//             $newTagsCount = count($branchGroupTags);
//             $currentCount = count($convGroupTags);
            
//             // Если у филиала есть теги этой группы и есть лимит
//             if ($maxTags > 0 && $newTagsCount > 0) {
                
//                 // СЛУЧАЙ: ЗАМЕНА или ДОБАВЛЕНИЕ С ЛИМИТОМ
                
//                 // Удаляем существующие теги этой группы из финального списка
//                 $finalTags = array_diff($finalTags, $convGroupTags);
                
//                 // Добавляем теги филиала, но только в пределах лимита
//                 $tagsToAdd = array_slice($branchGroupTags, 0, $maxTags);
//                 $finalTags = array_merge($finalTags, $tagsToAdd);
                
//             } else if ($maxTags == 0 && $newTagsCount > 0) {
                
//                 // СЛУЧАЙ: ЛИМИТА НЕТ (просто добавляем все)
//                 $finalTags = array_unique(array_merge($finalTags, $branchGroupTags));
                
//             }
//         }
        
//         // Очищаем от возможных дубликатов
//         $finalTags = array_unique($finalTags);
        
//         // --- 3. Синхронизация тегов заявки (удаление старых, добавление новых) ---
        
//         // Сначала удаляем все существующие теги заявки, чтобы сделать "sync" вручную
//         ConversationTag::where('conversation_id', $conversation->id)->delete();

//         // Затем привязываем только теги из финального списка
//         foreach ($finalTags as $tagId) {
//             $tag = Tag::find($tagId);
//             // Используем метод из модуля, как вы указали
//             if ($tag) {
//                 $tag->attachToConversation($conversation->id);
//             }
//         }

//         // --- 4. Привязка филиала к заявке ---
        
//         $conversationBranch = ConversationBranch::firstOrNew(
//             ['conversation_id' => $conversation->id]
//         );

//         $conversationBranch->branch_id = $branch->id;
//         $conversationBranch->attached_by = auth()->id(); 
        
//         $conversationBranch->save();

//         return response()->json(['success' => true]);
//     }


    /**
     * AJAX поиск филиалов для выбора в модалке
     * По имени филиала, полному адресу и имени тега
     */
    public function search(Request $request)
    {
        $q = $request->get('q', '');
        $limit = (int) $request->get('limit', 10);

        if (strlen($q) < 3) {
            return response()->json([]);
        }

        $branches = Branch::with('address', 'tags')
            ->searchAll($q) // Используем новый Scope
            ->limit($limit)
            ->get();

            

        $result = $branches->map(function ($b) {
            return [
                'id' => $b->id,
                'name' => $b->name,
                'full_address' => $b->address->full_address ?? '',
                'tags' => $b->tags->map(fn($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'color' => $t->color
                ])
            ];
        });

        return response()->json($result);
    }

    public function ajaxAttachTag(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|integer',
            'tag_name' => 'required|string',
        ]);

        $branch = Branch::findOrFail($request->branch_id);

        $tag = \Modules\Tags\Entities\Tag::firstOrCreate(['name' => $request->tag_name]);

        $branch->tags()->syncWithoutDetaching([$tag->id]);

        return response()->json([
            'status' => 'success',
            'tag' => [
                'name' => $tag->name,
                'url'  => route('branches.index', ['branch_tag' => $tag->name])
            ]
        ]);
    }

    public function ajaxDetachTag(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|integer',
            'tag_name' => 'required|string',
        ]);

        $branch = Branch::findOrFail($request->branch_id);

        $tag = \Modules\Tags\Entities\Tag::where('name', $request->tag_name)->first();

        if ($tag) {
            $branch->tags()->detach($tag->id);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Создание нового тега через AJAX (или поиск существующего).
     */
    public function ajaxCreateTag(Request $request)
    {
        // 1. Валидация: Должна принимать ключ 'name', как отправлено из JS.
        $request->validate([
            'name' => 'required|string|max:255', // Ограничение длины обязательно
        ]);
        
        // Получаем имя тега
        $tagName = $request->input('name'); // <--- Ключ должен быть 'name', а не 'tag_name'

        // 2. Создание или поиск тега
        // Используем firstOrCreate, чтобы избежать дублирования
        $tag = \Modules\Tags\Entities\Tag::firstOrCreate(
            ['name' => $tagName],
            [
                'name' => $tagName,
                'color' => 0, // Установите значение по умолчанию
            ]
        );

        // 3. Возврат данных в формате, который ожидает JS
        // JS ожидает объект 'tag' с id и name напрямую, без обертки в 'status' или 'result'.
        // Возвращаем объект тега с HTTP-статусом 201 Created.
        return response()->json([
            'id' => $tag->id,
            'name' => $tag->name,
            'color' => $tag->color ?? 0, // Возвращаем цвет, если он нужен для UI
        ], 201); // 201 Created
    }

    public function ajaxTagAutocomplete(Request $request)
    {
        $q = $request->get('q');

        $tags = \Modules\Tags\Entities\Tag::where('name', 'ILIKE', "%$q%")
            ->limit(20)
            ->get();

        return response()->json([
            'results' => $tags->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'color' => $t->getColor()
            ])
        ]);
    }


    /**
     * Ограничение по max_tags_for_conversation
     */
    protected function filterTagsByMaxGroup(array $tagIds)
    {
        $groups = TagGroup::with('tags')->get();
        $result = [];

        foreach ($groups as $group) {
            $groupTagIds = TagGroupTag::pluck('tag_id')->toArray();
            $intersection = array_intersect($tagIds, $groupTagIds);

            if ($group->max_tags_for_conversation && count($intersection) > $group->max_tags_for_conversation) {
                $intersection = array_slice($intersection, 0, $group->max_tags_for_conversation);
            }

            $result = array_merge($result, $intersection);
        }

        // Добавляем теги, которые не входят в группы
        $ungrouped = array_diff($tagIds, $groups->flatMap(fn($g) => $g->tags->pluck('id'))->toArray());
        $result = array_merge($result, $ungrouped);

        return array_unique($result);
    }
}

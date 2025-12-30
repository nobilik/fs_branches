<?php

namespace Modules\NobilikBranches\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Tags\Entities\Tag;
use App\Conversation;

class Branch extends Model
{

    protected $table = 'branches';

    protected $fillable = [
        'name',
        'comment',
        'address_id',
    ];

    /**
     * Один объект — один адрес
     */
    public function address()
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    /**
     * Теги объекта
     * Используем настоящую модель тегов FreeScout: Modules\Tags\Entities\Tag
     * ->withTimestamps(); ???
     */ 
    public function tags()
    {
        return $this->belongsToMany(
            Tag::class,
            'branch_tag',
            'branch_id',
            'tag_id'
        );
    }

    public function tagIds()
    {
        return $this->tags()->pluck('tag_id')->toArray();
    }

    /**
     * Тикеты, относящиеся к объекту
     */
    public function conversations()
    {
        return $this->hasMany(Conversation::class, 'branch_id');
    }

    /**
     * Получает объект Branch по ID тикета.
     * Используется как статический метод, чтобы не трогать модель Conversation.
     * Usage: Branch::getByConversationId($conversationId)
     */
    public static function getByConversationId(int $conversationId)
    {
        // Находим запись в промежуточной таблице по ID тикета
        $pivotEntry = ConversationBranch::where('conversation_id', $conversationId)->first();

        if (!$pivotEntry) {
            return null;
        }

        // Загружаем Branch через найденный ID
        return self::find($pivotEntry->branch_id);
    }


    // public function conversationLinks()
    // {
    //     // Объект может быть связан со многими записями в промежуточной таблице
    //     return $this->hasMany(ConversationBranch::class, 'branch_id', 'id');
    // }

    // /**
    //  * Метод получения тикетов по объекту.
    //  * $branch->conversations
    //  */
    // public function conversations()
    // {
    //     // Получаем все тикеты через связанную таблицу
    //     return $this->hasManyThrough(
    //         \App\Conversation::class,
    //         ConversationBranch::class,
    //         'branch_id',       // Внешний ключ в ConversationBranch (промежуточная таблица)
    //         'id',              // Внешний ключ в Conversation (конечная таблица)
    //         'id',              // Локальный ключ в Branch (исходная таблица)
    //         'conversation_id'  // Локальный ключ в ConversationBranch, указывающий на Conversation
    //     );
    // }

    /**
     * Scope для поиска объектов по имени, адресу или тегам.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $searchQuery
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchAll(Builder $query, string $searchQuery): Builder
    {
        $q = $searchQuery;
        
        return $query->where(function (Builder $query) use ($q) {
            // 1. Поиск по имени
            $query->where('name', 'ILIKE', "%{$q}%");
            
            // 2. Поиск по адресу (OR EXISTS для belongsTo)
            $query->orWhereRaw('EXISTS (
                SELECT *
                FROM addresses
                WHERE addresses.id = branches.address_id
                AND full_address ILIKE ?
            )', ["%{$q}%"]);

            // 3. Поиск по тегам (OR EXISTS для belongsToMany)
            $query->orWhereRaw('EXISTS (
                SELECT *
                FROM tags
                INNER JOIN branch_tag ON tags.id = branch_tag.tag_id
                WHERE branch_tag.branch_id = branches.id
                AND tags.name ILIKE ?
            )', ["%{$q}%"]);
        });
    }
}

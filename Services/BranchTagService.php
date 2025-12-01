<?php
namespace Modules\NobilikBranches\Services;

use Modules\NobilikBranches\Entities\Branch;
use Modules\Tags\Entities\Tag;
use Modules\NobilikBranches\Entities\TagGroup; // группированные теги модуль
use Modules\NobilikBranches\Entities\TagGroupTag;
use Modules\Tags\Entities\ConversationTag;
use Illuminate\Support\Facades\DB;
use App\Conversation;

class BranchTagService
{
    /**
     * Прикрепляет branch к conversation и копирует теги.
     * Логика:
     *  - получить теги филиала
     *  - для каждой группы с ограничением max_tags_for_conversation проверять конфликт:
     *      если у conversation уже есть теги из этой группы и branch содержит теги из той же группы,
     *      то заменяем (удаляем теги conversation из этой группы и прикрепляем теги филиала).
     *  - Иначе просто прикрепляем теги филиала (attachToConversation).
     *
     * Возвращает ассоциативный массив результат.
     */
    public function attachBranchToConversation(int $branchId, int $conversationId): array
    {
        $branch = Branch::find($branchId);
        $conversation = Conversation::find($conversationId);

        if (!$branch || !$conversation) {
            return ['status' => 'error', 'message' => 'Branch or conversation not found'];
        }

        // 1) сохраняем связь в conversation_branch (upsert)
        DB::table('conversation_branch')->updateOrInsert(
            ['conversation_id' => $conversationId],
            ['branch_id' => $branchId, 'updated_at' => now(), 'created_at' => now()]
        );

        // 2) получаем id тегов филиала
        $branchTagIds = $branch->tags()->pluck('tags.id')->toArray();

        if (empty($branchTagIds)) {
            return ['status' => 'success', 'message' => 'Branch attached, no branch tags to copy'];
        }

        // 3) получаем все группированные группы с пересечением на branchTagIds
        // TagGroupTag таблица связывает tag_group_id <-> tag_id
        $groupIds = \Modules\NobilikBranches\Entities\TagGroupTag::whereIn('tag_id', $branchTagIds)
            ->pluck('tag_group_id')->unique()->toArray();

        // Для каждой найденной группы с группировкой берём её конфиг
        foreach ($groupIds as $gid) {
            $group = TagGroup::with('tags')->find($gid);
            if (!$group) continue;

            // теги группы
            $groupTagIds = $group->tags->pluck('id')->toArray();

            // какие теги из conversation уже принадлежат этой группе?
            $existingInConv = ConversationTag::where('conversation_id', $conversationId)
                ->whereIn('tag_id', $groupTagIds)
                ->pluck('tag_id')->toArray();

            // какие из branchTagIds принадлежат этой группе
            $branchInGroup = array_values(array_intersect($branchTagIds, $groupTagIds));

            if ($group->max_tags_for_conversation > 0) {
                // если есть лимит и уже есть теги в conversation из этой группы - мы будем заменить их:
                if (!empty($existingInConv)) {
                    // удаляем старые теги из этой группы у беседы
                    DB::table('conversation_tag')->where('conversation_id', $conversationId)
                        ->whereIn('tag_id', $existingInConv)->delete();

                    // затем прикрепляем теги филиала (но не более лимита)
                    $toAttach = array_slice($branchInGroup, 0, $group->max_tags_for_conversation);
                    foreach ($toAttach as $tid) {
                        $tag = Tag::find($tid);
                        if ($tag) $tag->attachToConversation($conversationId);
                    }
                    // done for this group
                    continue;
                }
            }

            // иначе: просто прикрепляем теги филиала, если они ещё не прикреплены
            foreach ($branchInGroup as $tid) {
                $already = ConversationTag::where('conversation_id', $conversationId)
                    ->where('tag_id', $tid)->exists();
                if (!$already) {
                    $tag = Tag::find($tid);
                    if ($tag) $tag->attachToConversation($conversationId);
                }
            }
        }

        // 4) Теперь обработаем branch tags, которые НЕ принадлежат никакой группе (обычные теги)
        $branchUngrouped = array_diff($branchTagIds, \Modules\NobilikBranches\Entities\TagGroupTag::whereIn('tag_id', $branchTagIds)->pluck('tag_id')->toArray());

        foreach ($branchUngrouped as $tid) {
            $already = ConversationTag::where('conversation_id', $conversationId)
                ->where('tag_id', $tid)->exists();
            if (!$already) {
                $tag = Tag::find($tid);
                if ($tag) $tag->attachToConversation($conversationId);
            }
        }

        return ['status' => 'success', 'message' => 'Branch attached and tags applied'];
    }
}

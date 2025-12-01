<?php

namespace Modules\NobilikBranches\Listeners;

use App\Conversation;
use Illuminate\Support\Facades\Log;
use Modules\Tags\Entities\Tag;
use Modules\NobilikGroupedTags\Entities\TagGroup;

class BranchAttachListener
{
    public function handle(Conversation $conversation, $branch)
    {
        Log::info('[BRANCH] Attaching branch', [
            'conversation' => $conversation->id,
            'branch' => $branch->id,
        ]);

        $branchTagIds = $branch->tags()->pluck('tags.id')->toArray();
        $conversationTagIds = $conversation->tags()->pluck('tags.id')->toArray();

        foreach ($branchTagIds as $tagId) {

            $tag = Tag::find($tagId);
            if (!$tag) continue;

            $group = TagGroup::findByTag($tagId);

            if ($group && $group->max_tags_for_conversation) {
                $conversation->tags()->detach(
                    $group->tags()->pluck('tags.id')->toArray()
                );
            }

            $conversation->tags()->syncWithoutDetaching([$tagId]);
        }
    }
}

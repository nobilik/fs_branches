<?php

namespace Modules\NobilikBranches\Entities;

// use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Model;

use Modules\NobilikBranches\Entities\Branch; 
use App\Conversation; 

class ConversationBranch extends Model
// class ConversationBranch extends Pivot
{
    protected $table = 'conversation_branch';
    // public $timestamps = true; // Важно, чтобы проставлялись таймштампы
    
    // Поля, которые можно заполнять
    protected $fillable = ['conversation_id', 'branch_id', 'attached_by'];

    // Определяем отношения для удобства
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }
}
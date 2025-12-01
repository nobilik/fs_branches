<?php

namespace Modules\NobilikBranches\Entities;

use Illuminate\Database\Eloquent\Model;

class BranchTag extends Model
{
    protected $table = 'branch_tag'; // pivot? or actual table if exists

    public function getUrl()
    {
        return route('branches.index', ['tag' => $this->name]);
    }
}

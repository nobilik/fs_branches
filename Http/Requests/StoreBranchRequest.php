<?php

namespace Modules\NobilikBranches\Http\Requests;

// Наследуем от базового класса
class StoreBranchRequest extends BaseBranchRequest 
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        // Используем общие правила
        return $this->commonRules();
    }
}
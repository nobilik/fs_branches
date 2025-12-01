<?php

namespace Modules\NobilikBranches\Http\Requests;

// Наследуем от базового класса
class UpdateBranchRequest extends BaseBranchRequest 
{
    public function authorize()
    {
        // Здесь можно добавить проверку, что пользователь имеет право обновлять этот филиал
        return true;
    }

    public function rules()
    {
        // Используем общие правила
        return $this->commonRules();
        
        // Если нужны дополнительные правила для обновления, объедините их:
        /*
        return array_merge($this->commonRules(), [
            // 'some_field' => 'unique:table,column,' . $this->route('branch')->id,
        ]);
        */
    }
}
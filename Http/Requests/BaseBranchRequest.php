<?php

namespace Modules\NobilikBranches\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseBranchRequest extends FormRequest
{
    /**
     * Преобразует поля перед выполнением правил валидации.
     */
    protected function prepareForValidation()
    {
        // 1. Декодирование поля 'tags' (из JSON-строки в массив ID)
        if ($this->has('tags') && is_string($this->tags)) {
            $decodedTags = json_decode($this->tags, true);
            if (is_array($decodedTags)) {
                $this->merge([
                    'tags' => $decodedTags,
                ]);
            }
        }
        
        // 2. Декодирование поля 'address_meta' (из JSON-строки в массив)
        if ($this->has('address_meta') && is_string($this->address_meta)) {
            $decodedMeta = json_decode($this->address_meta, true);
            if (is_array($decodedMeta)) {
                 $this->merge([
                    'address_meta' => $decodedMeta,
                ]);
            }
        }
    }
    
    /**
     * Общие правила валидации для создания и обновления.
     */
    protected function commonRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'address_guid' => 'required|string',
            'full_address' => 'required|string',
            'comment' => 'nullable|string|max:1000',
            
            // Теперь tags и address_meta будут массивами благодаря prepareForValidation()
            'tags' => 'nullable|array', 
            'tags.*' => 'integer|exists:tags,id',
            
            'address_meta' => 'nullable|array', 
        ];
    }
}
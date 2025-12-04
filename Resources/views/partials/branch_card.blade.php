<div class="branch-card">
    <h4 class="branch-title">{{ $branch->name }}</h4>

    <p class="branch-address-text">
        <strong>Адрес:</strong> {{ $branch->full_address ?? $branch->address->full_address ?? 'Адрес не указан' }}
    </p>

    {{-- Вывод тегов --}}
    <div class="branch-tags-wrapper">
        @foreach($branch->tags as $tag)
            {{-- Класс tag-c-{{ $tag->color }} должен быть определен глобально. --}}
            <span class="branch-tag tag-c-{{ $tag->color }}">
                {{ $tag->name }}
            </span>
        @endforeach
    </div>

    {{-- Вывод комментария --}}
    <p class="branch-comment-text">
        <strong>Комментарий:</strong> {{ $branch->comment ?? 'Нет комментария' }}
    </p>

    <div class="branch-actions">
        
        {{-- Кнопка "Изменить" (синяя) --}}
        <a href="{{ route('branches.edit', $branch) }}" class="branch-btn branch-btn-edit">
            Изменить
        </a>
            
        <button type="button" 
                class="branch-btn branch-btn-delete js-branch-delete"
                data-id="{{ $branch->id }}">
            Удалить
        </button>
    </div>

</div>
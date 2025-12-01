<div class="branch-create-form-wrapper">
    <div class="branch-create-form">
        <div class="mb-3">
            <label class="form-label">Название</label>
            <input type="text" name="name"
                value="{{ old('name', $branch->name ?? '') }}"
                class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Адрес</label>
            <input type="text"
                name="full_address"
                value="{{ old('full_address', $branch->address->full_address ?? '') }}"
                class="form-control address-autocomplete"
                data-guid-input="#address_guid" data-meta-input="#address_meta"> 

            <input type="hidden"
                id="address_guid"
                name="address_guid"
                value="{{ old('address_guid', $branch->address->guid ?? '') }}">
        
            <input type="hidden"
                id="address_meta"
                name="address_meta"
                value="{{ old('address_meta', $branch->address->meta ?? '') }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Комментарий</label>
            <textarea name="comment" class="form-control">{{ old('comment', $branch->comment ?? '') }}</textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Теги</label>

            {{-- Список тегов --}}
            <div id="branch-tags" class="d-flex flex-wrap gap-2 mb-2">
                @if(isset($branch))
                    @foreach($branch->tags as $tag)
                        <span class="badge bg-primary branch-tag-badge" 
                            data-id="{{ $tag->id }}"
                            style="cursor:pointer;">
                            {{ $tag->name }}
                            <span class="ms-1 text-white remove-tag" data-id="{{ $tag->id }}" style="cursor:pointer;">×</span>
                        </span>
                    @endforeach
                @endif
            </div>

            <input type="hidden" name="tags" id="selected-tags-input">

            {{-- Поле автодобавления тегов --}}
            <input type="text"
                id="branch-tag-input"
                class="form-control"
                placeholder="Введите тег..."
                autocomplete="off">

            {{-- Контейнер подсказок --}}
            <div id="tag-suggestions"
                class="list-group position-absolute mt-1 shadow"
                style="z-index: 2000; display:none; max-height: 200px; overflow-y: auto;">
            </div>

        </div>

    </div>
</div>

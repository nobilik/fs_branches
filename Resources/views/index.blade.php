@extends('layouts.app')

@section('content')

<div class="branch-container">

    {{-- 1. Заголовок (Отдельный ряд) --}}
    <div class="branch-section-spacing">
        <h3>Объекты</h3>
    </div>

{{-- 2. Форма единой фильтрации (Во всю ширину) --}}
    <div class="branch-section-spacing">
        <div class="branch-filter-card">
            <form method="GET" class="branch-filter-grid branch-filter-grid-single-field">
                
                {{-- Единое поле для поиска по названию, адресу и тегу --}}
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Поиск по названию, адресу или тегу..."
                       class="branch-form-input search-input">
                
                <button class="branch-form-button">Поиск</button>
                
            </form>
        </div>
    </div>

    {{-- 3. Кнопка создания объекта (Справа) --}}
    <div class="branch-create-button-row">
        <a href="{{ route('branches.create') }}" class="branch-btn-primary">Создать объект</a>
    </div>

    {{-- 4. Список объектов (Две карточки в ряд) --}}
    <div class="branch-list-grid">
        @foreach($branches as $branch)
            {{-- Предполагается, что branch_card содержит минимальную разметку без внешних классов сетки --}}
            <div>
                @include('nobilikbranches::partials.branch_card', ['branch' => $branch])
            </div>
        @endforeach
    </div>

    {{-- 5. Пагинация --}}
    <div class="branch-pagination-wrapper">
        {{ $branches->links() }}
    </div>

</div>
@endsection
@extends('layouts.app')

@section('content')

<div class="app-container">

    {{-- 1. Заголовок (Отдельный ряд) --}}
    <div class="section-spacing">
        <h3>Филиалы</h3>
    </div>

{{-- 2. Форма единой фильтрации (Во всю ширину) --}}
    <div class="section-spacing">
        <div class="filter-card">
            <form method="GET" class="filter-grid filter-grid-single-field">
                
                {{-- Единое поле для поиска по названию, адресу и тегу --}}
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Поиск по названию, адресу или тегу..."
                       class="form-input search-input">
                
                <button class="form-button">Поиск</button>
                
            </form>
        </div>
    </div>

    {{-- 3. Кнопка создания филиала (Справа) --}}
    <div class="create-button-row">
        <a href="{{ route('branches.create') }}" class="btn-primary">Создать филиал</a>
    </div>

    {{-- 4. Список филиалов (Две карточки в ряд) --}}
    <div class="branch-list-grid">
        @foreach($branches as $branch)
            {{-- Предполагается, что branch_card содержит минимальную разметку без внешних классов сетки --}}
            <div>
                @include('nobilikbranches::partials.branch_card', ['branch' => $branch])
            </div>
        @endforeach
    </div>

    {{-- 5. Пагинация --}}
    <div class="pagination-wrapper">
        {{ $branches->links() }}
    </div>

</div>
@endsection
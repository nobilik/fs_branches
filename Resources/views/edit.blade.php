@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-3">Редактировать филиал: {{ $branch->name }}</h3>

    <form action="{{ route('branches.update', $branch) }}" method="POST">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="hidden" name="_method" value="PUT">
        @include('nobilikbranches::partials.form', ['branch' => $branch])
        
        <div class="form-actions-container">
            
            {{-- Кнопка "Назад" (серый стиль) --}}
            <a href="{{ route('branches.index') }}" 
               class="app-btn app-btn-secondary app-mr-10">
                Назад
            </a>
            
            {{-- Кнопка "Обновить" (зеленый стиль) --}}
            <button type="submit" 
                    class="app-btn app-btn-success">
                Обновить
            </button>
        </div>
    </form>
</div>
@endsection
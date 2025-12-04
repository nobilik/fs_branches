@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-3">Создать филиал</h3>

    <form action="{{ route('branches.store') }}" method="POST">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="hidden" name="_method" value="POST">
        @include('nobilikbranches::partials.form')

        <div class="branch-form-actions-container">
            
            {{-- Кнопка "Назад" (серый стиль) --}}
            <a href="{{ route('branches.index') }}" 
               class="branch-btn branch-btn-secondary branch-mr-10">
                Назад
            </a>
            
            {{-- Кнопка "Сохранить" (зеленый стиль) --}}
            <button type="submit" 
                    class="branch-btn branch-btn-success">
                Сохранить
            </button>
        </div>
         
    </form>
</div>
@endsection

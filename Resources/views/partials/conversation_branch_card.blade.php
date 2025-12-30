@php
    $conversationId = $conversation->id;
    
    $BranchModel = \Modules\NobilikBranches\Entities\Branch::class;
    $branch = $BranchModel::getByConversationId($conversationId);
@endphp

@if ($branch)
    <div class="branch-card-select mb-3">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <strong>Объект: {{ $branch->name }}</strong>
                <div class="text-muted small">
                    {{ $branch->address->full_address ?? 'Адрес не указан' }}
                </div>
                <div class="branch-comment-text">
                    <strong>Комментарий:</strong> {{ $branch->comment ?? 'Нет комментария' }}
                </div>
            </div>

            <div class="d-flex flex-column gap-2">
                <button class="branch-modal__submit-btn js-open-branch-modal"
                        data-conversation-id="{{ $conversation->id }}">
                    Сменить
                </button>
            </div>
        </div>
    </div>
@else
    <div class="alert alert-warning d-flex justify-content-between align-items-center">
        <div>Объект не прикреплён.</div>
        <button class="branch-modal__submit-btn js-open-branch-modal"
                data-conversation-id="{{ $conversation->id }}">
            Выбрать объект
        </button>
    </div>
@endif

<div class="form-group">
    <label class="col-sm-2 control-label">{{ __('Объект') }}</label>

    <div class="col-sm-9">
        <input type="hidden" name="branch_id" id="nb-selected-branch-id" value="{{ $selectedBranchId ?: '' }}">

        <div class="branch-card-select">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong>{{ __('Выбранный объект:') }}</strong>
                    <div class="text-muted small" id="nb-selected-branch-name" data-empty-text="{{ __('Не выбран') }}">
                        @if ($selectedBranch)
                            {{ $selectedBranch->name }}
                        @else
                            {{ __('Не выбран') }}
                        @endif
                    </div>
                </div>
                <div>
                    <button
                        type="button"
                        class="branch-modal__submit-btn js-open-branch-modal"
                        data-new-conversation="1"
                    >
                        @if ($selectedBranchId)
                            {{ __('Сменить') }}
                        @else
                            {{ __('Выбрать объект') }}
                        @endif
                    </button>
                </div>
            </div>
        </div>

        <p class="help-block">{{ __('Филиал будет привязан при сохранении новой заявки.') }}</p>
    </div>
</div>

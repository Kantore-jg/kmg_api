<?php

namespace App\Http\Requests\Place;

use App\Enums\PlaceStatus;
use App\Models\MarketBlock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePlaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_places') ?? false;
    }

    public function rules(): array
    {
        return [
            'market_id' => ['required', 'exists:markets,id'],
            'market_block_id' => ['required', 'exists:market_blocks,id'],
            'number' => [
                'required', 'string', 'max:50',
                Rule::unique('places')->where(fn ($q) => $q->where('market_id', $this->market_id)),
            ],
            'category' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(PlaceStatus::class)],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $user = $this->user();
            $marketId = (int) $this->input('market_id');

            if ($user?->managed_market_id && ! $user->can('manage_markets')) {
                if ($marketId !== (int) $user->managed_market_id) {
                    $validator->errors()->add(
                        'market_id',
                        'Vous ne pouvez créer des emplacements que pour votre marché assigné.',
                    );
                }
            }

            $blockId = $this->input('market_block_id');
            if ($blockId) {
                $block = MarketBlock::find($blockId);
                if (! $block || (int) $block->market_id !== $marketId) {
                    $validator->errors()->add(
                        'market_block_id',
                        'Ce bloc n\'appartient pas au marché sélectionné.',
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'number.unique' => 'Un emplacement avec ce code existe déjà dans ce marché.',
            'status.enum' => 'Le statut de l emplacement est invalide.',
        ];
    }
}

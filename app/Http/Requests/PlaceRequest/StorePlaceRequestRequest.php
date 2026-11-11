<?php

namespace App\Http\Requests\PlaceRequest;

use App\Enums\PlaceStatus;
use App\Models\Place;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePlaceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'market_id' => ['required', 'exists:markets,id'],
            'place_id' => ['nullable', 'exists:places,id'],
            'merchant_name' => ['required', 'string', 'max:255'],
            'merchant_phone' => ['required', 'string', 'max:20'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->user()?->hasAssignedPlace()) {
                $validator->errors()->add(
                    'user',
                    'Vous avez déjà une place assignée. Vous ne pouvez pas faire une nouvelle demande.',
                );

                return;
            }

            $placeId = $this->input('place_id');
            if (! $placeId) {
                return;
            }

            $place = Place::query()->find($placeId);
            if (! $place) {
                return;
            }

            if ((int) $place->market_id !== (int) $this->input('market_id')) {
                $validator->errors()->add(
                    'place_id',
                    'Cet emplacement n\'appartient pas au marché sélectionné.',
                );
            }

            if ($place->status !== PlaceStatus::Available) {
                $validator->errors()->add(
                    'place_id',
                    'Cet emplacement est déjà occupé ou indisponible.',
                );
            }
        });
    }
}

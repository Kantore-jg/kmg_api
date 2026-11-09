<?php

namespace App\Services;

use App\Models\Market;
use App\Models\MarketBlock;
use App\Models\Place;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class MarketBlockService
{
    public function listByMarket(Market $market): Collection
    {
        return MarketBlock::query()
            ->where('market_id', $market->id)
            ->withCount('places')
            ->orderBy('name')
            ->get();
    }

    public function create(Market $market, array $data): MarketBlock
    {
        $block = MarketBlock::create([
            'market_id' => $market->id,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'description' => $data['description'] ?? null,
            'rent_amount' => $data['rent_amount'] ?? 0,
            'total_places' => 0,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return $block->loadCount('places');
    }

    public function update(MarketBlock $block, array $data): MarketBlock
    {
        $block->update($data);

        return $block->fresh()->loadCount('places');
    }

    public function delete(MarketBlock $block): void
    {
        if ($block->places()->exists()) {
            throw ValidationException::withMessages([
                'block' => ['Ce bloc contient encore des emplacements. Supprimez ou réaffectez-les d\'abord.'],
            ]);
        }

        $block->delete();
    }

    public function syncPlacesCount(MarketBlock $block): void
    {
        $block->update([
            'total_places' => Place::where('market_block_id', $block->id)->count(),
        ]);
    }
}

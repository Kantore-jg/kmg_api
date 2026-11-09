<?php

namespace App\Repositories\Implementations;

use App\Enums\PlaceStatus;
use App\Models\Market;
use App\Repositories\Contracts\MarketRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class MarketRepository implements MarketRepositoryInterface
{
    private function applyPlaceCounts($query)
    {
        return $query->withCount([
            'places',
            'places as occupied_places_count' => function ($q) {
                $q->whereIn('status', [
                    PlaceStatus::Occupied->value,
                    PlaceStatus::Reserved->value,
                ]);
            },
        ]);
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->applyPlaceCounts(Market::query());

        if (! empty($filters['province'])) {
            $query->where('province', $filters['province']);
        } elseif (! empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('province', 'like', "%{$search}%")
                    ->orWhere('commune', 'like', "%{$search}%")
                    ->orWhere('zone', 'like', "%{$search}%")
                    ->orWhere('colline', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        $sort = $filters['sort'] ?? 'name';
        $direction = $filters['direction'] ?? 'asc';
        $query->orderBy($sort, $direction);

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Market
    {
        return $this->applyPlaceCounts(
            Market::query()->with(['blocks', 'places.chief'])
        )->find($id);
    }

    public function findBySlug(string $slug): ?Market
    {
        return $this->applyPlaceCounts(
            Market::query()->with(['blocks', 'places.chief'])
        )->where('slug', $slug)->first();
    }

    public function getPopular(int $limit = 5): Collection
    {
        return $this->applyPlaceCounts(
            Market::query()->where('is_active', true)
        )
            ->orderByDesc('visit_count')
            ->limit($limit)
            ->get();
    }

    public function create(array $data): Market
    {
        unset($data['total_places'], $data['occupied_places']);
        $data['total_places'] = 0;
        $data['occupied_places'] = 0;

        return Market::create($data);
    }

    public function update(Market $market, array $data): Market
    {
        unset($data['total_places'], $data['occupied_places']);
        $market->update($data);

        return $this->findById($market->id) ?? $market->fresh();
    }

    public function delete(Market $market): bool
    {
        return (bool) $market->delete();
    }
}

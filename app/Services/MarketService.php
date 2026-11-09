<?php

namespace App\Services;

use App\Models\Market;
use App\Repositories\Contracts\MarketRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class MarketService
{
    public function __construct(
        private MarketRepositoryInterface $marketRepository,
        private FileStorageService $fileStorage,
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->marketRepository->paginate($filters, $perPage);
    }

    public function getById(int $id): ?Market
    {
        return $this->marketRepository->findById($id);
    }

    public function getPopular(int $limit = 5): Collection
    {
        return $this->marketRepository->getPopular($limit);
    }

    public function getStatistics(Market $market): array
    {
        $market->loadCount([
            'places',
            'places as occupied_places_count' => function ($q) {
                $q->whereIn('status', ['occupied', 'reserved']);
            },
        ]);

        $total = (int) ($market->places_count ?? 0);
        $occupied = (int) ($market->occupied_places_count ?? 0);

        return [
            'total_places' => $total,
            'occupied_places' => $occupied,
            'available_places' => max(0, $total - $occupied),
            'occupancy_rate' => $total > 0
                ? round(($occupied / $total) * 100, 2)
                : 0,
            'places_count' => $total,
            'visit_count' => $market->visit_count,
        ];
    }

    public function recordVisit(Market $market, ?int $userId = null, ?string $ip = null): void
    {
        $market->increment('visit_count');
    }

    public function create(array $data, ?UploadedFile $image = null, ?UploadedFile $coverImage = null): Market
    {
        if (Market::query()->exists()) {
            throw ValidationException::withMessages([
                'market' => ['Un seul marché peut être configuré.'],
            ]);
        }

        $data['city'] = $data['city'] ?? ($data['province'] ?? null);

        if ($image) {
            $data['image'] = $this->fileStorage->store($image, 'markets/images');
        }

        if ($coverImage) {
            $data['cover_image'] = $this->fileStorage->store($coverImage, 'markets/covers');
        }

        return $this->marketRepository->create($data);
    }

    public function update(Market $market, array $data, ?UploadedFile $image = null, ?UploadedFile $coverImage = null): Market
    {
        $data['city'] = $data['city'] ?? ($data['province'] ?? $market->city);

        if ($image) {
            $this->fileStorage->delete($market->image);
            $data['image'] = $this->fileStorage->store($image, 'markets/images');
        }

        if ($coverImage) {
            $this->fileStorage->delete($market->cover_image);
            $data['cover_image'] = $this->fileStorage->store($coverImage, 'markets/covers');
        }

        return $this->marketRepository->update($market, $data);
    }

    public function delete(Market $market): bool
    {
        if (Market::query()->count() <= 1) {
            throw ValidationException::withMessages([
                'market' => ['Le marché unique ne peut pas être supprimé.'],
            ]);
        }

        if ($market->places()->exists()) {
            throw ValidationException::withMessages([
                'market' => ['Ce marché contient encore des emplacements. Supprimez-les d\'abord.'],
            ]);
        }

        if ($market->admins()->exists()) {
            throw ValidationException::withMessages([
                'market' => ['Ce marché est encore géré par un administrateur. Réaffectez-le d\'abord.'],
            ]);
        }

        return $this->marketRepository->delete($market);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\WasteCategory;
use App\Models\WasteReport;
use App\Support\ReportLabels;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(): View
    {
        $filters = request()->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $categories = WasteCategory::query()
            ->withCount(['reports', 'aiPredictedReports'])
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereIn('slug', array_keys(array_filter(
                            ReportLabels::CATEGORIES,
                            fn (string $label): bool => Str::contains($label, $search, ignoreCase: true),
                        )));
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $summary = [
            'categories' => WasteCategory::query()->count(),
            'usedCategories' => WasteCategory::query()
                ->where(fn ($query) => $query->whereHas('reports')->orWhereHas('aiPredictedReports'))
                ->count(),
            'linkedReports' => WasteReport::query()
                ->where(fn ($query) => $query->whereNotNull('category_id')->orWhereNotNull('ai_category_id'))
                ->count(),
        ];

        return view('admin.categories.index', compact('categories', 'summary'));
    }

    public function create(): View
    {
        return view('admin.categories.create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $category = WasteCategory::query()->create([
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['name']),
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('admin.categories.index')
            ->with('status', "Kategori {$category->name} berhasil ditambahkan.");
    }

    public function edit(WasteCategory $category): View
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(UpdateCategoryRequest $request, WasteCategory $category): RedirectResponse
    {
        $validated = $request->validated();
        $category->update([
            'name' => $category->isDefault() ? $category->name : $validated['name'],
            'slug' => $category->isDefault() ? $category->slug : $this->uniqueSlug($validated['name'], $category->id),
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('admin.categories.index')
            ->with('status', "Kategori {$category->name} berhasil diperbarui.");
    }

    public function destroy(WasteCategory $category): RedirectResponse
    {
        $result = DB::transaction(function () use ($category): string {
            $lockedCategory = WasteCategory::query()->lockForUpdate()->findOrFail($category->id);

            if ($lockedCategory->isDefault()) {
                return 'default';
            }

            if ($lockedCategory->reports()->exists() || $lockedCategory->aiPredictedReports()->exists()) {
                return 'in_use';
            }

            $lockedCategory->delete();

            return 'deleted';
        });

        if ($result === 'default') {
            return redirect()->route('admin.categories.index')
                ->with('error', 'Kategori bawaan tidak dapat dihapus. Anda dapat mengubah deskripsinya.');
        }

        if ($result === 'in_use') {
            return redirect()->route('admin.categories.index')
                ->with('error', "Kategori {$category->name} masih digunakan laporan dan tidak dapat dihapus.");
        }

        return redirect()->route('admin.categories.index')
            ->with('status', "Kategori {$category->name} berhasil dihapus.");
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'category';
        $slug = $baseSlug;
        $suffix = 2;

        while (WasteCategory::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}

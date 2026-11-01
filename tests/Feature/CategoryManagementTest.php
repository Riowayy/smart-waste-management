<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WasteCategory;
use App\Models\WasteReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_category_management_and_sidebar_marks_it_active(): void
    {
        $admin = $this->admin();
        $category = $this->category('Organic Waste');
        $this->report($this->member(), ['category_id' => $category->id]);

        $response = $this->actingAs($admin)
            ->get(route('admin.categories.index'));

        $response
            ->assertOk()
            ->assertSee('Manajemen Kategori')
            ->assertSee('Organic Waste')
            ->assertViewHas('summary', fn (array $summary): bool => $summary === [
                'categories' => 1,
                'usedCategories' => 1,
                'linkedReports' => 1,
            ]);

        $this->assertMatchesRegularExpression(
            '/<a href="[^"]*\/admin\/categories"[^>]*aria-current="page"[^>]*>/',
            $response->getContent(),
        );
    }

    public function test_guest_is_redirected_and_regular_user_is_forbidden_from_category_management(): void
    {
        $this->get(route('admin.categories.index'))->assertRedirect(route('login'));

        $user = $this->member();

        $this->actingAs($user)
            ->get(route('admin.categories.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.categories.store'), ['name' => 'Unauthorized Category'])
            ->assertForbidden();
    }

    public function test_admin_can_create_category_and_slug_is_generated_from_name(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.categories.store'), [
                'name' => 'Household Organic Waste',
                'description' => 'Sisa bahan alami dari rumah tangga.',
            ])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('waste_categories', [
            'name' => 'Household Organic Waste',
            'slug' => 'household-organic-waste',
            'description' => 'Sisa bahan alami dari rumah tangga.',
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.categories.store'), ['name' => 'Household-Organic Waste'])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('waste_categories', [
            'name' => 'Household-Organic Waste',
            'slug' => 'household-organic-waste-2',
        ]);
    }

    public function test_category_name_is_required(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.categories.create'))
            ->post(route('admin.categories.store'), ['description' => 'Deskripsi saja'])
            ->assertRedirect(route('admin.categories.create'))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('waste_categories', 0);
    }

    public function test_duplicate_category_name_is_rejected(): void
    {
        $this->category('Paper Waste');
        $plastic = $this->category('Plastic Waste');

        $this->actingAs($this->admin())
            ->from(route('admin.categories.create'))
            ->post(route('admin.categories.store'), ['name' => 'Paper Waste'])
            ->assertRedirect(route('admin.categories.create'))
            ->assertSessionHasErrors('name');

        $this->actingAs($this->admin())
            ->from(route('admin.categories.edit', $plastic))
            ->put(route('admin.categories.update', $plastic), ['name' => 'Paper Waste'])
            ->assertRedirect(route('admin.categories.edit', $plastic))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('waste_categories', 2);
    }

    public function test_admin_can_update_category_and_its_generated_slug(): void
    {
        $category = $this->category('Organic Waste');

        $this->actingAs($this->admin())
            ->put(route('admin.categories.update', $category), [
                'name' => 'Food Scraps',
                'description' => 'Sisa makanan dan bahan organik.',
            ])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('waste_categories', [
            'id' => $category->id,
            'name' => 'Food Scraps',
            'slug' => 'food-scraps',
            'description' => 'Sisa makanan dan bahan organik.',
        ]);
    }

    public function test_admin_can_delete_category_that_is_not_used_by_reports(): void
    {
        $category = $this->category('Unused Category');

        $this->actingAs($this->admin())
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('waste_categories', ['id' => $category->id]);
    }

    public function test_category_used_as_confirmed_or_ai_prediction_cannot_be_deleted(): void
    {
        $confirmedCategory = $this->category('Confirmed Category');
        $predictedCategory = $this->category('Predicted Category');
        $owner = $this->member();
        $report = $this->report($owner, [
            'category_id' => $confirmedCategory->id,
            'ai_category_id' => $predictedCategory->id,
        ]);

        foreach ([$confirmedCategory, $predictedCategory] as $category) {
            $this->actingAs($this->admin())
                ->delete(route('admin.categories.destroy', $category))
                ->assertRedirect(route('admin.categories.index'))
                ->assertSessionHas('error');

            $this->assertDatabaseHas('waste_categories', ['id' => $category->id]);
            $this->assertDatabaseHas('waste_reports', ['id' => $report->id]);
        }
    }

    public function test_category_list_uses_real_confirmed_and_ai_report_counts_without_double_counting_summary(): void
    {
        $organic = $this->category('Organic Waste');
        $plastic = $this->category('Plastic Waste');
        $owner = $this->member();
        $this->report($owner, [
            'category_id' => $organic->id,
            'ai_category_id' => $plastic->id,
        ]);
        $this->report($owner, ['ai_category_id' => $organic->id]);

        $this->actingAs($this->admin())
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertViewHas('summary', fn (array $summary): bool => $summary === [
                'categories' => 2,
                'usedCategories' => 2,
                'linkedReports' => 2,
            ])
            ->assertViewHas('categories', function ($categories): bool {
                $organic = $categories->firstWhere('slug', 'organic-waste');

                return $organic?->reports_count === 1 && $organic?->ai_predicted_reports_count === 1;
            });
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        return $admin;
    }

    private function member(): User
    {
        return User::factory()->create();
    }

    private function category(string $name): WasteCategory
    {
        return WasteCategory::query()->create([
            'name' => $name,
            'slug' => str($name)->slug(),
        ]);
    }

    /** @param array{category_id?: int|null, ai_category_id?: int|null} $attributes */
    private function report(User $owner, array $attributes = []): WasteReport
    {
        return WasteReport::query()->create(array_merge([
            'user_id' => $owner->id,
            'description' => 'Laporan uji kategori',
            'image_path' => 'reports/category-test.jpg',
        ], $attributes));
    }
}
